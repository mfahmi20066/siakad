<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Kelola Berita & Pengumuman";
$user_id = (int)($_SESSION['user_id'] ?? 0);

// Cek dan tambah kolom ringkasan jika belum ada
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM berita_sekolah LIKE 'ringkasan'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE berita_sekolah ADD COLUMN ringkasan TEXT AFTER judul");
}

// Cek dan tambah kolom kategori jika belum ada
$cek_kategori = mysqli_query($koneksi, "SHOW COLUMNS FROM berita_sekolah LIKE 'kategori'");
if (mysqli_num_rows($cek_kategori) == 0) {
    mysqli_query($koneksi, "ALTER TABLE berita_sekolah ADD COLUMN kategori VARCHAR(100) DEFAULT 'Umum' AFTER isi");
}

// Cek dan tambah kolom gambar jika belum ada
$cek_gambar = mysqli_query($koneksi, "SHOW COLUMNS FROM berita_sekolah LIKE 'gambar'");
if (mysqli_num_rows($cek_gambar) == 0) {
    mysqli_query($koneksi, "ALTER TABLE berita_sekolah ADD COLUMN gambar VARCHAR(255) AFTER kategori");
}

$success = '';
$error = '';

// Handle tambah berita
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_berita'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul'] ?? '');
    $ringkasan = mysqli_real_escape_string($koneksi, $_POST['ringkasan'] ?? '');
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal'] ?? date('Y-m-d H:i:s'));

    if (empty($judul) || empty($isi)) {
        $error = "Judul dan isi tidak boleh kosong!";
    } else {
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['size'] > 0) {
            $file = $_FILES['gambar'];
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];

            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Format gambar tidak diizinkan. Gunakan: jpg, jpeg, png, gif, webp";
            } elseif ($file_size > 5 * 1024 * 1024) {
                $error = "Ukuran gambar terlalu besar (max 5MB)";
            } else {
                $new_filename = time() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', basename($file['name']));
                $upload_path = '../../assets/img/foto_berita/' . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                } else {
                    $error = "Gagal mengupload gambar";
                }
            }
        }

        if (empty($error)) {
            $insert = mysqli_query($koneksi, "INSERT INTO berita_sekolah (judul, ringkasan, isi, gambar, kategori, tanggal, admin_id) 
                VALUES ('$judul', '$ringkasan', '$isi', '$gambar', '$kategori', '$tanggal', $user_id)");

            if ($insert) {
                $success = "Berita berhasil ditambahkan!";
            } else {
                $error = "Gagal menambah berita: " . mysqli_error($koneksi);
            }
        }
    }
}

// Handle edit berita
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_berita'])) {
    $id = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul'] ?? '');
    $ringkasan = mysqli_real_escape_string($koneksi, $_POST['ringkasan'] ?? '');
    $isi = mysqli_real_escape_string($koneksi, $_POST['isi'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');

    if (empty($judul) || empty($isi)) {
        $error = "Judul dan isi tidak boleh kosong!";
    } else {
        $query_old = "SELECT gambar FROM berita_sekolah WHERE id=$id";
        $result_old = mysqli_query($koneksi, $query_old);
        $old_data = mysqli_fetch_assoc($result_old);
        $gambar = $old_data['gambar'];

        if (isset($_FILES['gambar']) && $_FILES['gambar']['size'] > 0) {
            $file = $_FILES['gambar'];
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];

            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Format gambar tidak diizinkan. Gunakan: jpg, jpeg, png, gif, webp";
            } elseif ($file_size > 5 * 1024 * 1024) {
                $error = "Ukuran gambar terlalu besar (max 5MB)";
            } else {
                $new_filename = time() . '_' . preg_replace('/[^a-z0-9_.-]/i', '', basename($file['name']));
                $upload_path = '../../assets/img/foto_berita/' . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Hapus gambar lama
                    if ($gambar && file_exists('../../assets/img/foto_berita/' . $gambar)) {
                        unlink('../../assets/img/foto_berita/' . $gambar);
                    }
                    $gambar = $new_filename;
                } else {
                    $error = "Gagal mengupload gambar";
                }
            }
        }

        if (empty($error)) {
            $update = mysqli_query($koneksi, "UPDATE berita_sekolah SET 
                judul='$judul', ringkasan='$ringkasan', isi='$isi', gambar='$gambar', kategori='$kategori'
                WHERE id=$id");

            if ($update) {
                $success = "Berita berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui berita: " . mysqli_error($koneksi);
            }
        }
    }
}

