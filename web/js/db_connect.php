<?php
$servername = "localhost";
$username = "root";
$password = "";  // Empty password for WAMP
$database = "entrysense";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>