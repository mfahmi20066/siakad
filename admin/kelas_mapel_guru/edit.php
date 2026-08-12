<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Penugasan Mapel";

$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT kmg.*, k.nama_kelas, mp.nama_mapel, mp.kode_mapel
     FROM kelas_mapel_guru kmg
     JOIN kelas k ON k.id = kmg.kelas_id
     JOIN mata_pelajaran mp ON mp.id = kmg.mapel_id
     WHERE kmg.id = $id"));

if (!$data) {
    header("Location: index.php?error=Penugasan tidak ditemukan");
    exit();
}

$guru_list = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gid = (int) ($_POST['guru_id'] ?? 0);
    $kkm = (int) ($_POST['kkm'] ?? 75);
    $jam = (int) ($_POST['jam_per_minggu'] ?? 2);

    if ($gid <= 0) {
        $error = "Guru pengampu wajib dipilih.";
    } elseif ($kkm < 0 || $kkm > 100) {
        $error = "KKM harus antara 0 - 100.";
    } elseif ($jam < 1 || $jam > 40) {
        $error = "Jam per minggu harus antara 1 - 40.";
    } else {
        $q = mysqli_query($koneksi,
            "UPDATE kelas_mapel_guru SET guru_id=$gid, kkm=$kkm, jam_per_minggu=$jam WHERE id=$id");
        if ($q) {
            header("Location: index.php?kelas_id={$data['kelas_id']}&success=Penugasan berhasil diperbarui");
            exit();
        } else {
            $error = "Gagal memperbarui: " . mysqli_error($koneksi);
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Penugasan Mapel</h4>
        <a href="index.php?kelas_id=<?= $data['kelas_id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Penugasan: <?= e($data['nama_mapel']) ?> di <?= e($data['nama_kelas']) ?></div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <input type="text" class="form-control" value="<?= e($data['nama_mapel']) ?> (<?= e($data['kode_mapel']) ?>)" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <input type="text" class="form-control" value="<?= e($data['nama_kelas']) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Guru Pengampu <span class="text-danger">*</span></label>
                            <select name="guru_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>" <?= $data['guru_id'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['nama'] ?? $g['nama_lengkap'] ?? '-') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">KKM</label>
                                    <input type="number" name="kkm" class="form-control" value="<?= (int) $data['kkm'] ?>" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jam/Minggu</label>
                                    <input type="number" name="jam_per_minggu" class="form-control" value="<?= (int) $data['jam_per_minggu'] ?>" min="1" max="40">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                <a href="index.php?kelas_id=<?= $data['kelas_id'] ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
