<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Rekap Absensi";

$kid        = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$filter_bln = isset($_GET['bulan'])    ? $_GET['bulan']    : '';
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");

if ($kid) {
    $where_bln = $filter_bln ? "AND MONTH(a.tanggal) = '$filter_bln'" : '';

    $data = mysqli_query($koneksi,
            "SELECT s.nis, s.nama,
                SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status = 'Izin'  THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status = 'Alpa'  THEN 1 ELSE 0 END) AS alpa,
                COUNT(a.id) AS total
             FROM siswa s
             LEFT JOIN absensi a ON s.id = a.siswa_id
                 AND a.kelas_id = '$kid'
                 $where_bln
             WHERE s.kelas_id = '$kid'
             GROUP BY s.id
             ORDER BY s.nama");

    $nama_kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
                  "SELECT nama_kelas FROM kelas WHERE id='$kid'"))['nama_kelas'];
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-chart-bar text-icon me-2"></i>Rekap Absensi</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas <span class="text-danger">*</span></label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= $k['id'] ?>"
                            <?= $kid == $k['id'] ? 'selected' : '' ?>>
                            <?= e($k['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        <?php
                        $bulan = ['1'=>'Januari','2'=>'Februari','3'=>'Maret',
                                  '4'=>'April','5'=>'Mei','6'=>'Juni',
                                  '7'=>'Juli','8'=>'Agustus','9'=>'September',
                                  '10'=>'Oktober','11'=>'November','12'=>'Desember'];
                        foreach ($bulan as $no => $nm):
                        ?>
                        <option value="<?= $no ?>"
                            <?= $filter_bln == $no ? 'selected' : '' ?>>
                            <?= $nm ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($data)): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-table"></i>
                Rekap Kehadiran — <strong><?= $nama_kelas ?></strong>
                <?= $filter_bln ? '— ' . $bulan[$filter_bln] : '' ?>
            </span>
            <a href="cetak.php?kelas_id=<?= $kid ?>&bulan=<?= $filter_bln ?>" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Cetak
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th class="text-success">Hadir</th>
                            <th class="text-warning">Sakit</th>
                            <th class="text-info">Izin</th>
                            <th class="text-danger">Alpa</th>
                            <th>Total Hari</th>
                            <th>% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <?php
                        $persen = $r['total'] > 0
                            ? round(($r['hadir'] / $r['total']) * 100, 1)
                            : 0;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $r['nis'] ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-success"><?= $r['hadir'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark"><?= $r['sakit'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $r['izin'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-danger"><?= $r['alpa'] ?></span>
                        </td>
                        <td><?= $r['total'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:8px">
                                    <div class="progress-bar bg-<?= $persen >= 75 ? 'success' : 'danger' ?>"
                                         style="width:<?= $persen ?>%"></div>
                                </div>
                                <span class="<?= $persen >= 75 ? 'text-success' : 'text-danger' ?> fw-bold small">
                                    <?= $persen ?>%
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="empty-state py-5">
                <i class="fas fa-calendar-times fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data absensi untuk kelas ini.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Pilih kelas untuk melihat rekap absensi.
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>