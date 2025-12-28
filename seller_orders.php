<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['vaitro'] !== 'seller') {
    header("Location: admin/login.php");
    exit;
}

$maNguoiBan = intval($_SESSION['user_id']);
// thêm tk.diaChi AS diaChiNguoiMua
$sql = "
SELECT dh.maDonHang, dh.ngayDat, dh.tongTien, dh.trangThai, tk.tenNguoiDung AS tenNguoiMua, tk.diaChi AS diaChiNguoiMua
FROM donhang dh
JOIN chitietdonhang ct ON dh.maDonHang = ct.maDonHang
JOIN taikhoan tk ON dh.maNguoiMua = tk.maTaiKhoan
WHERE ct.maNguoiBan = ?
GROUP BY dh.maDonHang
ORDER BY dh.maDonHang DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $maNguoiBan);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Đơn hàng của tôi</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/style1.css">
</head>
<body>
<div class="seller-container">
    <h2 class="seller-title">Đơn hàng của tôi</h2>
    <table class="seller-table">
        <tr>
            <th>Mã đơn</th>
            <th>Người mua</th>
            <th>Địa chỉ</th>
            <th>Ngày đặt</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        <?php while($r = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $r['maDonHang'] ?></td>
            <td><?= htmlspecialchars($r['tenNguoiMua']) ?></td>
            <td><?= htmlspecialchars($r['diaChiNguoiMua']) ?></td>
            <td><?= $r['ngayDat'] ?></td>
            <td><?= number_format($r['tongTien'], 0, ',', '.') ?> VND</td>

            <td>
                <?php 
                $status = $r['trangThai'];
                $class = 
                    ($status == 'Hoàn thành') ? 'status-hoanthanh' :
                    (($status == 'Đã hủy') ? 'status-huy' :
                    (($status == 'Đang giao') ? 'status-danggiao' : ''));
                ?>

                <span class="<?= $class ?>"><?= $status ?></span>
            </td>

            <td>
                <a href="seller_order_detail.php?id=<?= $r['maDonHang'] ?>">Xem</a>
                <?php if ($status !== 'Đã hủy' && $status !== 'Hoàn thành'): ?>
                    | <a href="update_order_status.php?id=<?= $r['maDonHang'] ?>&status=Đang giao">Đang giao</a>
                    | <a href="update_order_status.php?id=<?= $r['maDonHang'] ?>&status=Hoàn thành">Hoàn thành</a>
                    | <a href="update_order_status.php?id=<?= $r['maDonHang'] ?>&status=Đã hủy">Hủy</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a class="back-link" href="index.php">⬅ Quay lại</a>
</div>
</body>
</html>
