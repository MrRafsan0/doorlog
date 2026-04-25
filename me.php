<?php
session_start();
header("Content-Type: application/json");
echo json_encode([
    "logged_in" => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
]);
?>