<?php
include '../config/koneksi.php';
include '../config/session.php';
cekSiswa();
$title = "Jadwal Pelajaran";

// Ambil ID siswa dari session login yang aktif
$sid = $_SESSION['id_ref'];

// Cari data kelas_id milik siswa yang sedang login dari tabel siswa
$q_siswa = mysqli_query($koneksi, "SELECT kelas_id FROM siswa WHERE id = '$sid'");
$siswa   = mysqli_fetch_assoc($q_siswa);
$kid     = $siswa['kelas_id'] ?? '';

// Ambil nama wali kelas dari data kelas (kelas.wali_kelas -> guru.nama_lengkap)
$wali_kelas = '-';
if ($kid) {
    $qw = mysqli_query($koneksi,
        "SELECT g.nama_lengkap AS nama, g.nip AS nip
         FROM kelas k
         LEFT JOIN guru g ON k.wali_kelas = g.id
         WHERE k.id = '$kid' LIMIT 1");
    if ($qw && $wrow = mysqli_fetch_assoc($qw)) {
        $wali_kelas = $wrow['nama'] ?? '-';
    }
}

// Ambil data jadwal berdasarkan kelas_id dan sesuaikan nama kolom guru menjadi nama_lengkap
$data  = mysqli_query($koneksi,
         "SELECT j.*, m.nama_mapel, g.nama_lengkap AS nama_guru
          FROM jadwal j
          JOIN mata_pelajaran m ON j.mapel_id = m.id
          JOIN guru g ON j.guru_id = g.id
          WHERE j.kelas_id = '$kid'
          ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat'),
                   j.jam_mulai");

$rows = [];
if ($data) {
    while ($r = mysqli_fetch_assoc($data)) {
        $rows[] = $r;
    }
}

// Hitung total jam per hari untuk ringkasan
$per_hari = [];
foreach ($rows as $r) {
    $per_hari[$r['hari']] = ($per_hari[$r['hari']] ?? 0) + 1;
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_siswa.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_siswa.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-calendar-alt text-gold me-2"></i>Jadwal Pelajaran</h4>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-school"></i>
        Menampilkan jadwal untuk kelas Anda.
        Total <strong><?= count($rows) ?></strong> sesi pelajaran per minggu.
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Jadwal Kelas Saya
        </div>
        <div class="card-body">
            <?php if (count($rows) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Hari</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Wali Kelas</th>
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
                        $durasi = round((strtotime($r['jam_selesai']) - strtotime($r['jam_mulai'])) / 60);
                    ?>
                    <tr>
                        <td><?= $no + 1 ?></td>
                        <td>
                            <span class="badge bg-<?= $warna_hari[$r['hari']] ?? 'dark' ?>">
                                <?= $r['hari'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                        <td>
                            <i class="fas fa-user-tie text-success"></i>
                            <?= htmlspecialchars($r['nama_guru']) ?>
                        </td>
                        <td>
                            <i class="fas fa-user-shield text-primary"></i>
                            <?= htmlspecialchars($wali_kelas) ?>
                        </td>
                        <td><?= substr($r['jam_mulai'], 0, 5) ?></td>
                        <td><?= substr($r['jam_selesai'], 0, 5) ?></td>
                        <td>
                            <small class="text-muted"><?= $durasi ?> mnt</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="mb-0">Jadwal pelajaran belum tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>