<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $javaDir = 'C:/wamp64/www/entrysense/JavaListener';
    
    // Change to Java directory
    chdir($javaDir);
    
    // Execute Java directly
    $command = 'java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener';
    $output = [];
    $returnCode = 0;
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        $response['success'] = true;
        $response['message'] = 'RFID Listener started successfully';
    } else {
        $response['message'] = 'Java execution failed with code: ' . $returnCode;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>