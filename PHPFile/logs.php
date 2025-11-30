<?php
// Fix the include path
include __DIR__ . '/../Srcipt/access_control.php';
include __DIR__ . '/../Srcipt/db_connect.php';

// --- Use existing database connection from db_connect.php ---
// Remove the duplicate connection code and use the existing one

// --- Security: Input validation and pagination ---
$limit = 100; // Show last 100 logs by default
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// --- Search and Filter parameters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// --- Build dynamic SQL query ---
$sql = "SELECT id, rfid_uid, role, status, scan_time FROM logs WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM logs WHERE 1=1";
$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    $count_sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    $search_param = "%$search%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= 'ss';
}

// Add status filter
if (!empty($status_filter) && in_array($status_filter, ['Access Granted', 'Access Denied'])) {
    $sql .= " AND status = ?";
    $count_sql .= " AND status = ?";
    $params[] = &$status_filter;
    $types .= 's';
}

// Add role filter
if (!empty($role_filter) && in_array($role_filter, ['student', 'employee', 'guest', 'security'])) {
    $sql .= " AND role = ?";
    $count_sql .= " AND role = ?";
    $params[] = &$role_filter;
    $types .= 's';
}

// Store parameters for count query
$count_params = $params;
$count_types = $types;

// Add sorting and pagination to main query
$sql .= " ORDER BY scan_time DESC LIMIT ? OFFSET ?";
$params[] = &$limit;
$params[] = &$offset;
$types .= 'ii';

// --- Execute queries ---
try {
    // Get total count for pagination
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

} catch (Exception $e) {
    error_log("Logs query error: " . $e->getMessage());
    die("Unable to load logs. Please try again later.");
}

// Calculate statistics with filters
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Access Granted' THEN 1 ELSE 0 END) as granted,
    SUM(CASE WHEN status = 'Access Denied' THEN 1 ELSE 0 END) as denied
    FROM logs WHERE 1=1";

$stats_params = [];
$stats_types = '';

if (!empty($search)) {
    $stats_sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    $search_param_stats = "%$search%";
    $stats_params[] = &$search_param_stats;
    $stats_params[] = &$search_param_stats;
    $stats_types .= 'ss';
}

if (!empty($status_filter) && in_array($status_filter, ['Access Granted', 'Access Denied'])) {
    $stats_sql .= " AND status = ?";
    $stats_params[] = &$status_filter;
    $stats_types .= 's';
}

if (!empty($role_filter) && in_array($role_filter, ['student', 'employee', 'guest', 'security'])) {
    $stats_sql .= " AND role = ?";
    $stats_params[] = &$role_filter;
    $stats_types .= 's';
}

