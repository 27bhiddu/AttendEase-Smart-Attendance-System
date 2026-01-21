# Quick PHP Installation

## Option 1: Run the Installation Script (Recommended)

1. **Open PowerShell as Administrator:**
   - Press `Win + X`
   - Select "Windows PowerShell (Admin)" or "Terminal (Admin)"

2. **Navigate to the admin-panel folder:**
   ```powershell
   cd C:\project_attendease\admin-panel
   ```

3. **Run the installation script:**
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope Process -Force
   .\install-php.ps1
   ```

The script will:
- Download PHP 8.2.13
- Extract to `C:\php`
- Configure php.ini
- Add PHP to your system PATH

---

## Option 2: Manual Installation (If script fails)

### Step 1: Download PHP
1. Go to: https://windows.php.net/download/
2. Download: **PHP 8.2.x Thread Safe (ZIP)**
   - Look for: `php-8.2.x-Win32-vs16-x64.zip`

### Step 2: Extract
1. Create folder: `C:\php`
2. Extract the ZIP to `C:\php`
3. Copy `php.ini-development` to `php.ini`

### Step 3: Configure php.ini
1. Open `C:\php\php.ini`
2. Find and remove `;` from these lines:
   ```
   extension=curl
   extension=mbstring
   extension=openssl
   ```

### Step 4: Add to PATH
1. Press `Win + R`, type `sysdm.cpl`, press Enter
2. **Advanced** tab → **Environment Variables**
3. Under **System variables**, select **Path** → **Edit**
4. Click **New** → Add: `C:\php`
5. Click **OK** on all dialogs

### Step 5: Restart PowerShell
Close and reopen PowerShell, then test:
```powershell
php -v
```

---

## After Installation

Once PHP is installed, start your admin panel:

```powershell
cd C:\project_attendease\admin-panel
php -S localhost:8000
```

Then open: **http://localhost:8000/login.php**

Your Docker Directus will continue running on port 8055, and your PHP admin panel will run on port 8000 - they work together! ✅





