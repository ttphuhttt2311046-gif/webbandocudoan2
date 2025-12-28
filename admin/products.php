<?php
session_start();
include "../db.php";

$vaitro = $_SESSION['vaitro'] ?? 'user';

if ($vaitro !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/stylekhac.css" rel="stylesheet">
</head>
<body id="page-top">

<div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Quản lý sản phẩm</h1>

                <div id="productSection">
                    <input type="text" id="search" placeholder="Tìm theo tên..." 
                           style="padding: 6px; width: 200px;">

                    <select id="filterDanhMuc" style="padding:5px;">
                        <option value="">Tất cả danh mục</option>
                        <?php
                        $dm = $conn->query("SELECT * FROM danhmuc ORDER BY tenDanhMuc ASC");
                        while ($row = $dm->fetch_assoc()):
                        ?>
                            <option value="<?= $row['maDanhMuc'] ?>"><?= $row['tenDanhMuc'] ?></option>
                        <?php endwhile; ?>
                    </select>

                    <select id="filterTinhTrang" style="padding:5px;">
                        <option value="">Tất cả tình trạng</option>
                        <option value="hiện">Hiện</option>
                        <option value="ẩn">Ẩn</option>
                        <option value="còn hàng">Còn hàng</option>
                        <option value="hết hàng">Hết hàng</option>
                    </select>

                    <div id="productList"></div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function loadProducts() {
    $.post("products_manager.php", {
        list: 1,
        search: $("#search").val(),
        danhmuc: $("#filterDanhMuc").val(),
        tinhtrang: $("#filterTinhTrang").val()
    }, function (data) {
        $("#productList").html(data);
    });
}

/* =========== ẨN / HIỆN =========== */
function toggleProduct(id, tt) {
    $.get("products_manager.php", tt === "hiện" ? {show: id} : {hide: id}, function(res){
        if (res === "OK") loadProducts();
    });
}

/* =========== XÓA =========== */
function deleteProduct(id) {
    if (!confirm("Bạn có chắc muốn xóa sản phẩm?")) return;

    $.get("products_manager.php", {delete: id}, function(res){
        if (res === "OK") loadProducts();
    });
}

/* Tự động load khi mở trang */
$("#search, #filterDanhMuc, #filterTinhTrang").on("input change", loadProducts);
loadProducts();
</script>

</body>
</html>
