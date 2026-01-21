<?php
require_once __DIR__ . '/../config.php'; // yahan se DB ya Directus helper lo

header('Content-Type: application/json');

/**
 * Agar tum direct MySQL use kar rahe ho to ek PDO helper jaise:
 * Directus use karte ho to yahan Directus REST call likho (collection: students).
 */

function getDb() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8mb4', 'user', 'pass');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Only GET allowed.'
    ]);
    exit;
}

$branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';
$year   = isset($_GET['year'])   ? trim($_GET['year'])   : '';

try {
    $pdo = getDb();

    $sql = "
        SELECT
            id,
            name,
            roll_no,
            email,
            branch,
            semester,
            passing_year
        FROM students
        WHERE status = 'PassOut'
    ";
    $params = [];

    if ($branch !== '') {
        $sql      .= " AND branch = :branch";
        $params[':branch'] = $branch;
    }

    if ($year !== '') {
        $sql      .= " AND passing_year = :year";
        $params[':year'] = (int)$year;
    }

    $sql .= " ORDER BY passing_year DESC, branch ASC, roll_no ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching pass out students list.'
    ]);
}
