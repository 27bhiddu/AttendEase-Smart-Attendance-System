<?php
// Include config.php - use __DIR__ to ensure correct path resolution
require_once __DIR__ . '/config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Require login - redirect to login page if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit();
    }
}

// Get Directus API headers
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

