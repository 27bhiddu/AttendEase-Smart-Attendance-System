<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/student_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$todayDate = date("d M Y");

// No 'header.php' include needed here as we are building the full custom layout
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk | StudentPortal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* --- THEME CSS (MATCHING DASHBOARD) --- */
        :root {
            --bg-body: #f1f5f9;
            --sidebar-width: 270px;
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --shadow-light: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body, html {
            margin: 0; padding: 0;
            width: 100%; height: 100%;
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .dashboard-layout { display: flex; width: 100%; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 50;
            padding: 24px 0;
        }

        .brand {
            padding: 0 24px 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }

        .nav-label {
            padding: 0 24px;
            margin-bottom: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .nav-items { list-style: none; padding: 0; margin: 0 12px; display: flex; flex-direction: column; gap: 6px; }

        .nav-items a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .nav-items a:hover { background-color: #f8fafc; color: var(--primary-color); transform: translateX(3px); }
        .nav-items a.active { background-color: #eef2ff; color: var(--primary-color); }

        /* Main Content */
        .main-wrapper {
            flex-grow: 1;
            padding: 30px 40px;
            background-color: var(--bg-body);
            display: flex;
            flex-direction: column;
            width: 100%;
            box-sizing: border-box;
        }

        /* Header */
        .dashboard-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 35px; width: 100%;
        }

        .header-text h1 { font-size: 2rem; font-weight: 800; margin: 0; color: var(--text-main); }
        .header-text p { margin: 4px 0 0; color: var(--text-muted); font-size: 1rem; }

        .logout-btn {
            background: #fee2e2; color: #991b1b;
            padding: 12px 24px; border-radius: 50px;
            font-weight: 700; font-size: 0.9rem; text-decoration: none;
            display: flex; align-items: center; gap: 10px;
            transition: all 0.3s ease;
        }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(220, 38, 38, 0.25); background: #ef4444; color: white; }

        /* Help Content */
        .section-box {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid #e2e8f0;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            margin-bottom: 30px;
        }

        .section-box h2 { font-size: 1.5rem; color: var(--text-main); margin-bottom: 10px; }
        .section-box p { color: var(--text-muted); max-width: 600px; margin: 0 auto 30px; line-height: 1.6; }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .contact-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }

        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-color);
        }

        .icon-circle {
            width: 70px; height: 70px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .cc-phone .icon-circle { background: #dcfce7; color: #16a34a; }
        .cc-email .icon-circle { background: #dbeafe; color: #2563eb; }

        .contact-card h3 { margin: 0; font-size: 1.1rem; color: var(--text-main); font-weight: 700; }
        .contact-card span { margin-top: 8px; font-size: 1rem; color: var(--text-muted); font-weight: 500; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 0; padding: 0; overflow: hidden; position: fixed; }
            .main-wrapper { padding: 20px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .contact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap"></i> AttendEase
        </div>
        <div class="nav-label">Menu</div>
        <ul class="nav-items">
            <li><a href="student_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="student_profile.php"><i class="fa-regular fa-user"></i> My Profile</a></li>
            <li><a href="subject_attendance.php"><i class="fa-solid fa-list-check"></i> Subject Report</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-headset"></i> Helpdesk</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        
        <div class="dashboard-header">
            <div class="header-text">
                <h1>Help & Support</h1>
                <p>Need assistance with your account or attendance?</p>
            </div>
            <a href="student_logout.php" class="logout-btn">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>

        <div class="section-box">
            <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;">
                <i class="fa-solid fa-life-ring"></i>
            </div>
            <h2>We are here to help!</h2>
            <p>
                If you are facing issues related to login, attendance discrepancies, or incorrect profile details, 
                please reach out to the administration using the contacts below.
            </p>

            <div class="contact-grid">
                <a href="tel:7297033536" class="contact-card cc-phone">
                    <div class="icon-circle">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <h3>Call Support</h3>
                    <span>+91 7297033536</span>
                </a>

                <a href="mailto:mayankrawal8@gmail.com" class="contact-card cc-email">
                    <div class="icon-circle">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <h3>Email Support</h3>
                    <span>mayankrawal8@gmail.com</span>
                </a>
            </div>
        </div>

    </main>

</div>

</body>
</html>
