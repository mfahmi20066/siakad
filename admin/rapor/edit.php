<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Rapor";

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// â”€â”€ Cek kolom yang ada di tabel rapor â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$cols_res = mysqli_query($koneksi, "SHOW COLUMNS FROM rapor");
$cols = [];
while ($c = mysqli_fetch_assoc($cols_res)) $cols[] = $c['Field'];

// Tentukan nama kolom catatan yang benar
$col_catatan = in_array('catatan_wali', $cols) ? 'catatan_wali'
             : (in_array('catatan', $cols) ? 'catatan' : 'catatan_wali');

// Tentukan nama kolom status yang benar
$col_status = in_array('status', $cols) ? 'status' : 'status';

// â”€â”€ Ambil data rapor dengan LEFT JOIN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT r.*,
            COALESCE(s.nama_lengkap, s.nama, '') AS nama_siswa,
            COALESCE(s.nis, '') AS nis,
            COALESCE(k.nama_kelas, '') AS nama_kelas
     FROM rapor r
     LEFT JOIN siswa s ON r.siswa_id = s.id
     LEFT JOIN kelas k ON r.kelas_id = k.id
     WHERE r.id = '$id'"));

if (!$data) {
    header("Location: index.php?error=Data rapor tidak ditemukan");
    exit();
}

// â”€â”€ Proses simpan â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cat = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $st  = mysqli_real_escape_string($koneksi, $_POST['status'] ?? 'draft');
    $kerajinan = mysqli_real_escape_string($koneksi, $_POST['kerajinan'] ?? 'Baik');
    $kelakuan  = mysqli_real_escape_string($koneksi, $_POST['kelakuan'] ?? 'Baik');
    $kerapihan = mysqli_real_escape_string($koneksi, $_POST['kerapihan'] ?? 'Baik');
    $eskul     = mysqli_real_escape_string($koneksi, $_POST['ekstrakurikuler'] ?? '');

    $ada_kolom_kepribadian = in_array('kerajinan', $cols);

    // Update pakai nama kolom yang benar
    if ($ada_kolom_kepribadian) {
        $q = mysqli_query($koneksi,
            "UPDATE rapor 
             SET $col_catatan='$cat', $col_status='$st',
                 kerajinan='$kerajinan', kelakuan='$kelakuan',
                 kerapihan='$kerapihan', ekstrakurikuler='$eskul'
             WHERE id='$id'");
    } else {
        $q = mysqli_query($koneksi,
            "UPDATE rapor SET $col_catatan='$cat', $col_status='$st' WHERE id='$id'");
    }

    if ($q) {
        header("Location: index.php?success=" . urlencode("Rapor berhasil diupdate"));
    } else {
        header("Location: index.php?error=" . urlencode("Gagal update: " . mysqli_error($koneksi)));
    }
    exit();
}

// Nilai catatan saat ini
$catatan_saat_ini = $data[$col_catatan] ?? $data['catatan_wali'] ?? $data['catatan'] ?? '';
$status_saat_ini  = $data[$col_status] ?? 'draft';
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Rapor</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">Form Edit Rapor</div>
        <div class="card-body">

            <!-- Info Siswa -->
            <div class="alert alert-secondary mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Siswa</small>
                        <div class="fw-bold"><?= e($data['nama_siswa'] ?: 'Tidak Diketahui') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">NIS</small>
                        <div class="fw-bold"><?= e($data['nis'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= e($data['nama_kelas'] ?: '-') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Semester</small>
                        <div class="fw-bold">Semester <?= e($data['semester'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Tahun Ajaran</small>
                        <div class="fw-bold"><?= e($data['tahun_ajaran'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Catatan Wali Kelas</label>
                            <textarea name="catatan" class="form-control" rows="6"
                                      placeholder="Masukkan catatan wali kelas untuk siswa ini..."><?= e($catatan_saat_ini) ?></textarea>
                        </div>

                        <label class="form-label fw-bold">Kepribadian</label>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted">Kerajinan</label>
                                <select name="kerajinan" class="form-select">
                                    <?php $krj = $data['kerajinan'] ?? 'Baik'; foreach (['Baik','Cukup','Kurang'] as $o): ?>
                                    <option value="<?= $o ?>" <?= $krj == $o ? 'selected' : '' ?>><?= $o ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted">Kelakuan</label>
                                <select name="kelakuan" class="form-select">
                                    <?php $klk = $data['kelakuan'] ?? 'Baik'; foreach (['Baik','Cukup','Kurang'] as $o): ?>
                                    <option value="<?= $o ?>" <?= $klk == $o ? 'selected' : '' ?>><?= $o ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small text-muted">Kerapihan</label>
                                <select name="kerapihan" class="form-select">
                                    <?php $krp = $data['kerapihan'] ?? 'Baik'; foreach (['Baik','Cukup','Kurang'] as $o): ?>
                                    <option value="<?= $o ?>" <?= $krp == $o ? 'selected' : '' ?>><?= $o ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kegiatan Pengembangan Diri / Ekstrakurikuler</label>
                            <input type="text" name="ekstrakurikuler" class="form-control"
                                   placeholder="Contoh: Pramuka, PMR, dsb"
                                   value="<?= e($data['ekstrakurikuler'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status Rapor</label>
                            <select name="status" class="form-select">
                                <option value="draft"  <?= $status_saat_ini=='draft' ?'selected':'' ?>>Draft</option>
                                <option value="final"  <?= $status_saat_ini=='final' ?'selected':'' ?>>Final / Selesai</option>
                                <option value="aktif"  <?= $status_saat_ini=='aktif' ?'selected':'' ?>>Aktif</option>
                                <option value="naik"   <?= $status_saat_ini=='naik' ?'selected':'' ?>>Naik Kelas</option>
                                <option value="tinggal"<?= $status_saat_ini=='tinggal'?'selected':'' ?>>Tinggal Kelas</option>
                            </select>
                        </div>

                        <!-- Info kolom database -->
                        <div class="alert alert-info small">
                            <i class="fas fa-database me-1"></i>
                            Kolom catatan: <code><?= $col_catatan ?></code><br>
                            Kolom status: <code><?= $col_status ?></code>
                        </div>

                        <a href="cetak.php?id=<?= $id ?>" target="_blank"
                           class="btn btn-info w-100 mt-2">
                            <i class="fas fa-print"></i> Preview Cetak
                        </a>
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