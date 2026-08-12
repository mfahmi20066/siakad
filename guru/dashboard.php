<?php
include '../config/koneksi.php';
include '../config/session.php';
cekGuru();
$title = "Dashboard Guru";

// ── FIX: ganti $gid → $id_guru ──────────────────────────────
$id_guru = $_SESSION['id_ref'] ?? $_SESSION['guru_id'] ?? $_SESSION['user_id'] ?? 0;

$guru = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM guru WHERE id='$id_guru'"));
$guru = $guru ?: [];
$nama = $_SESSION['nama'] ?? ($guru['nama_lengkap'] ?? ($guru['nama'] ?? 'Guru'));

$jml_jadwal = mysqli_fetch_row(mysqli_query($koneksi,
              "SELECT COUNT(*) FROM jadwal WHERE guru_id='$id_guru'"))[0] ?? 0;
$jml_mapel  = mysqli_fetch_row(mysqli_query($koneksi,
              "SELECT COUNT(DISTINCT mapel_id) FROM kelas_mapel_guru WHERE guru_id='$id_guru'"))[0] ?? 0;
$jml_kelas  = mysqli_fetch_row(mysqli_query($koneksi,
              "SELECT COUNT(DISTINCT kelas_id) FROM kelas_mapel_guru WHERE guru_id='$id_guru'"))[0] ?? 0;

$pengumuman = mysqli_query($koneksi,
              "SELECT * FROM pengumuman ORDER BY tanggal DESC LIMIT 5");

// Jadwal hari ini
$hari_map = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
             'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hari_ini = date('l');
$hari_id  = $hari_map[$hari_ini] ?? '';

$jadwal_hari_ini = mysqli_query($koneksi,
    "SELECT j.*, k.nama_kelas, m.nama_mapel
     FROM jadwal j
     JOIN kelas k ON j.kelas_id = k.id
     JOIN mata_pelajaran m ON j.mapel_id = m.id
     WHERE j.guru_id = '$id_guru' AND k.status = 'aktif' AND j.hari = '$hari_id'
     ORDER BY j.jam_mulai");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_guru.php'; ?>
<?php include '../includes/topbar_guru.php'; ?>


<div class="main-content">
        <!-- Selamat Datang -->
    <div class="welcome-banner mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3 p-4"
         style="background:linear-gradient(135deg,#0D2540 0%,#163A63 55%,#2C5A8F 100%);border-radius:18px;box-shadow:0 18px 44px rgba(13,37,64,.14);border:1px solid rgba(255,255,255,.08);">
        <div>
            <h4 class="mb-1 fw-bold" style="color:#fff;font-size:22px;">
                <i class="fas fa-hand-sparkles me-2" style="color:#fff;"></i>
                Selamat datang, <?= e($nama) ?>!
            </h4>
            <p class="mb-0" style="color:rgba(255,255,255,.78);font-size:13.5px;">
                Sistem Informasi Akademik SMA Negeri 4 Palopo
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge" style="background:rgba(240,144,0,.18);color:#FFB74D;padding:8px 14px;font-size:12.5px;border-radius:20px;">
                <i class="fas fa-user-tie me-1"></i> Guru
            </span>
            <span class="badge" style="background:rgba(255,255,255,.12);color:#fff;padding:8px 14px;font-size:12.5px;border-radius:20px;">
                <i class="fas fa-calendar-day me-1"></i> <?= tanggal_indo_pendek(date('Y-m-d')) ?>
            </span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card bg-blue">
                <i class="fas fa-calendar stat-icon"></i>
                <div class="stat-info">
                    <h3><?= $jml_jadwal ?></h3>
                    <p>Total Jadwal</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-green">
                <i class="fas fa-book stat-icon"></i>
                <div class="stat-info">
                    <h3><?= $jml_mapel ?></h3>
                    <p>Mata Pelajaran</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-orange">
                <i class="fas fa-school stat-icon"></i>
                <div class="stat-info">
                    <h3><?= $jml_kelas ?></h3>
                    <p>Kelas Diajar</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">

        <!-- Jadwal Hari Ini -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-day"></i>
                    Jadwal Mengajar Hari Ini
                    <span class="badge bg-secondary ms-1"><?= $hari_id ?: date('l') ?></span>
                </div>
                <div class="card-body">
                    <?php if ($jadwal_hari_ini && mysqli_num_rows($jadwal_hari_ini) > 0): ?>
                        <?php while ($j = mysqli_fetch_assoc($jadwal_hari_ini)): ?>
                        <div class="d-flex align-items-center p-3 mb-3 bg-light rounded">
                            <div class="me-3 text-center" style="min-width:75px">
                                <strong class="text-primary">
                                    <?= substr($j['jam_mulai'], 0, 5) ?>
                                </strong>
                                <br>
                                <small class="text-muted">
                                    <?= substr($j['jam_selesai'], 0, 5) ?>
                                </small>
                            </div>
                            <div class="flex-grow-1">
                                <strong><?= e($j['nama_mapel']) ?></strong>
                                <br>
                                <span class="badge bg-info">
                                    <?= e($j['nama_kelas']) ?>
                                </span>
                            </div>
                            <div>
                                <a href="absensi/input.php"
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-clipboard-check"></i> Absensi
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <p>Tidak ada jadwal mengajar hari ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Akses Cepat -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Akses Cepat
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="nilai/input.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-star"></i> Input Nilai
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="absensi/input.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-clipboard-check"></i> Input Absensi
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="jadwal/index.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-calendar-alt"></i> Lihat Jadwal
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="absensi/rekap.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-chart-bar"></i> Rekap Absensi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengumuman -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bullhorn"></i> Pengumuman Terbaru
                </div>
                <div class="card-body p-0">
                    <?php if ($pengumuman && mysqli_num_rows($pengumuman) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($pengumuman)): ?>
                        <div class="p-3 border-bottom">
                            <strong class="small d-block">
                                <?= e($p['judul']) ?>
                            </strong>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                <?= tanggal_indo_pendek($p['tanggal']) ?>
                            </small>
                            <p class="small mb-0 mt-1 text-muted">
                                <?= substr(e($p['isi']), 0, 70) ?>...
                            </p>
                        </div>
                        <?php endwhile; ?>
                        <div class="p-2 text-center">
                            <a href="pengumuman/index.php" class="btn btn-link btn-sm">
                                Lihat semua →
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted p-3">Belum ada pengumuman.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
// Tampilkan widget chatbot SiA Bot di halaman ini (footer.php bersifat kondisional)
$show_chatbot = true;
include '../includes/footer.php';
?>