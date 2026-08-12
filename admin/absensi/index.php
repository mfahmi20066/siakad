<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Absensi";

$gid = isset($_GET['guru_id'])  ? mysqli_real_escape_string($koneksi, $_GET['guru_id'])  : '';
$kid = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';
$tgl = isset($_GET['tanggal'])  ? mysqli_real_escape_string($koneksi, $_GET['tanggal'])  : '';

function normalStatus($st) {
    if ($st == 'H') return 'Hadir';
    if ($st == 'S') return 'Sakit';
    if ($st == 'I') return 'Izin';
    if ($st == 'A') return 'Alpa';
    return $st;
}

// ============================================================
// LEVEL 3: Guru + Tanggal + Kelas -> daftar siswa (bisa edit)
// ============================================================
if ($gid && $tgl && $kid) {
    $info = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT g.nama AS nama_guru, k.nama_kelas
         FROM guru g
         JOIN kelas k ON k.id = '$kid'
         WHERE g.id = '$gid'
         LIMIT 1"));

    $data = mysqli_query($koneksi,
        "SELECT a.*, s.nama, s.nis
         FROM absensi a
         JOIN siswa s ON s.id = a.siswa_id
         WHERE a.tanggal = '$tgl' AND a.kelas_id = '$kid'
           AND EXISTS (SELECT 1 FROM kelas_mapel_guru kmg
                       WHERE kmg.kelas_id = '$kid'
                         AND kmg.guru_id = '$gid'
                         AND (a.mapel_id IS NULL OR a.mapel_id = kmg.mapel_id))
         GROUP BY a.id
         ORDER BY s.nama");

    if (!$data) die("Query Error: " . mysqli_error($koneksi));
    $m = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT GROUP_CONCAT(DISTINCT COALESCE(mp.nama_mapel,'Umum') ORDER BY mp.nama_mapel SEPARATOR ', ') AS nama_mapel
         FROM absensi a
         LEFT JOIN mata_pelajaran mp ON mp.id = a.mapel_id
         WHERE a.tanggal = '$tgl' AND a.kelas_id = '$kid'"));
    $nama_guru  = $info ? $info['nama_guru'] : '-';
    $nama_kelas = $info ? $info['nama_kelas'] : '-';
    $nama_mapel = $m && $m['nama_mapel'] ? $m['nama_mapel'] : '-';
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-clipboard-list text-icon me-2"></i>Detail Absensi</h4>
        <a href="index.php?guru_id=<?= $gid ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Tanggal
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <i class="fas fa-calendar-day"></i>
                <?= tanggal_indo($tgl, true) ?>
                <span class="badge bg-info ms-1"><?= $nama_mapel ?></span>
                <span class="badge bg-secondary ms-1"><?= $nama_kelas ?></span>
                <span class="badge bg-success ms-1">Guru: <?= e($nama_guru) ?></span>
                <span class="badge bg-primary ms-1"><?= mysqli_num_rows($data) ?> Siswa</span>
            </span>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="alert alert-info text-center m-3 mb-0">
                <i class="fas fa-info-circle"></i> Belum ada data absensi.
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
                            <th style="width:120px">Aksi</th>
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
                        <td><?= e($r['nis']) ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-<?= $bg_color ?>"><?= $status_text ?></span>
                        </td>
                        <td><small class="text-muted"><?= e($r['keterangan'] ?? '') ?></small></td>
                        <td>
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
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
<?php exit; } ?>

<?php
// ============================================================
// LEVEL 2: Guru -> daftar tanggal absensi + kelas
// ============================================================
if ($gid) {
    $guru = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM guru WHERE id='$gid'"));
    if (!$guru) { header("Location: index.php"); exit(); }

    $data = mysqli_query($koneksi,
        "SELECT a.tanggal, a.kelas_id, k.nama_kelas,
                GROUP_CONCAT(DISTINCT COALESCE(mp.nama_mapel,'Umum') ORDER BY mp.nama_mapel SEPARATOR ', ') AS nama_mapel,
                COUNT(a.id) AS jml_siswa,
                SUM(a.status='Hadir' OR a.status='H') AS hadir,
                SUM(a.status='Sakit' OR a.status='S') AS sakit,
                SUM(a.status='Izin'  OR a.status='I') AS izin,
                SUM(a.status='Alpa'  OR a.status='A') AS alpa
         FROM absensi a
         JOIN kelas k ON k.id = a.kelas_id
         LEFT JOIN mata_pelajaran mp ON mp.id = a.mapel_id
         WHERE EXISTS (SELECT 1 FROM kelas_mapel_guru kmg
                       WHERE kmg.kelas_id = a.kelas_id
                         AND kmg.guru_id = '$gid'
                         AND (a.mapel_id IS NULL OR a.mapel_id = kmg.mapel_id))
         GROUP BY a.tanggal, a.kelas_id
         ORDER BY a.tanggal DESC, k.nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?> 

<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-calendar-week text-icon me-2"></i>Absensi Guru</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Guru
        </a>
    </div>

    <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="fas fa-user-circle fs-4"></i>
        <div>
            <strong><?= e($guru['nama'] ?? $guru['nama_lengkap']) ?></strong><br>
            <small>Pilih tanggal untuk melihat detail kehadiran siswa.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Tanggal Absensi
            <span class="badge bg-primary ms-2"><?= mysqli_num_rows($data) ?> Tanggal</span>
        </div>
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="alert alert-info text-center m-3 mb-0">
                <i class="fas fa-info-circle"></i> Guru ini belum memiliki data absensi.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Tanggal</th>
                            <th style="width:130px">Kelas</th>
                            <th style="width:130px">Mapel</th>
                            <th style="width:90px">Siswa</th>
                            <th class="text-center" style="width:70px">Hadir</th>
                            <th class="text-center" style="width:70px">Sakit</th>
                            <th class="text-center" style="width:70px">Izin</th>
                            <th class="text-center" style="width:70px">Alpa</th>
                            <th style="width:90px">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <i class="fas fa-calendar-day text-primary me-1"></i>
                            <strong><?= tanggal_indo($r['tanggal'], true) ?></strong>
                        </td>
                        <td><span class="badge bg-secondary"><?= e($r['nama_kelas']) ?></span></td>
                        <td><span class="badge bg-info"><?= e($r['nama_mapel'] ?? '-') ?></span></td>
                        <td><span class="badge bg-primary"><?= $r['jml_siswa'] ?></span></td>
                        <td class="text-center"><span class="badge bg-success"><?= (int)$r['hadir'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= (int)$r['sakit'] ?></span></td>
                        <td class="text-center"><span class="badge bg-info"><?= (int)$r['izin'] ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= (int)$r['alpa'] ?></span></td>
                        <td>
                            <a href="index.php?guru_id=<?= $gid ?>&tanggal=<?= urlencode($r['tanggal']) ?>&kelas_id=<?= $r['kelas_id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Lihat</a>
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
<?php exit; } ?>

<?php
// ============================================================
// LEVEL 1: Daftar Guru yang memiliki absensi
// ============================================================
$guru_list = mysqli_query($koneksi,
    "SELECT g.id, g.nama, g.nip,
            COUNT(DISTINCT a.id) AS jml_data,
            COUNT(DISTINCT CONCAT(a.tanggal,'-',a.kelas_id)) AS jml_tanggal
     FROM guru g
     JOIN kelas_mapel_guru kmg ON kmg.guru_id = g.id
     JOIN absensi a ON a.kelas_id = kmg.kelas_id
                   AND (a.mapel_id IS NULL OR a.mapel_id = kmg.mapel_id)
     GROUP BY g.id
     ORDER BY g.nama");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>

<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-clipboard-check text-icon me-2"></i>Absensi Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto"><i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="fas fa-users"></i> Pilih Guru</div>
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($guru_list) == 0): ?>
            <div class="alert alert-info text-center m-3 mb-0">
                <i class="fas fa-info-circle"></i> Belum ada data absensi. Guru akan tampil setelah absensi diinput.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Nama Guru</th>
                            <th style="width:130px">NIP</th>
                            <th style="width:120px">Tanggal</th>
                            <th style="width:120px">Data</th>
                            <th style="width:100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($g = mysqli_fetch_assoc($guru_list)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><i class="fas fa-user-tie text-primary me-1"></i> <strong><?= e($g['nama'] ?? '') ?></strong></td>
                        <td><?= e($g['nip'] ?? '-') ?></td>
                        <td><span class="badge bg-primary"><?= $g['jml_tanggal'] ?> Tanggal</span></td>
                        <td><span class="badge bg-secondary"><?= $g['jml_data'] ?> Data</span></td>
                        <td><a href="index.php?guru_id=<?= $g['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Lihat</a></td>
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

