<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Rekap Absensi";

// fix session: pake id_ref biar ga error undefined
$gid = $_SESSION['id_ref'];
$kid = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';

// daftar kelas buat dropdown: semua kelas yang punya absensi guru ini (filter via jadwal)
$kelas_list = mysqli_query($koneksi,
    "SELECT DISTINCT k.*
     FROM absensi a
     JOIN kelas_mapel_guru kmg ON kmg.kelas_id = a.kelas_id AND kmg.mapel_id = a.mapel_id
     JOIN kelas k ON k.id = a.kelas_id
     WHERE kmg.guru_id = '$gid'
       AND a.kelas_id IS NOT NULL
     ORDER BY k.tingkat, k.nama_kelas");



if ($kid) {
    $kid_clean = mysqli_real_escape_string($koneksi, $kid);

    // fix query rekap: hubungkan absensi ke jadwal buat validasi guru ngajar
    $query_rekap = "SELECT s.nis, s.nama,
                        SUM(CASE WHEN a.status='Hadir' OR a.status='H' THEN 1 ELSE 0 END) AS hadir,
                        SUM(CASE WHEN a.status='Sakit' OR a.status='S' THEN 1 ELSE 0 END) AS sakit,
                        SUM(CASE WHEN a.status='Izin'  OR a.status='I' THEN 1 ELSE 0 END) AS izin,
                        SUM(CASE WHEN a.status='Alpa'  OR a.status='A' THEN 1 ELSE 0 END) AS alpa,
                        COUNT(a.id) AS total
                     FROM siswa s
                     LEFT JOIN absensi a ON s.id = a.siswa_id
                     LEFT JOIN kelas_mapel_guru kmg ON a.kelas_id = kmg.kelas_id AND a.mapel_id = kmg.mapel_id AND kmg.guru_id = '$gid'
                     WHERE s.kelas_id = '$kid_clean'
                     GROUP BY s.id
                     ORDER BY s.nama";

    $data = mysqli_query($koneksi, $query_rekap);

    if (!$data) {
        die("Query Error: " . mysqli_error($koneksi));
    }

    // ambil nama kelas aktif
    $q_nama_kelas = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id='$kid_clean'");
    $res_kelas = mysqli_fetch_assoc($q_nama_kelas);
    $nama_kelas = $res_kelas ? $res_kelas['nama_kelas'] : 'Unknown';
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-chart-bar text-icon me-2"></i>Rekap Absensi</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php if ($kelas_list): ?>
                            <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                            <option value="<?= $k['id'] ?>"
                                <?= $kid == $k['id'] ? 'selected' : '' ?>>
                                <?= e($k['nama_kelas']) ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
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
                Rekap — <strong><?= e($nama_kelas) ?></strong>
            </span>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Cetak
            </button>
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
                            <th>Total</th>
                            <th>% Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <?php
                        $persen = $r['total'] > 0
                            ? round(($r['hadir'] / $r['total']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($r['nis']) ?></td>
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
                                <span class="small fw-bold <?= $persen >= 75 ? 'text-success' : 'text-danger' ?>">
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