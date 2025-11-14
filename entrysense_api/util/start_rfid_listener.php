<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $javaDir = 'C:/wamp64/www/entrysense/JavaListener';
    
    // Change to Java directory
    chdir($javaDir);
    
    // Execute Java with gate control - run in background
    $command = 'start /B java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener';
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    // Wait a bit for the server to start
    sleep(3);
    
    // Test if gate control server is responding
    $socket = @fsockopen('127.0.0.1', 9090, $errno, $errstr, 5);
    if ($socket) {
        fclose($socket);
        $response['success'] = true;
        $response['message'] = 'RFID Listener with Gate Control started successfully';
    } else {
        $response['message'] = 'Listener started but gate control not responding yet. Try again in a few seconds.';
        $response['debug'] = $errstr;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>