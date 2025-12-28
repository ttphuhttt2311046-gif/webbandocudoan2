<?php
session_start();
include "db.php";

$query = trim($_GET['query'] ?? '');
if ($query === '') {
    header("Location: index.php");
    exit;
}
// chỗ này sửa tìm kiếm hiện nó mới tìm
$sql = "SELECT * FROM sanpham 
        WHERE trangThai = 1
          AND (tenSanPham LIKE ? OR moTa LIKE ?)
        ORDER BY maSanPham DESC";

$stmt = $conn->prepare($sql);
$likeQuery = "%$query%";
$stmt->bind_param("ss", $likeQuery, $likeQuery);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Kết quả tìm kiếm - <?php echo htmlspecialchars($query); ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="container">
    <h1><a href="index.php">Shop Đồ Cũ</a></h1>
    <div class="search-bar">
        <form action="timkiem.php" method="GET">
            <input type="text" name="query" placeholder="Tìm sản phẩm..." required value="<?php echo htmlspecialchars($query); ?>">
            <button type="submit">➤</button>
        </form>
    </div>
    <div class="nav">
      <a href="cart.php">Giỏ hàng (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>)</a>
      <?php if (isset($_SESSION['user_id'])): ?>
          <?php if (isset($_SESSION['vaitro']) && ($_SESSION['vaitro'] === 'seller' || $_SESSION['vaitro'] === 'admin')): ?>
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
  <h2>Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($query); ?>"</h2>
  <div class="grid">
  <?php
  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $img = 'assets/img/' . ($row['hinhAnh'] ?: 'placeholder.png');
          echo '<div class="card" onclick="window.location.href=\'product.php?id='.$row['maSanPham'].'\'">';
          echo '<div class="thumb"><img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($row['tenSanPham']).'"></div>';
          echo '<div class="meta">';
          echo '<div class="title">'.htmlspecialchars($row['tenSanPham']).'</div>';
          echo '<div class="price">'.number_format($row['gia'],0,',','.').' VND</div>';
          echo '</div>';
          echo '<p class="desc">'. (strlen($row['moTa'])>80 ? htmlspecialchars(substr($row['moTa'],0,80)).'...' : htmlspecialchars($row['moTa'])) .'</p>';
          echo '<div class="card-actions">';
          echo '<a class="btn" href="product.php?id='.$row['maSanPham'].'">Xem chi tiết</a>';
          echo '<a class="btn btn-outline" href="cart.php?action=add&id='.$row['maSanPham'].'">Thêm vào giỏ</a>';
          echo '</div>';
          echo '</div>';
      }
  } else {
      echo '<p>Không tìm thấy sản phẩm nào phù hợp.</p>';
  }
  ?>
  </div>
</main>
<footer class="footer">
  <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
</footer>
</body>
</html>
