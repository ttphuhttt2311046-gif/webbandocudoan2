<?php
session_start();
include "db.php";

// Thời gian 20 phút (1200 giây) để tính là phiên mới
$sessionTimeout = 1200;

if (!isset($_SESSION['last_visit']) || time() - $_SESSION['last_visit'] > $sessionTimeout) {
    $conn->query("UPDATE counter SET total = total + 1 WHERE id = 1");
}
$_SESSION['last_visit'] = time();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Shop Đồ Cũ - Trang chủ</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="container">
    <div class="logo-title">
      <img src="assets/img/LOGO.png" alt="Logo" class="logo">
      <h1><a href="index.php">Shop Đồ Cũ</a></h1>
    </div>

    <!-- 🔍 SEARCH + 🎤 VOICE -->
    <div class="search-bar">
      <form action="timkiem.php" method="GET" id="searchForm">
        <input type="text"
               id="query"
               name="query"
               placeholder="Tìm sản phẩm..."
               required>
        <button type="button" class="btn-mic" onclick="startVoice()">🎤</button>
        <button type="submit">➤</button>
      </form>
    </div>

    <div class="nav">
      <a href="cart.php">
        Giỏ hàng (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>)
      </a>

      <?php if (isset($_SESSION['user_id'])): ?>
          <?php if (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'admin'): ?>
              <a href="admin/dashboard.php">Quản lý</a>
          <?php endif; ?>

          <?php if (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'seller'): ?>
              <a href="admin/index.php">Quản lí sản phẩm</a>
              <a href="seller_orders.php">Đơn hàng của tôi</a>
          <?php endif; ?>

          <a href="admin/ttnguoidung.php" style="color:blue;">
              Xin chào, <?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?>
          </a>
          <a href="admin/logout.php">Đăng xuất</a>
      <?php else: ?>
          <a href="admin/login.php">Đăng nhập</a>
          <a href="admin/register.php">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- 🔻 BANNER -->
<div class="banner-img1">
  <div class="banner-slider">
    <img src="assets/img/cau-hinh-pc-cho-thiet-ke-do-hoa.jpg">
    <img src="assets/img/bean.jpg">
    <img src="assets/img/phan-biet-may-giat-cong-nghiep-va-may-giat-thuong.jpg">
    <img src="assets/img/banner1.jpg">
    <img src="assets/img/banner2.png">
    <img src="assets/img/banner3.jpg">
    <img src="assets/img/bean.jpg">
    <img src="assets/img/banner4.jpg">
    <img src="assets/img/banner5.jpg">
    <img src="assets/img/banner6.png">
  </div>
</div>

<main class="container">

<!-- 🔹 DANH MỤC -->
<div class="category-bar">
<?php
$cats = $conn->query("SELECT maDanhMuc, tenDanhMuc FROM danhmuc ORDER BY tenDanhMuc ASC");
$allActive = !isset($_GET['cat']) || $_GET['cat'] === '' ? 'active' : '';
echo '<a href="index.php" class="cat-item all '.$allActive.'">Tất cả</a>';

if ($cats && $cats->num_rows > 0) {
    while ($cat = $cats->fetch_assoc()) {
        $catId = (int)$cat['maDanhMuc'];
        $active = (isset($_GET['cat']) && intval($_GET['cat']) === $catId) ? 'active' : '';
        echo '<a class="cat-item '.$active.'" href="index.php?cat='.$catId.'">'.htmlspecialchars($cat['tenDanhMuc']).'</a>';
    }
}
?>
</div>

<!-- 🔹 SẢN PHẨM -->
<div class="grid">
  <?php include "phantrang.php"; ?>
</div>

</main>

<footer class="footer">
  <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
</footer>

<!-- 🎤 VOICE SEARCH SCRIPT -->
<script>
function startVoice() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Trình duyệt không hỗ trợ tìm kiếm bằng giọng nói");
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = "vi-VN";
    recognition.interimResults = false;

    recognition.onresult = function(event) {
        const text = event.results[0][0].transcript;
        document.getElementById("query").value = text;
        document.getElementById("searchForm").submit();
    };

    recognition.onerror = function() {
        alert("Không nhận được giọng nói");
    };

    recognition.start();
}
</script>

</body>
</html>
