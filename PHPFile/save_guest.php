<?php
include '../Srcipt/db_connect.php';

header('Content-Type: application/json');

// Set the correct timezone for the Philippines
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['visitorName', 'personToVisit', 'office', 'purpose', 'timeIn'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Extract name parts
    $name_parts = explode(' ', trim($input['visitorName']));
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[count($name_parts) - 1] ?? '';
    $middle_name = '';
    
    // If there are more than 2 names, everything between first and last is middle name
    if (count($name_parts) > 2) {
        $middle_name = implode(' ', array_slice($name_parts, 1, -1));
    }

    // Prepare the SQL query matching your table structure
    $stmt = $conn->prepare("
        INSERT INTO guest (
            first_name, 
            middle_name, 
            last_name, 
            person_to_visit, 
            office, 
            purpose, 
            visit_date, 
            time_in, 
            photo, 
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");

    // Bind parameters
    $stmt->bind_param(
        'sssssssss',
        $first_name,
        $middle_name,
        $last_name,
        $input['personToVisit'],
        $input['office'],
        $input['purpose'],
        $input['date'],
        $input['timeIn'],
        $input['photo']
    );

    // Execute the query
    if ($stmt->execute()) {
        $guest_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Guest registered successfully',
            'guest_id' => $guest_id
        ]);
    } else {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}

$conn->close();
?>