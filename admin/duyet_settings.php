<?php
include "../db.php";

/* ============================================================
   1) LƯU CÀI ĐẶT (AJAX POST)
   - Lưu auto_review & banned_words
   - Nếu auto = 1 → tự động duyệt ngay các SP đang chờ duyệt
============================================================ */
if (isset($_POST["save_settings"])) {

    $auto = intval($_POST['auto_review'] ?? 0);
    $words = trim($_POST['banned_words'] ?? "");

    // Lưu vào DB
    $stmt = $conn->prepare("UPDATE duyet_settings SET auto_review=?, banned_words=? WHERE id=1");
    $stmt->bind_param("is", $auto, $words);
    $stmt->execute();

    /* ---- AUTO DUYỆT SP ĐANG CHỜ ---- */
    if ($auto == 1) {

        // Xử lý danh sách từ cấm
        $bws = array_filter(array_map('trim', explode(",", strtolower($words))));

        // Lấy các sản phẩm đang chờ
        $list = $conn->query("SELECT maSanPham, tenSanPham, moTa 
                              FROM sanpham 
                              WHERE duyetTrangThai = 0");

        while ($p = $list->fetch_assoc()) {
            $ten = strtolower($p['tenSanPham']);
            $moTa = strtolower($p['moTa']);

            $found = false;
            foreach ($bws as $w) {
                if ($w !== "" && (str_contains($ten, $w) || str_contains($moTa, $w))) {
                    $found = true;
                    break;
                }
            }

            // Không có từ cấm → tự duyệt
            if (!$found) {
                $id = $p['maSanPham'];
                $conn->query("UPDATE sanpham SET duyetTrangThai = 1 WHERE maSanPham = $id");
            }
        }
    }

    echo "saved";
    exit;
}

/* ============================================================
   2) DUYỆT THỦ CÔNG (AJAX)
============================================================ */
if (isset($_POST["update_review"])) {

    $id = intval($_POST['id']);
    $status = intval($_POST['status']); // 1: duyệt, 2: từ chối

    $stmt = $conn->prepare("UPDATE sanpham SET duyetTrangThai=? WHERE maSanPham=?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();

    echo "updated";
    exit;
}

/* ============================================================
   3) LẤY CÀI ĐẶT HIỆN TẠI
============================================================ */
$q = $conn->query("SELECT * FROM duyet_settings LIMIT 1");
$settings = $q->fetch_assoc();

$auto_review = $settings["auto_review"];
$banned_words = $settings["banned_words"];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Cài đặt xét duyệt sản phẩm</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">

<style>
.switch {
    position: relative;
    width: 55px;
    height: 28px;
    display: inline-block;
}
.switch input {display:none;}
.slider {
    position: absolute;
    cursor: pointer;
    top:0; left:0; right:0; bottom:0;
    background: #ccc;
    transition: .4s;
    border-radius: 28px;
}
.slider:before {
    content:"";
    position: absolute;
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background:white;
    transition:.4s;
    border-radius:50%;
}
input:checked + .slider {
    background:#28a745;
}
input:checked + .slider:before {
    transform:translateX(26px);
}
.pending-card {
    border-left: 5px solid orange;
}
</style>

</head>
<body class="p-4">

<!-- =============================
     CÀI ĐẶT DUYỆT
=================================-->
<div class="card p-3 mb-4">
    <h3 class="mb-3">⚙️ Cài đặt xét duyệt sản phẩm</h3>

    <!-- Nút bật -->
    <label class="form-label me-2">Tự động duyệt</label>
    <label class="switch">
        <input type="checkbox" id="auto_review" <?= $auto_review ? "checked" : "" ?>>
        <span class="slider"></span>
    </label>

    <div class="mt-3">
        <label>Các từ bị cấm (cách nhau bằng dấu phẩy)</label>
        <textarea id="banned_words" class="form-control" rows="3"><?= $banned_words ?></textarea>
    </div>

    <button class="btn btn-primary mt-3" onclick="saveSettings()">Lưu cài đặt</button>
    <div id="save_msg" class="text-success fw-bold mt-2"></div>
</div>

<!-- =============================
     DUYỆT THỦ CÔNG (auto off)
=================================-->
<div id="manual_section" style="<?= $auto_review ? 'display:none;' : '' ?>">

    <h4 class="mb-3">📝 Duyệt sản phẩm thủ công</h4>

    <?php
    $list = $conn->query("SELECT * FROM sanpham WHERE duyetTrangThai = 0 ORDER BY maSanPham DESC");

    if ($list->num_rows == 0) {
        echo "<p>Không có sản phẩm chờ duyệt.</p>";
    } else {
        while ($p = $list->fetch_assoc()) {
            ?>
            <div class="card p-3 mb-3 pending-card">
                <h5><?= htmlspecialchars($p['tenSanPham']) ?></h5>
                <p><?= nl2br(htmlspecialchars($p['moTa'])) ?></p>

                <button class="btn btn-success me-2" onclick="updateReview(this, <?= $p['maSanPham'] ?>,1)">Đồng ý</button>
<button class="btn btn-danger" onclick="updateReview(this, <?= $p['maSanPham'] ?>,2)">Từ chối</button>

            </div>
    <?php   }
    } ?>
</div>

<script>
/* ===============================
   LƯU CÀI ĐẶT AJAX
================================*/
function saveSettings() {
    const auto_review = document.getElementById("auto_review").checked ? 1 : 0;
    const words = document.getElementById("banned_words").value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "duyet_settings.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function() {
        document.getElementById("save_msg").innerHTML = "Đã lưu!";
        document.getElementById("manual_section").style.display = (auto_review == 1 ? "none" : "block");
    }

    xhr.send("save_settings=1&auto_review=" + auto_review + "&banned_words=" + encodeURIComponent(words));
}

/* ===============================
   CẬP NHẬT DUYỆT AJAX
================================*/
function updateReview(btn, id, status) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "duyet_settings.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function() {
        const card = btn.closest(".card");
        card.style.opacity = "0.3";
        setTimeout(() => card.remove(), 200);
    };

    xhr.send("update_review=1&id=" + id + "&status=" + status);
}

</script>

</body>
</html>
