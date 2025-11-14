<?php
header('Content-Type: application/json');

$response = ['success' => false, 'rfidRunning' => false, 'message' => ''];

try {
    // Method 1: Check if gate control server is responding
    $socket = @fsockopen('127.0.0.1', 9090, $errno, $errstr, 3);
    
    if ($socket) {
        fwrite($socket, "STATUS\n");
        $responseText = fread($socket, 1024);
        fclose($socket);
        
        if (strpos($responseText, 'SUCCESS') !== false) {
            $response['success'] = true;
            $response['rfidRunning'] = true;
            $response['message'] = 'RFID Listener with gate control is running';
        }
    } else {
        // Method 2: Check Java process
        $processCheck = shell_exec('tasklist /FI "IMAGENAME eq java.exe" 2>&1');
        $javaRunning = strpos($processCheck, 'java.exe') !== false;
        
        $response['success'] = true;
        $response['rfidRunning'] = $javaRunning;
        $response['message'] = $javaRunning ? 'Java process running' : 'No Java process found';
        $response['method'] = 'process_check';
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>