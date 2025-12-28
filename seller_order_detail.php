<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['vaitro'] !== 'seller') {
    header("Location: admin/login.php");
    exit;
}

$maDonHang = intval($_GET['id']);
$maNguoiBan = intval($_SESSION['user_id']);

$stmt = $conn->prepare("
    SELECT sp.tenSanPham, ct.soLuong, ct.donGia, (ct.soLuong * ct.donGia) AS thanhTien
    FROM chitietdonhang ct
    JOIN sanpham sp ON ct.maSanPham = sp.maSanPham
    WHERE ct.maDonHang = ? AND ct.maNguoiBan = ?
");
$stmt->bind_param("ii", $maDonHang, $maNguoiBan);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Chi tiết đơn hàng #<?= $maDonHang ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/style1.css">
</head>
<body>
<div class="seller-container">
    <h2 class="seller-title">Chi tiết đơn hàng #<?= $maDonHang ?></h2>

    <table class="seller-table">
        <tr>
            <th>Sản phẩm</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Thành tiền</th>
        </tr>

        <?php while ($r = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($r['tenSanPham']) ?></td>
            <td><?= $r['soLuong'] ?></td>
            <td><?= number_format($r['donGia'], 0, ',', '.') ?> VND</td>
            <td><?= number_format($r['thanhTien'], 0, ',', '.') ?> VND</td>
        </tr>
        <?php endwhile; ?>
    </table>

    <a class="back-link" href="seller_orders.php">⬅ Quay lại danh sách đơn hàng</a>
</div>
</body>
</html>