// Handle hapus berita
if (isset($_GET['hapus'])) {
    verifyCsrf();
    $id = (int)$_GET['hapus'];
    
    $query_get = "SELECT gambar FROM berita_sekolah WHERE id=$id";
    $result_get = mysqli_query($koneksi, $query_get);
    $data = mysqli_fetch_assoc($result_get);
    
    if ($data && $data['gambar']) {
        $gambar_path = '../../assets/img/foto_berita/' . $data['gambar'];
        if (file_exists($gambar_path)) {
            unlink($gambar_path);
        }
    }
    
    $delete = mysqli_query($koneksi, "DELETE FROM berita_sekolah WHERE id=$id");
    if ($delete) {
        $success = "Berita berhasil dihapus!";
    } else {
        $error = "Gagal menghapus berita";
    }
}

// Ambil data berita
$query_berita = mysqli_query($koneksi, "SELECT * FROM berita_sekolah ORDER BY tanggal DESC");
$berita_list = [];
while ($row = mysqli_fetch_assoc($query_berita)) {
    $berita_list[] = $row;
}

// Mode edit
$edit_mode = false;
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query_edit = "SELECT * FROM berita_sekolah WHERE id=$edit_id";
    $result_edit = mysqli_query($koneksi, $query_edit);
    if ($result_edit && mysqli_num_rows($result_edit) > 0) {
        $edit_mode = true;
        $edit_data = mysqli_fetch_assoc($result_edit);
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="container-fluid px-4 py-3">
        <div class="page-header mb-4">
            <h4><i class="fas fa-newspaper text-icon me-2"></i>Kelola Berita & Pengumuman</h4>
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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list me-2"></i>Daftar Berita (<?php echo count($berita_list); ?> berita)</span>
                            <?php if ($edit_mode): ?>
                            <a href="?" class="btn btn-sm btn-secondary">
                                <i class="fas fa-plus me-1"></i>Tambah Baru
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($berita_list)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($berita_list as $berita): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e(substr($berita['judul'], 0, 40)); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo tanggal_waktu_indo($berita['tanggal']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo e($berita['kategori'] ?? 'Umum'); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="?edit=<?php echo $berita['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?hapus=<?php echo $berita['id']; ?>" class="btn btn-sm btn-danger" 
                                                onclick="return siHapus('?hapus=<?php echo $berita['id']; ?>', '<?php echo addslashes(e($berita['judul'])); ?>');" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada berita</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus'; ?> me-2"></i>
                            <?php echo $edit_mode ? 'Edit Berita' : 'Tambah Berita Baru'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if ($edit_mode): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="judul" name="judul" 
                                    value="<?php echo e($edit_data['judul'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="ringkasan" class="form-label">Ringkasan</label>
                                <textarea class="form-control" id="ringkasan" name="ringkasan" rows="2"><?php echo e($edit_data['ringkasan'] ?? ''); ?></textarea>
                                <small class="text-muted">Opsional, untuk preview di halaman beranda</small>
                            </div>

                            <div class="mb-3">
                                <label for="isi" class="form-label">Isi Berita <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="isi" name="isi" rows="4" required><?php echo e($edit_data['isi'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="kategori" name="kategori" 
                                    value="<?php echo e($edit_data['kategori'] ?? ''); ?>">
                                <small class="text-muted">Contoh: Akademik, Pengumuman, Kegiatan</small>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal" class="form-label">Tanggal & Waktu</label>
                                <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" 
                                    value="<?php echo $edit_data ? date('Y-m-d\TH:i', strtotime($edit_data['tanggal'])) : date('Y-m-d\TH:i'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar</label>
                                <?php if ($edit_mode && $edit_data['gambar']): ?>
                                <div class="mb-2">
                                    <img src="/siakad/assets/img/foto_berita/<?php echo e($edit_data['gambar']); ?>" 
                                        alt="Gambar Berita" class="img-thumbnail" style="max-height: 100px;">
                                    <small class="d-block text-muted mt-1">Ganti dengan upload baru jika perlu</small>
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="<?php echo $edit_mode ? 'edit_berita' : 'tambah_berita'; ?>" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-save me-2"></i><?php echo $edit_mode ? 'Simpan Perubahan' : 'Tambah Berita'; ?>
                                </button>
                                <?php if ($edit_mode): ?>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
