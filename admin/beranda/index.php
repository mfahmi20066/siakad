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

// ===== Program Unggulan (dinamis, bisa diedit admin) =====
require_once '../../config/helper_program.php';
program_cek_table($koneksi);
program_seed_default($koneksi);

$edit_program = null;
if (isset($_GET['edit_program'])) {
    $edit_id = (int) $_GET['edit_program'];
    $ep = mysqli_query($koneksi, "SELECT * FROM program_unggulan WHERE id=$edit_id");
    if ($ep && mysqli_num_rows($ep) > 0) $edit_program = mysqli_fetch_assoc($ep);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi_program'])) {
    $judul     = mysqli_real_escape_string($koneksi, trim($_POST['judul'] ?? ''));
    $deskripsi = mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'] ?? ''));
    $ikon      = mysqli_real_escape_string($koneksi, trim($_POST['ikon'] ?? ''));
    if ($ikon === '') $ikon = 'fa-star';
    $urutan = (int) ($_POST['urutan_program'] ?? 0);

    if ($judul === '' || $deskripsi === '') {
        $error = 'Judul dan deskripsi program wajib diisi.';
    } elseif (isset($_POST['id_program']) && $_POST['id_program'] !== '') {
        $id = (int) $_POST['id_program'];
        $upd = mysqli_query($koneksi, "UPDATE program_unggulan SET judul='$judul', deskripsi='$deskripsi', ikon='$ikon', urutan=$urutan WHERE id=$id");
        if ($upd) {
            $new_foto = program_simpan_foto($koneksi, $id, $_FILES['foto_program'] ?? null);
            if (($_FILES['foto_program']['error'] ?? 4) !== 4 && $new_foto === '') {
                $error = 'Gagal mengupload foto (format jpg/jpeg/png/gif/webp, maks 2MB).';
            } else {
                $_SESSION['flash_success'] = "Program \"$judul\" berhasil diperbarui!";
                header("Location: index.php#program");
                exit();
            }
        } else {
            $error = 'Gagal memperbarui program: ' . mysqli_error($koneksi);
        }
    } else {
        $ins = mysqli_query($koneksi, "INSERT INTO program_unggulan (judul, deskripsi, ikon, urutan) VALUES ('$judul', '$deskripsi', '$ikon', $urutan)");
        if ($ins) {
            $id = (int) mysqli_insert_id($koneksi);
            $new_foto = program_simpan_foto($koneksi, $id, $_FILES['foto_program'] ?? null);
            if (($_FILES['foto_program']['error'] ?? 4) !== 4 && $new_foto === '') {
                $error = 'Gagal mengupload foto (format jpg/jpeg/png/gif/webp, maks 2MB).';
            } else {
                $_SESSION['flash_success'] = "Program \"$judul\" berhasil ditambahkan!";
                header("Location: index.php#program");
                exit();
            }
        } else {
            $error = 'Gagal menambahkan program: ' . mysqli_error($koneksi);
        }
    }
}

if (isset($_GET['hapus_program'])) {
    $id = (int) $_GET['hapus_program'];
    mysqli_query($koneksi, "DELETE FROM program_unggulan WHERE id=$id");
    $success = 'Program berhasil dihapus.';
}

if (isset($_GET['naik_program']) || isset($_GET['turun_program'])) {
    $id   = (int) ($_GET['naik_program'] ?? $_GET['turun_program']);
    $naik = isset($_GET['naik_program']);
    $row  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM program_unggulan WHERE id=$id"));
    if ($row) {
        $u = (int) $row['urutan'];
        $sibling = mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT * FROM program_unggulan WHERE id<>$id AND urutan " . ($naik ? "< $u" : "> $u")
            . " ORDER BY urutan " . ($naik ? "DESC" : "ASC") . " LIMIT 1"));
        if ($sibling) {
            mysqli_query($koneksi, "UPDATE program_unggulan SET urutan={$sibling['urutan']} WHERE id=$id");
            mysqli_query($koneksi, "UPDATE program_unggulan SET urutan=$u WHERE id={$sibling['id']}");
        }
    }
}

// Hapus foto program (link Hapus Foto di form edit & tabel)
if (isset($_GET['hapus_foto_program'])) {
    program_hapus_foto($koneksi, (int) $_GET['hapus_foto_program']);
    $_SESSION['flash_success'] = 'Foto program berhasil dihapus.';
    header("Location: index.php#program");
    exit();
}

