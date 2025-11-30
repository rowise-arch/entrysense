<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id_number = $input['id_number'] ?? '';

error_log("📥 Received from Java - ID: " . $id_number . ", Name: " . ($input['name'] ?? 'empty') . ", Role: " . ($input['role'] ?? 'unknown'));

try {
    // Prepare the data
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
    
    error_log("💾 Saving to JSON - Name: " . $data['name'] . ", Role: " . $data['role'] . ", Status: " . $data['status']);
    
    // Save to the correct file path for your entry monitor
    $file = __DIR__ . "/../data/last_entry.json";
    
    // Ensure directory exists
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        error_log("✅ JSON file saved successfully: " . $file);
        echo json_encode([
            "success" => true,
            "message" => "Entry updated successfully",
            "data_saved" => [
                "id_number" => $id_number,
                "name" => $data['name'],
                "role" => $data['role'],
                "status" => $data['status']
            ]
        ]);
    } else {
        error_log("❌ Failed to save JSON file: " . $file);
        echo json_encode([
            "success" => false, 
            "error" => "Could not save JSON file",
            "file_path" => $file,
            "file_exists" => file_exists($file),
            "is_writable" => is_writable(dirname($file))
        ]);
    }
    
} catch (Exception $e) {
    error_log("❌ PHP error in update_entry.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "PHP error: " . $e->getMessage()
    ]);
}
?>