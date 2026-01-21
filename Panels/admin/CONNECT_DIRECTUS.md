# Connect Admin Panel to Directus Database

## Step-by-Step Connection Guide

### Step 1: Get Your Directus API Key

1. **Open Directus Admin Panel**
   - Go to: `http://localhost:8055`
   - Login with your Directus admin credentials

2. **Navigate to Settings**
   - Click the **Settings** icon (gear) in the left sidebar
   - Or go to: `http://localhost:8055/admin/settings`

3. **Get API Key**
   - Option A: Go to **Access Control** → **Roles** → **Administrator** → **API Access** tab
   - Option B: Go to **Access Tokens** and create a new token
   - Copy the **Static Token** or **Token** value

### Step 2: Update config.php

1. Open `admin-panel/config.php`
2. Find this line:
   ```php
   define('DIRECTUS_API_KEY', '<PUT_MY_DIRECTUS_API_KEY_HERE>');
   ```
3. Replace `<PUT_MY_DIRECTUS_API_KEY_HERE>` with your actual API key:
   ```php
   define('DIRECTUS_API_KEY', 'your-actual-api-key-here');
   ```

### Step 3: Verify Directus URL

Make sure the Directus URL is correct:
```php
define('DIRECTUS_BASE_URL', 'http://localhost:8055');
```

If your Directus runs on a different port or URL, update it here.

### Step 4: Test Connection

1. **Start PHP Server** (if not running):
   ```bash
   cd admin-panel
   php -S localhost:8000
   ```

2. **Open Admin Panel**:
   - Go to: `http://localhost:8000/login.php`
   - Login with: `admin` / `password123`

3. **Check Dashboard**:
   - Should load student/teacher counts
   - If you see data, connection is working! ✅

4. **Check Browser Console** (F12):
   - Look for any API errors
   - Check Network tab for failed requests

### Step 5: Verify Collections

Make sure these collections exist in Directus:
- ✅ `students`
- ✅ `teachers`
- ✅ `attendance`
- ✅ `branches` (optional)

---

## Quick Test Script

Create a test file to verify connection:

**File**: `admin-panel/test_connection.php`

```php
<?php
require_once 'config.php';

echo "<h2>Testing Directus Connection</h2>";

// Test 1: Check API Key
echo "<p><strong>API Key:</strong> " . (defined('DIRECTUS_API_KEY') && DIRECTUS_API_KEY !== '<PUT_MY_DIRECTUS_API_KEY_HERE>' ? '✅ Set' : '❌ Not Set') . "</p>";

// Test 2: Test Students Collection
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . DIRECTUS_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>Students API Test:</strong> ";
if ($curlError) {
    echo "❌ Error: " . htmlspecialchars($curlError);
} elseif ($httpCode === 200) {
    $data = json_decode($response, true);
    $count = isset($data['data']) ? count($data['data']) : 0;
    echo "✅ Success! Found $count students";
} else {
    echo "❌ HTTP $httpCode: " . htmlspecialchars(substr($response, 0, 200));
}
echo "</p>";

// Test 3: Test Teachers Collection
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . DIRECTUS_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Teachers API Test:</strong> ";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    $count = isset($data['data']) ? count($data['data']) : 0;
    echo "✅ Success! Found $count teachers";
} else {
    echo "❌ HTTP $httpCode";
}
echo "</p>";

echo "<hr>";
echo "<p><a href='login.php'>Go to Admin Panel</a></p>";
?>
```

Visit: `http://localhost:8000/test_connection.php` to test your connection.

---

## Common Issues

### ❌ "Connection error"
- **Fix**: Check Directus is running on port 8055
- **Fix**: Verify `DIRECTUS_BASE_URL` is correct

### ❌ "401 Unauthorized" or "403 Forbidden"
- **Fix**: API key is incorrect or expired
- **Fix**: Regenerate API key in Directus
- **Fix**: Check API key has proper permissions

### ❌ "404 Not Found" for collections
- **Fix**: Collection name doesn't exist
- **Fix**: Check collection name matches exactly (case-sensitive)
- **Fix**: Verify collection is not hidden/archived

### ❌ "Field not found"
- **Fix**: Field name doesn't match
- **Fix**: Check field exists in Directus collection
- **Fix**: Verify field is not hidden

---

## Verify Your Setup

✅ Directus running at `http://localhost:8055`  
✅ API key copied and set in `config.php`  
✅ Collections exist: students, teachers, attendance  
✅ Field names match between Directus and admin panel  
✅ PHP server running  
✅ Can login to admin panel  
✅ Dashboard shows data  

---

## Need Help?

1. Check `DIRECTUS_SETUP.md` for detailed setup
2. Check `DIRECTUS_DATABASE.md` for database structure
3. Run `test_connection.php` to diagnose issues
4. Check browser console (F12) for errors
5. Check PHP error logs



