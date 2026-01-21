<?php
$pageTitle = 'Students';
require_once __DIR__ . '/layout/header.php';

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

$selectedBranch   = $_GET['branch']   ?? '';
$selectedSemester = $_GET['semester'] ?? '';
?>

<div class="selection-container">
    <div class="selection-card">
        <h2>Select Branch and Semester</h2>
        <p class="selection-subtitle">Please select a branch and semester to view students</p>

        <form method="GET" action="students1.php" class="selection-form">
            <div class="form-group">
                <label for="branch">Branch <span class="required">*</span></label>
                <select id="branch" name="branch" required class="form-control">
                    <option value="">-- Select Branch --</option>
                    <?php foreach ($departments as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>"
                            <?php echo $selectedBranch === $value ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="semester">Semester <span class="required">*</span></label>
                <select id="semester" name="semester" required class="form-control">
                    <option value="">-- Select Semester --</option>
                    <?php
                    if ($selectedBranch && isset($maxSemesters[$selectedBranch])) {
                        for ($i = 1; $i <= $maxSemesters[$selectedBranch]; $i++) {
                            $isSelected = ($selectedSemester == $i) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($i) . "\" $isSelected>Semester " . htmlspecialchars($i) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block">View Students</button>
        </form>
    </div>
</div>

<script>
const maxSemesters = {
    'CSE': 8,
    'IT': 8,
    'AI': 8,
    'MCA': 4
};

document.addEventListener('DOMContentLoaded', function () {
    const branchSelect   = document.getElementById('branch');
    const semesterSelect = document.getElementById('semester');

    if (branchSelect && semesterSelect) {
        branchSelect.addEventListener('change', function () {
            const branch = this.value;
            semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';
            if (!branch) return;

            const maxSem = maxSemesters[branch] || 8;
            for (let i = 1; i <= maxSem; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = 'Semester ' + i;
                semesterSelect.appendChild(opt);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
