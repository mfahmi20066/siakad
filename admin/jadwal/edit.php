<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Jadwal";

$id         = $_GET['id'];
$data       = mysqli_fetch_assoc(mysqli_query($koneksi,
              "SELECT * FROM jadwal WHERE id='$id'"));
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");
$mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$guru_list  = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kid     = $_POST['kelas_id'];
    $mid     = $_POST['mapel_id'];
    $gid     = $_POST['guru_id'];
    $hari    = $_POST['hari'];
    $mulai   = $_POST['jam_mulai'];
    $selesai = $_POST['jam_selesai'];

    // Validasi jam
    if (!in_array($hari, ['Senin','Selasa','Rabu','Kamis','Jumat'], true)) {
        $error = "Hari tidak valid. Hanya Senin s.d Jumat yang diizinkan (Sabtu/Minggu ditolak).";
    } elseif ($selesai <= $mulai) {
        $error = "Jam selesai harus lebih dari jam mulai!";
    } else {
        // Cek bentrok jadwal, kecuali data diri sendiri
        $cek_bentrok = mysqli_query($koneksi,
            "SELECT id FROM jadwal
             WHERE kelas_id = '$kid'
             AND hari = '$hari'
             AND id != '$id'
             AND (
                 '$mulai' < jam_selesai AND '$selesai' > jam_mulai
             )");

        if (mysqli_num_rows($cek_bentrok) > 0) {
            $error = "Jadwal bentrok! Kelas tersebut sudah memiliki jadwal 
                      di hari dan jam yang sama.";
        } else {
            mysqli_query($koneksi,
                "UPDATE jadwal
                 SET kelas_id = '$kid', mapel_id = '$mid', guru_id = '$gid',
                     hari = '$hari', jam_mulai = '$mulai', jam_selesai = '$selesai'
                 WHERE id = '$id'");

            header("Location: index.php?success=Jadwal berhasil diupdate");
            exit();
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-gold me-2"></i>Edit Jadwal</h4>
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
        <div class="card-header">Form Edit Jadwal</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">

                    <!-- Kolom Kiri -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Data Jadwal</h6>

                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="mapel_id" class="form-select" required>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                <option value="<?= $m['id'] ?>"
                                    <?= $data['mapel_id'] == $m['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nama_mapel']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guru Pengajar</label>
                            <select name="guru_id" class="form-select" required>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>"
                                    <?= $data['guru_id'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Waktu Jadwal</h6>

                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $h): ?>
                                <option value="<?= $h ?>"
                                    <?= $data['hari'] == $h ? 'selected' : '' ?>>
                                    <?= $h ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control"
                                   value="<?= $data['jam_mulai'] ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control"
                                   value="<?= $data['jam_selesai'] ?>" required>
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