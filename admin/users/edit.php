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
    $email    = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));

    $error = null;
    $cek = mysqli_fetch_row(mysqli_query($koneksi,
           "SELECT COUNT(*) FROM users
            WHERE username='$username' AND id!='$id'"))[0];
    if ($cek > 0) {
        $error = "Username sudah digunakan!";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!empty($email) && mysqli_fetch_row(mysqli_query($koneksi,
                "SELECT COUNT(*) FROM users WHERE email='$email' AND id!='$id'"))[0] > 0) {
        $error = "Email sudah digunakan oleh pengguna lain!";
    }

    if ($error === null) {
        $emailSql = $email !== '' ? "'$email'" : "NULL";
        if (!empty($_POST['password'])) {
            $pass = hashPassword($_POST['password']);
            mysqli_query($koneksi,
                "UPDATE users SET nama='$nama', username='$username',
                 role='$role', email=$emailSql, password='$pass' WHERE id='$id'");
        } else {
            mysqli_query($koneksi,
                "UPDATE users SET nama='$nama', username='$username',
                 role='$role', email=$emailSql WHERE id='$id'");
        }
        // sinkron nama & email ke guru/siswa biar tetep terelasi
        if ($role === 'guru' && !empty($data['id_ref'])) {
            mysqli_query($koneksi, "UPDATE guru SET nama='$nama', nama_lengkap='$nama', email=$emailSql WHERE id='{$data['id_ref']}'");
        } elseif ($role === 'siswa' && !empty($data['id_ref'])) {
            mysqli_query($koneksi, "UPDATE siswa SET nama='$nama', nama_lengkap='$nama', email=$emailSql WHERE id='{$data['id_ref']}'");
        }
        header("Location: index.php?success=edit");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-user-edit text-icon me-2"></i>Edit User</h4>
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
                                   value="<?= e($data['nama']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username"
                                   class="form-control"
                                   value="<?= e($data['username']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email"
                                   class="form-control"
                                   value="<?= e($data['email'] ?? '') ?>"
                                   placeholder="nama@email.com">
                            <small class="text-muted">Tersinkron otomatis dengan data guru/siswa terkait.</small>
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