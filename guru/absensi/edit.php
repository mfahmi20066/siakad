<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru(); // Memastikan hanya role guru yang bisa akses
$title = "Edit Absensi";

$id  = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
// SINKRONISASI SESSION: Menggunakan id_ref sebagai ID Guru yang sedang login
$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : '';

// Mengambil data absensi dan memvalidasi hak akses guru melalui pivot kelas_mapel_guru
$query = "SELECT a.*, s.nama_lengkap AS nama_siswa, s.nis, k.nama_kelas
          FROM absensi a
          JOIN siswa s ON a.siswa_id = s.id
          LEFT JOIN kelas k ON a.kelas_id = k.id
          LEFT JOIN kelas_mapel_guru kmg ON a.mapel_id = kmg.mapel_id AND a.kelas_id = kmg.kelas_id
          WHERE a.id = '$id' AND kmg.guru_id = '$gid'
          LIMIT 1";

$res  = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

// Jika data absensi tidak ditemukan atau bukan kelas/mapel ampu guru ini, kembalikan ke index
if (!$data) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $st  = mysqli_real_escape_string($koneksi, $_POST['status']);
    $ket = mysqli_real_escape_string($koneksi, trim($_POST['keterangan']));

    // UPDATE FIX: Menghapus guru_id dari kondisi WHERE karena tidak ada di tabel absensi
    $update_query = "UPDATE absensi SET status='$st', keterangan='$ket' WHERE id='$id'";
    
    if (mysqli_query($koneksi, $update_query)) {
        header("Location: index.php?success=Absensi berhasil diupdate");
        exit();
    } else {
        $error = "Gagal memperbarui absensi: " . mysqli_error($koneksi);
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Absensi</h4>
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
        <div class="card-header">Form Edit Absensi</div>
        <div class="card-body">

            <div class="alert alert-secondary mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Siswa</small>
                        <div class="fw-bold"><?= e($data['nama_siswa'] ?? '') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">NIS</small>
                        <div class="fw-bold"><?= e($data['nis'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= e($data['nama_kelas'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Tanggal</small>
                        <div class="fw-bold">
                            <?= isset($data['tanggal']) ? tanggal_indo($data['tanggal'], true) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Status Kehadiran</label>
                        <select name="status" class="form-select">
                            <?php 
                            // Mendukung opsi ENUM panjang maupun inisial satu huruf (H/I/S/A) sesuai struktur table Anda
                            $status_list = ['Hadir', 'Sakit', 'Izin', 'Alpa', 'H', 'S', 'I', 'A'];
                            foreach ($status_list as $s): 
                                if ($data['status'] == $s):
                            ?>
                                <option value="<?= $s ?>" selected><?= $s ?></option>
                            <?php else: ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan alasan jika sakit/izin..."><?= e($data['keterangan'] ?? '') ?></textarea>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>