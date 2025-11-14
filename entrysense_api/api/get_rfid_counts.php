<?php
// Correct include path for PHPFile directory
include __DIR__ . '/../Srcipt/db_connect.php';
// Set the correct timezone (adjust to your timezone)
date_default_timezone_set('Asia/Manila'); // Or your actual timezone
// Prevent caching - add these headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Check if database connection is established
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $today = date('Y-m-d');
    
    // Count scans by role for today
    $query = "SELECT 
                COALESCE(SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END), 0) as student,
                COALESCE(SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END), 0) as employee,
                COALESCE(SUM(CASE WHEN role = 'guest' THEN 1 ELSE 0 END), 0) as guest,
                COALESCE(SUM(CASE WHEN status = 'Access Denied' THEN 1 ELSE 0 END), 0) as denied
              FROM logs 
              WHERE DATE(scan_time) = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    echo json_encode([
        'student' => (int)$data['student'],
        'employee' => (int)$data['employee'],
        'guest' => (int)$data['guest'],
        'denied' => (int)$data['denied'],
        'timestamp' => time() // Add timestamp for debugging
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'student' => 0,
        'employee' => 0,
        'guest' => 0,
        'denied' => 0,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>