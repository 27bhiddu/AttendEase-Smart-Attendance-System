<?php
$pageTitle = 'Students Detail';
require_once __DIR__ . '/layout/header.php';
?>
<style>
/* Shared page header (top bar) */
.page-header-modern {
    padding: 18px 24px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.page-header-left h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
}
.page-subtitle {
    margin-top: 4px;
    font-size: 13px;
    color: #6b7280;
}

/* Header buttons */
.page-header-right {
    display: flex;
    gap: 10px;
}
.page-header-right .btn {
    border-radius: 999px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
}

/* ===== Single student detail panel ===== */
.student-detail-wrapper {
    padding: 24px 16px 32px;
    box-sizing: border-box;
    display: block;
    height: auto;
}

.sd-panel {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid rgba(148,163,184,0.35);
    box-shadow: 0 24px 60px rgba(15,23,42,0.16);
    padding: 22px 26px 26px;
    width: 70%;
    max-width: 880px;
    margin-top: 4px;
}

/* Panel header: title + Edit / Back buttons */
.sd-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.sd-subtitle {
    margin: 0;
    font-size: 23px;
    color: #111827;
    font-weight: 600;
}
.sd-actions {
    display: flex;
    gap: 10px;
}
.sd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}
.sd-btn-primary {
    background-color: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}
.sd-btn-primary:hover { background-color: #4338ca; }
.sd-btn-light {
    background-color: #f3f4ff;
    border-color: #e0e7ff;
    color: #4f46e5;
}
.sd-btn-light:hover { background-color: #e0e7ff; }

/* Inner detail card inside panel */
.sd-card {
    margin-top: 8px;
    border-radius: 16px;
    background: #f9fafb;
    padding: 18px 24px 20px;
}

/* SINGLE STUDENT DETAIL TABLE */
.detail-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 6px; /* vertical gap */
    font-size: 14px;
}
.detail-table th,
.detail-table td {
    padding: 10px 4px;
}
.detail-table th {
    width: 160px;
    text-align: left;
    font-weight: 600;
    color: #494646;
    font-size: 15px;
     text-shadow: 0px 3px 5px rgba(11, 87, 34, 0.21);
}
.detail-table td {
    font-weight: 500;
    font-size: 18px;
    color: #111827;
    text-shadow: 0px 3px 5px rgba(23, 55, 199, 0.21);
}
.detail-eq {
    display: inline-block;
    margin: 0 40px 0 0;
    color: #9ca3af;
    font-weight: 600;
}

/* COUNT CARD for list view */
.card.students-count-card {
    margin: 10px 24px 16px;
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 18px;
    border: 1px solid rgba(15,23,42,0.06);
    box-shadow: 0 12px 32px rgba(15,23,42,0.06);
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
}
.card.students-count-card .label {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}
.card.students-count-card #students-count {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
}

