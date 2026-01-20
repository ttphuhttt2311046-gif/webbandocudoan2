<?php
session_start();
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$me = (int)$_SESSION['user_id'];

// danh sách user khác
$users = $conn->query("SELECT maTaiKhoan, tenDangNhap, tenNguoiDung FROM taikhoan WHERE maTaiKhoan <> $me ORDER BY tenDangNhap ASC");

// nhận receiver_id từ query param
$receiver_id = isset($_GET['to']) ? (int)$_GET['to'] : null;

// nếu receiver tồn tại, lấy tên hiển thị
$receiver_name = '';
if ($receiver_id) {
    $stmt = $conn->prepare("SELECT tenNguoiDung, tenDangNhap FROM taikhoan WHERE maTaiKhoan = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $rres = $stmt->get_result();
    if ($rrow = $rres->fetch_assoc()) {
        $receiver_name = $rrow['tenDangNhap'] ?: $rrow['tenNguoiDung'];
    } else {
        $receiver_id = null;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Chat</title>
<style>
.container { display:flex; gap:20px; padding:20px; font-family:Arial; }
.users { width:200px; border:1px solid #ccc; padding:10px; }
.chat { flex:1; border:1px solid #ccc; padding:10px; display:flex; flex-direction:column; }
.chat-window { flex:1; overflow-y:auto; padding:10px; border:1px solid #eee; margin-bottom:10px; height:400px; }
.msg-me {
    text-align: right;
    background: #DCF8C6;
    padding: 6px 10px;
    margin: 6px 0;
    border-radius: 10px 0 10px 10px;
    display: inline-block;
    clear: both;
    max-width: 70%;
}
.msg-other {
    text-align: left;
    background: #FFF;
    border: 1px solid #ddd;
    padding: 6px 10px;
    margin: 6px 0;
    border-radius: 0 10px 10px 10px;
    display: inline-block;
    clear: both;
    max-width: 70%;
}
.time {
    font-size: 0.8em;
    color: gray;
    display: block;
}

</style>
</head>
<body>
<p>Xin chào <b><?= e($_SESSION['tenNguoiDung']) ?></b>
<div class="container">
    <div class="users">
        <h4>Người dùng</h4>
        <?php while ($u = $users->fetch_assoc()): ?>
            <div>
                <a href="messages.php?to=<?= $u['maTaiKhoan'] ?>"><?= e($u['tenNguoiDung'] ?: $u['tenDangNhap']) ?></a>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="chat">
        <h4>Trò chuyện với: <?= $receiver_id ? e($receiver_name) : '<em>Chưa chọn</em>' ?></h4>

        <div id="chat-window" class="chat-window">
            <?php if (!$receiver_id): ?>
                <p>Chọn người để bắt đầu chat.</p>
            <?php endif; ?>
            <!-- messages sẽ được load bằng AJAX -->
        </div>

        <?php if ($receiver_id): ?>
        <form id="send-form">
            <input type="hidden" name="receiver_id" value="<?= $receiver_id ?>">
            <input id="message-input" name="message" placeholder="Nhập tin nhắn..." style="width:80%;" required>
            <button type="submit">Gửi</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
const receiverId = <?= $receiver_id ? $receiver_id : 'null' ?>;
const chatWindow = document.getElementById("chat-window");
const form = document.getElementById("send-form");
const input = document.getElementById("message-input");

/* LOAD TIN NHẮN */
function loadMessages() {
    if (!receiverId) return;

    fetch("msg_load.php?to=" + receiverId)
        .then(r => r.text())
        .then(html => {
            chatWindow.innerHTML = html;
            chatWindow.scrollTop = chatWindow.scrollHeight;
        });
}

/* GỬI TIN */
function sendMsg(){
    const msg = input.value.trim();
    if (!msg) return;

    fetch("msg_send.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:
          "receiver_id=" + encodeURIComponent(receiverId) +
          "&message=" + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(j => {
        if (j.success) {
            input.value = "";
            loadMessages();
        } else {
            alert("Lỗi gửi: " + j.error);
        }
    })
    .catch(() => alert("Không gửi được tin nhắn"));
}

/* SUBMIT */
if (form) {
    form.addEventListener("submit", function(e){
        e.preventDefault();
        sendMsg();
    });
}

/* ENTER = GỬI */
if (input) {
    input.addEventListener("keydown", function(e){
        if (e.key === "Enter") {
            e.preventDefault();
            sendMsg();
        }
    });
}

/* AUTO LOAD */
if (receiverId) {
    loadMessages();
    setInterval(loadMessages, 3000);
}
</script>
</body>
</html>
