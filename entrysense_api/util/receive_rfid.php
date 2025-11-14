<?php
// Fix the include path
include __DIR__ . '/../Srcipt/db_connect.php';

header('Content-Type: application/json');

// ✅ Step 1: Check if the RFID parameter exists (from GET or POST)
if (isset($_GET['rfid'])) {
    $rfid = trim($_GET['rfid']);
} elseif (isset($_POST['rfid'])) {
    $rfid = trim($_POST['rfid']);
} else {
    echo json_encode(["error" => "No RFID provided"]);
    exit;
}

// ✅ Step 2: Validate RFID format (numeric only)
if (!preg_match('/^\d+$/', $rfid)) {
    echo json_encode(["error" => "Invalid RFID format"]);
    exit;
}

// ✅ Step 3: Use prepared statements for safety
$query = "SELECT * FROM rfid_info WHERE rfid_uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $rfid);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();

    // Compute validity (6 months)
    $created = new DateTime($data['created_at']);
    $validUntil = clone $created;
    $validUntil->modify('+6 months');
    $now = new DateTime();
    $status = ($now <= $validUntil) ? "Access Granted" : "Expired";

    // ✅ Step 4: Return clean JSON
    echo json_encode([
        "rfid" => $rfid,
        "status" => $status,
        "details" => [
            "id" => $data['rfid_id'],
            "rfid" => $data['rfid_uid'],
            "role" => ucfirst($data['role']),
            "created_at" => $data['created_at'],
            "valid_until" => $validUntil->format('Y-m-d'),
            "status" => $status
        ]
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["rfid" => $rfid, "status" => "Access Denied"]);
}

$stmt->close();
$conn->close();
?>