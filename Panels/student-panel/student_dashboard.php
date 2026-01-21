<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/student_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$studentId = $_SESSION['student_id'] ?? null;

if (!$studentId) {
    header('Location: student_login.php');
    exit;
}

// Always set date first
$todayDate = date("d M Y");

// --- STUDENT DATA FETCHING ---
$student = null;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students/' . intval($studentId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (!curl_errno($ch) && $httpCode === 200) {
    $data = json_decode($response, true);
    $student = $data['data'] ?? null;
} else {
    error_log("Student fetch failed for ID $studentId: HTTP $httpCode");
}
curl_close($ch);

// Fallback display variables
$studentName = $student ? htmlspecialchars($student['name'] ?? 'Student') : 'Student';
$firstChar   = strtoupper(substr($studentName, 0, 1));
$rollNo      = $student ? htmlspecialchars($student['roll_number'] ?? 'N/A') : 'N/A';
$branch      = $student ? htmlspecialchars($student['branch'] ?? '') : '';
$semester    = $student ? htmlspecialchars($student['semester'] ?? 'N/A') : 'N/A';
$email       = htmlspecialchars($_SESSION['student_email'] ?? 'N/A');

// --- ATTENDANCE CALCULATION ---
$total = 0;
$present = 0;
$percent = 0;

$attendanceUrl = DIRECTUS_BASE_URL . '/items/attendance'
    . '?filter[student_id][_eq]=' . intval($studentId)
    . '&fields=id,present';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $attendanceUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    $records = $data['data'] ?? [];

    $total = count($records);

    foreach ($records as $row) {
        if (isset($row['present']) && $row['present'] === true) {
            $present++;
        }
    }
}

$percent = ($total > 0) ? round(($present * 100) / $total, 1) : 0;

// --- PROFILE PICTURE LOGIC ---
$profilePicId = $student['image'] ?? null; 
$profilePicUrl = null;

