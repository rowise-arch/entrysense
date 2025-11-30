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
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/login.css">
</head>
<body>
    <!-- Background Overlay -->
    <div class="bg-overlay"></div>

    <!-- Login Container -->
    <div class="login-wrapper">
        <div class="login-container glass-card">
            <div class="login-header">
                <div class="logo-container">
                    <div class="logo">
                        <img src="../Assets/rsulogo.png" alt="RSU Logo">
                    </div>
                    <h1><i class="fas fa-shield-alt"></i> RSU Security Management</h1>
                    <p class="subtitle">Secure Access Control System</p>
                </div>
            </div>

            <!-- Error Message Display -->
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span>You have been logged out successfully.</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['timeout'])): ?>
                <div class="warning-message">
                    <i class="fas fa-clock"></i>
                    <span>Session expired. Please login again.</span>
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
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required placeholder="Enter your password"
                            autocomplete="current-password">
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to System
                </button>
            </form>

            <!-- System Status -->
            <div class="system-status">
                <div class="status-row">
                    <span>System Status:</span>
                    <span class="status-info online">
                        <i class="fas fa-circle"></i> Operational
                    </span>
                </div>
                <div class="status-row">
                    <span>Last Updated:</span>
                    <span id="currentTime"><?= date('Y-m-d H:i:s') ?></span>
                </div>
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

        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString();
        }
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();
    </script>
</body>
</html>