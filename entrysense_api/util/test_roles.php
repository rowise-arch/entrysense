<?php
// PHPFile/test_roles.php - Test role-based access WITH CORRECT LOGIC
session_start();
include __DIR__ . '/../Srcipt/Auth.php';

$auth = new Auth();

echo "<h2>Role Access Test - CORRECTED LOGIC</h2>";

// Test with actual session data
$roles = ['admin', 'security'];
$pages = [
    'dashboard.php' => ['admin', 'security', 'receptionist'],
    'database.php' => ['admin'],
    'logs.php' => ['admin'],
    'control_panel.php' => ['admin'],
    'entrymonitor.php' => ['admin', 'security', 'receptionist'],
    'register.php' => ['admin', 'security', 'receptionist']
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Role</th>";
foreach ($pages as $page => $allowedRoles) {
    echo "<th>$page<br><small>Allowed: " . implode(', ', $allowedRoles) . "</small></th>";
}
echo "</tr>";

foreach ($roles as $role) {
    // Set session for this role
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $role;
    $_SESSION['full_name'] = ucfirst($role) . ' User';
    
    echo "<tr>";
    echo "<td><strong>$role</strong></td>";
    
    foreach ($pages as $page => $allowedRoles) {
        $hasAccess = false;
        
        // Check if this role can access the page
        foreach ($allowedRoles as $allowedRole) {
            if ($auth->hasRole($allowedRole)) {
                $hasAccess = true;
                break;
            }
        }
        
        echo "<td style='background: " . ($hasAccess ? '#c6f6d5' : '#fed7d7') . "; padding: 10px; text-align: center;'>";
        echo $hasAccess ? '✓ Access' : '✗ Denied';
        if (!$hasAccess) {
            echo "<br><small>Needs: " . implode(', ', $allowedRoles) . "</small>";
        }
        echo "</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Test the hasRole method directly
echo "<hr><h3>hasRole() Method Direct Test:</h3>";
foreach ($roles as $role) {
    $_SESSION['role'] = $role;
    echo "<h4>Testing Role: $role</h4>";
    echo "hasRole('admin'): " . ($auth->hasRole('admin') ? 'TRUE' : 'FALSE') . "<br>";
    echo "hasRole('security'): " . ($auth->hasRole('security') ? 'TRUE' : 'FALSE') . "<br>";
    echo "hasRole('receptionist'): " . ($auth->hasRole('receptionist') ? 'TRUE' : 'FALSE') . "<br><br>";
}

// Show current session
echo "<hr><h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<br><a href='login.php'>Back to Login</a>";
?>