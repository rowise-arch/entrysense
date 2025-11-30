<?php
// Fix the include path
include __DIR__ . '/../Srcipt/access_control.php';

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

include __DIR__ . '/../Srcipt/db_connect.php';

// Handle filters and pagination
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action']) ? $_GET['action'] : '';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';

// Build query
$sql = "SELECT ual.*, u.username, u.full_name 
        FROM user_activity_logs ual 
        LEFT JOIN users u ON ual.user_id = u.id 
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM user_activity_logs ual LEFT JOIN users u ON ual.user_id = u.id WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR ual.description LIKE ? OR ual.action LIKE ?)";
    $count_sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR ual.description LIKE ? OR ual.action LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 4, $search_param);
    $types = str_repeat('s', 4);
}

if (!empty($action_filter)) {
    $sql .= " AND ual.action = ?";
    $count_sql .= " AND ual.action = ?";
    $params[] = &$action_filter;
    $types .= 's';
}

if (!empty($user_filter)) {
    $sql .= " AND ual.user_id = ?";
    $count_sql .= " AND ual.user_id = ?";
    $params[] = &$user_filter;
    $types .= 'i';
}

// Store parameters for count query
$count_params = $params;
$count_types = $types;

// Add sorting and pagination
$sql .= " ORDER BY ual.timestamp DESC LIMIT ? OFFSET ?";
$params[] = &$limit;
$params[] = &$offset;
$types .= 'ii';

// Execute queries
try {
    // Get total count
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($count_types)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_count / $limit);
    
    // Get paginated logs
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("User logs query error: " . $e->getMessage());
    $logs = [];
    $total_pages = 1;
    $total_count = 0;
}

// Get distinct actions for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM user_activity_logs ORDER BY action");
$actions = $actions_result->fetch_all(MYSQLI_ASSOC);

// Get users for filter dropdown
$users_result = $conn->query("SELECT id, username, full_name FROM users ORDER BY username");
$users = $users_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs - RSU Security Management System</title>

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
            
            <a href="entrymonitor.php" class="<?= basename($_SERVER['PHP_SELF']) == 'entrymonitor.php' ? 'active' : '' ?>">
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
            <a href="control_panel.php" class="<?= basename($_SERVER['PHP_SELF']) == 'control_panel.php' ? 'active' : '' ?>">
                <i class="fas fa-cogs"></i><span>Control Panel</span>
            </a>
            <?php endif; ?>
            
            <!-- User Management Links -->
            <?php if ($auth->hasRole('admin')): ?>
            <a href="user_management.php" class="<?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>">
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
                <h2><i class="fas fa-history"></i> User Activity Logs</h2>
                <p>Audit trail of all user management activities</p>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Logs</h3>
                        <p><?= $total_count ?></p>
                    </div>
                </div>
                
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>User Creations</h3>
                        <p><?= array_reduce($logs, function($carry, $log) {
                            return $carry + ($log['action'] === 'USER_CREATED' ? 1 : 0);
                        }, 0) ?></p>
                    </div>
                </div>
                
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="stat-info">
                        <h3>User Updates</h3>
                        <p><?= array_reduce($logs, function($carry, $log) {
                            return $carry + ($log['action'] === 'USER_UPDATED' ? 1 : 0);
                        }, 0) ?></p>
                    </div>
                </div>
                
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="stat-info">
                        <h3>User Deletions</h3>
                        <p><?= array_reduce($logs, function($carry, $log) {
                            return $carry + ($log['action'] === 'USER_DELETED' ? 1 : 0);
                        }, 0) ?></p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="filters-bar">
                <form method="GET" action="" id="filterForm" class="filter-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" id="searchInput" placeholder="Search logs..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <select name="action" id="actionFilter" class="action-filter">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?= htmlspecialchars($action['action']) ?>" 
                                    <?= $action_filter == $action['action'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($action['action']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="user_id" id="userFilter" class="user-filter">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" 
                                    <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['full_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="user_logs.php" class="btn-clear">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </form>
            </div>

            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($action_filter) || !empty($user_filter)): ?>
            <div class="active-filters">
                <strong>Active Filters:</strong>
                <?php if (!empty($search)): ?>
                    <span class="filter-tag">
                        Search: "<?= htmlspecialchars($search) ?>"
                        <a href="?<?= http_build_query(array_filter(['action' => $action_filter, 'user_id' => $user_filter])) ?>" class="remove-filter">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if (!empty($action_filter)): ?>
                    <span class="filter-tag">
                        Action: <?= htmlspecialchars($action_filter) ?>
                        <a href="?<?= http_build_query(array_filter(['search' => $search, 'user_id' => $user_filter])) ?>" class="remove-filter">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                <?php endif; ?>
                <?php if (!empty($user_filter)): 
                    $selected_user = array_filter($users, function($u) use ($user_filter) { return $u['id'] == $user_filter; });
                    $selected_user = reset($selected_user);
                ?>
                    <span class="filter-tag">
                        User: <?= htmlspecialchars($selected_user['username'] ?? 'Unknown') ?>
                        <a href="?<?= http_build_query(array_filter(['search' => $search, 'action' => $action_filter])) ?>" class="remove-filter">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Logs Table -->
            <div class="table-container glass-card">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <div class="user-info-small">
                                                <strong><?= htmlspecialchars($log['username']) ?></strong>
                                                <small><?= htmlspecialchars($log['full_name']) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <em>System</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?= htmlspecialchars(strtolower(str_replace('_', '-', $log['action']))) ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td class="description-cell">
                                        <?= htmlspecialchars($log['description']) ?>
                                    </td>
                                    <td>
                                        <div class="timestamp">
                                            <div class="date"><?= date('M j, Y', strtotime($log['timestamp'])) ?></div>
                                            <div class="time"><?= date('g:i A', strtotime($log['timestamp'])) ?></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No activity logs found</h3>
                                    <p>User activities will appear here</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action_filter) ?>&user_id=<?= $user_filter ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action_filter) ?>&user_id=<?= $user_filter ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Filter functions
        function applyFilters() {
            document.getElementById('filterForm').submit();
        }

        function clearFilters() {
            window.location.href = 'user_logs.php';
        }

        // Enter key support for search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Auto-refresh every 30 seconds
        let autoRefreshInterval;
        
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                refreshLogs();
            }, 30000); // 30 seconds
        }

        function refreshLogs() {
            const search = document.getElementById('searchInput').value;
            const action = document.getElementById('actionFilter').value;
            const userId = document.getElementById('userFilter').value;
            const currentPage = <?= $page ?>;
            
            // Only refresh if we're on page 1 and no active filters that might change results
            if (currentPage === 1 && !search && !action && !userId) {
                window.location.reload();
            }
        }

        // Start auto-refresh
        startAutoRefresh();

        // Stop auto-refresh when page is not visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(autoRefreshInterval);
            } else {
                startAutoRefresh();
            }
        });

        // Export functionality
        function exportLogs(format) {
            const search = document.getElementById('searchInput').value;
            const action = document.getElementById('actionFilter').value;
            const userId = document.getElementById('userFilter').value;
            
            const params = new URLSearchParams();
            params.append('format', format);
            if (search) params.append('search', search);
            if (action) params.append('action', action);
            if (userId) params.append('user_id', userId);
            
            window.open('export_user_logs.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>