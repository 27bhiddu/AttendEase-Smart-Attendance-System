# Install PHP for Windows (Quick Guide)

Since you're using Docker for Directus/MySQL, you just need PHP on your Windows machine to run the admin panel.

## Quick Installation Steps

### Step 1: Download PHP
1. Go to: https://windows.php.net/download/
2. Download **PHP 8.2 or 8.3** (Thread Safe version, ZIP)
   - Example: `php-8.2.13-Win32-vs16-x64.zip`

### Step 2: Extract PHP
1. Create folder: `C:\php`
2. Extract the ZIP file to `C:\php`
3. You should have: `C:\php\php.exe`

### Step 3: Add PHP to PATH
1. Press `Win + X` and select **System**
2. Click **Advanced system settings**
3. Click **Environment Variables**
4. Under **System variables**, find and select **Path**, then click **Edit**
5. Click **New** and add: `C:\php`
6. Click **OK** on all dialogs

### Step 4: Verify Installation
Open a NEW PowerShell window and run:
```powershell
php -v
```

You should see PHP version information.

### Step 5: Start Admin Panel Server
Navigate to your admin-panel folder and run:
```powershell
cd C:\project_attendease\admin-panel
php -S localhost:8000
```

### Step 6: Access Admin Panel
Open browser: `http://localhost:8000/login.php`

---

## Alternative: Use Chocolatey (Easier)

If you have Chocolatey package manager:

```powershell
choco install php
```

Then restart PowerShell and run:
```powershell
php -S localhost:8000
```

---

## Enable Required Extensions

After installing PHP, make sure these extensions are enabled in `C:\php\php.ini`:

1. Open `C:\php\php.ini` (copy from `php.ini-development` if it doesn't exist)
2. Find and uncomment (remove the `;`):
   - `extension=curl`
   - `extension=mbstring`
   - `extension=openssl`

Save and restart your PHP server.

---

## Your Setup

- **Directus**: Running in Docker at `http://localhost:8055` ✅
- **MySQL**: Running in Docker ✅
- **Admin Panel**: Will run on `http://localhost:8000` (PHP server)

Both can run simultaneously!





