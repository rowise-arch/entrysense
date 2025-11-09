<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $isRunning = false;
    $processInfo = [];
    
    if (function_exists('shell_exec')) {
        // Check for Java processes
        $output = shell_exec('tasklist /FI "IMAGENAME eq java.exe" /FO CSV 2>nul');
        
        if (strpos($output, 'java.exe') !== false) {
            // Get detailed process information
            $output = shell_exec('wmic process where "name=\'java.exe\'" get ProcessId,CommandLine /format:csv 2>nul');
            
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (strpos($line, 'RFIDListener') !== false) {
                    $isRunning = true;
                    $parts = explode(',', $line);
                    if (count($parts) >= 3) {
                        $processInfo = [
                            'process_id' => $parts[1] ?? 'unknown',
                            'command_line' => $parts[2] ?? 'unknown',
                            'detected' => true
                        ];
                    }
                    break;
                }
            }
        }
    }
    
    echo json_encode([
        'isRunning' => $isRunning,
        'processInfo' => $processInfo,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'isRunning' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>