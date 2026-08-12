<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Absensi";

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT a.*, s.nama, s.nis, k.nama_kelas
         FROM absensi a
         JOIN siswa s ON a.siswa_id = s.id
         JOIN kelas k ON a.kelas_id = k.id
         WHERE a.id = '$id'"));

if (!$data) {
    header("Location: index.php?error=Data absensi tidak ditemukan");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $st  = $_POST['status'];
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    mysqli_query($koneksi,
        "UPDATE absensi SET status='$st', keterangan='$ket' WHERE id='$id'");

    header("Location: index.php?success=Absensi berhasil diupdate");
    exit();
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Absensi</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">Form Edit Absensi</div>
        <div class="card-body">

            <!-- Info (read-only) -->
            <div class="alert alert-secondary mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Siswa</small>
                        <div class="fw-bold"><?= e($data['nama']) ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">NIS</small>
                        <div class="fw-bold"><?= $data['nis'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= $data['nama_kelas'] ?></div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Tanggal</small>
                        <div class="fw-bold">
                            <?= tanggal_indo($data['tanggal'], true) ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status Kehadiran</label>
                            <select name="status" class="form-select">
                                <?php foreach (['Hadir','Sakit','Izin','Alpa'] as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= $data['status'] == $s ? 'selected' : '' ?>>
                                    <?= $s ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"
                                      placeholder="Keterangan tambahan (opsional)">
                                <?= e($data['keterangan'] ?? '') ?>
                            </textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>