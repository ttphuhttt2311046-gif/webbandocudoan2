<?php
session_start();
include("../db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tenDangNhap = trim($_POST['email']);  // Form nhập là email
    $matKhau = trim($_POST['password']);

    // Truy vấn kiểm tra tài khoản
   $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE tenDangNhap = ?");
$stmt->bind_param("s", $tenDangNhap);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $taikhoan = $result->fetch_assoc();

    // 🔒 Kiểm tra tài khoản bị khóa
    if ($taikhoan['trangThai'] == 0) {
        echo "<script>alert('Tài khoản của bạn đã bị khóa!'); window.location='login.php';</script>";
        exit;
    }

    // 🔐 Kiểm tra mật khẩu
    if (password_verify($matKhau, $taikhoan['matKhau'])) {

        // Lưu session
        $_SESSION['tenNguoiDung'] = $taikhoan['tenNguoiDung'];
        $_SESSION['user_id'] = $taikhoan['maTaiKhoan'];
        $_SESSION['email'] = $taikhoan['tenDangNhap'];
        $_SESSION['vaitro'] = $taikhoan['vaitro'];
        $_SESSION['login_success'] = true;

        // Chuyển hướng theo vai trò
        if ($taikhoan['vaitro'] === 'admin') {
            header("Location: dashboard.php");
        } elseif ($taikhoan['vaitro'] === 'seller') {
            header("Location: ../index.php");
        } else {
            header("Location: ../index.php");
        }
        exit;

    } else {
        echo "<script>alert('Sai mật khẩu!');</script>";
    }

} else {
    echo "<script>alert('Không tìm thấy tài khoản!');</script>";
}

$stmt->close();

}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="cakhoi">
        <div class="h2">ĐĂNG NHẬP</div>
        <form method="post">
            <div class="mk">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="mk">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required>
            </div>
            <div class="cadkdn">
                <div class="dangki">
                    <button type="submit">Đăng Nhập</button>
                </div>
                <div class="dangki">
                    <button type="button" onclick="window.location.href='register.php'">Đăng ký</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
