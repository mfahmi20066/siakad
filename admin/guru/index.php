<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Guru";

$data = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-chalkboard-teacher text-gold me-2"></i>Data Guru</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Guru</span>
            <div class="btn-group">
                <a href="tambah.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Guru
                </a>
                <a href="cetak_guru.php" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Cetak/Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php
                        $foto_g = $r['foto'] ?? '';
                        $foto_g_src = (!empty($foto_g) && file_exists(__DIR__ . '/../../assets/img/foto_guru/' . $foto_g))
                            ? '/siakad/assets/img/foto_guru/' . $foto_g
                            : '/siakad/assets/img/default-avatar.png';
                        ?>
                        <img src="<?= $foto_g_src ?>" alt="Foto" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                    </td>
                    <td><?= $r['nip'] ?></td>
                    <td><?= htmlspecialchars($r['nama']) ?></td>
                    <td>
                        <?php if ($r['jenis_kelamin'] == 'L'): ?>
                            <span class="badge bg-primary">Laki-laki</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Perempuan</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['no_hp'] ?></td>
                    <td>
                        <span class="text-muted small">
                            <?= htmlspecialchars(substr($r['alamat'] ?? '', 0, 40)) ?>...
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="detail.php?id=<?= $r['id'] ?>"
                               class="btn btn-info btn-sm" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $r['id'] ?>"
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>')"
                                    class="btn btn-danger btn-sm" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>