<?php
session_start();
include "db.php";

/* ĐẾM LƯỢT TRUY CẬP */
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
        🛒 Giỏ hàng (
        <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>
        )
      </a>

      <?php if (isset($_SESSION['user_id'])): ?>

        <?php if ($_SESSION['vaitro'] === 'admin'): ?>
          <a href="admin/dashboard.php">Quản lý</a>
        <?php endif; ?>

        <?php if ($_SESSION['vaitro'] === 'seller'): ?>
          <a href="admin/index.php">Quản lí sản phẩm</a>
          <a href="seller_orders.php">Đơn hàng của tôi</a>
        <?php endif; ?>

        <a href="admin/ttnguoidung.php" style="color:white;">
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
$allActive = !isset($_GET['cat']) ? 'active' : '';
echo '<a href="index.php" class="cat-item all '.$allActive.'">Tất cả</a>';

if ($cats && $cats->num_rows > 0) {
    while ($cat = $cats->fetch_assoc()) {
        $catId = (int)$cat['maDanhMuc'];
        $active = (isset($_GET['cat']) && intval($_GET['cat']) === $catId) ? 'active' : '';
        echo '<a class="cat-item '.$active.'" href="index.php?cat='.$catId.'">'
            .htmlspecialchars($cat['tenDanhMuc']).'</a>';
    }
}
?>
</div>
  <?php include "phantrang.php"; ?>
</main>

<footer class="footer">
  <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
</footer>

<!-- 🎤 VOICE SEARCH (ĐÃ FIX LỖI DẤU . , KÝ TỰ LẠ) -->
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

        // 🔥 LÀM SẠCH CHUỖI GIỌNG NÓI
        text = text
            .toLowerCase()
            .replace(/[.,\/#!$%\^&\*;:{}=\-_`~()?"']/g, '')
            .replace(/\s{2,}/g, ' ')
            .trim();

        if (text.length === 0) {
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
    fetch("msg_load.php?to=" + currentUser)
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
    fetch("msg_send.php", {
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
