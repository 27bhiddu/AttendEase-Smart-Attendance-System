# AttendEase Admin Panel

A complete admin panel built with PHP, HTML, CSS, and JavaScript that connects to a Directus backend.

## Features

- ✅ Custom PHP login system (not using Directus authentication)
- ✅ Session-based authentication
- ✅ Full CRUD operations for Students collection
- ✅ Clean, modern UI with sidebar navigation
- ✅ Responsive design
- ✅ Directus REST API integration
- ✅ Error handling and validation

## Requirements

- PHP 7.4 or higher
- PHP cURL extension enabled
- Directus instance running at `http://localhost:8055`
- Directus API Key

## Installation

### 1. Setup Directus API Key

1. Open `admin-panel/config.php`
2. Replace `<PUT_MY_DIRECTUS_API_KEY_HERE>` with your actual Directus API Key

```php
define('DIRECTUS_API_KEY', 'your-actual-api-key-here');
```

### 2. Configure Admin Credentials

Edit `admin-panel/config.php` to change the admin login credentials:

```php
$ADMIN_USER = "admin";
$ADMIN_PASS = "password123";
```

**⚠️ IMPORTANT:** Change these credentials in production!

### 3. Setup Directus Collection

Ensure your Directus `students` collection has the following fields:

- `id` (Primary Key, Auto-increment)
- `name` (String, Required)
- `roll_no` (String, Required)
- `department` (String, Required)
- `attendance_percentage` (Float/Decimal, Optional)

### 4. Run the Application

#### Option A: Using XAMPP

1. Copy the `admin-panel` folder to `C:\xampp\htdocs\`
2. Start Apache server in XAMPP Control Panel
3. Open browser and navigate to: `http://localhost/admin-panel/login.php`

#### Option B: Using Laragon

1. Copy the `admin-panel` folder to your Laragon `www` directory
2. Start Laragon
3. Open browser and navigate to: `http://admin-panel.test/login.php` (or your configured domain)

#### Option C: Using PHP Built-in Server

1. Open terminal/command prompt
2. Navigate to the `admin-panel` directory:
   ```bash
   cd admin-panel
   ```
3. Start PHP server:
   ```bash
   php -S localhost:8000
   ```
4. Open browser and navigate to: `http://localhost:8000/login.php`

## File Structure

```
admin-panel/
├── api/
│   ├── get_students.php      # Fetch students from Directus
│   ├── create_student.php     # Create new student
│   ├── update_student.php     # Update existing student
│   └── delete_student.php     # Delete student
├── assets/
│   ├── style.css              # Main stylesheet
│   └── script.js              # JavaScript utilities
├── layout/
│   ├── header.php             # Common header with sidebar
│   └── footer.php             # Common footer
├── config.php                 # Configuration (API key, credentials)
├── auth.php                   # Authentication helpers
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── dashboard.php              # Dashboard with statistics
├── students.php               # Students list view
├── add_student.php            # Add new student form
├── edit_student.php           # Edit student form
└── README.md                  # This file
```

## Pages

### Login (`login.php`)
- Custom PHP authentication
- Username: `admin`
- Password: `password123` (change in `config.php`)

### Dashboard (`dashboard.php`)
- Overview statistics
- Total students count
- Average attendance percentage
- High/Low attendance counts

### Students List (`students.php`)
- View all students in a table
- Edit and Delete actions
- Add new student button

### Add Student (`add_student.php`)
- Form to create new student
- Validates required fields
- Submits to Directus API

### Edit Student (`edit_student.php`)
- Form to edit existing student
- Pre-fills with current data
- Updates via Directus API

## API Endpoints

All API files are located in `/api/` and handle Directus communication:

- **GET** `/api/get_students.php` - Fetch all students or single student by ID
- **POST** `/api/create_student.php` - Create new student
- **POST** `/api/update_student.php` - Update student
- **POST** `/api/delete_student.php` - Delete student

All API endpoints:
- Require authentication (session check)
- Use Directus API key for authorization
- Return JSON responses
- Handle errors gracefully

## Directus API Integration

The admin panel uses Directus REST API endpoints:

- `GET /items/students` - List all students
- `GET /items/students/{id}` - Get single student
- `POST /items/students` - Create student
- `PATCH /items/students/{id}` - Update student
- `DELETE /items/students/{id}` - Delete student

All requests include:
```
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

## Security Notes

1. **Change default credentials** in `config.php` before deploying
2. **Use HTTPS** in production (set `session.cookie_secure = 1` in `config.php`)
3. **Protect API files** - ensure they're not directly accessible without authentication
4. **Validate input** - all forms validate required fields
5. **Session security** - sessions are configured with HttpOnly cookies

## Troubleshooting

### "Connection error" when loading students
- Check if Directus is running at `http://localhost:8055`
- Verify API key in `config.php`
- Check PHP cURL extension is enabled

### "Student not found" error
- Verify the student ID exists in Directus
- Check Directus collection name matches `students`

### Session not persisting
- Check PHP session directory is writable
- Verify cookies are enabled in browser
- Check `session.cookie_httponly` settings

### Styling issues
- Ensure `assets/style.css` is accessible
- Check file paths are correct
- Clear browser cache

## Customization

### Change Colors
Edit CSS variables in `assets/style.css`:
```css
:root {
    --primary-color: #6644ff;
    --bg-dark: #1a1d29;
    /* ... */
}
```

### Add More Collections
1. Create new PHP pages (e.g., `teachers.php`)
2. Create corresponding API files in `/api/`
3. Add navigation link in `layout/header.php`

## Support

For issues related to:
- **Directus**: Check Directus documentation
- **PHP**: Verify PHP version and extensions
- **Admin Panel**: Review error messages and check browser console

## License

This admin panel is provided as-is for use with AttendEase project.





