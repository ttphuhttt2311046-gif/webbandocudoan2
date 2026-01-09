<?php
if (!isset($conn)) {
    include "db.php";
}

/* CẤU HÌNH */
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/* FILTER */
$where_sql = " WHERE trangThai = 1 AND duyetTrangThai = 1 ";
$queryStringBase = "";

if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $catId = intval($_GET['cat']);
    $where_sql .= " AND maDanhMuc = $catId ";
    $queryStringBase .= "cat=$catId&";
}

/* TOTAL */
$total_sql = "SELECT COUNT(*) AS total FROM sanpham $where_sql";
$total_res = $conn->query($total_sql);
$total_row = $total_res->fetch_assoc();
$totalItems = intval($total_row['total']);
$totalPages = max(1, ceil($totalItems / $limit));

/* QUERY */
$sql = "SELECT maSanPham, tenSanPham, moTa, gia, hinhAnh
        FROM sanpham
        $where_sql
        ORDER BY maSanPham DESC
        LIMIT $limit OFFSET $offset";

$res = $conn->query($sql);

/* ========== GRID ========== */
echo '<div class="grid">';

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $img = 'assets/img/' . ($row['hinhAnh'] ?: 'placeholder.png');

        echo '<div class="card">';
        echo '<img src="'.$img.'">';
        echo '<div class="title">'.$row['tenSanPham'].'</div>';
        echo '<div class="price">'.number_format($row['gia'],0,',','.').' VND</div>';
        echo '<p class="desc">'.mb_strimwidth($row['moTa'],0,80,'...').'</p>';

        echo '<div class="card-actions">';
        echo '<a href="product.php?id='.$row['maSanPham'].'">Xem chi tiết</a>';
        echo '<a href="cart.php?action=add&id='.$row['maSanPham'].'">Thêm vào giỏ</a>';
        echo '</div>';

        echo '</div>';
    }
} else {
    echo '<p>Chưa có sản phẩm</p>';
}

echo '</div>'; // END GRID

/* ========== PAGINATION ========== */
if ($totalPages > 1) {
    echo '<div class="pagination">';

    // << về trang đầu
    if ($page > 1) {
        echo '<a class="page-first" href="?'.$queryStringBase.'page=1"><<</a>';
        echo '<a class="page-prev" href="?'.$queryStringBase.'page='.($page-1).'"><</a>';
    }

    // số trang
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $page) ? 'active' : '';
        echo '<a class="page-link '.$active.'" href="?'.$queryStringBase.'page='.$i.'">'.$i.'</a>';
    }

    // > sau 1 trang, >> cuối trang
    if ($page < $totalPages) {
        echo '<a class="page-next" href="?'.$queryStringBase.'page='.($page+1).'">></a>';
        echo '<a class="page-last" href="?'.$queryStringBase.'page='.$totalPages.'">>></a>';
    }

    echo '</div>';
}
?>
