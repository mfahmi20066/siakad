<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_periode_nilai.php';
cekGuru(); // cuma guru yang bisa akses
$title = "Edit Nilai";

$id  = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
// sesuaikan dengan session id_ref guru
$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : (isset($_SESSION['guru_id']) ? $_SESSION['guru_id'] : '');

// ambil data nilai dengan hak akses guru
if (!isset($stmt_nilai_cek) || $stmt_nilai_cek === null) {
    $stmt_nilai_cek = mysqli_prepare($koneksi,
        "SELECT n.*, s.nama_lengkap AS nama_siswa, s.nis, m.nama_mapel, k.nama_kelas
         FROM nilai n
         JOIN siswa s ON n.siswa_id = s.id
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         JOIN kelas_mapel_guru kmg ON kmg.mapel_id = n.mapel_id AND kmg.kelas_id = s.kelas_id
         JOIN kelas k ON kmg.kelas_id = k.id
         WHERE n.id = ? AND kmg.guru_id = ?
         LIMIT 1");
    mysqli_stmt_bind_param($stmt_nilai_cek, "ii", $id, $gid);
}
mysqli_stmt_execute($stmt_nilai_cek);
mysqli_stmt_bind_result($stmt_nilai_cek, $n_id, $n_siswa_id, $n_mapel_id, $n_nilai_harian, $n_nilai_uts, $n_nilai_uas, $n_nilai_akhir, $n_nilai_kehadiran, $n_nilai_uh, $n_semester, $n_nama_siswa, $n_nis, $n_nama_mapel, $n_nama_kelas);
mysqli_stmt_fetch($stmt_nilai_cek);

// nilai ga ketemu / bukan hak guru ini? balik ke index
if (!$data) {
    header("Location: index.php");
    exit();
}

// rekap kehadiran dari absensi, ditampilkan sebagai info
$kehadiran_info = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'total' => 0, 'persen' => 0];
if (!empty($data['siswa_id']) && !empty($data['mapel_id'])) {
    // ambil rekap kehadiran
    if (!isset($stmt_abs_info) || $stmt_abs_info === null) {
        $stmt_abs_info = mysqli_prepare($koneksi,
            "SELECT SUM(status = 'Hadir') AS hadir,
             SUM(status = 'Izin')  AS izin,
             SUM(status = 'Sakit') AS sakit,
             SUM(status = 'Alpa')  AS alpa,
             COUNT(*) AS total
             FROM absensi WHERE siswa_id = ? AND mapel_id = ?");
        mysqli_stmt_bind_param($stmt_abs_info, "is", $data['siswa_id'], $data['mapel_id']);
    }
    mysqli_stmt_execute($stmt_abs_info);
    mysqli_stmt_bind_result($stmt_abs_info, $hadir, $izin, $sakit, $alpa, $total);
    mysqli_stmt_fetch($stmt_abs_info);
    $kehadiran_info['hadir'] = (int) $hadir;
    $kehadiran_info['izin']  = (int) $izin;
    $kehadiran_info['sakit'] = (int) $sakit;
    $kehadiran_info['alpa']  = (int) $alpa;
    $kehadiran_info['total'] = (int) $total;
    $kehadiran_info['persen'] = $kehadiran_info['total'] > 0
        ? round(($kehadiran_info['hadir'] / $kehadiran_info['total']) * 100, 2)
        : 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uh  = (float) ($_POST['nilai_uh'] ?? 0);
    $uts = (float) ($_POST['nilai_uts'] ?? 0);
    $uas = (float) ($_POST['nilai_uas'] ?? 0);

    if ($uh < 0 || $uh > 100 || $uts < 0 || $uts > 100 || $uas < 0 || $uas > 100) {
        $error = "Nilai harus antara 0 sampai 100!";
    } else {
        // lock periode: guru cuma bisa edit kalo periode kelas dibuka admin
        $taData    = (int) ($data['tahun_ajaran_id'] ?? 0);
        $semData   = (int) ($data['semester'] ?? 1);
        $kelasData = (int) ($data['kelas_id'] ?? 0);
        if ($taData > 0 && $kelasData > 0 && !isPeriodeBuka($koneksi, $taData, $semData, $kelasData)) {
            $error = pesanNilaiTerkunci();
} else {
            // rapor final? nilai ga boleh berubah; cek rapor final
            if (!isset($stmt_rapor_final) || $stmt_rapor_final === null) {
                $stmt_rapor_final = mysqli_prepare($koneksi,
                    "SELECT id FROM rapor WHERE siswa_id=? AND semester=? AND tahun_ajaran_id=? AND status='final' LIMIT 1");
                mysqli_stmt_bind_param($stmt_rapor_final, "isi", $data['siswa_id'], $semData, $taData);
            }
            mysqli_stmt_execute($stmt_rapor_final);
            mysqli_stmt_bind_result($stmt_rapor_final, $rapor_final_id);
            mysqli_stmt_fetch($stmt_rapor_final);
            $rapor_final = ($rapor_final_id !== null);

            if ($rapor_final) {
                $error = "Nilai tidak dapat diubah: rapor semester ini sudah difinalisasi.";
} else {
                // kehadiran dari absensi (20% nilai akhir)
                $kehadiran = 0;
                // ambil rekap kehadiran buat update
                if (!isset($stmt_abs_update) || $stmt_abs_update === null) {
                    $stmt_abs_update = mysqli_prepare($koneksi,
                        "SELECT SUM(status = 'Hadir') AS hadir, COUNT(*) AS total
                        FROM absensi WHERE siswa_id = ? AND mapel_id = ?");
                    mysqli_stmt_bind_param($stmt_abs_update, "is", $data['siswa_id'], $data['mapel_id']);
                }
                mysqli_stmt_execute($stmt_abs_update);
                mysqli_stmt_bind_result($stmt_abs_update, $hadir, $total_abs);
                mysqli_stmt_fetch($stmt_abs_update);
                $kehadiran = $total_abs > 0
                    ? round(((int) $hadir / $total_abs) * 100, 2)
                    : 0;

                // rumus nilai akhir: harian 20% + uts 25% + uas 35% + kehadiran 20%
                $akhir = round(($uh * 0.20) + ($uts * 0.25) + ($uas * 0.35) + ($kehadiran * 0.20), 2);

$cek_uh_col = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_uh'");
                $kolom_uh_sql = (mysqli_num_rows($cek_uh_col) > 0) ? "nilai_uh='$uh'," : "";
                $cek_had_col = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_kehadiran'");
                $set_kehadiran_sql = (mysqli_num_rows($cek_had_col) > 0) ? "nilai_kehadiran='$kehadiran'," : "";

                // update nilai
                if (!isset($stmt_update_nilai) || $stmt_update_nilai === null) {
                    $stmt_update_nilai = mysqli_prepare($koneksi,
                        "UPDATE nilai SET nilai_harian = ?, nilai_uts = ?, nilai_uas = ?, $kolom_uh_sql nilai_akhir = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_update_nilai, "ssssi", $uh, $uts, $uas, $akhir, $id);
                }
                mysqli_stmt_execute($stmt_update_nilai);

                if (mysqli_stmt_affected_rows($stmt_update_nilai) > 0) {
                    header("Location: index.php?success=Nilai berhasil diupdate");
                    exit();
                } else {
                    $error = "Gagal memperbarui nilai: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Nilai</h4>
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
                        <div class="fw-bold"><?= e($data['nama_siswa'] ?? '') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Mata Pelajaran</small>
                        <div class="fw-bold"><?= e($data['nama_mapel'] ?? '') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= e($data['nama_kelas'] ?? '') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Semester</small>
                        <div class="fw-bold">Semester <?= e($data['semester'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">Nilai Ulangan Harian (20%)</label>
                            <input type="number" name="nilai_uh" class="form-control"
                                   value="<?= e($data['nilai_uh'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UTS (25%)</label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   value="<?= e($data['nilai_uts'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UAS (35%)</label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   value="<?= e($data['nilai_uas'] ?? 0) ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Nilai Kehadiran <small class="text-muted">(otomatis dari data Absensi, ikut menentukan 20% Nilai Akhir)</small>
                            </label>
                            <?php if ($kehadiran_info['total'] > 0): ?>
                            <div class="alert alert-<?= $kehadiran_info['persen'] >= 75 ? 'success' : 'danger' ?> mb-0 py-2 px-3">
                                <strong><?= $kehadiran_info['persen'] ?>%</strong> kehadiran
                                <span class="text-muted">(dari <?= $kehadiran_info['total'] ?>x pertemuan)</span><br>
                                <span class="badge bg-success me-1">Hadir: <?= $kehadiran_info['hadir'] ?></span>
                                <span class="badge bg-info me-1">Izin: <?= $kehadiran_info['izin'] ?></span>
                                <span class="badge bg-warning text-dark me-1">Sakit: <?= $kehadiran_info['sakit'] ?></span>
                                <span class="badge bg-danger">Alpa: <?= $kehadiran_info['alpa'] ?></span>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning mb-0 py-2 px-3">
                                <i class="fas fa-exclamation-triangle"></i> Belum ada data absensi untuk siswa & mapel ini (dihitung 0 untuk sementara).
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preview Nilai Akhir</label>
                            <div class="input-group">
                                <input type="text" id="preview" class="form-control fw-bold"
                                       value="<?= e($data['nilai_akhir'] ?? 0) ?>" readonly>
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
    const kehadiran = <?= json_encode($kehadiran_info['persen']) ?>;
    const val = Math.round(((nh * 0.20) + (uts * 0.25) + (uas * 0.35) + (kehadiran * 0.20)) * 100) / 100;
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