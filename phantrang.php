<?php
if (!isset($conn)) {
    include "db.php";
}

/* ===== CẤU HÌNH ===== */
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/* ===== BỘ LỌC ===== */
$where_sql = " WHERE trangThai = 1 AND duyetTrangThai = 1 ";
$queryStringBase = "";

if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $catId = intval($_GET['cat']);
    $where_sql .= " AND maDanhMuc = $catId ";
    $queryStringBase .= "cat=$catId&";
}

/* ===== TỔNG SẢN PHẨM ===== */
$total_sql = "SELECT COUNT(*) AS total FROM sanpham $where_sql";
$total_res = $conn->query($total_sql);
$total_row = $total_res->fetch_assoc();
$totalItems = intval($total_row['total']);
$totalPages = max(1, ceil($totalItems / $limit));

/* ===== LẤY SẢN PHẨM ===== */
$sql = "SELECT maSanPham, tenSanPham, moTa, gia, hinhAnh
        FROM sanpham
        $where_sql
        ORDER BY maSanPham DESC
        LIMIT $limit OFFSET $offset";

$res = $conn->query($sql);

/* ===== HIỂN THỊ ===== */
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        $img = 'assets/img/' . ($row['hinhAnh'] ? $row['hinhAnh'] : 'placeholder.png');

        echo '<div class="card" onclick="location.href=\'product.php?id='.$row['maSanPham'].'\'">';

        echo '<div class="thumb">';
        echo '<img src="'.$img.'" alt="'.htmlspecialchars($row['tenSanPham']).'">';
        echo '<div class="tet-icon">🎉 XUÂN 2026</div>'; // ICON TẾT
        echo '</div>';

        echo '<div class="meta">';
        echo '<div class="title">'.htmlspecialchars($row['tenSanPham']).'</div>';
        echo '<div class="price">'.number_format($row['gia'],0,',','.').' VND</div>';
        echo '</div>';

        echo '<p class="desc">';
        echo strlen($row['moTa']) > 80
            ? htmlspecialchars(substr($row['moTa'],0,80)).'...'
            : htmlspecialchars($row['moTa']);
        echo '</p>';

        echo '<div class="card-actions">';
        echo '<a class="btn" href="product.php?id='.$row['maSanPham'].'">Xem chi tiết</a>';
        echo '<a class="btn btn-outline" href="cart.php?action=add&id='.$row['maSanPham'].'">Thêm vào giỏ</a>';
        echo '</div>';

        echo '</div>';
    }
} else {
    echo '<p>Hiện chưa có sản phẩm.</p>';
}

/* ===== PHÂN TRANG ===== */
if ($totalPages > 1) {
    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $page) ? 'active' : '';
        echo '<a class="'.$active.'" href="index.php?'.$queryStringBase.'page='.$i.'">'.$i.'</a>';
    }
    echo '</div>';
}
?>
