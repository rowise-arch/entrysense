<?php
// Database connection settings
$host = 'localhost';
$user = 'root';         // default XAMPP MySQL username
$pass = '';             // default XAMPP MySQL password (empty)
$dbname = 'entrysense';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>