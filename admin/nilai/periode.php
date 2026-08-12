<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
include '../../config/helper_periode_nilai.php';
cekAdmin();
$title = "Periode Nilai";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

$q_peng = mysqli_query($koneksi, "SELECT semester FROM pengaturan WHERE id = 1");
$sem_aktif = '1';
if ($q_peng && $rp = mysqli_fetch_assoc($q_peng)) {
    if (preg_match('/\d+/', (string)$rp['semester'], $m)) $sem_aktif = $m[0];
}
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : (int)$sem_aktif;
if ($semester !== 1 && $semester !== 2) $semester = 1;
$semester = (string)$semester;

$userId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi     = $_POST['aksi'] ?? '';
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;

    if ($kelas_id <= 0) {
        header("Location: periode.php?semester=$semester&error=" . urlencode("Kelas tidak valid."));
        exit;
    }
    if ($taId === null) {
        header("Location: periode.php?semester=$semester&error=" . urlencode("Tidak ada tahun ajaran aktif."));
        exit;
    }

    $nama_kelas = '';
    $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $kelas_id");
    if ($qk && $rk = mysqli_fetch_assoc($qk)) $nama_kelas = $rk['nama_kelas'];

    if ($aksi === 'buka' || $aksi === 'buka_kembali') {
        mysqli_query($koneksi,
            "INSERT INTO periode_nilai (tahun_ajaran_id, semester, kelas_id, status, dibuka_oleh, dibuka_pada)
             VALUES ($taId, $semester, $kelas_id, 'open', $userId, NOW())
             ON DUPLICATE KEY UPDATE
               status = 'open',
               dibuka_oleh = $userId,
               dibuka_pada = NOW(),
               dikunci_oleh = NULL,
               dikunci_pada = NULL");
        catatLogPeriode($koneksi, $aksi, [
            'tahun_ajaran_id' => $taId,
            'semester' => (int)$semester,
            'kelas_id' => $kelas_id,
            'nama_kelas' => $nama_kelas,
        ], $userId);
        notifikasiPeriodeKeGuru($koneksi, $taId, $kelas_id, $nama_kelas, (int)$semester, 'buka');
        header("Location: periode.php?semester=$semester&success=" . urlencode("Periode nilai kelas $nama_kelas dibuka. Guru dapat menginput nilai."));
        exit;
    }

    if ($aksi === 'kunci') {
        // Kunci TANPA syarat kelengkapan: admin bebas kunci kapan pun.
        // Kelengkapan hanya informasi (monitoring + dialog konfirmasi), bukan penghalang.
        $rekap = rekapKelengkapanKelas($koneksi, $taId, (int)$semester, $kelas_id);
        mysqli_query($koneksi,
            "INSERT INTO periode_nilai (tahun_ajaran_id, semester, kelas_id, status, dikunci_oleh, dikunci_pada)
             VALUES ($taId, $semester, $kelas_id, 'locked', $userId, NOW())
             ON DUPLICATE KEY UPDATE
               status = 'locked',
               dikunci_oleh = $userId,
               dikunci_pada = NOW(),
               dibuka_oleh = NULL,
               dibuka_pada = NULL");
        catatLogPeriode($koneksi, 'kunci', [
            'tahun_ajaran_id' => $taId,
            'semester' => (int)$semester,
            'kelas_id' => $kelas_id,
            'nama_kelas' => $nama_kelas,
        ], $userId);
        notifikasiPeriodeKeGuru($koneksi, $taId, $kelas_id, $nama_kelas, (int)$semester, 'kunci');
        header("Location: periode.php?semester=$semester&success=" . urlencode("Nilai kelas $nama_kelas terkunci. Guru tidak dapat mengubah nilai."));
        exit;
    }

    header("Location: periode.php?semester=$semester&error=" . urlencode("Aksi tidak dikenali."));
    exit;
}

$rekap = ($taId !== null)
    ? rekapKelengkapanSemuaKelas($koneksi, $taId, (int)$semester)
    : ['grand' => ['total' => 0, 'terisi' => 0, 'kosong' => 0, 'lengkap' => 0, 'belum' => 0, 'persen' => 0], 'kelas' => []];

$detail_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$siswa_kurang = [];
$nama_detail  = '';
if ($detail_kelas > 0 && $taId !== null) {
    $siswa_kurang = siswaBelumLengkapKelas($koneksi, $taId, (int)$semester, $detail_kelas);
    $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $detail_kelas");
    if ($qk && $rk = mysqli_fetch_assoc($qk)) $nama_detail = $rk['nama_kelas'];
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>
<div class="main-content">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-lock-open text-icon me-2"></i>Periode Penginputan Nilai</h4>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list"></i> Daftar Nilai
            </a>
            <a href="input.php" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Input Nilai
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= e($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="fas fa-info-circle"></i>
        <span>
            Alur: <strong>Admin buka penginputan</strong> &rarr; guru menginput nilai &rarr; <strong>admin kunci nilai</strong> (guru tidak bisa mengubah) &rarr; admin review / buka kembali bila perlu.
            Kunci hanya bisa dilakukan jika nilai kelas <strong>100% lengkap</strong>.
        </span>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tahun Ajaran Aktif</label>
                    <input type="text" class="form-control" value="<?= e($taTahun ?: '-') ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
                        <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2 (Genap)</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body py-3">
                    <div class="fw-bold fs-4 text-primary"><?= number_format($rekap['grand']['total']) ?></div>
                    <small class="text-muted">Total Nilai (Siswa x Mapel)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body py-3">
                    <div class="fw-bold fs-4 text-success"><?= number_format($rekap['grand']['terisi']) ?></div>
                    <small class="text-muted">Terisi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body py-3">
                    <div class="fw-bold fs-4 text-danger"><?= number_format($rekap['grand']['kosong']) ?></div>
                    <small class="text-muted">Kosong</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body py-3">
                    <div class="fw-bold fs-4 text-warning"><?= $rekap['grand']['persen'] ?>%</div>
                    <small class="text-muted">Progress Kelengkapan</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-tasks"></i> Monitoring Kelengkapan per Kelas</span>
            <span class="badge bg-<?= $rekap['grand']['kosong'] > 0 ? 'warning text-dark' : 'success' ?>">
                <?= $rekap['grand']['kosong'] > 0 ? '&#9888; BELUM LENGKAP' : 'SEMUA LENGKAP' ?>
            </span>
        </div>
        <div class="card-body">
            <div class="progress mb-3" style="height:12px;">
                <div class="progress-bar bg-<?= $rekap['grand']['persen'] >= 100 ? 'success' : 'warning' ?>"
                     style="width: <?= min(100, $rekap['grand']['persen']) ?>%;">
                    <?= $rekap['grand']['persen'] ?>%
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th>Kelas</th>
                            <th class="text-center">Mapel</th>
                            <th class="text-center">Siswa</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Terisi</th>
                            <th class="text-center">Kosong</th>
                            <th class="text-center">Kelengkapan</th>
                            <th class="text-center">Periode</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rekap['kelas'])): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-folder-open d-block mb-2 fs-4"></i> Tidak ada kelas dengan siswa & mapel terdaftar.
                            </td>
                        </tr>
                    <?php else: foreach ($rekap['kelas'] as $rk): ?>
                        <?php
                        $persen_k = $rk['total'] > 0 ? round($rk['terisi'] / $rk['total'] * 100, 1) : 0;
                        $is_open  = $rk['status_periode'] === 'open';
                        ?>
                        <tr>
                            <td class="text-nowrap">
                                <a href="periode.php?semester=<?= $semester ?>&kelas_id=<?= $rk['kelas_id'] ?>">
                                    <?= e($rk['nama_kelas']) ?>
                                </a>
                            </td>
                            <td class="text-center"><?= $rk['mapel'] ?></td>
                            <td class="text-center"><?= $rk['siswa'] ?></td>
                            <td class="text-center"><?= number_format($rk['total']) ?></td>
                            <td class="text-center text-success"><?= number_format($rk['terisi']) ?></td>
                            <td class="text-center <?= $rk['kosong'] > 0 ? 'text-danger' : 'text-muted' ?>"><?= number_format($rk['kosong']) ?></td>
                            <td class="text-center">
                                <?php if ($rk['lengkap']): ?>
                                <span class="badge bg-success">Lengkap</span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">&#9888; <?= $persen_k ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_open): ?>
                                <span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Open</span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <?php if ($is_open): ?>
                                <form method="POST" class="d-inline" id="form-kunci-<?= $rk['kelas_id'] ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="kunci">
                                    <input type="hidden" name="kelas_id" value="<?= $rk['kelas_id'] ?>">
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="kunciPeriode(<?= $rk['kelas_id'] ?>, <?= e(json_encode($rk['nama_kelas']), ENT_QUOTES) ?>, <?= (int)$rk['kosong'] ?>)">
                                        <i class="fas fa-lock"></i> Kunci
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="buka_kembali">
                                    <input type="hidden" name="kelas_id" value="<?= $rk['kelas_id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-unlock"></i> Buka Kembali
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($detail_kelas > 0): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users"></i> Siswa Belum Lengkap — <?= e($nama_detail) ?> (Semester <?= $semester ?>)</span>
            <a href="periode.php?semester=<?= $semester ?>" class="btn btn-secondary btn-sm">Tutup</a>
        </div>
        <div class="card-body">
            <?php if (empty($siswa_kurang)): ?>
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle"></i> Semua siswa di kelas ini sudah lengkap nilainya.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">Terisi</th>
                            <th class="text-center">Total Mapel</th>
                            <th class="text-center">Kosong</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; foreach ($siswa_kurang as $sk): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($sk['nis']) ?></td>
                            <td><?= e($sk['nama']) ?></td>
                            <td class="text-center text-success"><?= $sk['terisi'] ?></td>
                            <td class="text-center"><?= $sk['total'] ?></td>
                            <td class="text-center text-danger fw-bold"><?= $sk['kosong'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function kunciPeriode(kelasId, namaKelas, kosong) {
    var teks = 'Nilai kelas ' + namaKelas + ' akan dikunci sehingga guru tidak dapat mengubah nilai.';
    if (kosong > 0) {
        teks += ' Masih ada ' + kosong + ' nilai kosong — guru tidak bisa mengisi nilai tersebut sampai periode dibuka kembali.';
    }
    siConfirm({
        icon: 'warning',
        title: 'Kunci Periode Nilai?',
        text: teks,
        confirmText: 'Ya, Kunci',
        cancelText: 'Batal',
        danger: true
    }).then(function (ok) {
        if (ok) document.getElementById('form-kunci-' + kelasId).submit();
    });
}
</script>

<?php include '../../includes/footer.php'; ?>