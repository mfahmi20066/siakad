<?php
include '../config/koneksi.php';
include '../config/session.php';
cekGuru();

$user_id = $_SESSION['user_id'] ?? 0;
$title = "Profil Saya";

// Get user data
$query = "SELECT * FROM users WHERE id=$user_id";
$result = mysqli_query($koneksi, $query);
$user = mysqli_fetch_assoc($result);

// Get guru data via users.id_ref -> guru.id
$guru_ref_id = (int)($user['id_ref'] ?? 0);
$query_guru = "SELECT * FROM guru WHERE id=" . $guru_ref_id;
$result_guru = mysqli_query($koneksi, $query_guru);
$guru = $guru_ref_id > 0 ? mysqli_fetch_assoc($result_guru) : null;

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    
    if (empty($nama)) {
        $error = "Nama tidak boleh kosong!";
    } else {
        // Nama guru tersimpan di DUA tempat: users.nama (akun) & guru.nama/nama_lengkap
        // (identitas guru — dipakai login/nav/sidebar/listing). Update keduanya agar
        // perubahan benar-benar tersimpan & konsisten.
        $update = mysqli_query($koneksi, "UPDATE users SET nama='$nama' WHERE id=$user_id");
        if ($update && $guru_ref_id > 0) {
            mysqli_query($koneksi, "UPDATE guru SET nama='$nama', nama_lengkap='$nama' WHERE id=$guru_ref_id");
        }
        if ($update) {
            $_SESSION['nama'] = $nama;
            $success = "Profil berhasil diperbarui!";
            $user['nama'] = $nama;
            if ($guru_ref_id > 0) {
                $guru['nama'] = $nama;
                $guru['nama_lengkap'] = $nama;
            }
        } else {
            $error = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_guru.php'; ?>

    <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-user-circle text-gold me-2"></i>Profil Saya</h4>
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

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div style="width: 120px; height: 120px; margin: 0 auto 20px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: bold;">
                            <?php 
                            $nama = htmlspecialchars($user['nama'] ?? 'Guru');
                            $nameParts = explode(' ', $nama);
                            $initials = '';
                            foreach ($nameParts as $part) {
                                $initials .= strtoupper(substr($part, 0, 1));
                            }
                            echo substr($initials, 0, 2);
                            ?>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['nama'] ?? 'Guru'); ?></h5>
                        <p class="text-muted mb-3">
                            <span class="badge bg-info">Guru</span>
                        </p>
                        <hr>
                        <p class="text-muted" style="font-size: 13px;">
                            <i class="fas fa-calendar me-2"></i>
                            Terdaftar sejak <?php echo tanggal_indo_pendek($user['created_at'] ?? date('Y-m-d')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Informasi Profil</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                                <small class="text-muted">Username tidak dapat diubah</small>
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email'] ?? '-'); ?>" disabled>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nip" class="form-label">NIP</label>
                                        <input type="text" class="form-control" id="nip" value="<?php echo htmlspecialchars($guru['nip'] ?? '-'); ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <div>
                                            <?php if ($user['status'] == 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex gap-2">
                                <button type="submit" name="update_profil" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
