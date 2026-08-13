<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Pengaturan SPMB Online";

// 1. Tambah kolom SPMB ke tabel pengaturan jika belum ada
$cek_kolom_spmb = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'spmb_aktif'");
if (mysqli_num_rows($cek_kolom_spmb) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_aktif TINYINT DEFAULT 0 AFTER quote");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_tanggal_buka DATE AFTER spmb_aktif");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_tanggal_tutup DATE AFTER spmb_tanggal_buka");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_jalur TEXT AFTER spmb_tanggal_tutup");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_syarat TEXT AFTER spmb_jalur");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_link_daftar VARCHAR(255) AFTER spmb_syarat");
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN spmb_pengumuman_aktif TINYINT DEFAULT 0 AFTER spmb_link_daftar");
}

// 2. Tambah kolom spmb_dokumen jika belum ada
$cek_dokumen = mysqli_query($koneksi, "SHOW COLUMNS FROM spmb_dokumen LIKE 'status_verifikasi'");
if (mysqli_num_rows($cek_dokumen) == 0) {
    mysqli_query($koneksi, "ALTER TABLE spmb_dokumen ADD COLUMN status_verifikasi ENUM('menunggu','valid','tidak_valid') DEFAULT 'menunggu' AFTER path_file");
}

// 3. Proses CRUD Jalur
if (isset($_POST['tambah_jalur'])) {
    $nama_jalur = mysqli_real_escape_string($koneksi, $_POST['nama_jalur']);
    $kuota = (int)$_POST['kuota'];
    $dokumen_wajib = json_encode(['kk', 'akta', 'ijazah', 'foto']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    $insert = mysqli_query($koneksi, "INSERT INTO spmb_jalur (nama_jalur, kuota, dokumen_wajib, keterangan) VALUES ('$nama_jalur', $kuota, '$dokumen_wajib', '$keterangan')");
    
    if ($insert) {
        $success = "Jalur berhasil ditambahkan!";
    } else {
        $error = "Gagal menambah jalur: " . mysqli_error($koneksi);
    }
}

if (isset($_POST['edit_jalur'])) {
    $id = (int)$_POST['id'];
    $nama_jalur = mysqli_real_escape_string($koneksi, $_POST['nama_jalur']);
    $kuota = (int)$_POST['kuota'];
    $dokumen_wajib = json_encode(['kk', 'akta', 'ijazah', 'foto']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    $update = mysqli_query($koneksi, "UPDATE spmb_jalur SET 
        nama_jalur='$nama_jalur', kuota=$kuota, dokumen_wajib='$dokumen_wajib', keterangan='$keterangan' 
        WHERE id=$id");
    
    if ($update) {
        $success = "Jalur berhasil diupdate!";
    } else {
        $error = "Gagal update jalur: " . mysqli_error($koneksi);
    }
}

if (isset($_GET['hapus_jalur'])) {
    verifyCsrf();
    $id = (int)$_GET['hapus_jalur'];
    $delete = mysqli_query($koneksi, "DELETE FROM spmb_jalur WHERE id=$id");
    
    if ($delete) {
        $success = "Jalur berhasil dihapus!";
    } else {
        $error = "Gagal hapus jalur: " . mysqli_error($koneksi);
    }
}

// 3.1 Proses CRUD Gelombang
if (isset($_POST['tambah_gelombang'])) {
    $nama_gelombang = mysqli_real_escape_string($koneksi, $_POST['nama_gelombang']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $status = $_POST['status_gelombang'] ?? 'nonaktif';
    
    $insert = mysqli_query($koneksi, "INSERT INTO spmb_gelombang (nama_gelombang, tanggal_mulai, tanggal_selesai, status) VALUES ('$nama_gelombang', '$tanggal_mulai', '$tanggal_selesai', '$status')");
    
    if ($insert) {
        $success = "Gelombang berhasil dibuka!";
    } else {
        $error = "Gagal membuka gelombang: " . mysqli_error($koneksi);
    }
}

if (isset($_POST['edit_gelombang'])) {
    $id = (int)$_POST['id'];
    $nama_gelombang = mysqli_real_escape_string($koneksi, $_POST['nama_gelombang']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $status = $_POST['status_gelombang'] ?? 'nonaktif';
    
    $update = mysqli_query($koneksi, "UPDATE spmb_gelombang SET 
        nama_gelombang='$nama_gelombang', tanggal_mulai='$tanggal_mulai', tanggal_selesai='$tanggal_selesai', status='$status' 
        WHERE id=$id");
    
    if ($update) {
        $success = "Gelombang berhasil diupdate!";
    } else {
        $error = "Gagal update gelombang: " . mysqli_error($koneksi);
    }
}

if (isset($_GET['hapus_gelombang'])) {
    verifyCsrf();
    $id = (int)$_GET['hapus_gelombang'];
    $delete = mysqli_query($koneksi, "DELETE FROM spmb_gelombang WHERE id=$id");
    
    if ($delete) {
        $success = "Gelombang berhasil ditutup!";
    } else {
        $error = "Gagal tutup gelombang: " . mysqli_error($koneksi);
    }
}

// 4. Proses form update SPMB
if (isset($_POST['update_settings']) && !isset($_POST['tambah_jalur']) && !isset($_POST['edit_jalur'])) {
    $spmb_aktif = isset($_POST['spmb_aktif']) ? 1 : 0;
    $spmb_tanggal_buka = mysqli_real_escape_string($koneksi, $_POST['spmb_tanggal_buka'] ?? '');
    $spmb_tanggal_tutup = mysqli_real_escape_string($koneksi, $_POST['spmb_tanggal_tutup'] ?? '');
    $spmb_jalur = mysqli_real_escape_string($koneksi, $_POST['spmb_jalur'] ?? '');
    $spmb_syarat = mysqli_real_escape_string($koneksi, $_POST['spmb_syarat'] ?? '');
    $spmb_link_daftar = mysqli_real_escape_string($koneksi, $_POST['spmb_link_daftar'] ?? '');
    $spmb_pengumuman_aktif = isset($_POST['spmb_pengumuman_aktif']) ? 1 : 0;

    $update = mysqli_query($koneksi, "UPDATE pengaturan SET 
        spmb_aktif=$spmb_aktif,
        spmb_tanggal_buka='$spmb_tanggal_buka',
        spmb_tanggal_tutup='$spmb_tanggal_tutup',
        spmb_jalur='$spmb_jalur',
        spmb_syarat='$spmb_syarat',
        spmb_link_daftar='$spmb_link_daftar',
        spmb_pengumuman_aktif=$spmb_pengumuman_aktif
        WHERE id=1");

    if ($update) {
        $success = "Pengaturan SPMB berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui pengaturan: " . mysqli_error($koneksi);
    }
}

// 5. Ambil data pengaturan
$query_settings = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$settings = mysqli_fetch_assoc($query_settings);

// 6. Ambil data jalur
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");

// 6.1 Ambil data gelombang
$query_gelombang = mysqli_query($koneksi, "SELECT * FROM spmb_gelombang ORDER BY tanggal_mulai ASC");

// 7. Ambil data pendaftar dengan status dokumen
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($koneksi, $_GET['filter_status']) : '';

$query_where = "WHERE 1=1";
if (!empty($search)) {
    $query_where .= " AND (sp.nama_lengkap LIKE '%$search%' OR sp.no_pendaftaran LIKE '%$search%' OR sp.email LIKE '%$search%')";
}
if (!empty($filter_status)) {
    $query_where .= " AND sp.status = '$filter_status'";
}

$query_pendaftar = mysqli_query($koneksi, "
    SELECT sp.*, sj.nama_jalur, COUNT(sd.id) as total_dokumen
    FROM spmb_pendaftar sp
    LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
    LEFT JOIN spmb_dokumen sd ON sp.id = sd.pendaftar_id
    $query_where
    GROUP BY sp.id
    ORDER BY sp.created_at DESC
");

// 8. Proses verifikasi dokumen
if (isset($_POST['verifikasi_dokumen'])) {
    $pendaftar_id = (int)$_POST['pendaftar_id'];
    $status_verifikasi = mysqli_real_escape_string($koneksi, $_POST['status_verifikasi']);
    $jenis_dokumen = mysqli_real_escape_string($koneksi, $_POST['jenis_dokumen']);
    
    $update_dokumen = mysqli_query($koneksi, "
        UPDATE spmb_dokumen 
        SET status_verifikasi = '$status_verifikasi'
        WHERE pendaftar_id = $pendaftar_id AND jenis_dokumen = '$jenis_dokumen'
    ");
    
    if ($update_dokumen) {
        // Cek apakah semua dokumen sudah diverifikasi
        $check_all = mysqli_query($koneksi, "
            SELECT COUNT(*) as belum_verifikasi 
            FROM spmb_dokumen 
            WHERE pendaftar_id = $pendaftar_id AND jenis_dokumen IN ('kk', 'akta', 'ijazah', 'foto') AND status_verifikasi = 'menunggu'
        ");
        $result_check = mysqli_fetch_assoc($check_all);
        
        if ($result_check['belum_verifikasi'] == 0) {
            // Update status pendaftar ke diverifikasi
            mysqli_query($koneksi, "UPDATE spmb_pendaftar SET status = 'diverifikasi' WHERE id = $pendaftar_id");
        }
        
        $success = "Dokumen berhasil diverifikasi!";
    } else {
        $error = "Gagal memverifikasi dokumen: " . mysqli_error($koneksi);
    }
}

// 9. Proses update status pendaftar (diterima/ditolak)
if (isset($_POST['update_status_pendaftar'])) {
    $pendaftar_id = (int)$_POST['pendaftar_id'];
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_baru']);
    
    $update_status = mysqli_query($koneksi, "
        UPDATE spmb_pendaftar 
        SET status = '$status_baru'
        WHERE id = $pendaftar_id
    ");
    
    if ($update_status) {
        $success = "Status pendaftar berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui status: " . mysqli_error($koneksi);
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="container-fluid px-4 py-3">
        <div class="page-header">
            <h4><i class="fas fa-graduation-cap text-icon me-2"></i>Pengaturan SPMB Online</h4>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-auto">
            <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-auto">
            <i class="fas fa-times-circle"></i> <?php echo e($error); ?>
        </div>
        <?php endif; ?>

    <!-- Form Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-sliders-h"></i> Pengaturan Umum</span>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation">
                <input type="hidden" name="update_settings" value="1">
                
                <!-- Status SPMB -->
                <div class="form-group mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="spmb_aktif" name="spmb_aktif" 
                            <?php echo ($settings['spmb_aktif'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="spmb_aktif">
                            <strong>Aktifkan SPMB Online</strong>
                            <small class="d-block text-muted mt-2">Centang untuk membuka pendaftaran SPMB ke publik</small>
                        </label>
                    </div>
                </div>

                <hr>

                <!-- Tanggal Buka -->
                <div class="form-group mb-3">
                    <label for="spmb_tanggal_buka" class="form-label"><strong>Tanggal Buka SPMB</strong></label>
                    <input type="date" class="form-control" id="spmb_tanggal_buka" name="spmb_tanggal_buka" 
                        value="<?php echo e($settings['spmb_tanggal_buka'] ?? ''); ?>">
                </div>

                <!-- Tanggal Tutup -->
                <div class="form-group mb-3">
                    <label for="spmb_tanggal_tutup" class="form-label"><strong>Tanggal Tutup SPMB</strong></label>
                    <input type="date" class="form-control" id="spmb_tanggal_tutup" name="spmb_tanggal_tutup" 
                        value="<?php echo e($settings['spmb_tanggal_tutup'] ?? ''); ?>">
                </div>

                <!-- Link Form -->
                <div class="form-group mb-4">
                    <label for="spmb_link_daftar" class="form-label"><strong>Link Form Pendaftaran (Optional)</strong></label>
                    <input type="url" class="form-control" id="spmb_link_daftar" name="spmb_link_daftar" 
                        placeholder="https://..." value="<?php echo e($settings['spmb_link_daftar'] ?? ''); ?>">
                </div>

                <!-- Pengumuman -->
                <div class="form-group mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="spmb_pengumuman_aktif" name="spmb_pengumuman_aktif" 
                            <?php echo ($settings['spmb_pengumuman_aktif'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="spmb_pengumuman_aktif">
                            <strong>Tampilkan Pengumuman Hasil Seleksi</strong>
                            <small class="d-block text-muted mt-2">Centang untuk menampilkan hasil pengumuman SPMB ke halaman publik</small>
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Simpan Pengaturan
                    </button>
                    <a href="javascript:location.reload()" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Tambah Jalur -->
    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-road"></i> Tambah Jalur Pendaftaran</span>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <label for="nama_jalur" class="form-label">Nama Jalur <span style="color: #E11D48;">*</span></label>
                        <input type="text" class="form-control" id="nama_jalur" name="nama_jalur" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="kuota" class="form-label">Kuota <span style="color: #E11D48;">*</span></label>
                        <input type="number" class="form-control" id="kuota" name="kuota" min="1" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <input type="text" class="form-control" id="keterangan" name="keterangan">
                    </div>
                </div>
                <button type="submit" name="tambah_jalur" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i> Tambah Jalur
                </button>
            </form>
        </div>
    </div>

    <!-- List Jalur -->
    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Daftar Jalur Pendaftaran</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Jalur</th>
                            <th>Kuota</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($query_jalur && mysqli_num_rows($query_jalur) > 0):
                            $no = 1;
                            while ($jalur = mysqli_fetch_assoc($query_jalur)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo e($jalur['nama_jalur']); ?></td>
                            <td><span class="badge bg-primary"><?php echo $jalur['kuota']; ?> orang</span></td>
                            <td><?php echo e($jalur['keterangan']); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $jalur['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?hapus_jalur=<?php echo $jalur['id']; ?>" class="btn btn-danger btn-sm" onclick="return siHapus('?hapus_jalur=<?php echo $jalur['id']; ?>', '<?php echo addslashes(e($jalur['nama_jalur'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $jalur['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="edit_jalur" value="1">
                                        <input type="hidden" name="id" value="<?php echo $jalur['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Jalur</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group mb-3">
                                                <label for="edit_nama_jalur<?php echo $jalur['id']; ?>" class="form-label">Nama Jalur</label>
                                                <input type="text" class="form-control" id="edit_nama_jalur<?php echo $jalur['id']; ?>" name="nama_jalur" 
                                                    value="<?php echo e($jalur['nama_jalur']); ?>" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="edit_kuota<?php echo $jalur['id']; ?>" class="form-label">Kuota</label>
                                                <input type="number" class="form-control" id="edit_kuota<?php echo $jalur['id']; ?>" name="kuota" 
                                                    value="<?php echo $jalur['kuota']; ?>" min="1" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="edit_keterangan<?php echo $jalur['id']; ?>" class="form-label">Keterangan</label>
                                                <input type="text" class="form-control" id="edit_keterangan<?php echo $jalur['id']; ?>" name="keterangan" 
                                                    value="<?php echo e($jalur['keterangan']); ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state py-5">
                                    <i class="fas fa-road fa-3x text-muted"></i>
                                    <p class="mt-3 mb-0">Belum ada jalur pendaftaran.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Form Buka Gelombang -->
    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-calendar-alt"></i> Buka Gelombang Pendaftaran</span>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation">
                <div class="row g-3">
                    <div class="col-md-4 mb-3">
                        <label for="nama_gelombang" class="form-label">Nama Gelombang <span style="color: #E11D48;">*</span></label>
                        <input type="text" class="form-control" id="nama_gelombang" name="nama_gelombang" placeholder="Gelombang 1, Gelombang 2, dst" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai <span style="color: #E11D48;">*</span></label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai <span style="color: #E11D48;">*</span></label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label for="status_gelombang" class="form-label">Status <span style="color: #E11D48;">*</span></label>
                        <select class="form-select" id="status_gelombang" name="status_gelombang" required>
                            <option value="aktif">Aktif (bisa menerima pendaftar)</option>
                            <option value="nonaktif">Nonaktif (belum dibuka)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <button type="submit" name="tambah_gelombang" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i> Buka Gelombang Baru
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- List Gelombang -->
    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-list-alt"></i> Daftar Gelombang Pendaftaran</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Gelombang</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($query_gelombang && mysqli_num_rows($query_gelombang) > 0):
                            $no = 1;
                            while ($gel = mysqli_fetch_assoc($query_gelombang)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo e($gel['nama_gelombang']); ?></strong></td>
                            <td><?php echo date('d-m-Y', strtotime($gel['tanggal_mulai'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($gel['tanggal_selesai'])); ?></td>
                            <td>
                                <?php 
                                $status_color = $gel['status'] == 'aktif' ? 'success' : 'secondary';
                                echo '<span class="badge bg-' . $status_color . '">';
                                echo $gel['status'] == 'aktif' ? 'AKTIF' : 'Nonaktif';
                                echo '</span>';
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editGelombangModal<?php echo $gel['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?hapus_gelombang=<?php echo $gel['id']; ?>" class="btn btn-danger btn-sm" onclick="return siHapus('?hapus_gelombang=<?php echo $gel['id']; ?>', '<?php echo addslashes(e($gel['nama_gelombang'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Edit Gelombang Modal -->
                        <div class="modal fade" id="editGelombangModal<?php echo $gel['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="edit_gelombang" value="1">
                                        <input type="hidden" name="id" value="<?php echo $gel['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Gelombang</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group mb-3">
                                                <label class="form-label">Nama Gelombang</label>
                                                <input type="text" class="form-control" name="nama_gelombang" 
                                                    value="<?php echo e($gel['nama_gelombang']); ?>" required>
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Tanggal Mulai</label>
                                                    <input type="date" class="form-control" name="tanggal_mulai" 
                                                        value="<?php echo e($gel['tanggal_mulai']); ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Tanggal Selesai</label>
                                                    <input type="date" class="form-control" name="tanggal_selesai" 
                                                        value="<?php echo e($gel['tanggal_selesai']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status_gelombang">
                                                    <option value="aktif" <?php echo $gel['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="nonaktif" <?php echo $gel['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state py-5">
                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                    <p class="mt-3 mb-0">Belum ada gelombang pendaftaran yang dibuka.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Data Pendaftar Section -->
    <div class="card mt-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i> Data Pendaftar SPMB</span>
                <a href="pendaftar/index.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-right me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Search & Filter -->
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Cari nama/no pendaftaran/email..." 
                            value="<?php echo e($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="filter_status">
                            <option value="">Semua Status</option>
                            <option value="pendaftaran" <?php echo $filter_status === 'pendaftaran' ? 'selected' : ''; ?>>Pendaftaran</option>
                            <option value="menunggu_dokumen" <?php echo $filter_status === 'menunggu_dokumen' ? 'selected' : ''; ?>>Menunggu Dokumen</option>
                            <option value="menunggu_verifikasi" <?php echo $filter_status === 'menunggu_verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                            <option value="diverifikasi" <?php echo $filter_status === 'diverifikasi' ? 'selected' : ''; ?>>Diverifikasi</option>
                            <option value="diterima" <?php echo $filter_status === 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                            <option value="ditolak" <?php echo $filter_status === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                    </div>
                </div>
            </form>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white text-center p-3">
                        <h5>
                            <?php 
                            $total_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM spmb_pendaftar");
                            $total = mysqli_fetch_assoc($total_query)['total'];
                            echo $total;
                            ?>
                        </h5>
                        <small>Total Pendaftar</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white text-center p-3">
                        <h5>
                            <?php 
                            $pending_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM spmb_pendaftar WHERE status = 'menunggu_verifikasi'");
                            $pending = mysqli_fetch_assoc($pending_query)['total'];
                            echo $pending;
                            ?>
                        </h5>
                        <small>Menunggu Verifikasi</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white text-center p-3">
                        <h5>
                            <?php 
                            $verified_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM spmb_pendaftar WHERE status = 'diverifikasi'");
                            $verified = mysqli_fetch_assoc($verified_query)['total'];
                            echo $verified;
                            ?>
                        </h5>
                        <small>Diverifikasi</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white text-center p-3">
                        <h5>
                            <?php 
                            $accepted_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM spmb_pendaftar WHERE status = 'diterima'");
                            $accepted = mysqli_fetch_assoc($accepted_query)['total'];
                            echo $accepted;
                            ?>
                        </h5>
                        <small>Diterima</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white text-center p-3">
                        <h5>
                            <?php 
                            $rejected_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM spmb_pendaftar WHERE status = 'ditolak'");
                            $rejected = mysqli_fetch_assoc($rejected_query)['total'];
                            echo $rejected;
                            ?>
                        </h5>
                        <small>Ditolak</small>
                    </div>
                </div>
            </div>

            <!-- Table Pendaftar -->
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Jalur</th>
                            <th>Dokumen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($query_pendaftar && mysqli_num_rows($query_pendaftar) > 0):
                            $no = 1;
                            while ($pendaftar = mysqli_fetch_assoc($query_pendaftar)):
                                // Ambil status dokumen
                                $doc_query = mysqli_query($koneksi, "
                                    SELECT jenis_dokumen, status_verifikasi 
                                    FROM spmb_dokumen 
                                    WHERE pendaftar_id = {$pendaftar['id']}
                                ");
                                $total_docs = 0;
                                $valid_docs = 0;
                                while ($doc = mysqli_fetch_assoc($doc_query)) {
                                    $total_docs++;
                                    if ($doc['status_verifikasi'] === 'valid') {
                                        $valid_docs++;
                                    }
                                }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo e($pendaftar['no_pendaftaran']); ?></strong></td>
                            <td><?php echo e($pendaftar['nama_lengkap']); ?></td>
                            <td><?php echo e($pendaftar['email']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo e($pendaftar['nama_jalur'] ?? '-'); ?></span></td>
                            <td>
                                <?php if ($total_docs > 0): ?>
                                    <span class="badge bg-info"><?php echo "$valid_docs/$total_docs"; ?> <i class="fas fa-check ms-1"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Upload</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status = $pendaftar['status'];
                                $status_color = [
                                    'pendaftaran' => 'primary',
                                    'menunggu_dokumen' => 'warning',
                                    'menunggu_verifikasi' => 'warning',
                                    'diverifikasi' => 'info',
                                    'lolos_seleksi' => 'info',
                                    'diterima' => 'success',
                                    'ditolak' => 'danger'
                                ];
                                $status_text = [
                                    'pendaftaran' => 'Pendaftaran',
                                    'menunggu_dokumen' => 'Menunggu Dokumen',
                                    'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                    'diverifikasi' => 'Diverifikasi',
                                    'lolos_seleksi' => 'Lolos Seleksi',
                                    'diterima' => 'Diterima',
                                    'ditolak' => 'Ditolak'
                                ];
                                $color = $status_color[$status] ?? 'secondary';
                                $text = $status_text[$status] ?? $status;
                                ?>
                                <span class="badge bg-<?php echo $color; ?>"><?php echo $text; ?></span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $pendaftar['id']; ?>" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="pendaftar/index.php?id=<?php echo $pendaftar['id']; ?>" class="btn btn-warning btn-sm" title="Verifikasi Dokumen">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Detail Modal -->
                        <div class="modal fade" id="detailModal<?php echo $pendaftar['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detail Pendaftar - <?php echo e($pendaftar['nama_lengkap']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>No. Pendaftaran</strong></label>
                                                <p><?php echo e($pendaftar['no_pendaftaran']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Nama Lengkap</strong></label>
                                                <p><?php echo e($pendaftar['nama_lengkap']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Email</strong></label>
                                                <p><?php echo e($pendaftar['email']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>No. HP Orang Tua</strong></label>
                                                <p><?php echo e($pendaftar['no_hp_ortu'] ?? '-'); ?></p>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Tanggal Lahir</strong></label>
                                                <p><?php echo date('d-m-Y', strtotime($pendaftar['tanggal_lahir'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Jalur Pendaftaran</strong></label>
                                                <p><?php echo e($pendaftar['nama_jalur'] ?? '-'); ?></p>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><strong>Alamat</strong></label>
                                            <p><?php echo e($pendaftar['alamat'] ?? '-'); ?></p>
                                        </div>
                                        <hr>
                                        <h6><strong>Status Dokumen:</strong></h6>
                                        <?php 
                                        $doc_query2 = mysqli_query($koneksi, "
                                            SELECT jenis_dokumen, status_verifikasi, path_file 
                                            FROM spmb_dokumen 
                                            WHERE pendaftar_id = {$pendaftar['id']}
                                            ORDER BY jenis_dokumen ASC
                                        ");
                                        if ($doc_query2 && mysqli_num_rows($doc_query2) > 0):
                                            while ($doc = mysqli_fetch_assoc($doc_query2)):
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <strong><?php echo ucfirst($doc['jenis_dokumen']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo e($doc['path_file']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php echo $doc['status_verifikasi'] === 'valid' ? 'success' : ($doc['status_verifikasi'] === 'tidak_valid' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $doc['status_verifikasi'])); ?>
                                            </span>
                                        </div>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <p class="text-muted">Belum ada dokumen yang diunggah.</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <a href="pendaftar/index.php?id=<?php echo $pendaftar['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> Verifikasi Dokumen
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-5">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                    <p class="mt-3 mb-0">Belum ada data pendaftar.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <!-- Info Card - Paling Bawah -->
    <div class="card mt-5 border-0 shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%); border: none; padding: 1.5rem;">
            <div class="d-flex align-items-center gap-3">
                <div style="background: rgba(255,255,255,0.2); padding: 12px 15px; border-radius: 8px;">
                    <i class="fas fa-info-circle fa-lg text-white"></i>
                </div>
                <div>
                    <h5 class="text-white mb-0" style="font-weight: 600; font-size: 1.1rem;">Informasi SPMB Online</h5>
                    <small class="text-white" style="opacity: 0.9;">Panduan dan referensi sistem pendaftaran</small>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Kolom Kiri -->
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 style="color: #163A63; font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="fas fa-graduation-cap text-primary me-2"></i>Tentang SPMB Online
                        </h6>
                        <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                            Sistem Penerimaan Murid Baru (SPMB) Online memudahkan calon siswa baru untuk mendaftar secara digital dengan verifikasi dokumen terintegrasi dan tracking status real-time.
                        </p>
                    </div>

                    <!-- Link Publik -->
                    <div class="bg-light p-4 rounded-3" style="border-left: 5px solid #0284C7;">
                        <h6 class="mb-3" style="color: #163A63; font-weight: 600;">
                            <i class="fas fa-link text-primary me-2"></i>Link Publik
                        </h6>
                        <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                            <li class="mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-globe text-primary" style="width: 20px;"></i>
                                    <span><strong>Landing Page:</strong></span>
                                </div>
                                <code class="d-block bg-white px-3 py-2 rounded mt-1 border" style="font-size: 0.85rem; color: #0284C7; border: 1px solid #E5E7EB;">/spmb/index.php</code>
                            </li>
                            <li class="mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-edit text-primary" style="width: 20px;"></i>
                                    <span><strong>Form Daftar:</strong></span>
                                </div>
                                <code class="d-block bg-white px-3 py-2 rounded mt-1 border" style="font-size: 0.85rem; color: #0284C7; border: 1px solid #E5E7EB;">/spmb/daftar.php</code>
                            </li>
                            <li class="mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-search text-primary" style="width: 20px;"></i>
                                    <span><strong>Cek Status:</strong></span>
                                </div>
                                <code class="d-block bg-white px-3 py-2 rounded mt-1 border" style="font-size: 0.85rem; color: #0284C7; border: 1px solid #E5E7EB;">/spmb/cek-status.php</code>
                            </li>
                            <li>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-bullhorn text-primary" style="width: 20px;"></i>
                                    <span><strong>Hasil Pengumuman:</strong></span>
                                </div>
                                <code class="d-block bg-white px-3 py-2 rounded mt-1 border" style="font-size: 0.85rem; color: #0284C7; border: 1px solid #E5E7EB;">/spmb/pengumuman.php</code>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="col-md-6">
                    <!-- Dokumen Wajib -->
                    <div class="bg-light p-4 rounded-3 mb-3" style="border-left: 5px solid #10B981;">
                        <h6 class="mb-3" style="color: #163A63; font-weight: 600;">
                            <i class="fas fa-file-upload text-success me-2"></i>Dokumen Wajib Upload
                        </h6>
                        <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Kartu Keluarga (KK)</strong></li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Akta Kelahiran</strong></li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Ijazah / SKL</strong></li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Pas Foto (4x6)</strong></li>
                            <li><i class="fas fa-circle text-muted me-2" style="font-size: 0.6rem;"></i><em class="text-muted">Rapor (Opsional)</em></li>
                        </ul>
                    </div>

                    <!-- Status Pendaftar -->
                    <div class="bg-light p-4 rounded-3" style="border-left: 5px solid #F59E0B;">
                        <h6 class="mb-3" style="color: #163A63; font-weight: 600;">
                            <i class="fas fa-check-double text-warning me-2"></i>Status Pendaftar
                        </h6>
                        <ul class="list-unstyled mb-0" style="font-size: 0.85rem;">
                            <li class="mb-2"><span class="badge bg-primary px-2 py-1">Pendaftaran</span> Form selesai</li>
                            <li class="mb-2"><span class="badge bg-warning text-dark px-2 py-1">Menunggu Dokumen</span> Belum upload</li>
                            <li class="mb-2"><span class="badge bg-warning text-dark px-2 py-1">Menunggu Verifikasi</span> Belum periksa</li>
                            <li class="mb-2"><span class="badge bg-info px-2 py-1">Verifikasi Selesai</span> Siap seleksi</li>
                            <li class="mb-2"><span class="badge bg-success px-2 py-1"><i class="fas fa-check me-1"></i>Diterima</span> Lolos seleksi</li>
                            <li><span class="badge bg-danger px-2 py-1"><i class="fas fa-xmark me-1"></i>Ditolak</span> Tidak diterima</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-top" style="border-top: 1px solid #E5E7EB; padding: 1rem 2rem;">
            <div class="d-flex align-items-start gap-3">
                <div style="background: #FEF3C7; padding: 10px 12px; border-radius: 8px; min-width: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-lightbulb text-warning"></i>
                </div>
                <div>
                    <strong style="color: #92400E; font-size: 0.95rem;">Tips Penting:</strong>
                    <p class="mb-0 text-muted" style="font-size: 0.9rem; margin-top: 0.25rem;">
                        Pantau notifikasi admin untuk setiap pendaftar baru yang mengunggah dokumen. Verifikasi dokumen dengan teliti sebelum mengubah status pendaftar agar proses seleksi berjalan lancar.
                    </p>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
