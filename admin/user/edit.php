<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
cekAdmin();
$title = "Edit User";

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM users WHERE id='$id'"));

if (!$data) {
    header("Location: index.php?error=Data user tidak ditemukan");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $uname = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role  = $_POST['role'];

    // cek username duplikat, kecuali diri sendiri
    $cek = mysqli_query($koneksi,
           "SELECT id FROM users WHERE username='$uname' AND id != '$id'");

    if (mysqli_num_rows($cek) > 0) {
        $error = "Username <strong>$uname</strong> sudah digunakan user lain!";
    } else {
        mysqli_query($koneksi,
            "UPDATE users SET nama='$nama', username='$uname', role='$role'
             WHERE id='$id'");

        // update password cuma kalo diisi
        if (!empty($_POST['password'])) {
            $pass = hashPassword($_POST['password']);
            mysqli_query($koneksi,
                "UPDATE users SET password='$pass' WHERE id='$id'");
        }

        header("Location: index.php?success=User $nama berhasil diupdate");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-user-edit text-icon me-2"></i>Edit User</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Form Edit User</div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control"
                                   value="<?= e($data['nama']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= e($data['username']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select"
                                <?= $id == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="admin"
                                    <?= $data['role'] == 'admin' ? 'selected' : '' ?>>
                                    <i class="fas fa-crown"></i> Admin
                                </option>
                                <option value="guru"
                                    <?= $data['role'] == 'guru' ? 'selected' : '' ?>>
                                    <i class="fas fa-chalkboard-user"></i> Guru
                                </option>
                                <option value="siswa"
                                    <?= $data['role'] == 'siswa' ? 'selected' : '' ?>>
                                    <i class="fas fa-graduation-cap"></i> Siswa
                                </option>
                            </select>
                            <?php if ($id == $_SESSION['user_id']): ?>
                            <!-- Kirim nilai role tetap meski select disabled -->
                            <input type="hidden" name="role" value="<?= $data['role'] ?>">
                            <small class="text-warning">
                                <i class="fas fa-lock"></i>
                                Role tidak bisa diubah untuk akun sendiri
                            </small>
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-secondary">
                            <h6>Info User</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>ID User</td>
                                    <td>: <strong><?= $data['id'] ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Role Saat Ini</td>
                                    <td>:
                                        <?php
                                        $rb = ['admin'=>'danger','guru'=>'success','siswa'=>'primary'];
                                        ?>
                                        <span class="badge bg-<?= $rb[$data['role']] ?>">
                                            <?= ucfirst($data['role']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($id == $_SESSION['user_id']): ?>
                                <tr>
                                    <td colspan="2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-user"></i> Ini adalah akun Anda
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>