// Handle update pengaturan
$success = '';
$error = '';

// Flash message dari redirect POST (agar pesan sukses tetap tampil setelah redirect ke tab)
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

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
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-home text-icon me-2"></i>Kelola Beranda</h4>
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
                <button class="nav-link" id="program-tab" data-bs-toggle="tab" data-bs-target="#program" type="button" role="tab">
                    <i class="fas fa-rocket me-2"></i>Program Unggulan
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
                                            value="<?php echo e($setting['nama_sekolah'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="alamat_sekolah" class="form-label">Alamat Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="alamat_sekolah" name="alamat_sekolah" rows="2" required><?php echo e($setting['alamat_sekolah'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="nama_kepsek" class="form-label">Nama Kepala Sekolah <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_kepsek" name="nama_kepsek" 
                                            value="<?php echo e($setting['nama_kepsek'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="visi" class="form-label">Visi Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="visi" name="visi" rows="3" required><?php echo e($setting['visi'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="misi" class="form-label">Misi Sekolah <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="misi" name="misi" rows="3" required><?php echo e($setting['misi'] ?? ''); ?></textarea>
                                        <small class="form-text text-muted">Tulis satu misi per baris — setiap baris tampil sebagai poin di beranda.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sambutan_kepsek" class="form-label">Sambutan Kepala Sekolah</label>
                                        <textarea class="form-control" id="sambutan_kepsek" name="sambutan_kepsek" rows="5"
                                            placeholder="Tulis sambutan kepala sekolah untuk landing page..."><?php echo e($setting['sambutan_kepsek'] ?? ''); ?></textarea>
                                        <small class="text-muted">Ditampilkan di bagian "Sambutan Kepala Sekolah" pada halaman utama.</small>
                                    </div>

                                    <hr>

                                    <h6 class="mb-3"><i class="fas fa-phone me-2"></i>Informasi Kontak</h6>

                                    <div class="mb-3">
                                        <label for="telepon" class="form-label">Telepon</label>
                                        <input type="tel" class="form-control" id="telepon" name="telepon" 
                                            value="<?php echo e($setting['telepon'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                            value="<?php echo e($setting['email'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="whatsapp" class="form-label">WhatsApp</label>
                                        <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                            value="<?php echo e($setting['whatsapp'] ?? ''); ?>">
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
                                <img src="/siakad/assets/img/<?php echo e($setting['foto_kepsek']); ?>" 
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

            <!-- TAB 2: Struktur Organisasi (Upload Foto) -->
            <div class="tab-pane fade" id="struktur" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i>Struktur Organisasi</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-info-circle me-2"></i>Upload foto struktur organisasi untuk ditampilkan di beranda.
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card text-center mb-4">
                                    <div class="card-body">
                                        <?php if (!empty($setting['foto_struktur'])): ?>
                                        <img src="/siakad/assets/img/<?php echo e($setting['foto_struktur']); ?>"
                                            alt="Struktur Organisasi" class="img-thumbnail mb-3" style="max-width:100%">
                                        <?php else: ?>
                                        <div class="rounded mb-3 p-4 bg-light d-inline-block" style="max-width:250px;">
                                            <i class="fas fa-sitemap fa-3x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="file" class="form-control mb-2" name="foto_struktur" accept="image/*" required>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-upload me-2"></i>Upload Foto
                                            </button>
                                        </form>
                                        <small class="d-block mt-3 text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="preview-photo mb-3">
                                    <?php if (!empty($setting['foto_struktur'])): ?>
                                    <img src="/siakad/assets/img/<?php echo e($setting['foto_struktur']); ?>"
                                        alt="Preview Struktur Organisasi" class="img-fluid rounded shadow-sm">
                                    <div class="text-center mt-2 text-muted">
                                        <small>Foto struktur organisasi akan ditampilkan di beranda (fallback bila struktur dinamis kosong).</small>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-circle me-2"></i>Belum ada foto struktur organisasi. Silakan upload foto untuk mengganti tampilan beranda.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<!-- TAB 3: Program Unggulan (dinamis, bisa diedit) -->
            <div class="tab-pane fade" id="program" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-rocket me-2"></i>Program Unggulan Sekolah</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info py-2">
                            <i class="fas fa-info-circle me-2"></i>Program ini tampil di beranda (section
                            <strong>Program Unggulan)</strong> dan diperbarui otomatis dari data di bawah.
                            Berita sekolah tidak terpengaruh.
                        </div>
                        <div class="row">
                            <div class="col-lg-7">
                                <h6 class="mb-3">Preview di Beranda</h6>
                                <div class="row g-3">
                                    <?php foreach (program_get_all($koneksi) as $i => $p): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 border-0 shadow-sm overflow-hidden rounded-3">
                                            <div class="text-center d-flex align-items-center justify-content-center position-relative"
                                                 style="height:110px;background:linear-gradient(135deg,rgba(4,70,128,.08),rgba(240,144,0,.10));">
                                                <i class="fas <?= e($p['ikon']) ?>" style="font-size:36px;color:var(--primary);opacity:.75;"></i>
                                                <span class="badge position-absolute top-0 end-0 m-2" style="background:var(--primary);"><?= $i + 1 ?></span>
                                            </div>
                                            <div class="card-body text-center">
                                                <h6 class="fw-bold mb-1" style="color:var(--primary);"><?= e($p['judul']) ?></h6>
                                                <p class="text-muted small mb-0"><?= e($p['deskripsi']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card border-primary mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="fas <?= $edit_program ? 'fa-pen' : 'fa-plus' ?> me-2"></i>
                                            <?= $edit_program ? 'Edit Program' : 'Tambah Program' ?>
                                            <?php if ($edit_program): ?>
                                                <a href="index.php#program" class="btn btn-sm btn-outline-secondary float-end" title="Batal"><i class="fas fa-times"></i></a>
                                            <?php endif; ?>
                                        </h6>
                                        <form method="POST" enctype="multipart/form-data">
                                            <?php if ($edit_program): ?>
                                            <input type="hidden" name="id_program" value="<?= (int) $edit_program['id'] ?>">
                                            <?php endif; ?>
                                            <input type="hidden" name="aksi_program" value="1">
                                            <div class="mb-3">
                                                <label class="form-label">Judul Program <span class="text-danger">*</span></label>
                                                <input type="text" name="judul" class="form-control" required
                                                       value="<?= e($edit_program['judul'] ?? '') ?>"
                                                       placeholder="cth: Program IPA">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                                <textarea name="deskripsi" class="form-control" rows="3" required
                                                          placeholder="Penjelasan singkat program..."><?= e($edit_program['deskripsi'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ikon (Font Awesome)</label>
                                                <input type="text" name="ikon" class="form-control" list="ikon-program"
                                                       value="<?= e($edit_program['ikon'] ?? '') ?>"
                                                       placeholder="cth: fa-flask">
                                                <datalist id="ikon-program">
                                                    <option value="fa-flask"><option value="fa-landmark"><option value="fa-futbol">
                                                    <option value="fa-laptop"><option value="fa-book"><option value="fa-microscope">
                                                    <option value="fa-music"><option value="fa-palette"><option value="fa-dumbbell">
                                                    <option value="fa-users"><option value="fa-robot"><option value="fa-globe">
                                                    <option value="fa-calculator"><option value="fa-atom"><option value="fa-language">
                                                    <option value="fa-star"><option value="fa-lightbulb"><option value="fa-chalkboard-user">
                                                </datalist>
                                                <small class="text-muted">Nama ikon dari <code>fontawesome.com/icons</code>. Kosongkan untuk ikon default bintang.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Foto Program</label>
                                                <?php if (!empty($edit_program['foto']) && is_file('../../assets/img/foto_program/' . $edit_program['foto'])): ?>
                                                    <div class="mb-2">
                                                        <img src="/siakad/assets/img/foto_program/<?= e($edit_program['foto']) ?>"
                                                            alt="<?= e($edit_program['judul']) ?>" class="img-thumbnail d-block" style="max-width:100%;max-height:120px;object-fit:cover;">
                                                        <a href="index.php?hapus_foto_program=<?= (int) $edit_program['id'] ?>#program" class="btn btn-sm btn-outline-danger mt-1"
                                                        onclick="return siHapus('index.php?hapus_foto_program=<?= (int) $edit_program['id'] ?>#program', 'foto <?= e($edit_program['judul']) ?>');"><i class="fas fa-trash"></i> Hapus foto</a>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" name="foto_program" class="form-control" accept="image/*">
                                                <small class="text-muted">Format: jpg/jpeg/png/gif/webp (max 2MB). Kosongkan bila tidak ganti.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Urutan</label>
                                                <input type="number" name="urutan_program" class="form-control" min="0"
                                                    value="<?= (int) ($edit_program['urutan'] ?? 0) ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-2"></i><?= $edit_program ? 'Simpan Perubahan' : 'Tambah Program' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <h6 class="mb-3">Daftar Program</h6>
                                <div class="table-responsive" style="max-height:420px; overflow:auto;">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Foto</th>
                                                    <th>Program</th>
                                                    <th style="width:200px;" class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach (program_get_all($koneksi) as $p): ?>
                                                <tr>
                                                    <td class="text-center align-middle" style="width:70px;">
                                                        <?php if (!empty($p['foto']) && is_file('../../assets/img/foto_program/' . $p['foto'])): ?>
                                                            <img src="/siakad/assets/img/foto_program/<?= e($p['foto']) ?>"
                                                                alt="<?= e($p['judul']) ?>"
                                                                class="img-thumbnail" style="max-width:60px;max-height:44px;object-fit:cover;">
                                                        <?php else: ?>
                                                            <span class="text-muted small">belum</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <i class="fas <?= e($p['ikon']) ?> text-primary me-1"></i>
                                                        <strong><?= e($p['judul']) ?></strong>
                                                        <br><small class="text-muted"><?= e(mb_strimwidth($p['deskripsi'], 0, 60, '…')) ?></small>
                                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        <?php if (!empty($p['foto']) && is_file('../../assets/img/foto_program/' . $p['foto'])): ?>
                                                            <a href="index.php?hapus_foto_program=<?= (int) $p['id'] ?>#program" class="btn btn-sm btn-outline-danger" title="Hapus foto"
                                                            onclick="return siHapus('index.php?hapus_foto_program=<?= (int) $p['id'] ?>#program', 'foto <?= e($p['judul']) ?>');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="index.php?edit_program=<?= (int) $p['id'] ?>#program" class="btn btn-sm btn-outline-primary" title="Edit (ganti juga fotonya di sini)"><i class="fas fa-pen"></i></a>
                                                        <a href="index.php?naik_program=<?= (int) $p['id'] ?>#program" class="btn btn-sm btn-outline-secondary" title="Naik"><i class="fas fa-chevron-up"></i></a>
                                                        <a href="index.php?turun_program=<?= (int) $p['id'] ?>#program" class="btn btn-sm btn-outline-secondary" title="Turun"><i class="fas fa-chevron-down"></i></a>
                                                        <a href="index.php?hapus_program=<?= (int) $p['id'] ?>#program" class="btn btn-sm btn-outline-danger" title="Hapus"
                                                           onclick="return siHapus('index.php?hapus_program=<?= (int) $p['id'] ?>#program', '<?= addslashes(e($p['judul'])) ?>');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Galeri Sekolah -->
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
                    $program_list_admin = program_get_all($koneksi);
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
                                        <img src="/siakad/assets/img/<?php echo $kat['folder']; ?>/<?php echo e($file); ?>" 
                                            alt="<?php echo e($file); ?>" class="img-fluid rounded" style="height: 60px; object-fit: cover;">
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
                                    <?php if ($kat['folder'] === 'foto_program' && !empty($program_list_admin)): ?>
                                    <div class="mb-2">
                                        <select class="form-select form-select-sm" name="pasang_program">
                                            <option value="0">Pasang otomatis ke program tanpa foto</option>
                                            <?php foreach ($program_list_admin as $p): ?>
                                            <option value="<?= (int) $p['id'] ?>">Ganti foto: <?= e($p['judul']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
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
// Buka tab otomatis sesuai hash URL (mis. #program, #struktur, #galeri) — supaya
// setelah edit/simpan/hapus pengguna tetap berada di tab yang sama.
document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash;
    if (!hash) return;
    var tabId = hash.replace('#', '') + '-tab';
    var tab = document.getElementById(tabId);
    if (tab && window.bootstrap) {
        new bootstrap.Tab(tab).show();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
