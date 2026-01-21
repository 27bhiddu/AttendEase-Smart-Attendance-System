<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];


/* =========================
   Helpers
   ======================== */
function semesterLabelToNumber($label) {
    return (int) filter_var($label, FILTER_SANITIZE_NUMBER_INT);
}

function semesterNumberToLabel($num) {
    return 'Semester ' . $num;
}

function maxSemForBranch($branch) {
    return ($branch === 'MCA') ? 4 : 8;
}


/* =========================
   GET : Load Students (Directus)
   ======================== */
if ($method === 'GET') {

    $branch   = trim($_GET['branch']   ?? '');
    $semester = trim($_GET['semester'] ?? '');

    if ($branch === '' || $semester === '') {
        echo json_encode(['success' => false, 'message' => 'Branch and semester are required.']);
        exit;
    }

    // semester is STRING: "Semester 1"
    $query = http_build_query([
        'filter[branch][_eq]'   => $branch,
        'filter[semester][_eq]' => $semester,
        'fields'                => 'id,name,email,roll_number,branch,semester,section',
        'sort'                  => 'roll_number',
        'limit'                 => 500,
    ]);

    $url = rtrim(DIRECTUS_BASE_URL, '/') . '/items/students?' . $query;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => getDirectusHeaders(),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode < 200 || $httpCode >= 300) {
        echo json_encode([
            'success'  => false,
            'message'  => 'Error loading students from Directus.',
            'status'   => $httpCode,
            'error'    => $err,
            'response' => json_decode($response, true)
        ]);
        exit;
    }

    $json  = json_decode($response, true);
    $items = $json['data'] ?? [];

    $data = array_map(function ($row) {
        return [
            'id'       => $row['id'],
            'name'     => $row['name'] ?? '',
            'email'    => $row['email'] ?? '',
            'roll_no'  => $row['roll_number'] ?? '',
            'branch'   => $row['branch'] ?? '',
            'semester' => $row['semester'] ?? '',
            'section'  => $row['section'] ?? '',
        ];
    }, $items);

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}


/* =========================
   POST : Promote (Directus)
   ======================== */
if ($method === 'POST') {

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];

    $action    = $payload['action'] ?? '';
    $studentId = (int) ($payload['student_id'] ?? 0);
    $branch    = trim($payload['branch'] ?? '');
    $semester  = trim($payload['semester'] ?? '');

    if ($action === '') {
        echo json_encode(['success' => false, 'message' => 'Action is required.']);
        exit;
    }

    /* ---------- Promote ONE Student ---------- */
    if ($action === 'promote_one') {

        if ($studentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
            exit;
        }

        $url = rtrim(DIRECTUS_BASE_URL, '/') . '/items/students/' . $studentId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => getDirectusHeaders(),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            echo json_encode(['success' => false, 'message' => 'Failed to load student.']);
            exit;
        }

        $stu = json_decode($response, true)['data'] ?? null;
        if (!$stu) {
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
            exit;
        }

        $currentNum = semesterLabelToNumber($stu['semester']);
        $maxSem     = maxSemForBranch($stu['branch']);

        if ($currentNum >= $maxSem) {
            echo json_encode(['success' => false, 'message' => 'Student already in final semester.']);
            exit;
        }

        $nextLabel = semesterNumberToLabel($currentNum + 1);

        $payloadU = json_encode(['semester' => $nextLabel]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => getDirectusHeaders(),
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $payloadU,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            echo json_encode(['success' => false, 'message' => 'Failed to promote student.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => "Student promoted to {$nextLabel}."]);
        exit;
    }

    /* ---------- Promote BATCH ---------- */
    if ($action === 'promote_batch') {

        if ($branch === '' || $semester === '') {
            echo json_encode(['success' => false, 'message' => 'Branch and semester are required.']);
            exit;
        }

        $currentNum = semesterLabelToNumber($semester);
        $maxSem     = maxSemForBranch($branch);

        if ($currentNum <= 0 || $currentNum >= $maxSem) {
            echo json_encode(['success' => false, 'message' => 'Invalid or final semester.']);
            exit;
        }

        $nextLabel = semesterNumberToLabel($currentNum + 1);

        $query = http_build_query([
            'filter[branch][_eq]'   => $branch,
            'filter[semester][_eq]' => $semester,
        ]);

        $url = rtrim(DIRECTUS_BASE_URL, '/') . '/items/students?' . $query;

        $payloadU = json_encode(['semester' => $nextLabel]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => getDirectusHeaders(),
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $payloadU,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            echo json_encode([
                'success'  => false,
                'message'  => 'Batch promotion failed.',
                'status'   => $httpCode,
                'response' => json_decode($response, true)
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => "Batch promoted to {$nextLabel}."
        ]);
        exit;
    }

    /* ---------- Promote SELECTED ---------- */
    if ($action === 'promote_selected') {

        $studentIds = $payload['student_ids'] ?? [];

        if (!is_array($studentIds) || count($studentIds) === 0) {
            echo json_encode(['success' => false, 'message' => 'No students selected.']);
            exit;
        }

        // IDs ko int me cast
        $cleanIds = array_map('intval', $studentIds);
        $idFilter = implode(',', $cleanIds); // e.g. 1,2,3

        // 1) Selected students ka current data
        $query = http_build_query([
            'filter[id][_in]' => $idFilter,
            'fields'          => 'id,branch,semester',
            'limit'           => 500,
        ]);

        $url = rtrim(DIRECTUS_BASE_URL, '/') . '/items/students?' . $query;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => getDirectusHeaders(),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to load selected students.',
                'status'  => $httpCode,
            ]);
            exit;
        }

        $json  = json_decode($response, true);
        $items = $json['data'] ?? [];

        if (!$items) {
            echo json_encode(['success' => false, 'message' => 'Selected students not found.']);
            exit;
        }

        // 2) Har eligible student ko next semester
        foreach ($items as $stu) {
            $id       = $stu['id'];
            $branchSt = $stu['branch']   ?? '';
            $semSt    = $stu['semester'] ?? '';

            $currentNum = semesterLabelToNumber($semSt);
            $maxSem     = maxSemForBranch($branchSt);

            if ($currentNum <= 0 || $currentNum >= $maxSem) {
                continue; // final semester wale skip
            }

            $nextLabel = semesterNumberToLabel($currentNum + 1);

            $urlOne   = rtrim(DIRECTUS_BASE_URL, '/') . '/items/students/' . $id;
            $payloadU = json_encode(['semester' => $nextLabel]);

            $ch = curl_init($urlOne);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER     => getDirectusHeaders(),
                CURLOPT_CUSTOMREQUEST  => 'PATCH',
                CURLOPT_POSTFIELDS     => $payloadU,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Selected students promoted (where eligible).'
        ]);
        exit;
    }

    // Unknown action
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}


/* =========================
   Method Not Allowed
   ======================== */
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
