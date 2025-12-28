<?php
session_start();
include "db.php";

// Thời gian 20 phút (1200 giây) để tính là phiên mới
/*Hành động	Lượt truy cập
Vào trang lần đầu	                    +1
F5	                                  0
Mở tab mới	                          0
Đóng tab, mở lại ngay	                0
Đóng tab, quay lại sau 20–30 phút	    +1
Đổi máy/đổi trình duyệt	              +1 */
$sessionTimeout = 1200;

if (!isset($_SESSION['last_visit']) || time() - $_SESSION['last_visit'] > $sessionTimeout) {

    // Tăng lượt truy cập
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

    <div class="search-bar">
      <form action="timkiem.php" method="GET">
          <input type="text" name="query" placeholder="Tìm sản phẩm..." required>
          <button type="submit">➤</button>
      </form>
    </div>

    <div class="nav">
      <a href="cart.php">
        Giỏ hàng (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>)
      </a>
      <?php if (isset($_SESSION['user_id'])): ?>
          <?php if (isset($_SESSION['vaitro']) && ($_SESSION['vaitro'] === 'admin')): ?>
              <a href="admin/dashboard.php">Quản lý</a>
              <?php endif; ?>
          <?php if (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'seller'): ?>
              <a href="admin/index.php">Quản lí sản phẩm</a>
              <?php endif; ?>
          <?php if (isset($_SESSION['vaitro']) && $_SESSION['vaitro'] === 'seller'): ?>
              <a href="seller_orders.php">Đơn hàng của tôi</a>
              <?php endif; ?>
          <a href="admin/ttnguoidung.php" style="color:blue;">
              Xin chào, <?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?></a>
              <a href="admin/logout.php">Đăng xuất</a>
              <?php else: ?>
          <a href="admin/login.php">Đăng nhập</a>
          <a href="admin/register.php">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<div class="banner-img1">
  <div class="banner-slider">
    <img src="assets/img/cau-hinh-pc-cho-thiet-ke-do-hoa.jpg" alt="bntren">
    <img src="assets/img/bean.jpg" alt="bntren">
    <img src="assets/img/phan-biet-may-giat-cong-nghiep-va-may-giat-thuong.jpg" alt="bntren">
    <img src="assets/img/banner1.jpg" alt="bntren">
    <img src="assets/img/banner2.png" alt="bntren">
    <img src="assets/img/banner3.jpg" alt="bntren">
    <img src="assets/img/bean.jpg" alt="bntren">
    <img src="assets/img/banner4.jpg" alt="bntren">
    <img src="assets/img/banner5.jpg" alt="bntren">
    <img src="assets/img/banner6.png" alt="bntren">
  </div>
</div>
  <main class="container">
      <!-- 🔹 DANH MỤC SẢN PHẨM -->
   <div class="category-bar">
    <?php
    // Lấy danh mục; kiểm tra kết quả trước khi dùng
    $cats = $conn->query("SELECT maDanhMuc, tenDanhMuc FROM danhmuc ORDER BY tenDanhMuc ASC");

    // "Tất cả" — active khi không có tham số cat
    $allActive = !isset($_GET['cat']) || $_GET['cat'] === '' ? 'active' : '';
    echo '<a href="index.php" class="cat-item all '.$allActive.'">Tất cả</a>';

    if ($cats && $cats->num_rows > 0) {
        while ($cat = $cats->fetch_assoc()) {
            $catId = (int)$cat['maDanhMuc'];
            $active = (isset($_GET['cat']) && intval($_GET['cat']) === $catId) ? 'active' : '';
            echo '<a class="cat-item '.$active.'" href="index.php?cat='.$catId.'">'.htmlspecialchars($cat['tenDanhMuc']).'</a>';
        }
    } else {};

    ?>
  </div>

  <!-- 🔹 DANH SÁCH SẢN PHẨM -->
  <div class="grid">
    <?php include "phantrang.php"; ?>
</div>
</main>
  <!-- 🔻 Banner quảng cáo -->
  <section class="banner-bottom">
    <div class="banner-content">
      <div class="banner-text">
        <h1>Cũ người - mới ta - lãi gấp ba</h1>
        <p>Mua đồ cũ không chỉ là tiết kiệm, mà còn là bảo vệ môi trường 🌱  
        Shop Đồ Cũ cam kết mang đến sản phẩm được kiểm tra kỹ, giá tốt nhất!</p>
      </div>
      <div class="banner-img">
        <img src="assets/img/Banner.jpg" alt="Banner">
      </div>
    </div>
  </section>
  <footer class="footer">
    <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
  </footer>
  <?php if (isset($_SESSION['user_id'])): ?>
<div id="chat-toggle" onclick="toggleChat()">💬</div>
<div id="chat-popup">
    <div id="chat-header">
        <span>💬 Trò chuyện</span>
        <button onclick="toggleChat()">✖</button>
    </div>
    <div id="chat-body">
        <select id="chat-user">
            <option value="">-- Chọn người để chat --</option>
            <?php
            $me = $_SESSION['user_id'];
            $users = $conn->query("SELECT maTaiKhoan, tenDangNhap, vaitro FROM taikhoan WHERE maTaiKhoan <> $me ORDER BY tenDangNhap ASC");
            while ($u = $users->fetch_assoc()) {
                echo "<option value='{$u['maTaiKhoan']}'>{$u['tenDangNhap']} ({$u['vaitro']})</option>";
            }
            ?>
        </select>
        <div id="chat-messages"></div>
        <div id="chat-input">
            <input type="text" id="chat-text" placeholder="Nhập tin nhắn...">
            <button onclick="sendMsg()">Gửi</button>
        </div>
    </div>
</div>
<script>
let chatBox = document.getElementById("chat-popup");
let currentUser = null;

function toggleChat(){
    chatBox.style.display = chatBox.style.display === "block" ? "none" : "block";
}

document.getElementById("chat-user").addEventListener("change", function(){
    currentUser = this.value;
    if (currentUser) loadMessages();
});

function loadMessages(){
    if (!currentUser) return;
    fetch("load_messages.php?to=" + currentUser)
    .then(r => r.text())
    .then(html => {
        document.getElementById("chat-messages").innerHTML = html;
        let cm = document.getElementById("chat-messages");
        cm.scrollTop = cm.scrollHeight;
    });
}

function sendMsg(){
    if (!currentUser) return alert("Chọn người cần chat!");
    let msg = document.getElementById("chat-text").value.trim();
    if (msg === "") return;
    fetch("send_message.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "receiver_id=" + encodeURIComponent(currentUser) + "&message=" + encodeURIComponent(msg)
    })
    .then(r=>r.json())
    .then(j=>{
        if (j.success){
            document.getElementById("chat-text").value="";
            loadMessages();
        } else alert("Lỗi: "+j.error);
    });
}

