<?php
include 'config.php'; // database connection

// Count how many tapped today
$today = date('Y-m-d');
$sqlCounts = "SELECT role, COUNT(*) as count FROM rfid_logs WHERE DATE(timestamp) = '$today' GROUP BY role";
$result = $conn->query($sqlCounts);

$data = [
    'Student' => 0,
    'Faculty/Staff' => 0,
    'Guest' => 0
];

while ($row = $result->fetch_assoc()) {
    $data[$row['role']] = $row['count'];
}

// For graph: group by hour (today)
$sqlGraph = "
SELECT HOUR(timestamp) as hour, role, COUNT(*) as count
FROM rfid_logs
WHERE DATE(timestamp) = '$today'
GROUP BY HOUR(timestamp), role
ORDER BY hour ASC
";
$resultGraph = $conn->query($sqlGraph);

$graphData = [];

while ($row = $resultGraph->fetch_assoc()) {
    $graphData[] = $row;
}

echo json_encode(['counts' => $data, 'graph' => $graphData]);
?>
