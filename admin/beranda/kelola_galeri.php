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

// Buat folder jika belum ada
if (!is_dir($folder_path)) {
    mkdir($folder_path, 0755, true);
}

// Handle upload foto
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
        $new_filename = time() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', basename($file['name']));
        $upload_path = $folder_path . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $success = "Foto berhasil diupload!";
        } else {
            $error = "Gagal mengupload file";
        }
    }
}

// Handle delete foto
if (isset($_GET['delete'])) {
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

// Scan folder
$files = [];
if (is_dir($folder_path)) {
    $scanned = glob($folder_path . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
    $files = array_map('basename', $scanned);
    rsort($files); // Sort newest first
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-images text-gold me-2"></i><?php echo $folder_label[$folder] ?? 'Galeri'; ?></h4>
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
                                <div class="col-md-9">
                                    <input type="file" class="form-control" name="foto_galeri" accept="image/*" required>
                                    <small class="text-muted">JPG, JPEG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload
                                    </button>
                                </div>
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
                                <img src="/siakad/assets/img/<?php echo $folder; ?>/<?php echo htmlspecialchars($file); ?>" 
                                    alt="<?php echo htmlspecialchars($file); ?>" 
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy">
                            </div>
                            <div class="card-body p-3">
                                <p class="card-text text-truncate small mb-2" title="<?php echo htmlspecialchars($file); ?>">
                                    <strong><?php echo htmlspecialchars($file); ?></strong>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="/siakad/assets/img/<?php echo $folder; ?>/<?php echo htmlspecialchars($file); ?>" 
                                        target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="fas fa-eye me-1"></i>Lihat
                                    </a>
                                    <a href="?folder=<?php echo $folder; ?>&delete=<?php echo htmlspecialchars($file); ?>" 
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return siHapus('?folder=<?php echo $folder; ?>&delete=<?php echo htmlspecialchars($file); ?>', '<?php echo addslashes(htmlspecialchars($file)); ?>');">
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
