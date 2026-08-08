<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Pengaturan Akademik";

// Tahun ajaran aktif dari master (source of truth); tahun_pelajaran di sini = mirror.
$taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taTahun = ''; }

// Proses simpan data saat form diklik
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tahun pelajaran = mirror master; user tidak bisa mengubah jadi sumber berbeda.
    $tapel = $taTahun;
    $sem   = mysqli_real_escape_string($koneksi, $_POST['semester']);

    $update = mysqli_query($koneksi, "UPDATE akademik_setting SET tahun_pelajaran='$tapel', semester='$sem' WHERE id=1");
    if ($update) {
        $success = "Pengaturan akademik berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
}

// Ambil data yang sedang aktif saat ini
$data_akademik = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM akademik_setting WHERE id=1"));
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-user-cog text-gold me-2"></i>Pengaturan Tahun & Semester</h4>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card-header">Form Update Tahun Ajaran & Semester aktif</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tahun Pelajaran</label>
                    <input type="text" name="tahun_pelajaran" class="form-control" value="<?= htmlspecialchars($taTahun) ?>" readonly>
                            <small class="text-muted">Mengikuti tahun ajaran aktif (master). Tidak dapat diubah manual.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Semester Aktif</label>
                    <select name="semester" class="form-select" required>
                        <option value="1 (Ganjil)" <?= $data_akademik['semester'] == '1 (Ganjil)' ? 'selected' : '' ?>>1 (Ganjil)</option>
                        <option value="2 (Genap)" <?= $data_akademik['semester'] == '2 (Genap)' ? 'selected' : '' ?>>2 (Genap)</option>
                    </select>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>