<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit();
}

// Validate required fields
if (empty($data['name']) || empty($data['roll_no']) || empty($data['department'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: name, roll_no, department'
    ]);
    exit();
}

// Validate classes
$totalClasses = isset($data['total_classes']) ? intval($data['total_classes']) : 0;
$presentClasses = isset($data['present_classes']) ? intval($data['present_classes']) : 0;

if ($presentClasses > $totalClasses) {
    echo json_encode([
        'success' => false,
        'message' => 'Present classes cannot be greater than total classes'
    ]);
    exit();
}

$payload = [
    'name' => $data['name'],
    'roll_number' => $data['roll_no'], // Directus uses roll_number
    'branch' => $data['department'], // Directus uses branch
    'department' => $data['department'], // Also set department for compatibility
    'total_classes' => isset($data['total_classes']) ? intval($data['total_classes']) : 0,
    'present_classes' => isset($data['present_classes']) ? intval($data['present_classes']) : 0,
    'attendance_percentage' => isset($data['attendance_percentage']) ? floatval($data['attendance_percentage']) : 0
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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
    $result = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $result['data'] ?? $result,
        'message' => 'Student created successfully'
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error creating student',
        'http_code' => $httpCode
    ]);
}
?>

