<?php
/**
 * API endpoint to fetch attendance records with full details
 * Returns: semester, student_id, teacher_id for each attendance record
 */
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = $_GET['teacher_id'] ?? null;
$studentId = $_GET['student_id'] ?? null;
$semester = $_GET['semester'] ?? null;
$date = $_GET['date'] ?? null;

// Build filter URL
$filters = [];
if ($teacherId) {
    $filters[] = 'filter[teacher_id][_eq]=' . urlencode($teacherId);
}
if ($studentId) {
    $filters[] = 'filter[student_id][_eq]=' . urlencode($studentId);
}
if ($semester) {
    $filters[] = 'filter[semester][_eq]=' . urlencode($semester);
}
if ($date) {
    $filters[] = 'filter[date][_eq]=' . urlencode($date);
}

// Request fields including relationships
$fields = 'id,teacher_id,student_id,date,semester,status,teacher.id,teacher.username,student.id,student.name,student.semester';

$url = DIRECTUS_BASE_URL . '/items/attendance';
if (!empty($filters)) {
    $url .= '?' . implode('&', $filters) . '&fields=' . urlencode($fields);
} else {
    $url .= '?fields=' . urlencode($fields);
}

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
    
    // Normalize the data to ensure semester, student_id, teacher_id are always present
    $normalizedRecords = [];
    foreach ($attendanceRecords as $record) {
        $normalized = [
            'id' => $record['id'] ?? null,
            'teacher_id' => $record['teacher_id'] ?? (is_array($record['teacher']) ? $record['teacher']['id'] : null),
            'student_id' => $record['student_id'] ?? (is_array($record['student']) ? $record['student']['id'] : null),
            'semester' => $record['semester'] ?? (is_array($record['student']) ? ($record['student']['semester'] ?? null) : null),
            'date' => is_array($record['date']) ? $record['date']['date'] : ($record['date'] ?? null),
            'status' => $record['status'] ?? null
        ];
        
        // Add teacher info if available
        if (isset($record['teacher']) && is_array($record['teacher'])) {
            $normalized['teacher'] = [
                'id' => $record['teacher']['id'] ?? null,
                'username' => $record['teacher']['username'] ?? null
            ];
        }
        
        // Add student info if available
        if (isset($record['student']) && is_array($record['student'])) {
            $normalized['student'] = [
                'id' => $record['student']['id'] ?? null,
                'name' => $record['student']['name'] ?? null,
                'semester' => $record['student']['semester'] ?? null
            ];
        }
        
        $normalizedRecords[] = $normalized;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $normalizedRecords,
        'count' => count($normalizedRecords)
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



