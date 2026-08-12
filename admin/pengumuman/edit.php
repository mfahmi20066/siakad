<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Pengumuman";

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// 1. Ambil data pengumuman secara mandiri tanpa JOIN awal agar aman dari fatal error kolom
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengumuman WHERE id = '$id'"));

// 2. Deteksi dinamis nama pembuat pengumuman berdasarkan kolom relasi user yang tersedia
$admin_name = 'Administrator';
if ($data) {
    $id_u = '';
    if (isset($data['id_user'])) { $id_u = $data['id_user']; }
    elseif (isset($data['user_id'])) { $id_u = $data['user_id']; }
    elseif (isset($data['admin_id'])) { $id_u = $data['admin_id']; }

    if (!empty($id_u)) {
        $q_user = mysqli_query($koneksi, "SELECT nama FROM users WHERE id = '$id_u'");
        if ($q_user && $u = mysqli_fetch_assoc($q_user)) {
            $admin_name = $u['nama'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $isi   = mysqli_real_escape_string($koneksi, $_POST['isi']);
    $tgl   = $_POST['tanggal'];

    mysqli_query($koneksi,
        "UPDATE pengumuman
         SET judul='$judul', isi='$isi', tanggal='$tgl'
         WHERE id='$id'");

    header("Location: index.php?success=Pengumuman berhasil diupdate");
    exit();
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Pengumuman</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">Form Edit Pengumuman</div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-8">

                        <div class="mb-3">
                            <label class="form-label">Judul Pengumuman</label>
                            <input type="text" name="judul" class="form-control"
                                   value="<?= e($data['judul'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Isi Pengumuman</label>
                            <textarea name="isi" class="form-control" rows="8"
                                      required><?= e($data['isi'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control"
                                   value="<?= e($data['tanggal'] ?? '') ?>" required>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary">
                            <small class="text-muted">Dibuat oleh</small>
                            <div class="fw-bold">
                                <i class="fas fa-user-shield text-danger"></i>
                                <?= e($admin_name) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>