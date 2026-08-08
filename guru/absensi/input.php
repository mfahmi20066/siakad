<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekGuru();
$title = "Input Absensi";

// Tahun ajaran aktif (source of truth) — bukan POST/date('Y').
$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

// SINKRONISASI SESSION: Menggunakan id_ref untuk ID Guru
$gid = $_SESSION['id_ref'];

// Hanya tampilkan kelas yang diajar guru ini berdasarkan tabel jadwal
$kelas_list = mysqli_query($koneksi,
    "SELECT DISTINCT k.*
     FROM kelas k
     JOIN jadwal j ON j.kelas_id = k.id
     WHERE j.guru_id = '$gid'
     ORDER BY k.tingkat, k.nama_kelas");

if (isset($_POST['kelas_id']) && !isset($_POST['simpan'])) {
    $kid         = mysqli_real_escape_string($koneksi, $_POST['kelas_id']);
    $tgl         = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $siswa_kelas = mysqli_query($koneksi, "SELECT * FROM siswa WHERE kelas_id='$kid' ORDER BY nama");
}

if (isset($_POST['simpan'])) {
    $kid         = mysqli_real_escape_string($koneksi, $_POST['kelas_id']);
    $tgl         = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $siswa_ids   = $_POST['siswa_id'];
    $statuses    = $_POST['status'];
    $keterangans = $_POST['keterangan'];

    // Ambil mapel_id secara otomatis dari jadwal guru untuk kelas ini
    $q_mapel = mysqli_query($koneksi, "SELECT mapel_id FROM jadwal WHERE kelas_id='$kid' AND guru_id='$gid' LIMIT 1");
    $res_mapel = mysqli_fetch_assoc($q_mapel);
    $mapel_id = $res_mapel ? (int)$res_mapel['mapel_id'] : 0;

    // Siapkan nilai SQL untuk mapel_id (0 = tanpa mapel / presensi harian)
    $mapel_sql = $mapel_id > 0 ? "'$mapel_id'" : "NULL";

    // Validasi relasional: kelas harus ada & pada tahun ajaran aktif.
    $kelasTa = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT tahun_ajaran_id FROM kelas WHERE id=".(int)$kid));
    if ($taId === null || $taTahun === '') {
        $error_msg = "Tidak ada tahun ajaran aktif. Tetapkan tahun aktif di Modul Tahun Ajaran.";
        $simpan_ok = false;
    } elseif (!$kelasTa) {
        $error_msg = "Kelas tidak ditemukan.";
        $simpan_ok = false;
    } elseif ($kelasTa['tahun_ajaran_id'] !== null && (int)$kelasTa['tahun_ajaran_id'] !== $taId) {
        $error_msg = "Kelas terpilih bukan pada tahun ajaran aktif ($taTahun).";
        $simpan_ok = false;
    }

    $simpan_ok  = isset($simpan_ok) ? $simpan_ok : true;
    $error_msg  = $error_msg ?? '';
    $total_save = 0;

    if ($simpan_ok) {
    foreach ($siswa_ids as $i => $sid) {
        $sid_clean = mysqli_real_escape_string($koneksi, $sid);
        $st        = mysqli_real_escape_string($koneksi, $statuses[$i]);
        $ket       = mysqli_real_escape_string($koneksi, $keterangans[$i]);

        // Validasi siswa berada di kelas terpilih.
        $srow = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT kelas_id FROM siswa WHERE id='$sid_clean'"));
        if (!$srow || $srow['kelas_id'] != $kid) continue;

        // Cek apakah sudah ada absensi siswa ini pada tanggal & kelas yang sama
        // (khusus saat mapel_id NULL, unique key MySQL tidak bisa mencegah duplikasi)
        $cek = mysqli_query($koneksi,
            "SELECT id FROM absensi
             WHERE siswa_id='$sid_clean' AND tanggal='$tgl' AND kelas_id='$kid'
               AND mapel_id " . ($mapel_id > 0 ? "='$mapel_id'" : "IS NULL") . " LIMIT 1");
        $ada = mysqli_fetch_assoc($cek);

        if ($ada) {
            $q_insert = mysqli_query($koneksi,
                "UPDATE absensi SET status='$st', keterangan='$ket', tahun_ajaran_id='$taId' WHERE id='{$ada['id']}'");
        } else {
            $q_insert = mysqli_query($koneksi,
                "INSERT INTO absensi (siswa_id, mapel_id, kelas_id, tanggal, status, keterangan, tahun_ajaran_id)
                 VALUES ('$sid_clean', $mapel_sql, '$kid', '$tgl', '$st', '$ket', '$taId')");
        }

        if (!$q_insert) {
            $simpan_ok = false;
            $error_msg = mysqli_error($koneksi);
            break;
        }
        $total_save++;
    }
    }

    if ($simpan_ok) {
        header("Location: index.php?success=Absensi tanggal $tgl berhasil disimpan ($total_save siswa)");
        exit();
    } else {
        // Jika gagal, jangan redirect sukses palsu — tampilkan pesan error
        $simpan_error = "Gagal menyimpan absensi tanggal $tgl: " . htmlspecialchars($error_msg);
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-clipboard-check text-gold me-2"></i>Input Absensi</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($simpan_error)): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= $simpan_error ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i> Pilih Kelas & Tanggal
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Kelas <span class="text-danger">*</span></label>
                        <select name="kelas_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php if ($kelas_list): ?>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= (isset($kid) && $kid == $k['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= isset($tgl) ? htmlspecialchars($tgl) : date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
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
            <i class="fas fa-list-check"></i> Isi Kehadiran Siswa
            <span class="badge bg-info ms-2">
                <?= mysqli_num_rows($siswa_kelas) ?> Siswa
            </span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="kelas_id" value="<?= htmlspecialchars($kid) ?>">
                <input type="hidden" name="tanggal"  value="<?= htmlspecialchars($tgl) ?>">

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
                    <table class="table table-hover align-middle">
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
                        <td><?= htmlspecialchars($s['nis']) ?></td>
                        <td><?= htmlspecialchars($s['nama']) ?></td>
                        <td>
                            <input type="hidden" name="siswa_id[]" value="<?= $s['id'] ?>">
                            <select name="status[]" class="form-select form-select-sm st">
                                <option value="Hadir" selected>Hadir</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin</option>
                                <option value="Alpa">Alpa</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="keterangan[]"
                                   class="form-control form-control-sm"
                                   placeholder="Opsional">
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
                <a href="input.php" class="btn btn-secondary">Ulangi</a>
            </form>
        </div>
    </div>
    <?php elseif (isset($siswa_kelas)): ?>
    <div class="card mt-3">
        <div class="card-body text-center py-4 text-muted">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p class="mb-0">Tidak ada data siswa ditemukan di kelas ini.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function setSemuaStatus(status) {
    document.querySelectorAll('.st').forEach(function(sel) {
        sel.value = status;
    });
}
</script>

<?php include '../../includes/footer.php'; ?>