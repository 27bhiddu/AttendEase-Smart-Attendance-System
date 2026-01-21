<?php
// Include auth.php - use __DIR__ to ensure correct path resolution
require_once __DIR__ . '/../auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>AttendEase Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Make BASE_PATH available to JavaScript
        const BASE_PATH = '<?php echo BASE_PATH; ?>';
    </script>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-icon">📚</div>
                    <h2>AttendEase</h2>
                </div>
                <p class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-label">Main</span>
                    <a href="<?php echo BASE_PATH; ?>dashboard.php"
                       class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <span class="nav-section-label">Management</span>
                    <a href="<?php echo BASE_PATH; ?>students.php"
                       class="nav-item <?php
                            $file = basename($_SERVER['PHP_SELF']);
                            echo in_array($file, ['students.php','add_student.php','edit_student.php']) ? 'active' : '';
                       ?>">
                        <span class="nav-icon">👥</span>
                        <span class="nav-label">Students</span>
                    </a>
                    <a href="<?php echo BASE_PATH; ?>teachers.php"
                       class="nav-item <?php
                            $file = basename($_SERVER['PHP_SELF']);
                            echo in_array($file, ['teachers.php','add_teacher.php','edit_teacher.php','teacher_profile.php']) ? 'active' : '';
                       ?>">
                        <span class="nav-icon">👨‍🏫</span>
                        <span class="nav-label">Teachers</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <span class="nav-section-label">Account</span>
                    <a href="<?php echo BASE_PATH; ?>logout.php" class="nav-item logout">
                        <span class="nav-icon">🚪</span>
                        <span class="nav-label">Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <div class="top-nav-left">
                    <h1 class="page-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                </div>
                
                <div class="top-nav-right">
                    <!-- Notification: pending teacher verification -->
                    <div class="notification-bell"
                         onclick="window.location.href='<?php echo BASE_PATH; ?>teachers.php'">
                        <span class="bell-icon">🔔</span>
                        <span class="notification-badge" id="pending-teachers-count">0</span>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?></span>
                        </div>
                        <div class="user-info-dropdown">
                            <span class="user-name">
                                <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                            </span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="content-area">
