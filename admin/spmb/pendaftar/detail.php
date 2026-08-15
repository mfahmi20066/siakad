<?php
include '../../../config/koneksi.php';
include '../../../config/session.php';
cekAdmin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// query pendaftar + detail
$query = "SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          WHERE sp.id=$id";
$data = mysqli_query($koneksi, $query);

if (!$data || mysqli_num_rows($data) == 0) {
    header("Location: index.php");
    exit();
}

$pendaftar = mysqli_fetch_assoc($data);

// query dokumen
$query_dokumen = mysqli_query($koneksi, "SELECT * FROM spmb_dokumen WHERE pendaftar_id=$id ORDER BY jenis_dokumen ASC");

// konfigurasi status
$status_config = [
    'menunggu_dokumen' => ['label' => 'Menunggu Dokumen', 'color' => 'warning'],
    'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'color' => 'warning'],
    'diverifikasi' => ['label' => 'Diverifikasi', 'color' => 'info'],
    'lolos_seleksi' => ['label' => 'Lolos Seleksi', 'color' => 'info'],
    'diterima' => ['label' => 'Diterima', 'color' => 'success'],
    'ditolak' => ['label' => 'Ditolak', 'color' => 'danger'],
];
?>
<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/sidebar_admin.php'; ?>
<?php include '../../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-user-graduate text-icon me-2"></i>Detail Pendaftar</h4>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-info-circle"></i> Biodata Pendaftar</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">Nomor Pendaftaran</label>
                        <h5 class="mb-0"><?php echo e($pendaftar['no_pendaftaran']); ?></h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">Nama Lengkap</label>
                        <h5 class="mb-0"><?php echo e($pendaftar['nama_lengkap']); ?></h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">Email</label>
                        <h5 class="mb-0"><?php echo e($pendaftar['email']); ?></h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">NIK</label>
                        <h5 class="mb-0"><?php echo e($pendaftar['nik']); ?></h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">Jalur</label>
                        <h5 class="mb-0">
                            <span class="badge bg-primary"><?php echo e($pendaftar['nama_jalur']); ?></span>
                        </h5>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 13px;">Gelombang</label>
                        <h5 class="mb-0"><?php echo e($pendaftar['nama_gelombang']); ?></h5>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Tempat / Tanggal Lahir</label></div>
                    <p class="mb-0"><?php echo e($pendaftar['tempat_lahir'] ?? '-'); ?>, <?php echo date('d-m-Y', strtotime($pendaftar['tanggal_lahir'])); ?></p>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Jenis Kelamin</label></div>
                    <p class="mb-0"><?php echo ($pendaftar['jenis_kelamin'] ?? '') == 'L' ? 'Laki-laki' : 'Perempuan'; ?></p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Asal Sekolah</label></div>
                    <p class="mb-0"><?php echo e($pendaftar['asal_sekolah'] ?? '-'); ?></p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Alamat</label></div>
                    <p class="mb-0"><?php echo e($pendaftar['alamat'] ?? '-'); ?></p>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Nama Orang Tua</label></div>
                    <p class="mb-0"><?php echo e($pendaftar['nama_ortu'] ?? '-'); ?></p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">No. HP Orang Tua</label></div>
                    <p class="mb-0"><?php echo e($pendaftar['no_hp_ortu'] ?? '-'); ?></p>
                </div>
                <div class="col-md-6">
                    <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Tgl. Daftar</label></div>
                    <p class="mb-0"><?php echo date('d-m-Y H:i', strtotime($pendaftar['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-upload"></i> Dokumen Upload</span>
                    <span class="badge bg-info ms-2">
                        <?php echo $query_dokumen ? mysqli_num_rows($query_dokumen) : 0; ?> dokumen
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($query_dokumen && mysqli_num_rows($query_dokumen) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>File</th>
                                <th>Status Verifikasi</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($dok = mysqli_fetch_assoc($query_dokumen)): ?>
                            <?php
                                // bikin url download aman dengan parameter verifikasi
                                $file_path = e($dok['path_file'] ?? '');
                                $jenis = $dok['jenis_dokumen'] ?? '';
                                $download_url = '../../../spmb/download-dokumen.php?id=' . $pendaftar['id'] . '&jenis=' . $jenis . '&no=' . urlencode($pendaftar['no_pendaftaran']) . '&tgl=' . urlencode($pendaftar['tanggal_lahir']);
                                $view_url = '/siakad/uploads/spmb/' . $pendaftar['id'] . '/' . $file_path;
                            ?>
                            <tr>
                                <td><strong><?php echo ucfirst(str_replace('_', ' ', $dok['jenis_dokumen'] ?? '')); ?></strong></td>
                                <td>
                                    <i class="fas fa-file"></i>
                                    <?php echo $file_path; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_verif = [
                                        'menunggu' => ['label' => 'Menunggu', 'color' => 'warning'],
                                        'valid' => ['label' => 'Valid', 'color' => 'success'],
                                        'tidak_valid' => ['label' => 'Tidak Valid', 'color' => 'danger'],
                                    ];
                                    $config = $status_verif[$dok['status_verifikasi']] ?? ['label' => $dok['status_verifikasi'], 'color' => 'secondary'];
                                    echo '<span class="badge bg-' . $config['color'] . '">' . $config['label'] . '</span>';
                                    ?>
                                </td>
                                <td><?php echo e($dok['catatan'] ?? ''); ?></td>
                                <td class="text-center">
                                    <a href="<?php echo $view_url; ?>" target="_blank"
                                    class="btn btn-sm btn-outline-primary" title="Lihat Dokumen">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo $download_url; ?>" 
                                    class="btn btn-sm btn-outline-secondary" title="Unduh Dokumen">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state py-4">
                    <i class="fas fa-file fa-2x text-muted mb-2"></i>
                    <p>Belum ada dokumen yang diupload.</p>
                </div>
                <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <span><i class="fas fa-clipboard-list"></i> Status Pendaftaran</span>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <?php
                $statuses = ['menunggu_dokumen', 'menunggu_verifikasi', 'diverifikasi', 'lolos_seleksi', 'diterima', 'ditolak'];
                $current_status = $pendaftar['status'];
                $status_labels = [
                    'menunggu_dokumen' => 'Menunggu Dokumen',
                    'menunggu_verifikasi' => 'Menunggu Verifikasi',
                    'diverifikasi' => 'Diverifikasi',
                    'lolos_seleksi' => 'Lolos Seleksi',
                    'diterima' => 'Diterima',
                    'ditolak' => 'Ditolak',
                ];
                $status_colors = [
                    'menunggu_dokumen' => 'secondary',
                    'menunggu_verifikasi' => 'warning',
                    'diverifikasi' => 'info',
                    'lolos_seleksi' => 'info',
                    'diterima' => 'success',
                    'ditolak' => 'danger',
                ];
                
                $current_idx = array_search($current_status, $statuses);
                
                foreach ($statuses as $idx => $status):
                    $is_active = ($idx == $current_idx);
                    $is_completed = ($idx < $current_idx);
                ?>
                <div class="text-center" style="flex: 1;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto 8px;
                        <?php if ($is_active): ?>background: #3B82F6;<?php elseif ($is_completed): ?>background: #10B981;<?php else: ?>background: #94A3B8;<?php endif; ?>">
                        <?php echo $idx + 1; ?>
                    </div>
                    <div style="font-size: 11px; font-weight: 600;">
                        <?php echo $status_labels[$status]; ?>
                    </div>
                </div>
                <?php if ($idx < count($statuses) - 1): ?>
                <div style="flex: 1; height: 2px; background: #E2E8F0; position: relative; top: -19px;">
                    <?php if ($is_completed): ?>
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #10B981;"></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4 p-3" style="background: #F5F7FB; border-radius: 8px;">
                <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Status Saat Ini</label></div>
                <span class="badge bg-<?php echo $status_colors[$current_status]; ?>" style="font-size: 16px; padding: 10px 16px;">
                    <?php echo $status_labels[$current_status]; ?>
                </span>
            </div>
            
            <?php if ($pendaftar['catatan_verifikasi']): ?>
            <div class="mt-3 p-3" style="background: #FEF3C7; border-left: 4px solid #F59E0B; border-radius: 8px;">
                <div class="mb-2"><label class="form-label text-muted" style="font-size: 13px;">Catatan Verifikasi</label></div>
                <p class="mb-0" style="color: #92400E; font-size: 14px;"><?php echo e($pendaftar['catatan_verifikasi']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
        <a href="verifikasi.php?id=<?php echo $id; ?>" class="btn btn-warning">
            <i class="fas fa-check-circle me-2"></i> Verifikasi Dokumen
        </a>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
