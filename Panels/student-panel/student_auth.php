<?php
// Start session for student auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config.php - use __DIR__ to ensure correct path resolution
require_once __DIR__ . '/config.php';

// Check if student is logged in
function isStudentLoggedIn() {
    return isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true
        && isset($_SESSION['student_id']);
}

// Require student login - redirect to student login page if not authenticated
function requireStudentLogin() {
    if (!isStudentLoggedIn()) {
        // Adjust path if your student_login.php is in a different folder
        header('Location: ' . BASE_PATH . 'student_login.php');
        exit();
    }
}

// Get Directus API headers (same as admin)
function getDirectusHeaders() {
    $headers = [
        'Content-Type: application/json'
    ];
    
    // Only add Authorization header if API key is set and not placeholder
    if (defined('DIRECTUS_API_KEY') &&
        DIRECTUS_API_KEY !== '<PUT_MY_DIRECTUS_API_KEY_HERE>' &&
        !empty(DIRECTUS_API_KEY)) {
        $headers[] = 'Authorization: Bearer ' . DIRECTUS_API_KEY;
    }
    
    return $headers;
}
?>
