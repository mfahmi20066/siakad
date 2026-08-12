<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Laporan";

// Riwayat log export
$log = mysqli_query($koneksi,
    "SELECT l.*, u.nama AS nama_user
     FROM laporan_log l
     LEFT JOIN users u ON l.dibuat_oleh = u.id
     ORDER BY l.id DESC LIMIT 10");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-folder-open text-icon me-2"></i>Laporan</h4>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center py-5">
                    <div class="icon-wrap mb-3" style="font-size: 40px; color: var(--primary);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h5 class="fw-bold">Laporan Akademik</h5>
                    <p class="text-muted small">
                        Rekapitulasi data siswa, guru, kelas, dan rata-rata nilai per kelas & mapel.
                    </p>
                    <a href="akademik.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Lihat Laporan
                    </a>
                    <a href="export.php?jenis=akademik" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center py-5">
                    <div class="icon-wrap mb-3" style="font-size: 40px; color: var(--primary);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h5 class="fw-bold">Statistik</h5>
                    <p class="text-muted small">
                        Distribusi nilai, statistik absensi, siswa berprestasi & pelanggaran terbanyak.
                    </p>
                    <a href="statistik.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Lihat Statistik
                    </a>
                    <a href="export.php?jenis=statistik" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center py-5">
                    <div class="icon-wrap mb-3" style="font-size: 40px; color: var(--primary);">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h5 class="fw-bold">Laporan Kesiswaan</h5>
                    <p class="text-muted small">
                        Data prestasi siswa sebagai laporan bidang kesiswaan.
                    </p>
                    <a href="export.php?jenis=kesiswaan" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-history"></i> Riwayat Export Terakhir
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr><th>Jenis Laporan</th><th>Dibuat Oleh</th><th>Waktu</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($log && mysqli_num_rows($log) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($log)): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= e(ucfirst($r['jenis_laporan'])) ?></span></td>
                            <td><?= e($r['nama_user'] ?: '-') ?></td>
                            <td><small class="text-muted"><?= tanggal_waktu_indo($r['created_at']) ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Belum ada riwayat export.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
