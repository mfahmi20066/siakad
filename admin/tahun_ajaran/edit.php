<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Edit Tahun Ajaran";

$pdo = tahun_ajaran_pdo();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = getTahunAjaranById($pdo, $id);

if (!$data) {
    header("Location: index.php?error=" . urlencode("Data tahun ajaran tidak ditemukan"));
    exit();
}

$semesters = getSemestersByTahunAjaran($pdo, $id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ta = trim((string)($_POST['nama_tahun_ajaran'] ?? ''));
    $mulai   = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

    if (!preg_match('/^\d{4}\/\d{4}$/', $nama_ta)) {
        $error = "Format tahun ajaran salah. Gunakan YYYY/YYYY.";
    } elseif ($mulai && $selesai && strtotime($mulai) > strtotime($selesai)) {
        $error = "Tanggal mulai tidak boleh lebih lambat dari tanggal selesai.";
    } else {
        try {
            // Cek duplikat pada tahun lain
            $cek = $pdo->prepare('SELECT id FROM tahun_ajaran WHERE nama_tahun_ajaran = :nama AND id <> :id LIMIT 1');
            $cek->execute([':nama' => $nama_ta, ':id' => $id]);
            if ($cek->fetch()) {
                $error = "Tahun ajaran '$nama_ta' sudah digunakan oleh tahun ajaran lain.";
            } else {
                $upd = $pdo->prepare(
                    'UPDATE tahun_ajaran SET nama_tahun_ajaran = :nama, tanggal_mulai = :mulai, tanggal_selesai = :selesai WHERE id = :id'
                );
                $upd->execute([':nama'=>$nama_ta, ':mulai'=>$mulai, ':selesai'=>$selesai, ':id'=>$id]);

                if ($data['status'] === 'aktif' && !empty($nama_ta) && $nama_ta !== $data['nama_tahun_ajaran']) {
                    $pdo->prepare("UPDATE pengaturan SET tahun_pelajaran = :ta WHERE id = 1")->execute([':ta' => $nama_ta]);
                }
                header("Location: index.php?success=" . urlencode("Tahun ajaran berhasil diperbarui"));
                exit();
            }
        } catch (Throwable $e) {
            $error = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-calendar-alt text-icon me-2"></i>Edit Tahun Ajaran</h4>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 640px;">
        <div class="card-header">
            <i class="fas fa-calendar-alt"></i> Form Edit Tahun Ajaran
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Semester (dikelola dari tabel semester)</label>
                <div>
                    <?php foreach ($semesters as $s): ?>
                        <span class="badge bg-<?= ($s['status'] == 'aktif') ? 'success' : 'secondary' ?>"><?= e($s['nama']) ?></span>
                    <?php endforeach; ?>
                    <?php if (!$semesters): ?><span class="text-muted">belum ada semester</span><?php endif; ?>
                </div>
            </div>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Tahun Ajaran</label>
                    <input type="text" name="nama_tahun_ajaran" class="form-control"
                           value="<?= e($data['nama_tahun_ajaran']) ?>" required>
                    <div class="form-text">Format <code>YYYY/YYYY</code>.</div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control"
                               value="<?= e($data['tanggal_mulai'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                               value="<?= e($data['tanggal_selesai'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="index.php" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left"></i> Kembali</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>