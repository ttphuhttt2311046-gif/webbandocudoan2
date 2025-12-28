<?php
session_start();
include "../db.php";

// Nếu chưa đăng nhập thì quay về login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['user_id'];

// Lấy thông tin cũ
$stmt = $conn->prepare("SELECT tenNguoiDung, soDienThoai, diaChi 
                        FROM taikhoan 
                        WHERE maTaiKhoan = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $tenNguoiDung= trim($_POST["tenNguoiDung"]);
    $soDienThoai = trim($_POST["soDienThoai"]);
    $diaChi = trim($_POST["diaChi"]);

    if (!preg_match('/^[0-9]{10}$/', $soDienThoai)) {
        $error = "Không nhập chữ và nhập đúng 10 chữ số";
    } else {

        $update = $conn->prepare("UPDATE taikhoan SET tenNguoiDung=?, soDienThoai=?, diaChi=? WHERE maTaiKhoan=?");
        $update->bind_param("sssi", $tenNguoiDung, $soDienThoai, $diaChi, $id);

        if ($update->execute()) {

            // cập nhật session
            $_SESSION['tenNguoiDung'] = $tenNguoiDung;
            $_SESSION['soDienThoai'] = $soDienThoai;
            $_SESSION['diaChi'] = $diaChi;

            echo "<script>alert('Cập nhật thành công'); window.location='ttnguoidung.php';</script>";
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thông tin người dùng</title>
<link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
<div class="cakhoi">

<h2 class="h2">Thông tin cá nhân</h2>

<form method="post">

<div class="mk">
<label>Tên người dùng:</label>
<input type="text" name="tenNguoiDung" required value="<?php echo $user['tenNguoiDung']; ?>">
</div>

<div class="mk">
<label>Số điện thoại:</label>
<input type="text" name="soDienThoai" required value="<?php echo $user['soDienThoai']; ?>">
</div>

<div class="mk">
<label>Địa chỉ:</label>
<input type="text" name="diaChi" required value="<?php echo $user['diaChi']; ?>">
</div>

<div class="dangki" style="display:flex; gap:10px;">
    <button type="submit">Lưu thay đổi</button>
    <button type="button" onclick="window.location='../index.php'">Quay lại</button>
</div>


<?php if (isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

</form>
</div>
</body>
</html>
