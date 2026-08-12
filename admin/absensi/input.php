<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Input Absensi";

// Tahun ajaran aktif (source of truth) — bukan POST/date('Y').
$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
$guru_list  = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

// Tahap 1: tampilkan daftar siswa setelah kelas dipilih
if (isset($_POST['kelas_id']) && !isset($_POST['simpan'])) {
    $kid         = $_POST['kelas_id'];
    $gid         = $_POST['guru_id'];
    $tgl         = $_POST['tanggal'];
    $siswa_kelas = mysqli_query($koneksi, "SELECT * FROM siswa WHERE kelas_id='$kid' ORDER BY nama");
}

// Tahap 2: simpan absensi
if (isset($_POST['simpan'])) {
    $kid         = $_POST['kelas_id'];
    $tgl         = $_POST['tanggal'];
    $siswa_ids   = $_POST['siswa_id'];
    $statuses    = $_POST['status'];
    $keterangans = $_POST['keterangan'];

    // Validasi relasional: kelas harus ada & pada tahun ajaran aktif.
    $kelasTa = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT tahun_ajaran_id FROM kelas WHERE id=".(int)$kid));
    if ($taId === null || $taTahun === '') {
        $error = "Tidak ada tahun ajaran aktif. Tetapkan tahun aktif di Modul Tahun Ajaran.";
    } elseif (!$kelasTa) {
        $error = "Kelas tidak ditemukan.";
    } elseif ($kelasTa['tahun_ajaran_id'] !== null && (int)$kelasTa['tahun_ajaran_id'] !== $taId) {
        $error = "Kelas terpilih bukan pada tahun ajaran aktif ($taTahun).";
    } else {
        foreach ($siswa_ids as $i => $sid) {
            // Pastikan siswa benar-benar berada di kelas ini (validasi relasi).
            $srow = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT kelas_id FROM siswa WHERE id=".(int)$sid));
            if (!$srow || $srow['kelas_id'] != $kid) continue;

            $st  = $statuses[$i];
            $ket = mysqli_real_escape_string($koneksi, $keterangans[$i]);

            mysqli_query($koneksi,
                "INSERT INTO absensi (siswa_id, kelas_id, tanggal, status, keterangan, tahun_ajaran_id)
                 VALUES ('$sid', '$kid', '$tgl', '$st', '$ket', '$taId')
                 ON DUPLICATE KEY UPDATE status='$st', keterangan='$ket', tahun_ajaran_id='$taId'") or die(mysqli_error($koneksi));
        }

        header("Location: index.php?success=Absensi tanggal $tgl berhasil disimpan");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-clipboard-check text-icon me-2"></i>Input Absensi</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i>
            Langkah 1 — Pilih Kelas, Guru & Tanggal
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Kelas <span class="text-danger">*</span></label>
                        <select name="kelas_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                            <option value="<?= $k['id'] ?>"
                                <?= (isset($kid) && $kid == $k['id']) ? 'selected' : '' ?>>
                                <?= e($k['nama_kelas']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Guru <span class="text-danger">*</span></label>
                        <select name="guru_id" class="form-select" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                            <option value="<?= $g['id'] ?>"
                                <?= (isset($gid) && $gid == $g['id']) ? 'selected' : '' ?>>
                                <?= e($g['nama']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= isset($tgl) ? $tgl : date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-users"></i> Tampilkan Siswa
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($siswa_kelas) && mysqli_num_rows($siswa_kelas) > 0): ?>
    <div class="card mt-3">
        <div class="card-header">
            <i class="fas fa-list-check"></i>
            Langkah 2 — Isi Status Kehadiran
            <span class="badge bg-info ms-2">
                <?= mysqli_num_rows($siswa_kelas) ?> Siswa
            </span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="kelas_id" value="<?= $kid ?>">
                <input type="hidden" name="guru_id"  value="<?= $gid ?>">
                <input type="hidden" name="tanggal"  value="<?= $tgl ?>">

                <div class="mb-3">
                    <button type="button" class="btn btn-success btn-sm"
                            onclick="setSemuaStatus('Hadir')">
                        <i class="fas fa-check-double"></i> Semua Hadir
                    </button>
                    <button type="button" class="btn btn-danger btn-sm ms-1"
                            onclick="setSemuaStatus('Alpa')">
                        <i class="fas fa-times"></i> Semua Alpa
                    </button>
                </div>

                <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th style="width:150px">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($s = mysqli_fetch_assoc($siswa_kelas)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $s['nis'] ?></td>
                        <td><?= e($s['nama']) ?></td>
                        <td>
                            <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                            <select name="status[]" class="form-select form-select-sm status-select">
                                <option value="Hadir" selected>Hadir</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin</option>
                                <option value="Alpa">Alpa</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="keterangan[]"
                                   class="form-control form-control-sm"
                                   placeholder="Keterangan (opsional)">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>

                <hr>
                <button type="submit" name="simpan" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan Absensi
                </button>
                <a href="input.php" class="btn btn-secondary">Ulangi Pilih Kelas</a>
            </form>
        </div>
    </div>

    <script>
    function setSemuaStatus(status) {
        document.querySelectorAll('.status-select').forEach(function(sel) {
            sel.value = status;
        });
    }
    </script>

    <?php elseif (isset($siswa_kelas) && mysqli_num_rows($siswa_kelas) == 0): ?>
    <div class="alert alert-warning mt-3">
        <i class="fas fa-exclamation-triangle"></i>
        Tidak ada siswa di kelas ini.
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>