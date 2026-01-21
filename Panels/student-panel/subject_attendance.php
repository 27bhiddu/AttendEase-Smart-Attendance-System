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

// --- 1. FETCH STUDENT DETAILS ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students/' . intval($studentId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response = curl_exec($ch);
$studentData = json_decode($response, true);
$student = $studentData['data'] ?? null;
curl_close($ch);

$studentBranch   = $student['branch'] ?? '';
$studentSemester = $student['semester'] ?? '';

// --- 2. FETCH ALL SUBJECTS ---
$subjectsList = [];
$subjectStats = []; 

if ($studentBranch && $studentSemester) {
    $subQuery = http_build_query([
        'filter' => [
            '_and' => [
                ['branch' => ['_eq' => $studentBranch]],
                ['semster' => ['_eq' => $studentSemester]] 
            ]
        ],
        'sort' => ['name'],
        'limit' => 100
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/subjects?' . $subQuery);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
    $response = curl_exec($ch);
    $subData = json_decode($response, true);
    $subjectsList = $subData['data'] ?? [];
    curl_close($ch);
}

// Initialize Stats
foreach ($subjectsList as $sub) {
    $name = $sub['name'];
    $subjectStats[$name] = [
        'present' => 0,
        'absent'  => 0,
        'total'   => 0,
        'theory'  => 0,
        'lab'     => 0,
        'percent' => 0
    ];
}

// --- 3. FETCH ATTENDANCE LOGS ---
$query = http_build_query([
    'filter' => [
        'student_id' => ['_eq' => $studentId],
    ],
    'limit' => 1000 
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/attendance?' . $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
$response = curl_exec($ch);
$data     = json_decode($response, true);
$allRecords = $data['data'] ?? [];
curl_close($ch);

// --- 4. CALCULATE STATS ---
foreach ($allRecords as $row) {
    $subName = $row['subject_name'] ?? 'General';
    $type    = strtolower($row['type'] ?? 'theory');
    $isPres  = !empty($row['present']);

    if (!isset($subjectStats[$subName])) {
        $subjectStats[$subName] = [
            'present' => 0, 'absent' => 0, 'total' => 0, 
            'theory' => 0, 'lab' => 0, 'percent' => 0
        ];
    }

    $subjectStats[$subName]['total']++;
    
    if ($isPres) {
        $subjectStats[$subName]['present']++;
    } else {
        $subjectStats[$subName]['absent']++;
    }

    if ($type === 'lab' || $type === 'laboratory') {
        $subjectStats[$subName]['lab']++;
    } else {
        $subjectStats[$subName]['theory']++;
    }
}

foreach ($subjectStats as &$stat) {
    if ($stat['total'] > 0) {
        $stat['percent'] = round(($stat['present'] / $stat['total']) * 100, 1);
    }
}
unset($stat);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Reports | AttendEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f8f9fc;
            --sidebar-width: 270px;
            --primary-color: #6366f1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .dashboard-layout { display: flex; width: 100%; min-height: 100vh; }

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

        /* --- SUBJECT CARDS GRID (Top Section) --- */
        .subjects-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px; margin-bottom: 40px;
        }
        .subject-card {
            background: white; border-radius: 16px; padding: 24px;
            border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .subject-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.08); }
        .sc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .sc-title { font-size: 1.1rem; font-weight: 800; color: var(--text-main); line-height: 1.4; max-width: 80%; }
        .sc-percent-badge { background: #f3e8ff; color: #7e22ce; padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; }
        .progress-track { width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-bottom:20px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); border-radius: 10px; }

        /* --- TABLE STYLES (Bottom Section) --- */
        .section-heading { font-size: 1.25rem; font-weight: 800; margin-bottom: 20px; color: var(--text-main); }
        
        .table-card {
            background: white; border-radius: 16px; border: 1px solid var(--border-color);
            overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .table-responsive { width: 100%; overflow-x: auto; }

        .custom-table {
            width: 100%; border-collapse: collapse; min-width: 800px;
        }

        .custom-table th {
            background: #f8fafc; color: var(--text-muted); font-weight: 700;
            text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;
            padding: 16px 24px; text-align: center; border-bottom: 1px solid var(--border-color);
        }
        .custom-table th:first-child { text-align: left; }

        .custom-table td {
            padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
            color: var(--text-main); font-size: 0.95rem; font-weight: 600;
            text-align: center; vertical-align: middle;
        }
        .custom-table td:first-child { text-align: left; font-weight: 700; color: var(--primary-color); }
        .custom-table tr:last-child td { border-bottom: none; }
        .custom-table tr:hover { background: #fcfcfc; }

        /* Table Badges */
        .badge-count {
            display: inline-block; padding: 4px 12px; border-radius: 6px;
            font-size: 0.9rem; font-weight: 700; min-width: 30px;
        }
        .bg-green-light { background: #dcfce7; color: #16a34a; }
        .bg-red-light { background: #fee2e2; color: #ef4444; }
        .bg-blue-light { background: #e0f2fe; color: #0284c7; }
        .bg-gray-light { background: #f1f5f9; color: #64748b; }

        /* Table Progress */
        .tbl-progress-wrapper {
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .tbl-progress-track {
            width: 60px; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden;
        }
        .tbl-progress-fill { height: 100%; background: #6366f1; border-radius: 10px; }

        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-wrapper { padding: 20px; }
            .subjects-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="brand"><i class="fa-solid fa-graduation-cap"></i> AttendEase</div>
        <div class="nav-label">Main Menu</div>
        <ul class="nav-items">
            <li><a href="student_dashboard.php"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
            <li><a href="student_profile.php"><i class="fa-regular fa-user"></i> My Profile</a></li>
            <li><a href="#" class="active"><i class="fa-solid fa-list-check"></i> Subject Report</a></li>
            <li><a href="student_helpdesk.php"><i class="fa-solid fa-headset"></i> Helpdesk</a></li>
        </ul>
    </aside>

    <main class="main-wrapper">
        <div class="page-header">
            <div class="page-title">
                <h1>Subject Report</h1>
                <p>Overview of your attendance performance.</p>
            </div>
        </div>

        <div class="subjects-grid">
            <?php foreach ($subjectStats as $subName => $stat): ?>
                <div class="subject-card">
                    <div class="sc-header">
                        <div class="sc-title"><?php echo htmlspecialchars($subName); ?></div>
                        <div class="sc-percent-badge"><?php echo $stat['percent']; ?>%</div>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $stat['percent']; ?>%;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:var(--text-muted); font-weight:600;">
                        <span>Attended: <strong style="color:#16a34a"><?php echo $stat['present']; ?></strong></span>
                        <span>Missed: <strong style="color:#ef4444"><?php echo $stat['absent']; ?></strong></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-heading">Detailed Subject Report</h2>
        
        <div class="table-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Attended</th>
                            <th>Missed</th>
                            <th>Total Classes</th>
                            <th>Attendance %</th>
                            <th>Theory</th>
                            <th>Laboratory</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjectStats)): ?>
                            <tr><td colspan="7" style="padding:40px; color:#94a3b8;">No subjects found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($subjectStats as $subName => $stat): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:8px; height:8px; background:var(--primary-color); border-radius:50%;"></div>
                                            <?php echo htmlspecialchars($subName); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-count bg-green-light"><?php echo $stat['present']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-count bg-red-light"><?php echo $stat['absent']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-count bg-blue-light"><?php echo $stat['total']; ?></span>
                                    </td>
                                    <td>
                                        <div class="tbl-progress-wrapper">
                                            <span style="min-width:40px; text-align:right; color:var(--primary-color);"><?php echo $stat['percent']; ?>%</span>
                                            <div class="tbl-progress-track">
                                                <div class="tbl-progress-fill" style="width: <?php echo $stat['percent']; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-count bg-gray-light">
                                            <i class="fa-solid fa-book" style="margin-right:5px; opacity:0.6;"></i> <?php echo $stat['theory']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-count bg-gray-light">
                                            <i class="fa-solid fa-flask" style="margin-right:5px; opacity:0.6;"></i> <?php echo $stat['lab']; ?>
                                        </span>
                                    </td>
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