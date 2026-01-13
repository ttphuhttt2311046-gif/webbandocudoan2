<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tenNguoiDung= trim($_POST["tenNguoiDung"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $vaitro = $_POST["vaitro"];
    $soDienThoai = trim($_POST["soDienThoai"]);
    $diaChi = trim($_POST["diaChi"]);

    if ($email === "" || $password === "") {
        $error = "Vui lòng nhập đầy đủ email và mật khẩu!";
    } else {
        // ✅ Kiểm tra tài khoản đã tồn tại chưa
        $check = $conn->prepare("SELECT * FROM taikhoan WHERE tenDangNhap = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email này đã tồn tại!";
        } else {
            // ✅ Mã hóa mật khẩu
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Thêm tài khoản mới
            $stmt = $conn->prepare("INSERT INTO taikhoan (tenNguoiDung,tenDangNhap, matKhau, soDienThoai, diaChi, vaitro)
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss",$tenNguoiDung, $email, $hashed, $soDienThoai, $diaChi, $vaitro);

            if ($stmt->execute()) {
                echo "<script>alert('Đăng ký thành công! Hãy đăng nhập.'); window.location='login.php';</script>";
                exit;
            } else {
                $error = "Lỗi khi đăng ký: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="UTF-8">
  <title>Đăng ký tài khoản</title>
  <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
  <div class="cakhoi">
    <h2 class="h2">Đăng ký tài khoản</h2>
    <form method="post">

    <div class="mk">
                <label>Tên người dùng:</label>
                <input type="text" name="tenNguoiDung" required>
            </div>
      <div class="mk">
        <label>Email:</label>
        <input type="email" name="email" required>
      </div>

      <div class="mk">
        <label>Mật khẩu:</label>
        <input type="password" name="password" required>
      </div>

      <div class="mk">
        <label>Số điện thoại:</label>
        <input type="text" name="soDienThoai" required>
      </div>

      <div class="mk">
        <label>Địa chỉ:</label>
        <input type="text" name="diaChi" required>
      </div>

      <div class="banla">
        <label>Bạn là:</label>
        <select name="vaitro" required style="width:300px; height:40px; border-radius:8px; padding:5px 10px; font-size:16px;">
          <option value="buyer">Người mua</option>
          <option value="seller">Người bán</option>
        </select>
      </div>

      <div class="cadkdn">
        <div class="dangki">
          <button type="submit">Đăng ký</button>
        </div>
        <p style="color:white; font-size:18px; text-align:center; margin-top:15px;">
          Đã có tài khoản? 
          <a href="login.php" style="color:#00ff2f;">Đăng nhập</a>
        </p>
      </div>

      <?php if (isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    </form>
  </div>
</body>
</html>
