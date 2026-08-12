<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tambah Penugasan Mapel";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int) $taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) {}

$kelas_list  = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
$mapel_list  = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");
$guru_list   = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

$preset_kelas = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kid  = (int) ($_POST['kelas_id'] ?? 0);
    $mid  = (int) ($_POST['mapel_id'] ?? 0);
    $gid  = (int) ($_POST['guru_id'] ?? 0);
    $kkm  = (int) ($_POST['kkm'] ?? 75);
    $jam  = (int) ($_POST['jam_per_minggu'] ?? 2);

    if ($taId === null) {
        $error = "Tidak ada tahun ajaran aktif.";
    } elseif ($kid <= 0 || $mid <= 0 || $gid <= 0) {
        $error = "Kelas, mapel, dan guru wajib dipilih.";
    } elseif ($kkm < 0 || $kkm > 100) {
        $error = "KKM harus antara 0 - 100.";
    } elseif ($jam < 1 || $jam > 40) {
        $error = "Jam per minggu harus antara 1 - 40.";
    } else {
        // Cek duplikat: satu guru per (kelas + mapel + TA)
        $cek = mysqli_query($koneksi, "SELECT id FROM kelas_mapel_guru WHERE kelas_id=$kid AND mapel_id=$mid AND tahun_ajaran_id=$taId LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Mapel ini sudah ditugaskan di kelas tersebut. Gunakan Edit untuk mengubah guru/KKM.";
        } else {
            $q = mysqli_query($koneksi,
                "INSERT INTO kelas_mapel_guru (kelas_id, mapel_id, guru_id, tahun_ajaran_id, kkm, jam_per_minggu)
                 VALUES ($kid, $mid, $gid, $taId, $kkm, $jam)");
            if ($q) {
                header("Location: index.php?kelas_id=$kid&success=Penugasan mapel berhasil ditambahkan");
                exit();
            } else {
                $error = "Gagal menyimpan: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-icon me-2"></i>Tambah Penugasan Mapel</h4>
        <a href="index.php<?= $preset_kelas ? '?kelas_id=' . $preset_kelas : '' ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Form Tambah Penugasan</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>" <?= ($_POST['kelas_id'] ?? $preset_kelas) == $k['id'] ? 'selected' : '' ?>>
                                    <?= e($k['nama_kelas']) ?> (<?= $k['jurusan'] ?? 'Umum' ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="mapel_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                <option value="<?= $m['id'] ?>" <?= ($_POST['mapel_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                                    <?= e($m['nama_mapel']) ?> (<?= e(ucfirst($m['kategori'] ?? 'wajib')) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guru Pengampu <span class="text-danger">*</span></label>
                            <select name="guru_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>" <?= ($_POST['guru_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['nama'] ?? $g['nama_lengkap'] ?? '-') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">KKM</label>
                            <input type="number" name="kkm" class="form-control" value="<?= $_POST['kkm'] ?? 75 ?>" min="0" max="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam per Minggu</label>
                            <input type="number" name="jam_per_minggu" class="form-control" value="<?= $_POST['jam_per_minggu'] ?? 2 ?>" min="1" max="40">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" class="form-control" value="<?= e($taTahun) ?>" readonly>
                            <small class="text-muted">Mengikuti tahun ajaran aktif.</small>
                        </div>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="index.php<?= $preset_kelas ? '?kelas_id=' . $preset_kelas : '' ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
