<?php
session_start();
include "../db.php";

/* ======================================================
   1) KIỂM TRA ĐĂNG NHẬP
====================================================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$vaitro  = $_SESSION['vaitro'] ?? 'user';

/* ======================================================
   2) LẤY DANH SÁCH SẢN PHẨM HIỂN THỊ TRÊN BẢNG
====================================================== */
if ($vaitro === 'admin') {

    // ADMIN → thấy tất cả sản phẩm
    $sql = "SELECT s.*, d.tenDanhMuc, t.tenNguoiDung
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.maDanhMuc = d.maDanhMuc
            LEFT JOIN taikhoan t ON s.maNguoiBan = t.maTaiKhoan
            ORDER BY s.maSanPham DESC";

    $resProducts = $conn->query($sql);

} else {

    // SELLER → chỉ thấy sản phẩm của mình đã được duyệt
    $stmt = $conn->prepare("
        SELECT s.*, d.tenDanhMuc
        FROM sanpham s
        LEFT JOIN danhmuc d ON s.maDanhMuc = d.maDanhMuc
        WHERE s.maNguoiBan = ? AND (s.duyetTrangThai = 1 OR s.duyetTrangThai IS NULL)
        ORDER BY s.maSanPham DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $resProducts = $stmt->get_result();
}

/* ======================================================
   3) TÍNH DOANH THU (CHỈ SELLER)
====================================================== */
$tongDoanhThuAll = 0;
$doanhThuNgay = 0;
$doanhThuThang = 0;
$doanhThuNam = 0;

if ($vaitro !== 'admin') {

    /* --- Doanh thu --- */
    $stmt = $conn->prepare("
    SELECT COALESCE(SUM(ct.soLuong * ct.donGia),0) AS doanhThu
    FROM chitietdonhang ct
    INNER JOIN sanpham s ON ct.maSanPham = s.maSanPham
    INNER JOIN donhang dh ON ct.maDonHang = dh.maDonHang
    WHERE s.maNguoiBan = ?
      AND dh.trangThai = 'Hoàn thành'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tongDoanhThuAll = $stmt->get_result()->fetch_assoc()['doanhThu'];

    /* --- Doanh thu theo ngày --- */
    $today = date("Y-m-d");
    $stmt = $conn->prepare("
    SELECT COALESCE(SUM(ct.soLuong * ct.donGia),0) AS doanhThuNgay
    FROM chitietdonhang ct
    INNER JOIN sanpham s ON ct.maSanPham = s.maSanPham
    INNER JOIN donhang dh ON ct.maDonHang = dh.maDonHang
    WHERE s.maNguoiBan = ?
      AND DATE(dh.ngayDat) = ?
      AND dh.trangThai = 'Hoàn thành'");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $doanhThuNgay = $stmt->get_result()->fetch_assoc()['doanhThuNgay'];

    /* --- Doanh thu theo tháng --- */
    $month = date("Y-m");       
    $stmt = $conn->prepare("
    SELECT COALESCE(SUM(ct.soLuong * ct.donGia),0) AS doanhThuThang
    FROM chitietdonhang ct
    INNER JOIN sanpham s ON ct.maSanPham = s.maSanPham
    INNER JOIN donhang dh ON ct.maDonHang = dh.maDonHang
    WHERE s.maNguoiBan = ?
      AND DATE_FORMAT(dh.ngayDat,'%Y-%m') = ?
      AND dh.trangThai = 'Hoàn thành'");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $doanhThuThang = $stmt->get_result()->fetch_assoc()['doanhThuThang'];

    /* --- Doanh thu theo năm --- */
    $year = date("Y");
    $stmt = $conn->prepare("
    SELECT COALESCE(SUM(ct.soLuong * ct.donGia),0) AS doanhThuNam
    FROM chitietdonhang ct
    INNER JOIN sanpham s ON ct.maSanPham = s.maSanPham
    INNER JOIN donhang dh ON ct.maDonHang = dh.maDonHang
    WHERE s.maNguoiBan = ?
      AND DATE_FORMAT(dh.ngayDat,'%Y') = ?
      AND dh.trangThai = 'hoàn thành'");
    $stmt->bind_param("is", $user_id, $year);
    $stmt->execute();
    $doanhThuNam = $stmt->get_result()->fetch_assoc()['doanhThuNam'];
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Quản lý sản phẩm</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
table.admin-table img { border-radius: 8px; object-fit: cover; }
th, td { padding: 8px; }
th { background: #f0f0f0; }

.doanhthu {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(6px);
    padding: 20px 25px;
    border-radius: 12px;
    width: 350px;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.doanhthu h3 {
    margin: 0 0 15px 0;
    font-size: 22px;
    font-weight: 600;
    color: #333;
    text-align: left;
}

.doanhthu p {
    margin: 8px 0;
    font-size: 16px;
    color: #333;
}

.doanhthu p span {
    font-weight: bold;
    color: #000;
}

</style>
</head>

<body>

<header class="topbar">
    <div class="container">
        <h1>Quản lý sản phẩm</h1>
        <div class="nav">
            <a href="../index.php">Xem site</a>
            <a href="add.php">Thêm sản phẩm</a>
            <a href="logout.php">Đăng xuất (<?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?>)</a>
        </div>
    </div>
</header>

<main class="container">
    <h2>Danh sách sản phẩm</h2>
<?php if ($vaitro !== 'admin'): ?>
<div class="doanhthu">
    <h3>Doanh thu</h3>

    <p>Tổng doanh thu: <span><?= number_format($tongDoanhThuAll,0,',','.') ?> VND</span></p>
    <p>Hôm nay: <span><?= number_format($doanhThuNgay,0,',','.') ?> VND</span></p>
    <p>Tháng này: <span><?= number_format($doanhThuThang,0,',','.') ?> VND</span></p>
    <p>Năm nay: <span><?= number_format($doanhThuNam,0,',','.') ?> VND</span></p>
</div>
<?php endif; ?>

    <table class="admin-table" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Tình trạng</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>

        <?php if ($resProducts && $resProducts->num_rows > 0): ?>
            <?php while ($p = $resProducts->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['maSanPham'] ?></td>

                    <td>
                        <?php if (!empty($p['hinhAnh'])): ?>
                            <img src="../assets/img/<?= htmlspecialchars($p['hinhAnh']) ?>" width="80" height="80">
                        <?php else: ?>
                            Không ảnh
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($p['tenSanPham']) ?></td>
                    <td><?= htmlspecialchars($p['tenDanhMuc'] ?? 'Không có') ?></td>
                    <td><?= number_format($p['gia'], 0, ',', '.') ?> VND</td>
                    <td><?= htmlspecialchars($p['tinhTrang']) ?></td>

                    <td>
                        <?php if ($vaitro === 'admin' || $p['maNguoiBan'] == $user_id): ?>
                            <a class="btn" href="edit.php?id=<?= $p['maSanPham'] ?>">Sửa</a>
                            <a class="btn btn-outline" 
                               href="delete.php?id=<?= $p['maSanPham'] ?>"
                               onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">Xóa</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr><td colspan="7">Chưa có sản phẩm nào.</td></tr>
        <?php endif; ?>

        </tbody>
    </table>
    
</main>
</body>
</html>
