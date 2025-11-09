<?php
// Check if dio functions are available
if (function_exists('dio_open')) {
    echo "dio functions are AVAILABLE<br>";
} else {
    echo "dio functions are NOT available<br>";
}

// Check available PHP extensions
echo "Loaded extensions: <br>";
foreach (get_loaded_extensions() as $ext) {
    echo "- $ext<br>";
}
?>