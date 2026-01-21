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

if (empty($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Teacher ID is required'
    ]);
    exit();
}

// Validate required fields
if (empty($data['username']) || empty($data['email']) || empty($data['contact'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: username, email, contact'
    ]);
    exit();
}

$teacherId = $data['id'];
$payload = [
    'username' => $data['username'],
    'email' => $data['email'],
    'contact' => $data['contact']
];

// Only update password if provided
if (!empty($data['password'])) {
    $payload['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers/' . urlencode($teacherId));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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

if ($httpCode === 200 || $httpCode === 201 || $httpCode === 204) {
    $result = json_decode($response, true);
    echo json_encode([
        'success' => true,
        'data' => $result['data'] ?? $result,
        'message' => 'Teacher updated successfully'
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error updating teacher',
        'http_code' => $httpCode
    ]);
}
?>





