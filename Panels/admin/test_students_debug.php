<?php
// Debug script to test student data fetching
require_once __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Students API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
        .info { color: #666; font-size: 12px; }
        button { padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #4338ca; }
    </style>
</head>
<body>
    <h1>🔍 Students API Debug Tool</h1>
    
    <div class="section">
        <h2>1. Configuration</h2>
        <p><strong>Directus URL:</strong> <?php echo DIRECTUS_BASE_URL; ?></p>
        <p><strong>API Key:</strong> <?php echo !empty(DIRECTUS_API_KEY) ? 'Set (' . strlen(DIRECTUS_API_KEY) . ' chars)' : 'Not set (public access)'; ?></p>
        <p><strong>Request URL:</strong> <code><?php echo DIRECTUS_BASE_URL; ?>/items/students?fields=id,name,roll_number,branch,semester,total_classes,present_classes</code></p>
    </div>
    
    <div class="section">
        <h2>2. Direct API Test</h2>
        <?php
        $url = DIRECTUS_BASE_URL . '/items/students?fields=id,name,roll_number,branch,semester,total_classes,present_classes';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo '<p class="error">❌ cURL Error: ' . htmlspecialchars($curlError) . '</p>';
        } else {
            echo '<p><strong>HTTP Status:</strong> ' . $httpCode . '</p>';
            if ($httpCode === 200 || $httpCode === 201) {
                echo '<p class="success">✅ Connection successful!</p>';
                $data = json_decode($response, true);
                if ($data) {
                    echo '<p><strong>Response Keys:</strong> ' . implode(', ', array_keys($data)) . '</p>';
                    if (isset($data['data'])) {
                        $students = is_array($data['data']) ? $data['data'] : [];
                        echo '<p class="success"><strong>✅ Students Found: ' . count($students) . '</strong></p>';
                        if (count($students) > 0) {
                            echo '<p><strong>First Student Sample:</strong></p>';
                            echo '<pre>' . htmlspecialchars(json_encode($students[0], JSON_PRETTY_PRINT)) . '</pre>';
                        }
                    } else {
                        echo '<p class="error">❌ No "data" key in response</p>';
                    }
                    echo '<p><strong>Full Response:</strong></p>';
                    echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
                } else {
                    echo '<p class="error">❌ Invalid JSON response</p>';
                    echo '<pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
                }
            } else {
                echo '<p class="error">❌ HTTP Error: ' . $httpCode . '</p>';
                echo '<pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. API Endpoint Test</h2>
        <?php
        ob_start();
        include __DIR__ . '/api/get_students.php';
        $apiResponse = ob_get_clean();
        
        $apiData = json_decode($apiResponse, true);
        if ($apiData) {
            if (isset($apiData['success']) && $apiData['success']) {
                echo '<p class="success">✅ API endpoint working!</p>';
                if (isset($apiData['data'])) {
                    $students = is_array($apiData['data']) ? $apiData['data'] : [];
                    echo '<p class="success"><strong>Students Count: ' . count($students) . '</strong></p>';
                }
            } else {
                echo '<p class="error">❌ API returned error</p>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($apiData['message'] ?? 'Unknown error') . '</p>';
            }
            echo '<pre>' . htmlspecialchars(json_encode($apiData, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            echo '<p class="error">❌ Invalid JSON from API</p>';
            echo '<pre>' . htmlspecialchars(substr($apiResponse, 0, 1000)) . '</pre>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. JavaScript Test</h2>
        <button onclick="testAPI()">Test API via JavaScript</button>
        <div id="js-result" style="margin-top: 15px;"></div>
    </div>
    
    <div class="section">
        <h2>5. Troubleshooting</h2>
        <ul>
            <li>✅ Check if Directus is running at <?php echo DIRECTUS_BASE_URL; ?></li>
            <li>✅ Verify "students" collection exists in Directus</li>
            <li>✅ Check API permissions in Directus settings</li>
            <li>✅ Verify field names match: id, name, roll_number, branch, semester, total_classes, present_classes</li>
            <li>✅ Check browser console (F12) for JavaScript errors</li>
        </ul>
    </div>
    
    <script>
    const BASE_PATH = '<?php echo BASE_PATH; ?>';
    
    function testAPI() {
        const resultDiv = document.getElementById('js-result');
        resultDiv.innerHTML = '<p>Testing...</p>';
        
        fetch(BASE_PATH + 'api/get_students.php')
            .then(response => {
                resultDiv.innerHTML += '<p>Response status: ' + response.status + '</p>';
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                resultDiv.innerHTML += '<p class="success">✅ JavaScript fetch successful!</p>';
                resultDiv.innerHTML += '<p>Success: ' + data.success + '</p>';
                if (data.success && data.data) {
                    resultDiv.innerHTML += '<p class="success"><strong>Students Count: ' + (Array.isArray(data.data) ? data.data.length : 0) + '</strong></p>';
                } else {
                    resultDiv.innerHTML += '<p class="error">Error: ' + (data.message || 'Unknown') + '</p>';
                }
                resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                resultDiv.innerHTML += '<p class="error">❌ Error: ' + error.message + '</p>';
                console.error('Error:', error);
            });
    }
    </script>
</body>
</html>


