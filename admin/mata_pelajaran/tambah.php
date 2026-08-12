<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Tambah Mata Pelajaran";

// Use ORDER BY kolom yang kemungkinan ada
$guru_list = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");
if (!$guru_list) {
    $guru_list = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama_lengkap");
}

$error = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapel_id = isset($_POST['mapel_id']) ? (int)$_POST['mapel_id'] : 0;
    $guru_id  = isset($_POST['guru_id']) ? (int)$_POST['guru_id'] : 0;
    $kelas_id = isset($_POST['kelas_id_wali']) ? (int)$_POST['kelas_id_wali'] : 0;

    // Load kode & nama mapel berdasarkan mapel_id
    $kode = '';
    $nama = '';
    if ($mapel_id > 0) {
        $q_mapel = mysqli_query(
            $koneksi,
            "SELECT id, kode_mapel, nama_mapel FROM mata_pelajaran WHERE id='$mapel_id' LIMIT 1"
        );

        if ($row_mapel = mysqli_fetch_assoc($q_mapel)) {
            $kode = $row_mapel['kode_mapel'] ?? '';
            $nama = $row_mapel['nama_mapel'] ?? '';
        }
    }

    if (empty($kode) || empty($nama)) {
        $error = "Mata pelajaran belum dipilih atau data tidak valid.";
    } else {
        $kode = mysqli_real_escape_string($koneksi, strtoupper($kode));
        $nama = mysqli_real_escape_string($koneksi, $nama);

        // Set wali kelas -> kelas.wali_kelas (hanya jika guru_id valid)
        if ($kelas_id > 0 && $guru_id > 0) {
            mysqli_query(
                $koneksi,
                "UPDATE kelas SET wali_kelas='$guru_id' WHERE id='$kelas_id'"
            );
        }

        header("Location: index.php?success=Mata pelajaran " . urlencode($nama) . " berhasil diset");
        exit();
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-icon me-2"></i>Tambah Mata Pelajaran</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-add"></i> Form Tambah Mata Pelajaran
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Pilih Mata Pelajaran</label>
                            <select name="mapel_id" class="form-select" required onchange="syncMapel()">
                                <option value="">-- Pilih --</option>
                                <?php
                                $mata_pelajaran_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");
                                while ($mp = mysqli_fetch_assoc($mata_pelajaran_list)):
                                ?>
                                    <option value="<?= (int)$mp['id'] ?>"
                                        <?= (isset($_POST['mapel_id']) && (int)$_POST['mapel_id'] === (int)$mp['id']) ? 'selected' : '' ?> >
                                        <?= e($mp['nama_mapel']) ?> (<?= e($mp['kode_mapel']) ?>) — <?= e(ucfirst($mp['kategori'] ?? 'wajib')) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Kode & nama diambil otomatis. Kategori (wajib/pilihan/projek) ditampilkan agar mudah membedakan mapel Kurikulum Merdeka.</small>
                        </div>

                        <input type="hidden" name="kode_mapel" id="kode_mapel" value="<?= isset($_POST['kode_mapel']) ? e((string)$_POST['kode_mapel']) : '' ?>">
                        <input type="hidden" name="nama_mapel" id="nama_mapel" value="<?= isset($_POST['nama_mapel']) ? e((string)$_POST['nama_mapel']) : '' ?>">

                        <div class="mb-3">
                            <label class="form-label">Guru Pengampu</label>
                            <select name="guru_id" class="form-select">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                    <option value="<?= (int)$g['id'] ?>"
                                        <?= (isset($_POST['guru_id']) && (int)$_POST['guru_id'] === (int)$g['id']) ? 'selected' : '' ?> >
                                        <?= e($g['nama']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Boleh dikosongkan, bisa diisi nanti</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kelas sebagai Wali Kelas</label>
                            <select name="kelas_id_wali" class="form-select">
                                <option value="0">-- Tidak sebagai Wali Kelas --</option>
                                <?php
                                $kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY nama_kelas");
                                while ($k = mysqli_fetch_assoc($kelas_list)):
                                ?>
                                    <option value="<?= (int)$k['id'] ?>"
                                        <?= (isset($_POST['kelas_id_wali']) && (int)$_POST['kelas_id_wali'] === (int)$k['id']) ? 'selected' : '' ?> >
                                        <?= e($k['nama_kelas']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Walikelas di-set berdasarkan guru pengampu yang dipilih.</small>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Fungsi Halaman Ini</h6>
                            <p class="small mb-2">
                                Form ini untuk <strong>mengatur guru pengampu & wali kelas</strong> dari mata pelajaran yang sudah ada.
                                Data mapel (nama, kode, kelompok, kategori, KKM, status) dikelola di <strong>Edit Mata Pelajaran</strong>.
                            </p>
                            <h6><i class="fas fa-info-circle"></i> Contoh Kode Mapel</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td><span class="badge bg-secondary">MTK</span></td>
                                    <td>Matematika</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">BIN</span></td>
                                    <td>Bahasa Indonesia</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">BIG</span></td>
                                    <td>Bahasa Inggris</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">IPA</span></td>
                                    <td>Ilmu Pengetahuan Alam</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">IPS</span></td>
                                    <td>Ilmu Pengetahuan Sosial</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">PKN</span></td>
                                    <td>Pendidikan Kewarganegaraan</td>
                                </tr>
                            </table>
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

