<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Prestasi Siswa";

// â”€â”€ Filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$tingkat  = isset($_GET['tingkat']) ? $_GET['tingkat'] : '';

$where = "WHERE 1=1";
if ($kategori !== '') $where .= " AND p.kategori = '$kategori'";
if ($tingkat !== '')  $where .= " AND p.tingkat = '$tingkat'";

$data = mysqli_query($koneksi,
    "SELECT p.*, s.nis, s.nama_lengkap, s.nama AS nama_siswa
     FROM prestasi_siswa p
     LEFT JOIN siswa s ON p.siswa_id = s.id
     $where
     ORDER BY p.tanggal DESC, p.id DESC");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-trophy text-icon me-2"></i>Prestasi Siswa</h4>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Prestasi
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Prestasi</span>
            <form method="GET" class="d-flex gap-2">
                <select name="kategori" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Semua Kategori</option>
                    <option value="Akademik" <?= $kategori === 'Akademik' ? 'selected' : '' ?>>Akademik</option>
                    <option value="Non-Akademik" <?= $kategori === 'Non-Akademik' ? 'selected' : '' ?>>Non-Akademik</option>
                </select>
                <select name="tingkat" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Semua Tingkat</option>
                    <?php foreach (['Sekolah','Kecamatan','Kota','Provinsi','Nasional','Internasional'] as $t): ?>
                        <option value="<?= $t ?>" <?= $tingkat === $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo"></i></a>
            </form>
        </div>
        <div class="card-body">
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Siswa</th>
                            <th>Prestasi</th>
                            <th>Kategori</th>
                            <th>Tingkat</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($r = mysqli_fetch_assoc($data)):
                        $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= e($nama_s ?: '-') ?></strong>
                            <br><small class="text-muted">NIS: <?= e($r['nis'] ?: '-') ?></small>
                        </td>
                        <td><?= e($r['nama_prestasi']) ?></td>
                        <td>
                            <?php if ($r['kategori'] == 'Akademik'): ?>
                                <span class="badge bg-primary">Akademik</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Non-Akademik</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-success"><?= e($r['tingkat']) ?></span></td>
                        <td>
                            <?= $r['tanggal'] ? tanggal_indo_pendek($r['tanggal']) : '-' ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>', '<?= e($r['nama_prestasi']) ?>')"
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
                <i class="fas fa-trophy fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data prestasi.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
