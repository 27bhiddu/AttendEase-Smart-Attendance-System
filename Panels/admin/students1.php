<?php
$pageTitle = 'Students';
require_once __DIR__ . '/layout/header.php';

if (isset($_GET['delete']) && $_GET['delete'] === 'success') {
    echo '<div class="alert alert-success">Student deleted successfully!</div>';
}
if (isset($_GET['delete']) && $_GET['delete'] === 'error') {
    echo '<div class="alert alert-error">Error deleting student. Please try again.</div>';
}
if (isset($_GET['create']) && $_GET['create'] === 'success') {
    echo '<div class="alert alert-success">Student created successfully!</div>';
}
if (isset($_GET['update']) && $_GET['update'] === 'success') {
    echo '<div class="alert alert-success">Student updated successfully!</div>';
}

$selectedBranch   = $_GET['branch']   ?? '';
$selectedSemester = $_GET['semester'] ?? '';

// If called without branch+semester, go back to selector (students.php)
if (!$selectedBranch || !$selectedSemester) {
    header('Location: students.php');
    exit;
}

$departments = [
    'CSE' => 'CSE (Computer Science)',
    'IT'  => 'IT (Information Technology)',
    'AI'  => 'AI (Artificial Intelligence)',
    'MCA' => 'MCA',
];

$maxSemesters = [
    'CSE' => 8,
    'IT'  => 8,
    'AI'  => 8,
    'MCA' => 4,
];

$matchedStudentsCount = 0;
?>