if ($profilePicId) {     
    $baseUrl = rtrim(DIRECTUS_BASE_URL, '/');
    $profilePicUrl = $baseUrl . '/assets/' . $profilePicId . '?fit=cover&width=300&height=300&quality=90';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AttendEase</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* --- GLOBAL VARIABLES --- */
        :root {
            --bg-body: #f8f9fc;
            --sidebar-width: 270px;
            --primary-color: #6366f1;
            --primary-gradient: linear-gradient(135deg, #6366f1, #4f46e5);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
        }

        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .dashboard-layout { display: flex; width: 100%; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: var(--card-bg); border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; flex-shrink: 0; position: sticky; top: 0; height: 100vh; z-index: 50; padding: 24px 0; }
        .brand { padding: 0 24px 32px; display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; color: var(--primary-color); }
        .nav-label { padding: 0 24px; margin-bottom: 12px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .nav-items { list-style: none; padding: 0; margin: 0 12px; display: flex; flex-direction: column; gap: 6px; }
        .nav-items a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; }
        .nav-items a:hover { background-color: #f8fafc; color: var(--primary-color); }
        .nav-items a.active { background-color: #eef2ff; color: var(--primary-color); }

        /* Main Content */
        .main-wrapper { flex-grow: 1; padding: 30px 40px; display: flex; flex-direction: column; }
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .header-title h1 { margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--text-main); }
        .header-title p { margin: 5px 0 0; color: var(--text-muted); font-size: 0.95rem; }
        .btn-logout { background: #fff; border: 1px solid #e2e8f0; color: #ef4444; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s; }
        .btn-logout:hover { border-color: #ef4444; background: #fef2f2; }

        /* Hero Grid */
        .hero-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px; margin-bottom: 24px; }

        /* --- REDESIGNED PROFILE CARD --- */
        .profile-card {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 32px;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
            display: flex;
            align-items: center; /* Vertically center items */
            gap: 40px; /* Space between Image and Info */
            min-height: 320px;
        }

        .profile-left {
            flex-shrink: 0; /* Prevent image from shrinking */
        }

        .profile-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Large Avatar Box */
        .avatar-box {
            width: 130px; height: 130px; 
            background: rgba(255,255,255,0.2);
            border-radius: 24px; /* Slightly softer corners for large box */
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem; font-weight: 700;
            overflow: hidden; 
            border: 3px solid rgba(255, 255, 255, 0.3);
            position: relative;
        }

        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .fallback-initial { display: none; color: #ffffff; }

        .pc-name h2 { margin: 0; font-size: 1.8rem; font-weight: 800; line-height: 1.2; }
        .pc-role { opacity: 0.8; font-size: 0.95rem; font-weight: 500; margin-bottom: 20px; display: block; }
        .pc-divider-h { width: 100%; height: 1px; background: rgba(255,255,255,0.2); margin-bottom: 20px; }

        .pc-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px; /* Row Gap, Col Gap */
        }
        .detail-item label { display: block; font-size: 0.75rem; opacity: 0.8; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-item div { font-size: 1.05rem; font-weight: 600; }

        /* Attendance Card */
        .attendance-card { background: white; border-radius: 20px; padding: 32px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 320px; }
        .ac-title { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px; display: block; }
        .big-percent { font-size: 4rem; font-weight: 800; color: var(--text-main); line-height: 1; margin: 10px 0; }
        .ac-sub { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px; }
        .badge-warning { background: #fee2e2; color: #ef4444; padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; }
        .badge-success { background: #dcfce7; color: #16a34a; padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; }

        /* Small Cards */
        .small-cards-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .mini-card { background: white; border-radius: 16px; padding: 24px; display: flex; align-items: center; gap: 20px; text-decoration: none; border: 1px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s; }
        .mini-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.08); border-color: #e0e7ff; }
        .mc-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .bg-blue { background: #e0f2fe; color: #0284c7; }
        .bg-green { background: #dcfce7; color: #16a34a; }
        .mc-text h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-main); }
        .mc-text p { margin: 4px 0 0; font-size: 0.85rem; color: var(--text-muted); }

        @media (max-width: 1024px) {
            .sidebar { width: 0; overflow: hidden; padding: 0; position: fixed; }
            .main-wrapper { padding: 20px; width: 100%; }
            .hero-grid { grid-template-columns: 1fr; }
            .small-cards-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .profile-card { flex-direction: column; text-align: center; gap: 24px; padding: 24px; }
            .pc-details-grid { text-align: left; width: 100%; }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap"></i> AttendEase
        </div>
        <div class="nav-label">Main Menu</div>
        <ul class="nav-items">
            <li><a href="#" class="active"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
            <li><a href="student_profile.php"><i class="fa-regular fa-user"></i> My Profile</a></li>
            <li><a href="student_helpdesk.php"><i class="fa-solid fa-headset"></i> Helpdesk</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        
        <div class="header-section">
            <div class="header-title">
                <h1>Welcome, <?php echo $studentName; ?> 👋</h1>
                <p><?php echo $todayDate; ?></p>
            </div>
            <a href="student_logout.php" class="btn-logout">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>

        <div class="hero-grid">
            
            <div class="profile-card">
                <div class="profile-left">
                    <div class="avatar-box">
                        <?php if ($profilePicUrl): ?>
                            <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" 
                                 alt="Profile" 
                                 class="avatar-img"
                                 onerror="this.style.display='none'; document.getElementById('fallback-char').style.display='block';">
                            <span id="fallback-char" class="fallback-initial">
                                <?php echo $firstChar; ?>
                            </span>
                        <?php else: ?>
                            <span><?php echo $firstChar; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-right">
                    <div class="pc-name">
                        <h2><?php echo $studentName; ?></h2>
                        <span class="pc-role">Student Profile</span>
                    </div>
                    
                    <div class="pc-divider-h"></div>
                    
                    <div class="pc-details-grid">
                        <div class="detail-item">
                            <label>Roll Number</label>
                            <div><?php echo $rollNo; ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Branch & Semester</label>
                            <div>
                                <?php echo $branch; ?> - 
                                <?php 
                                    // FIXED: Logic to prevent double "Semester" text
                                    if (stripos($semester, 'sem') !== false) {
                                        // If DB already says "Semester 3" or "Sem 3", just print it
                                        echo $semester;
                                    } else {
                                        // If DB just says "3", prepend "Semester "
                                        echo 'Semester ' . $semester;
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Email ID</label>
                            <div style="font-size:0.95rem; word-break:break-all;"><?php echo $email; ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Profile Status</label>
                            <div><i class="fa-solid fa-circle-check"></i> Active</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="attendance-card">
                <div style="display:flex; justify-content:space-between;">
                    <span class="ac-title">Overall Attendance</span>
                    <i class="fa-solid fa-chart-pie" style="color:var(--primary-color);"></i>
                </div>
                
                <div>
                    <div class="big-percent"><?php echo $percent; ?>%</div>
                    <div class="ac-sub">You have attended <strong><?php echo $present; ?></strong> out of <strong><?php echo $total; ?></strong> classes.</div>
                </div>

                <?php if($percent < 75): ?>
                    <div class="badge-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i> Below 75% Requirement
                    </div>
                <?php else: ?>
                    <div class="badge-success">
                        <i class="fa-solid fa-check-circle"></i> Good Standing
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="small-cards-grid">
            
            <a href="student_profile.php" class="mini-card">
                <div class="mc-icon bg-blue">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="mc-text">
                    <h3>My Profile</h3>
                    <p>Update details</p>
                </div>
            </a>

            <a href="student_helpdesk.php" class="mini-card">
                <div class="mc-icon bg-green">
                    <i class="fa-solid fa-life-ring"></i>
                </div>
                <div class="mc-text">
                    <h3>Helpdesk</h3>
                    <p>Contact admin</p>
                </div>
            </a>

        </div>

    </main>
</div>

</body>
</html>
