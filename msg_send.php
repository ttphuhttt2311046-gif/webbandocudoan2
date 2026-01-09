<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Chưa đăng nhập']);
    exit;
}

$sender   = (int)$_SESSION['user_id'];
$receiver = (int)($_POST['receiver_id'] ?? 0);
$message  = trim($_POST['message'] ?? '');

if ($receiver <= 0 || $message === '') {
    echo json_encode(['success'=>false,'error'=>'Dữ liệu không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO nhantin (noiDung, maNguoiGui, maNguoiNhan, trangThai, ngayGui)
    VALUES (?, ?, ?, 'chua_xem', NOW())
");
$stmt->bind_param("sii", $message, $sender, $receiver);

$ok = $stmt->execute();

echo json_encode([
    'success' => $ok,
    'error'   => $ok ? null : $conn->error
]);
