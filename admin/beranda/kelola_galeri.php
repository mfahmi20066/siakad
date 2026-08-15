<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Kelola Galeri Sekolah";
$folder = isset($_GET['folder']) ? preg_replace('/[^a-z_]/', '', $_GET['folder']) : 'foto_sekolah';
$folder_label = [
    'foto_sekolah' => 'Foto Sekolah',
    'foto_siswa' => 'Foto Siswa',
    'foto_guru' => 'Foto Guru & Staff',
    'foto_berita' => 'Foto Berita',
    'foto_program' => 'Foto Program'
];

$success = '';
$error = '';
$folder_path = '../../assets/img/' . $folder . '/';

// helper program unggulan, buat pasang foto program lewat galeri
if ($folder === 'foto_program') {
    require_once '../../config/helper_program.php';
    program_cek_table($koneksi);
}
$program_list = $folder === 'foto_program' ? program_get_all($koneksi) : [];

// bikin folder kalo belum ada
if (!is_dir($folder_path)) {
    mkdir($folder_path, 0755, true);
}

// handle upload foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_galeri'])) {
    $file = $_FILES['foto_galeri'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_size = $file['size'];

    if (!in_array($file_ext, $allowed_ext)) {
        $error = "Format file tidak diizinkan. Gunakan: jpg, jpeg, png, gif, webp";
    } elseif ($file_size > 5 * 1024 * 1024) {
        $error = "Ukuran file terlalu besar (max 5MB)";
    } else {
        // folder foto_program: foto dipasang langsung ke program unggulan (pilih via dropdown, atau otomatis ke program yang belum punya foto)
        $target_program = null;
        if ($folder === 'foto_program') {
            $pid = (int) ($_POST['pasang_program'] ?? 0);
            if ($pid === 0) {
                $q = mysqli_query($koneksi, "SELECT id, judul FROM program_unggulan WHERE foto = '' OR foto IS NULL ORDER BY urutan ASC, id ASC LIMIT 1");
                $target_program = $q ? mysqli_fetch_assoc($q) : null;
            } else {
                $q = mysqli_query($koneksi, "SELECT id, judul FROM program_unggulan WHERE id = $pid");
                $target_program = $q ? mysqli_fetch_assoc($q) : null;
            }
        }

        if ($target_program) {
            $new_filename = 'program_' . (int) $target_program['id'] . '_' . time() . '.' . $file_ext;
            $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT foto FROM program_unggulan WHERE id=" . (int) $target_program['id']));
            if (!empty($old['foto']) && is_file($folder_path . $old['foto'])) @unlink($folder_path . $old['foto']);
        } else {
            $new_filename = time() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', basename($file['name']));
        }
        $upload_path = $folder_path . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            if ($target_program) {
                mysqli_query($koneksi, "UPDATE program_unggulan SET foto='$new_filename' WHERE id=" . (int) $target_program['id']);
                $success = "Foto berhasil diupload dan dipasang ke program \"" . e($target_program['judul']) . "\"!";
            } else {
                $success = "Foto berhasil diupload!";
            }
        } else {
            $error = "Gagal mengupload file";
        }
    }
}

// handle delete foto
if (isset($_GET['delete'])) {
    verifyCsrf();
    $delete_file = preg_replace('/[^a-z0-9_.-]/i', '', $_GET['delete']);
    $delete_path = $folder_path . $delete_file;

    if (file_exists($delete_path) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $delete_path)) {
        if (unlink($delete_path)) {
            $success = "Foto berhasil dihapus!";
        } else {
            $error = "Gagal menghapus foto";
        }
    }
}

// scan folder
$files = [];
if (is_dir($folder_path)) {
    $scanned = glob($folder_path . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
    $files = array_map('basename', $scanned);
    rsort($files); // yang terbaru dulu
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-images text-icon me-2"></i><?php echo $folder_label[$folder] ?? 'Galeri'; ?></h4>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
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

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Foto Baru</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <?php if ($folder === 'foto_program' && !empty($program_list)): ?>
                                <div class="col-md-9">
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <input type="file" class="form-control" name="foto_galeri" accept="image/*" required>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="pasang_program">
                                                <option value="0">Foto dimasukkan ke program yang belum punya foto (otomatis)</option>
                                                <?php foreach ($program_list as $p): ?>
                                                <option value="<?= (int) $p['id'] ?>">Ganti foto: <?= e($p['judul']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <small class="text-muted">JPG, JPEG, PNG, GIF, WebP (Max 5MB). Foto langsung tampil di beranda.</small>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="col-md-9">
                                    <input type="file" class="form-control" name="foto_galeri" accept="image/*" required>
                                    <small class="text-muted">JPG, JPEG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>Daftar Foto (<?php echo count($files); ?> foto)</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($files)): ?>
                <div class="row g-3">
                    <?php foreach ($files as $file): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-light shadow-sm overflow-hidden">
                            <div class="ratio ratio-1x1 bg-light overflow-hidden">
                                <img src="/siakad/assets/img/<?php echo $folder; ?>/<?php echo e($file); ?>" 
                                    alt="<?php echo e($file); ?>" 
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy">
                            </div>
                            <div class="card-body p-3">
                                <p class="card-text text-truncate small mb-2" title="<?php echo e($file); ?>">
                                    <strong><?php echo e($file); ?></strong>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="/siakad/assets/img/<?php echo $folder; ?>/<?php echo e($file); ?>" 
                                        target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="fas fa-eye me-1"></i>Lihat
                                    </a>
                                    <a href="?folder=<?php echo $folder; ?>&delete=<?php echo e($file); ?>" 
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return siHapus('?folder=<?php echo $folder; ?>&delete=<?php echo e($file); ?>', '<?php echo addslashes(e($file)); ?>');">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada foto di folder ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
