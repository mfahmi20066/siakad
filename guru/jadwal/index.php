<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Jadwal Mengajar";

$gid  = $_SESSION['id_ref'] ?? $_SESSION['guru_id'] ?? $_SESSION['user_id'] ?? 0;

$data = mysqli_query($koneksi,
        "SELECT j.*, k.nama_kelas, m.nama_mapel
         FROM jadwal j
         JOIN kelas k ON j.kelas_id = k.id
         JOIN mata_pelajaran m ON j.mapel_id = m.id
         WHERE j.guru_id = '$gid' AND k.status = 'aktif'
         ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat'),
                  j.jam_mulai");

$total_jam = 0;
$rows = [];
while ($r = mysqli_fetch_assoc($data)) {
    $durasi      = (strtotime($r['jam_selesai']) - strtotime($r['jam_mulai'])) / 3600;
    $r['durasi'] = $durasi;
    $total_jam  += $durasi;
    $rows[]      = $r;
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-calendar-alt text-icon me-2"></i>Jadwal Mengajar</h4>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center border-primary">
                <div class="card-body py-2">
                    <h4 class="text-primary mb-0"><?= count($rows) ?></h4>
                    <small class="text-muted">Total Jadwal</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-success">
                <div class="card-body py-2">
                    <h4 class="text-success mb-0"><?= round($total_jam, 1) ?> Jam</h4>
                    <small class="text-muted">Total Jam/Minggu</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-info">
                <div class="card-body py-2">
                    <?php $kelas_unik = array_unique(array_column($rows, 'nama_kelas')); ?>
                    <h4 class="text-info mb-0"><?= count($kelas_unik) ?></h4>
                    <small class="text-muted">Kelas Diajar</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Jadwal Mengajar Saya
        </div>
        <div class="card-body">
            <?php if (count($rows) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Hari</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $warna_hari = ['Senin'=>'primary','Selasa'=>'success','Rabu'=>'warning',
                                   'Kamis'=>'info','Jumat'=>'danger'];
                    foreach ($rows as $no => $r):
                    ?>
                    <tr>
                        <td><?= $no + 1 ?></td>
                        <td>
                            <span class="badge bg-<?= $warna_hari[$r['hari']] ?? 'secondary' ?>">
                                <?= $r['hari'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?= e($r['nama_kelas']) ?>
                            </span>
                        </td>
                        <td><?= e($r['nama_mapel']) ?></td>
                        <td><?= substr($r['jam_mulai'], 0, 5) ?></td>
                        <td><?= substr($r['jam_selesai'], 0, 5) ?></td>
                        <td><span class="text-muted small"><?= $r['durasi'] ?> jam</span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p>Belum ada jadwal mengajar yang ditetapkan.</p>
                <small>Silakan hubungi admin untuk mengatur jadwal.</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>