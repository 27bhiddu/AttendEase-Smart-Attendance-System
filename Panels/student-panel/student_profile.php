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

$student = null;
$error   = '';

// --- DATA FETCHING ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students/' . intval($studentId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode !== 200) {
    $error = 'Unable to load student details.';
} else {
    $data    = json_decode($response, true);
    $student = $data['data'] ?? null;
}

// --- PROFILE PICTURE LOGIC ---
$profilePicId = $student['image'] ?? null; // Ensure this matches your Directus field name
$profilePicUrl = null;

if ($profilePicId) {
    $baseUrl = rtrim(DIRECTUS_BASE_URL, '/');
    // Fetch a medium-sized image (200x200) for the profile page
    $profilePicUrl = $baseUrl . '/assets/' . $profilePicId . '?fit=cover&width=200&height=200&quality=90';
}

// --- HANDLE PROFILE PHOTO UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {

    if ($_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {

        $fileTmp  = $_FILES['profile_photo']['tmp_name'];
        $fileName = $_FILES['profile_photo']['name'];

        // 1️⃣ Upload file to Directus Files API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => DIRECTUS_BASE_URL . '/files',
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . DIRECTUS_API_KEY
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile(
                    $fileTmp,
                    mime_content_type($fileTmp),
                    $fileName
                )
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (($httpCode === 200 || $httpCode === 201) && $response) {

            $data   = json_decode($response, true);
            $fileId = $data['data']['id'] ?? null;

            if ($fileId) {
                // 2️⃣ Update student record with image ID
                $update = curl_init();
                curl_setopt_array($update, [
                    CURLOPT_URL => DIRECTUS_BASE_URL . '/items/students/' . intval($studentId),
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . DIRECTUS_API_KEY
                    ],
                    CURLOPT_POSTFIELDS => json_encode([
                        'image' => $fileId
                    ])
                ]);

                curl_exec($update);
                curl_close($update);

                // 3️⃣ Reload page
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $error = 'Image upload failed.';
        }
    } else {
        $error = 'Invalid file selected.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | AttendEase</title>
    
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

        /* --- PROFILE SPECIFIC STYLES --- */
        .profile-layout {
            display: grid;
            grid-template-columns: 350px 1fr; /* Fixed width for card, fluid for details */
            gap: 30px;
        }

        /* Card Styles */
        .profile-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid #e2e8f0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-avatar-area {
            width: 120px; height: 120px;
            margin: 0 auto 20px;
            background: #e0e7ff;
            color: var(--primary-color);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            border: 4px solid #ffffff;
            box-shadow: 0 0 0 4px #e0e7ff;
            overflow: hidden; /* Ensures image stays circular */
        }

        .profile-img {
            width: 100%; height: 100%; object-fit: cover;
        }

        .profile-name { font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .profile-role { color: var(--text-muted); font-size: 0.95rem; font-weight: 500; margin-bottom: 25px; }

        .btn-upload {
            background: var(--primary-color); color: white;
            border: none; padding: 12px 24px; border-radius: 12px;
            font-weight: 600; font-size: 0.9rem; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background 0.2s;
        }
        .btn-upload:hover { background: var(--primary-hover); }

        .upload-note { font-size: 0.8rem; color: #94a3b8; margin-top: 15px; }

        /* Details Section */
        .details-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid #e2e8f0;
        }

        .ds-header {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px; margin-bottom: 25px;
            font-size: 1.2rem; font-weight: 700; color: var(--text-main);
            display: flex; align-items: center; gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .info-item label {
            display: block;
            font-size: 0.8rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .info-item div {
            font-size: 1.1rem; font-weight: 600; color: var(--text-main);
            background: #f8fafc; padding: 12px 16px;
            border-radius: 10px; border: 1px solid #f1f5f9;
        }

        .status-active { color: #16a34a; background: #dcfce7; display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; }

        @media (max-width: 1024px) {
            .sidebar { width: 0; padding: 0; overflow: hidden; position: fixed; }
            .main-wrapper { padding: 20px; }
            .profile-layout { grid-template-columns: 1fr; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .info-grid { grid-template-columns: 1fr; }
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
            <li><a href="#" class="active"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <li><a href="subject_attendance.php"><i class="fa-solid fa-list-check"></i> Subject Report</a></li>
            <li><a href="student_helpdesk.php"><i class="fa-solid fa-headset"></i> Helpdesk</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        
        <div class="dashboard-header">
            <div class="header-text">
                <h1>My Profile</h1>
                <p>Manage your personal information and account settings.</p>
            </div>
            <a href="student_logout.php" class="logout-btn">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($student): ?>
        <div class="profile-layout">
            
            <div class="profile-card">
                <div class="profile-avatar-area">
                    <?php if ($profilePicUrl): ?>
                        <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" 
                             alt="Profile Picture" 
                             class="profile-img"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span style="display:none;"><?php echo strtoupper(substr($student['name'] ?? 'S', 0, 1)); ?></span>
                    <?php else: ?>
                        <?php 
                            // Default to Initial if no image
                            echo strtoupper(substr($student['name'] ?? 'S', 0, 1)); 
                        ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name">
                    <?php echo htmlspecialchars($student['name'] ?? 'Student Name'); ?>
                </div>
                <div class="profile-role">
                    Student &bull; Semester <?php echo htmlspecialchars($student['semester'] ?? 'N/A'); ?>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input 
                        type="file" 
                        name="profile_photo" 
                        accept="image/*" 
                        style="display:none" 
                        id="photoInput"
                        onchange="this.form.submit()"
                    >

                    <button
                        type="button" 
                        class="btn-upload"
                        onclick="document.getElementById('photoInput').click()"
                    >
                        <i class="fa-solid fa-camera"></i> Change Photo
                    </button>
                </form>

                <div class="upload-note">
                    (Feature connected with Directus API)
                </div>
            </div>

            <div class="details-section">
                <div class="ds-header">
                    <i class="fa-regular fa-id-card" style="color: var(--primary-color);"></i>
                    Student Information
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div><?php echo htmlspecialchars($student['name'] ?? '-'); ?></div>
                    </div>

                    <div class="info-item">
                        <label>Roll Number</label>
                        <div><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></div>
                    </div>

                    <div class="info-item">
                        <label>Email Address</label>
                        <div><?php echo htmlspecialchars($student['email'] ?? '-'); ?></div>
                    </div>

                    <div class="info-item">
                        <label>Branch / Department</label>
                        <div><?php echo htmlspecialchars($student['branch'] ?? '-'); ?></div>
                    </div>

                    <div class="info-item">
                        <label>Current Semester</label>
                        <div><?php echo htmlspecialchars($student['semester'] ?? '-'); ?></div>
                    </div>

                    <div class="info-item">
                        <label>Account Status</label>
                        <div style="background: transparent; border: none; padding: 0;">
                            <span class="status-active">
                                <i class="fa-solid fa-check-circle"></i> Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>

    </main>

</div>

</body>
</html>