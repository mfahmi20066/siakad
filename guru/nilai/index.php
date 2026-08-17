<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_periode_nilai.php';
cekGuru();
$title = "Nilai Siswa";

// pake id_ref sebagai id guru yang login
$gid  = $_SESSION['id_ref'];

// hubungkan via pivot kelas_mapel_guru (sumber kebenaran)
if (!isset($stmt_guru_nilai) || $stmt_guru_nilai === null) {
    $stmt_guru_nilai = mysqli_prepare($koneksi,
        "SELECT n.*, s.nama, s.nis, m.nama_mapel, k.nama_kelas
         FROM nilai n
         JOIN siswa s ON n.siswa_id = s.id
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         JOIN kelas k ON s.kelas_id = k.id
         JOIN kelas_mapel_guru kmg ON kmg.mapel_id = n.mapel_id AND kmg.kelas_id = k.id
         WHERE kmg.guru_id = ?
         GROUP BY n.id
         ORDER BY s.nama, m.nama_mapel");
    mysqli_stmt_bind_param($stmt_guru_nilai, "i", $gid);
}
mysqli_stmt_execute($stmt_guru_nilai);
$data = mysqli_stmt_get_result($stmt_guru_nilai);

// cache status periode per (kelas, semester) biar ga query berulang
$periode_cache = [];
while ($row = mysqli_fetch_assoc($data)) {
    $kunci = (int)$row['kelas_id'] . ':' . (int)$row['semester'] . ':' . (int)$row['tahun_ajaran_id'];
    if (!isset($periode_cache[$kunci])) {
        $periode_cache[$kunci] = getPeriodeStatus($koneksi, (int)$row['tahun_ajaran_id'], (int)$row['semester'], (int)$row['kelas_id']);
    }
}
mysqli_data_seek($data, 0);

$ada_terkunci = in_array('locked', $periode_cache, true);
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-star text-icon me-2"></i>Nilai Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if ($ada_terkunci): ?>
    <div class="alert alert-warning">
        <i class="fas fa-lock me-1"></i>
        Sebagian periode nilai sedang <strong>dikunci</strong> oleh administrator.
        Nilai pada periode terkunci tidak dapat diubah sampai admin membuka kembali.
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
                            <th>Periode</th>
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

                        $kunci_cache = (int)$r['kelas_id'] . ':' . (int)$r['semester'] . ':' . (int)$r['tahun_ajaran_id'];
                        $periode_row = $periode_cache[$kunci_cache] ?? 'locked';
                        $periode_buka = $periode_row === 'open';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $r['nis'] ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-info"><?= $r['nama_kelas'] ?></span>
                        </td>
                        <td><?= e($r['nama_mapel']) ?></td>
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
                            <?php if ($periode_buka): ?>
                            <span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Open</span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Locked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <?php if ($periode_buka): ?>
                                <a href="edit.php?id=<?= $r['id'] ?>"
                                   class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small"><i class="fas fa-lock"></i></span>
                                <?php endif; ?>
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