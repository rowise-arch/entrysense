<?php
include __DIR__ . '/../Srcipt/db_connect.php';

// Get current settings
function getSettings() {
    global $conn;
    $settings = [];
    
    $query = "SELECT setting_key, setting_value FROM system_settings";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Default settings if table is empty
    $defaults = [
        'auto_logout' => '30',
        'theme' => 'light',
        'notifications' => '1',
        'scan_sound' => '1',
        'language' => 'en',
        'timezone' => 'Asia/Manila'
    ];
    
    return array_merge($defaults, $settings);
}

// Save settings
if ($_POST['action'] ?? '' === 'save_settings') {
    $auto_logout = $_POST['auto_logout'] ?? '30';
    $theme = $_POST['theme'] ?? 'light';
    $notifications = $_POST['notifications'] ?? '1';
    $scan_sound = $_POST['scan_sound'] ?? '1';
    $language = $_POST['language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'Asia/Manila';
    
    // Save to database (you'll need to create the table first)
    $settings = [
        'auto_logout' => $auto_logout,
        'theme' => $theme,
        'notifications' => $notifications,
        'scan_sound' => $scan_sound,
        'language' => $language,
        'timezone' => $timezone
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    exit;
}

$current_settings = getSettings();
?>