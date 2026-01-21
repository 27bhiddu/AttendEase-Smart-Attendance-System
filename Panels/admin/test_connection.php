<?php
require_once 'config.php';

// Simple HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Directus Connection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1d29;
            color: #fff;
        }
        h2 { color: #6644ff; }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: #2a2d3a;
        }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { border-left: 4px solid #17a2b8; }
        code {
            background: #1a1d29;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        a {
            color: #6644ff;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>🔌 Testing Directus Connection</h2>
    
    <?php
    // Test 1: Check Configuration
    echo '<div class="test-result info">';
    echo '<h3>1. Configuration Check</h3>';
    echo '<p><strong>Directus URL:</strong> <code>' . htmlspecialchars(DIRECTUS_BASE_URL) . '</code></p>';
    
    $apiKeySet = defined('DIRECTUS_API_KEY') && DIRECTUS_API_KEY !== '<PUT_MY_DIRECTUS_API_KEY_HERE>' && !empty(DIRECTUS_API_KEY);
    echo '<p><strong>API Key:</strong> ';
    if ($apiKeySet) {
        echo '<span style="color: #28a745;">✅ Set</span> (Length: ' . strlen(DIRECTUS_API_KEY) . ' characters)';
    } else {
        echo '<span style="color: #dc3545;">❌ Not Set</span> - Please update config.php';
    }
    echo '</p>';
    echo '</div>';
    
    if (!$apiKeySet) {
        echo '<div class="test-result error">';
        echo '<p><strong>⚠️ Please set your Directus API key in config.php first!</strong></p>';
        echo '<p>Edit <code>admin-panel/config.php</code> and replace <code>&lt;PUT_MY_DIRECTUS_API_KEY_HERE&gt;</code> with your actual API key.</p>';
        echo '</div>';
        echo '<p><a href="login.php">← Back to Login</a></p>';
        exit;
    }
    
    // Test 2: Test Students Collection
    echo '<div class="test-result">';
    echo '<h3>2. Testing Students Collection</h3>';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DIRECTUS_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo '<p style="color: #dc3545;">❌ <strong>Connection Error:</strong> ' . htmlspecialchars($curlError) . '</p>';
        echo '<p>Make sure Directus is running at ' . htmlspecialchars(DIRECTUS_BASE_URL) . '</p>';
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = isset($data['data']) ? count($data['data']) : 0;
        echo '<p style="color: #28a745;">✅ <strong>Success!</strong> Found ' . $count . ' student(s)</p>';
        if ($count > 0 && isset($data['data'][0])) {
            echo '<p><strong>Sample student:</strong> ' . htmlspecialchars($data['data'][0]['name'] ?? 'N/A') . '</p>';
        }
    } elseif ($httpCode === 401 || $httpCode === 403) {
        echo '<p style="color: #dc3545;">❌ <strong>Authentication Failed</strong> (HTTP ' . $httpCode . ')</p>';
        echo '<p>Your API key may be incorrect or expired. Please check your Directus API key.</p>';
    } elseif ($httpCode === 404) {
        echo '<p style="color: #dc3545;">❌ <strong>Collection Not Found</strong> (HTTP 404)</p>';
        echo '<p>The "students" collection does not exist in Directus. Please create it first.</p>';
    } else {
        $errorMsg = json_decode($response, true);
        $errorText = isset($errorMsg['errors'][0]['message']) ? $errorMsg['errors'][0]['message'] : 'Unknown error';
        echo '<p style="color: #dc3545;">❌ <strong>Error</strong> (HTTP ' . $httpCode . '): ' . htmlspecialchars($errorText) . '</p>';
    }
    echo '</div>';
    
    // Test 3: Test Teachers Collection
    echo '<div class="test-result">';
    echo '<h3>3. Testing Teachers Collection</h3>';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DIRECTUS_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = isset($data['data']) ? count($data['data']) : 0;
        echo '<p style="color: #28a745;">✅ <strong>Success!</strong> Found ' . $count . ' teacher(s)</p>';
        if ($count > 0 && isset($data['data'][0])) {
            echo '<p><strong>Sample teacher:</strong> ' . htmlspecialchars($data['data'][0]['username'] ?? 'N/A') . '</p>';
        }
    } elseif ($httpCode === 404) {
        echo '<p style="color: #dc3545;">❌ <strong>Collection Not Found</strong> (HTTP 404)</p>';
        echo '<p>The "teachers" collection does not exist in Directus. Please create it first.</p>';
    } else {
        echo '<p style="color: #dc3545;">❌ <strong>Error</strong> (HTTP ' . $httpCode . ')</p>';
    }
    echo '</div>';
    
    // Test 4: Test Attendance Collection
    echo '<div class="test-result">';
    echo '<h3>4. Testing Attendance Collection</h3>';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/attendance?limit=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DIRECTUS_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = isset($data['data']) ? count($data['data']) : 0;
        echo '<p style="color: #28a745;">✅ <strong>Success!</strong> Attendance collection accessible</p>';
    } elseif ($httpCode === 404) {
        echo '<p style="color: #ffc107;">⚠️ <strong>Collection Not Found</strong> (HTTP 404) - Optional collection</p>';
    } else {
        echo '<p style="color: #dc3545;">❌ <strong>Error</strong> (HTTP ' . $httpCode . ')</p>';
    }
    echo '</div>';
    
    // Summary
    echo '<div class="test-result info">';
    echo '<h3>📋 Summary</h3>';
    echo '<p>If all tests show ✅, your connection is working!</p>';
    echo '<p><a href="login.php">→ Go to Admin Panel</a></p>';
    echo '</div>';
    ?>
</body>
</html>



