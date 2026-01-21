<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = $_POST['teacher_id'] ?? $_GET['teacher_id'] ?? null;
$date = $_POST['date'] ?? $_GET['date'] ?? null;

if (!$teacherId || !$date) {
    echo json_encode([
        'success' => false,
        'message' => 'teacher_id and date are required'
    ]);
    exit();
}

// Build filter to fetch attendance records for the teacher on the date
$filter = 'filter[teacher_id][_eq]=' . urlencode($teacherId);
$filter .= '&filter[date][_eq]=' . urlencode($date);
$filter .= '&fields=' . urlencode('id');

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

if ($httpCode !== 200 && $httpCode !== 201) {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error fetching attendance',
        'http_code' => $httpCode
    ]);
    exit();
}

$data = json_decode($response, true);
$records = $data['data'] ?? [];

if (count($records) === 0) {
    echo json_encode([
        'success' => true,
        'message' => 'No attendance records found for specified teacher and date',
        'deleted' => 0
    ]);
    exit();
}

$deletedCount = 0;
$errors = [];

foreach ($records as $rec) {
    $id = $rec['id'] ?? null;
    if (!$id) continue;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/attendance/' . urlencode($id));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

    $delResp = curl_exec($ch);
    $delCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $delErr = curl_error($ch);
    curl_close($ch);

    if ($delErr) {
        $errors[] = 'Curl error deleting id ' . $id . ': ' . $delErr;
        continue;
    }

    if ($delCode === 200 || $delCode === 201 || $delCode === 204) {
        $deletedCount++;
    } else {
        $errData = json_decode($delResp, true);
        $errors[] = 'Failed to delete id ' . $id . ': ' . ($errData['errors'][0]['message'] ?? 'HTTP ' . $delCode);
    }
}

$success = count($errors) === 0;

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Deleted ' . $deletedCount . ' attendance records' : 'Deleted ' . $deletedCount . ' attendance records with some errors',
    'deleted' => $deletedCount,
    'errors' => $errors
]);

?>
