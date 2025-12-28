<?php
// (Trang chi tiết sản phẩm)
session_start();
include "db.php";

// ✅ Lấy ID sản phẩm từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ✅ Lấy thông tin sản phẩm theo ID
$stmt = $conn->prepare("SELECT * FROM sanpham WHERE maSanPham = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();
// Lấy tên người bán thêm vào mô tả
$sellerName = '';
if ($product && !empty($product['maNguoiBan'])) {
    $sellerId = intval($product['maNguoiBan']);
    $sqlSeller = "SELECT tenDangNhap, tenNguoiDung FROM taikhoan WHERE maTaiKhoan = ?";
    $stmtSeller = $conn->prepare($sqlSeller);
    $stmtSeller->bind_param("i", $sellerId);
    $stmtSeller->execute();
    $sellerRes = $stmtSeller->get_result();
    if ($seller = $sellerRes->fetch_assoc()) {
        $sellerName = $seller['tenNguoiDung'] ?: $seller['tenDangNhap'];
    }
    $stmtSeller->close();
}
// ✅ Tự động cập nhật tình trạng theo số lượng (chỉ khi sản phẩm tồn tại)
if ($product) {
    $newStatus = ($product['soLuong'] > 0) ? 'Còn hàng' : 'Hết hàng';

    if ($product['tinhTrang'] !== $newStatus) {
        $update = $conn->prepare("UPDATE sanpham SET tinhTrang = ? WHERE maSanPham = ?");
        $update->bind_param("si", $newStatus, $id);
        $update->execute();
        $update->close();
        // Cập nhật lại mảng sản phẩm để hiển thị đúng
        $product['tinhTrang'] = $newStatus;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Chi tiết sản phẩm</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .product-detail1 {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 30px;
    }
    .left1 { max-width: 400px; }
    .main-img1 {
      width: 380px;
      height: 380px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      display: block;
      margin: 0 auto;
      transition: none;
    }
    .thumbs1 {
      margin-top: 12px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      flex-wrap: nowrap;
    }
    .thumbs1 img {
      width: 65px;
      height: 65px;
      border-radius: 8px;
      border: 2px solid #ddd;
      cursor: pointer;
      object-fit: cover;
      transition: border-color 0.2s ease, transform 0.2s ease;
    }
    .thumbs1 img:hover {
      border-color: #0b76ff;
      transform: scale(1.05);
    }
    .product-detail1 .right {
      flex: 1;
      min-width: 280px;
    }
    .product-detail1 h2 { margin-top: 0; }
    .price1 {
      color: #e43;
      font-weight: bold;
      font-size: 25px;
    }
    .btn1 {
      background: #0b76ff;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: 600;
    }
    .btn1:hover { background: #005be8; }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container">
      <h1><a href="index.php">Shop Đồ Cũ</a></h1>
      <div class="nav">
        <a href="cart.php">
          Giỏ hàng (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'qty')) : 0; ?>)
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['vaitro'] === 'admin' || $_SESSION['vaitro'] === 'seller'): ?>
                <a href="admin/index.php">Quản lý sản phẩm</a>
            <?php endif; ?>
            <span>Xin chào, <?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?></span>
            <a href="admin/logout.php">Đăng xuất</a>
        <?php else: ?>
            <a href="admin/login.php">Đăng nhập</a>
            <a href="admin/register.php">Đăng ký</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <main class="container">
    <?php if ($product): ?>
      <div class="product-detail1">
        <div class="left">
          <img class="main-img1" 
               src="assets/img/<?php echo htmlspecialchars($product['hinhAnh']); ?>" 
               alt="<?php echo htmlspecialchars($product['tenSanPham']); ?>">

          <div class="thumbs1">
            <?php 
              for ($i = 1; $i <= 3; $i++) {
                $field = 'hinhAnh' . $i;
                if (!empty($product[$field])) {
                  echo '<img src="assets/img_phu/' . htmlspecialchars($product[$field]) . '" alt="Ảnh phụ">';
                }
              }
            ?>
          </div>
        </div>

        <div class="right">
          <h2 style="font-size:35px"><?php echo htmlspecialchars($product['tenSanPham']); ?></h2>
          <p class="price1"><?php echo number_format($product['gia'], 0, ',', '.'); ?> VND</p>
          <p style="font-size:25px"><strong>Tình trạng:</strong> <?php echo htmlspecialchars($product['tinhTrang']); ?></p>
          <p style="font-size:25px"><strong>Số lượng còn:</strong> <?php echo htmlspecialchars($product['soLuong']); ?></p>
          <p style="   color: #003366;;;;font-size:20px"><?php echo nl2br(htmlspecialchars($product['moTa'])); ?></p>
          <?php if (!empty($sellerName)): ?>
          <p  style="font-size:20px"><strong>Người bán:</strong> <?php echo htmlspecialchars($sellerName); ?></p>
          <?php endif; ?>

          <form action="cart.php" method="get" style="margin-top:15px;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id" value="<?php echo $product['maSanPham']; ?>">
            <label>Số lượng: </label>
            <input type="number" name="qty" value="1" min="1" style="width:80px;">
            <button class="btn" type="submit">Thêm vào giỏ</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <p>Sản phẩm không tồn tại.</p>
    <?php endif; ?>
  </main>

  <footer class="footer">
    <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
  </footer>

  <script>
const mainImg = document.querySelector('.main-img1');
let currentMainSrc = mainImg.src; // lưu ảnh chính hiện tại

document.querySelectorAll('.thumbs1 img').forEach(img => {
  img.addEventListener('click', () => {
    // Hoán đổi ảnh: phụ -> chính, chính -> phụ
    const temp = mainImg.src;
    mainImg.src = img.src;
    img.src = temp;
  });
});

// Cho phép click lại ảnh chính để phục hồi ảnh gốc ban đầu (nếu muốn)
mainImg.addEventListener('click', () => {
  if (mainImg.src !== currentMainSrc) {
    const temp = mainImg.src;
    mainImg.src = currentMainSrc;
    currentMainSrc = temp;
  }
});
</script>
</body>
</html>
