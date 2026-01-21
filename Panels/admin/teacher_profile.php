<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Teacher Profile';
$teacher = null;
$attendanceData = null;
$teacherId = $_GET['id'] ?? null;

if (!$teacherId) {
    header('Location: ' . BASE_PATH . 'teachers.php');
    exit();
}

// Load teacher data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers/' . urlencode($teacherId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
if ($httpCode === 200 && isset($result['data'])) {
    $teacher = $result['data'];
}

// Load attendance data
if ($teacher) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/attendance?filter[teacher_id][_eq]=' . urlencode($teacherId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $attResult = json_decode($response, true);
        $attendanceRecords = $attResult['data'] ?? [];
        
        // Count unique dates
        $uniqueDates = [];
        $attendanceByDate = [];
        foreach ($attendanceRecords as $record) {
            if (isset($record['date'])) {
                $date = is_array($record['date']) ? $record['date']['date'] : $record['date'];
                $dateKey = date('Y-m-d', strtotime($date));
                if (!isset($attendanceByDate[$dateKey])) {
                    $attendanceByDate[$dateKey] = [];
                    $uniqueDates[] = $dateKey;
                }
                $attendanceByDate[$dateKey][] = $record;
            }
        }
        
        $attendanceData = [
            'total_records' => count($attendanceRecords),
            'unique_dates' => count($uniqueDates),
            'attendance_dates' => $uniqueDates,
            'attendance_by_date' => $attendanceByDate,
            'records' => $attendanceRecords
        ];
    }
}

require_once __DIR__ . '/layout/header.php';
?>

<?php if (!$teacher): ?>
    <div class="alert alert-error">Teacher not found.</div>
    <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">Back to Teachers</a>
<?php else: ?>
    <div class="page-header">
        <h2>Teacher Profile</h2>
        <div>
            <a href="<?php echo BASE_PATH; ?>edit_teacher.php?id=<?php echo htmlspecialchars($teacherId); ?>" class="btn btn-edit">Edit Profile</a>
            <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">← Back to Teachers</a>
        </div>
    </div>

    <div class="profile-container">
        <!-- Teacher Info Card -->
        <div class="profile-card">
            <h3>Teacher Information</h3>
            <div class="profile-info">
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($teacher['id'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($teacher['username'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <span class="info-value"><?php echo htmlspecialchars($teacher['contact'] ?? '-'); ?></span>
                </div>
                <?php if (isset($teacher['created_at'])): ?>
                <div class="info-row">
                    <span class="info-label">Joined:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($teacher['created_at'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance Statistics -->
        <div class="stats-grid" style="margin-top: 30px;">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?php echo $attendanceData ? $attendanceData['unique_dates'] : '0'; ?></h3>
                    <p>Attendance Sessions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-info">
                    <h3><?php echo $attendanceData ? $attendanceData['total_records'] : '0'; ?></h3>
                    <p>Total Records</p>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <?php if ($attendanceData && count($attendanceData['attendance_dates']) > 0): ?>
        <div class="attendance-history" style="margin-top: 30px;">
            <h3>Attendance History</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Records Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dates = $attendanceData['attendance_dates'];
                        rsort($dates); // Most recent first
                        foreach ($dates as $date): 
                            $records = $attendanceData['attendance_by_date'][$date];
                            $firstRecord = $records[0];
                        ?>
                        <tr>
                            <td><?php echo date('F j, Y', strtotime($date)); ?></td>
                            <td><?php echo htmlspecialchars($firstRecord['branch'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($firstRecord['semester'] ?? '-'); ?></td>
                            <td><?php echo count($records); ?> student(s)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="no-data" style="margin-top: 30px; padding: 40px; text-align: center; color: var(--text-secondary);">
            <p>No attendance records found for this teacher.</p>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
.profile-container {
    max-width: 1200px;
}

.profile-card {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 30px;
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
}

.profile-card h3 {
    margin-bottom: 20px;
    color: var(--text-primary);
    font-size: 20px;
}

.profile-info {
    display: grid;
    gap: 15px;
}

.info-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--text-secondary);
    min-width: 120px;
}

.info-value {
    color: var(--text-primary);
}

.attendance-history h3 {
    margin-bottom: 20px;
    color: var(--text-primary);
    font-size: 20px;
}
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>





