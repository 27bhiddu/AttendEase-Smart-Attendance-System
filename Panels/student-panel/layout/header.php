<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Panel - AttendEase</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>admin-panel/style.css">
</head>
<body class="bg-secondary">
<div class="login-page" style="min-height: 100vh; background: var(--bg-secondary);">
    <div class="login-container" style="max-width: 900px;">
