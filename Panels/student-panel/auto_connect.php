<?php
/**
 * Auto-Connect Script for Directus
 * This script attempts to find and set the Directus API key automatically
 */

require_once 'config.php';

// Try to get API key from Directus admin user
function tryGetApiKey() {
    $directusUrl = DIRECTUS_BASE_URL;
    
    // Method 1: Try to login as admin and get token
    // Note: This requires knowing admin credentials
    // For now, we'll try to use Directus API to create/get a static token
    
    // Method 2: Check if there's a default admin token
    // Directus sometimes has a default admin token on first setup
    
    // Method 3: Try to access collections without auth (won't work but shows if Directus is up)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $directusUrl . '/server/info');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return ['status' => 'directus_running', 'message' => 'Directus is running but API key needed'];
    }
    
    return ['status' => 'error', 'message' => 'Cannot connect to Directus'];
}

// Check current config
$currentKey = defined('DIRECTUS_API_KEY') ? DIRECTUS_API_KEY : 'gk3WLa_wSVuj4YkEedlDpgwmB9ryXtuv';
$needsKey = ($currentKey === 'gk3WLa_wSVuj4YkEedlDpgwmB9ryXtuv' || empty($currentKey));

if ($needsKey) {
    $result = tryGetApiKey();
    /*echo json_encode([
        'status' => 'needs_key',
        'message' => 'Please set Directus API key in config.php',
        'directus_status' => $result
    ], JSON_PRETTY_PRINT);*/
} else {
    // Test the existing key
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DIRECTUS_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        /*echo json_encode([
            'status' => 'connected',
            'message' => 'Successfully connected to Directus!'
        ], JSON_PRETTY_PRINT);*/
    } else {
        /*echo json_encode([
            'status' => 'auth_failed',
            'message' => 'API key may be incorrect (HTTP ' . $httpCode . ')'
        ], JSON_PRETTY_PRINT);*/
    }
}
?>



