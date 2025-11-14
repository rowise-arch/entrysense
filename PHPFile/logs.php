<?php
// Fix the include path
include __DIR__ . '/../Srcipt/access_control.php';
include __DIR__ . '/../Srcipt/db_connect.php';

// --- Database connection ---
$servername = "localhost";
$username = "root";
$password = "";
$database = "entrysense";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Fetch logs ---
$sql = "SELECT id, rfid_uid, role, status, scan_time FROM logs ORDER BY scan_time DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Calculate statistics
$grantedCount = 0;
$deniedCount = 0;
$totalLogs = $result->num_rows;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] == 'Access Granted')
            $grantedCount++;
        else
            $deniedCount++;
    }
    // Reset pointer
    $result->data_seek(0);
}
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
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <img src="../Assets/Settings.png" alt="Settings" class="settings-icon">
        </div>
    </header>

    <!-- ===== Unified Sidebar ===== -->
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
            <i class="fas fa-clipboard-list"></i><span>Logs</span>
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
            <div class="action-bar">
                <a href="dashboard.php" class="btn-primary">
                    <i class="fas fa-arrow-left"></i>Back to Dashboard
                </a>
                <div class="auto-refresh">
                    <i class="fas fa-sync-alt"></i>
                    Auto-refresh every 10 seconds
                </div>
            </div>

            <!-- Logs Table -->
            <div class="table-container glass-card">
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
                                    <p>RFID scan data will appear here once available</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

        // Auto-refresh every 10 seconds
        setTimeout(() => {
            window.location.reload();
        }, 10000);
    </script>
</body>

</html>

<?php $conn->close(); ?>