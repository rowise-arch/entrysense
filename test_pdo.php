<?php
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=entrysense",
        "root",
        ""
    );
    echo "PDO connection successful!";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>