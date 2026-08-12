<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Statistik";

// Statistik absensi: distribusi status
$stat_absensi = mysqli_query($koneksi,
    "SELECT status, COUNT(*) AS jml FROM absensi GROUP BY status ORDER BY jml DESC");

// Statistik nilai: distribusi nilai akhir (kelompok nilai)
$dist_nilai = mysqli_query($koneksi,
    "SELECT
         SUM(CASE WHEN nilai_akhir >= 90 THEN 1 ELSE 0 END) AS a,
         SUM(CASE WHEN nilai_akhir >= 80 AND nilai_akhir < 90 THEN 1 ELSE 0 END) AS b,
         SUM(CASE WHEN nilai_akhir >= 70 AND nilai_akhir < 80 THEN 1 ELSE 0 END) AS c,
         SUM(CASE WHEN nilai_akhir < 70 THEN 1 ELSE 0 END) AS d
     FROM nilai");

$dist = mysqli_fetch_assoc($dist_nilai);

// Statistik kesiswaan
$stat_prestasi = mysqli_query($koneksi,
    "SELECT tingkat, COUNT(*) AS jml FROM prestasi_siswa GROUP BY tingkat ORDER BY jml DESC");
$stat_pelanggaran = mysqli_query($koneksi,
    "SELECT tingkat_pelanggaran, COUNT(*) AS jml FROM pelanggaran GROUP BY tingkat_pelanggaran");

// 5 siswa dengan prestasi terbanyak
$top_prestasi = mysqli_query($koneksi,
    "SELECT s.nis, s.nama_lengkap, s.nama AS nama_siswa, COUNT(p.id) AS jml
     FROM prestasi_siswa p
     LEFT JOIN siswa s ON p.siswa_id = s.id
     GROUP BY p.siswa_id
     ORDER BY jml DESC LIMIT 5");

// 5 siswa dengan poin pelanggaran terbanyak
$top_pelanggaran = mysqli_query($koneksi,
    "SELECT s.nis, s.nama_lengkap, s.nama AS nama_siswa, SUM(pg.poin) AS total_poin
     FROM pelanggaran pg
     LEFT JOIN siswa s ON pg.siswa_id = s.id
     GROUP BY pg.siswa_id
     ORDER BY total_poin DESC LIMIT 5");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-chart-pie text-icon me-2"></i>Statistik</h4>
        <a href="export.php?jenis=statistik" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-clipboard-check"></i> Statistik Absensi</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Status</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                            <?php if ($stat_absensi && mysqli_num_rows($stat_absensi) > 0): ?>
                                <?php
                                $total_abs = 0;
                                while ($r = mysqli_fetch_assoc($stat_absensi)) $total_abs += (int) $r['jml'];
                                mysqli_data_seek($stat_absensi, 0);
                                $warna = ['Hadir' => 'success', 'Izin' => 'info', 'Sakit' => 'warning', 'Alpa' => 'danger'];
                                while ($r = mysqli_fetch_assoc($stat_absensi)):
                                    $pct = $total_abs > 0 ? round(((int) $r['jml'] / $total_abs) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= $warna[$r['status']] ?? 'secondary' ?>"><?= e($r['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?= (int) $r['jml'] ?></strong>
                                        <small class="text-muted">(<?= $pct ?>%)</small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data absensi.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-star"></i> Distribusi Nilai Akhir</div>
                <div class="card-body">
                    <?php
                    $bins = [
                        ['label' => 'Sangat Baik (90+)', 'v' => (int) ($dist['a'] ?? 0), 'c' => 'success'],
                        ['label' => 'Baik (80-89)',      'v' => (int) ($dist['b'] ?? 0), 'c' => 'primary'],
                        ['label' => 'Cukup (70-79)',     'v' => (int) ($dist['c'] ?? 0), 'c' => 'warning'],
                        ['label' => 'Perlu Bimbingan (<70)', 'v' => (int) ($dist['d'] ?? 0), 'c' => 'danger'],
                    ];
                    $total_n = array_sum(array_column($bins, 'v'));
                    ?>
                    <?php foreach ($bins as $b):
                        $pct = $total_n > 0 ? round(($b['v'] / $total_n) * 100) : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= $b['label'] ?></span>
                            <span><strong><?= $b['v'] ?></strong> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 8px;">
                            <div class="progress-bar bg-<?= $b['c'] ?>" role="progressbar"
                                 style="width: <?= $pct ?>%; border-radius: 8px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($total_n == 0): ?>
                        <p class="text-center text-muted py-3 mb-0">Belum ada data nilai.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><i class="fas fa-trophy"></i> Siswa Berprestasi Terbanyak</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Siswa</th><th class="text-end">Jumlah Prestasi</th></tr></thead>
                            <tbody>
                            <?php if ($top_prestasi && mysqli_num_rows($top_prestasi) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($top_prestasi)): ?>
                                    <?php $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa']; ?>
                                <tr>
                                    <td>
                                        <strong><?= e($nama_s ?: '-') ?></strong>
                                        <br><small class="text-muted">NIS: <?= e($r['nis'] ?: '-') ?></small>
                                    </td>
                                    <td class="text-end"><span class="badge bg-warning"><?= (int) $r['jml'] ?> prestasi</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data prestasi.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><i class="fas fa-exclamation-triangle"></i> Siswa dengan Pelanggaran Terbanyak</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Siswa</th><th class="text-end">Total Poin</th></tr></thead>
                            <tbody>
                            <?php if ($top_pelanggaran && mysqli_num_rows($top_pelanggaran) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($top_pelanggaran)): ?>
                                    <?php $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa']; ?>
                                <tr>
                                    <td>
                                        <strong><?= e($nama_s ?: '-') ?></strong>
                                        <br><small class="text-muted">NIS: <?= e($r['nis'] ?: '-') ?></small>
                                    </td>
                                    <td class="text-end"><span class="badge bg-danger"><?= (int) $r['total_poin'] ?> poin</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data pelanggaran.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
