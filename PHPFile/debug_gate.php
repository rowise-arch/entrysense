<?php
// debug_gate.php
header('Content-Type: text/plain');

echo "Testing gate command...\n";

$socket = @fsockopen('127.0.0.1', 9090, $errno, $errstr, 5);

if (!$socket) {
    echo "ERROR: Cannot connect to Java - $errstr\n";
    echo "Make sure RFIDListener.java is running!\n";
    exit;
}

echo "Connected to Java gate server\n";

// Send OPEN command
fwrite($socket, "OPEN\n");
echo "Sent: OPEN\n";

// Read response
$response = fread($socket, 1024);
fclose($socket);

echo "Java response: " . $response . "\n";
echo "Java should send 'Access Granted Manual' to Arduino\n";
?>