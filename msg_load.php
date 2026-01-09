<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) exit;

$me = (int)$_SESSION['user_id'];
$to = (int)($_GET['to'] ?? 0);
if ($to <= 0) exit;

$stmt = $conn->prepare("
    SELECT * FROM nhantin
    WHERE
      (maNguoiGui = ? AND maNguoiNhan = ?)
      OR
      (maNguoiGui = ? AND maNguoiNhan = ?)
    ORDER BY ngayGui ASC
");
$stmt->bind_param("iiii", $me, $to, $to, $me);
$stmt->execute();
$res = $stmt->get_result();

while ($m = $res->fetch_assoc()) {
    $isMe = $m['maNguoiGui'] == $me;
    $cls  = $isMe ? 'msg-me' : 'msg-other';

    echo "<div class='$cls'>";

    // 👉 Nếu là THÔNG BÁO HỆ THỐNG
    if (strpos($m['noiDung'], '\n')!== false) {
        echo $m['noiDung']; // render HTML
    } else {
        echo nl2br(htmlspecialchars($m['noiDung'])); // chat thường
    }

    echo "<span class='time'>".date("H:i", strtotime($m['ngayGui']))."</span>";
    echo "</div>";
}

/* đánh dấu đã xem */
$conn->query("
    UPDATE nhantin
    SET trangThai='da_xem'
    WHERE maNguoiNhan=$me AND maNguoiGui=$to
");
