<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$me = intval($_SESSION['user_id']);

// Lấy danh sách người gửi có tin chưa xem
$sql = "SELECT maNguoiGui 
        FROM nhantin 
        WHERE maNguoiNhan = $me AND trangThai = 'chua_xem'";
$res = $conn->query($sql);

$unread = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $unread[] = $r['maNguoiGui'];
    }
}

echo json_encode(["success" => true, "unread" => $unread]);
?>
