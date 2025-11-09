<?php
// Fix the include path
include __DIR__ . '/../Srcipt/db_connect.php';

// Check if RFID listener is running
$rfidRunning = false;
if (function_exists('shell_exec')) {
    $processCheck = shell_exec('tasklist /FI "IMAGENAME eq java.exe" 2>&1');
    $rfidRunning = strpos($processCheck, 'java.exe') !== false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel - RSU Security Management System</title>

    <!-- Unified Styles -->
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/control_panel.css">
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
            <a href="dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
            <a href="database.php"><i class="fas fa-database"></i><span>Database</span></a>
            <a href="entrymonitor.php"><i class="fas fa-id-card"></i><span>Entry Monitor</span></a>
            <a href="logs.php"><i class="fas fa-clipboard-list"></i><span>Logs</span></a>
            <a href="register.php"><i class="fas fa-user-plus"></i><span>Register Guest</span></a>
            <a href="control_panel.php" class="active"><i class="fas fa-cogs"></i><span>Control Panel</span></a>
        </nav>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content" id="mainContent">
        <div class="main-container">
            <div class="page-header">
                <h2><i class="fas fa-cogs"></i> Control Panel</h2>
                <p>Manual gate control and RFID system management</p>
            </div>

            <!-- System Status Overview -->
            <div class="status-overview">
                <div class="status-card glass-card">
                    <div class="status-icon <?= $rfidRunning ? 'running' : 'stopped' ?>">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div class="status-info">
                        <h3>RFID Listener</h3>
                        <p id="rfidStatusText"><?= $rfidRunning ? 'Running' : 'Stopped' ?></p>
                        <span id="rfidStatusDetail"><?= $rfidRunning ? 'Active and scanning' : 'Not running' ?></span>
                    </div>
                </div>
                
                <div class="status-card glass-card">
                    <div class="status-icon unknown">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="status-info">
                        <h3>Gate Status</h3>
                        <p id="gateStatusText">Unknown</p>
                        <span id="gateStatusDetail">Checking gate status...</span>
                    </div>
                </div>
            </div>

            <!-- Gate Control Section -->
            <div class="control-section glass-card">
                <h3><i class="fas fa-door-open"></i> Manual Gate Control</h3>
                <p>Manually open or close the security gate</p>
                
                <div class="gate-controls">
                    <button class="control-btn open-gate" id="openGateBtn">
                        <i class="fas fa-lock-open"></i>
                        <span>Open Gate</span>
                        <small>Grant temporary access</small>
                    </button>
                    
                    <button class="control-btn close-gate" id="closeGateBtn">
                        <i class="fas fa-lock"></i>
                        <span>Close Gate</span>
                        <small>Restrict access</small>
                    </button>
                </div>
                
                <div class="gate-timer" id="gateTimer" style="display: none;">
                    <div class="timer-display">
                        <i class="fas fa-clock"></i>
                        <span id="timerText">Gate will close in: 30 seconds</span>
                    </div>
                    <div class="timer-progress">
                        <div class="timer-bar" id="timerBar"></div>
                    </div>
                </div>
            </div>

            <!-- RFID Listener Control -->
            <div class="control-section glass-card">
                <h3><i class="fas fa-microchip"></i> RFID Listener Control</h3>
                <p>Start or stop the RFID scanning service</p>
                
                <div class="listener-controls">
                    <button class="control-btn start-listener <?= $rfidRunning ? 'disabled' : '' ?>" id="startListenerBtn" <?= $rfidRunning ? 'disabled' : '' ?>>
                        <i class="fas fa-play-circle"></i>
                        <span>Start Listener</span>
                        <small>Begin RFID scanning</small>
                    </button>
                    
                    <button class="control-btn stop-listener <?= !$rfidRunning ? 'disabled' : '' ?>" id="stopListenerBtn" <?= !$rfidRunning ? 'disabled' : '' ?>>
                        <i class="fas fa-stop-circle"></i>
                        <span>Stop Listener</span>
                        <small>Halt RFID scanning</small>
                    </button>
                </div>
                
                <div class="listener-status">
                    <div class="status-indicators">
                        <div class="status-item">
                            <span class="status-label">Process:</span>
                            <span class="status-value" id="processStatus"><?= $rfidRunning ? 'Running' : 'Stopped' ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Last Scan:</span>
                            <span class="status-value" id="lastScanTime">--:--:--</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Scans Today:</span>
                            <span class="status-value" id="scansToday">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Controls -->
            <div class="control-section glass-card emergency">
                <h3><i class="fas fa-exclamation-triangle"></i> Emergency Controls</h3>
                <p>Immediate system actions for emergency situations</p>
                
                <div class="emergency-controls">
                    <button class="control-btn emergency-stop" id="emergencyStopBtn">
                        <i class="fas fa-ban"></i>
                        <span>Emergency Stop</span>
                        <small>Stop all systems immediately</small>
                    </button>
                    
                    <button class="control-btn lock-system" id="lockSystemBtn">
                        <i class="fas fa-shield-alt"></i>
                        <span>Lock System</span>
                        <small>Disable all access</small>
                    </button>
                </div>
            </div>

            <!-- System Log -->
            <div class="control-section glass-card">
                <h3><i class="fas fa-list-alt"></i> Control Log</h3>
                <p>Recent control panel activities</p>
                
                <div class="control-log" id="controlLog">
                    <div class="log-empty">
                        <i class="fas fa-info-circle"></i>
                        <p>No control actions yet</p>
                        <span>Control actions will appear here</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" id="modalCancel">Cancel</button>
                <button class="btn-primary" id="modalConfirm">Confirm</button>
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
    </script>

    <!-- Control Panel JavaScript -->
    <script src="../Srcipt/control_panel.js"></script>
</body>
</html>