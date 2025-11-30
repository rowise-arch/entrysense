<?php
// Add at the VERY TOP of the file
include __DIR__ . '/../Srcipt/access_control.php';

// Fix the include path - adjust since dashboard.php is now in PHPFile
include __DIR__ . '/../Srcipt/db_connect.php';

$name = "";
$course = "";
$status = "";
$id_number = "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - RSU Security Management System</title>

  <!-- Unified Styles -->
  <link rel="stylesheet" href="../Style/global.css">
  <link rel="stylesheet" href="../Style/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <!-- <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button> -->
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
      <!-- Header Section -->
      <div class="dashboard-header">
        <div class="header-content">
          <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
          <p>Real-time RFID access monitoring and analytics</p>
        </div>
        <div class="header-stats">
          <div class="current-time">
            <i class="fas fa-clock"></i>
            <span id="clock">--:--:--</span>
          </div>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="stats-overview">
        <div class="stat-card primary">
          <div class="stat-icon">
            <i class="fas fa-id-card"></i>
          </div>
          <div class="stat-content">
            <h3>Scans Today</h3>
            <p id="scansToday">0</p>
            <span>Total RFID scans</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
          </div>
          <div class="stat-content">
            <h3>Students</h3>
            <p id="studentCount">0</p>
            <span>Access granted</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <div class="stat-content">
            <h3>Faculty/Staff</h3>
            <p id="facultyCount">0</p>
            <span>Access granted</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-user-friends"></i>
          </div>
          <div class="stat-content">
            <h3>Guests</h3>
            <p id="guestCount">0</p>
            <span>Access granted</span>
          </div>
        </div>

        <div class="stat-card danger">
          <div class="stat-icon">
            <i class="fas fa-ban"></i>
          </div>
          <div class="stat-content">
            <h3>Access Denied</h3>
            <p id="accessdenied">0</p>
            <span>Failed attempts</span>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-grid">
        <!-- Activity Chart -->
        <div class="chart-container">
          <div class="chart-header">
            <h3><i class="fas fa-chart-line"></i> Hourly Activity</h3>
            <span class="chart-subtitle">RFID scans per hour today</span>
          </div>
          <div class="chart-content">
            <canvas id="rfidChart"></canvas>
          </div>
        </div>

        <!-- Distribution Chart -->
        <div class="chart-container">
          <div class="chart-header">
            <h3><i class="fas fa-chart-pie"></i> Access Distribution</h3>
            <span class="chart-subtitle">Today's scan breakdown</span>
          </div>
          <div class="chart-content" style="height: 300px; position: relative;">
            <canvas id="rfidPieChart"></canvas>
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
  </script>

  <!-- Graph Logic -->
  <script>
    const ctx = document.getElementById('rfidChart').getContext('2d');
    const rfidChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: [],
        datasets: [{
          label: 'Scans per Hour',
          data: [],
          borderColor: 'rgba(66, 153, 225, 1)',
          backgroundColor: 'rgba(66, 153, 225, 0.1)',
          fill: true,
          borderWidth: 3,
          tension: 0.4,
          pointBackgroundColor: 'rgba(66, 153, 225, 1)',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(226, 232, 240, 0.6)'
            },
            ticks: {
              color: '#718096'
            }
          },
          x: {
            grid: {
              color: 'rgba(226, 232, 240, 0.6)'
            },
            ticks: {
              color: '#718096'
            }
          }
        }
      }
    });

    async function updateGraph() {
      try {
        const response = await fetch('get_rfid_graph_data.php');

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Graph data:', data);

        const labels = data.map(d => d.hour + ":00");
        const counts = data.map(d => d.total);
        rfidChart.data.labels = labels;
        rfidChart.data.datasets[0].data = counts;
        rfidChart.update();
      } catch (error) {
        console.error("Error updating graph:", error);
      }
    }
    setInterval(updateGraph, 5000);
    updateGraph();
  </script>

  <!-- Counters Logic -->
  <script>
    // Counters Logic - Real-time updates
    async function updateCounters() {
      try {
        const response = await fetch('get_rfid_counts.php');

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Counters data:', data);

        // Update individual counts
        document.getElementById("studentCount").textContent = data.student || 0;
        document.getElementById("facultyCount").textContent = data.employee || 0;
        document.getElementById("guestCount").textContent = data.guest || 0;
        document.getElementById("accessdenied").textContent = data.denied || 0;

        // Calculate total scans today
        const totalScansToday = (data.student || 0) + (data.employee || 0) + (data.guest || 0) + (data.denied || 0);
        document.getElementById("scansToday").textContent = totalScansToday;

      } catch (error) {
        console.error("Error updating counters:", error);
        // Set default values on error
        document.getElementById("studentCount").textContent = 0;
        document.getElementById("facultyCount").textContent = 0;
        document.getElementById("guestCount").textContent = 0;
        document.getElementById("accessdenied").textContent = 0;
        document.getElementById("scansToday").textContent = 0;
      }
    }
    // Update every 5 seconds
    updateCounters();
    setInterval(updateCounters, 5000);
  </script>

  <!-- Clock -->
  <script>
    function updateClock() {
      const now = new Date();
      const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
      document.getElementById('clock').textContent = now.toLocaleTimeString([], options);
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>

  <!-- Pie Chart -->
  <script>
    let rfidPieChart = null;

    async function updatePieChart() {
      try {
        console.log("🥧 Updating pie chart...");
        const timestamp = new Date().getTime();
        const response = await fetch(`get_rfid_counts.php?t=${timestamp}`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('🥧 Pie chart data:', data);

        const pieData = {
          labels: ['Students', 'Faculty/Staff', 'Guests', 'Access Denied'],
          datasets: [{
            data: [data.student || 0, data.employee || 0, data.guest || 0, data.denied || 0],
            backgroundColor: [
              'rgba(66, 153, 225, 0.8)',
              'rgba(72, 187, 120, 0.8)',
              'rgba(237, 137, 54, 0.8)',
              'rgba(245, 101, 101, 0.8)'
            ],
            borderColor: [
              'rgba(66, 153, 225, 1)',
              'rgba(72, 187, 120, 1)',
              'rgba(237, 137, 54, 1)',
              'rgba(245, 101, 101, 1)'
            ],
            borderWidth: 2,
            hoverOffset: 15
          }]
        };

        const ctxPie = document.getElementById('rfidPieChart');

        if (!ctxPie) {
          console.error('❌ Pie chart canvas not found!');
          return;
        }

        if (rfidPieChart) {
          // Update existing chart
          rfidPieChart.data = pieData;
          rfidPieChart.update();
          console.log('✅ Pie chart updated');
        } else {
          // Create new chart
          rfidPieChart = new Chart(ctxPie, {
            type: 'pie',
            data: pieData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    color: '#4a5568',
                    font: {
                      size: 12,
                      family: "'Inter', 'Segoe UI', sans-serif"
                    },
                    padding: 20,
                    usePointStyle: true
                  }
                },
                tooltip: {
                  backgroundColor: 'rgba(0, 0, 0, 0.7)',
                  titleFont: {
                    size: 13
                  },
                  bodyFont: {
                    size: 13
                  }
                }
              },
              animation: {
                animateScale: true,
                animateRotate: true
              }
            }
          });
          console.log('✅ Pie chart created');
        }
      } catch (error) {
        console.error("❌ Error updating pie chart:", error);
      }
    }

    // Initialize pie chart when page loads
    document.addEventListener('DOMContentLoaded', function () {
      console.log("📄 DOM loaded, initializing pie chart...");
      updatePieChart();
    });

    // Update every 5 seconds
    setInterval(updatePieChart, 5000);
  </script>
</body>

</html>