<?php
// ../PHPFile/gate_control_alternative.php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('No action specified');
    }
    
    // Use Java to send serial commands (reusing your existing Java infrastructure)
    $javaDir = 'C:/wamp64/www/entrysense/JavaListener';
    chdir($javaDir);
    
    if ($action === 'open') {
        $command = 'java -cp ".;jSerialComm-2.11.2.jar" GateControl OPEN';
    } else {
        $command = 'java -cp ".;jSerialComm-2.11.2.jar" GateControl CLOSE';
    }
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        $response['success'] = true;
        $response['message'] = "Gate $action command sent successfully";
    } else {
        throw new Exception("Failed to send gate command. Return code: $returnCode");
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>