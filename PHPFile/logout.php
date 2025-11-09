<?php
session_start();
include __DIR__ . '/../Srcipt/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: ../index.html?logout=1');
exit();
?>