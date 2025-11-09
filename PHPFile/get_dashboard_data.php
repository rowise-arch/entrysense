<?php
// Fix the include path
include __DIR__ . '/../Srcipt/db_connect.php';

header('Content-Type: application/json');

// Count entries for today
$today = date('Y-m-d');

$query = "
    SELECT 
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS student_count,
        SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END) AS employee_count,
        SUM(CASE WHEN role = 'guest' THEN 1 ELSE 0 END) AS guest_count,
        SUM(CASE WHEN status = 'Access Denied' THEN 1 ELSE 0 END) AS denied_count
    FROM logs
    WHERE DATE(scan_time) = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'data' => $result
]);
?>