<!-- Header -->
<div class="page-header-modern">
    <div class="page-header-left">
        <h2>Students Management</h2>
        <p class="page-subtitle">
            <?php echo htmlspecialchars($departments[$selectedBranch] ?? $selectedBranch); ?>
            - Semester <?php echo htmlspecialchars($selectedSemester); ?>
        </p>
    </div>
    <div class="page-header-right">
        <!-- Branch filter -->
        <select id="branch-filter" class="filter-select" onchange="updateFilters()">
            <?php foreach ($departments as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo $selectedBranch === $value ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Semester filter -->
        <select id="semester-filter" class="filter-select" onchange="updateFilters()">
            <?php
            if ($selectedBranch && isset($maxSemesters[$selectedBranch])) {
                for ($i = 1; $i <= $maxSemesters[$selectedBranch]; $i++) {
                    $isSelected = ($selectedSemester == $i) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($i) . "\" $isSelected>Semester " . htmlspecialchars($i) . "</option>";
                }
            }
            ?>
        </select>

        <a href="<?php echo BASE_PATH; ?>add_student.php" class="btn btn-primary">
            <span>➕</span> Add New Student
        </a>
    </div>
</div>

<!-- Students count card -->
<div class="card" style="margin-bottom:15px; box-shadow:0 8px 20px rgba(0,0,0,0.12); border-radius:8px;">
    <div style="font-size:18px; font-weight:600;">
        Students Found:
        <span style="font-size:26px; font-weight:700; margin-left:6px;" id="students-count">
            0
        </span>
    </div>
</div>

<!-- Students table -->
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
                <th>Action</th>
            </tr>
            </thead>
            <tbody id="students-tbody">
            <?php
            $studentsRowsHtml = '';

            $apiUrl = DIRECTUS_BASE_URL . '/items/students?fields=' .
                urlencode('id,name,roll_number,branch,semester,total_classes,present_classes');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $apiResponse = curl_exec($ch);
            $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError   = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo '<tr><td colspan="7" class="error">Connection error: ' .
                    htmlspecialchars($curlError) . '</td></tr>';
            } elseif ($httpCode !== 200 && $httpCode !== 201) {
                echo '<tr><td colspan="7" class="error">Error fetching students (HTTP ' .
                    intval($httpCode) . ').</td></tr>';
            } else {
                $data     = json_decode($apiResponse, true);
                $students = [];
                if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
                    $students = $data['data'];
                }

                $fetchedCount = is_array($students) ? count($students) : 0;

                foreach ($students as $s) {
                    $rawBranch      = trim((string)($s['branch'] ?? $s['department'] ?? ''));
                    $rawBranchUpper = strtoupper($rawBranch);
                    $selBranchUpper = strtoupper(trim((string)$selectedBranch));

                    $rawSem = trim((string)($s['semester'] ?? ''));
                    $semNum = preg_replace('/\D/', '', $rawSem);
                    $semVal = $semNum !== '' ? $semNum : $rawSem;

                    $branchMatches   = ($rawBranchUpper === $selBranchUpper);
                    $semesterMatches = (strval($semVal) === strval($selectedSemester));

                    if ($branchMatches && $semesterMatches) {
                        $matchedStudentsCount++;

                        $id            = htmlspecialchars($s['id'] ?? '');
                        $name          = htmlspecialchars($s['name'] ?? '-');
                        $roll          = htmlspecialchars($s['roll_number'] ?? $s['roll_no'] ?? '-');
                        $branchLabel   = htmlspecialchars($s['branch'] ?? $s['department'] ?? $selectedBranch);
                        $semesterLabel = 'Semester ' . htmlspecialchars($semVal);

                        $branchUpper = strtoupper($branchLabel);
                        $statusText  = 'Active';
                        if (($branchUpper === 'MCA' && intval($semVal) >= 4) ||
                            ($branchUpper !== 'MCA' && intval($semVal) >= 8)) {
                            $statusText = 'Graduated';
                        }

                        $studentsRowsHtml .= "<tr>" .
                            "<td><span class=\"id-badge\">#" . $id . "</span></td>" .
                            "<td><div class=\"student-info\"><strong>" . $name . "</strong></div></td>" .
                            "<td>" . $roll . "</td>" .
                            "<td><span class=\"attendance-badge medium\">" . $branchLabel . "</span></td>" .
                            "<td><span class=\"semester-badge\">" . $semesterLabel . "</span></td>" .
                            "<td><span class=\"status-badge active\">" . $statusText . "</span></td>" .
                            "<td class=\"actions\"><div class=\"action-buttons\">" .
                                "<a href=\"" . BASE_PATH . "delete_student.php?id=" .
                                    urlencode($s['id']) .
                                    "\" class=\"btn-action btn-delete\" title=\"Delete Student\" " .
                                    "onclick=\"return confirm('Are you sure you want to delete this student?')\">" .
                                    "<span>Delete</span></a>" .
                                "<a href=\"" . BASE_PATH . "students_detail.php?id=" .
                                    urlencode($s['id']) .
                                    "\" class=\"btn-action btn-view\" title=\"View Details\">" .
                                    "<span>View</span></a>" .
                            "</div></td>" .
                            "</tr>";
                    }
                }

                if ($studentsRowsHtml === '') {
                    echo '<tr><td colspan="7" class="no-data"><div class="empty-state">' .
                        '<div class="empty-icon">👥</div>' .
                        '<p>No students found for selected criteria</p>' .
                        '<p style="font-size:12px;color:#666;margin-top:8px;">(Fetched ' .
                        intval($fetchedCount) .
                        ' students from Directus)</p>' .
                        '<a href="add_student.php" class="btn btn-primary">Add Your First Student</a>' .
                        '</div></td></tr>';
                } else {
                    echo $studentsRowsHtml;
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>


<script>
// update Students Found count
document.getElementById('students-count').textContent =
    '<?php echo intval($matchedStudentsCount); ?>';

const maxSemesters = {
    'CSE': 8,
    'IT': 8,
    'AI': 8,
    'MCA': 4
};

function updateFilters() {
    const branchSelect   = document.getElementById('branch-filter');
    const semesterSelect = document.getElementById('semester-filter');

    const branch   = branchSelect ? branchSelect.value : '';
    const semester = semesterSelect ? semesterSelect.value : '';

    if (branch && semester) {
        window.location.href =
            BASE_PATH + 'students1.php?branch=' +
            encodeURIComponent(branch) +
            '&semester=' + encodeURIComponent(semester);
    }
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
