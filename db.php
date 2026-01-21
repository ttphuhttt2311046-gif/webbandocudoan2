<?php
// db.php - kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "bandocu";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>