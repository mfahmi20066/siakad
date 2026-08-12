<?php
include '../../../config/koneksi.php';
include '../../../config/session.php';
cekAdmin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Query pendaftar
$query = "SELECT sp.*, sj.dokumen_wajib, sj.nama_jalur 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          WHERE sp.id=$id";
$data = mysqli_query($koneksi, $query);

if (!$data || mysqli_num_rows($data) == 0) {
    header("Location: index.php");
    exit();
}

$pendaftar = mysqli_fetch_assoc($data);

// Ambil status verifikasi dokumen yang sudah ada
$query_dokumen = mysqli_query($koneksi, "SELECT * FROM spmb_dokumen WHERE pendaftar_id=$id");
$uploaded_docs = [];
$status_verifikasi = [];
if ($query_dokumen) {
    while ($row = mysqli_fetch_assoc($query_dokumen)) {
        $uploaded_docs[] = $row['jenis_dokumen'];
        $status_verifikasi[$row['jenis_dokumen']] = $row['status_verifikasi'];
    }
}

// Proses simpan verifikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'verifikasi_dokumen') {
        $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
        $status_update = 'menunggu_verifikasi';
        $all_valid = true;
        $has_invalid = false;
        
        foreach ($uploaded_docs as $doc) {
            $status = $_POST['status_' . $doc] ?? 'menunggu';
            $cat_doc = mysqli_real_escape_string($koneksi, $_POST['catatan_' . $doc] ?? '');
            
            $update = mysqli_query($koneksi, "
                UPDATE spmb_dokumen 
                SET status_verifikasi='$status', catatan='$cat_doc'
                WHERE pendaftar_id=$id AND jenis_dokumen='$doc'
            ");
            
            if ($status === 'tidak_valid') {
                $has_invalid = true;
                $all_valid = false;
            } elseif ($status !== 'menunggu') {
                $status_update = 'diverifikasi';
            }
        }
        
        // Update status pendaftar
        if ($all_valid) {
            mysqli_query($koneksi, "UPDATE spmb_pendaftar SET status='diverifikasi' WHERE id=$id");
            $success = "Semua dokumen terverifikasi dan valid!";
        } elseif ($has_invalid) {
            mysqli_query($koneksi, "UPDATE spmb_pendaftar SET status='menunggu_verifikasi' WHERE id=$id");
            $success = "Verifikasi dokumen selesai. Beberapa dokumen ditolak.";
        } else {
            $success = "Verifikasi dokumen berhasil disimpan!";
        }
    } 
    elseif ($action === 'update_status') {
        $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_baru']);
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
        
        $update = mysqli_query($koneksi, "
            UPDATE spmb_pendaftar 
            SET status='$status_baru', catatan_verifikasi='$keterangan'
            WHERE id=$id
        ");
        
        if ($update) {
            $success = "Status pendaftar berhasil diperbarui menjadi: " . ucfirst(str_replace('_', ' ', $status_baru));
            // Reload data pendaftar
            $query = "SELECT sp.*, sj.dokumen_wajib, sj.nama_jalur 
                      FROM spmb_pendaftar sp
                      LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
                      WHERE sp.id=$id";
            $data = mysqli_query($koneksi, $query);
            $pendaftar = mysqli_fetch_assoc($data);
        } else {
            $error = "Gagal memperbarui status: " . mysqli_error($koneksi);
        }
    }
}

