<?php
// Add at the VERY TOP of the file
include __DIR__ . '/../Srcipt/access_control.php';

// Fallback values before JS updates
$name = $course = $status = $id_number = "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entry Monitor - RSU Security Management System</title>

  <!-- Unified Styles -->
  <link rel="stylesheet" href="../Style/global.css">
  <link rel="stylesheet" href="../Style/entrymonitor.css">
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
      <!-- Current Scan Display -->
      <div class="monitor-card glass-card" id="currentScanPanel">
        <div class="scan-header">
          <h3><i class="fas fa-sync-alt"></i> Current Scan</h3>
          <div class="header-controls">
            <div class="scan-time" id="currentScanTime">--:--:--</div>
            <button class="btn-fullscreen" id="fullscreenBtn" title="Full Screen">
              <i class="fas fa-expand"></i>
            </button>
          </div>
        </div>

        <div class="scan-content">
          <!-- Photo Section -->
          <div class="photo-section">
            <div class="photo-box" id="photo">
              <img src="" alt="User Photo" class="user-photo" id="userPhoto">
              <div class="access-indicator" id="accessIndicator">
                <i class="fas fa-question"></i>
              </div>
            </div>
          </div>

          <!-- Details Section -->
          <div class="details-section">
            <div class="details">
              <div class="detail-grid">
                <div class="detail-row">
                  <strong><i class="fas fa-user"></i> Name:</strong>
                  <span id="name"><?= htmlspecialchars($name) ?></span>
                </div>
                <div class="detail-row">
                  <strong><i class="fas fa-building"></i> Course/Department:</strong>
                  <span id="course"><?= htmlspecialchars($course) ?></span>
                </div>
                <div class="detail-row">
                  <strong><i class="fas fa-id-card"></i> ID Number:</strong>
                  <span id="id_number"><?= htmlspecialchars($id_number) ?></span>
                </div>
                <div class="detail-row">
                  <strong><i class="fas fa-user-tag"></i> Role:</strong>
                  <span id="role">—</span>
                </div>
                <div class="status-container">
                  <span id="status" class="status"><?= htmlspecialchars($status) ?></span>
                </div>
              </div>
            </div>

            <div class="last-update">
              <span class="scan-indicator"></span>
              Live scanning — <span id="lastUpdate">Waiting for scan...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div class="recent-activity glass-card">
        <div class="activity-header">
          <h3><i class="fas fa-history"></i> Recent Activity</h3>
          <div class="activity-stats">
            <span class="stat granted" id="grantedCount">0 Granted</span>
            <span class="stat denied" id="deniedCount">0 Denied</span>
          </div>
        </div>

        <div class="activity-list" id="activityList">
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No recent activity</p>
            <span>RFID scans will appear here</span>
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

    // Logout function
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }

    // Fullscreen functionality for current scan panel
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const currentScanPanel = document.getElementById('currentScanPanel');

    fullscreenBtn.addEventListener('click', toggleFullscreen);

    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        // Enter fullscreen
        if (currentScanPanel.requestFullscreen) {
          currentScanPanel.requestFullscreen();
        } else if (currentScanPanel.webkitRequestFullscreen) {
          currentScanPanel.webkitRequestFullscreen();
        } else if (currentScanPanel.msRequestFullscreen) {
          currentScanPanel.msRequestFullscreen();
        }
      } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        }
      }
    }

    // Update fullscreen button when fullscreen state changes
    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('msfullscreenchange', updateFullscreenButton);

    function updateFullscreenButton() {
      if (document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.msFullscreenElement) {
        fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        fullscreenBtn.title = 'Exit Full Screen';
        fullscreenBtn.classList.add('active');
      } else {
        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        fullscreenBtn.title = 'Full Screen';
        fullscreenBtn.classList.remove('active');
      }
    }

    // Activity tracking
    let activityHistory = [];
    let grantedCount = 0;
    let deniedCount = 0;
    const maxHistory = 10;

    // Your existing JavaScript functionality with enhancements
    // Your existing JavaScript functionality with enhancements
    let lastData = "";
    async function updateEntry() {
      try {
        // TRY BOTH FILES - Your system uses last_entry.json, notify uses latest_entry.json
        let response = await fetch('../data/last_entry.json?rand=' + Math.random());
        if (!response.ok) {
          // Fallback to the other file
          response = await fetch('../latest_entry.json?rand=' + Math.random());
        }

        if (!response.ok) throw new Error('File not found');
        const data = await response.json();
        const current = JSON.stringify(data);

        if (current !== lastData) {
          lastData = current;
          displayEntry(data);
          addToActivityHistory(data);
          updateStats(data);
          flashStatus();
          updateScanTime();
          document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();

          // Debug log
          console.log('🔄 Entry monitor updated:', {
            name: data.name,
            status: data.status,
            file: response.url.includes('last_entry') ? 'last_entry.json' : 'latest_entry.json'
          });
        }
      } catch (e) {
        console.error('❌ Entry monitor update failed:', e.message);
        document.getElementById('lastUpdate').textContent = 'No data available';
      }
    }

    function displayEntry(data) {
      const department = data.department || data.course_or_department || '—';
      const name = data.name || '—';
      const status = data.status || '—';
      const id_number = data.id_number || '—';
      const role = data.role || '—';
      const photo = data.photo || '';

      console.log('🖼️ Displaying entry:', {
        name: name,
        hasPhoto: !!photo,
        photoLength: photo.length,
        photoType: photo.substring(0, 30) + '...'
      });

      document.getElementById('name').textContent = name;
      document.getElementById('course').textContent = department;
      document.getElementById('status').textContent = status;
      document.getElementById('id_number').textContent = id_number;
      document.getElementById('role').textContent = role;

      const statusEl = document.getElementById('status');
      const userPhoto = document.getElementById('userPhoto');
      const photoBox = document.getElementById('photo');
      const accessIndicator = document.getElementById('accessIndicator');

      // Reset everything
      statusEl.className = 'status';
      userPhoto.style.display = 'none';
      userPhoto.src = '';
      photoBox.style.backgroundImage = "url('../Assets/rsulogo.png')";
      photoBox.style.backgroundSize = '60%';
      photoBox.style.backgroundRepeat = 'no-repeat';
      photoBox.style.backgroundPosition = 'center';
      accessIndicator.className = 'access-indicator';

      // Handle photo data
      if (photo && photo.startsWith('data:image')) {
        console.log('✅ Loading base64 image');

        userPhoto.onload = function () {
          console.log('✅ Base64 image loaded successfully');
          userPhoto.style.display = 'block';
          photoBox.style.backgroundImage = 'none';
        };

        userPhoto.onerror = function () {
          console.error('❌ Failed to load base64 image');
          userPhoto.style.display = 'none';
          photoBox.style.backgroundImage = "url('../Assets/rsulogo.png')";
        };

        // Load the image
        userPhoto.src = photo;

      } else if (photo) {
        console.log('⚠️ Photo data exists but not base64 format');
        userPhoto.style.display = 'none';
        photoBox.style.backgroundImage = "url('../Assets/rsulogo.png')";
      } else {
        console.log('📭 No photo data available');
        userPhoto.style.display = 'none';
        photoBox.style.backgroundImage = "url('../Assets/rsulogo.png')";
      }

      // Handle status and access indicator
      if (status.toLowerCase().includes('granted')) {
        statusEl.classList.add('status-granted');
        accessIndicator.classList.add('granted');
        accessIndicator.innerHTML = '<i class="fas fa-check"></i>';
      } else if (status.toLowerCase().includes('denied')) {
        statusEl.classList.add('status-denied');
        accessIndicator.classList.add('denied');
        accessIndicator.innerHTML = '<i class="fas fa-times"></i>';
      } else {
        accessIndicator.innerHTML = '<i class="fas fa-question"></i>';
      }
    }

    function addToActivityHistory(data) {
      const activity = {
        name: data.name || 'Unknown',
        id_number: data.id_number || '—',
        status: data.status || 'Unknown',
        department: data.department || data.course_or_department || '—',
        role: data.role || '—',
        timestamp: new Date().toLocaleTimeString()
      };

      // Add to beginning of array
      activityHistory.unshift(activity);

      // Keep only last 10 items
      if (activityHistory.length > maxHistory) {
        activityHistory = activityHistory.slice(0, maxHistory);
      }

      updateActivityList();
    }

    function updateActivityList() {
      const activityList = document.getElementById('activityList');

      if (activityHistory.length === 0) {
        activityList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No recent activity</p>
            <span>RFID scans will appear here</span>
          </div>
        `;
        return;
      }

      activityList.innerHTML = activityHistory.map(activity => `
        <div class="activity-item ${activity.status.toLowerCase().includes('granted') ? 'granted' : 'denied'}">
          <div class="activity-icon">
            <i class="fas fa-${activity.status.toLowerCase().includes('granted') ? 'check' : 'times'}"></i>
          </div>
          <div class="activity-details">
            <div class="activity-name">${activity.name}</div>
            <div class="activity-meta">
              <span class="activity-id">${activity.id_number}</span>
              <span class="activity-dept">${activity.department}</span>
              <span class="activity-role">${activity.role}</span>
            </div>
          </div>
          <div class="activity-time">${activity.timestamp}</div>
        </div>
      `).join('');
    }

    function updateStats(data) {
      if (data.status && data.status.toLowerCase().includes('granted')) {
        grantedCount++;
      } else if (data.status && data.status.toLowerCase().includes('denied')) {
        deniedCount++;
      }

      document.getElementById('grantedCount').textContent = `${grantedCount} Granted`;
      document.getElementById('deniedCount').textContent = `${deniedCount} Denied`;
    }

    function updateScanTime() {
      const now = new Date();
      document.getElementById('currentScanTime').textContent = now.toLocaleTimeString();
    }

    function flashStatus() {
      const statusEl = document.getElementById('status');
      const accessIndicator = document.getElementById('accessIndicator');

      statusEl.classList.add('flash');
      accessIndicator.classList.add('pulse');

      setTimeout(() => {
        statusEl.classList.remove('flash');
        accessIndicator.classList.remove('pulse');
      }, 3000);
    }

    // Initialize
    setInterval(updateEntry, 1000);
    updateEntry();
    updateActivityList();
  </script>
</body>

</html>