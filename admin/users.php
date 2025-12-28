<?php 
session_start();
include "../db.php";
// Kiểm tra vai trò admin
if (!isset($_SESSION['vaitro']) || $_SESSION['vaitro'] !== 'admin') {
    die("Bạn không có quyền truy cập trang này");
}
// Khóa tài khoản
if (isset($_GET['lock'])) {
    $id = intval($_GET['lock']);
    $conn->query("UPDATE taikhoan SET trangThai = 0 WHERE maTaiKhoan = $id");
    exit("OK");
}
// Mở khóa tài khoản
if (isset($_GET['unlock'])) {
    $id = intval($_GET['unlock']);
    $conn->query("UPDATE taikhoan SET trangThai = 1 WHERE maTaiKhoan = $id");
    exit("OK");
}
// Lấy danh sách user
$users = $conn->query("SELECT * FROM taikhoan ORDER BY maTaiKhoan DESC");
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Quản lý người dùng</h1>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Trạng thái</th>
                <th>Tên người dùng</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>SĐT</th>
                <th>Địa chỉ</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $u['maTaiKhoan'] ?></td>
                <td>
                    <?= $u['trangThai'] == 1 
                        ? '<span class="badge badge-success">Hoạt động</span>' 
                        : '<span class="badge badge-danger">Bị khóa</span>' 
                    ?>
                </td>
                <td><?= htmlspecialchars($u['tenNguoiDung']) ?></td>
                <td><?= htmlspecialchars($u['tenDangNhap']) ?></td>
                <td><?= $u['vaitro'] ?></td>
                <td><?= htmlspecialchars($u['soDienThoai']) ?></td>
                <td><?= htmlspecialchars($u['diaChi']) ?></td>
                <td>
                    <button class="btn btn-danger btn-sm"
                    onclick="if(confirm('Xóa người dùng?')) manageUser('delete', <?= $u['maTaiKhoan'] ?>)">Xóa</button>
                    <?php if ($u['trangThai'] == 1): ?>
                        <button class="btn btn-warning btn-sm"
                            onclick="if(confirm('Khóa tài khoản này?')) toggleUser(<?= $u['maTaiKhoan'] ?>,'lock')">
                            Khóa
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm"
                            onclick="if(confirm('Mở khóa tài khoản này?')) toggleUser(<?= $u['maTaiKhoan'] ?>,'unlock')">
                            Mở khóa
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
function loadPage(page){
    $("#ajax-content").load(page);
}

function toggleUser(id, action){
    $.get("users.php?" + action + "=" + id, function(res){
        if(res === "OK"){
            loadPage("users.php");
        }
    });
}
function manageUser(action, id){
    $.get("users_manager.php?" + action + "=" + id, function(res){
        if(res === "OK"){
            loadPage("users.php");
        }
    });
}

</script>
