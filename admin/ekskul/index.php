<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Ekstrakurikuler";

// hitung jumlah anggota per ekskul
$data = mysqli_query($koneksi,
    "SELECT e.*,
            (SELECT COUNT(*) FROM ekstrakurikuler_anggota ea WHERE ea.ekskul_id = e.id) AS jml_anggota
     FROM ekstrakurikuler e
     ORDER BY e.nama_ekskul");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-futbol text-icon me-2"></i>Ekstrakurikuler</h4>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Ekskul
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Daftar Ekstrakurikuler</span>
        </div>
        <div class="card-body">
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Ekskul</th>
                            <th>Pembina</th>
                            <th>Hari</th>
                            <th>Pukul</th>
                            <th>Anggota</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($r = mysqli_fetch_assoc($data)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($r['nama_ekskul']) ?></strong></td>
                        <td><?= e($r['pembina'] ?: '-') ?></td>
                        <td><?= e($r['hari'] ?: '-') ?></td>
                        <td><?= e($r['pukul'] ?: '-') ?></td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">
                                <?= (int) $r['jml_anggota'] ?> Anggota
                            </span>
                            <a href="anggota.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary btn-sm btn-icon-only" title="Kelola Anggota">
                                <i class="fas fa-users"></i>
                            </a>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>', '<?= e($r['nama_ekskul']) ?>')"
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
                <i class="fas fa-futbol fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data ekstrakurikuler.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
