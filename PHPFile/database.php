<?php
// Add at the VERY TOP of the file
include __DIR__ . '/../Srcipt/access_control.php';

include '../Srcipt/db_connect.php';

// Handle CSV Import
$message = '';
$debug_output = ''; // Add this line to capture debug info

// =============================================
// CSV IMPORT PROCESSING
// =============================================
if (isset($_POST['import'])) {
  $debug_output = "";
  $message = "";

  if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
    // File size validation
    $max_file_size = 50 * 1024 * 1024; // 50MB
    $file_size = $_FILES['csv_file']['size'];


    if ($file_size > $max_file_size) {
      $message = "❌ File too large. Maximum size: 50MB";
    } elseif ($file_size == 0) {
      $message = "❌ File is empty";
    } else {
      $file_tmp = $_FILES['csv_file']['tmp_name'];

      // Verify file exists and is readable
      if (!file_exists($file_tmp) || !is_readable($file_tmp)) {
        $message = "❌ Cannot read uploaded file";
      } else {
        $debug_output .= "<strong>=== PROCESSING START ===</strong><br>";

        try {
          $handle = fopen($file_tmp, 'r');
          if (!$handle) {
            throw new Exception("Cannot open CSV file");
          }

          // ✅ Read and parse headers - USE COMMA DELIMITER
          do {
            $headers = fgetcsv($handle, 100000, ",");
          } while ($headers && isset($headers[0]) && str_starts_with(trim($headers[0]), '#'));

          // ✅ DEBUG: Show headers in browser
          $debug_output .= "<strong>CSV Headers Found:</strong><br>";
          $debug_output .= implode(' | ', $headers) . "<br>";
          $debug_output .= "Total columns: " . count($headers) . "<br><br>";

          // ✅ Check if headers were read successfully
          if ($headers === FALSE || count($headers) < 7) {
            throw new Exception("Invalid CSV format. Please use the exported CSV format.");
          }

          // ✅ Find photo part columns
          $photo_columns = [];
          foreach ($headers as $index => $header) {
            if (strpos($header, 'photo_part_') === 0) {
              $photo_columns[$index] = $header;
            }
          }

          // Sort photo columns to ensure correct order
          ksort($photo_columns);

          // DEBUG: Show photo columns found
          $debug_output .= "<strong>Photo Columns Found:</strong><br>";
          foreach ($photo_columns as $index => $name) {
            $debug_output .= "Column $index: $name<br>";
          }
          $debug_output .= "<br>";

          $imported_count = 0;
          $skipped_count = 0;
          $error_count = 0;
          $empty_rows = 0;
          $batch_counter = 0;

          // ✅ PROCESS CSV ROWS WITH MEMORY OPTIMIZATION
          while (($row = fgetcsv($handle, 1000000, ",")) !== FALSE) {
            $batch_counter++;

            // Free memory every 50 rows
            if ($batch_counter % 50 == 0) {
              gc_collect_cycles();
            }

            try {
              // Skip empty rows
              if (count($row) < 2 || (count($row) === 1 && empty(trim($row[0] ?? '')))) {
                $empty_rows++;
                continue;
              }

              // Extract CSV columns with null checks
              $rfid = isset($row[0]) ? trim($row[0]) : '';
              $role = isset($row[1]) ? strtolower(trim($row[1])) : '';
              $first_name = isset($row[2]) ? trim($row[2]) : '';
              $middle_name = isset($row[3]) ? trim($row[3]) : '';
              $last_name = isset($row[4]) ? trim($row[4]) : '';
              $department = isset($row[5]) ? trim($row[5]) : '';
              $course_or_position = isset($row[6]) ? trim($row[6]) : '';

              // ✅ Combine all photo_part_1 ... photo_part_14 automatically
              $photo = '';
              for ($i = 7; $i < count($headers); $i++) {
                if (strpos($headers[$i], 'photo_part_') === 0) {
                  $part = trim($row[$i] ?? '', '"');
                  $part = str_replace('...[TRUNCATED]', '', $part);
                  $photo .= $part;
                }
              }

              // ✅ Validate and fix photo data
              if (!empty($photo)) {
                if (!preg_match('/^data:image\/(\w+);base64,/', $photo)) {
                  if (base64_decode($photo, true) !== false) {
                    $photo = "data:image/jpeg;base64," . $photo;
                  } else {
                    $photo = null;
                  }
                }
              } else {
                $photo = null;
              }

              if ($imported_count === 0) {
                $debug_output .= "<strong>Final Photo Status:</strong> " .
                  ($photo ? "VALID - Ready for database" : "NULL/INVALID - No photo will be saved") .
                  "<br><br>";
              }

              // ✅ Skip if required fields are empty
              if (empty($rfid) || empty($first_name) || empty($last_name)) {
                $skipped_count++;
                continue;
              }

              // ✅ Prevent duplicate RFID imports
              $stmt = $conn->prepare("SELECT rfid_id FROM rfid_info WHERE rfid_uid = ?");
              $stmt->bind_param("s", $rfid);
              $stmt->execute();
              $check = $stmt->get_result();
              if ($check && $check->num_rows > 0) {
                $skipped_count++;
                continue;
              }

              // Insert into rfid_info
              $stmt = $conn->prepare("INSERT INTO rfid_info (rfid_uid, role) VALUES (?, ?)");
              $stmt->bind_param("ss", $rfid, $role);
              if (!$stmt->execute()) {
                $error_count++;
                continue;
              }

              $rfid_id = $conn->insert_id;

              // Insert by role
              if ($role === 'student') {
                $student_id = 'S-' . str_pad($rfid_id, 4, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("
                    INSERT INTO student (student_id, first_name, middle_name, last_name, department, course, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('sssssss', $student_id, $first_name, $middle_name, $last_name, $department, $course_or_position, $photo);
                $result = $stmt->execute();

                if ($result) {
                  $stmt = $conn->prepare("INSERT INTO rfid_student_info (rfid_id, student_id) VALUES (?, ?)");
                  $stmt->bind_param("is", $rfid_id, $student_id);
                  $stmt->execute();
                  $imported_count++;
                } else {
                  $error_count++;
                  $stmt = $conn->prepare("DELETE FROM rfid_info WHERE rfid_id = ?");
                  $stmt->bind_param("i", $rfid_id);
                  $stmt->execute();
                }

              } elseif ($role === 'employee') {
                $employee_id = 'E-' . str_pad($rfid_id, 4, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("
                    INSERT INTO employee (employee_id, first_name, middle_name, last_name, department, position, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('sssssss', $employee_id, $first_name, $middle_name, $last_name, $department, $course_or_position, $photo);
                $result = $stmt->execute();

                if ($result) {
                  $stmt = $conn->prepare("INSERT INTO rfid_employee_info (rfid_id, employee_id) VALUES (?, ?)");
                  $stmt->bind_param("is", $rfid_id, $employee_id);
                  $stmt->execute();
                  $imported_count++;
                } else {
                  $error_count++;
                  $stmt = $conn->prepare("DELETE FROM rfid_info WHERE rfid_id = ?");
                  $stmt->bind_param("i", $rfid_id);
                  $stmt->execute();
                }

              } elseif ($role === 'guest') {
                $guest_id = 'G-' . str_pad($rfid_id, 4, '0', STR_PAD_LEFT);
                $stmt = $conn->prepare("
                    INSERT INTO guest (guest_id, first_name, middle_name, last_name, purpose, photo)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('ssssss', $guest_id, $first_name, $middle_name, $last_name, $course_or_position, $photo);
                $result = $stmt->execute();

                if ($result) {
                  $stmt = $conn->prepare("INSERT INTO rfid_guest_info (rfid_id, guest_id) VALUES (?, ?)");
                  $stmt->bind_param("is", $rfid_id, $guest_id);
                  $stmt->execute();
                  $imported_count++;
                } else {
                  $error_count++;
                  $stmt = $conn->prepare("DELETE FROM rfid_info WHERE rfid_id = ?");
                  $stmt->bind_param("i", $rfid_id);
                  $stmt->execute();
                }

              } else {
                $skipped_count++;
                $stmt = $conn->prepare("DELETE FROM rfid_info WHERE rfid_id = ?");
                $stmt->bind_param("i", $rfid_id);
                $stmt->execute();
              }

            } catch (Exception $e) {
              $error_count++;
              error_log("CSV Import Error for RFID $rfid: " . $e->getMessage());
            }
          }

          fclose($handle);


        } catch (Exception $e) {
          $message = "❌ Import failed: " . $e->getMessage() . "<br><br>Debug Info:<br>" . $debug_output;
        }
      }
    }
  } else {
    $upload_error = $_FILES['csv_file']['error'] ?? 4;
    $error_messages = [
      1 => 'File too large (php.ini limit)',
      2 => 'File too large (form limit)',
      3 => 'Partial upload',
      4 => 'No file selected',
      6 => 'Missing temp folder',
      7 => 'Write failed',
      8 => 'PHP extension stopped upload'
    ];

    $message = "❌ Please select a valid CSV file. Error: " . ($error_messages[$upload_error] ?? 'Unknown error');
    $message .= "<br><br>Debug Info:<br>" . $debug_output;
  }

  // Display final message
  echo $message;
}

/**
 * Reconstruct base64 image from split parts
 */
function reconstructBase64FromParts($row, $photo_columns)
{
  $reconstructed_photo = '';

  foreach ($photo_columns as $index => $column_name) {
    if (isset($row[$index]) && !empty(trim($row[$index] ?? ''))) {
      $part = trim($row[$index]);

      // Remove quotes if present (from CSV escaping)
      $part = trim($part, '"');

      // Remove truncation indicators if present
      $part = str_replace('...[TRUNCATED]', '', $part);

      $reconstructed_photo .= $part;
    }
  }

  // Validate if we have a complete base64 image
  if (!empty($reconstructed_photo)) {
    // Check if it's a valid base64 image string
    if (preg_match('/^data:image\/(\w+);base64,/', $reconstructed_photo)) {
      return $reconstructed_photo;
    } else {
      // Try to detect if it's base64 data without data URI prefix
      if (base64_decode($reconstructed_photo, true) !== false) {
        // It's valid base64 but missing data URI, try to detect type
        $image_type = detectImageType($reconstructed_photo);
        if ($image_type) {
          return "data:image/$image_type;base64," . $reconstructed_photo;
        }
      }
    }
  }

  return null; // Return null if no valid photo data
}

/**
 * Detect image type from base64 data
 */
function detectImageType($base64_data)
{
  $decoded = base64_decode($base64_data);
  if (!$decoded)
    return null;

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime_type = finfo_buffer($finfo, $decoded);
  finfo_close($finfo);

  switch ($mime_type) {
    case 'image/jpeg':
      return 'jpeg';
    case 'image/png':
      return 'png';
    case 'image/gif':
      return 'gif';
    default:
      return null;
  }
}
// Your existing query logic remains unchanged
$studentQuery = "
  SELECT 
      s.student_id,
      CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) AS full_name,
      s.department,
      s.course,
      s.created_at,
      s.photo,
      r.rfid_uid,
      r.issued_at,
      r.valid_from,
      r.valid_to
  FROM student s
  LEFT JOIN rfid_student_info rs ON s.student_id = rs.student_id
  LEFT JOIN rfid_info r ON rs.rfid_id = r.rfid_id
";
$students = $conn->query($studentQuery)->fetch_all(MYSQLI_ASSOC);

$employeeQuery = "
  SELECT 
      e.employee_id,
      CONCAT(e.first_name, ' ', e.middle_name, ' ', e.last_name) AS full_name,
      e.department,
      e.position,
      e.created_at,
      e.photo,
      r.rfid_uid,
      r.issued_at,
      r.valid_from,
      r.valid_to
  FROM employee e
  LEFT JOIN rfid_employee_info re ON e.employee_id = re.employee_id
  LEFT JOIN rfid_info r ON re.rfid_id = r.rfid_id
";
$employees = $conn->query($employeeQuery)->fetch_all(MYSQLI_ASSOC);

$guestQuery = "
  SELECT 
      g.guest_id,
      CONCAT(g.first_name, ' ', COALESCE(g.middle_name, ''), ' ', g.last_name) AS full_name,
      g.person_to_visit,
      g.office,
      g.purpose,
      g.visit_date,
      g.time_in,
      g.time_out,
      g.status,
      g.created_at,
      g.photo
  FROM guest g
  ORDER BY g.created_at DESC
";
$guests = $conn->query($guestQuery)->fetch_all(MYSQLI_ASSOC);

function getStatus($created_at)
{
  if (!$created_at)
    return '—';
  $created = new DateTime($created_at);
  $expiry = clone $created;
  $expiry->modify('+6 months');
  $now = new DateTime();
  return ($now <= $expiry) ? 'Active' : 'Expired';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database - RSU Security Management System</title>

  <!-- Unified Styles -->
  <link rel="stylesheet" href="../Style/global.css">
  <link rel="stylesheet" href="../Style/database.css">
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
      <div class="page-header">
        <h2><i class="fas fa-database"></i> Database Management</h2>
        <p>Manage and import user records</p>
      </div>

      <!-- CSV Import Section -->
      <div class="import-box glass-card">
        <h3><i class="fas fa-file-csv"></i> Import CSV File</h3>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" name="import" class="btn-primary">
              <i class="fas fa-upload"></i> Upload CSV
            </button>
          </div>
        </form>

        <?php if (!empty($message)): ?>
          <div class="message"><?= $message ?></div>
        <?php endif; ?>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button class="tab-button active" data-target="students">
          <i class="fas fa-user-graduate"></i> Students
        </button>
        <button class="tab-button" data-target="employees">
          <i class="fas fa-chalkboard-teacher"></i> Faculty/Staff
        </button>
        <button class="tab-button" data-target="guests">
          <i class="fas fa-user-friends"></i> Guests
        </button>
      </div>

      <!-- Students Table -->
      <div class="content-container active" id="students">
        <div class="table-header">
          <div class="header-left">
            <h3>Student Records</h3>
            <!-- Search Bar for Students -->
            <div class="table-search-box">
              <div class="search-input-group">
                <input type="text" class="table-search-input" placeholder="Search students..." data-table="students">
                <button type="button" class="table-search-btn">
                  <i class="fas fa-search"></i>
                </button>
                <button type="button" class="table-clear-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
          <span class="record-count"><?= count($students) ?> records</span>
        </div>
        <div class="table-wrapper glass-card">
          <div class="table-container">
            <table id="studentsTable">
              <thead>
                <tr>
                  <th>RFID</th>
                  <th>ID Number</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Course</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $row): ?>
                  <tr class="record-row" data-details='<?= json_encode($row) ?>' data-role="student"
                    data-status="<?= strtolower(getStatus($row['created_at'])) ?>">
                    <td><?= htmlspecialchars($row['rfid_uid'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['course'] ?? '—') ?></td>
                    <td><span
                        class="status-badge <?= strtolower(getStatus($row['created_at'])) ?>"><?= getStatus($row['created_at']) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Employees Table -->
      <div class="content-container" id="employees">
        <div class="table-header">
          <div class="header-left">
            <h3>Faculty & Staff Records</h3>
            <!-- Search Bar for Employees -->
            <div class="table-search-box">
              <div class="search-input-group">
                <input type="text" class="table-search-input" placeholder="Search faculty/staff..."
                  data-table="employees">
                <button type="button" class="table-search-btn">
                  <i class="fas fa-search"></i>
                </button>
                <button type="button" class="table-clear-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
          <span class="record-count"><?= count($employees) ?> records</span>
        </div>
        <div class="table-wrapper glass-card">
          <div class="table-container">
            <table id="employeesTable">
              <thead>
                <tr>
                  <th>RFID</th>
                  <th>ID Number</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($employees as $row): ?>
                  <tr class="record-row" data-details='<?= json_encode($row) ?>' data-role="employee"
                    data-status="<?= strtolower(getStatus($row['created_at'])) ?>">
                    <td><?= htmlspecialchars($row['rfid_uid'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['employee_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['position'] ?? '—') ?></td>
                    <td><span
                        class="status-badge <?= strtolower(getStatus($row['created_at'])) ?>"><?= getStatus($row['created_at']) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Guests Table -->
      <div class="content-container" id="guests">
        <div class="table-header">
          <div class="header-left">
            <h3>Guest Records</h3>
            <!-- Search Bar for Guests -->
            <div class="table-search-box">
              <div class="search-input-group">
                <input type="text" class="table-search-input" placeholder="Search guests..." data-table="guests">
                <button type="button" class="table-search-btn">
                  <i class="fas fa-search"></i>
                </button>
                <button type="button" class="table-clear-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
          <span class="record-count"><?= count($guests) ?> records</span>
        </div>
        <div class="table-wrapper glass-card">
          <div class="table-container">
            <table id="guestsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Visitor Name</th>
                  <th>Person to Visit</th>
                  <th>Office</th>
                  <th>Purpose</th>
                  <th>Date</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($guests) > 0): ?>
                  <?php foreach ($guests as $row): ?>
                    <tr class="record-row" data-details='<?= json_encode($row) ?>' data-role="guest">
                      <td><?= htmlspecialchars($row['guest_id']) ?></td>
                      <td><?= htmlspecialchars($row['full_name']) ?></td>
                      <td><?= htmlspecialchars($row['person_to_visit'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($row['office'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($row['visit_date'] ?? '—') ?></td>
                      <td><?= htmlspecialchars($row['time_in'] ?? '—') ?></td>
                      <td>
                        <?php if ($row['time_out']): ?>
                          <?= htmlspecialchars($row['time_out']) ?>
                        <?php else: ?>
                          <span class="not-signed-out">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (empty($row['time_out'])): ?>
                          <button class="btn-signout" data-guest-id="<?= $row['guest_id'] ?>"
                            data-visitor-name="<?= htmlspecialchars($row['full_name']) ?>">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                          </button>
                        <?php else: ?>
                          <span class="status-badge signed-out">Signed Out</span>
                          <div class="sign-out-time"><?= htmlspecialchars($row['time_out']) ?></div>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="no-data">
                      <i class="fas fa-inbox"></i>
                      <h3>No guest records found</h3>
                      <p>Guest registrations will appear here</p>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Record Details Modal -->
  <div class="modal" id="detailsModal">
    <div class="modal-content glass-card">
      <span class="close-btn">&times;</span>
      <img id="modalPhoto" src="../Assets/default-user.png" alt="Profile Photo">
      <h3 id="modalName">Name</h3>
      <div class="modal-details">
        <p><strong>ID:</strong> <span id="modalId"></span></p>
        <p><strong>RFID:</strong> <span id="modalRfid"></span></p>
        <p><strong>Department:</strong> <span id="modalDept"></span></p>
        <p><strong>Course/Position:</strong> <span id="modalCourse"></span></p>
        <p><strong>Created At:</strong> <span id="modalCreated"></span></p>
        <p><strong>Valid Until:</strong> <span id="modalValidUntil"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
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

    // Your existing JavaScript functionality remains unchanged
    const tabs = document.querySelectorAll(".tab-button");
    const containers = document.querySelectorAll(".content-container");
    tabs.forEach(tab => {
      tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("active"));
        containers.forEach(c => c.classList.remove("active"));
        tab.classList.add("active");
        document.getElementById(tab.dataset.target).classList.add("active");
      });
    });

    const modal = document.getElementById("detailsModal");
    const closeBtn = document.querySelector(".close-btn");
    document.querySelectorAll(".record-row").forEach(row => {
      row.addEventListener("click", () => {
        const data = JSON.parse(row.dataset.details);
        const modalPhoto = document.getElementById("modalPhoto");
        modalPhoto.src = (data.photo && data.photo.startsWith("data:image")) ? data.photo : "../Assets/default-user.png";
        document.getElementById("modalName").innerText = data.full_name || "—";
        document.getElementById("modalId").innerText = data.student_id || data.employee_id || data.guest_id || "—";
        document.getElementById("modalRfid").innerText = data.rfid_uid || "—";
        document.getElementById("modalDept").innerText = data.department || "—";
        document.getElementById("modalCourse").innerText = data.course || data.position || data.purpose || "—";

        // Format Created At date
        if (data.created_at) {
          const createdDate = new Date(data.created_at);
          document.getElementById("modalCreated").innerText = formatDate(createdDate);
        } else {
          document.getElementById("modalCreated").innerText = "—";
        }

        // Calculate and display Valid Until (6 months from created_at)
        if (data.created_at) {
          const created = new Date(data.created_at);
          const validUntil = new Date(created);
          validUntil.setMonth(validUntil.getMonth() + 6);
          document.getElementById("modalValidUntil").innerText = formatDate(validUntil);

          const now = new Date();
          document.getElementById("modalStatus").innerText = now <= validUntil ? "Active" : "Expired";
        } else {
          document.getElementById("modalValidUntil").innerText = "—";
          document.getElementById("modalStatus").innerText = "—";
        }

        modal.style.display = "flex";
      });
    });

    // Function to format date as "Month Name, Day, Year"
    function formatDate(date) {
      const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      };
      return date.toLocaleDateString('en-US', options);
    }

    closeBtn.onclick = () => modal.style.display = "none";
    window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };

    // Individual table search functionality with real-time updates
    document.querySelectorAll('.table-search-input').forEach(input => {
      const tableId = input.dataset.table;
      const searchBtn = input.nextElementSibling;
      const clearBtn = searchBtn.nextElementSibling;
      const recordCount = input.closest('.table-header').querySelector('.record-count');

      // Store original table data
      const originalTable = document.getElementById(`${tableId}Table`).cloneNode(true);

      // Real-time search on input
      let searchTimeout;
      input.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          performTableSearch(tableId, input, recordCount);
        }, 300); // 300ms delay for better performance
      });

      // Instant search on button click
      searchBtn.addEventListener('click', () => performTableSearch(tableId, input, recordCount));

      // Clear search
      clearBtn.addEventListener('click', () => clearTableSearch(tableId, input, recordCount, originalTable));

      // Search on Enter key
      input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          performTableSearch(tableId, input, recordCount);
        }
      });
    });

    function performTableSearch(tableId, input, recordCount) {
      const searchTerm = input.value.toLowerCase().trim();
      const table = document.getElementById(`${tableId}Table`);
      const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
      let resultsCount = 0;

      for (let row of rows) {
        // Skip no-results rows
        if (row.classList.contains('no-results')) continue;

        const rowText = row.textContent.toLowerCase();

        if (!searchTerm || rowText.includes(searchTerm)) {
          row.style.display = '';
          resultsCount++;

          // Highlight search term
          if (searchTerm) {
            highlightText(row, searchTerm);
          } else {
            removeHighlights(row);
          }
        } else {
          row.style.display = 'none';
        }
      }

      // Show no results message if needed
      const tableBody = table.getElementsByTagName('tbody')[0];
      if (resultsCount === 0 && searchTerm) {
        if (!tableBody.querySelector('.no-results')) {
          const noResultsRow = document.createElement('tr');
          noResultsRow.className = 'no-results';
          noResultsRow.innerHTML = `
                <td colspan="6">
                    <div>
                        <i class="fas fa-search"></i>
                        <h4>No records found</h4>
                        <p>Try different search terms</p>
                    </div>
                </td>
            `;
          tableBody.appendChild(noResultsRow);
        }
      } else {
        const noResultsRow = tableBody.querySelector('.no-results');
        if (noResultsRow) {
          noResultsRow.remove();
        }
      }

      // Update record count
      updateRecordCount(tableId, searchTerm, resultsCount, recordCount);
    }

    function updateRecordCount(tableId, searchTerm, resultsCount, recordCount) {
      const originalCount = getOriginalCount(tableId);

      if (searchTerm) {
        recordCount.textContent = `${resultsCount} of ${originalCount} records`;
        recordCount.style.background = 'rgba(102, 126, 234, 0.1)';
        recordCount.style.color = '#667eea';

        // Add search indicator
        if (!recordCount.querySelector('.search-indicator')) {
          const indicator = document.createElement('span');
          indicator.className = 'search-indicator';
          indicator.innerHTML = ' <i class="fas fa-search"></i>';
          recordCount.appendChild(indicator);
        }
      } else {
        recordCount.textContent = `${originalCount} records`;
        recordCount.style.background = 'rgba(226, 232, 240, 0.8)';
        recordCount.style.color = '#718096';

        // Remove search indicator
        const indicator = recordCount.querySelector('.search-indicator');
        if (indicator) {
          indicator.remove();
        }
      }
    }

    function clearTableSearch(tableId, input, recordCount, originalTable) {
      input.value = '';

      // Restore original table
      const currentTable = document.getElementById(`${tableId}Table`);
      currentTable.parentNode.replaceChild(originalTable.cloneNode(true), currentTable);

      // Reattach event listeners to new rows
      document.querySelectorAll('.record-row').forEach(row => {
        row.addEventListener('click', showRecordDetails);
      });

      // Reattach sign-out event listeners
      document.querySelectorAll('.btn-signout').forEach(button => {
        button.addEventListener('click', handleSignOut);
      });

      // Update record count
      updateRecordCount(tableId, '', getOriginalCount(tableId), recordCount);
    }

    function getOriginalCount(tableId) {
      const counts = {
        students: <?= count($students) ?>,
        employees: <?= count($employees) ?>,
        guests: <?= count($guests) ?>
      };
      return counts[tableId] || 0;
    }

    // Keep the existing highlight functions
    function highlightText(element, searchTerm) {
      removeHighlights(element);

      const walker = document.createTreeWalker(
        element,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );

      let node;
      while (node = walker.nextNode()) {
        const text = node.nodeValue;
        const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
        const newText = text.replace(regex, '<mark class="highlight">$1</mark>');

        if (newText !== text) {
          const span = document.createElement('span');
          span.innerHTML = newText;
          node.parentNode.replaceChild(span, node);
        }
      }
    }

    function removeHighlights(element) {
      const highlights = element.querySelectorAll('.highlight');
      highlights.forEach(highlight => {
        const parent = highlight.parentNode;
        parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
        parent.normalize();
      });
    }

    function escapeRegex(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Sign-out functionality
    document.addEventListener('click', function (e) {
      if (e.target.closest('.btn-signout')) {
        const button = e.target.closest('.btn-signout');
        const guestId = button.dataset.guestId;
        const visitorName = button.dataset.visitorName;

        signOutGuest(guestId, visitorName, button);
      }
    });

    async function signOutGuest(guestId, visitorName, buttonElement) {
      if (!confirm(`Sign out ${visitorName}? This will record the current time as their sign-out time.`)) {
        return;
      }

      try {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing Out...';
        buttonElement.disabled = true;

        const response = await fetch('../PHPFile/sign_out_guest.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            guest_id: guestId
          })
        });

        const result = await response.json();

        if (result.success) {
          // Update the UI
          const row = buttonElement.closest('tr');
          const cells = row.cells;

          // Update Time Out cell
          cells[7].innerHTML = result.time_out;

          // Update Status cell
          cells[8].innerHTML = `
            <span class="status-badge signed-out">Signed Out</span>
            <div class="sign-out-time">${result.time_out}</div>
          `;

          showNotification(`${visitorName} signed out successfully at ${result.time_out}`, 'success');
        } else {
          throw new Error(result.message);
        }
      } catch (error) {
        console.error('Sign-out error:', error);
        showNotification('Error signing out: ' + error.message, 'error');

        // Reset button
        buttonElement.innerHTML = '<i class="fas fa-sign-out-alt"></i> Sign Out';
        buttonElement.disabled = false;
      }
    }

    // Updated notification function
    function showNotification(message, type = 'info') {
      // Remove existing notifications
      const existingNotifications = document.querySelectorAll('.custom-notification');
      existingNotifications.forEach(notif => notif.remove());

      const notification = document.createElement('div');
      notification.className = `custom-notification ${type}`;

      const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
      };

      notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
        </div>
      `;

      document.body.appendChild(notification);

      // Animate in
      setTimeout(() => notification.classList.add('show'), 100);

      // Remove after 5 seconds
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
      }, 5000);
    }
  </script>
</body>

</html>