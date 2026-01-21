<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = $_GET['teacher_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$teacherId) {
    echo json_encode([
        'success' => false,
        'message' => 'Teacher ID is required'
    ]);
    exit();
}

// Build filter URL
$filter = 'filter[teacher_id][_eq]=' . urlencode($teacherId);
if ($date) {
    $filter .= '&filter[date][_eq]=' . urlencode($date);
}

// Add fields parameter to ensure we get student_id, teacher_id, and semester
$fields = 'id,teacher_id,student_id,date,semester,status';
$filter .= '&fields=' . urlencode($fields);

// Get attendance records for this teacher
$url = DIRECTUS_BASE_URL . '/items/attendance?' . $filter;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

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
    $attendanceRecords = $data['data'] ?? [];
    
    // Ensure all records have teacher_id, student_id, and semester
    foreach ($attendanceRecords as &$record) {
        // Ensure teacher_id is present
        if (!isset($record['teacher_id']) && isset($record['teacher'])) {
            $record['teacher_id'] = is_array($record['teacher']) ? $record['teacher']['id'] : $record['teacher'];
        }
        if (!isset($record['teacher_id']) && $teacherId) {
            $record['teacher_id'] = $teacherId;
        }
        
        // Ensure student_id is present
        if (!isset($record['student_id']) && isset($record['student'])) {
            $record['student_id'] = is_array($record['student']) ? $record['student']['id'] : $record['student'];
        }
        
        // Ensure semester is present (may come from student relationship)
        if (!isset($record['semester']) && isset($record['student']) && is_array($record['student'])) {
            $record['semester'] = $record['student']['semester'] ?? null;
        }
    }
    unset($record); // Unset reference
    
    // Count unique dates (how many times teacher took attendance)
    $uniqueDates = [];
    foreach ($attendanceRecords as $record) {
        if (isset($record['date'])) {
            $dateValue = is_array($record['date']) ? $record['date']['date'] : $record['date'];
            $dateKey = date('Y-m-d', strtotime($dateValue));
            if (!in_array($dateKey, $uniqueDates)) {
                $uniqueDates[] = $dateKey;
            }
        }
    }
    
    // Check if teacher took attendance today
    $hasAttendanceToday = false;
    if ($date) {
        $hasAttendanceToday = count($attendanceRecords) > 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_records' => count($attendanceRecords),
            'unique_dates' => count($uniqueDates),
            'attendance_dates' => $uniqueDates,
            'records' => $attendanceRecords,
            'has_attendance_today' => $hasAttendanceToday,
            'teacher_id' => $teacherId
        ]
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error fetching attendance',
        'http_code' => $httpCode
    ]);
}
?>



