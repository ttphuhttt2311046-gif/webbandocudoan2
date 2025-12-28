<?php
session_start();
include "../db.php";

// Kiểm tra quyền admin
if (!isset($_SESSION['vaitro']) || $_SESSION['vaitro'] !== 'admin') {
    exit("NO_PERMISSION");
}

// ==================
//  XÓA NGƯỜI DÙNG
// ==================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM taikhoan WHERE maTaiKhoan = $id");
    exit("OK");
}
// ==================
//  ĐẶT LẠI MẬT KHẨU
// ==================
if (isset($_GET['resetpass'])) {

    $id = intval($_GET['resetpass']);

    // mật khẩu mặc định 123
    $newPass = password_hash("123", PASSWORD_DEFAULT);

    $conn->query("UPDATE taikhoan SET matKhau = '$newPass' WHERE maTaiKhoan = $id");

    exit("OK");
}
// ==================
//  FORM EDIT
// ==================
if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);
    $user = $conn->query("SELECT * FROM taikhoan WHERE maTaiKhoan = $id")->fetch_assoc();

    if (!$user) exit("USER_NOT_FOUND");
    ?>

    <div class="container">
        <form id="editForm">
            <input type="hidden" name="id" value="<?= $user['maTaiKhoan'] ?>">

            <label>Tên người dùng:</label>
            <input class="form-control" name="tenNguoiDung"
                   value="<?= htmlspecialchars($user['tenNguoiDung']) ?>">

            <label class="mt-2">Email:</label>
            <input class="form-control" name="tenDangNhap"
                   value="<?= htmlspecialchars($user['tenDangNhap']) ?>">

            <label class="mt-2">Vai trò:</label>
            <select class="form-control" name="vaitro">
                <option value="admin" <?= $user['vaitro']=='admin'?'selected':'' ?>>Admin</option>
                <option value="seller" <?= $user['vaitro']=='seller'?'selected':'' ?>>Người bán</option>
                <option value="buyer" <?= $user['vaitro']=='buyer'?'selected':'' ?>>Người mua</option>
            </select>

            <label class="mt-2">Số điện thoại:</label>
            <input class="form-control" name="soDienThoai"
                   value="<?= htmlspecialchars($user['soDienThoai']) ?>">

            <label class="mt-2">Địa chỉ:</label>
            <input class="form-control" name="diaChi"
                   value="<?= htmlspecialchars($user['diaChi']) ?>">

            <button class="btn btn-success mt-3">Cập nhật</button>
            <button type="button" class="btn btn-warning mt-3 ml-2"
            onclick="resetPassword(<?= $user['maTaiKhoan'] ?>)">Đặt mật khẩu mặc định</button>
        </form>
    </div>

    <script>
    $("#editForm").submit(function(e){
        e.preventDefault();

        $.post("users_manager.php", 
            $("#editForm").serialize() + "&update=1",
            function(res){
                if(res === "OK"){
                    alert("Cập nhật thành công!");
                    loadPage("users.php");
                } else {
                    alert("Có lỗi xảy ra!");
                }
            }
        );
    });
    </script>
    <script>
function resetPassword(id){
    if(confirm("Bạn có chắc muốn đặt lại mật khẩu về 123?")){
        $.get("users_manager.php?resetpass=" + id, function(res){
            if(res === "OK"){
                alert("Đã đặt lại mật khẩu về 123!");
            }
        });
    }
}

$("#editForm").submit(function(e){
    e.preventDefault();

    $.post("users_manager.php", 
        $("#editForm").serialize() + "&update=1",
        function(res){
            if(res === "OK"){
                alert("Cập nhật thành công!");
                loadPage("users.php");
            } else {
                alert("Có lỗi xảy ra!");
            }
        }
    );
});
</script>
<?php } ?>
