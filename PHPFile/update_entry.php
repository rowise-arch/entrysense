<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration - same as Java
$host = 'localhost';
$dbname = 'entrysense';
$username = 'root';
$password = '';

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id_number = $input['id_number'] ?? '';

error_log("📥 Received from Java - ID: " . $id_number . ", Photo length: " . strlen($input['photo'] ?? ''));

if (empty($id_number)) {
    echo json_encode(["error" => "No ID number provided"]);
    exit;
}

try {
    // Prepare the data - use photo from Java if available
    $photo = $input['photo'] ?? '';
    
    $data = [
        "id_number" => $id_number,
        "name" => $input['name'] ?? 'Unknown User',
        "department" => $input['department'] ?? 'Unknown Department',
        "role" => $input['role'] ?? 'unknown',
        "status" => $input['status'] ?? 'Access Denied',
        "photo" => $photo,
        "timestamp" => date("Y-m-d H:i:s")
    ];
    
    error_log("💾 Saving to JSON - Name: " . $data['name'] . ", Photo length: " . strlen($photo));
    
    $file = "../data/last_entry.json";
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        echo json_encode([
            "success" => true,
            "message" => "Entry updated successfully",
            "photo_received" => !empty($photo),
            "photo_length" => strlen($photo),
            "data_saved" => [
                "id_number" => $id_number,
                "name" => $data['name'],
                "status" => $data['status']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "error" => "Could not save JSON file",
            "file_path" => realpath($file) ?: $file
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "PHP error: " . $e->getMessage()
    ]);
}
?>