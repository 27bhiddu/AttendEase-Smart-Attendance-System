<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teacherId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid teacher id',
    ]);
    exit();
}

$url = DIRECTUS_BASE_URL . '/items/teachers/' . $teacherId;

$payload = json_encode([
    'is_verified' => true,
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection error: ' . $curlError,
    ]);
    exit();
}

if ($httpCode === 200) {
    echo json_encode([
        'success' => true,
        'message' => 'Teacher verified successfully',
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error verifying teacher',
        'http_code' => $httpCode,
    ]);
}
