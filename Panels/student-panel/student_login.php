<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/student_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → redirect to dashboard
if (!empty($_SESSION['student_id'])) {
    header('Location: student_dashboard.php');
    exit();
}

$error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $query = http_build_query([
            'filter' => [
                'email' => ['_eq' => $email],
            ],
            'limit' => 1,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, DIRECTUS_BASE_URL . '/items/students?' . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, getDirectusHeaders());
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $error = 'Connection error: ' . $curlError;
        } elseif ($httpCode !== 200) {
            $error = 'Error fetching student record (HTTP ' . (int)$httpCode . ').';
        } else {
            $data     = json_decode($response, true);
            $students = $data['data'] ?? [];

            if (count($students) !== 1) {
                $error = 'Invalid email or password.';
            } else {
                $student    = $students[0];
                $storedRoll = strtoupper($student['roll_number'] ?? '');
                $inputPass  = strtoupper($password);

                if ($storedRoll === '' || $storedRoll !== $inputPass) {
                    $error = 'Invalid email or password.';
                } else {
                    $_SESSION['student_id']    = $student['id'];
                    $_SESSION['student_name']  = $student['name'] ?? '';
                    $_SESSION['student_email'] = $student['email'] ?? '';

                    header('Location: student_dashboard.php');
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login | AttendEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 24px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.1);
            padding: 40px 36px;
            position: relative;
            z-index: 1;
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 8px;
            text-align: center;
        }

        .login-subtitle {
            margin: 0 0 30px;
            font-size: 0.95rem;
            color: #64748b;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 12px 14px;
            font-size: 0.95rem;
            color: #0f172a;
            background-color: #f8fafc;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .password-wrapper { position: relative; width: 100%; }
        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #94a3b8; font-size: 1rem; transition: color 0.2s;
        }
        .toggle-password:hover { color: #6366f1; }

        .alert {
            border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; font-weight: 500;
        }
        .alert-danger { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }

        .field-hint { font-size: 0.85rem; color: #475569; margin-top: 8px; line-height: 1.5; }
        .example-badge {
            display: inline-block; background: #eef2ff; color: #4f46e5;
            padding: 2px 8px; border-radius: 6px; font-weight: 700;
            border: 1px solid #e0e7ff; font-family: monospace; font-size: 0.9rem;
        }

        .btn-primary {
            width: 100%; border-radius: 10px; border: none; padding: 14px 0; margin-top: 10px;
            font-size: 1rem; font-weight: 700; background-color: #4f46e5; color: #ffffff;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-primary:hover { background-color: #4338ca; transform: translateY(-1px); box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3); }

        .forgot-link {
            color: #4f46e5; text-decoration: none; font-weight: 600;
            font-size: 0.85rem; display: inline-block; margin-bottom: 15px;
            cursor: pointer;
        }
        .forgot-link:hover { text-decoration: underline; }

        .login-meta-note { text-align: center; font-size: 0.8rem; color: #9ca3af; margin-top: 20px; }

        /* --- MODAL STYLES --- */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); /* Backdrop blur color */
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-active {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            background: white;
            width: 90%;
            max-width: 400px;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-active .modal-box {
            transform: translateY(0);
        }

        .modal-icon {
            width: 60px; height: 60px;
            background: #eef2ff; color: #4f46e5;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
        }

        .modal-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 10px; }
        .modal-desc { font-size: 0.95rem; color: #64748b; margin-bottom: 25px; line-height: 1.5; }

        .contact-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            display: flex; align-items: center; gap: 12px;
            font-weight: 600; color: #334155; font-size: 0.95rem;
        }
        .contact-item i { color: #6366f1; width: 20px; }

        .btn-close-modal {
            background: #f1f5f9; color: #64748b;
            border: none; padding: 12px; width: 100%;
            border-radius: 10px; font-weight: 700; cursor: pointer;
            margin-top: 10px;
            transition: 0.2s;
        }
        .btn-close-modal:hover { background: #e2e8f0; color: #0f172a; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="login-card">
        <h1 class="login-title">Student Login</h1>
        <p class="login-subtitle">Please enter your details to continue.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input id="email" type="email" name="email" class="form-control" placeholder="student@college.ac.in" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (Roll Number)</label>
                <div class="password-wrapper">
                    <input id="password" type="password" name="password" class="form-control" placeholder="Enter your roll number" autocomplete="current-password" required>
                    <i class="fa-regular fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <p class="field-hint">Example: <span class="example-badge">25MCA10001</span> (All Caps, No Spaces)</p>
            </div>

            <div style="text-align: right;">
                <a class="forgot-link" id="troubleLink">Trouble signing in?</a>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>

            <p class="login-meta-note"><i class="fa-solid fa-shield-halved"></i> Secure Attendance Portal</p>
        </form>
    </div>
</div>

<div class="modal-overlay" id="helpModal">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fa-solid fa-headset"></i>
        </div>
        <h3 class="modal-title">Helpdesk Support</h3>
        <p class="modal-desc">Forgot your credentials? Contact the administration for a reset.</p>
        
        <div class="contact-item">
            <i class="fa-solid fa-phone"></i> +91 98765 43210
        </div>
        <div class="contact-item">
            <i class="fa-solid fa-envelope"></i> helpdesk@college.ac.in
        </div>

        <button class="btn-close-modal" id="closeModalBtn">Close</button>
    </div>
</div>

<script>
    // --- Password Toggle ---
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
        this.classList.toggle('fa-eye');
    });

    // --- Modal Logic ---
    const modal = document.getElementById('helpModal');
    const triggerLink = document.getElementById('troubleLink');
    const closeBtn = document.getElementById('closeModalBtn');

    // Open
    triggerLink.addEventListener('click', function() {
        modal.classList.add('modal-active');
    });

    // Close Button
    closeBtn.addEventListener('click', function() {
        modal.classList.remove('modal-active');
    });

    // Close on Click Outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('modal-active');
        }
    });
</script>

</body>
</html>