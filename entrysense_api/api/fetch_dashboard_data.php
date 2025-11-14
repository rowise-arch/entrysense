<?php
// Fix the include path
include __DIR__ . '/../Srcipt/db_connect.php';

header('Content-Type: application/json');

// Count active student RFIDs
$studentCount = $conn->query("SELECT COUNT(*) AS total FROM rfid_student_info")->fetch_assoc()['total'];

// Count active employee RFIDs
$employeeCount = $conn->query("SELECT COUNT(*) AS total FROM rfid_employee_info")->fetch_assoc()['total'];

// Count active guest RFIDs
$guestCount = $conn->query("SELECT COUNT(*) AS total FROM rfid_guest_info")->fetch_assoc()['total'];

// For now, simulate access denied (can be replaced later with logs)
$accessDenied = 0;

echo json_encode([
  'students' => $studentCount,
  'employees' => $employeeCount,
  'guests' => $guestCount,
  'accessDenied' => $accessDenied
]);

$conn->close();
?>