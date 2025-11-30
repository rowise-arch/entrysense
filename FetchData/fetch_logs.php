<?php
include __DIR__ . '/../Srcipt/db_connect.php';

header('Content-Type: application/json');

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query (same as logs.php but limited to recent entries)
$sql = "SELECT id, rfid_uid, role, status, scan_time FROM logs WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($status_filter) && in_array($status_filter, ['Access Granted', 'Access Denied'])) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($role_filter) && in_array($role_filter, ['student', 'employee', 'guest', 'security'])) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$sql .= " ORDER BY scan_time DESC LIMIT 50";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    // Get stats
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Access Granted' THEN 1 ELSE 0 END) as granted,
        SUM(CASE WHEN status = 'Access Denied' THEN 1 ELSE 0 END) as denied
        FROM logs WHERE 1=1";
        
    $stats_params = $params;
    $stats_types = $types;
    
    if (!empty($search)) {
        $stats_sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    }
    
    if (!empty($status_filter)) {
        $stats_sql .= " AND status = ?";
    }
    
    if (!empty($role_filter)) {
        $stats_sql .= " AND role = ?";
    }
    
    $stats_stmt = $conn->prepare($stats_sql);
    if (!empty($stats_types)) {
        $stats_stmt->bind_param($stats_types, ...$stats_params);
    }
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch logs'
    ]);
}
?>