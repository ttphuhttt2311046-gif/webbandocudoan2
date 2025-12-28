<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo '<p>Chưa đăng nhập</p>';
    exit;
}

$me = (int)$_SESSION['user_id'];
$to = (int)($_GET['to'] ?? 0);
if ($to <= 0) {
    echo '<p>Chưa chọn người nhận</p>';
    exit;
}

// 🧩 Lấy danh sách tin nhắn giữa hai người, mới nhất lên đầu
$sql = "SELECT maNguoiGui, noiDung, thoiGian
        FROM nhantin
        WHERE (maNguoiGui = ? AND maNguoiNhan = ?)
           OR (maNguoiGui = ? AND maNguoiNhan = ?)
        ORDER BY thoiGian ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $me, $to, $to, $me);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $senderId = (int)$row['maNguoiGui'];
        $text = $row['noiDung'];
        $time = date('H:i', strtotime($row['thoiGian'] ?? 'now'));

        // ⚙️ Hiển thị khác nhau cho người gửi / người nhận
        if ($senderId == $me) {
            echo "<div class='msg-me'>{$text}<div class='time'>{$time}</div></div>";
        } else {
            echo "<div class='msg-other'>{$text}<div class='time'>{$time}</div></div>";
        }
    }
} else {
    echo "<p>Chưa có tin nhắn nào.</p>";
}

// 🔹 Đánh dấu tin đã xem
$conn->query("UPDATE nhantin 
              SET trangThai='da_xem' 
              WHERE maNguoiNhan=$me AND maNguoiGui=$to");
?>
