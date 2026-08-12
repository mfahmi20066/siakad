<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Kelas";

$id        = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
$data      = mysqli_fetch_assoc(mysqli_query($koneksi,
             "SELECT * FROM kelas WHERE id='$id'"));
$guru_list = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

if (!$data) {
    header("Location: index.php?error=Data kelas tidak ditemukan");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tahun ajaran TIDAK dapat diubah dari halaman edit: gunakan tahun_ajaran_id dari record
    // yang sudah ada sebagai sumber utama, agar kelas tidak dipindah tanpa konfirmasi.
    $erDataTaId  = $data['tahun_ajaran_id'];
    $taIdSql     = ($erDataTaId !== null && $erDataTaId !== '') ? "'".(int)$erDataTaId."'" : 'NULL';
    $taTxtSql    = "'".mysqli_real_escape_string($koneksi, $data['tahun_ajaran'])."'";

    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
    $tingkat = $_POST['tingkat'];
    $jurusan = in_array($_POST['jurusan'] ?? 'Umum', ['Umum', 'IPA', 'IPS']) ? $_POST['jurusan'] : 'Umum';
    $wali    = $_POST['wali_kelas'];

    // Cek nama kelas duplikat, kecuali data diri sendiri (berbasis tahun_ajaran_id)
    $cek = mysqli_query($koneksi,
           "SELECT id FROM kelas 
            WHERE nama_kelas='$nama' AND tahun_ajaran_id = $taIdSql AND id != '$id'");

    if (mysqli_num_rows($cek) > 0) {
        $error = "Kelas $nama sudah ada di tahun ajaran " . ($data['tahun_ajaran'] ? $data['tahun_ajaran'] : '(tanpa tahun)') . "!";
    } else {
        // PERBAIKAN UTAMA: Mengatasi error Foreign Key Constraint dengan mengeset NULL jika wali kelas kosong
        if (empty($wali)) {
            mysqli_query($koneksi,
                "UPDATE kelas 
                 SET nama_kelas='$nama', tingkat='$tingkat', jurusan='$jurusan',
                     wali_kelas=NULL, tahun_ajaran=$taTxtSql, tahun_ajaran_id=$taIdSql
                 WHERE id='$id'");
        } else {
            mysqli_query($koneksi,
                "UPDATE kelas 
                 SET nama_kelas='$nama', tingkat='$tingkat', jurusan='$jurusan',
                     wali_kelas='$wali', tahun_ajaran=$taTxtSql, tahun_ajaran_id=$taIdSql
                 WHERE id='$id'");
        }

        header("Location: index.php?success=Kelas $nama berhasil diupdate");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Kelas</h4>
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
        <div class="card-header">Form Edit Kelas</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" name="nama_kelas" class="form-control"
                                   value="<?= e($data['nama_kelas']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select">
                                <option value="10" <?= $data['tingkat'] == '10' ? 'selected' : '' ?>>
                                    Kelas 10 (X)
                                </option>
                                <option value="11" <?= $data['tingkat'] == '11' ? 'selected' : '' ?>>
                                    Kelas 11 (XI)
                                </option>
                                <option value="12" <?= $data['tingkat'] == '12' ? 'selected' : '' ?>>
                                    Kelas 12 (XII)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jurusan</label>
                            <select name="jurusan" class="form-select">
                                <?php $j = $data['jurusan'] ?? 'Umum'; ?>
                                <option value="Umum" <?= $j == 'Umum' ? 'selected' : '' ?>>Umum</option>
                                <option value="IPA"  <?= $j == 'IPA'  ? 'selected' : '' ?>>IPA</option>
                                <option value="IPS"  <?= $j == 'IPS'  ? 'selected' : '' ?>>IPS</option>
                            </select>
                            <small class="text-muted">Umum untuk kelas X (fase umum); IPA/IPS untuk kelas XI/XII berpenjurusan.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Wali Kelas</label>
                            <select name="wali_kelas" class="form-select">
                                <option value="">-- Pilih Wali Kelas --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>"
                                    <?= $data['wali_kelas'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control"
                                   value="<?= e($data['tahun_ajaran']) ?>"
                                   readonly>
                            <small class="text-muted">Tahun ajaran tidak dapat diubah di sini. Untuk memindahkan kelas, hubungi langkah khusus.</small>
                        </div>

                    </div>
                    <div class="col-md-6">

                        <?php
                        $jml_siswa = 0;
                        $q_siswa = mysqli_query($koneksi, "SELECT COUNT(*) FROM siswa WHERE kelas_id='$id'");
                        if ($q_siswa) {
                            $jml_siswa = mysqli_fetch_row($q_siswa)[0];
                        }
                        ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Perhatian</h6>
                            <p class="mb-0">
                                Kelas ini saat ini memiliki
                                <strong><?= $jml_siswa ?> siswa</strong>.
                                Perubahan kelas akan mempengaruhi data siswa tersebut.
                            </p>
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