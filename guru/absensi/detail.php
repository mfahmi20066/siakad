<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Detail Absensi";

// SINKRONISASI SESSION: Menggunakan id_ref sebagai ID Guru yang sah
$gid = $_SESSION['id_ref'];

$tgl = isset($_GET['tanggal'])  ? mysqli_real_escape_string($koneksi, $_GET['tanggal'])  : '';
$kid = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';

if (!$tgl || !$kid) {
    header("Location: index.php");
    exit();
}

// Validasi: pastikan guru benar-benar mengajar kelas ini
$cek = mysqli_query($koneksi,
    "SELECT j.kelas_id, k.nama_kelas, mp.nama_mapel
     FROM jadwal j
     JOIN kelas k ON k.id = j.kelas_id
     LEFT JOIN mata_pelajaran mp ON mp.id = j.mapel_id
     WHERE j.kelas_id = '$kid' AND j.guru_id = '$gid'
     LIMIT 1");
$info = mysqli_fetch_assoc($cek);

if (!$info) {
    header("Location: index.php");
    exit();
}

// Ambil detail absensi siswa pada tanggal & kelas tersebut
$data = mysqli_query($koneksi,
    "SELECT a.*, s.nama, s.nis
     FROM absensi a
     JOIN siswa s ON a.siswa_id = s.id
     JOIN jadwal j ON a.kelas_id = j.kelas_id
                   AND j.guru_id = '$gid'
                   AND (a.mapel_id IS NULL OR a.mapel_id = j.mapel_id)
     WHERE a.tanggal = '$tgl' AND a.kelas_id = '$kid'
     GROUP BY a.id
     ORDER BY s.nama");

if (!$data) {
    die("Query Error: " . mysqli_error($koneksi));
}

$nama_kelas = htmlspecialchars($info['nama_kelas']);
$nama_mapel = htmlspecialchars($info['nama_mapel'] ?? '-');

$hari_id = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];

function normalStatus($st) {
    if ($st == 'H') return 'Hadir';
    if ($st == 'S') return 'Sakit';
    if ($st == 'I') return 'Izin';
    if ($st == 'A') return 'Alpa';
    return $st;
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-clipboard-list text-gold me-2"></i>Detail Absensi</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <i class="fas fa-calendar-day"></i>
                <?= tanggal_indo($tgl, true) ?>
                <span class="badge bg-secondary ms-1"><?= $nama_kelas ?></span>
                <span class="badge bg-info ms-1"><?= $nama_mapel ?></span>
                <span class="badge bg-primary ms-1">
                    <?= mysqli_num_rows($data) ?> Siswa
                </span>
            </span>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="alert alert-info text-center m-3 mb-0">
                <i class="fas fa-info-circle"></i> Belum ada data absensi pada tanggal ini.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th style="width:130px">Status</th>
                            <th>Keterangan</th>
                            <th style="width:80px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <?php
                        $status_text = normalStatus($r['status']);
                        $badge = ['Hadir'=>'success','Sakit'=>'warning','Izin'=>'info','Alpa'=>'danger'];
                        $bg_color = isset($badge[$status_text]) ? $badge[$status_text] : 'secondary';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['nis']) ?></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-<?= $bg_color ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($r['keterangan'] ?? '') ?></small>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

