<?php 
// Cart: thêm, cập nhật (AJAX), xóa, checkout (lưu donhang + chitietdonhang, gửi thông báo người bán)
ini_set('session.cookie_path', '/');
session_start();
include "db.php";

$action = isset($_GET['action']) ? $_GET['action'] : '';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// =========================
// THÊM SẢN PHẨM VÀO GIỎ
// =========================
if ($action === 'add') {
    $id = intval($_GET['id']);
    $qty = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;

    $stmt = $conn->prepare("SELECT maSanPham, tenSanPham, gia, hinhAnh, soLuong FROM sanpham WHERE maSanPham = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $p = $res->fetch_assoc();

    if ($p) {
        $soLuongTon = intval($p['soLuong']);
        if ($soLuongTon <= 0) {
            echo "<script>alert('Sản phẩm đã hết hàng!'); window.location='index.php';</script>";
            exit;
        }

        $currentQty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['qty'] : 0;
        $newQty = $currentQty + $qty;
        if ($newQty > $soLuongTon) {
            echo "<script>alert('Số lượng vượt quá hàng tồn! Còn lại {$soLuongTon} sản phẩm.'); window.location='product.php?id={$id}';</script>";
            exit;
        }

        $_SESSION['cart'][$id] = [
            'id' => $p['maSanPham'],
            'name' => $p['tenSanPham'],
            'price' => $p['gia'],
            'image' => $p['hinhAnh'],
            'qty' => $newQty
        ];
        header("Location: cart.php");
        exit;
    } else {
        echo "<script>alert('Không tìm thấy sản phẩm!'); window.location='index.php';</script>";
        exit;
    }
}

// =========================
// XÓA SẢN PHẨM KHỎI GIỎ
// =========================
if ($action === 'remove') {
    $id = intval($_GET['id']);
    if (isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit;
}

// =========================
// CẬP NHẬT GIỎ HÀNG QUA AJAX (từ CODE MỚI)
// =========================
if ($action === 'update-ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $qty = max(1, intval($_POST['qty'] ?? 1));

    // Kiểm tra tồn kho
    $stmt = $conn->prepare("SELECT soLuong FROM sanpham WHERE maSanPham=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $p = $res->fetch_assoc();
    $stmt->close();

    if ($p) {
        $soLuongTon = intval($p['soLuong']);
        if ($qty > $soLuongTon) {
            // Nếu số lượng vượt tồn, giới hạn về tồn kho
            $qty = $soLuongTon;
        }
        if (isset($_SESSION['cart'][$id])) {
            // Nếu tồn kho bằng 0, xóa khỏi giỏ
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id]['qty'] = $qty;
            }
        }
    }
    // Trả về mã 200 (không cần content)
    http_response_code(200);
    exit;
}

// =========================
// THANH TOÁN - LƯU ĐƠN HÀNG + CHI TIẾT (giữ từ CODE CŨ, bao gồm notify seller)
// =========================
if ($action === 'checkout') {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Vui lòng đăng nhập trước khi thanh toán!'); window.location='admin/login.php';</script>";
        exit;
    }

    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Giỏ hàng trống!'); window.location='index.php';</script>";
        exit;
    }

    // Tính tổng
    $tongTien = 0;
    foreach ($_SESSION['cart'] as $item) {
        $tongTien += $item['price'] * $item['qty'];
    }

    $maNguoiMua = intval($_SESSION['user_id']);
    $ngayDat = date('Y-m-d');
    $trangThai = "Chờ xử lý";

    // Begin transaction - KHuyến nghị (nếu MySQL hỗ trợ InnoDB)
    $conn->begin_transaction();

    try {
        // Thêm vào bảng donhang
        $stmt = $conn->prepare("INSERT INTO donhang (ngayDat, tongTien, trangThai, maNguoiMua) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $ngayDat, $tongTien, $trangThai, $maNguoiMua);

        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi lưu đơn hàng: " . $stmt->error);
        }
        $maDonHang = $conn->insert_id;
        $stmt->close();

        // Thêm chi tiết đơn hàng, trừ tồn, gửi thông báo
        foreach ($_SESSION['cart'] as $item) {
            $id = intval($item['id']);
            $qty = intval($item['qty']);
            $gia = floatval($item['price']);

            // Lấy thông tin sản phẩm (cập nhật lại tồn kho và lấy seller)
            $seller_stmt = $conn->prepare("SELECT maNguoiBan, soLuong FROM sanpham WHERE maSanPham = ? FOR UPDATE");
            $seller_stmt->bind_param("i", $id);
            $seller_stmt->execute();
            $prod_info = $seller_stmt->get_result()->fetch_assoc();
            $seller_stmt->close();

            if (!$prod_info) {
                throw new Exception("Sản phẩm #{$id} không tồn tại.");
            }

            $maNguoiBan = intval($prod_info['maNguoiBan'] ?? 0);
            $soLuongTon = intval($prod_info['soLuong']);

            if ($qty > $soLuongTon) {
                throw new Exception("Số lượng sản phẩm #{$id} vượt quá tồn kho (còn {$soLuongTon}).");
            }

            // Lưu chi tiết đơn hàng có người bán
            $sql = $conn->prepare("
                INSERT INTO chitietdonhang (maDonHang, maSanPham, maNguoiBan, soLuong, donGia)
                VALUES (?, ?, ?, ?, ?)
            ");
            $sql->bind_param("iiiid", $maDonHang, $id, $maNguoiBan, $qty, $gia);
            if (!$sql->execute()) {
                $sql->close();
                throw new Exception("Lỗi lưu chi tiết đơn hàng: " . $sql->error);
            }
            $sql->close();

            // Giảm hàng tồn
            $update = $conn->prepare("UPDATE sanpham SET soLuong = soLuong - ? WHERE maSanPham = ?");
            $update->bind_param("ii", $qty, $id);
            if (!$update->execute()) {
                $update->close();
                throw new Exception("Lỗi cập nhật tồn kho: " . $update->error);
            }
            $update->close();

            // Gửi thông báo tới người bán (thông báo chung)
            $thongbao = $conn->prepare("INSERT INTO nhantin (noiDung, maNguoiGui, maNguoiNhan, trangThai) VALUES (?, ?, ?, 'chua_xem')");
            $noiDung = "Bạn có đơn hàng mới #" . $maDonHang;
            $maNguoiGui = $maNguoiMua;
            $thongbao->bind_param("sii", $noiDung, $maNguoiGui, $maNguoiBan);
            if (!$thongbao->execute()) {
                $thongbao->close();
                throw new Exception("Lỗi gửi thông báo: " . $thongbao->error);
            }
            $thongbao->close();

            // Gửi tin nhắn chi tiết từng sản phẩm
            $nguoiMua = $_SESSION['tenNguoiDung'] ?? 'Khách hàng';
            $noiDungCT = "
            📦 <b>{$nguoiMua}</b> vừa đặt <b>{$qty}</b> sản phẩm <b>“{$item['name']}”</b><br>
            💰 Đơn giá: " . number_format($gia, 0, ',', '.') . "₫<br>
            🧮 Tổng: <b>" . number_format($qty * $gia, 0, ',', '.') . "₫</b><br>
            🧾 Mã đơn: #{$maDonHang}
            ";
            $chitiet = $conn->prepare("
                INSERT INTO nhantin (noiDung, maNguoiGui, maNguoiNhan, trangThai)
                VALUES (?, ?, ?, 'chua_xem')
            ");
            $chitiet->bind_param("sii", $noiDungCT, $maNguoiGui, $maNguoiBan);
            if (!$chitiet->execute()) {
                $chitiet->close();
                throw new Exception("Lỗi gửi tin nhắn chi tiết: " . $chitiet->error);
            }
            $chitiet->close();
        }

        // Commit transaction
        $conn->commit();

        // Xóa giỏ hàng
        $_SESSION['cart'] = [];

        echo "<script>alert('Đặt hàng thành công! Đơn hàng và chi tiết đã được lưu.'); window.location='index.php';</script>";
        exit;
    } catch (Exception $e) {
        // Rollback nếu lỗi
        $conn->rollback();
        // Hiện lỗi (có thể thay bằng log)
        $err = htmlspecialchars($e->getMessage());
        echo "<script>alert('Lỗi khi lưu đơn hàng: {$err}'); window.location='cart.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Giỏ hàng</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <header class="topbar">
    <div class="container">
      <h1><a href="index.php">Shop Đồ Cũ</a></h1>
      <div class="nav">
        <a href="cart.php">Giỏ hàng (<?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0; ?>)</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (isset($_SESSION['vaitro']) && ($_SESSION['vaitro'] === 'seller' || $_SESSION['vaitro'] === 'admin')): ?>
                <a href="admin/index.php">Quản lý sản phẩm</a>
            <?php endif; ?>
            <span>Xin chào, <?php echo htmlspecialchars($_SESSION['tenNguoiDung'] ?? ''); ?></span>
            <a href="admin/logout.php">Đăng xuất</a>
        <?php else: ?>
            <a href="admin/login.php">Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
    <h2>Giỏ hàng của bạn</h2>

    <?php if (!empty($_SESSION['cart'])): ?>
      <!-- form giữ lại để fallback (submit truyền thống) -->
      <table class="cart-table">
  <thead>
    <tr>
      <th>Ảnh</th>
      <th>Sản phẩm</th>
      <th>Giá</th>
      <th>Số lượng</th>
      <th>Tạm tính</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php
    $total = 0;
    foreach ($_SESSION['cart'] as $id => $item):
      $sub = $item['price'] * $item['qty'];
      $total += $sub;
  ?>
    <tr>
      <td><img src="assets/img/<?php echo htmlspecialchars($item['image']); ?>" width="80"></td>
      <td><?php echo htmlspecialchars($item['name']); ?></td>
      <td class="price"><?php echo number_format($item['price'],0,',','.'); ?> VND</td>

      <td>
        <input type="number"
               class="qty-input"
               data-id="<?php echo $id; ?>"
               value="<?php echo $item['qty']; ?>"
               min="1"
               style="width:70px;">
      </td>

      <td class="sub-total"><?php echo number_format($sub,0,',','.'); ?> VND</td>

      <td>
        <a class="btn btn-outline" href="#" onclick="return confirmDelete(<?php echo $id; ?>)">Xóa</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="cart-summary">
  <strong>Tổng cộng: <?php echo number_format($total,0,',','.'); ?> VND</strong>
</div>

<div class="cart-actions">
  <a class="btn btn-outline" href="#" onclick="return confirmCheckout()">Thanh toán</a>
</div>

<script>
// JS tự động cập nhật số lượng và tổng tiền (từ CODE MỚI)
// Option A: ô số lượng có min=1, xoá bằng nút Xóa
document.addEventListener('DOMContentLoaded', function(){
    function sanitizeNumberFromText(str){
        return parseFloat(str.replace(/\D/g,'')) || 0;
    }

    const qtyInputs = document.querySelectorAll('.qty-input');
    qtyInputs.forEach(input => {
        input.addEventListener('input', function(){
            let id = this.dataset.id;
            let qty = parseInt(this.value);
            if (!Number.isFinite(qty) || qty < 1) qty = 1;
            this.value = qty;

            let tr = this.closest('tr');
            let price = sanitizeNumberFromText(tr.querySelector('.price').textContent);
            tr.querySelector('.sub-total').textContent = (price * qty).toLocaleString('vi-VN') + ' VND';

            let total = 0;
            document.querySelectorAll('.sub-total').forEach(td => {
                total += sanitizeNumberFromText(td.textContent);
            });
            document.querySelector('.cart-summary strong').textContent = 'Tổng cộng: ' + total.toLocaleString('vi-VN') + ' VND';

            // gửi AJAX cập nhật session (non-blocking)
            fetch('cart.php?action=update-ajax', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id) + '&qty=' + encodeURIComponent(qty)
            }).then(response => {
                // có thể check response.status nếu muốn hiển thị lỗi
                if (!response.ok) {
                    console.error('Update-ajax lỗi', response.status);
                }
            }).catch(err => {
                console.error('Lỗi fetch update-ajax', err);
            });
        });

        // optional: gửi event blur để đảm bảo cập nhật khi người dùng rời ô nhập
        input.addEventListener('blur', function(){
            if (this.value === '' || parseInt(this.value) < 1) {
                this.value = 1;
                this.dispatchEvent(new Event('input'));
            }
        });
    });
});

// SweetAlert confirm delete
function confirmDelete(id) {
    Swal.fire({
        title: 'Bạn chắc chắn?',
        text: "Bạn có thực sự muốn xóa sản phẩm này khỏi giỏ hàng?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart.php?action=remove&id=' + id;
        }
    });
    return false;
}
function confirmCheckout() {
    Swal.fire({
        title: 'Xác nhận thanh toán?',
        text: "Bạn có chắc chắn muốn thanh toán đơn hàng này?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Thanh toán',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart.php?action=checkout';
        }
    });
    return false; // Ngăn href mặc định
}
</script>

    <?php else: ?>
      <p>Giỏ hàng trống. <a href="index.php">Quay lại cửa hàng</a></p>
    <?php endif; ?>
  </main>

  <footer class="footer">
    <div class="container">© <?php echo date("Y"); ?> Shop Đồ Cũ</div>
  </footer>
</body>
</html>
