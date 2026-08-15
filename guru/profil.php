<?php
ob_start();
include '../config/koneksi.php';
include '../config/session.php';
cekGuru();

$user_id = $_SESSION['user_id'] ?? 0;
$title = "Profil Saya";

// ambil data user
$query = "SELECT * FROM users WHERE id=$user_id";
$result = mysqli_query($koneksi, $query);
$user = mysqli_fetch_assoc($result);

// ambil data guru via users.id_ref -> guru.id
$guru_ref_id = (int)($user['id_ref'] ?? 0);
$query_guru = "SELECT * FROM guru WHERE id=" . $guru_ref_id;
$result_guru = mysqli_query($koneksi, $query_guru);
$guru = $guru_ref_id > 0 ? mysqli_fetch_assoc($result_guru) : null;

// upload foto profil (drag & drop)
$foto_folder = __DIR__ . '/../assets/img/foto_guru/';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_foto'])) {
    if ($guru_ref_id > 0 && !empty($_FILES['foto']['name'])) {
        $file = $_FILES['foto'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allow = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allow, true)) {
            $error = "Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "Ukuran foto maksimal 5 MB.";
        } else {
            // hapus foto lama kalo ada
            $foto_lama = $guru['foto'] ?? '';
            if (!empty($foto_lama) && file_exists($foto_folder . $foto_lama)) {
                @unlink($foto_folder . $foto_lama);
            }

            $nama_file = 'guru_' . $guru_ref_id . '_' . time() . '.' . $ext;
            if (!is_dir($foto_folder)) mkdir($foto_folder, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $foto_folder . $nama_file)) {
                mysqli_query($koneksi, "UPDATE guru SET foto='$nama_file' WHERE id=$guru_ref_id");
                $_SESSION['foto'] = $nama_file;
                $guru['foto'] = $nama_file;
                $success = "Foto profil berhasil diperbarui!";
                // prg: redirect ke get biar ga ada confirm form resubmission
                header("Location: profil.php?success=" . urlencode($success));
                exit;
            } else {
                $error = "Gagal mengupload foto. Silakan coba lagi.";
            }
        }
    } else {
        $error = "Silakan pilih file foto terlebih dahulu.";
    }
}

// path foto profil
$foto_file = $guru['foto'] ?? '';
$foto_src  = (!empty($foto_file) && file_exists($foto_folder . $foto_file))
    ? '/siakad/assets/img/foto_guru/' . $foto_file
    : '';

// handle update profil
$success = '';
$error = '';

// tampilkan pesan sukses dari redirect (prg)
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// handle update profil

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profil'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama'] ?? '');
    
    if (empty($nama)) {
        $error = "Nama tidak boleh kosong!";
    } else {
        // nama guru tersimpan di users.nama & guru.nama/nama_lengkap — update dua-duanya biar konsisten
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
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Location: profil.php?success=" . urlencode($success));
            exit;
        } else {
            $error = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_guru.php'; ?>
<?php include '../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-user-circle text-icon me-2"></i>Profil Saya</h4>
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
                        <?php if ($foto_src): ?>
                            <img src="<?= $foto_src ?>" alt="Foto Profil"
                                 class="rounded-circle shadow mb-3"
                                 style="width:120px; height:120px; object-fit:cover; border:4px solid #fff; box-shadow:0 4px 14px rgba(22,58,99,.18);">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; margin: 0 auto 20px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: bold;">
                                <?php
                                $nama = e($user['nama'] ?? 'Guru');
                                $nameParts = explode(' ', $nama);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                echo substr($initials, 0, 2);
                                ?>
                            </div>
                        <?php endif; ?>
                        <h5 class="card-title"><?php echo e($user['nama'] ?? 'Guru'); ?></h5>
                        <p class="text-muted mb-3">
                            <span class="badge bg-info">Guru</span>
                        </p>

                        <!-- Upload Foto Drag & Drop -->
                        <form method="POST" enctype="multipart/form-data" id="formFoto">
                            <div class="foto-dropzone mb-2" id="dropzoneFoto">
                                <div class="dz-preview">
                                    <?php if ($foto_src): ?>
                                        <img src="<?= $foto_src ?>" id="previewFoto" alt="Foto Profil">
                                    <?php else: ?>
                                        <img src="/siakad/assets/img/default-avatar.png" id="previewFoto" alt="Foto Profil">
                                    <?php endif; ?>
                                    <div class="dz-icon"><i class="fas fa-camera"></i></div>
                                    <div class="dz-text">Seret & lepas foto di sini<br>
                                        <span>atau klik untuk memilih file (maks. 5 MB)</span>
                                    </div>
                                </div>
                                <input type="file" name="foto" id="inputFoto"
                                       accept="image/jpg,image/jpeg,image/png,image/gif,image/webp" hidden>
                                <input type="hidden" name="simpan_foto" value="1">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-upload me-1"></i>Simpan Foto
                            </button>
                        </form>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Format: JPG/PNG/GIF/WEBP, Maks. 5 MB
                        </small>

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
                                <input type="text" class="form-control" id="username" value="<?php echo e($user['username'] ?? ''); ?>" disabled>
                                <small class="text-muted">Username tidak dapat diubah</small>
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo e($user['nama'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo e($user['email'] ?? '-'); ?>" disabled>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nip" class="form-label">NIP</label>
                                        <input type="text" class="form-control" id="nip" value="<?php echo e($guru['nip'] ?? '-'); ?>" disabled>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var dz     = document.getElementById('dropzoneFoto');
    var input  = document.getElementById('inputFoto');
    var img    = document.getElementById('previewFoto');
    var form   = document.getElementById('formFoto');
    if (!dz || !input || !img) return;

    function validasiFile(file) {
        var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (allowed.indexOf(file.type) === -1) {
            siToast('warning', 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.');
            return false;
        }
        if (file.size > 5 * 1024 * 1024) {
            siToast('warning', 'Ukuran foto maksimal 5 MB.');
            return false;
        }
        return true;
    }

    function tampilkanPreview(file) {
        var reader = new FileReader();
        reader.onload = function (e) { img.src = e.target.result; };
        reader.readAsDataURL(file);
    }

    dz.addEventListener('click', function () { input.click(); });

    dz.addEventListener('dragover', function (e) {
        e.preventDefault();
        dz.classList.add('dragover');
    });
    dz.addEventListener('dragleave', function () {
        dz.classList.remove('dragover');
    });
    dz.addEventListener('drop', function (e) {
        e.preventDefault();
        dz.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            var file = e.dataTransfer.files[0];
            if (validasiFile(file)) tampilkanPreview(file);
            input.files = e.dataTransfer.files;
        }
    });
    input.addEventListener('change', function () {
        if (this.files.length) {
            var file = this.files[0];
            if (validasiFile(file)) tampilkanPreview(file);
        }
    });

    // konfirmasi sebelum submit
    form.addEventListener('submit', function (e) {
        if (!input.files.length) {
            e.preventDefault();
            siToast('warning', 'Silakan pilih file foto terlebih dahulu.');
            return;
        }
        e.preventDefault();
        siConfirm({
            icon: 'question',
            title: 'Upload foto ini sebagai foto profil?',
            confirmText: 'Ya, Upload'
        }).then(function (ok) {
            if (ok) form.submit();
        });
    });
});
</script>

<?php ob_end_flush(); ?>
<?php include '../includes/footer.php'; ?>
