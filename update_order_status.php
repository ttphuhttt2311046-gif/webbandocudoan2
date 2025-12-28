<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['vaitro'] !== 'seller') {
    header("Location: admin/login.php");
    exit;
}

$id = intval($_GET['id']);
$status = $_GET['status'] ?? '';
$maNguoiBan = intval($_SESSION['user_id']);

$stmt = $conn->prepare("
UPDATE donhang 
SET trangThai = ? 
WHERE maDonHang IN (
    SELECT maDonHang FROM chitietdonhang WHERE maNguoiBan = ?
)
AND maDonHang = ?
");
$stmt->bind_param("sii", $status, $maNguoiBan, $id);
$stmt->execute();

echo "<script>alert('Cập nhật trạng thái thành công!'); window.location='seller_orders.php';</script>";