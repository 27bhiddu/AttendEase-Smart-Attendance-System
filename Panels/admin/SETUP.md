# Quick Setup Guide

## Step 1: Configure Directus API Key

1. Open `config.php`
2. Replace `<PUT_MY_DIRECTUS_API_KEY_HERE>` with your actual Directus API Key:
   ```php
   define('DIRECTUS_API_KEY', 'your-actual-api-key-here');
   ```

## Step 2: Change Admin Credentials (Optional but Recommended)

Edit `config.php`:
```php
$ADMIN_USER = "admin";        // Change this
$ADMIN_PASS = "password123";  // Change this
```

## Step 3: Verify Directus Collection

Ensure your Directus `students` collection has these fields:
- `id` (Primary Key, Auto-increment)
- `name` (String, Required)
- `roll_no` (String, Required) 
- `department` (String, Required)
- `attendance_percentage` (Float/Decimal, Optional)

## Step 4: Start Your PHP Server

### Option A: XAMPP
1. Copy `admin-panel` folder to `C:\xampp\htdocs\`
2. Start Apache in XAMPP
3. Visit: `http://localhost/admin-panel/login.php`

### Option B: Laragon
1. Copy `admin-panel` folder to Laragon's `www` directory
2. Start Laragon
3. Visit: `http://admin-panel.test/login.php`

### Option C: PHP Built-in Server
```bash
cd admin-panel
php -S localhost:8000
```
Visit: `http://localhost:8000/login.php`

## Step 5: Login

- Username: `admin` (or your custom username)
- Password: `password123` (or your custom password)

## Troubleshooting

### "Connection error" when loading students
- ✅ Check Directus is running at `http://localhost:8055`
- ✅ Verify API key in `config.php`
- ✅ Ensure PHP cURL extension is enabled

### "Student not found" error
- ✅ Verify student ID exists in Directus
- ✅ Check collection name is exactly `students`

### CSS/JS not loading
- ✅ Check `BASE_PATH` in `config.php` matches your setup
- ✅ If admin-panel is in subdirectory, set: `define('BASE_PATH', '/admin-panel');`

## File Structure

```
admin-panel/
├── api/              # Directus API integration
├── assets/           # CSS and JavaScript
├── layout/           # Header and footer templates
├── config.php        # Configuration (API key, credentials)
├── auth.php          # Authentication helpers
├── login.php         # Login page
├── logout.php        # Logout handler
├── dashboard.php     # Dashboard
├── students.php      # Students list
├── add_student.php   # Add student form
└── edit_student.php  # Edit student form
```

## Next Steps

1. ✅ Configure API key
2. ✅ Change default credentials
3. ✅ Test login
4. ✅ Add your first student
5. ✅ Verify CRUD operations work

Enjoy your admin panel! 🎉