/* TABLE for list view */
.table-wrapper {
    padding: 0 24px 24px;
}
.table-container-modern {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,0.25);
    box-shadow: 0 18px 45px rgba(15,23,42,0.08);
    overflow: hidden;
}
.data-table-modern {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.data-table-modern thead {
    background: #f9fafb;
}
.data-table-modern th,
.data-table-modern td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.data-table-modern th {
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.04em;
}
.data-table-modern tbody tr:hover {
    background-color: #f4f5fb;
}

/* ID pill */
.id-badge {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4f46e5;
    font-size: 11px;
    font-weight: 600;
}

/* Semester badge */
.semester-badge {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    background: #f5f3ff;
    color: #6d28d9;
    font-size: 11px;
    font-weight: 500;
}

/* Status badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 500;
}
.status-badge.active {
    background: #dcfce7;
    color: #166534;
}
.status-badge.graduated {
    background: #e0f2fe;
    color: #075985;
}

/* Filters on list view */
.filter-select {
    min-width: 190px;
    border-radius: 999px;
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    font-size: 13px;
    color: #111827;
    background-color: #ffffff;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.filter-select:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}

/* Empty / error states */
.empty-state {
    padding: 26px 0;
    text-align: center;
}
.empty-state .empty-icon {
    font-size: 34px;
    margin-bottom: 6px;
}
.loading,
.error {
    text-align: center;
    padding: 18px 0;
    font-size: 14px;
}
.error {
    color: #b91c1c;
}
</style>

<?php
// ================= SINGLE STUDENT DETAIL VIEW =================
$studentId = $_GET['id'] ?? null;
if ($studentId) {
    $url = DIRECTUS_BASE_URL . '/items/students/' . urlencode($studentId) .
        '?fields=' . urlencode('id,name,roll_number,branch,semester,total_classes,present_classes');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo '<div class="alert alert-error">Connection error: ' . htmlspecialchars($curlErr) . '</div>';
        require_once __DIR__ . '/layout/footer.php';
        exit;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        echo '<div class="alert alert-error">Error fetching student (HTTP ' . intval($httpCode) . ')</div>';
        require_once __DIR__ . '/layout/footer.php';
        exit;
    }

    $data    = json_decode($resp, true);
    $student = $data['data'] ?? $data;

    if (!$student || !isset($student['id'])) {
        echo '<div class="alert alert-error">Student not found.</div>';
        require_once __DIR__ . '/layout/footer.php';
        exit;
    }

    $id       = htmlspecialchars($student['id']);
    $name     = htmlspecialchars($student['name'] ?? '-');
    $roll     = htmlspecialchars($student['roll_number'] ?? $student['roll_no'] ?? '-');
    $branch   = htmlspecialchars($student['branch'] ?? $student['department'] ?? '-');
    $semester = htmlspecialchars(preg_replace('/\D/', '', (string)($student['semester'] ?? '')) ?: ($student['semester'] ?? '-'));
    $total    = isset($student['total_classes']) ? (int)$student['total_classes'] : 0;
    $present  = isset($student['present_classes']) ? (int)$student['present_classes'] : 0;
    $attendance = $total > 0 ? round(($present / $total) * 100, 2) : 0;
?>

<div class="student-detail-wrapper">
    <div class="sd-panel">
        <div class="sd-header-row">
            <p class="sd-subtitle">Detail for <?php echo $name; ?></p>
            <div class="sd-actions">
                <a href="<?php echo BASE_PATH; ?>edit_student.php?id=<?php echo urlencode($student['id']); ?>"
                   class="sd-btn sd-btn-primary">Edit</a>
                <a href="<?php echo BASE_PATH; ?>students1.php?branch=<?php
                    echo urlencode($branch); ?>&semester=<?php
                    echo urlencode($semester); ?>"
                   class="sd-btn sd-btn-light">Back to list</a>
            </div>
        </div>

        <div class="sd-card">
            <table class="detail-table">
                <tr>
                    <th>ID</th>
                    <td><span class="detail-eq">=</span><span><?php echo $id; ?></span></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td><span class="detail-eq">=</span><span><?php echo $name; ?></span></td>
                </tr>
                <tr>
                    <th>Roll Number</th>
                    <td><span class="detail-eq">=</span><span><?php echo $roll; ?></span></td>
                </tr>
                <tr>
                    <th>Branch</th>
                    <td><span class="detail-eq">=</span><span><?php echo $branch; ?></span></td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td><span class="detail-eq">=</span><span><?php echo $semester; ?></span></td>
                </tr>
                <tr>
                    <th>Attendance</th>
                    <td><span class="detail-eq">=</span><span><?php echo number_format($attendance, 2); ?>%</span></td>
                </tr>
                <tr>
                    <th>Present / Total</th>
                    <td><span class="detail-eq">=</span><span><?php echo $present . ' / ' . $total; ?></span></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php
    require_once __DIR__ . '/layout/footer.php';
    exit;
}
?>

<!-- ================= ALL STUDENTS LIST ================= -->

<div class="page-header-modern">
    <div class="page-header-left">
        <h2>All Students Detail</h2>
        <p class="page-subtitle">Complete list of students with attendance criteria</p>
    </div>
    <div class="page-header-right">
        <select id="branch-filter" class="filter-select" onchange="loadStudents()">
            <option value="">All Branches</option>
            <option value="CSE">CSE</option>
            <option value="IT">IT</option>
            <option value="AI">AI</option>
            <option value="MCA">MCA</option>
        </select>
        <select id="semester-filter" class="filter-select" onchange="loadStudents()">
            <option value="">All Semesters</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
</div>

<div class="card students-count-card">
    <span class="label">Number of Students:</span>
    <span id="students-count">0</span>
</div>

<div class="table-wrapper">
    <div class="table-container-modern">
        <table class="data-table-modern" id="students-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Roll Number</th>
                <th>Branch</th>
                <th>Semester</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody id="students-tbody">
            <tr>
                <td colspan="6" class="loading">Loading students...</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
});

