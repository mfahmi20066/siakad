<?php
include '../config/koneksi.php';

// Cek pengumuman aktif
$query_setting = mysqli_query($koneksi, "SELECT spmb_pengumuman_aktif FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);
$pengumuman_aktif = $setting['spmb_pengumuman_aktif'] ?? 0;

// Jika pengumuman tidak aktif
if ($pengumuman_aktif != 1) {
    $pengumuman_aktif = false;
}

$search_no = '';
$search_results = null;
$error_msg = '';

// Proses pencarian
if ($_SERVER['REQUEST_METHOD'] == 'POST' || (isset($_GET['cari']) && !empty($_GET['no_pendaftaran']))) {
    $search_no = mysqli_real_escape_string($koneksi, $_GET['no_pendaftaran'] ?? $_POST['no_pendaftaran'] ?? '');
    
    if (!empty($search_no)) {
        $query = mysqli_query($koneksi, "
            SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
            FROM spmb_pendaftar sp
            LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
            LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
            WHERE sp.no_pendaftaran LIKE '%$search_no%' AND sp.status IN ('diterima', 'ditolak')
            LIMIT 1
        ");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $search_results = mysqli_fetch_assoc($query);
        } else {
            $error_msg = "Data tidak ditemukan atau belum ada pengumuman hasil.";
        }
    }
}

// Ambil daftar pendaftar yang diterima (untuk ditampilkan publik, bisa sesuai kebijakan)
$query_diterima = mysqli_query($koneksi, "
    SELECT sp.nama_lengkap, sp.no_pendaftaran, sj.nama_jalur, sg.nama_gelombang
    FROM spmb_pendaftar sp
    LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
    LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
    WHERE sp.status='diterima'
    ORDER BY sp.created_at DESC
    LIMIT 50
");

$total_diterima = 0;
if ($query_diterima) {
    $total_diterima = mysqli_num_rows($query_diterima);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman Hasil SPMB — SMA Negeri 4 Palopo</title>
    <link rel="icon" type="image/png" href="/siakad/assets/img/logo-sekolah.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/siakad/assets/css/landing.css?v=1.0">
    
    <style>
        body { font-family: 'Roboto', sans-serif; background: #F5F7FB; }
        .content-section { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 18px; box-shadow: 0 8px 24px rgba(13, 37, 64, 0.08); margin-top: 40px; }
        .section-title { color: #163A63; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .section-subtitle { color: #4A5568; margin-bottom: 32px; }
        .search-box { background: #F5F7FB; padding: 24px; border-radius: 14px; margin-bottom: 32px; }
        .search-box .form-control { border-color: #E2E8F0; }
        .search-box .form-control:focus { border-color: #F09000; box-shadow: 0 0 0 0.2rem rgba(240, 144, 0, 0.25); }
        .btn-search { background: #163A63; color: white; border: none; padding: 10px 28px; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; }
        .btn-search:hover { background: #2C5A8F; transform: translateY(-2px); }
        .result-card { border-left: 4px solid #10B981; padding: 24px; background: #F0FDF4; border-radius: 12px; margin-bottom: 24px; }
        .result-card.ditolak { border-left-color: #E11D48; background: #FEE2E2; }
        .result-title { color: #163A63; font-size: 18px; font-weight: 700; margin-bottom: 16px; }
        .result-row { display: grid; grid-template-columns: 150px 1fr; gap: 16px; margin-bottom: 12px; }
        .result-label { color: #4A5568; font-weight: 600; font-size: 13px; }
        .result-value { color: #163A63; font-weight: 500; font-size: 13px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 12px; }
        .status-diterima { background: #D4EDDA; color: #155724; }
        .status-ditolak { background: #F8D7DA; color: #721C24; }
        .list-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .list-table th { background: #163A63; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .list-table td { padding: 12px; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #4A5568; }
        .list-table tr:hover { background: #F5F7FB; }
        .info-box { background: #E0F2FE; border-left: 4px solid #0284C7; padding: 16px; border-radius: 8px; margin-bottom: 24px; }
        .info-box p { margin: 0; color: #0C4A6E; font-size: 13px; }
        .empty-state { text-align: center; padding: 40px; }
        .empty-state i { font-size: 48px; color: #94A3B8; margin-bottom: 16px; }
        .empty-state p { color: #94A3B8; }
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

<!-- MAIN CONTENT -->
<div style="padding: 60px 24px;">
    <div class="content-section">
        <h1 class="section-title">
            <i class="fas fa-bullhorn me-2"></i> Pengumuman Hasil SPMB
        </h1>
        <p class="section-subtitle">Cari status hasil seleksi SPMB Anda di sini</p>
        
        <?php if (!$pengumuman_aktif): ?>
        <div class="info-box">
            <p>
                <i class="fas fa-info-circle me-2"></i>
                <strong>Pengumuman belum tersedia.</strong> Silakan kembali lagi nanti untuk melihat hasil seleksi.
            </p>
        </div>
        <?php else: ?>
        
        <!-- SEARCH BOX -->
        <div class="search-box">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 12px;">
                    <div>
                        <label for="no_pendaftaran" style="display: block; margin-bottom: 8px; color: #163A63; font-weight: 600; font-size: 13px;">
                            Cari berdasarkan Nomor Pendaftaran
                        </label>
                        <input type="text" class="form-control" id="no_pendaftaran" name="no_pendaftaran" 
                            placeholder="Contoh: SPMB-2026-00001" value="<?php echo e($search_no); ?>">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- HASIL PENCARIAN -->
        <?php if (!empty($search_no)): ?>
            <?php if ($search_results): ?>
            <div class="result-card <?php echo ($search_results['status'] == 'ditolak') ? 'ditolak' : ''; ?>">
                <div class="result-title">
                    <i class="fas <?php echo ($search_results['status'] == 'diterima') ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                    Hasil Seleksi
                </div>
                
                <div class="result-row">
                    <div class="result-label">Nomor Pendaftaran</div>
                    <div class="result-value"><strong><?php echo e($search_results['no_pendaftaran']); ?></strong></div>
                </div>
                
                <div class="result-row">
                    <div class="result-label">Nama</div>
                    <div class="result-value"><?php echo e($search_results['nama_lengkap']); ?></div>
                </div>
                
                <div class="result-row">
                    <div class="result-label">Jalur</div>
                    <div class="result-value"><?php echo e($search_results['nama_jalur'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="result-row">
                    <div class="result-label">Status</div>
                    <div class="result-value">
                        <span class="status-badge <?php echo ($search_results['status'] == 'diterima') ? 'status-diterima' : 'status-ditolak'; ?>">
                            <?php echo ($search_results['status'] == 'diterima') ? 'DITERIMA' : 'DITOLAK'; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($search_results['status'] == 'diterima'): ?>
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(16, 185, 129, 0.2);">
                    <p style="color: #155724; font-size: 13px; margin: 0;">
                        <i class="fas fa-check me-2"></i>
                        <strong>Selamat! Anda diterima sebagai calon siswa.</strong> Silakan hubungi sekolah untuk informasi daftar ulang.
                    </p>
                </div>
                <?php else: ?>
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(225, 29, 72, 0.2);">
                    <p style="color: #721C24; font-size: 13px; margin: 0;">
                        <i class="fas fa-times me-2"></i>
                        Maaf, Anda tidak lolos dalam seleksi ini. Terima kasih atas minat Anda.
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($error_msg): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_msg; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- DAFTAR YANG DITERIMA -->
        <div style="margin-top: 48px; padding-top: 32px; border-top: 2px solid #E2E8F0;">
            <h3 style="color: #163A63; font-size: 20px; font-weight: 700; margin-bottom: 24px;">
                <i class="fas fa-list me-2"></i> Daftar Calon Siswa Diterima (<?php echo $total_diterima; ?>)
            </h3>
            
            <?php if ($total_diterima > 0): ?>
            <div class="table-responsive">
            <table class="list-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nomor Pendaftaran</th>
                        <th>Nama</th>
                        <th>Jalur</th>
                        <th>Gelombang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($query_diterima, 0);
                    while ($row = mysqli_fetch_assoc($query_diterima)):
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo e($row['no_pendaftaran']); ?></strong></td>
                        <td><?php echo e($row['nama_lengkap']); ?></td>
                        <td><?php echo e($row['nama_jalur'] ?? 'N/A'); ?></td>
                        <td><?php echo e($row['nama_gelombang'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada calon siswa yang diterima untuk ditampilkan.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- INFO PENTING -->
        <div style="margin-top: 40px; padding-top: 32px; border-top: 2px solid #E2E8F0;">
            <div class="info-box">
                <p>
                    <i class="fas fa-bell me-2"></i>
                    <strong>Penting:</strong> Pengumuman hasil seleksi dapat berubah sesuai dengan kebijakan sekolah. 
                    Untuk informasi lebih lanjut, silakan hubungi bagian admisi: (0471) 324567
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="landing-footer">
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
