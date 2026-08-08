<?php
include '../config/koneksi.php';

// Ambil data pengaturan SPMB
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);

// Cek SPMB aktif
$spmb_aktif = $setting['spmb_aktif'] ?? 0;
$spmb_tanggal_buka = $setting['spmb_tanggal_buka'] ?? '';
$spmb_tanggal_tutup = $setting['spmb_tanggal_tutup'] ?? '';

// Jika SPMB tidak aktif, redirect ke landing page
if ($spmb_aktif != 1) {
    header("Location: /siakad/index.php");
    exit();
}

// Ambil jalur pendaftaran dari database
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPMB Online — SMA Negeri 4 Palopo</title>
    <link rel="icon" type="image/png" href="/siakad/assets/img/logo-sekolah.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/siakad/assets/css/landing.css?v=1.0">
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="landing-navbar">
    <div class="landing-navbar-container">
        <div class="landing-navbar-brand">
            <img src="/siakad/assets/img/logo-sekolah.png" alt="Logo" loading="lazy">
            <h6>SPMB Online 2026</h6>
        </div>
        
        <ul class="landing-navbar-menu">
            <li><a href="/siakad/index.php">← Kembali</a></li>
        </ul>
        
        <a href="/siakad/auth/login.php" class="landing-navbar-cta">
            <i class="fas fa-sign-in-alt me-2"></i> Login
        </a>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="landing-hero">
    <div class="landing-hero-content">
        <h1>Pendaftaran SPMB Online</h1>
        <p class="landing-hero-tagline">Sistem Penerimaan Murid Baru SMA Negeri 4 Palopo</p>
        <p class="landing-hero-desc">
            Daftar sebagai calon siswa baru melalui sistem pendaftaran online kami. 
            Proses yang mudah, transparan, dan dapat diakses kapan saja.
        </p>
        
        <div class="landing-hero-buttons">
            <a href="/siakad/spmb/daftar.php" class="btn-hero-primary">
                <i class="fas fa-user-plus me-2"></i> Mulai Pendaftaran
            </a>
            <a href="/siakad/spmb/cek-status.php" class="btn-hero-secondary">
                <i class="fas fa-search me-2"></i> Cek Status
            </a>
        </div>
    </div>
</section>

