<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Buat Pengumuman";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $isi   = mysqli_real_escape_string($koneksi, $_POST['isi']);
    $tgl   = $_POST['tanggal'];
    $uid   = $_SESSION['user_id'];

    // PERBAIKAN: Menggunakan 'admin_id' sesuai dengan struktur bawaan database pengumuman
    mysqli_query($koneksi,
        "INSERT INTO pengumuman (judul, isi, admin_id, tanggal)
         VALUES ('$judul', '$isi', '$uid', '$tgl')") or die(mysqli_error($koneksi));

    // Notifikasi otomatis ke semua guru dan siswa
    if (!function_exists('notifikasi_ke_role')) {
        include __DIR__ . '/../../includes/notifikasi_functions.php';
    }
    $ringkas = mb_strlen($isi) > 120 ? mb_substr($isi, 0, 120) . '...' : $isi;
    notifikasi_ke_role($koneksi, 'guru', 'Pengumuman baru: ' . $judul, $ringkas, '/siakad/guru/pengumuman/index.php');
    notifikasi_ke_role($koneksi, 'siswa', 'Pengumuman baru: ' . $judul, $ringkas, '/siakad/siswa/pengumuman.php');

    header("Location: index.php?success=Pengumuman berhasil dibuat");
    exit();
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-icon me-2"></i>Buat Pengumuman</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-wpforms"></i> Form Buat Pengumuman
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-8">

                        <div class="mb-3">
                            <label class="form-label">
                                Judul Pengumuman <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="judul" class="form-control"
                                   placeholder="Judul pengumuman yang jelas dan singkat"
                                   value="<?= isset($_POST['judul']) ? e($_POST['judul']) : '' ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Isi Pengumuman <span class="text-danger">*</span>
                            </label>
                            <textarea name="isi" class="form-control" rows="8"
                                      placeholder="Tulis isi pengumuman secara lengkap..."
                                      required><?= isset($_POST['isi']) ? e($_POST['isi']) : '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Tanggal <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Informasi</h6>
                            <p class="small mb-1">
                                Pengumuman yang dibuat akan langsung terlihat oleh:
                            </p>
                            <ul class="small mb-0">
                                <li>Semua <strong>Guru</strong></li>
                                <li>Semua <strong>Siswa</strong></li>
                                <li>Admin lainnya</li>
                            </ul>
                        </div>
                        <div class="alert alert-secondary">
                            <small>
                                <i class="fas fa-user-shield"></i>
                                Dibuat oleh: <strong><?= $_SESSION['nama'] ?></strong>
                            </small>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Publikasikan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>