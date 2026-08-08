<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Nilai";

$id   = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// SINKRONISASI SELECT: Menggunakan s.nama untuk menampilkan nama siswa secara utuh
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT n.*, s.nama AS nama_siswa, s.nis,
                m.nama_mapel, k.nama_kelas
         FROM nilai n
         JOIN siswa s ON n.siswa_id = s.id
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         LEFT JOIN kelas k ON s.kelas_id = k.id
         WHERE n.id = '$id'"));

// FITUR AMAN: Cek struktur nama kolom nilai saat ini (nilai_uh vs nilai_harian)
$cek_uh = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_uh'");
$kolom_uh = (mysqli_num_rows($cek_uh) > 0) ? "nilai_uh" : "nilai_harian";

// OTOMATIS: rekap kehadiran diambil langsung dari tabel absensi, untuk ditampilkan sebagai info
$kehadiran_info = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'total' => 0, 'persen' => 0];
if (!empty($data['siswa_id']) && !empty($data['mapel_id'])) {
    $abs_q = mysqli_query($koneksi, "SELECT
            SUM(status = 'Hadir') AS hadir,
            SUM(status = 'Izin')  AS izin,
            SUM(status = 'Sakit') AS sakit,
            SUM(status = 'Alpa')  AS alpa,
            COUNT(*) AS total
        FROM absensi WHERE siswa_id = '{$data['siswa_id']}' AND mapel_id = '{$data['mapel_id']}'");
    if ($abs_row = mysqli_fetch_assoc($abs_q)) {
        $kehadiran_info['hadir'] = (int) $abs_row['hadir'];
        $kehadiran_info['izin']  = (int) $abs_row['izin'];
        $kehadiran_info['sakit'] = (int) $abs_row['sakit'];
        $kehadiran_info['alpa']  = (int) $abs_row['alpa'];
        $kehadiran_info['total'] = (int) $abs_row['total'];
        $kehadiran_info['persen'] = $kehadiran_info['total'] > 0
            ? round(($kehadiran_info['hadir'] / $kehadiran_info['total']) * 100, 2)
            : 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nh  = $_POST['nilai_harian'];
    $uts = $_POST['nilai_uts'];
    $uas = $_POST['nilai_uas'];

    if ($nh < 0 || $nh > 100 || $uts < 0 || $uts > 100 || $uas < 0 || $uas > 100) {
        $error = "Nilai harus antara 0 sampai 100!";
    } else {
        // OTOMATIS: nilai kehadiran dihitung ulang dari tabel absensi (bukan input manual)
        // Ikut menentukan 20% dari Nilai Akhir
        $cek_kolom_kehadiran = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_kehadiran'");
        $ada_kolom_kehadiran = mysqli_num_rows($cek_kolom_kehadiran) > 0;

        $abs = mysqli_query($koneksi, "SELECT
                SUM(status = 'Hadir') AS hadir, COUNT(*) AS total
                FROM absensi WHERE siswa_id = '{$data['siswa_id']}' AND mapel_id = '{$data['mapel_id']}'");
        $row_abs = mysqli_fetch_assoc($abs);
        $total_abs = (int) ($row_abs['total'] ?? 0);
        $kehadiran = $total_abs > 0
            ? round(((int) $row_abs['hadir'] / $total_abs) * 100, 2)
            : 0;
        $kehadiran_sql = "'$kehadiran'";

        // RUMUS NILAI AKHIR: Harian 20% + UTS 25% + UAS 35% + Kehadiran 20%
        $akhir = round(($nh * 0.20) + ($uts * 0.25) + ($uas * 0.35) + ($kehadiran * 0.20), 2);

        // Update yang konsisten dengan skema tabel nilai
        $set_kehadiran = $ada_kolom_kehadiran ? "nilai_kehadiran = $kehadiran_sql," : "";
        mysqli_query($koneksi,
            "UPDATE nilai 
             SET nilai_harian = '$nh', 
                 nilai_uts = '$uts',
                 nilai_uas = '$uas',
                 $kolom_uh = '$nh',
                 $set_kehadiran
                 nilai_akhir = '$akhir'
             WHERE id = '$id'");


        header("Location: index.php?success=Nilai berhasil diupdate");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

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
                    <div class="col-md-3">
                        <small class="text-muted">Siswa</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_siswa'] ?? 'Tidak Diketahui') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">NIS</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nis'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Mata Pelajaran</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_mapel'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Kelas</small>
                        <div class="fw-bold"><?= htmlspecialchars($data['nama_kelas'] ?? 'Tanpa Kelas') ?></div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Semester</small>
                        <div class="fw-bold">Semester <?= htmlspecialchars($data['semester'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Rumus:</strong>
                (Harian × 20%) + (UTS × 25%) + (UAS × 35%) + (Kehadiran × 20%)
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai Harian <small class="text-muted">(bobot 30%)</small>
                            </label>
                            <input type="number" name="nilai_harian" class="form-control"
                                   value="<?= $data['nilai_harian'] ?? $data['nilai_uh'] ?? 0 ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai UTS <small class="text-muted">(bobot 30%)</small>
                            </label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   value="<?= $data['nilai_uts'] ?? 0 ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai UAS <small class="text-muted">(bobot 40%)</small>
                            </label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   value="<?= $data['nilai_uas'] ?? 0 ?>"
                                   min="0" max="100" step="0.01"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preview Nilai Akhir</label>
                            <div class="input-group">
                                <input type="text" id="preview_akhir"
                                       class="form-control fw-bold"
                                       value="<?= $data['nilai_akhir'] ?? 0 ?>" readonly>
                                <span class="input-group-text fw-bold" id="preview_predikat">
                                    <?php
                                    $na = $data['nilai_akhir'] ?? 0;
                                    if ($na >= 90) echo 'A';
                                    elseif ($na >= 80) echo 'B';
                                    elseif ($na >= 70) echo 'C';
                                    elseif ($na >= 60) echo 'D';
                                    else echo 'E';
                                    ?>
                                </span>
                            </div>
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
                                <i class="fas fa-exclamation-triangle"></i> Belum ada data absensi untuk siswa & mapel ini.
                            </div>
                            <?php endif; ?>
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
window.onload = function() { hitungNilaiAkhir(); }

function hitungNilaiAkhir() {
    const nh        = parseFloat(document.querySelector('[name="nilai_harian"]').value) || 0;
    const uts       = parseFloat(document.querySelector('[name="nilai_uts"]').value) || 0;
    const uas       = parseFloat(document.querySelector('[name="nilai_uas"]').value) || 0;
    const kehadiran = <?= json_encode($kehadiran_info['persen']) ?>; // dari absensi, tetap (tidak diubah lewat form ini)
    const akhir = Math.round(((nh * 0.20) + (uts * 0.25) + (uas * 0.35) + (kehadiran * 0.20)) * 100) / 100;

    let predikat = 'E';
    if (akhir >= 90)      predikat = 'A';
    else if (akhir >= 80) predikat = 'B';
    else if (akhir >= 70) predikat = 'C';
    else if (akhir >= 60) predikat = 'D';

    document.getElementById('preview_akhir').value      = akhir;
    document.getElementById('preview_predikat').textContent = predikat;
    document.getElementById('preview_akhir').className  =
        `form-control fw-bold ${akhir >= 75 ? 'text-success' : 'text-danger'}`;
}
</script>

<?php include '../../includes/footer.php'; ?>