<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Nilai Siswa";

// SINKRONISASI SESSION: Menggunakan id_ref sebagai ID Guru yang login
$gid  = $_SESSION['id_ref'];

// PERBAIKAN QUERY: Menghubungkan relasi lewat tabel jadwal karena tabel nilai tidak memiliki kolom guru_id
$data = mysqli_query($koneksi,
        "SELECT n.*, s.nama, s.nis, m.nama_mapel, k.nama_kelas
         FROM nilai n
         JOIN siswa s ON n.siswa_id = s.id
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         JOIN kelas k ON s.kelas_id = k.id
         JOIN jadwal j ON n.mapel_id = j.mapel_id AND s.kelas_id = j.kelas_id
         WHERE j.guru_id = '$gid'
         GROUP BY n.id
         ORDER BY s.nama, m.nama_mapel");

if (!$data) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-star text-gold me-2"></i>Nilai Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Nilai yang Saya Input</span>
            <a href="input.php" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Input Nilai
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Harian</th>
                            <th>UTS</th>
                            <th>UAS</th>
                            <th>Nilai Akhir</th>
                            <th>Smt</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <?php
                        $na = $r['nilai_akhir'];
                        if ($na >= 90) $predikat = 'A';
                        elseif ($na >= 80) $predikat = 'B';
                        elseif ($na >= 70) $predikat = 'C';
                        elseif ($na >= 60) $predikat = 'D';
                        else $predikat = 'E';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $r['nis'] ?></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-info"><?= $r['nama_kelas'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                        <td><?= $r['nilai_uh'] ?></td>
                        <td><?= $r['nilai_uts'] ?></td>
                        <td><?= $r['nilai_uas'] ?></td>
                        <td>
                            <strong class="<?= $na >= 75 ? 'text-success' : 'text-danger' ?>">
                                <?= $na ?>
                            </strong>
                            <span class="badge bg-<?= $na >= 75 ? 'success' : 'danger' ?> ms-1">
                                <?= $predikat ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $r['semester'] ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="edit.php?id=<?= $r['id'] ?>"
                                   class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="empty-state py-5">
                <i class="fas fa-star fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data nilai.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>