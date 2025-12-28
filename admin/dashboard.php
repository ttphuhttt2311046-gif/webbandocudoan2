<?php
session_start();
include "../db.php";

// Kiểm tra quyền admin
$user_id = $_SESSION['user_id'] ?? 0;
$vaitro = $_SESSION['vaitro'] ?? 'user';

if ($vaitro !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Tổng doanh thu & số đơn hàng toàn hệ thống
$sql = "SELECT SUM(tongTien) AS tongDoanhThu, COUNT(*) AS soDonHang 
        FROM donhang 
        WHERE trangThai = 'Hoàn thành'";
$res = $conn->query($sql);
$total = $res ? $res->fetch_assoc() : ['tongDoanhThu'=>0,'soDonHang'=>0];

// Doanh thu hôm nay
$sqlToday = "SELECT SUM(tongTien) AS tongDoanhThu, COUNT(*) AS soDonHang 
             FROM donhang 
             WHERE DATE(ngayDat)=CURDATE()
             AND trangThai = 'Hoàn thành'";
$resToday = $conn->query($sqlToday);
$today = $resToday ? $resToday->fetch_assoc() : ['tongDoanhThu'=>0,'soDonHang'=>0];

// Doanh thu tháng này
$sqlMonth = "SELECT SUM(tongTien) AS tongDoanhThu, COUNT(*) AS soDonHang 
             FROM donhang 
             WHERE YEAR(ngayDat)=YEAR(CURDATE()) 
               AND MONTH(ngayDat)=MONTH(CURDATE())
               AND trangThai = 'Hoàn thành'";
$resMonth = $conn->query($sqlMonth);
$month = $resMonth ? $resMonth->fetch_assoc() : ['tongDoanhThu'=>0,'soDonHang'=>0];

// Doanh thu năm nay
$sqlYear = "SELECT SUM(tongTien) AS tongDoanhThu, COUNT(*) AS soDonHang 
            FROM donhang 
            WHERE YEAR(ngayDat)=YEAR(CURDATE())
              AND trangThai = 'hoàn thành'";
$resYear = $conn->query($sqlYear);
$year = $resYear ? $resYear->fetch_assoc() : ['tongDoanhThu'=>0,'soDonHang'=>0];

// Lượt truy cập
$trafficQuery = $conn->query("SELECT total FROM counter WHERE id = 1");
$totalTraffic = intval($trafficQuery->fetch_assoc()['total']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Dashboard Admin</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../index.php">
            <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-laugh-wink"></i></div>
            <div class="sidebar-brand-text mx-3">Admin Panel</div>
        </a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item active"><a class="nav-link" href="dashboard.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Trang Tổng quan</span></a></li>
        <hr class="sidebar-divider">
        <li class="nav-item"><a class="nav-link load-page" data-page="users.php" href="#"><i class="fas fa-fw fa-table"></i><span>Quản lý người dùng</span></a></li>
        <hr class="sidebar-divider d-none d-md-block">
        <li class="nav-item"><a class="nav-link load-page" data-page="duyet_settings.php" href="#"><i class="fas fa-fw fa-table"></i><span>Duyệt sản phẩm</span></a></li>
        <hr class="sidebar-divider d-none d-md-block">
        <li class="nav-item"><a class="nav-link load-page" data-page="products.php" href="#"><i class="fas fa-fw fa-table"></i><span>Quản lý sản phẩm</span></a></li>
        <hr class="sidebar-divider d-none d-md-block">
</a>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['tenNguoiDung']); ?></span>
                            <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                            <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div id="ajax-content" class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Trang Tổng Quan</h1>
                    <div class="row">
                    <?php 
                    $cards = [
                        ['title'=>'Tổng Doanh Thu','value'=>$total['tongDoanhThu'],'icon'=>'dollar-sign','color'=>'primary','count'=>$total['soDonHang']],
                        ['title'=>'Hôm Nay','value'=>$today['tongDoanhThu'],'icon'=>'calendar-day','color'=>'info','count'=>$today['soDonHang']],
                        ['title'=>'Tháng Này','value'=>$month['tongDoanhThu'],'icon'=>'calendar-alt','color'=>'warning','count'=>$month['soDonHang']],
                        ['title'=>'Năm Nay','value'=>$year['tongDoanhThu'],'icon'=>'calendar','color'=>'secondary','count'=>$year['soDonHang']],
                        ['title'=>'Lượt truy cập','value'=>$totalTraffic,'icon'=>'eye','color'=>'success','count'=>''],
                    ];
                    foreach($cards as $card): ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?php echo $card['color']; ?> shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?php echo $card['color']; ?> text-uppercase mb-1"><?php echo $card['title']; ?></div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($card['value'] ?? 0, 0, ',', '.');
                                              if ($card['title'] !== 'Lượt truy cập') {echo " VND";}?></div>
                                            <?php if ($card['title'] !== 'Lượt truy cập'): ?>
                                            <div class="text-xs text-gray-500">Số đơn: <?php echo $card['count']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-<?php echo $card['icon']; ?> fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto text-center">
                <span>Copyright &copy; Bán đồ cũ</span>
            </div>
        </footer>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>
<script>
$(document).on("click", ".load-page", function(e) {
    e.preventDefault();

    let page = $(this).data("page");

    $("#ajax-content").html(`
        <div class="text-center p-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Đang tải...</p>
        </div>
    `);

    $.ajax({
        url: page,
        type: "GET",
        success: function(data) {
            $("#ajax-content").html(data);
        },
        error: function(xhr, status, error) {
            $("#ajax-content").html(`
                <div class="alert alert-danger">
                    Không tải được trang: ${page}<br>Lỗi: ${error}
                </div>
            `);
        }
    });
});
</script>
</body>
</html>
