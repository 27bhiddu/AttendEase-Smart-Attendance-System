<?php
// Directus Configuration
define('DIRECTUS_BASE_URL', 'http://localhost:8055');

// yahan static token / API key rakho
define('DIRECTUS_API_KEY', 'gk3WLa_wSVuj4YkEedlDpgwmB9ryXtuv');

// Base path for admin panel
define('BASE_PATH', '');

// Admin Credentials (sirf local ke liye)
$ADMIN_USER = "admin";
$ADMIN_PASS = "password123";



ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_start();
