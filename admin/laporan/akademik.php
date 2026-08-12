<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Laporan Akademik";

// Data rekap: jumlah siswa, guru, kelas, mapel
$stat = [
    'siswa'   => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM siswa"))[0],
    'guru'    => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM guru"))[0],
    'kelas'   => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kelas"))[0],
    'mapel'   => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM mata_pelajaran"))[0],
];

// Distribusi siswa per kelas
$distribusi_kelas = mysqli_query($koneksi,
    "SELECT k.nama_kelas, COUNT(s.id) AS jml
     FROM kelas k
     LEFT JOIN siswa s ON s.kelas_id = k.id
     GROUP BY k.id
     ORDER BY k.tingkat, k.nama_kelas");

// Rekap nilai rata-rata per kelas (dari nilai_akhir)
$rekap_nilai = mysqli_query($koneksi,
    "SELECT k.nama_kelas, m.nama_mapel,
            ROUND(AVG(n.nilai_akhir), 2) AS rata,
            COUNT(n.id) AS jml_data
     FROM nilai n
     LEFT JOIN kelas k ON n.kelas_id = k.id
     LEFT JOIN mata_pelajaran m ON n.mapel_id = m.id
     GROUP BY n.kelas_id, n.mapel_id
     ORDER BY k.nama_kelas, m.nama_mapel");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-chart-bar text-icon me-2"></i>Laporan Akademik</h4>
        <a href="export.php?jenis=akademik" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="summary-card p-3 bg-white rounded-3 border">
                <div class="summary-label text-muted small">Total Siswa</div>
                <div class="summary-value fw-bold fs-4" style="color: var(--primary);"><?= $stat['siswa'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3 bg-white rounded-3 border">
                <div class="summary-label text-muted small">Total Guru</div>
                <div class="summary-value fw-bold fs-4" style="color: var(--gold);"><?= $stat['guru'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3 bg-white rounded-3 border">
                <div class="summary-label text-muted small">Total Kelas</div>
                <div class="summary-value fw-bold fs-4" style="color: var(--primary);"><?= $stat['kelas'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card p-3 bg-white rounded-3 border">
                <div class="summary-label text-muted small">Total Mapel</div>
                <div class="summary-value fw-bold fs-4" style="color: var(--gold);"><?= $stat['mapel'] ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-users"></i> Jumlah Siswa per Kelas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr><th>Kelas</th><th class="text-end">Jumlah Siswa</th></tr>
                            </thead>
                            <tbody>
                            <?php if ($distribusi_kelas && mysqli_num_rows($distribusi_kelas) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($distribusi_kelas)): ?>
                                <tr>
                                    <td><strong><?= e($r['nama_kelas']) ?></strong></td>
                                    <td class="text-end"><span class="badge bg-primary"><?= (int) $r['jml'] ?> siswa</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data kelas.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-star"></i> Rekap Rata-rata Nilai per Kelas & Mapel
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover dataTable align-middle">
                            <thead>
                                <tr><th>Kelas</th><th>Mata Pelajaran</th><th class="text-end">Rata-rata</th><th class="text-end">Data</th></tr>
                            </thead>
                            <tbody>
                            <?php if ($rekap_nilai && mysqli_num_rows($rekap_nilai) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($rekap_nilai)): ?>
                                <tr>
                                    <td><?= e($r['nama_kelas'] ?: '-') ?></td>
                                    <td><?= e($r['nama_mapel']) ?></td>
                                    <td class="text-end">
                                        <strong><?= $r['rata'] !== null ? number_format($r['rata'], 2) : '-' ?></strong>
                                    </td>
                                    <td class="text-end"><small class="text-muted"><?= (int) $r['jml_data'] ?></small></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data nilai.</td></tr>
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
