<?php 
require 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$sender = (int)$_SESSION['user_id'];
$receiver = (int)($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($receiver <= 0 || $message === '') {
    echo json_encode(['success'=>false, 'error'=>'Dữ liệu không hợp lệ']);
    exit;
}

// Tìm dòng chat giữa 2 người
$sql = "SELECT maThongBao, phanHoi FROM nhantin 
        WHERE (maNguoiGui = ? AND maNguoiNhan = ?) 
           OR (maNguoiGui = ? AND maNguoiNhan = ?)
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender, $receiver, $receiver, $sender);
$stmt->execute();
$res = $stmt->get_result();

$time = date("H:i");
$tag = "[{$sender}]"; // đánh dấu người gửi thực
$ok = false;

if ($row = $res->fetch_assoc()) {
    // Đã có hội thoại -> nối thêm vào phanHoi
    $old = $row['phanHoi'] ?? '';
    $newMsg = trim($old . "\n" . "$tag [$time] $message");
    // Luôn đặt trạng thái CHƯA XEM cho NGƯỜI NHẬN (receiver)
    $stmt2 = $conn->prepare("UPDATE nhantin 
                             SET phanHoi=?, ngayGui=NOW(), trangThai='chua_xem', maNguoiGui=?, maNguoiNhan=? 
                             WHERE maThongBao=?");
    $stmt2->bind_param("siii", $newMsg, $sender, $receiver, $row['maThongBao']);
    $ok = $stmt2->execute();
} else {
    // Chưa có hội thoại -> tạo mới
    $msgFormatted = "$tag [$time] $message";
    $stmt2 = $conn->prepare("INSERT INTO nhantin (noiDung, phanHoi, maNguoiGui, maNguoiNhan, trangThai) 
                             VALUES (?, ?, ?, ?, 'chua_xem')");
    $stmt2->bind_param("ssii", $message, $msgFormatted, $sender, $receiver);
    $ok = $stmt2->execute();
}


echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : $conn->error]);
?>
