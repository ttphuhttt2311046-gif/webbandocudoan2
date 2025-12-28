<?php
// chat.php
session_start();



require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$me = (int)$_SESSION['user_id'];

// danh sách user khác
$users = $conn->query("SELECT user_id, username, display_name FROM users WHERE user_id <> $me ORDER BY username ASC");

// nhận receiver_id từ query param
$receiver_id = isset($_GET['to']) ? (int)$_GET['to'] : null;

// nếu receiver tồn tại, lấy tên hiển thị
$receiver_name = '';
if ($receiver_id) {
    $stmt = $conn->prepare("SELECT display_name, username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $rres = $stmt->get_result();
    if ($rrow = $rres->fetch_assoc()) {
        $receiver_name = $rrow['display_name'] ?: $rrow['username'];
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
    float: right;
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
    float: left;
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
<p>Xin chào <b><?= e($_SESSION['display_name']) ?></b> — <a href="logout.php">Đăng xuất</a></p>
<div class="container">
    <div class="users">
        <h4>Người dùng</h4>
        <?php while ($u = $users->fetch_assoc()): ?>
            <div>
                <a href="chat.php?to=<?= $u['user_id'] ?>"><?= e($u['display_name'] ?: $u['username']) ?></a>
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
// Hàm tải tin nhắn
function loadMessages() {
    const receiver = <?= $receiver_id ? $receiver_id : 'null' ?>;
    if (!receiver) return;
    fetch('load_messages.php?to=' + receiver)
      .then(r => r.text())
      .then(html => {
          const cw = document.getElementById('chat-window');
          cw.innerHTML = html;
          cw.scrollTop = cw.scrollHeight;
      });
}

// gửi tin nhắn bằng AJAX
const form = document.getElementById('send-form');
if (form) {
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const data = new FormData(form);
        fetch('send_message.php', { method: 'POST', body: data })
          .then(r => r.json())
          .then(j => {
              if (j.success) {
                  document.getElementById('message-input').value = '';
                  loadMessages();
              } else {
                  alert('Lỗi gửi: ' + j.error);
              }
          });
    });
    // tự động load mỗi 2s
    loadMessages();
    setInterval(loadMessages, 2000);
}
</script>
</body>
</html>
