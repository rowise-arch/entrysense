<?php
session_start();

// Include the Auth class
include __DIR__ . '/../Srcipt/Auth.php';

// Process login if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        $auth = new Auth();
        $result = $auth->login($username, $password);

        if ($result['success']) {
            // Log successful login
            error_log("Login successful for user: " . $username . " with role: " . $result['role']);

            // Redirect to dashboard on successful login
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = $result['message'];
            // Log failed attempt
            error_log("Login failed for user: " . $username . " - " . $result['message']);
        }
    }
}

// If we reach here, show the login form
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RSU Security Management System</title>

    <!-- External Resources -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated Background */
        .bg-bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .bg-bubbles li {
            position: absolute;
            list-style: none;
            display: block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.15);
            bottom: -160px;
            animation: square 25s infinite;
            transition-timing-function: linear;
            border-radius: 50%;
        }

        .bg-bubbles li:nth-child(1) {
            left: 10%;
            animation-delay: 0s;
        }

        .bg-bubbles li:nth-child(2) {
            left: 20%;
            animation-delay: 2s;
            animation-duration: 17s;
        }

        .bg-bubbles li:nth-child(3) {
            left: 25%;
            animation-delay: 4s;
        }

        .bg-bubbles li:nth-child(4) {
            left: 40%;
            animation-delay: 0s;
            animation-duration: 22s;
        }

        .bg-bubbles li:nth-child(5) {
            left: 70%;
            animation-delay: 3s;
        }

        .bg-bubbles li:nth-child(6) {
            left: 80%;
            animation-delay: 6s;
            animation-duration: 18s;
        }

        .bg-bubbles li:nth-child(7) {
            left: 32%;
            animation-delay: 8s;
        }

        .bg-bubbles li:nth-child(8) {
            left: 55%;
            animation-delay: 10s;
            animation-duration: 20s;
        }

        .bg-bubbles li:nth-child(9) {
            left: 25%;
            animation-delay: 12s;
            animation-duration: 25s;
        }

        .bg-bubbles li:nth-child(10) {
            left: 90%;
            animation-delay: 14s;
            animation-duration: 16s;
        }

        @keyframes square {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }

        /* Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .logo img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }

        .login-header h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .login-header .subtitle {
            color: #718096;
            font-size: 16px;
            font-weight: 500;
        }

        /* Messages */
        .error-message,
        .success-message,
        .warning-message {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .success-message {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }

        .warning-message {
            background: #feebc8;
            color: #dd6b20;
            border: 1px solid #fbd38d;
        }

        /* Form Styles */
        .login-form {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #667eea;
        }

        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 38px;
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Demo Credentials */
        .demo-credentials {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .demo-credentials h4 {
            color: #4a5568;
            margin-bottom: 10px;
            font-size: 14px;
            text-align: center;
        }

        .credential-item {
            font-size: 12px;
            color: #718096;
            margin-bottom: 5px;
            text-align: center;
        }

        .credential-item strong {
            color: #4a5568;
        }

        /* System Status */
        .system-status {
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #4a5568;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-info.online {
            color: #38a169;
        }

        .status-info i {
            font-size: 8px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .form-group input {
                padding: 10px 40px 10px 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <ul class="bg-bubbles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>

    <!-- Login Container -->
    <div class="login-container glass-card">
        <div class="login-header">
            <div class="logo-container">
                <div class="logo">
                    <img src="../Assets/rsulogo.png" alt="RSU Logo">
                </div>
                <h1>RSU Security</h1>
                <p class="subtitle">Management System</p>
            </div>
        </div>

        <!-- Error Message Display -->
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                You have been logged out successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="warning-message">
                <i class="fas fa-clock"></i>
                Session expired. Please login again.
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form class="login-form" method="POST">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" id="username" name="username" required placeholder="Enter your username"
                    autocomplete="username"
                    value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" required placeholder="Enter your password"
                    autocomplete="current-password">
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login to System
            </button>
        </form>

        <!-- Demo Credentials -->
        <div class="demo-credentials">
            <h4>Demo Credentials:</h4>
            <div class="credential-item">
                <strong>Admin:</strong> admin / password
            </div>
            <div class="credential-item">
                <strong>Security:</strong> security / password
            </div>
        </div>

        <!-- System Status -->
        <div class="system-status">
            <div class="status-row">
                <span>System Status:</span>
                <span class="status-info online">
                    <i class="fas fa-circle"></i> Operational
                </span>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Form submission handling
        document.querySelector('.login-form').addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('.login-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            submitBtn.disabled = true;
        });

        // Clear error on input
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) errorMsg.style.display = 'none';
            });
        });
    </script>
</body>

</html>