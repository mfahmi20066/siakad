<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Detail User";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id'");
$user = mysqli_fetch_assoc($q);

if (!$user) {
    header("Location: index.php");
    exit();
}

// Ambil data referensi (guru/siswa) jika ada
$ref_data = null;
if ($user['role'] == 'guru' && $user['id_ref']) {
    $ref = mysqli_query($koneksi, "SELECT * FROM guru WHERE id='{$user['id_ref']}'");
    $ref_data = mysqli_fetch_assoc($ref);
} elseif ($user['role'] == 'siswa' && $user['id_ref']) {
    $ref = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id='{$user['id_ref']}'");
    $ref_data = mysqli_fetch_assoc($ref);
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-user text-icon me-2"></i>Detail User</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-user"></i> Detail User
        </div>
        <div class="card-body">
            <table class="table table-bordered" style="max-width:600px;">
                <tr>
                    <th style="width:180px;">ID</th>
                    <td><?= $user['id'] ?></td>
                </tr>
                <tr>
                    <th>Username</th>
                    <td><?= e($user['username']) ?></td>
                </tr>
                <tr>
                    <th>Nama</th>
                    <td><?= e($user['nama']) ?></td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td>
                        <?php if ($user['role'] == 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php elseif ($user['role'] == 'guru'): ?>
                            <span class="badge bg-success">Guru</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Siswa</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($user['status'] == 'aktif'): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>ID Referensi</th>
                    <td><?= $user['id_ref'] ?: '-' ?></td>
                </tr>
                <tr>
                    <th>Last Login</th>
                    <td><?= $user['last_login'] ? tanggal_waktu_indo($user['last_login'], true) : 'Belum pernah login' ?></td>
                </tr>
                <tr>
                    <th>Dibuat Pada</th>
                    <td><?= tanggal_waktu_indo($user['created_at'], true) ?></td>
                </tr>
            </table>

            <?php if ($ref_data): ?>
            <h6 class="mt-4 fw-bold">Data Terkait (<?= ucfirst($user['role']) ?>)</h6>
            <table class="table table-bordered" style="max-width:600px;">
                <?php foreach ($ref_data as $key => $val): ?>
                <tr>
                    <th style="width:180px;"><?= e($key) ?></th>
                    <td><?= e($val ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
</div>

<?php include '../../includes/footer.php'; ?>