setInterval(() => { if (currentUser) loadMessages(); }, 3000);
setInterval(checkNewMessages, 3000);

function checkNewMessages(){
    fetch("check_new_messages.php")
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        
        // Xóa chấm cũ
        document.querySelectorAll("#chat-user option").forEach(o => {
            o.textContent = o.textContent.replace(" 🔴", "");
        });

        // Đánh dấu người gửi có tin mới
        data.unread.forEach(uid => {
            let opt = document.querySelector(`#chat-user option[value='${uid}']`);
            if (opt) opt.textContent = opt.textContent.replace(" 🔴", "") + " 🔴";
        });
        // 🧩 Đưa người có tin nhắn mới lên đầu danh sách
        const select = document.getElementById("chat-user");
        data.unread.slice().reverse().forEach(uid => {
        const opt = select.querySelector(`option[value='${uid}']`);
        if (opt) {
        // Di chuyển option này lên ngay sau option đầu tiên (mục "-- Chọn người để chat --")
        select.insertBefore(opt, select.options[1]);
    }
});
        // Hiện chấm đỏ ở biểu tượng 💬 nếu có tin mới
        const dotId = "chat-dot";
        let dot = document.getElementById(dotId);
        if (data.unread.length > 0 && chatBox.style.display !== "block") {
            if (!dot) {
                dot = document.createElement("div");
                dot.id = dotId;
                dot.style.position = "absolute";
                dot.style.top = "6px";
                dot.style.right = "6px";
                dot.style.width = "10px";
                dot.style.height = "10px";
                dot.style.background = "red";
                dot.style.borderRadius = "50%";
                dot.style.border = "2px solid white";
                document.getElementById("chat-toggle").appendChild(dot);
            }
        } else if (dot) {
            dot.remove();
        }
    });
}
</script>
<?php endif; ?>
</body>
</html>
