<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Kelola Beranda";

// Cek dan tambah kolom pengaturan yang dibutuhkan untuk beranda
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'nama_sekolah'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN nama_sekolah VARCHAR(150) DEFAULT 'SMA Negeri 4 Palopo' AFTER id");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'visi'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN visi TEXT AFTER nip_kepsek");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'misi'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN misi TEXT AFTER visi");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'telepon'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN telepon VARCHAR(50) AFTER misi");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'email'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN email VARCHAR(100) AFTER telepon");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'whatsapp'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN whatsapp VARCHAR(50) AFTER email");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'foto_kepsek'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN foto_kepsek VARCHAR(255) AFTER nama_kepsek");
}
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'foto_struktur'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN foto_struktur VARCHAR(255) AFTER foto_kepsek");
}

$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'sambutan_kepsek'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN sambutan_kepsek TEXT NULL");
}

// Ambil data pengaturan
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);

// Handle update pengaturan
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_setting'])) {
    $nama_sekolah = mysqli_real_escape_string($koneksi, $_POST['nama_sekolah'] ?? '');
    $alamat_sekolah = mysqli_real_escape_string($koneksi, $_POST['alamat_sekolah'] ?? '');
    $nama_kepsek = mysqli_real_escape_string($koneksi, $_POST['nama_kepsek'] ?? '');
    $visi = mysqli_real_escape_string($koneksi, $_POST['visi'] ?? '');
    $misi = mysqli_real_escape_string($koneksi, $_POST['misi'] ?? '');
    $sambutan_kepsek = mysqli_real_escape_string($koneksi, $_POST['sambutan_kepsek'] ?? '');
    $telepon = mysqli_real_escape_string($koneksi, $_POST['telepon'] ?? '');
    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $whatsapp = mysqli_real_escape_string($koneksi, $_POST['whatsapp'] ?? '');
    $spmb_aktif = isset($_POST['spmb_aktif']) ? 1 : 0;

    $update = mysqli_query($koneksi, "UPDATE pengaturan SET 
        nama_sekolah='$nama_sekolah', 
        alamat_sekolah='$alamat_sekolah',
        nama_kepsek='$nama_kepsek',
        visi='$visi',
        misi='$misi',
        sambutan_kepsek='$sambutan_kepsek',
        telepon='$telepon',
        email='$email',
        whatsapp='$whatsapp',
        spmb_aktif=$spmb_aktif
        WHERE id=1");

    if ($update) {
        $success = "Pengaturan beranda berhasil diperbarui!";
        $query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
        $setting = mysqli_fetch_assoc($query_setting);
    } else {
        $error = "Gagal memperbarui pengaturan: " . mysqli_error($koneksi);
    }
}

// Handle upload foto struktur organisasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_struktur'])) {
    $file = $_FILES['foto_struktur'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_size = $file['size'];
    $upload_dir = '../../assets/img/';

    if ($file_ext && !in_array($file_ext, $allowed_ext)) {
        $error = "Format file tidak diizinkan. Gunakan: jpg, jpeg, png, gif, webp";
    } elseif ($file_size > 5 * 1024 * 1024) {
        $error = "Ukuran file terlalu besar (max 5MB)";
    } else {
        $new_filename = 'struktur-organisasi.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Hapus file struktur lama bila ada (hindari file yatim bila ekstensi berubah)
            $old_file = $setting['foto_struktur'] ?? '';
            if ($old_file !== '' && $old_file !== $new_filename) {
                $old_path = $upload_dir . $old_file;
                if (is_file($old_path)) @unlink($old_path);
            }
            $update = mysqli_query($koneksi, "UPDATE pengaturan SET foto_struktur='$new_filename' WHERE id=1");
            if ($update) {
                $success = "Foto struktur organisasi berhasil diupload!";
                $setting['foto_struktur'] = $new_filename;
            }
        } else {
            $error = "Gagal mengupload file";
        }
    }
}

