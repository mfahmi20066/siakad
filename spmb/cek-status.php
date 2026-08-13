<?php
include '../config/koneksi.php';

$title = "Cek Status Pendaftaran";
$pendaftar = null;
$error = '';

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $no_pendaftaran = mysqli_real_escape_string($koneksi, $_POST['no_pendaftaran'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir'] ?? '');
    
    if (empty($no_pendaftaran) || empty($tanggal_lahir)) {
        $error = "Harap isi semua field!";
    } else {
        // Query pendaftar
        $query = mysqli_query($koneksi, "
            SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
            FROM spmb_pendaftar sp
            LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
            LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
            WHERE sp.no_pendaftaran='$no_pendaftaran' AND sp.tanggal_lahir='$tanggal_lahir'
        ");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $pendaftar = mysqli_fetch_assoc($query);
        } else {
            $error = "Data tidak ditemukan. Periksa kembali nomor pendaftaran dan tanggal lahir Anda.";
        }
    }
}

// Status badges
$status_config = [
    'menunggu_dokumen' => ['label' => 'Menunggu Dokumen', 'color' => '#F59E0B', 'icon' => 'fa-hourglass-start'],
    'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'color' => '#F59E0B', 'icon' => 'fa-hourglass-half'],
    'diverifikasi' => ['label' => 'Diverifikasi', 'color' => '#3B82F6', 'icon' => 'fa-check-circle'],
    'lolos_seleksi' => ['label' => 'Lolos Seleksi', 'color' => '#3B82F6', 'icon' => 'fa-star'],
    'diterima' => ['label' => 'Diterima', 'color' => '#10B981', 'icon' => 'fa-check-double'],
    'ditolak' => ['label' => 'Ditolak', 'color' => '#E11D48', 'icon' => 'fa-times-circle'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> — SMA Negeri 4 Palopo</title>
    <link rel="icon" type="image/png" href="/siakad/assets/img/logo-sekolah.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/siakad/assets/css/landing.css?v=1.0">
    
    <style>
        body { font-family: 'Roboto', sans-serif; background: #F5F7FB; }
        .form-section { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 18px; box-shadow: 0 8px 24px rgba(13, 37, 64, 0.08); margin-top: 40px; }
        .form-title { color: #163A63; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .form-subtitle { color: #4A5568; margin-bottom: 32px; }
        .form-group label { color: #163A63; font-weight: 600; margin-bottom: 8px; }
        .form-control:focus { border-color: #F09000; box-shadow: 0 0 0 0.2rem rgba(240, 144, 0, 0.25); }
        .btn-cek { background: #163A63; color: white; padding: 12px 32px; border: none; border-radius: 12px; font-weight: 600; width: 100%; transition: all 0.3s ease; }
        .btn-cek:hover { background: #2C5A8F; transform: translateY(-2px); }
        .status-timeline { margin-top: 32px; }
        .status-step { display: flex; gap: 16px; margin-bottom: 24px; position: relative; }
        .status-step::after { content: ''; position: absolute; left: 20px; top: 60px; width: 2px; height: 40px; background: #E2E8F0; }
        .status-step:last-child::after { display: none; }
        .status-indicator { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; flex-shrink: 0; position: relative; z-index: 1; }
        .status-step.active .status-indicator { box-shadow: 0 0 0 8px rgba(13, 37, 64, 0.1); }
        .status-content h4 { color: #163A63; font-weight: 700; margin-bottom: 4px; font-size: 14px; }
        .status-content p { color: #4A5568; font-size: 13px; margin: 0; }
        .result-card { background: white; padding: 24px; border-radius: 14px; border-left: 4px solid #F09000; margin-top: 24px; }
        .result-card h3 { color: #163A63; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
        .result-row { display: grid; grid-template-columns: 150px 1fr; gap: 16px; margin-bottom: 12px; }
        .result-label { color: #4A5568; font-weight: 600; font-size: 13px; }
        .result-value { color: #163A63; font-weight: 500; font-size: 13px; }
        .action-buttons { margin-top: 24px; display: flex; gap: 12px; }
        .action-buttons a { flex: 1; padding: 10px; text-align: center; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; }
        .btn-upload { background: #F09000; color: white; }
        .btn-upload:hover { background: #FFB74D; }
        .btn-cetak { background: #163A63; color: white; }
        .btn-cetak:hover { background: #2C5A8F; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="landing-navbar">
    <div class="landing-navbar-container">
        <div class="landing-navbar-brand">
            <img src="/siakad/assets/img/logo-sekolah.png" alt="Logo" loading="lazy">
            <h6>SPMB Online</h6>
        </div>
        <a href="/siakad/spmb/index.php" style="color: rgba(255,255,255,0.85); font-size: 14px; text-decoration: none;">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>
</nav>

<!-- FORM SECTION -->
<div class="form-section">
    <h1 class="form-title">
        <i class="fas fa-search me-2"></i> Cek Status Pendaftaran
    </h1>
    <p class="form-subtitle">Masukkan nomor pendaftaran dan tanggal lahir Anda</p>
    
    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group mb-3">
            <label for="no_pendaftaran">Nomor Pendaftaran <span style="color: #E11D48;">*</span></label>
            <input type="text" class="form-control" id="no_pendaftaran" name="no_pendaftaran" 
                placeholder="Contoh: SPMB-2026-00001" value="<?php echo e($_POST['no_pendaftaran'] ?? ''); ?>" required>
            <small class="text-muted">Nomor yang dikirim melalui email pendaftaran</small>
        </div>
        
        <div class="form-group mb-4">
            <label for="tanggal_lahir">Tanggal Lahir <span style="color: #E11D48;">*</span></label>
            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" 
                value="<?php echo e($_POST['tanggal_lahir'] ?? ''); ?>" required>
        </div>
        
        <button type="submit" class="btn-cek">
            <i class="fas fa-search me-2"></i> Cek Status
        </button>
    </form>
</div>

<!-- HASIL PENCARIAN -->
<?php if ($pendaftar): ?>
<div class="form-section">
    <!-- BIODATA -->
    <div class="result-card">
        <h3><i class="fas fa-user me-2"></i> Biodata Pendaftar</h3>
        
        <div class="result-row">
            <div class="result-label">Nama</div>
            <div class="result-value"><?php echo e($pendaftar['nama_lengkap']); ?></div>
        </div>
        
        <div class="result-row">
            <div class="result-label">No. Pendaftaran</div>
            <div class="result-value"><strong><?php echo e($pendaftar['no_pendaftaran']); ?></strong></div>
        </div>
        
        <div class="result-row">
            <div class="result-label">Email</div>
            <div class="result-value"><?php echo e($pendaftar['email']); ?></div>
        </div>
        
        <div class="result-row">
            <div class="result-label">Jalur</div>
            <div class="result-value"><?php echo e($pendaftar['nama_jalur'] ?? 'N/A'); ?></div>
        </div>
        
        <div class="result-row">
            <div class="result-label">Gelombang</div>
            <div class="result-value"><?php echo e($pendaftar['nama_gelombang'] ?? 'N/A'); ?></div>
        </div>
        
        <div class="result-row">
            <div class="result-label">Tgl. Daftar</div>
            <div class="result-value"><?php echo date('d-m-Y H:i', strtotime($pendaftar['created_at'])); ?></div>
        </div>
    </div>
    
    <!-- STATUS TIMELINE -->
    <div class="result-card">
        <h3><i class="fas fa-list-check me-2"></i> Status Pendaftaran</h3>
        
        <div class="status-timeline">
            <?php 
            // Timeline: untuk yang ditolak, "Diterima" tidak pernah jadi tahap —
            // jalur finalnya adalah Ditolak (setelah Lolos Seleksi)
            if ($pendaftar['status'] == 'ditolak') {
                $statuses = ['menunggu_dokumen', 'menunggu_verifikasi', 'diverifikasi', 'lolos_seleksi', 'ditolak'];
            } else {
                $statuses = ['menunggu_dokumen', 'menunggu_verifikasi', 'diverifikasi', 'lolos_seleksi', 'diterima'];
            }
            $current_status = $pendaftar['status'];
            
            foreach ($statuses as $status):
                $config = $status_config[$status];
                $is_active = ($status == $current_status);
                $is_completed = (array_search($status, $statuses) < array_search($current_status, $statuses));
            ?>
            <div class="status-step <?php echo ($is_active || $is_completed) ? 'active' : ''; ?>">
                <div class="status-indicator" style="background: <?php echo $config['color']; ?>;">
                    <i class="fas <?php echo $config['icon']; ?>"></i>
                </div>
                <div class="status-content">
                    <h4><?php echo $config['label']; ?></h4>
                    <p>
                        <?php if ($is_active): ?>
                            Status terkini Anda
                        <?php elseif ($is_completed): ?>
                            Selesai
                        <?php else: ?>
                            Belum mencapai tahap ini
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($pendaftar['catatan_verifikasi']): ?>
        <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px; border-radius: 8px; margin-top: 20px;">
            <p style="margin: 0; color: #92400E; font-size: 13px;">
                <strong>Catatan:</strong> <?php echo e($pendaftar['catatan_verifikasi']); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ACTION BUTTONS -->
    <div class="result-card">
        <div class="action-buttons">
            <?php if ($current_status == 'menunggu_dokumen'): ?>
            <a href="/siakad/spmb/upload-dokumen.php" class="btn-upload">
                <i class="fas fa-upload me-2"></i> Upload Dokumen
            </a>
            <?php endif; ?>
            
            <?php if (in_array($current_status, ['diverifikasi', 'lolos_seleksi', 'diterima', 'ditolak'])): ?>
            <a href="/siakad/spmb/cetak-bukti.php?id=<?php echo $pendaftar['id']; ?>&no_pendaftaran=<?php echo urlencode($pendaftar['no_pendaftaran']); ?>&tanggal_lahir=<?php echo urlencode($pendaftar['tanggal_lahir']); ?>" class="btn-cetak" target="_blank">
                <i class="fas fa-print me-2"></i> Cetak Bukti
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- PESAN STATUS KHUSUS -->
    <?php if ($current_status == 'diterima'): ?>
    <div style="background: #D4EDDA; border: 1px solid #C3E6CB; color: #155724; padding: 20px; border-radius: 12px; margin-top: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <i class="fas fa-check-circle fa-2x"></i>
            <div>
                <strong>Selamat, Anda diterima!</strong><br>
                Silakan hubungi sekolah untuk proses daftar ulang lebih lanjut.
            </div>
        </div>
    </div>
    <?php elseif ($current_status == 'ditolak'): ?>
    <div style="background: #F8D7DA; border: 1px solid #F5C6CB; color: #721C24; padding: 20px; border-radius: 12px; margin-top: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <i class="fas fa-times-circle fa-2x"></i>
            <div>
                <strong>Maaf, pendaftaran Anda tidak lolos seleksi.</strong><br>
                Terima kasih atas minat Anda. Semoga berhasil di tempat lain.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- FOOTER -->
<footer class="landing-footer" style="margin-top: 60px;">
    <div class="landing-footer-container">
        <div class="landing-footer-bottom" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 32px;">
            <p class="landing-footer-copyright">
                &copy; 2026 SMA Negeri 4 Palopo — SPMB Online
            </p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
