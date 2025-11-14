<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Include database connection
include __DIR__ . '/../Srcipt/db_connect.php';

// Handle user actions
$message = '';
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'] ?? '';
    
    switch ($action) {
        case 'update_role':
            $new_role = $_POST['role'];
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            if ($stmt->execute()) {
                $message = "User role updated successfully";
            } else {
                $message = "Error updating user role";
            }
            break;
            
        case 'delete_user':
            // Prevent deleting own account
            if ($user_id == $_SESSION['user_id']) {
                $message = "Cannot delete your own account";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = "User deleted successfully";
                } else {
                    $message = "Error deleting user";
                }
            }
            break;
            
        case 'add_user':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $password = password_hash('default123', PASSWORD_DEFAULT); // Default password
            
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "Username or email already exists";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $username, $email, $password, $role);
                if ($stmt->execute()) {
                    $message = "User added successfully. Default password: default123";
                } else {
                    $message = "Error adding user";
                }
            }
            break;
    }
}

// Fetch all users
$users_result = $conn->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY created_at DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get user statistics
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(role = 'admin') as admin_count,
        SUM(role = 'operator') as operator_count,
        SUM(role = 'viewer') as viewer_count
    FROM users
");
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - RSU Security Management System</title>

    <!-- Unified Styles -->
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/user_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- ===== Unified Top Navigation ===== -->
    <header class="top-nav">
        <div class="nav-left">
            <img src="../Assets/rsulogo.png" alt="RSU Logo" class="logo">
            <h1>RSU Security Management System</h1>
        </div>
        <div class="nav-right">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <img src="../Assets/Settings.png" alt="Settings" class="settings-icon">
        </div>
    </header>

    <!-- ===== Unified Sidebar ===== -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-links">
            <!-- Always accessible to all roles -->
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i><span>Dashboard</span>
            </a>
            <a href="database.php" class="<?= basename($_SERVER['PHP_SELF']) === 'database.php' ? 'active' : '' ?>">
                <i class="fas fa-database"></i><span>Database</span>
            </a>
            <a href="entrymonitor.php" class="<?= basename($_SERVER['PHP_SELF']) === 'entrymonitor.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i><span>Entry Monitor</span>
            </a>
            <a href="logs.php" class="<?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i><span>Logs</span>
            </a>
            
            <!-- Operator+ only links -->
            <a href="register.php" 
               class="nav-link-protected <?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>" 
               data-required-role="operator"
               onclick="return checkPermission('operator', this)">
                <i class="fas fa-user-plus"></i><span>Register Guest</span>
                <?php if (!hasRole('operator')): ?>
                    <i class="fas fa-lock protected-icon"></i>
                <?php endif; ?>
            </a>
            
            <a href="control_panel.php" 
               class="nav-link-protected <?= basename($_SERVER['PHP_SELF']) === 'control_panel.php' ? 'active' : '' ?>" 
               data-required-role="operator"
               onclick="return checkPermission('operator', this)">
                <i class="fas fa-cogs"></i><span>Control Panel</span>
                <?php if (!hasRole('operator')): ?>
                    <i class="fas fa-lock protected-icon"></i>
                <?php endif; ?>
            </a>
            
            <!-- Admin only links -->
            <a href="user_management.php" 
               class="nav-link-protected <?= basename($_SERVER['PHP_SELF']) === 'user_management.php' ? 'active' : '' ?>" 
               data-required-role="admin"
               onclick="return checkPermission('admin', this)">
                <i class="fas fa-users-cog"></i><span>User Management</span>
                <?php if (!hasRole('admin')): ?>
                    <i class="fas fa-lock protected-icon"></i>
                <?php endif; ?>
            </a>
            
            <a href="settings.php" 
               class="nav-link-protected <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>" 
               data-required-role="admin"
               onclick="return checkPermission('admin', this)">
                <i class="fas fa-sliders-h"></i><span>System Settings</span>
                <?php if (!hasRole('admin')): ?>
                    <i class="fas fa-lock protected-icon"></i>
                <?php endif; ?>
            </a>
        </nav>
    </aside>

    <!-- Include permission modal -->
    <?php include 'auth/permission_denied_modal.php'; ?>

    <style>
    .nav-link-protected {
        position: relative;
    }

    .protected-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: #a0aec0;
    }

    .nav-link-protected:not(.active) {
        opacity: 0.7;
    }

    .nav-link-protected:not(.active):hover {
        opacity: 0.9;
    }

    /* Role badges for user info */
    .role-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 5px;
    }

    .role-badge.admin { background: #e53e3e; color: white; }
    .role-badge.operator { background: #ed8936; color: white; }
    .role-badge.viewer { background: #38a169; color: white; }
    </style>

    <script>
    // Permission check function
    function checkPermission(requiredRole, linkElement) {
        const userRole = '<?= $_SESSION['role'] ?>';
        
        const roleHierarchy = {
            'viewer': ['viewer'],
            'operator': ['operator', 'viewer'],
            'admin': ['admin', 'operator', 'viewer']
        };
        
        const hasAccess = roleHierarchy[requiredRole]?.includes(userRole);
        
        if (!hasAccess) {
            // Show permission modal instead of navigating
            if (typeof showPermissionModal === 'function') {
                showPermissionModal(requiredRole);
            } else {
                alert(`Access denied. Required role: ${requiredRole}\nYour role: ${userRole}`);
            }
            return false; // Prevent default navigation
        }
        
        return true; // Allow navigation
    }

    // Add visual indicators on page load
    document.addEventListener('DOMContentLoaded', function() {
        const protectedLinks = document.querySelectorAll('.nav-link-protected');
        const userRole = '<?= $_SESSION['role'] ?>';
        
        protectedLinks.forEach(link => {
            const requiredRole = link.getAttribute('data-required-role');
            const roleHierarchy = {
                'viewer': ['viewer'],
                'operator': ['operator', 'viewer'],
                'admin': ['admin', 'operator', 'viewer']
            };
            
            const hasAccess = roleHierarchy[requiredRole]?.includes(userRole);
            
            if (!hasAccess) {
                link.style.opacity = '0.6';
                link.title = `Requires ${requiredRole} role (Your role: ${userRole})`;
                
                // Remove href to prevent navigation
                link.href = 'javascript:void(0)';
            }
        });
    });
    </script>

    <!-- ===== Main Content ===== -->
    <main class="main-content" id="mainContent">
        <div class="main-container">
            <div class="page-header">
                <h2><i class="fas fa-users-cog"></i> User Management</h2>
                <p>Manage system users and their permissions</p>
            </div>

            <!-- Statistics Overview -->
            <div class="stats-overview">
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?= $stats['total_users'] ?? 0 ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Admins</h3>
                        <p><?= $stats['admin_count'] ?? 0 ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon operator">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Operators</h3>
                        <p><?= $stats['operator_count'] ?? 0 ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon viewer">
                        <i class="fas fa-user-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Viewers</h3>
                        <p><?= $stats['viewer_count'] ?? 0 ?></p>
                    </div>
                </div>
            </div>

            <!-- Add User Form -->
            <div class="add-user-section glass-card">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <form method="POST" class="add-user-form">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username *</label>
                            <input type="text" id="username" name="username" required placeholder="Enter username">
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" id="email" name="email" required placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                            <select id="role" name="role" required>
                                <option value="viewer">Viewer</option>
                                <option value="operator">Operator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                    </div>
                    <small class="form-note">Default password: <code>default123</code> (users should change after first login)</small>
                </form>
            </div>

            <!-- Users Table -->
            <div class="users-table-section glass-card">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> System Users</h3>
                    <span class="record-count"><?= count($users) ?> users</span>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($user['username']) ?>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="current-user-badge">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <form method="POST" class="role-form">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role" class="role-select" onchange="this.form.submit()" 
                                                    <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                                    <option value="viewer" <?= $user['role'] == 'viewer' ? 'selected' : '' ?>>Viewer</option>
                                                    <option value="operator" <?= $user['role'] == 'operator' ? 'selected' : '' ?>>Operator</option>
                                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?= date('M j, Y g:i A', strtotime($user['last_login'])) ?>
                                            <?php else: ?>
                                                <span class="never-logged-in">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" class="delete-form" onsubmit="return confirmDelete('<?= htmlspecialchars($user['username']) ?>')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="current-user-note">Current user</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users-slash"></i>
                                        <h3>No users found</h3>
                                        <p>Add new users using the form above</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Role Permissions Info -->
            <div class="permissions-info glass-card">
                <h3><i class="fas fa-info-circle"></i> Role Permissions</h3>
                <div class="permissions-grid">
                    <div class="permission-level">
                        <h4><i class="fas fa-user-shield"></i> Admin</h4>
                        <ul>
                            <li>Full system access</li>
                            <li>User management</li>
                            <li>System settings</li>
                            <li>All operator and viewer permissions</li>
                        </ul>
                    </div>
                    <div class="permission-level">
                        <h4><i class="fas fa-user-cog"></i> Operator</h4>
                        <ul>
                            <li>Guest registration</li>
                            <li>Control panel access</li>
                            <li>Gate control</li>
                            <li>All viewer permissions</li>
                        </ul>
                    </div>
                    <div class="permission-level">
                        <h4><i class="fas fa-user-eye"></i> Viewer</h4>
                        <ul>
                            <li>Dashboard viewing</li>
                            <li>Database access (read-only)</li>
                            <li>Entry monitor</li>
                            <li>Logs viewing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== Sidebar Toggle Script ===== -->
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Confirm deletion
        function confirmDelete(username) {
            return confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`);
        }

        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>

</html>