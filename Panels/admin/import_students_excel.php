<?php
// File: api/import_students_excel.php

// =========================
// Basic includes
// =========================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Composer autoload (PhpSpreadsheet ke liye)
// Ensure vendor/autoload.php path sahi hai (project root ke hisaab se)
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Response JSON banane ke liye helper
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Sirf POST + file upload allow
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid request method. Use POST.'
    ], 405);
}

// Check: file aaya bhi hai ya nahi
if (!isset($_FILES['students_file']) || $_FILES['students_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse([
        'success' => false,
        'message' => 'Please upload a valid Excel file (students_file).'
    ], 400);
}

$fileTmpPath = $_FILES['students_file']['tmp_name'];
$fileName    = $_FILES['students_file']['name'];

// Extension check basic validation ke liye
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, ['xls', 'xlsx'])) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid file type. Only .xls and .xlsx are allowed.'
    ], 400);
}

try {
    // =========================
    // Excel read karna
    // =========================
    // PhpSpreadsheet se file load kar rahe
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray(null, true, true, true);
    // $rows ek array of rows hai: [ rowIndex => [ 'A' => cell1, 'B' => cell2, ... ] ]

    // Assume first row header hai
    $headerRow = array_shift($rows);

    // Header mapping: kaunse column me kaun sa field hai
    // Yahan hum simple approach le rahe: column name match by lowercase
    $headerMap = [];
    foreach ($headerRow as $col => $value) {
        $key = strtolower(trim($value));
        if ($key !== '') {
            $headerMap[$key] = $col;  // e.g. 'name' => 'A'
        }
    }

    // Required columns check
    $requiredCols = ['name', 'roll_number', 'branch', 'semester'];
    foreach ($requiredCols as $req) {
        if (!isset($headerMap[$req])) {
            jsonResponse([
                'success' => false,
                'message' => "Missing required column in Excel header: {$req}"
            ], 400);
        }
    }

    $createdCount = 0;
    $failedRows   = [];

    // =========================
    // Har row ko Directus me create karna
    // =========================
    foreach ($rows as $rowIndex => $row) {
        // Row number for error reporting (plus 2 because 1st row header tha)
        $excelRowNumber = $rowIndex + 2;

        // Excel se values pick karna (header mapping se)
        $name      = trim($row[$headerMap['name']] ?? '');
        $rollNo    = trim($row[$headerMap['roll_number']] ?? '');
        $branch    = trim($row[$headerMap['branch']] ?? '');
        $semester  = trim($row[$headerMap['semester']] ?? '');
        $email     = isset($headerMap['email'])
            ? trim($row[$headerMap['email']] ?? '')
            : '';

        // Basic validation per row
        if ($name === '' || $rollNo === '' || $branch === '' || $semester === '') {
            $failedRows[] = [
                'row'     => $excelRowNumber,
                'reason'  => 'Missing required fields (name/roll_number/branch/semester)'
            ];
            continue;
        }

        // Semester ko int me convert
        $semesterInt = intval($semester);

        // Directus students collection ke liye payload
        $payload = [
            'name'            => $name,
            'roll_number'     => $rollNo,
            'email'           => $email,
            'branch'          => $branch,
            'semester'        => $semesterInt,
            'total_classes'   => 0,
            'present_classes' => 0,
        ];

        // Directus ko call karna
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Error handling per row
        if ($curlError) {
            $failedRows[] = [
                'row'    => $excelRowNumber,
                'reason' => 'Connection error: ' . $curlError
            ];
            continue;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorData = json_decode($response, true);
            $msg       = $errorData['errors'][0]['message'] ?? 'Directus error';
            $failedRows[] = [
                'row'    => $excelRowNumber,
                'reason' => $msg,
            ];
            continue;
        }

        $createdCount++;
    }

    // Final response
    jsonResponse([
        'success'       => true,
        'message'       => 'Excel import completed.',
        'created_count' => $createdCount,
        'failed_rows'   => $failedRows,
    ]);

} catch (Exception $e) {
    // Agar Excel parse karne me ya kuch aur me exception aayi
    jsonResponse([
        'success' => false,
        'message' => 'Error processing Excel file: ' . $e->getMessage()
    ], 500);
}
