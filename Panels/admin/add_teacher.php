<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$pageTitle = 'Add Teacher';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($contact)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Send to Directus API directly
        $payload = [
            'username' => $username,
            'email' => $email,
            'contact' => $contact
        ];
        
        // Only add password if provided
        if (!empty($password)) {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/teachers');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $error = 'Connection error: ' . $curlError;
        } elseif ($httpCode === 200 || $httpCode === 201) {
            header('Location: ' . BASE_PATH . 'teachers.php?create=success');
            exit();
        } else {
            $errorData = json_decode($response, true);
            $error = $errorData['errors'][0]['message'] ?? 'Error creating teacher. Please try again.';
        }
    }
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="page-header">
    <h2>Add New Teacher</h2>
    <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">← Back to Teachers</a>
</div>

<div class="form-container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo BASE_PATH; ?>add_teacher.php" class="student-form">
        <div class="form-group">
            <label for="username">Username <span class="required">*</span></label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="contact">Contact <span class="required">*</span></label>
            <input type="text" id="contact" name="contact" required value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
            <small>Leave blank if password is managed elsewhere</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Teacher</button>
            <a href="<?php echo BASE_PATH; ?>teachers.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>





