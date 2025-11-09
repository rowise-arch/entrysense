<?php
include 'db_connect.php';

// Get RFID UID from request (e.g., from your RFID scanner or form)
$rfid_uid = $_GET['rfid_uid'] ?? '';

if (empty($rfid_uid)) {
    echo json_encode(['error' => 'No RFID UID provided']);
    exit;
}

$sql = "
SELECT 
    r.rfid_uid,
    COALESCE(s.student_id, e.employee_id, g.guest_id) AS person_id,
    CASE
        WHEN s.student_id IS NOT NULL THEN 'Student'
        WHEN e.employee_id IS NOT NULL THEN 'Employee'
        WHEN g.guest_id IS NOT NULL THEN 'Guest'
        ELSE 'Unknown'
    END AS person_type,
    COALESCE(s.first_name, e.first_name, g.first_name) AS first_name,
    COALESCE(s.middle_name, e.middle_name, g.middle_name) AS middle_name,
    COALESCE(s.last_name, e.last_name, g.last_name) AS last_name,
    r.is_enabled,
    r.valid_from,
    r.valid_to,
    r.created_at,
    r.issued_at,
    r.updated_at
FROM 
    rfid_info r
LEFT JOIN rfid_student_info rs ON rs.rfid_id = r.rfid_id
LEFT JOIN student s ON s.student_id = rs.student_id
LEFT JOIN rfid_employee_info re ON re.rfid_id = r.rfid_id
LEFT JOIN employee e ON e.employee_id = re.employee_id
LEFT JOIN rfid_guest_info rg ON rg.rfid_id = r.rfid_id
LEFT JOIN guest g ON g.guest_id = rg.guest_id
WHERE 
    r.rfid_uid = '$rfid_uid'
    AND r.is_enabled = 1
    AND CURDATE() BETWEEN r.valid_from AND r.valid_to
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'RFID not found or inactive']);
}

$conn->close();
?>
