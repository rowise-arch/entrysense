<?php
include __DIR__ . '/../Srcipt/db_connect.php';

// Set correct timezone
date_default_timezone_set('Asia/Manila'); // CHANGE TO YOUR TIMEZONE

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    $today = date('Y-m-d');
    
    // Get hourly scan data for today - use CONVERT_TZ if timezone issues persist
    $query = "SELECT 
                HOUR(scan_time) as hour,
                COUNT(*) as total
              FROM logs 
              WHERE DATE(scan_time) = ?
              GROUP BY HOUR(scan_time)
              ORDER BY hour";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hourlyData = [];
    // Initialize all hours (0-23) with 0 counts
    for ($i = 0; $i < 24; $i++) {
        $hourlyData[$i] = ['hour' => $i, 'total' => 0];
    }
    
    // Update with actual data
    while ($row = $result->fetch_assoc()) {
        $hourlyData[$row['hour']] = $row;
    }
    
    // Convert to simple array
    $output = array_values($hourlyData);
    
    echo json_encode($output);
    
} catch (Exception $e) {
    $emptyData = [];
    for ($i = 0; $i < 24; $i++) {
        $emptyData[] = ['hour' => $i, 'total' => 0];
    }
    echo json_encode($emptyData);
}
?>