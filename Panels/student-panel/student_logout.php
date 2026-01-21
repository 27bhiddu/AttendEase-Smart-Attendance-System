<?php
// student_logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// redirect to student login page
header('Location: student_login.php');
exit;
