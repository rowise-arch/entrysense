<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RSU Security Management System</title>
    
    <!-- Unified Styles -->
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- Background Overlay -->
    <div class="login-overlay"></div>

    <!-- Login Container -->
    <div class="login-container glass-card">
        <div class="login-header">
            <img src="../Assets/rsulogo.png" alt="RSU Logo" class="logo">
            <h1>RSU Security Management System</h1>
            <p class="subtitle">Secure RFID Access Control</p>
        </div>

        <form class="login-form" action="../PHPFile/login.php" method="POST">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username">
            </div>

            <div class="input-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="login-footer">
                <p>RSU Security Management System v2.0</p>
            </div>
        </form>
    </div>

    <script>
        // Add subtle animation to login form
        document.addEventListener('DOMContentLoaded', function() {
            const loginContainer = document.querySelector('.login-container');
            loginContainer.style.animation = 'slideUp 0.6s ease';
        });
    </script>
</body>
</html>