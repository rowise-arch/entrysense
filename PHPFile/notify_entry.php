<?php
// File: C:\xampp\htdocs\entrysense\PHPFile\notify_entry.php
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

$file = "../latest_entry.json";
$lastModified = 0;

while (true) {
    clearstatcache(false, $file);
    $currentModified = file_exists($file) ? filemtime($file) : 0;

    if ($currentModified > $lastModified) {
        $lastModified = $currentModified;
        $data = file_get_contents($file);
        echo "data: {$data}\n\n";
        ob_flush();
        flush();
    }

    sleep(1);
}
