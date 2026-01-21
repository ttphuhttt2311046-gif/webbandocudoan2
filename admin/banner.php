<?php
session_start();
include "../db.php";

/* ======================
   AJAX HANDLER
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // THÊM BANNER
    if (isset($_POST['them_banner'])) {

        $uploadDir = __DIR__ . "/../assets/uploads/banner/";
        $dbPath    = "assets/uploads/banner/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!isset($_FILES['hinh']) || $_FILES['hinh']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status'=>'error','msg'=>'Upload lỗi']); exit;
        }

        $filename = time().'_'.basename($_FILES['hinh']['name']);
        $fullPath = $uploadDir.$filename;
        $savePath = $dbPath.$filename;

        if (!move_uploaded_file($_FILES['hinh']['tmp_name'], $fullPath)) {
            echo json_encode(['status'=>'error','msg'=>'Không lưu file']); exit;
        }

        $link   = trim($_POST['link'] ?? '');
        $thu_tu = intval($_POST['thu_tu'] ?? 0);

        $stmt = $conn->prepare(
            "INSERT INTO banner (hinh, link, thu_tu, trangthai)
             VALUES (?, ?, ?, 1)"
        );
        $stmt->bind_param("ssi", $savePath, $link, $thu_tu);
        $stmt->execute();

        echo json_encode([
            'status'=>'ok',
            'id'=>$stmt->insert_id,
            'hinh'=>$savePath,
            'link'=>$link,
            'thu_tu'=>$thu_tu
        ]);
        exit;
    }

    // XÓA BANNER
    if (isset($_POST['xoa'])) {
        $id = intval($_POST['xoa']);

        $res = $conn->query("SELECT hinh FROM banner WHERE id=$id");
        if ($row = $res->fetch_assoc()) {
            $file = __DIR__ . "/../" . $row['hinh'];
            if (file_exists($file)) unlink($file);
        }

        $conn->query("DELETE FROM banner WHERE id=$id");
        echo json_encode(['status'=>'ok','id'=>$id]);
        exit;
    }

    echo json_encode(['status'=>'invalid']);
    exit;
}

/* ======================
   LẤY DANH SÁCH
====================== */
$banners = $conn->query("SELECT * FROM banner ORDER BY thu_tu ASC, id DESC");
?>

<h4 class="mb-3">Quản lý Banner</h4>

<form id="formBanner" enctype="multipart/form-data" class="card p-3 mb-4">
    <input type="hidden" name="them_banner" value="1">
    <input type="file" name="hinh" class="form-control mb-2" required>
    <input type="text" name="link" class="form-control mb-2" placeholder="Link">
    <input type="number" name="thu_tu" class="form-control mb-2" value="0">
    <button class="btn btn-primary">Thêm banner</button>
</form>

<table class="table table-bordered align-middle" id="tableBanner">
<thead>
<tr>
    <th>Hình</th>
    <th>Link</th>
    <th>Thứ tự</th>
    <th width="80">Xóa</th>
</tr>
</thead>
<tbody>
<?php while($b = $banners->fetch_assoc()): ?>
<tr id="banner-<?= $b['id'] ?>">
    <td><img src="../<?= $b['hinh'] ?>" style="height:60px"></td>
    <td><?= htmlspecialchars($b['link']) ?></td>
    <td><?= $b['thu_tu'] ?></td>
    <td class="text-center">
        <button class="btn btn-danger btn-sm"
                onclick="xoaBanner(<?= $b['id'] ?>)">
            Xóa
        </button>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
document.getElementById('formBanner').addEventListener('submit', function(e){
    e.preventDefault();
    fetch('banner.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.status!=='ok') return alert(d.msg);

        const tr = document.createElement('tr');
        tr.id = 'banner-'+d.id;
        tr.innerHTML = `
            <td><img src="../${d.hinh}" style="height:60px"></td>
            <td>${d.link}</td>
            <td>${d.thu_tu}</td>
            <td class="text-center">
                <button class="btn btn-danger btn-sm"
                        onclick="xoaBanner(${d.id})">Xóa</button>
            </td>
        `;
        document.querySelector('#tableBanner tbody').prepend(tr);
        this.reset();
    });
});

function xoaBanner(id){
    if(!confirm('Xóa banner này?')) return;

    const fd = new FormData();
    fd.append('xoa', id);

    fetch('banner.php', {
        method: 'POST',
        body: fd
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='ok'){
            const row = document.getElementById('banner-'+d.id);
            if(row) row.remove();
        }
    });
}
</script>
