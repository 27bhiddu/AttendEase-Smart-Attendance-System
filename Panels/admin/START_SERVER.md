# How to Start the Admin Panel Server

## Option 1: Using XAMPP (Easiest for Windows)

### Step 1: Install XAMPP
1. Download XAMPP from: https://www.apachefriends.org/
2. Install it (usually to `C:\xampp`)

### Step 2: Copy Admin Panel
1. Copy the entire `admin-panel` folder
2. Paste it into: `C:\xampp\htdocs\`
3. You should have: `C:\xampp\htdocs\admin-panel\`

### Step 3: Start XAMPP
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Wait until Apache status shows "Running" (green)

### Step 4: Access Admin Panel
Open your browser and go to:
```
http://localhost/admin-panel/login.php
```

---

## Option 2: Using Laragon

### Step 1: Install Laragon
1. Download Laragon from: https://laragon.org/
2. Install it

### Step 2: Copy Admin Panel
1. Copy the entire `admin-panel` folder
2. Paste it into Laragon's `www` directory
   - Usually: `C:\laragon\www\`

### Step 3: Start Laragon
1. Open **Laragon**
2. Click **Start All**

### Step 4: Access Admin Panel
Open your browser and go to:
```
http://admin-panel.test/login.php
```
or
```
http://localhost/admin-panel/login.php
```

---

## Option 3: Install PHP Manually

### Step 1: Download PHP
1. Download PHP from: https://windows.php.net/download/
2. Extract to: `C:\php`

### Step 2: Add to PATH
1. Open **System Properties** → **Environment Variables**
2. Edit **Path** variable
3. Add: `C:\php`
4. Click OK

### Step 3: Start Server
Open PowerShell in the `admin-panel` folder and run:
```powershell
php -S localhost:8000
```

### Step 4: Access Admin Panel
Open your browser and go to:
```
http://localhost:8000/login.php
```

---

## Quick Test

After starting your server, test if it's working:
- Try: `http://localhost/admin-panel/login.php` (XAMPP)
- Try: `http://localhost:8000/login.php` (PHP built-in server)

---

## Troubleshooting

### "Site can't be reached"
- ✅ Make sure Apache/PHP server is running
- ✅ Check if port 8000 is available (or use XAMPP on port 80)
- ✅ Try `http://127.0.0.1` instead of `localhost`
- ✅ Check Windows Firewall isn't blocking it

### "404 Not Found"
- ✅ Verify the folder is in the correct location
- ✅ Check the URL path is correct
- ✅ Make sure `login.php` exists in the folder

### PHP Errors
- ✅ Make sure PHP is installed and working
- ✅ Check `config.php` is configured correctly





