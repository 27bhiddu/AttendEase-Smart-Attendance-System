<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$teacherId) {
    echo json_encode([
        'success' => false,
        'message' => 'Teacher ID is required'
    ]);
    exit();
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers/' . urlencode($teacherId));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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
    echo json_encode([
        'success' => true,
        'message' => 'Teacher deleted successfully'
    ]);
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error deleting teacher',
        'http_code' => $httpCode
    ]);
}
?>





