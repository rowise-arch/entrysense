<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Connect to Java gate control server and send stop command
    $socket = @fsockopen('127.0.0.1', 9090, $errno, $errstr, 5);
    
    if (!$socket) {
        // If can't connect, it might already be stopped - try force kill
        $killOutput = [];
        exec('taskkill /F /IM java.exe 2>&1', $killOutput, $killReturnCode);
        
        $response['success'] = true;
        $response['message'] = 'RFID Listener was not running or has been force stopped';
        $response['force_kill'] = true;
    } else {
        // Send stop command gracefully
        fwrite($socket, "STOP\n");
        $responseText = fread($socket, 1024);
        fclose($socket);
        
        if (strpos($responseText, 'SUCCESS') !== false) {
            $response['success'] = true;
            $response['message'] = 'RFID Listener stopped gracefully';
        } else {
            // Fallback to force kill
            exec('taskkill /F /IM java.exe 2>&1', $killOutput, $killReturnCode);
            $response['success'] = true;
            $response['message'] = 'RFID Listener force stopped';
            $response['fallback'] = true;
        }
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>