function loadStudents() {
    const branch = document.getElementById('branch-filter').value;
    let semester = document.getElementById('semester-filter').value;
    const countEl = document.getElementById('students-count');

    countEl.textContent = 'Loading...';

    if (semester) {
        semester = parseInt(semester);
        if (isNaN(semester)) semester = '';
    }

    fetch(BASE_PATH + 'api/get_students.php')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('students-tbody');

            if (!data.success) {
                countEl.textContent = 0;
                tbody.innerHTML = `<tr><td colspan="6" class="error">Error loading students</td></tr>`;
                return;
            }

            let students = data.data || [];

            if (branch) {
                students = students.filter(s => (s.branch || s.department) === branch);
            }

            if (semester) {
                students = students.filter(s => {
                    let sem = s.semester;
                    if (typeof sem === 'string') sem = parseInt(sem.replace(/[^0-9]/g, ''));
                    return sem === semester;
                });
            }

            countEl.textContent = students.length;

            if (students.length > 0) {
                displayStudents(students);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="no-data">
                            <div class="empty-state">
                                <div class="empty-icon">👥</div>
                                <p>No students found</p>
                            </div>
                        </td>
                    </tr>`;
            }
        })
        .catch(() => {
            document.getElementById('students-count').textContent = 0;
            document.getElementById('students-tbody').innerHTML =
                `<tr><td colspan="6" class="error">Error loading students</td></tr>`;
        });
}

function displayStudents(students) {
    const tbody = document.getElementById('students-tbody');

    tbody.innerHTML = students.map(student => {
        const rollNo  = student.roll_number || student.roll_no || '-';
        const semester = formatSemester(student.semester);
        const status  = getStatus(student);
        const branch  = student.branch || student.department || '-';

        return `
            <tr>
                <td><span class="id-badge">#${student.id}</span></td>
                <td><strong>${escapeHtml(student.name || '-')}</strong></td>
                <td>${escapeHtml(rollNo)}</td>
                <td>${escapeHtml(branch)}</td>
                <td><span class="semester-badge">${semester}</span></td>
                <td><span class="status-badge ${status.class}">${status.text}</span></td>
            </tr>
        `;
    }).join('');
}

function formatSemester(sem) {
    if (!sem) return '-';
    let s = String(sem).replace(/semester/gi, '').replace(/[^0-9]/g, '');
    return s ? `Semester ${s}` : '-';
}

function getStatus(student) {
    const branch = (student.branch || student.department || '').toUpperCase();
    const semester = parseInt(student.semester) || 0;

    if (branch === 'MCA' && semester >= 4) return { class: 'graduated', text: 'Graduated' };
    if (branch !== 'MCA' && semester >= 8) return { class: 'graduated', text: 'Graduated' };

    return { class: 'active', text: 'Active' };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
