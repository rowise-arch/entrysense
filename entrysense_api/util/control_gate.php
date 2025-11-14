<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? ''; // 'open' or 'close'
    
    if (empty($action)) {
        throw new Exception('No action specified');
    }
    
    // Convert action to uppercase command
    $command = strtoupper($action);
    
    // Connect to Java gate control server
    $socket = @fsockopen('127.0.0.1', 9090, $errno, $errstr, 5);
    
    if (!$socket) {
        throw new Exception("Failed to connect to gate controller: $errstr (Error: $errno)");
    }
    
    // Set timeout
    stream_set_timeout($socket, 5);
    
    // Send command
    fwrite($socket, $command . "\n");
    
    // Read response
    $responseText = fread($socket, 1024);
    fclose($socket);
    
    if (strpos($responseText, 'SUCCESS') !== false) {
        $response['success'] = true;
        $response['message'] = "Gate $action command sent successfully";
        $response['java_response'] = trim($responseText);
    } else {
        throw new Exception("Gate control failed: " . trim($responseText));
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>