<?php
session_start();
include "../db.php";

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['vaitro'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['vaitro'] !== 'admin' && $_SESSION['vaitro'] !== 'seller') {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

if (!isset($_GET['id'])) {
    echo "Thiếu ID sản phẩm.";
    exit;
}

$id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);
$vaitro = $_SESSION['vaitro'];

// ✅ Lấy sản phẩm cần sửa
$stmt = $conn->prepare("SELECT * FROM sanpham WHERE maSanPham = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "Không tìm thấy sản phẩm.";
    exit;
}

if ($vaitro !== 'admin' && $product['maNguoiBan'] != $user_id) {
    echo "<script>alert('Bạn không có quyền sửa sản phẩm này!'); window.location='index.php';</script>";
    exit;
}

// ✅ Lấy danh sách danh mục để hiển thị trong form
$danhmuc_result = $conn->query("SELECT maDanhMuc, tenDanhMuc FROM danhmuc ORDER BY tenDanhMuc ASC");

$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tenSanPham = trim($_POST['tenSanPham']);
    $moTa = trim($_POST['moTa']);
    $gia = floatval($_POST['gia']);
    $soLuong = intval($_POST['soLuong']);
    $maDanhMuc = intval($_POST['maDanhMuc']);
    $tinhTrang = ($soLuong <= 0) ? "Hết hàng" : "Còn hàng";

    // --- Xử lý ảnh chính ---
    $hinhAnh = $product['hinhAnh'];
    if (!empty($_FILES['hinhAnh']['name'])) {
        $orig = basename($_FILES['hinhAnh']['name']);
        $safeName = time() . "_" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $orig);
        move_uploaded_file($_FILES['hinhAnh']['tmp_name'], "../assets/img/" . $safeName);
        $hinhAnh = $safeName;
    }

    // --- Xử lý ảnh phụ 1, 2, 3 ---
    $hinhAnh1 = $product['hinhAnh1'];
    $hinhAnh2 = $product['hinhAnh2'];
    $hinhAnh3 = $product['hinhAnh3'];

    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES["hinhAnh{$i}"]["name"])) {
            $orig = basename($_FILES["hinhAnh{$i}"]["name"]);
            $safeName = time() . "_{$i}_" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $orig);
            move_uploaded_file($_FILES["hinhAnh{$i}"]["tmp_name"], "../assets/img_phu/" . $safeName);
            ${"hinhAnh{$i}"} = $safeName;
        }
    }

    // --- Cập nhật sản phẩm (CÓ cập nhật danh mục) ---
    $update = $conn->prepare("UPDATE sanpham 
        SET tenSanPham=?, moTa=?, gia=?, soLuong=?, tinhTrang=?, hinhAnh=?, hinhAnh1=?, hinhAnh2=?, hinhAnh3=?, maDanhMuc=?
        WHERE maSanPham=?");
    $update->bind_param("ssdisssssii",
        $tenSanPham, $moTa, $gia, $soLuong, $tinhTrang,
        $hinhAnh, $hinhAnh1, $hinhAnh2, $hinhAnh3, $maDanhMuc, $id
    );

    if ($update->execute()) {
        echo "<script>alert('Cập nhật sản phẩm thành công!'); window.location.href='index.php';</script>";
        exit;
    } else {
        $err = "Lỗi khi cập nhật sản phẩm: " . $conn->error;
    }
    $update->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Sửa sản phẩm</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
  input[readonly] {
      background: #f8f8f8;
      border: 1px solid #ccc;
      padding: 6px 10px;
      border-radius: 4px;
      color: #555;
  }
  </style>
</head>
<body>
  <main class="container">
    <h2>Sửa sản phẩm #<?php echo htmlspecialchars($product['maSanPham']); ?></h2>
    <?php if ($err) echo '<p class="error">'.htmlspecialchars($err).'</p>'; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form">
      <label>Tên sản phẩm</label>
      <input type="text" name="tenSanPham" value="<?php echo htmlspecialchars($product['tenSanPham']); ?>" required>

      <label>Mô tả</label>
      <textarea name="moTa" rows="4"><?php echo htmlspecialchars($product['moTa']); ?></textarea>

      <label>Giá (VND)</label>
      <input type="number" name="gia" step="1000" value="<?php echo htmlspecialchars($product['gia']); ?>" required>

      <label>Số lượng</label>
      <input type="number" name="soLuong" id="soLuong" min="0" value="<?php echo htmlspecialchars($product['soLuong']); ?>" required>

      <label>Tình trạng</label>
      <input type="text" name="tinhTrang" id="tinhTrang" value="<?php echo htmlspecialchars($product['tinhTrang']); ?>" readonly>

    <label>Danh mục</label>
<select name="maDanhMuc" required>
  <option value="">-- Chọn danh mục --</option>
  <?php while ($row = $danhmuc_result->fetch_assoc()): ?>
    <option value="<?php echo $row['maDanhMuc']; ?>"
      <?php if ($product['maDanhMuc'] == $row['maDanhMuc']) echo 'selected'; ?>>
      <?php echo htmlspecialchars($row['tenDanhMuc']); ?>
    </option>
  <?php endwhile; ?>
</select>


      <label>Ảnh hiện tại</label>
      <div><img src="../assets/img/<?php echo htmlspecialchars($product['hinhAnh']); ?>" width="160"></div>

      <label>Thay ảnh mới (nếu muốn)</label>
      <input type="file" name="hinhAnh" accept="image/*">
      <label>Ảnh phụ 1:</label>
      <input type="file" name="hinhAnh1"><br>
      <label>Ảnh phụ 2:</label>
      <input type="file" name="hinhAnh2"><br>
      <label>Ảnh phụ 3:</label>
      <input type="file" name="hinhAnh3"><br>

      <div class="form-actions">
  <button class="btn" type="submit">Lưu</button>
  <a class="btn btn-outline" href="index.php">Hủy</a>
</div>
    </form>
  </main>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
      const soLuongInput = document.getElementById('soLuong');
      const tinhTrangInput = document.getElementById('tinhTrang');
      function capNhatTinhTrang() {
          const sl = parseInt(soLuongInput.value) || 0;
          tinhTrangInput.value = sl <= 0 ? "Hết hàng" : "Còn hàng";
      }
      capNhatTinhTrang();
      soLuongInput.addEventListener('input', capNhatTinhTrang);
  });
  </script>
</body>
</html>
