<?php
header('Content-Type: application/json');

function startRFIDListener() {
    $javaDir = 'C:\\xampp\\htdocs\\entrysense\\JavaListener';
    
    // Direct command to start Java
    $command = 'cd /d "' . $javaDir . '" && start "RFID Listener" java -cp ".;jSerialComm-2.11.2.jar;mysql-connector-j-9.4.0.jar;json-20231013.jar" RFIDListener';
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        sleep(2); // Give it time to start
        return ['success' => true, 'message' => 'RFID Listener started'];
    } else {
        return ['success' => false, 'message' => 'Failed to start. Return code: ' . $returnCode];
    }
}

$result = startRFIDListener();
echo json_encode($result);
?>