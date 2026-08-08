<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
cekAdmin();
$title = "Edit User";
$icon  = "fa-user-edit";

$id   = mysqli_real_escape_string($koneksi, $_GET['id']);
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM users WHERE id='$id'"));

if (!$data) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    $cek = mysqli_fetch_row(mysqli_query($koneksi,
           "SELECT COUNT(*) FROM users
            WHERE username='$username' AND id!='$id'"))[0];
    if ($cek > 0) {
        $error = "Username sudah digunakan!";
    } else {
        if (!empty($_POST['password'])) {
            $pass = hashPassword($_POST['password']);
            mysqli_query($koneksi,
                "UPDATE users SET nama='$nama', username='$username',
                 role='$role', password='$pass' WHERE id='$id'");
        } else {
            mysqli_query($koneksi,
                "UPDATE users SET nama='$nama', username='$username',
                 role='$role' WHERE id='$id'");
        }
        header("Location: index.php?success=edit");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-user-edit text-gold me-2"></i>Edit User</h4>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-edit"></i> Edit User
        </div>
        <div class="card-body">

            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data['nama']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data['username']) ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Password Baru
                                <small class="text-muted">(kosongkan jika tidak diubah)</small>
                            </label>
                            <input type="password" name="password"
                                   class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin"  <?= $data['role']=='admin'?'selected':'' ?>>Admin</option>
                                <option value="guru"   <?= $data['role']=='guru' ?'selected':'' ?>>Guru</option>
                                <option value="siswa"  <?= $data['role']=='siswa'?'selected':'' ?>>Siswa</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Batal
                </a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>