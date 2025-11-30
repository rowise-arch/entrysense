<?php
// Fix the include path
include __DIR__ . '/../Srcipt/access_control.php';

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

include __DIR__ . '/../Srcipt/db_connect.php';

// Handle form actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate required fields
        if (empty($username) || empty($full_name) || empty($email) || empty($role)) {
            $message = '❌ Please fill in all required fields';
            $message_type = 'error';
        } else {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = '❌ Username already exists';
                $message_type = 'error';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $insert_stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sssssi", $username, $hashed_password, $full_name, $email, $role, $is_active);

                if ($insert_stmt->execute()) {
                    // Log the user creation
                    $auth->logActivity('USER_CREATED', "Created user: {$username} ({$role}) - {$full_name}");
                    $message = '✅ User added successfully';
                    $message_type = 'success';
                } else {
                    $message = '❌ Error adding user: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }

    } elseif (isset($_POST['edit_user'])) {
        // Edit existing user
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Get old user data for logging
        $old_user_stmt = $conn->prepare("SELECT username, full_name, role, is_active FROM users WHERE id = ?");
        $old_user_stmt->bind_param("i", $user_id);
        $old_user_stmt->execute();
        $old_user = $old_user_stmt->get_result()->fetch_assoc();

        // Check if password is being updated
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $username, $hashed_password, $full_name, $email, $role, $is_active, $user_id);
            $password_changed = true;
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $username, $full_name, $email, $role, $is_active, $user_id);
            $password_changed = false;
        }

        if ($stmt->execute()) {
            // Build log description with changes
            $changes = [];
            if ($old_user['username'] !== $username) {
                $changes[] = "username: {$old_user['username']} → {$username}";
            }
            if ($old_user['full_name'] !== $full_name) {
                $changes[] = "name: {$old_user['full_name']} → {$full_name}";
            }
            if ($old_user['role'] !== $role) {
                $changes[] = "role: {$old_user['role']} → {$role}";
            }
            if ($old_user['is_active'] != $is_active) {
                $status = $is_active ? 'active' : 'inactive';
                $changes[] = "status: {$status}";
            }
            if ($password_changed) {
                $changes[] = "password: updated";
            }

            $log_description = "Updated user: {$username}";
            if (!empty($changes)) {
                $log_description .= " (" . implode(', ', $changes) . ")";
            }

            $auth->logActivity('USER_UPDATED', $log_description);
            $message = '✅ User updated successfully';
            $message_type = 'success';
        } else {
            $message = '❌ Error updating user: ' . $conn->error;
            $message_type = 'error';
        }

    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = $_POST['user_id'];

        // Prevent admin from deleting their own account
        if ($user_id == $_SESSION['user_id']) {
            $message = '❌ You cannot delete your own account';
            $message_type = 'error';
        } else {
            // Get user info for logging before deletion
            $user_info_stmt = $conn->prepare("SELECT username, full_name, role FROM users WHERE id = ?");
            $user_info_stmt->bind_param("i", $user_id);
            $user_info_stmt->execute();
            $user_info = $user_info_stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                // Log the user deletion
                $auth->logActivity('USER_DELETED', "Deleted user: {$user_info['username']} ({$user_info['role']}) - {$user_info['full_name']}");
                $message = '✅ User deleted successfully';
                $message_type = 'success';
            } else {
                $message = '❌ Error deleting user: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }

    // Refresh the page to show updated data and message
    header("Location: user_management.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Check for message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'] ?? 'info';
}

// Fetch all users
$users_result = $conn->query("SELECT id, username, full_name, email, role, is_active, created_at, last_login FROM users ORDER BY created_at DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);
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
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <span class="user-role badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
            </div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <div class="user-menu">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <!-- ===== Unified Sidebar ===== -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-links">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i><span>Dashboard</span>
            </a>

            <?php if ($auth->hasRole('admin')): ?>
                <a href="database.php" class="<?= basename($_SERVER['PHP_SELF']) == 'database.php' ? 'active' : '' ?>">
                    <i class="fas fa-database"></i><span>Database</span>
                </a>
            <?php endif; ?>

            <a href="entrymonitor.php"
                class="<?= basename($_SERVER['PHP_SELF']) == 'entrymonitor.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i><span>Entry Monitor</span>
            </a>

            <?php if ($auth->hasRole('admin')): ?>
                <a href="logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i><span>RFID Logs</span>
                </a>
            <?php endif; ?>

            <a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i><span>Register Guest</span>
            </a>

            <?php if ($auth->hasRole('admin') || $auth->hasRole('security')): ?>
                <a href="control_panel.php"
                    class="<?= basename($_SERVER['PHP_SELF']) == 'control_panel.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i><span>Control Panel</span>
                </a>
            <?php endif; ?>

            <!-- User Management Links -->
            <?php if ($auth->hasRole('admin')): ?>
                <a href="user_management.php"
                    class="<?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i><span>User Management</span>
                </a>
                <a href="user_logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'user_logs.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i><span>User Activity Logs</span>
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content" id="mainContent">
        <div class="main-container">
            <div class="page-header">
                <h2><i class="fas fa-users-cog"></i> User Management</h2>
                <p>Manage system users and their permissions</p>
            </div>

            <!-- Message Display -->
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Action Bar -->
            <div class="action-bar">
                <button class="btn-primary" onclick="openUserModal()">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
                <div class="user-stats">
                    <span class="stat-total"><?= count($users) ?> Users</span>
                    <span class="stat-active"><?= array_sum(array_column($users, 'is_active')) ?> Active</span>
                    <a href="user_logs.php" class="btn-view-logs">
                        <i class="fas fa-history"></i> View Activity Logs
                    </a>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container glass-card">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td class="actions">
                                    <button class="btn-edit" onclick="editUser(<?= $user['id'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn-delete"
                                            onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3 id="modalTitle">Add New User</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" id="user_id" name="user_id">

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                    <small>Unique username for login</small>
                </div>

                <div class="form-group">
                    <label for="password" id="passwordLabel">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small id="passwordHelp">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                    <small>User's full name</small>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                    <small>User's email address</small>
                </div>

                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="security">Security</option>
                        <option value="receptionist">Receptionist</option>
                    </select>
                    <small>Admin: Full access, Security: Gate control, Receptionist: Guest registration</small>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <span class="checkmark"></span>
                        Active User
                    </label>
                    <small>Inactive users cannot log in</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="modalSubmit" name="add_user">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Are you sure you want to delete this user?</p>
                <p class="warning-text"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="submit" class="btn-danger" name="delete_user">
                        <i class="fas fa-trash"></i> Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== Sidebar Toggle Script ===== -->
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>

    <!-- User Management JavaScript -->
    <script>
        // Modal elements
        const userModal = document.getElementById('userModal');
        const confirmModal = document.getElementById('confirmModal');
        const userForm = document.getElementById('userForm');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubmit = document.getElementById('modalSubmit');
        const passwordField = document.getElementById('password');
        const passwordLabel = document.getElementById('passwordLabel');
        const passwordHelp = document.getElementById('passwordHelp');

        // Open modal for adding new user
        function openUserModal() {
            modalTitle.textContent = 'Add New User';
            modalSubmit.textContent = 'Add User';
            modalSubmit.name = 'add_user';
            userForm.reset();
            document.getElementById('user_id').value = '';
            document.getElementById('is_active').checked = true;

            // Reset password field for new user
            passwordField.required = true;
            passwordLabel.innerHTML = 'Password *';
            passwordHelp.textContent = 'Minimum 6 characters';
            passwordField.value = '';

            userModal.style.display = 'flex';
        }

        // Open modal for editing user
        async function editUser(userId) {
            try {
                const response = await fetch(`get_user.php?id=${userId}`);
                const user = await response.json();

                if (user.success) {
                    modalTitle.textContent = 'Edit User';
                    modalSubmit.textContent = 'Update User';
                    modalSubmit.name = 'edit_user';

                    document.getElementById('user_id').value = user.data.id;
                    document.getElementById('username').value = user.data.username;
                    document.getElementById('full_name').value = user.data.full_name;
                    document.getElementById('email').value = user.data.email;
                    document.getElementById('role').value = user.data.role;
                    document.getElementById('is_active').checked = user.data.is_active;

                    // Adjust password field for editing
                    passwordField.required = false;
                    passwordField.value = '';
                    passwordLabel.innerHTML = 'Password';
                    passwordHelp.textContent = 'Leave blank to keep current password';

                    userModal.style.display = 'flex';
                } else {
                    alert('Error loading user data');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error loading user data');
            }
        }

        // Delete user with confirmation
        function deleteUser(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('confirmMessage').textContent = `Are you sure you want to delete user "${username}"?`;
            confirmModal.style.display = 'flex';
        }

        // Close modals
        function closeModal() {
            userModal.style.display = 'none';
        }

        function closeConfirmModal() {
            confirmModal.style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            if (event.target === userModal) {
                closeModal();
            }
            if (event.target === confirmModal) {
                closeConfirmModal();
            }
        }

        // Close modals with X button
        document.querySelectorAll('.close-modal').forEach(button => {
            button.onclick = function () {
                closeModal();
                closeConfirmModal();
            }
        });

        // Form validation
        userForm.addEventListener('submit', function (e) {
            const isEdit = modalSubmit.name === 'edit_user';
            const password = passwordField.value;

            if (!isEdit && (!password || password.length < 6)) {
                e.preventDefault();
                alert('Password is required and must be at least 6 characters long for new users');
                return;
            }

            // Basic email validation
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }

            // Username validation
            const username = document.getElementById('username').value;
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return;
            }
        });

        // Auto-focus first input when modal opens
        userModal.addEventListener('shown', function () {
            document.getElementById('username').focus();
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>