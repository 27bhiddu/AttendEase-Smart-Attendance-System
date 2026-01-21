<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auto_connect.php';

$error   = '';
$success = '';

/* Branch + semester config */
$branches = ['CSE', 'IT', 'AI', 'MCA'];

$maxSemesters = [
    'CSE' => 8,
    'IT'  => 8,
    'AI'  => 8,
    'MCA' => 4,
];

/**
 * Build "Semester X" string from numeric input.
 */
function formatSemesterLabel($value) {
    $num = preg_replace('/\D/', '', (string)$value);
    return $num !== '' ? 'Semester ' . $num : $value;
}

/* Handle single-student form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single_student'])) {

    $name       = $_POST['name']        ?? '';
    $rollNumber = $_POST['roll_number'] ?? '';
    $email      = $_POST['email']       ?? '';
    $branch     = $_POST['branch']      ?? '';
    $semester   = $_POST['semester']    ?? '';

    if (empty($name) || empty($rollNumber) || empty($branch) || empty($semester)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Normalize semester to "Semester X"
        $semesterLabel = formatSemesterLabel($semester);

        $payload = [
            'name'            => $name,
            'roll_number'     => $rollNumber,
            'email'           => $email,
            'branch'          => $branch,
            'semester'        => $semesterLabel, // e.g. "Semester 4"
            'total_classes'   => 0,
            'present_classes' => 0,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $error = 'Connection error: ' . $curlError;
        } elseif ($httpCode === 200 || $httpCode === 201) {
            header('Location: ' . BASE_PATH . 'add_student.php?create=success');
            exit();
        } else {
            $errorData = json_decode($response, true);
            $error = $errorData['errors'][0]['message'] ?? 'Error creating student. Please try again.';
        }
    }
}

if (isset($_GET['create']) && $_GET['create'] === 'success') {
    $success = 'Student added successfully.';
}

/* Preserve selected values after error */
$selectedBranch   = $_POST['branch']   ?? '';
$selectedSemester = $_POST['semester'] ?? '';

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-add-student">
    <div class="page-header">
        <h1 class="page-title">Add Student</h1>
    </div>

    <div class="row">
        <!-- Excel bulk upload form -->
        <div class="col-lg-4 col-md-5 order-lg-2 order-md-2 order-1 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Bulk Upload via Excel</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Excel columns should include:
                        <code>name, roll_number, email, branch, semester</code>.
                    </p>

                    <form
                        method="POST"
                        action="<?php echo BASE_PATH; ?>api/import_students_excel.php"
                        enctype="multipart/form-data"
                    >
                        <div class="mb-3">
                            <label class="form-label">Excel File (.xlsx / .xls)</label>
                            <input
                                type="file"
                                name="students_file"
                                class="form-control"
                                accept=".xlsx,.xls"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-success">
                            Upload Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Single-student form -->
        <div class="col-lg-8 col-md-7 order-lg-1 order-md-1 order-2 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Single Student</h5>
                </div>
                <div class="card-body">

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="add_single_student" value="1" />

                        <div class="mb-3">
                            <label class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="<?php echo !empty($error) ? htmlspecialchars($_POST['name'] ?? '') : ''; ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Roll Number <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="roll_number"
                                class="form-control"
                                value="<?php echo !empty($error) ? htmlspecialchars($_POST['roll_number'] ?? '') : ''; ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (optional)</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?php echo !empty($error) ? htmlspecialchars($_POST['email'] ?? '') : ''; ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Branch <span class="text-danger">*</span>
                            </label>
                            <select name="branch" id="branch" class="form-select" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($branch); ?>"
                                        <?php echo (!empty($error) && $selectedBranch === $branch) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($branch); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Semester <span class="text-danger">*</span>
                            </label>
                            <select name="semester" id="semester" class="form-select" required>
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Add Student
                        </button>
                    </form>
                </div>
            </div>
        </div>
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

    const currentBranch   = '<?php echo htmlspecialchars($selectedBranch ?: ''); ?>';
    const currentSemester = '<?php echo htmlspecialchars($selectedSemester ?: ''); ?>';

    function populateSemesters(branch, selected) {
        semesterSelect.innerHTML = '<option value="">Select Semester</option>';
        if (!branch) return;

        const max = maxSemesters[branch] || 8;
        for (let i = 1; i <= max; i++) {
            const opt = document.createElement('option');
            opt.value = i; // numeric, e.g. 4
            opt.textContent = 'Semester ' + i;
            if (String(i) === String(selected)) {
                opt.selected = true;
            }
            semesterSelect.appendChild(opt);
        }
    }

    // Initial population (on error, restore previous selection)
    populateSemesters(currentBranch || branchSelect.value, currentSemester);

    branchSelect.addEventListener('change', function () {
        populateSemesters(this.value, '');
    });
});
</script>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