// Proses re-upload
if (isset($_GET['upload'])) {
    header("Location: /siakad/spmb/upload-dokumen.php");
    exit();
}
?>
<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/sidebar_admin.php'; ?>
<?php include '../../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-check-circle text-icon me-2"></i>Verifikasi Dokumen Pendaftar</h4>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 13px;">Nama Pendaftar</label>
                <h5 class="mb-0"><?php echo e($pendaftar['nama_lengkap']); ?></h5>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 13px;">No. Pendaftaran</label>
                <h5 class="mb-0"><?php echo e($pendaftar['no_pendaftaran']); ?></h5>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted" style="font-size: 13px;">Jalur</label>
                <h5 class="mb-0"><span class="badge bg-primary"><?php echo e($pendaftar['nama_jalur'] ?? '-'); ?></span></h5>
            </div>
        </div>
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

    <!-- Info Status Pendaftar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 13px;">Nama Pendaftar</label>
                    <h5 class="mb-0"><?php echo e($pendaftar['nama_lengkap']); ?></h5>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 13px;">No. Pendaftaran</label>
                    <h5 class="mb-0"><?php echo e($pendaftar['no_pendaftaran']); ?></h5>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted" style="font-size: 13px;">Status Saat Ini</label>
                    <h5 class="mb-0">
                        <?php 
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
                        $color = $status_color[$pendaftar['status']] ?? 'secondary';
                        $text = $status_text[$pendaftar['status']] ?? $pendaftar['status'];
                        ?>
                        <span class="badge bg-<?php echo $color; ?>"><?php echo $text; ?></span>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <form method="POST">
        <!-- Verifikasi Dokumen -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <span><i class="fas fa-files"></i> Verifikasi Dokumen</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>File</th>
                            <th>Status Verifikasi</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $query_dok = mysqli_query($koneksi, "SELECT * FROM spmb_dokumen WHERE pendaftar_id=$id ORDER BY jenis_dokumen ASC");
                        if ($query_dok && mysqli_num_rows($query_dok) > 0):
                            while ($dok = mysqli_fetch_assoc($query_dok)):
                                $jenis = $dok['jenis_dokumen'];
                                $status_badge_color = [
                                    'valid' => 'success',
                                    'tidak_valid' => 'danger',
                                    'menunggu' => 'warning'
                                ];
                                $badge_color = $status_badge_color[$dok['status_verifikasi']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><strong><?php echo ucfirst(str_replace('_', ' ', $jenis)); ?></strong></td>
                            <td>
                                <i class="fas fa-file"></i> 
                                <a href="/siakad/uploads/spmb/<?php echo $id; ?>/<?php echo e($dok['path_file']); ?>" target="_blank" class="text-decoration-none">
                                    <?php echo e($dok['path_file']); ?>
                                </a>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="status_<?php echo $jenis; ?>">
                                    <option value="menunggu" <?php echo ($dok['status_verifikasi'] == 'menunggu') ? 'selected' : ''; ?>>â³ Menunggu</option>
                                    <option value="valid" <?php echo ($dok['status_verifikasi'] == 'valid') ? 'selected' : ''; ?>>âœ“ Valid</option>
                                    <option value="tidak_valid" <?php echo ($dok['status_verifikasi'] == 'tidak_valid') ? 'selected' : ''; ?>>âœ— Tidak Valid</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="catatan_<?php echo $jenis; ?>" 
                                    placeholder="Catatan..." value="<?php echo e($dok['catatan'] ?? ''); ?>">
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                <i class="fas fa-inbox"></i> Belum ada dokumen yang diunggah
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Catatan Umum -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <span><i class="fas fa-sticky-note"></i> Catatan Umum</span>
            </div>
            <div class="card-body">
                <textarea class="form-control" name="catatan" rows="3" 
                    placeholder="Catatan tambahan untuk pendaftar..."></textarea>
            </div>
        </div>

        <!-- Buttons -->
        <div class="d-flex gap-2 mb-4">
            <input type="hidden" name="action" value="verifikasi_dokumen">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Simpan Verifikasi Dokumen
            </button>
            <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Detail
            </a>
        </div>
    </form>

    <!-- Update Status Pendaftar -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <span><i class="fas fa-user-check"></i> Update Status Pendaftar</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Ubah Status</strong></label>
                        <select class="form-select" name="status_baru" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="pendaftaran">Pendaftaran</option>
                            <option value="menunggu_dokumen">Menunggu Dokumen</option>
                            <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                            <option value="diverifikasi">Diverifikasi</option>
                            <option value="lolos_seleksi">Lolos Seleksi</option>
                            <option value="diterima" style="color: green; font-weight: bold;">âœ“ Diterima</option>
                            <option value="ditolak" style="color: red; font-weight: bold;">âœ— Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Keterangan (Optional)</strong></label>
                        <input type="text" class="form-control" name="keterangan" 
                            placeholder="Alasan perubahan status...">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <input type="hidden" name="action" value="update_status">
                    <button type="submit" class="btn btn-success" onclick="return siConfirmForm(event, {icon:'question', title:'Apakah Anda yakin ingin mengubah status?', text:'Status pendaftar akan diperbarui sesuai data yang Anda isi.', confirmText:'Ya, Ubah'})">
                        <i class="fas fa-check-circle me-2"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
