<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
cekAdmin();
$title = "Tambah User";
$icon  = "fa-user-plus";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = hashPassword($_POST['password']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    $cek = mysqli_fetch_row(mysqli_query($koneksi,
           "SELECT COUNT(*) FROM users WHERE username='$username'"))[0];
    if ($cek > 0) {
        $error = "Username sudah digunakan!";
    } else {
        mysqli_query($koneksi,
            "INSERT INTO users (nama, username, password, role)
             VALUES ('$nama','$username','$password','$role')");
        header("Location: index.php?success=tambah");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-user-plus text-icon me-2"></i>Tambah User</h4>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-plus"></i> Tambah User
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
                                   class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username"
                                   class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password"
                                   class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="guru">Guru</option>
                                <option value="siswa">Siswa</option>
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