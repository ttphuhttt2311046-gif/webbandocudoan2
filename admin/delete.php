<?php 
session_start();
include "../db.php";

// ✅ Kiểm tra đăng nhập hợp lệ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['vaitro'])) {
    header("Location: login.php");
    exit;
}

// ✅ Chỉ cho phép ADMIN hoặc NGƯỜI BÁN (seller)
if ($_SESSION['vaitro'] !== 'admin' && $_SESSION['vaitro'] !== 'seller') {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

// ✅ Kiểm tra có ID sản phẩm không
if (!isset($_GET['id'])) {
    echo "Thiếu ID sản phẩm.";
    exit;
}

$id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);
$vaitro = $_SESSION['vaitro'];

// ✅ Nếu không phải admin thì chỉ được xóa sản phẩm của chính mình
if ($vaitro !== 'admin') {
    $check = $conn->prepare("SELECT maNguoiBan FROM sanpham WHERE maSanPham = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    $check->close();

    if (!$row || $row['maNguoiBan'] != $user_id) {
        echo "<script>alert('Bạn không có quyền xóa sản phẩm này!'); window.location='qlsp.php';</script>";
        exit;
    }
}

// ✅ Tiến hành xóa sản phẩm
$delete = $conn->prepare("DELETE FROM sanpham WHERE maSanPham = ?");
$delete->bind_param("i", $id);

if ($delete->execute()) {
    echo "<script>alert('Xóa sản phẩm thành công!'); window.location='index.php';</script>";
} else {
    echo "Lỗi khi xóa sản phẩm: " . $conn->error;
}

$delete->close();
$conn->close();
?>
