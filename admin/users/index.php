<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Pengguna";

$data = mysqli_query($koneksi, "SELECT * FROM users ORDER BY role, nama");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-users text-gold me-2"></i>Data Pengguna</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i>
        <?php 
        $msg = $_GET['success'];
        if ($msg == 'tambah') echo 'User berhasil ditambahkan!';
        elseif ($msg == 'edit') echo 'User berhasil diedit!';
        elseif ($msg == 'hapus') echo 'User berhasil dihapus!';
        else echo htmlspecialchars($msg);
        ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users"></i> Daftar Pengguna</span>
            <a href="tambah.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah User
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelUsers" class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($r['username']) ?></strong></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td>
                            <?php if ($r['role'] == 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php elseif ($r['role'] == 'guru'): ?>
                                <span class="badge bg-success">Guru</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Siswa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['status'] == 'aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= $r['last_login'] ? tanggal_waktu_indo($r['last_login']) : '-' ?>
                            </small>
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
                                <?php if ($r['id'] != 1): ?>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>', '<?= htmlspecialchars($r['nama']) ?>')"
                                        class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>

<?php include '../../includes/footer.php'; ?>
