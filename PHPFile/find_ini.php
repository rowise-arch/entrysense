<?php
// find_ini.php
echo "<strong>=== PHP CONFIGURATION INFO ===</strong><br>";
echo "Loaded php.ini: <strong>" . php_ini_loaded_file() . "</strong><br>";
echo "Additional ini files: " . (php_ini_scanned_files() ?: 'None') . "<br>";
echo "Configuration File Path: " . PHP_CONFIG_FILE_PATH . "<br>";
?>