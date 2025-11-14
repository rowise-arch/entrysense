<?php
session_start();
include __DIR__ . '/Auth.php';

$auth = new Auth();
$auth->checkAuth();

// Define role-based access permissions - UPDATED: security can access control_panel
$accessMatrix = [
    'dashboard.php' => ['admin', 'security', 'receptionist'], // All roles
    'entrymonitor.php' => ['admin', 'security', 'receptionist'], // All roles  
    'register.php' => ['admin', 'security', 'receptionist'], // All roles
    'control_panel.php' => ['admin', 'security'], // Admin and Security only
    'database.php' => ['admin'], // Admin only
    'logs.php' => ['admin'], // Admin only
    'report.php' => ['admin'] // Admin only
];

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user has access to current page
if (isset($accessMatrix[$current_page])) {
    $allowedRoles = $accessMatrix[$current_page];
    $hasAccess = false;
    
    // Check if user's role is in the allowed roles
    foreach ($allowedRoles as $role) {
        if ($auth->hasRole($role)) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        header('HTTP/1.1 403 Forbidden');
        die('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Access Denied</title>
                <link rel="stylesheet" href="../Style/global.css">
                <style>
                    .access-denied {
                        text-align: center;
                        padding: 50px;
                        background: white;
                        border-radius: 10px;
                        margin: 100px auto;
                        max-width: 500px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    }
                    .access-denied i {
                        font-size: 64px;
                        color: #e53e3e;
                        margin-bottom: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="access-denied">
                    <i class="fas fa-ban"></i>
                    <h2>Access Denied</h2>
                    <p>You do not have permission to access this page.</p>
                    <p><strong>Required Role:</strong> ' . implode(', ', $allowedRoles) . '</p>
                    <p><strong>Your Role:</strong> ' . $_SESSION['role'] . '</p>
                    <br>
                    <a href="dashboard.php" class="btn-primary">Return to Dashboard</a>
                </div>
            </body>
            </html>
        ');
    }
}
?>