$stats_stmt = $conn->prepare($stats_sql);
if (!empty($stats_types)) {
    $stats_stmt->bind_param($stats_types, ...$stats_params);
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$grantedCount = $stats['granted'] ?? 0;
$deniedCount = $stats['denied'] ?? 0;
$totalLogs = $stats['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Logs - RSU Security Management System</title>

    <!-- Unified Styles -->
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/logs.css">
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
                <h2><i class="fas fa-clipboard-list"></i> RFID Access Logs</h2>
                <p>Real-time monitoring of all RFID access attempts</p>
            </div>

            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Logs</h3>
                        <p><?= $totalLogs ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon granted">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Access Granted</h3>
                        <p id="grantedCount"><?= $grantedCount ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon denied">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Access Denied</h3>
                        <p id="deniedCount"><?= $deniedCount ?></p>
                    </div>
                </div>
                <div class="stat-card glass-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Success Rate</h3>
                        <p><?= $totalLogs > 0 ? round(($grantedCount / $totalLogs) * 100, 1) : 0 ?>%</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <!-- Action Bar -->
            <div class="action-bar">
                <div class="action-left">
                    <a href="dashboard.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i>Back to Dashboard
                    </a>
                    <div class="export-buttons">
                        <button class="btn-export csv" onclick="exportLogs('csv')">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button class="btn-export pdf" onclick="exportLogs('pdf')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="auto-refresh">
                    <i class="fas fa-sync-alt"></i>
                    Auto-refresh every 10 seconds
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="filters-bar">
                <form method="GET" action="" id="filterForm" class="filter-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" id="searchInput" placeholder="Search by RFID UID or Role..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <select name="status" id="statusFilter" class="status-filter">
                        <option value="">All Status</option>
                        <option value="Access Granted" <?= $status_filter == 'Access Granted' ? 'selected' : '' ?>>Access
                            Granted</option>
                        <option value="Access Denied" <?= $status_filter == 'Access Denied' ? 'selected' : '' ?>>Access
                            Denied</option>
                    </select>
                    <select name="role" id="roleFilter" class="role-filter">
                        <option value="">All Roles</option>
                        <option value="student" <?= $role_filter == 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="employee" <?= $role_filter == 'employee' ? 'selected' : '' ?>>Employee</option>
                        <option value="guest" <?= $role_filter == 'guest' ? 'selected' : '' ?>>Guest</option>
                        <option value="security" <?= $role_filter == 'security' ? 'selected' : '' ?>>Security</option>
                    </select>
                    <div class="filter-buttons">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="logs.php" class="btn-clear">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                    </div>
                </form>
            </div>

            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($status_filter) || !empty($role_filter)): ?>
                <div class="active-filters">
                    <strong>Active Filters:</strong>
                    <?php if (!empty($search)): ?>
                        <span class="filter-tag">
                            Search: "<?= htmlspecialchars($search) ?>"
                            <a href="?<?= http_build_query(array_filter(['status' => $status_filter, 'role' => $role_filter])) ?>"
                                class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($status_filter)): ?>
                        <span class="filter-tag">
                            Status: <?= htmlspecialchars($status_filter) ?>
                            <a href="?<?= http_build_query(array_filter(['search' => $search, 'role' => $role_filter])) ?>"
                                class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($role_filter)): ?>
                        <span class="filter-tag">
                            Role: <?= htmlspecialchars(ucfirst($role_filter)) ?>
                            <a href="?<?= http_build_query(array_filter(['search' => $search, 'status' => $status_filter])) ?>"
                                class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Logs Table -->
            <div class="table-container glass-card" id="logsTable">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>RFID UID</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Scan Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                                    <td><code class="rfid-code"><?= htmlspecialchars($row['rfid_uid'] ?? '') ?></code></td>
                                    <td>
                                        <span class="role-badge role-<?= htmlspecialchars($row['role'] ?? 'unknown') ?>">
                                            <?= htmlspecialchars(($row['role'] ?? '') ? ucfirst($row['role']) : 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge <?= ($row['status'] ?? '') == 'Access Granted' ? 'status-granted' : 'status-denied' ?>">
                                            <?= htmlspecialchars($row['status'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['scan_time'] ?? '') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No access logs found</h3>
                                    <p>No logs match your current filters</p>
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
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&role=<?= urlencode($role_filter) ?>"
                            class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&role=<?= urlencode($role_filter) ?>"
                            class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ===== JavaScript ===== -->
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
            window.location.href = 'logs.php';
        }

        // Enter key support for search
        document.getElementById('searchInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Export functions
        function exportLogs(format) {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const role = document.getElementById('roleFilter').value;

            const params = new URLSearchParams();
            params.append('format', format);
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (role) params.append('role', role);

            window.open('export_logs.php?' + params.toString(), '_blank');
        }

        // Auto-refresh with AJAX (enhanced version)
        let autoRefreshInterval;

        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                refreshLogs();
            }, 10000);
        }

        function refreshLogs() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const role = document.getElementById('roleFilter').value;

            // Show loading state
            const tableContainer = document.getElementById('logsTable');
            tableContainer.classList.add('loading');

            fetch(`../FetchData/fetch_logs.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&role=${encodeURIComponent(role)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateLogsTable(data.logs);
                        updateStats(data.stats);
                    }
                    tableContainer.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error refreshing logs:', error);
                    tableContainer.classList.remove('loading');
                });
        }

        function updateLogsTable(logs) {
            const tbody = document.querySelector('#logsTable tbody');

            if (logs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-inbox"></i>
                            <h3>No access logs found</h3>
                            <p>No logs match your current filters</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>${log.id}</td>
                    <td><code class="rfid-code">${log.rfid_uid}</code></td>
                    <td>
                        <span class="role-badge role-${log.role}">
                            ${log.role ? log.role.charAt(0).toUpperCase() + log.role.slice(1) : 'Unknown'}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge ${log.status === 'Access Granted' ? 'status-granted' : 'status-denied'}">
                            ${log.status}
                        </span>
                    </td>
                    <td>${log.scan_time}</td>
                </tr>
            `).join('');
        }

        function updateStats(stats) {
            document.querySelector('.stat-card:nth-child(1) .stat-info p').textContent = stats.total || 0;
            document.getElementById('grantedCount').textContent = stats.granted || 0;
            document.getElementById('deniedCount').textContent = stats.denied || 0;

            const successRate = stats.total > 0 ? ((stats.granted / stats.total) * 100).toFixed(1) : 0;
            document.querySelector('.stat-card:nth-child(4) .stat-info p').textContent = `${successRate}%`;
        }

        // Start auto-refresh
        startAutoRefresh();

        // Stop auto-refresh when page is not visible
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearInterval(autoRefreshInterval);
            } else {
                startAutoRefresh();
            }
        });
    </script>
</body>

</html>

<?php $conn->close(); ?>