<?php
session_start();
include "../db.php";

// =======================
// KIỂM TRA ĐĂNG NHẬP
// =======================
if (!isset($_SESSION["user_id"])) {
    die("Bạn chưa đăng nhập!");
}
$maNguoiBan = $_SESSION["user_id"];

// =======================
// LẤY DANH MỤC
// =======================
$danhmuc_result = $conn->query("SELECT * FROM danhmuc ORDER BY tenDanhMuc ASC");

$msg = "";
$err = "";
$toast = false; // Mặc định không hiển thị toast

// =======================
// KHI SUBMIT FORM
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ten = trim($_POST["tenSanPham"]);
    $moTa = trim($_POST["moTa"]);
    $gia = intval($_POST["gia"]);
    $soLuong = intval($_POST["soLuong"]);
    $maDanhMuc = intval($_POST["maDanhMuc"]);

    $tinhTrang = ($soLuong > 0) ? "Còn hàng" : "Hết hàng";

    if ($ten == "" || $moTa == "" || $gia <= 0) {
        $err = "Thiếu thông tin sản phẩm!";
    } else {

        // =======================
        // LẤY CÀI ĐẶT DUYỆT
        // =======================
        $cs = $conn->query("SELECT * FROM duyet_settings LIMIT 1");
        $set = $cs->fetch_assoc();

        $auto = intval($set["auto_review"]);
        $banned_list = array_filter(array_map("trim", explode(",", strtolower($set["banned_words"]))));

        // =======================
        // CHECK TỪ CẤM
        // =======================
        $viPham = false;
        $ten_lower = strtolower($ten);
        $mota_lower = strtolower($moTa);

        foreach ($banned_list as $w) {
            if ($w != "" && (str_contains($ten_lower, $w) || str_contains($mota_lower, $w))) {
                $viPham = true;
                break;
            }
        }

        if ($auto == 1) {
            $duyet = $viPham ? 2 : 1; // 2 = từ chối, 1 = duyệt
        } else {
            $duyet = 0; // chờ duyệt
        }

        // =======================
        // UPLOAD ẢNH
        // =======================
        function uploadImg($fieldName) {
            if (!empty($_FILES[$fieldName]["name"])) {
                $orig = basename($_FILES[$fieldName]["name"]);
                $newName = time() . "_" . preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $orig);
                $target = "../assets/img/" . $newName;
                move_uploaded_file($_FILES[$fieldName]["tmp_name"], $target);
                return $newName;
            }
            return null;
        }

        $hinhAnh = uploadImg("hinhAnh");
        if (!$hinhAnh) {
            $err = "Vui lòng chọn ảnh chính!";
        }

        $h1 = uploadImg("hinhAnh1");
        $h2 = uploadImg("hinhAnh2");
        $h3 = uploadImg("hinhAnh3");

        // =======================
        // LƯU DATABASE
        // =======================
        if ($err == "") {

            $stmt = $conn->prepare("
                INSERT INTO sanpham 
                (tenSanPham, moTa, gia, soLuong, tinhTrang, maNguoiBan, maDanhMuc,
                 hinhAnh, hinhAnh1, hinhAnh2, hinhAnh3, duyetTrangThai)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssiiisiisssi",
                $ten, $moTa, $gia, $soLuong, $tinhTrang, $maNguoiBan, $maDanhMuc,
                $hinhAnh, $h1, $h2, $h3, $duyet
            );

            $stmt->execute();

            // =======================
            // HIỂN THỊ TOAST POPUP
            // =======================
            if ($duyet == 1) {
                $toast = [
                    'type' => 'success',
                    'icon' => '✔',
                    'message' => 'Sản phẩm đã được DUYỆT!'
                ];
            } elseif ($duyet == 2) {
                $toast = [
                    'type' => 'error',
                    'icon' => '❌',
                    'message' => 'Sản phẩm bị từ chối vì từ cấm!'
                ];
            } else {
                $toast = [
                    'type' => 'warning',
                    'icon' => '⏳',
                    'message' => 'Sản phẩm đang CHỜ admin DUYỆT!'
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Thêm sản phẩm</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    select[name="tinhTrang"] {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background: #f8f8f8;
        border: 1px solid #ccc;
        padding: 6px 10px;
        border-radius: 4px;
        color: #555;
    }
    form { max-width: 600px; margin: 20px auto; background: #f9f9f9; padding: 20px; border-radius: 10px; }
    input, textarea, select { width: 100%; margin: 8px 0; padding: 8px; }
    button { padding: 10px 15px; cursor: pointer; }
    .error { color: red; margin-bottom: 10px; }

    /* Toast popup giữa màn hình */
    .toast {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) translateY(100px);
        min-width: 300px;
        padding: 15px 20px;
        border-radius: 8px;
        color: #fff;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        opacity: 0;
        transition: all 0.5s ease;
        z-index: 9999;
        text-align: center;
    }
    .toast.show { 
        opacity: 1; 
        transform: translate(-50%, -50%) translateY(0); 
    }
    .toast.success { background: #4caf50; }
    .toast.error { background: #f44336; }
    .toast.warning { background: #ff9800; }
  </style>
</head>
<body>
  <main class="container">
    <
    <?php if ($err) echo '<p class="error">'.htmlspecialchars($err).'</p>'; ?>
    <form method="post" enctype="multipart/form-data" class="admin-form">
    <h2>Thêm sản phẩm mới</h2>
      <label>Tên sản phẩm</label>
      <input type="text" name="tenSanPham" required>

      <label>Mô tả</label>
      <textarea name="moTa" rows="4"></textarea>

      <label>Giá (VND)</label>
      <input type="number" name="gia" step="1000" required>

      <label>Số lượng</label>
      <input type="number" name="soLuong" id="soLuong" min="0" required>

      <label>Tình trạng</label>
      <input type="text" name="tinhTrang" id="tinhTrang" value="Còn hàng" readonly>

      <label>Danh mục:</label>
      <select name="maDanhMuc" required>
        <option value="">-- Chọn danh mục --</option>
        <?php while ($row = $danhmuc_result->fetch_assoc()): ?>
          <option value="<?php echo $row['maDanhMuc']; ?>">
            <?php echo htmlspecialchars($row['tenDanhMuc']); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>Hình ảnh</label>
      <input type="file" name="hinhAnh" accept="image/*">

      <label>Ảnh phụ 1:</label>
      <input type="file" name="hinhAnh1"><br>
      <label>Ảnh phụ 2:</label>
      <input type="file" name="hinhAnh2"><br>
      <label>Ảnh phụ 3:</label>
      <input type="file" name="hinhAnh3"><br>

      <button class="btn" type="submit">Thêm</button>
      <a class="btn btn-outline" href="index.php">Hủy</a>
    </form>

    <?php if ($toast): ?>
    <div id="toast" class="toast <?= $toast['type'] ?>">
        <span class="icon"><?= $toast['icon'] ?></span>
        <span class="message"><?= $toast['message'] ?></span>
    </div>
    <script>
    window.addEventListener("load", function(){
        const toast = document.getElementById("toast");
        toast.classList.add("show");
        setTimeout(()=>{ toast.classList.remove("show"); }, 3000);
    });
    </script>
    <?php endif; ?>

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
