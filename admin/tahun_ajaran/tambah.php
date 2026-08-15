<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tambah Tahun Ajaran";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ta = trim((string)($_POST['nama_tahun_ajaran'] ?? ''));
    $mulai   = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;

    // validasi format yyyy/yyyy
    if (!preg_match('/^\d{4}\/\d{4}$/', $nama_ta)) {
        $error = "Format tahun ajaran salah. Gunakan format YYYY/YYYY, contoh: 2027/2028.";
    } elseif ($mulai && $selesai && strtotime($mulai) > strtotime($selesai)) {
        $error = "Tanggal mulai tidak boleh lebih lambat dari tanggal selesai.";
    } else {
        $pdo = tahun_ajaran_pdo();
        try {
            // cek duplikat
            $cek = $pdo->prepare('SELECT id FROM tahun_ajaran WHERE nama_tahun_ajaran = :nama LIMIT 1');
            $cek->execute(['nama' => $nama_ta]);
            if ($cek->fetch()) {
                $error = "Tahun ajaran '$nama_ta' sudah ada. Tidak boleh duplikat.";
            } else {
                $pdo->beginTransaction();
                try {
                    $ins = $pdo->prepare(
                        "INSERT INTO tahun_ajaran (nama_tahun_ajaran, status, tanggal_mulai, tanggal_selesai)
                         VALUES (:nama, 'nonaktif', :mulai, :selesai)"
                    );
                    $ins->execute([
                        ':nama'   => $nama_ta,
                        ':mulai'  => $mulai,
                        ':selesai' => $selesai,
                    ]);
                    $taId = (int)$pdo->lastInsertId();

                    // auto-generate semester ganjil & genap (tanpa duplikat)
                    foreach (['Ganjil', 'Genap'] as $sems) {
                        $s = $pdo->prepare(
                            'INSERT INTO semester (tahun_ajaran_id, nama, status) VALUES (:ta, :nama, :status)
                             ON DUPLICATE KEY UPDATE id = id'
                        );
                        $s->execute([':ta' => $taId, ':nama' => $sems, ':status' => 'aktif']);
                    }

                    $pdo->commit();
                    header('Location: index.php?success=' . urlencode("Tahun ajaran '$nama_ta' berhasil ditambahkan beserta semester Ganjil & Genap."));
                    exit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = "Gagal menyimpan tahun ajaran (transaksi). (" . $e->getMessage() . ")";
                }
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
        <h4><i class="fas fa-calendar-plus text-icon me-2"></i>Tambah Tahun Ajaran</h4>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 640px;">
        <div class="card-header">
            <i class="fas fa-calendar-plus"></i> Form Tambah Tahun Ajaran
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Tahun Ajaran</label>
                    <input type="text" name="nama_tahun_ajaran" class="form-control"
                           placeholder="Contoh: 2027/2028" value="<?= e($_POST['nama_tahun_ajaran'] ?? '') ?>" required>
                    <div class="form-text">Format <code>YYYY/YYYY</code>. Status default <strong>nonaktif</strong>. Semester Ganjil &amp; Genap dibuat otomatis.</div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control"
                               value="<?= e($_POST['tanggal_mulai'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                               value="<?= e($_POST['tanggal_selesai'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>