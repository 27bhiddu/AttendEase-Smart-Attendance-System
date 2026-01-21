<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$studentId = $_GET['id'] ?? null;
$url = DIRECTUS_BASE_URL . '/items/students';

// Only request fields that exist and are accessible
// Removed: roll_no, department, attendance_percentage, status (may not exist or no permission)
$fields = 'id,name,roll_number,branch,semester,total_classes,present_classes';
$url .= '?fields=' . urlencode($fields);

if ($studentId) {
    $url = DIRECTUS_BASE_URL . '/items/students/' . urlencode($studentId) . '?fields=' . urlencode($fields);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection error: ' . $curlError
    ]);
    exit();
}

if ($httpCode === 200 || $httpCode === 201) {
    $data = json_decode($response, true);
    
    // Check if response is valid
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON response from Directus: ' . json_last_error_msg(),
            'raw_response' => substr($response, 0, 200),
            'url' => $url
        ]);
        exit();
    }
    
    // Check if data key exists - handle both array and object responses
    if (!isset($data['data'])) {
        // If response is directly an array, wrap it
        if (is_array($data) && isset($data[0])) {
            $data = ['data' => $data];
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No data key in response',
                'response_structure' => array_keys($data ?? []),
                'raw_response' => substr($response, 0, 500),
                'url' => $url
            ]);
            exit();
        }
    }
    
    if ($studentId) {
        // Single student - ensure student_id is included
        $student = $data['data'] ?? $data;
        if (isset($student['id']) && !isset($student['student_id'])) {
            $student['student_id'] = $student['id'];
        }
        // Add compatibility fields
        if (isset($student['roll_number']) && !isset($student['roll_no'])) {
            $student['roll_no'] = $student['roll_number'];
        }
        if (isset($student['branch']) && !isset($student['department'])) {
            $student['department'] = $student['branch'];
        }
        // Calculate attendance percentage if not present
        if (!isset($student['attendance_percentage'])) {
            $total = isset($student['total_classes']) ? (int)$student['total_classes'] : 0;
            $present = isset($student['present_classes']) ? (int)$student['present_classes'] : 0;
            $student['attendance_percentage'] = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        }
        echo json_encode([
            'success' => true,
            'data' => $student
        ]);
    } else {
        // Multiple students - ensure student_id is included for each
        $students = $data['data'] ?? [];
        
        // Handle case where data might be null or not an array
        if (!is_array($students)) {
            $students = [];
        }
        
        foreach ($students as &$student) {
            if (isset($student['id']) && !isset($student['student_id'])) {
                $student['student_id'] = $student['id'];
            }
            // Add compatibility fields
            if (isset($student['roll_number']) && !isset($student['roll_no'])) {
                $student['roll_no'] = $student['roll_number'];
            }
            if (isset($student['branch']) && !isset($student['department'])) {
                $student['department'] = $student['branch'];
            }
            // Calculate attendance percentage if not present
            if (!isset($student['attendance_percentage'])) {
                $total = isset($student['total_classes']) ? (int)$student['total_classes'] : 0;
                $present = isset($student['present_classes']) ? (int)$student['present_classes'] : 0;
                $student['attendance_percentage'] = $total > 0 ? round(($present / $total) * 100, 2) : 0;
            }
        }
        unset($student); // Unset reference
        
        echo json_encode([
            'success' => true,
            'data' => $students,
            'count' => count($students)
        ]);
    }
} else {
    $errorData = json_decode($response, true);
    $errorMessage = 'Error fetching students';
    
    if (isset($errorData['errors']) && is_array($errorData['errors']) && count($errorData['errors']) > 0) {
        $errorMessage = $errorData['errors'][0]['message'] ?? $errorMessage;
    } elseif (isset($errorData['message'])) {
        $errorMessage = $errorData['message'];
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'http_code' => $httpCode,
        'raw_response' => substr($response, 0, 500)
    ]);
}
?>