<!-- PERIODE & JADWAL -->
<section class="landing-section" style="background: #F5F7FB;">
    <div style="max-width: 900px; margin: 0 auto; padding: 0 20px;">
        <h2 class="landing-section-title">Jadwal Pendaftaran</h2>
        
        <div style="background: white; padding: 32px 24px; border-radius: 16px; box-shadow: 0 4px 16px rgba(13, 37, 64, 0.08);">
            <!-- Info Card: Tanggal Buka & Tutup -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <div style="padding: 20px; background: #F5F7FB; border-radius: 12px; border-left: 4px solid #163A63;">
                    <div style="font-size: 11px; color: #94A3B8; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Tanggal Buka</div>
                    <div style="font-size: 24px; font-weight: 800; color: #163A63; margin-bottom: 4px;">
                        <?php echo $spmb_tanggal_buka ? date('d', strtotime($spmb_tanggal_buka)) : '--'; ?>
                    </div>
                    <div style="font-size: 13px; color: #4A5568;">
                        <?php echo $spmb_tanggal_buka ? date('M Y', strtotime($spmb_tanggal_buka)) : 'Belum ditentukan'; ?>
                    </div>
                </div>
                <div style="padding: 20px; background: #FFF5F0; border-radius: 12px; border-left: 4px solid #E11D48;">
                    <div style="font-size: 11px; color: #94A3B8; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Tanggal Tutup</div>
                    <div style="font-size: 24px; font-weight: 800; color: #E11D48; margin-bottom: 4px;">
                        <?php echo $spmb_tanggal_tutup ? date('d', strtotime($spmb_tanggal_tutup)) : '--'; ?>
                    </div>
                    <div style="font-size: 13px; color: #4A5568;">
                        <?php echo $spmb_tanggal_tutup ? date('M Y', strtotime($spmb_tanggal_tutup)) : 'Belum ditentukan'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Info Card: Periode Pendaftaran -->
            <div style="background: linear-gradient(135deg, #E3F2FD 0%, #F3E5F5 100%); padding: 16px 20px; border-radius: 10px; font-size: 13px; color: #4A5568; border-left: 3px solid #3B82F6;">
                <i class="fas fa-calendar-alt" style="color: #3B82F6; margin-right: 8px;"></i>
                <strong>Periode:</strong> 
                <?php 
                if ($spmb_tanggal_buka && $spmb_tanggal_tutup) {
                    echo date('d F Y', strtotime($spmb_tanggal_buka)) . ' hingga ' . date('d F Y', strtotime($spmb_tanggal_tutup));
                } else {
                    echo 'Belum ditentukan';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- JALUR PENDAFTARAN -->
<section class="landing-section" style="background: #F5F7FB;">
    <h2 class="landing-section-title">Jalur Pendaftaran</h2>
    <p class="landing-section-subtitle">Pilih salah satu jalur sesuai dengan syarat dan kriteria yang Anda miliki.</p>
    
    <!-- Jalur Cards Grid -->
    <div class="landing-profil-grid">
        <?php 
        if ($query_jalur && mysqli_num_rows($query_jalur) > 0):
            $icons = ['🗺️', '🏆', '❤️', '📚'];
            $idx = 0;
            while ($jalur = mysqli_fetch_assoc($query_jalur)): 
                $icon = $icons[$idx % count($icons)];
                $idx++;
        ?>
        <div class="landing-profil-card">
            <div style="font-size: 48px; margin-bottom: 16px;">
                <?php echo $icon; ?>
            </div>
            <h3><?php echo htmlspecialchars($jalur['nama_jalur']); ?></h3>
            <p>
                <strong>Kuota:</strong> <?php echo $jalur['kuota']; ?> orang
                <?php if ($jalur['keterangan']): ?>
                    <br><br><?php echo htmlspecialchars($jalur['keterangan']); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94A3B8;">
            <i class="fas fa-folder-open fa-3x mb-3"></i>
            <p>Belum ada jalur pendaftaran yang tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- SYARAT & KETENTUAN -->
<section class="landing-section" style="background: white;">
    <h2 class="landing-section-title">Syarat & Ketentuan</h2>
    
    <div style="max-width: 800px; margin: 0 auto; background: #F5F7FB; padding: 32px; border-radius: 18px; border-left: 4px solid #F09000;">
        <?php if ($setting['spmb_syarat']): ?>
            <div style="color: #4A5568; line-height: 1.8; font-size: 14px;">
                <?php echo nl2br(htmlspecialchars($setting['spmb_syarat'])); ?>
            </div>
        <?php else: ?>
            <p style="color: #94A3B8; text-align: center;">
                <i class="fas fa-info-circle me-2"></i> Syarat dan ketentuan belum ditentukan.
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- ALUR PENDAFTARAN -->
<section class="landing-section" style="background: #F5F7FB;">
    <h2 class="landing-section-title">Alur Pendaftaran</h2>
    
    <!-- Step-by-step Process Cards -->
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <!-- Step 1: Isi Data Diri -->
            <div style="background: white; padding: 24px; border-radius: 14px; text-align: center; box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin: 0 auto 16px; font-weight: bold;">1</div>
                <h4 style="color: #163A63; margin-bottom: 8px;">Isi Data Diri</h4>
                <p style="color: #4A5568; font-size: 14px;">Lengkapi form pendaftaran dengan data diri Anda</p>
            </div>
            
            <!-- Step 2: Dapatkan No. Pendaftar -->
            <div style="background: white; padding: 24px; border-radius: 14px; text-align: center; box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin: 0 auto 16px; font-weight: bold;">2</div>
                <h4 style="color: #163A63; margin-bottom: 8px;">Dapatkan No. Pendaftar</h4>
                <p style="color: #4A5568; font-size: 14px;">Terima nomor pendaftar via email</p>
            </div>
            
            <!-- Step 3: Upload Dokumen -->
            <div style="background: white; padding: 24px; border-radius: 14px; text-align: center; box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin: 0 auto 16px; font-weight: bold;">3</div>
                <h4 style="color: #163A63; margin-bottom: 8px;">Upload Dokumen</h4>
                <p style="color: #4A5568; font-size: 14px;">Unggah dokumen pendukung yang diperlukan</p>
            </div>
            
            <!-- Step 4: Verifikasi -->
            <div style="background: white; padding: 24px; border-radius: 14px; text-align: center; box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin: 0 auto 16px; font-weight: bold;">4</div>
                <h4 style="color: #163A63; margin-bottom: 8px;">Verifikasi</h4>
                <p style="color: #4A5568; font-size: 14px;">Admin akan memverifikasi dokumen Anda</p>
            </div>
            
            <!-- Step 5: Hasil Seleksi -->
            <div style="background: white; padding: 24px; border-radius: 14px; text-align: center; box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08);">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #163A63, #2C5A8F); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; margin: 0 auto 16px; font-weight: bold;">5</div>
                <h4 style="color: #163A63; margin-bottom: 8px;">Hasil Seleksi</h4>
                <p style="color: #4A5568; font-size: 14px;">Tunggu pengumuman hasil seleksi</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION: Call to Action -->
<section class="landing-section" style="background: linear-gradient(135deg, #163A63 0%, #2C5A8F 100%); color: white; text-align: center;">
    <h2 style="font-size: 36px; font-weight: 800; margin-bottom: 20px;">Siap Mendaftar?</h2>
    <p style="font-size: 16px; margin-bottom: 32px; opacity: 0.9;">
        Jangan lewatkan kesempatan ini! Daftar sekarang dan mulai perjalanan akademik Anda bersama kami.
    </p>
    
    <!-- Action Buttons -->
    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <a href="/siakad/spmb/daftar.php" class="btn-hero-primary">
            <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
        </a>
        <a href="/siakad/spmb/cek-status.php" style="background: rgba(255,255,255,0.2); color: white; padding: 14px 32px; border-radius: 999px; font-weight: 600; display: inline-block; border: 2px solid rgba(255,255,255,0.5); transition: all 0.3s ease;">
            <i class="fas fa-search me-2"></i> Cek Status
        </a>
    </div>
</section>

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
