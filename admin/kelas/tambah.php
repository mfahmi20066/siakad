<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tambah Kelas";

$taId = null; $taTahun = '';
try {
    $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo());
    $taId    = (int) $taAktif['id'];
    $taTahun = $taAktif['tahun'];
} catch (Throwable $e) {
    // tanpa tahun aktif, kelas ga boleh ditambah
}

$guru_list = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // tahun ajaran dari master tahun aktif, bukan dari post
    if ($taId === null || $taTahun === '') {
        $error = "Tidak ada tahun ajaran aktif. Tetapkan tahun ajaran aktif di Modul Tahun Ajaran terlebih dahulu.";
    } else {
        $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
        $tingkat = (int)$_POST['tingkat'];
        $jurusan = in_array($_POST['jurusan'] ?? 'Umum', ['Umum', 'IPA', 'IPS']) ? $_POST['jurusan'] : 'Umum';

        // fix: wali_kelas NULL kalo kosong (foreign key butuh NULL, bukan '')
        $wali     = !empty($_POST['wali_kelas']) ? (int)$_POST['wali_kelas'] : null;
        $wali_sql = ($wali !== null) ? "'$wali'" : "NULL";

        // cek nama kelas udah ada atau belum
        $cek = mysqli_query($koneksi,
               "SELECT id FROM kelas WHERE nama_kelas='$nama' AND tahun_ajaran_id='$taId'");

        if (mysqli_num_rows($cek) > 0) {
            $error = "Kelas $nama sudah ada di tahun ajaran $taTahun ($taId)!";
        } else {
            $q = mysqli_query($koneksi,
                "INSERT INTO kelas (nama_kelas, tingkat, jurusan, wali_kelas, tahun_ajaran, tahun_ajaran_id)
                 VALUES ('$nama', '$tingkat', '$jurusan', $wali_sql, '$taTahun', '$taId')");

            if ($q) {
                header("Location: index.php?success=Kelas $nama berhasil ditambahkan");
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
        <h4 class="mb-0"><i class="fas fa-plus text-icon me-2"></i>Tambah Kelas</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-wpforms"></i> Form Tambah Kelas
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Kelas <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_kelas" class="form-control"
                                   placeholder="Contoh: X A / XI IPA 1 / XII IPS 2" required>
                            <small class="text-muted">
                                Kelas X umumnya memakai huruf (X A, X B, …). Kelas XI/XII dapat memakai
                                penjurusan (XI IPA 1, XI IPS 2, …) sesuai Kurikulum Merdeka.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select name="tingkat" class="form-select" required>
                                <option value="10">Kelas 10 (X)</option>
                                <option value="11">Kelas 11 (XI)</option>
                                <option value="12">Kelas 12 (XII)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jurusan</label>
                            <select name="jurusan" class="form-select">
                                <option value="Umum">Umum</option>
                                <option value="IPA">IPA</option>
                                <option value="IPS">IPS</option>
                            </select>
                            <small class="text-muted">Kelas X umumnya Umum; kelas XI/XII dapat IPA/IPS (Kurikulum Merdeka).</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Wali Kelas</label>
                            <select name="wali_kelas" class="form-select">
                                <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>">
                                    <?= e($g['nama'] ?? $g['nama_lengkap'] ?? '-') ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Boleh dikosongkan, bisa diisi nanti</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Tahun Ajaran <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tahun_ajaran" class="form-control"
                                   value="<?= e($taTahun) ?>" placeholder="<?= e($taTahun) ?>" readonly>
                            <small class="text-muted">Mengikuti tahun ajaran aktif (sumber kebenaran). Tidak dapat diubah manual.</small>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Informasi</h6>
                            <p class="mb-1">Panduan pengisian nama kelas:</p>
                            <ul class="mb-0 small">
                                <li>Kelas 10 (X): fase umum — pakai huruf, misal <strong>X A, X B</strong></li>
                                <li>Kelas 11 (XI) & 12 (XII): bisa pakai huruf ATAU penjurusan — misal <strong>XI IPA 1, XI IPS 2, XII IPA 1</strong></li>
                                <li>Pilih <strong>Jurusan</strong>: Umum untuk kelas X, IPA/IPS untuk kelas XI/XII yang berpenjurusan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>