<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Edit Teacher';
$error = '';
$teacher = null;
$teacherId = $_GET['id'] ?? null;

if (!$teacherId) {
    header('Location: ' . BASE_PATH . 'teachers.php');
    exit();
}

// Load teacher data from Directus
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

if (!$teacher) {
    $error = 'Teacher not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacher) {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $contact  = $_POST['contact'] ?? '';
    
    if (empty($username) || empty($email) || empty($contact)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Send to Directus API directly
        $payload = [
            'username' => $username,
            'email'    => $email,
            'contact'  => $contact,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers/' . urlencode($teacherId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $error = 'Connection error: ' . $curlError;
        } elseif ($httpCode === 200 || $httpCode === 201 || $httpCode === 204) {
            header('Location: ' . BASE_PATH . 'teachers.php?update=success');
            exit();
        } else {
            $errorData = json_decode($response, true);
            $error = $errorData['errors'][0]['message'] ?? 'Error updating teacher. Please try again.';
        }
    }
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <h2>Edit Teacher</h2>
    <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">← Back to Teachers</a>
</div>

<div class="form-container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!$teacher): ?>
        <div class="alert alert-error">Teacher not found.</div>
        <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">Back to Teachers</a>
    <?php else: ?>
        <form method="POST"
              action="<?php echo BASE_PATH; ?>edit_teacher.php?id=<?php echo htmlspecialchars($teacherId); ?>"
              class="student-form">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? $teacher['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? $teacher['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="contact">Contact <span class="required">*</span></label>
                <input
                    type="text"
                    id="contact"
                    name="contact"
                    required
                    value="<?php echo htmlspecialchars($_POST['contact'] ?? $teacher['contact'] ?? ''); ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Teacher</button>
                <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
