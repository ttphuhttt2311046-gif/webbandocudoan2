<?php
session_start();
include "../db.php";
/* ============================================================
   0) CHECK ADMIN
============================================================ */
if (!isset($_SESSION['vaitro']) || $_SESSION['vaitro'] !== 'admin') {
    exit("NO_PERMISSION");
}

/* ============================================================
   1) HIDE PRODUCT -> cập nhật trangThai = 0 (GET)
============================================================ */
if (isset($_GET['hide'])) {
    $id = intval($_GET['hide']);
    $conn->query("UPDATE sanpham SET trangThai=0 WHERE maSanPham=$id");
    exit("OK");
}

/* ============================================================
   2) SHOW PRODUCT -> cập nhật trangThai = 1 (GET)
============================================================ */
if (isset($_GET['show'])) {
    $id = intval($_GET['show']);
    $conn->query("UPDATE sanpham SET trangThai=1 WHERE maSanPham=$id");
    exit("OK");
}

/* ============================================================
   3) DELETE PRODUCT
============================================================ */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM sanpham WHERE maSanPham = $id");
    exit("OK");
}

/* ============================================================
4) LOAD LIST PRODUCTS (POST AJAX)
   - Hiển thị tất cả sản phẩm trừ khi có lọc trạng thái
============================================================ */
if (isset($_POST['list'])) {

    $search = $conn->real_escape_string($_POST['search'] ?? "");
    $danhmuc = intval($_POST['danhmuc'] ?? 0);
    $filter = isset($_POST['tinhtrang']) ? trim($_POST['tinhtrang']) : "";

    // Bắt đầu WHERE
    $where = " WHERE 1 ";

    // Nếu lọc theo danh mục
    if ($danhmuc > 0) {
        $where .= " AND s.maDanhMuc = $danhmuc ";
    }

    // Xử lý filter tình trạng:
    // - nếu filter === '' (Tất cả) -> KHÔNG thêm điều kiện trangThai (hiển thị cả ẩn và hiện)
    // - nếu filter === 'ẩn' -> trangThai = 0
    // - nếu filter === 'hiện' -> trangThai = 1
    // - nếu filter === 'hết hàng' -> soLuong = 0
    // - nếu filter === 'còn hàng' -> soLuong > 0
    if ($filter !== "") {
        if ($filter === "ẩn") {
            $where .= " AND s.trangThai = 0 ";
        } elseif ($filter === "hiện") {
            $where .= " AND s.trangThai = 1 ";
        } elseif ($filter === "hết hàng") {
            $where .= " AND s.soLuong = 0 ";
        } elseif ($filter === "còn hàng") {
            $where .= " AND s.soLuong > 0 ";
        }
    }

    // Nếu tìm kiếm theo tên
    if ($search !== "") {
        $where .= " AND s.tenSanPham LIKE '%$search%' ";
    }

    $sql = "SELECT s.*, d.tenDanhMuc, t.tenNguoiDung
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.maDanhMuc = d.maDanhMuc
            LEFT JOIN taikhoan t ON s.maNguoiBan = t.maTaiKhoan
            $where
            ORDER BY s.maSanPham DESC";

    $res = $conn->query($sql);
    ?>
<style>
.rejected-row {
    background: #ffcccc !important;
}
</style>
    <table>
        <tr>
            <th>ID</th>
            <th>Ảnh</th>
            <th>Tên SP</th>
            <th>Danh mục</th>
            <th>Giá</th>
            <th>Tình trạng</th>
            <th>Trạng thái</th>
            <th>Người bán</th>
            <th>Hành động</th>
        </tr>

        <?php if ($res && $res->num_rows > 0): ?>
            <?php while ($p = $res->fetch_assoc()): ?>
                <?php
                    if (intval($p['soLuong']) <= 0) {
                    $tinhTrang = "<span style='color:red'>Hết hàng</span>";
                    } else {
                    $tinhTrang = "<span style='color:green'>Còn hàng</span>";
                    }
                    $trangThaiLabel = (intval($p['trangThai']) === 1)
                    ? '<span class="badge badge-success">Hiện</span>'
                    : '<span class="badge badge-secondary">Ẩn</span>';
                    $reviewClass = (isset($p['duyetTrangThai']) && intval($p['duyetTrangThai']) === 2)
                    ? "rejected-row"
                    : "";
                ?>
                <tr class="<?= $reviewClass ?>">
                    <td><?= $p['maSanPham'] ?></td>
                    <td>
                        <?php if (!empty($p['hinhAnh'])): ?>
                            <img src="../assets/img/<?= htmlspecialchars($p['hinhAnh']) ?>" width="70" alt="">
                        <?php else: ?>
                            Không ảnh
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['tenSanPham']) ?></td>
                    <td><?= htmlspecialchars($p['tenDanhMuc']) ?></td>
                    <td><?= number_format($p['gia']) ?> VND</td>
                    <td><?= $tinhTrang ?></td>
                    <td><?= $trangThaiLabel ?></td>
                    <td><?= htmlspecialchars($p['tenNguoiDung']) ?></td>
                    <td>
                        <?php if (isset($p['trangThai']) && intval($p['trangThai']) === 0): ?>
                            <button onclick="toggleProduct(<?= $p['maSanPham'] ?>,'hiện')" class="btn btn-show">Hiện</button>
                        <?php else: ?>
                            <button onclick="toggleProduct(<?= $p['maSanPham'] ?>,'ẩn')" class="btn btn-hide">Ẩn</button>
                        <?php endif; ?>
                        <button onclick="deleteProduct(<?= $p['maSanPham'] ?>)" class="btn btn-del">Xóa</button>
                    </td>
                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">Không có sản phẩm nào.</td></tr>
        <?php endif; ?>

    </table>

    <?php
    exit();
}
