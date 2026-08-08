<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru(); // Memastikan hanya guru yang bisa akses
$title = "Edit Nilai";

$id  = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
// Sesuaikan dengan session id_ref guru Anda
$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : (isset($_SESSION['guru_id']) ? $_SESSION['guru_id'] : '');

// QUERY FIX: Mengambil data nilai & mencocokkan hak akses guru pengampu melalui tabel jadwal
$query = "SELECT n.*, s.nama_lengkap AS nama_siswa, s.nis, m.nama_mapel, k.nama_kelas
          FROM nilai n
          JOIN siswa s ON n.siswa_id = s.id
          JOIN mata_pelajaran m ON n.mapel_id = m.id
          JOIN jadwal j ON n.mapel_id = j.mapel_id
          JOIN kelas k ON j.kelas_id = k.id
          WHERE n.id = '$id' AND j.guru_id = '$gid'
          LIMIT 1";

$res = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($res);

// Jika data nilai tidak ditemukan atau bukan hak akses guru ini, kembalikan ke index
if (!$data) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Menyesuaikan name attribute komponen form
    $uh  = mysqli_real_escape_string($koneksi, $_POST['nilai_uh']);
    $uts = mysqli_real_escape_string($koneksi, $_POST['nilai_uts']);
    $uas = mysqli_real_escape_string($koneksi, $_POST['nilai_uas']);

    // Hitung nilai akhir otomatis
    $akhir = round(($uh * 0.3) + ($uts * 0.3) + ($uas * 0.4), 2);

    // UPDATE FIX: Menyesuaikan nama kolom tabel 'nilai' Anda (nilai_uh, nilai_uts, nilai_uas)
    $update_query = "UPDATE nilai 
                     SET nilai_uh='$uh', nilai_uts='$uts', nilai_uas='$uas', nilai_akhir='$akhir' 
                     WHERE id='$id'";
    
    if (mysqli_query($koneksi, $update_query)) {
        header("Location: index.php?success=Nilai berhasil diupdate");
        exit();
    } else {
        $error = "Gagal memperbarui nilai: " . mysqli_error($koneksi);
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-gold me-2"></i>Edit Nilai</h4>
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
        <div class="card-header">Form Edit Nilai</div>
        <div class="card-body">

            <div class="alert alert-secondary mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Siswa</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_siswa'] ?? '') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Mata Pelajaran</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_mapel'] ?? '') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_kelas'] ?? '') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Semester</small>
                        <div class="fw-bold">Semester <?= htmlspecialchars($data['semester'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Nilai Ulangan Harian (30%)</label>
                            <input type="number" name="nilai_uh" class="form-control"
                                   value="<?= htmlspecialchars($data['nilai_uh'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UTS (30%)</label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   value="<?= htmlspecialchars($data['nilai_uts'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UAS (40%)</label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   value="<?= htmlspecialchars($data['nilai_uas'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preview Nilai Akhir</label>
                            <div class="input-group">
                                <input type="text" id="preview" class="form-control fw-bold"
                                       value="<?= htmlspecialchars($data['nilai_akhir'] ?? 0) ?>" readonly>
                                <span class="input-group-text fw-bold" id="predikat">E</span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Nilai
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
window.onload = function() { hitungAkhir(); }
function hitungAkhir() {
    const nh  = parseFloat(document.querySelector('[name="nilai_uh"]').value) || 0;
    const uts = parseFloat(document.querySelector('[name="nilai_uts"]').value) || 0;
    const uas = parseFloat(document.querySelector('[name="nilai_uas"]').value) || 0;
    const val = Math.round(((nh * 0.3) + (uts * 0.3) + (uas * 0.4)) * 100) / 100;
    let p = 'E';
    if (val >= 90) p = 'A'; 
    else if (val >= 80) p = 'B';
    else if (val >= 70) p = 'C'; 
    else if (val >= 60) p = 'D';
    document.getElementById('preview').value        = val;
    document.getElementById('predikat').textContent = p;
}
</script>

<?php include '../../includes/footer.php'; ?>