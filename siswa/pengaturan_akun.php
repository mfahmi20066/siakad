<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_auth.php';
cekSiswa();

$user_id = $_SESSION['user_id'] ?? 0;
$title = "Pengaturan Akun";

// Get user data
$query = "SELECT * FROM users WHERE id=$user_id";
$result = mysqli_query($koneksi, $query);
$user = mysqli_fetch_assoc($result);

$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $password_konfirmasi = $_POST['password_konfirmasi'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
        $error = "Semua field password harus diisi!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter!";
    } elseif ($password_baru !== $password_konfirmasi) {
        $error = "Password baru dan konfirmasi tidak sesuai!";
    } elseif (!checkPassword($password_lama, $user['password'])) {
        $error = "Password lama tidak sesuai!";
    } else {
        $password_hash = hashPassword($password_baru);
        $update = mysqli_query($koneksi, "UPDATE users SET password='$password_hash' WHERE id=$user_id");
        if ($update) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password: " . mysqli_error($koneksi);
        }
    }
}

// Handle email update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_email'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Email tidak boleh kosong!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Check if email already exists
        $check = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email sudah digunakan oleh akun lain!";
        } else {
            $update = mysqli_query($koneksi, "UPDATE users SET email='$email' WHERE id=$user_id");
            if ($update) {
                $success = "Email berhasil diperbarui!";
                $user['email'] = $email;
            } else {
                $error = "Gagal mengubah email: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_siswa.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_siswa.php'; ?>

    <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-cog text-gold me-2"></i>Pengaturan Akun</h4>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Email Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-envelope me-2"></i>Pengaturan Email</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        <small class="text-muted">Email digunakan untuk notifikasi dan reset password</small>
                    </div>

                    <button type="submit" name="update_email" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Email
                    </button>
                </form>
            </div>
        </div>

        <!-- Password Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Pengaturan Password</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="password_lama" class="form-label">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                    </div>

                    <div class="mb-3">
                        <label for="password_baru" class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>

                    <div class="mb-3">
                        <label for="password_konfirmasi" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_konfirmasi" name="password_konfirmasi" required>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Security Info -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Informasi Keamanan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Username:</strong></p>
                        <p class="text-muted"><?php echo htmlspecialchars($user['username'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Status Akun:</strong></p>
                        <p class="text-muted">
                            <?php if ($user['status'] == 'aktif'): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Tidak Aktif</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <hr>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tips Keamanan:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Gunakan password yang kuat (kombinasi huruf, angka, dan simbol)</li>
                        <li>Jangan bagikan password dengan siapa pun</li>
                        <li>Ubah password secara berkala</li>
                        <li>Logout setelah selesai menggunakan sistem</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
