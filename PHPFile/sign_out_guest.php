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
$guest_id = $input['guest_id'] ?? '';

if (empty($guest_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Guest ID is required']);
    exit;
}

try {
    // Get current time in Philippines timezone
    $current_time = date('H:i:s');

    // First, check if guest exists and is not already signed out
    $check_stmt = $conn->prepare("SELECT time_out, status FROM guest WHERE guest_id = ?");
    $check_stmt->bind_param('s', $guest_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Guest not found with ID: ' . $guest_id
        ]);
        exit;
    }
    
    $guest_data = $result->fetch_assoc();
    if (!empty($guest_data['time_out'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Guest already signed out at ' . $guest_data['time_out']
        ]);
        exit;
    }

    // Update guest record - only set time_out and status (remove sign_out_timestamp)
    $stmt = $conn->prepare("
        UPDATE guest 
        SET time_out = ?, 
            status = 'signed_out'
        WHERE guest_id = ? AND (time_out IS NULL OR time_out = '')
    ");

    $stmt->bind_param('ss', $current_time, $guest_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Guest signed out successfully',
                'time_out' => $current_time
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No changes made - guest may already be signed out'
            ]);
        }
    } else {
        throw new Exception('Database update failed: ' . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>