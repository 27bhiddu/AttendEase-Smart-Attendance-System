<?php
// subject_details.php - Complete single file with inline PHP + CSS + HTML
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/student_auth.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$studentId = $_SESSION['student_id'] ?? null;
$subjectId = $_GET['id'] ?? null;

if (!$studentId || !$subjectId) {
    header('Location: subject_attendance.php'); exit;
}

// 1. FETCH SUBJECT NAME
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/subjects/' . intval($subjectId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$subResponse = curl_exec($ch);
$subject = json_decode($subResponse, true)['data'] ?? null;
$subjectName = $subject['name'] ?? 'Unknown Subject';
curl_close($ch);

// 2. FETCH ATTENDANCE FOR STUDENT + SUBJECT
$query = http_build_query([
    'filter' => [
        '_and' => [
            ['student_id' => ['_eq' => $studentId]],
            ['subject_id' => ['_eq' => $subjectId]]
        ]
    ],
    'sort' => ['date' => '-1'],  // Newest first
    'limit' => 500
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/attendance?' . $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response = curl_exec($ch);
$attData = json_decode($response, true);
$records = $attData['data'] ?? [];
curl_close($ch);

// 3. QUICK STATS
$present = $absent = $total = 0;
foreach ($records as $row) {
    $total++;
    if (!empty($row['present'])) $present++; else $absent++;
}
$percent = $total ? round(($present / $total) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subjectName); ?> Details | AttendEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f8f9fc; --sidebar-width: 270px; --primary-color: #6366f1;
            --text-main: #0f172a; --text-muted: #64748b; --card-bg: #ffffff; --border-color: #e2e8f0;
        }
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .dashboard-layout { display: flex; width: 100%; min-height: 100vh; }

        /* Sidebar (unchanged from subject_attendance.php) */
        .sidebar { width: var(--sidebar-width); background: var(--card-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; flex-shrink: 0; position: sticky; top: 0; height: 100vh; z-index: 50; padding: 24px 0; }
        .brand { padding: 0 24px 32px; display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; color: var(--primary-color); }
        .nav-label { padding: 0 24px; margin-bottom: 12px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .nav-items { list-style: none; padding: 0; margin: 0 12px; display: flex; flex-direction: column; gap: 6px; }
        .nav-items a { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease; }
        .nav-items a:hover { background-color: #f8fafc; color: var(--primary-color); }
        .nav-items a.active { background-color: #eef2ff; color: var(--primary-color); }

        .main-wrapper { flex-grow: 1; padding: 30px 40px; display: flex; flex-direction: column; }
        .page-header { margin-bottom: 30px; }
        .page-title h1 { margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--text-main); }
        .page-title p { margin: 5px 0 0; color: var(--text-muted); }

        /* Subject Header Card */
        .subject-header { background: var(--card-bg); border-radius: 16px; padding: 28px; border: 1px solid var(--border-color); margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .sh-stats { display: flex; gap: 24px; flex-wrap: wrap; }
        .stat-box { text-align: center; flex: 1; min-width: 120px; }
        .stat-number { font-size: 2.2rem; font-weight: 800; color: var(--primary-color); }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

        /* Table Styles (enhanced from previous) */
        .section-heading { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; color: var(--text-main); }
        .table-card { background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table-responsive { width: 100%; overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .custom-table th { background: #f8fafc; color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 16px 24px; text-align: left; border-bottom: 2px solid var(--border-color); }
        .custom-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: var(--text-main); font-weight: 600; vertical-align: middle; }
        .custom-table tr:hover { background: #fcfcfc; }

        /* Badges */
        .badge-count { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; }
        .bg-green-light { background: #dcfce7; color: #16a34a; }
        .bg-red-light { background: #fee2e2; color: #ef4444; }
        .bg-gray-light { background: #f1f5f9; color: #64748b; }

        /* Back Button */
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--primary-color); text-decoration: none; font-weight: 600; margin-bottom: 20px; padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 8px; transition: all 0.2s; }
        .back-link:hover { background: #eef2ff; }

        @media (max-width: 1024px) { .sidebar { display: none; } .main-wrapper { padding: 20px; } .sh-stats { flex-direction: column; gap: 16px; } }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-graduation-cap"></i> AttendEase</div>
        <div class="nav-label">Main Menu</div>
        <ul class="nav-items">
            <li><a href="student_dashboard.php"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
            <li><a href="student_profile.php"><i class="fa-regular fa-user"></i> Profile</a></li>
            <li><a href="subject_attendance.php" class="active"><i class="fa-solid fa-list-check"></i> Subjects</a></li>
            <li><a href="student_helpdesk.php"><i class="fa-solid fa-headset"></i> Helpdesk</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        <div class="page-header">
            <div class="page-title">
                <h1><?php echo htmlspecialchars($subjectName); ?></h1>
                <p>Detailed attendance records (<?php echo $total; ?> classes)</p>
            </div>
        </div>

        <a href="subject_attendance.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Subjects</a>

        <!-- Subject Stats Header -->
        <div class="subject-header">
            <div class="sh-stats">
                <div class="stat-box">
                    <div class="stat-number" style="color: #16a34a;"><?php echo $present; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #ef4444;"><?php echo $absent; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: var(--primary-color);"><?php echo $total; ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="font-size: 1.8rem;"><?php echo $percent; ?><span style="font-size: 1rem;">%</span></div>
                    <div class="stat-label">Attendance</div>
                </div>
            </div>
        </div>

        <h2 class="section-heading">Class Logs</h2>
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="4" style="padding: 60px; text-align: center; color: #94a3b8; font-style: italic;">
                                <i class="fa-solid fa-clock" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i><br>
                                No attendance records yet for this subject.<br>
                                <small>Your first class is coming soon!</small>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['date'] ?? 'N/A'); ?></strong></td>
                                    <td><span class="badge-count bg-gray-light"><?php echo ucfirst(strtolower($row['type'] ?? 'Theory')); ?></span></td>
                                    <td>
                                        <span class="badge-count <?php echo !empty($row['present']) ? 'bg-green-light' : 'bg-red-light'; ?>">
                                            <?php echo !empty($row['present']) ? 'Present' : 'Absent'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['notes'] ?? '-') ?: '<em>No notes</em>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
