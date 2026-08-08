<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Bimbingan Konseling (BK)";

$data = mysqli_query($koneksi,
    "SELECT pg.*, s.nis, s.nama_lengkap, s.nama AS nama_siswa
     FROM pelanggaran pg
     LEFT JOIN siswa s ON pg.siswa_id = s.id
     ORDER BY pg.tanggal DESC, pg.id DESC");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-exclamation-triangle text-gold me-2"></i>Bimbingan Konseling</h4>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Pelanggaran
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Daftar Pelanggaran Siswa</span>
        </div>
        <div class="card-body">
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Siswa</th>
                            <th>Jenis Pelanggaran</th>
                            <th>Tingkat</th>
                            <th>Poin</th>
                            <th>Tanggal</th>
                            <th>Petugas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($r = mysqli_fetch_assoc($data)):
                        $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
                        $badge = $r['tingkat_pelanggaran'] == 'Ringan' ? 'bg-success'
                               : ($r['tingkat_pelanggaran'] == 'Sedang' ? 'bg-warning' : 'bg-danger');
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($nama_s ?: '-') ?></strong>
                            <br><small class="text-muted">NIS: <?= htmlspecialchars($r['nis'] ?: '-') ?></small>
                        </td>
                        <td><?= htmlspecialchars($r['jenis_pelanggaran']) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($r['tingkat_pelanggaran']) ?></span></td>
                        <td>
                            <span class="badge bg-dark"><?= (int) $r['poin'] ?> poin</span>
                        </td>
                        <td><?= $r['tanggal'] ? tanggal_indo_pendek($r['tanggal']) : '-' ?></td>
                        <td><?= htmlspecialchars($r['petugas'] ?: '-') ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>', '<?= htmlspecialchars($r['jenis_pelanggaran']) ?>')"
                                        class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state py-5 text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data pelanggaran.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
