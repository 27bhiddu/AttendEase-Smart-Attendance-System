<?php
$pageTitle = 'Edit Student';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/config.php';

$id        = $_GET['id'] ?? null;
$student   = null;
$errorMsg  = '';
$successMsg = '';

if (!$id) {
    header('Location: students.php');
    exit;
}

/* Branch + semester config */
$branches = ['CSE', 'IT', 'AI', 'MCA'];

$maxSemesters = [
    'CSE' => 8,
    'IT'  => 8,
    'AI'  => 8,
    'MCA' => 4,
];

/* ========= STEP 1: Handle POST (Update vs Cancel) ========= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    // If Cancel pressed, go back to detail page without updating
    if ($action === 'cancel') {
        header('Location: ' . BASE_PATH . 'students_detail.php?id=' . urlencode($id));
        exit;
    }

    // Otherwise, perform update
    $name     = trim($_POST['name']        ?? '');
    $roll     = trim($_POST['roll_number'] ?? '');
    $email    = trim($_POST['email']       ?? '');
    $branch   = trim($_POST['branch']      ?? '');
    $semesterInput = trim($_POST['semester'] ?? '');

    // Normalize semester to "Semester X"
    $semNum = preg_replace('/\D/', '', $semesterInput);
    $semesterFormatted = $semNum !== '' ? 'Semester ' . $semNum : $semesterInput;

    $payload = [
        'name'        => $name,
        'roll_number' => $roll,
        'email'       => $email,
        'branch'      => $branch,
        'semester'    => $semesterFormatted,
    ];

    $url = DIRECTUS_BASE_URL . '/items/students/' . urlencode($id);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
        getDirectusHeaders(),
        ['Content-Type: application/json']
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        $errorMsg = 'Connection error while updating: ' . htmlspecialchars($curlErr);
    } elseif ($httpCode < 200 || $httpCode >= 300) {
        $errorMsg = 'Error updating student (HTTP ' . intval($httpCode) . ')';
    } else {
        $successMsg = 'Student updated successfully.';
    }
}

/* ========= STEP 2: Fetch latest student data for the form ========= */
$url = DIRECTUS_BASE_URL . '/items/students/' . urlencode($id) .
    '?fields=' . urlencode('id,name,roll_number,email,branch,semester');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    $errorMsg = 'Connection error: ' . htmlspecialchars($curlErr);
} elseif ($httpCode !== 200 && $httpCode !== 201) {
    $errorMsg = 'Error loading student (HTTP ' . intval($httpCode) . ').';
} else {
    $data    = json_decode($resp, true);
    $student = $data['data'] ?? $data;
}

$name   = htmlspecialchars($student['name']        ?? '');
$roll   = htmlspecialchars($student['roll_number'] ?? '');
$email  = htmlspecialchars($student['email']       ?? '');
$branch = htmlspecialchars($student['branch']      ?? '');

/* Extract numeric semester for dropdown */
$semesterNum = preg_replace('/\D/', '', (string)($student['semester'] ?? ''));
$semester    = htmlspecialchars($semesterNum !== '' ? $semesterNum : '');
?>

<style>
.edit-student-wrapper {
    padding: 24px 16px 32px;
    box-sizing: border-box;
}
.edit-panel {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid rgba(148,163,184,0.35);
    box-shadow: 0 24px 60px rgba(15,23,42,0.16);
    padding: 22px 26px 26px;
    width: 70%;
    max-width: 880px;
}
.edit-form .form-group {
    margin-bottom: 14px;
}
.edit-form label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 4px;
}
.edit-form .form-control {
    width: 100%;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    padding: 8px 10px;
    font-size: 14px;
    outline: none;
    background-color: #ffffff;
}
.edit-form .form-control:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79,70,229,0.18);
}
</style>

<div class="page-header-modern">
    <div class="page-header-left">
        <h2>Edit Student</h2>
        <p class="page-subtitle">
            Update details for ID #<?php echo htmlspecialchars($id); ?>
        </p>
    </div>
    <div class="page-header-right">
        <a href="<?php echo BASE_PATH; ?>students_detail.php?id=<?php echo urlencode($id); ?>"
           class="btn btn-light">Back to detail</a>
    </div>
</div>

<div class="edit-student-wrapper">
    <div class="edit-panel">
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?php echo $errorMsg; ?></div>
        <?php elseif ($successMsg): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>

        <form method="post" class="sd-card edit-form">
            <div class="form-group">
                <label for="name">Name</label>
                <input id="name"
                       type="text"
                       name="name"
                       class="form-control"
                       value="<?php echo $name; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="roll_number">Roll Number</label>
                <input id="roll_number"
                       type="text"
                       name="roll_number"
                       class="form-control"
                       value="<?php echo $roll; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email"
                       type="email"
                       name="email"
                       class="form-control"
                       value="<?php echo $email; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="branch">Branch</label>
                <select id="branch"
                        name="branch"
                        class="form-control"
                        required>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo htmlspecialchars($b); ?>"
                            <?php echo ($branch === $b) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="semester">Semester</label>
                <select id="semester"
                        name="semester"
                        class="form-control"
                        required>
                    <!-- options filled by JS -->
                </select>
            </div>

            <div style="margin-top:16px; display:flex; gap:10px;">
                <button type="submit"
                        name="action"
                        value="update"
                        class="btn btn-primary">
                    Update
                </button>

                <button type="submit"
                        name="action"
                        value="cancel"
                        class="btn btn-light">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const maxSemesters = {
    CSE: 8,
    IT: 8,
    AI: 8,
    MCA: 4
};

document.addEventListener('DOMContentLoaded', function () {
    const branchSelect   = document.getElementById('branch');
    const semesterSelect = document.getElementById('semester');

    const currentBranch   = '<?php echo $branch; ?>';
    const currentSemester = '<?php echo $semester; ?>'; // numeric string

    function populateSemesters(branch, selectedSem) {
        semesterSelect.innerHTML = '';
        if (!branch) return;

        const max = maxSemesters[branch] || 8;
        for (let i = 1; i <= max; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = 'Semester ' + i;
            if (String(i) === String(selectedSem)) {
                opt.selected = true;
            }
            semesterSelect.appendChild(opt);
        }
    }

    // Initial fill based on current data
    populateSemesters(currentBranch || branchSelect.value, currentSemester);

    branchSelect.addEventListener('change', function () {
        populateSemesters(this.value, '');
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
