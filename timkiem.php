<?php
session_start();
include "db.php";

/* ==============================
   HÀM BỎ DẤU TIẾNG VIỆT
================================ */
function removeVietnameseAccents($str) {
    $accents = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
        'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d','Đ'=>'D'
    ];
    return strtr($str, $accents);
}

/* ==============================
   LẤY & CHUẨN HÓA TỪ KHÓA
================================ */
$queryRaw = trim($_GET['query'] ?? '');
if ($queryRaw === '') {
    header("Location: index.php");
    exit;
}

$query = strtolower(removeVietnameseAccents($queryRaw));
$like = "%$query%";

/* ==============================
   SQL: TÌM KIẾM VIỆT + ANH
================================ */
$sql = "
SELECT * FROM sanpham
WHERE trangThai = 1
AND (
    LOWER(tenSanPham) LIKE ?
    OR LOWER(moTa) LIKE ?
)
ORDER BY maSanPham DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Kết quả tìm kiếm - <?php echo htmlspecialchars($queryRaw); ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="container">
<h1 style="
  margin-right:25px;
  white-space:nowrap;
  font-size:28px;
  display:flex;
  align-items:center;
">
  <a href="index.php" style="
    text-decoration:none;
    color:white;
    white-space:nowrap;
  ">
    Shop Đồ Cũ
  </a>
</h1>


    <!-- 🔍 SEARCH + 🎤 VOICE -->
    <div class="search-bar">
      <form action="timkiem.php" method="GET" id="searchForm">
        <input type="text"
               id="query"
               name="query"
               value="<?php echo htmlspecialchars($queryRaw); ?>"
               placeholder="Tìm sản phẩm..."
               required>
        <button type="button" class="btn-mic" onclick="startVoice()">🎤</button>
        <button type="submit">➤</button>
      </form>
    </div>

    <div class="nav">
      <a href="cart.php">
        🛒 Giỏ hàng (
        <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>
        )
      </a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="admin/ttnguoidung.php" style="color:white; font-size:20px">
          Xin chào, <?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?>
        </a>        <a href="admin/logout.php">Đăng xuất</a>
      <?php else: ?>
        <a href="admin/login.php">Đăng nhập</a>
        <a href="admin/register.php">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="container">
  <h2>Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($queryRaw); ?>"</h2>

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
          echo '<p class="desc">'.(strlen($row['moTa']) > 80
              ? htmlspecialchars(substr($row['moTa'],0,80)).'...'
              : htmlspecialchars($row['moTa'])).'</p>';
          echo '<div class="card-actions">';
          echo '<a class="btn" href="product.php?id='.$row['maSanPham'].'">Xem chi tiết</a>';
          echo '<a class="btn btn-outline" href="cart.php?action=add&id='.$row['maSanPham'].'">Thêm vào giỏ</a>';
          echo '</div>';
          echo '</div>';
      }
  } else {
      echo '<p>Không tìm thấy sản phẩm phù hợp.</p>';
  }
  ?>
  </div>
</main>

<footer class="footer">
  <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
</footer>

<!-- 🎤 VOICE SEARCH: VIỆT + ANH -->
<script>
function startVoice() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Trình duyệt không hỗ trợ tìm kiếm bằng giọng nói");
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = "vi-VN";
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onresult = function(event) {
        let text = event.results[0][0].transcript;

        text = text
            .toLowerCase()
            .replace(/[.,\/#!$%\^&\*;:{}=\-_`~()?"']/g, '')
            .replace(/\s{2,}/g, ' ')
            .trim();

        if (!text) {
            alert("Không nhận diện được từ khóa");
            return;
        }

        document.getElementById("query").value = text;
        document.getElementById("searchForm").submit();
    };

    recognition.onerror = function () {
        alert("Không nhận được giọng nói");
    };

    recognition.start();
}
</script>

</body>
</html>