// Handle upload foto kepala sekolah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_kepsek'])) {
    $file = $_FILES['foto_kepsek'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_size = $file['size'];
    $upload_dir = '../../assets/img/';

    if ($file_ext && !in_array($file_ext, $allowed_ext)) {
        $error = "Format file tidak diizinkan. Gunakan: jpg, jpeg, png, gif, webp";
    } elseif ($file_size > 5 * 1024 * 1024) {
        $error = "Ukuran file terlalu besar (max 5MB)";
    } else {
        $new_filename = 'kepala-sekolah.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $update = mysqli_query($koneksi, "UPDATE pengaturan SET foto_kepsek='$new_filename' WHERE id=1");
            if ($update) {
                $success = "Foto kepala sekolah berhasil diupload!";
                $setting['foto_kepsek'] = $new_filename;
            }
        } else {
            $error = "Gagal mengupload file";
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-home text-gold me-2"></i>Kelola Beranda</h4>
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

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="berandaTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pengaturan-tab" data-bs-toggle="tab" data-bs-target="#pengaturan" type="button" role="tab">
                    <i class="fas fa-cog me-2"></i>Pengaturan Umum
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="struktur-tab" data-bs-toggle="tab" data-bs-target="#struktur" type="button" role="tab">
                    <i class="fas fa-sitemap me-2"></i>Struktur Organisasi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="galeri-tab" data-bs-toggle="tab" data-bs-target="#galeri" type="button" role="tab">
                    <i class="fas fa-images me-2"></i>Galeri Sekolah
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="berandaTabContent">

            <!-- TAB 1: Pengaturan Umum -->
            <div class="tab-pane fade show active" id="pengaturan" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Pengaturan Umum Beranda</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="nama_sekolah" class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_sekolah" name="nama_sekolah" 
                                            value="<?php echo htmlspecialchars($setting['nama_sekolah'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="alamat_sekolah" class="form-label">Alamat Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="alamat_sekolah" name="alamat_sekolah" rows="2" required><?php echo htmlspecialchars($setting['alamat_sekolah'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="nama_kepsek" class="form-label">Nama Kepala Sekolah <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_kepsek" name="nama_kepsek" 
                                            value="<?php echo htmlspecialchars($setting['nama_kepsek'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="visi" class="form-label">Visi Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="visi" name="visi" rows="3" required><?php echo htmlspecialchars($setting['visi'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="misi" class="form-label">Misi Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="misi" name="misi" rows="3" required><?php echo htmlspecialchars($setting['misi'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sambutan_kepsek" class="form-label">Sambutan Kepala Sekolah</label>
                                        <textarea class="form-control" id="sambutan_kepsek" name="sambutan_kepsek" rows="5"
                                            placeholder="Tulis sambutan kepala sekolah untuk landing page..."><?php echo htmlspecialchars($setting['sambutan_kepsek'] ?? ''); ?></textarea>
                                        <small class="text-muted">Ditampilkan di bagian "Sambutan Kepala Sekolah" pada halaman utama.</small>
                                    </div>

                                    <hr>

                                    <h6 class="mb-3"><i class="fas fa-phone me-2"></i>Informasi Kontak</h6>

                                    <div class="mb-3">
                                        <label for="telepon" class="form-label">Telepon</label>
                                        <input type="tel" class="form-control" id="telepon" name="telepon" 
                                            value="<?php echo htmlspecialchars($setting['telepon'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                            value="<?php echo htmlspecialchars($setting['email'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="whatsapp" class="form-label">WhatsApp</label>
                                        <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                            value="<?php echo htmlspecialchars($setting['whatsapp'] ?? ''); ?>">
                                    </div>

                                    <hr>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="spmb_aktif" name="spmb_aktif" 
                                            <?php echo ($setting['spmb_aktif'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="spmb_aktif">
                                            <strong>Aktifkan SPMB di Beranda</strong>
                                            <small class="text-muted d-block">Menampilkan section SPMB di landing page publik</small>
                                        </label>
                                    </div>

                                    <div class="d-flex gap-2 mt-4">
                                        <button type="submit" name="update_setting" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                        </button>
                                        <a href="javascript:location.reload()" class="btn btn-secondary">
                                            <i class="fas fa-redo me-2"></i>Refresh
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Foto Kepala Sekolah</h6>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($setting['foto_kepsek'])): ?>
                                <img src="/siakad/assets/img/<?php echo htmlspecialchars($setting['foto_kepsek']); ?>" 
                                    alt="Kepala Sekolah" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="file" class="form-control mb-2" name="foto_kepsek" accept="image/*" required>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>Upload Foto
                                    </button>
                                </form>
                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Struktur Organisasi -->
            <div class="tab-pane fade" id="struktur" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i>Struktur Organisasi Sekolah</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-4">
                                    <h6 class="mb-3">Preview Struktur Organisasi</h6>
                                    <div class="bg-light p-4 rounded text-center">
                                        <?php if (!empty($setting['foto_struktur'])): ?>
                                        <img src="/siakad/assets/img/<?php echo htmlspecialchars($setting['foto_struktur']); ?>" 
                                            alt="Struktur Organisasi" class="img-fluid rounded" style="max-height: 400px;">
                                        <?php else: ?>
                                        <div class="text-muted py-5">
                                            <i class="fas fa-image fa-3x mb-3"></i>
                                            <p>Belum ada foto struktur organisasi</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Foto</h6>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="foto_struktur" class="form-label">Pilih Foto</label>
                                                <input type="file" class="form-control" id="foto_struktur" name="foto_struktur" 
                                                    accept="image/*" required>
                                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-upload me-2"></i>Upload
                                            </button>
                                        </form>
                                        
                                        <?php if (!empty($setting['foto_struktur'])): ?>
                                        <hr>
                                        <small class="text-muted">
                                            <strong>File saat ini:</strong><br>
                                            <?php echo htmlspecialchars($setting['foto_struktur']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Galeri Sekolah -->
            <div class="tab-pane fade" id="galeri" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informasi Galeri:</strong> Galeri sekolah dikelola melalui folder di server. 
                            Upload foto ke folder yang sesuai untuk menampilkannya di beranda.
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php 
                    $galeri_kategori = [
                        ['folder' => 'foto_sekolah', 'label' => 'Foto Sekolah', 'icon' => 'fa-school', 'warna' => 'primary'],
                        ['folder' => 'foto_siswa', 'label' => 'Foto Siswa', 'icon' => 'fa-users', 'warna' => 'success'],
                        ['folder' => 'foto_guru', 'label' => 'Foto Guru & Staff', 'icon' => 'fa-chalkboard-user', 'warna' => 'info'],
                        ['folder' => 'foto_berita', 'label' => 'Foto Berita', 'icon' => 'fa-newspaper', 'warna' => 'warning'],
                        ['folder' => 'foto_program', 'label' => 'Foto Program', 'icon' => 'fa-book', 'warna' => 'danger'],
                    ];

                    foreach ($galeri_kategori as $kat):
                        $folder_path = '../../assets/img/' . $kat['folder'] . '/';
                        $files = [];
                        if (is_dir($folder_path)) {
                            $files = glob($folder_path . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
                            $files = array_map('basename', $files);
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-<?php echo $kat['warna']; ?> text-white">
                                <h6 class="mb-0">
                                    <i class="fas <?php echo $kat['icon']; ?> me-2"></i><?php echo $kat['label']; ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <strong>Jumlah Foto:</strong> <?php echo count($files); ?>
                                    </small>
                                </div>

                                <?php if (!empty($files)): ?>
                                <div class="row g-2 mb-3">
                                    <?php foreach (array_slice($files, 0, 3) as $file): ?>
                                    <div class="col-4">
                                        <img src="/siakad/assets/img/<?php echo $kat['folder']; ?>/<?php echo htmlspecialchars($file); ?>" 
                                            alt="<?php echo htmlspecialchars($file); ?>" class="img-fluid rounded" style="height: 60px; object-fit: cover;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data" action="kelola_galeri.php">
                                    <input type="hidden" name="folder" value="<?php echo $kat['folder']; ?>">
                                    <div class="mb-2">
                                        <input type="file" class="form-control form-control-sm" name="foto_galeri" 
                                            accept="image/*" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-<?php echo $kat['warna']; ?> w-100">
                                        <i class="fas fa-upload me-1"></i>Upload
                                    </button>
                                    <a href="kelola_galeri.php?folder=<?php echo $kat['folder']; ?>" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                        <i class="fas fa-images me-1"></i>Lihat Semua
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Buka tab Struktur Organisasi otomatis saat diakses via menu sidebar (admin/beranda/index.php#struktur)
document.addEventListener('DOMContentLoaded', function () {
    if (window.location.hash === '#struktur') {
        var tab = document.getElementById('struktur-tab');
        if (tab && window.bootstrap) {
            new bootstrap.Tab(tab).show();
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
