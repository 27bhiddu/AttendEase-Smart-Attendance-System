<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

header('Content-Type: application/json');

$teacherId = $_GET['id'] ?? null;

// ✅ Required fields including is_verified
$fields = 'id,username,email,contact,is_verified';

if ($teacherId) {
    $url = DIRECTUS_BASE_URL . '/items/teachers/' . urlencode($teacherId) . '?fields=' . urlencode($fields);
} else {
    $url = DIRECTUS_BASE_URL . '/items/teachers?fields=' . urlencode($fields);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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

    if ($teacherId) {
        // Single teacher - ensure teacher_id is included
        $teacher = $data['data'] ?? $data;
        if (isset($teacher['id']) && !isset($teacher['teacher_id'])) {
            $teacher['teacher_id'] = $teacher['id'];
        }

        echo json_encode([
            'success' => true,
            'data'    => $teacher,
        ]);
    } else {
        // Multiple teachers - ensure teacher_id is included for each
        $teachers = $data['data'] ?? [];
        foreach ($teachers as &$teacher) {
            if (isset($teacher['id']) && !isset($teacher['teacher_id'])) {
                $teacher['teacher_id'] = $teacher['id'];
            }
        }

        echo json_encode([
            'success' => true,
            'data'    => $teachers,
        ]);
    }
} else {
    $errorData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $errorData['errors'][0]['message'] ?? 'Error fetching teachers',
        'http_code' => $httpCode
    ]);
}
