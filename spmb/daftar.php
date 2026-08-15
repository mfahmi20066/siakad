<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/csrf.php';
include '../config/koneksi.php';
include '../config/mailer.php';

$title = "Daftar SPMB";
$error = '';
$success = '';
$no_pendaftaran = '';

// ambil data pengaturan spmb
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);
$spmb_aktif = $setting['spmb_aktif'] ?? 0;

// spmb nonaktif? redirect
if ($spmb_aktif != 1) {
    header("Location: /siakad/index.php");
    exit();
}

// gelombang aktif yang masih dalam periode buka
$query_gelombang = mysqli_query($koneksi, "SELECT * FROM spmb_gelombang 
    WHERE status='aktif' 
      AND tanggal_mulai <= CURDATE() 
      AND tanggal_selesai >= CURDATE() 
    ORDER BY tanggal_mulai ASC");
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");

// proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrf();
    // validasi input
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap'] ?? '');
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn'] ?? '');
    $nik = mysqli_real_escape_string($koneksi, $_POST['nik'] ?? '');
    $tempat_lahir = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin'] ?? '');
    $asal_sekolah = mysqli_real_escape_string($koneksi, $_POST['asal_sekolah'] ?? '');
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
    $nama_ortu = mysqli_real_escape_string($koneksi, $_POST['nama_ortu'] ?? '');
    $no_hp_ortu = mysqli_real_escape_string($koneksi, $_POST['no_hp_ortu'] ?? '');
    $email = mysqli_real_escape_string($koneksi, strtolower($_POST['email'] ?? ''));
    $gelombang_id = (int) $_POST['gelombang_id'];
    $jalur_id = (int) $_POST['jalur_id'];
    
    // ambil nama gelombang & jalur buat email
    $q_gelombang = mysqli_query($koneksi, "SELECT nama_gelombang FROM spmb_gelombang WHERE id=$gelombang_id");
    $gelombang_data = mysqli_fetch_assoc($q_gelombang);
    $gelombang_name = $gelombang_data['nama_gelombang'] ?? 'N/A';
    
    $q_jalur = mysqli_query($koneksi, "SELECT nama_jalur FROM spmb_jalur WHERE id=$jalur_id");
    $jalur_data = mysqli_fetch_assoc($q_jalur);
    $jalur_name = $jalur_data['nama_jalur'] ?? 'N/A';
    
    // validasi field wajib
    if (empty($nama_lengkap) || empty($nik) || empty($tanggal_lahir) || empty($email) || empty($gelombang_id) || empty($jalur_id)) {
        $error = "Harap lengkapi semua field yang wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($nik) != 16) {
        $error = "NIK harus terdiri dari 16 digit!";
    } else {
        // validasi gelombang aktif & periode buka (server-side, jangan percaya ui)
        $q_gel_cek = mysqli_query($koneksi, "SELECT id FROM spmb_gelombang 
            WHERE id=$gelombang_id AND status='aktif' 
              AND tanggal_mulai <= CURDATE() 
              AND tanggal_selesai >= CURDATE()");
        if (!$q_gel_cek || mysqli_num_rows($q_gel_cek) == 0) {
            $error = "Gelombang yang dipilih tidak tersedia atau sudah ditutup.";
        } else {
        // cek email udah terdaftar
        $cek_email = mysqli_query($koneksi, "SELECT id FROM spmb_pendaftar WHERE email='$email'");
        if (mysqli_num_rows($cek_email) > 0) {
            $error = "Email sudah terdaftar di sistem SPMB! Gunakan email lain.";
        } else {
            // generate nomor pendaftaran
            $query_max = mysqli_query($koneksi, "SELECT MAX(id) as max_id FROM spmb_pendaftar");
            $row = mysqli_fetch_assoc($query_max);
            $next_id = ($row['max_id'] ?? 0) + 1;
            $tahun = date('Y');
            $no_pendaftaran = "SPMB-$tahun-" . str_pad($next_id, 5, '0', STR_PAD_LEFT);
            
            // insert ke database
            $insert = mysqli_query($koneksi, "INSERT INTO spmb_pendaftar 
                (no_pendaftaran, gelombang_id, jalur_id, nama_lengkap, nisn, nik, tempat_lahir, tanggal_lahir, 
                 jenis_kelamin, asal_sekolah, alamat, nama_ortu, no_hp_ortu, email, status)
                VALUES 
                ('$no_pendaftaran', $gelombang_id, $jalur_id, '$nama_lengkap', '$nisn', '$nik', '$tempat_lahir', 
                 '$tanggal_lahir', '$jenis_kelamin', '$asal_sekolah', '$alamat', '$nama_ortu', '$no_hp_ortu', 
                 '$email', 'menunggu_dokumen')");
            
            if ($insert) {
                // ambil id pendaftar yang barusan dibuat
                $pendaftar_id = mysqli_insert_id($koneksi);
                
                // url dasar dinamis (hindari hardcode localhost)
                $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                
                // kirim email
                $subject = "Nomor Pendaftaran SPMB SMA Negeri 4 Palopo";
                $body = "
                Halo $nama_lengkap,<br><br>
                
                Pendaftaran Anda telah berhasil diterima! Berikut detail pendaftaran Anda:<br><br>
                
                <strong>Nomor Pendaftaran:</strong> $no_pendaftaran<br>
                <strong>Nama:</strong> $nama_lengkap<br>
                <strong>Email:</strong> $email<br>
                <strong>Tanggal Lahir:</strong> " . date('d-m-Y', strtotime($tanggal_lahir)) . "<br>
                <strong>Jalur:</strong> $jalur_name<br>
                <strong>Gelombang:</strong> $gelombang_name<br><br>
                
                <strong>Langkah selanjutnya:</strong><br>
                1. Simpan nomor pendaftaran Anda dengan baik<br>
                2. Klik link di bawah untuk upload dokumen:<br>
                   <a href='$base_url/siakad/spmb/upload-dokumen.php'>Upload Dokumen</a><br>
                3. Atau masuk ke <a href='$base_url/siakad/spmb/cek-status.php'>Cek Status</a><br><br>
                
                Terima kasih,<br>
                Tim SPMB SMA Negeri 4 Palopo
                ";
                
                try {
                    kirimEmail($email, $subject, $body);
                } catch (\RuntimeException $e) {
                    error_log("[SPMB Daftar] Gagal kirim email konfirmasi ke $email: " . $e->getMessage());
                    // tetep lanjut, jangan gagalkan pendaftaran
                }
                $success = "Pendaftaran berhasil! Nomor pendaftaran Anda: <strong>$no_pendaftaran</strong>";
                $show_success = true;
            } else {
                $error = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
            }
        }
        }
    }
}
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
        .form-section { max-width: 700px; margin: 0 auto; background: white; padding: 40px; border-radius: 18px; box-shadow: 0 8px 24px rgba(13, 37, 64, 0.08); }
        .form-title { color: #163A63; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .form-subtitle { color: #4A5568; margin-bottom: 32px; }
        .form-group label { color: #163A63; font-weight: 600; margin-bottom: 8px; }
        .form-control:focus { border-color: #F09000; box-shadow: 0 0 0 0.2rem rgba(240, 144, 0, 0.25); }
        .btn-submit { background: #163A63; color: white; padding: 12px 32px; border: none; border-radius: 12px; font-weight: 600; transition: all 0.3s ease; width: 100%; }
        .btn-submit:hover { background: #2C5A8F; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(13, 37, 64, 0.2); }
        .success-box { background: #D4EDDA; border: 1px solid #C3E6CB; color: #155724; padding: 20px; border-radius: 12px; margin-bottom: 24px; }
        .success-number { font-size: 24px; font-weight: 800; color: #163A63; margin: 16px 0; }
        .form-row-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 576px) {
            .form-section { padding: 24px; }
            .form-row-cols { grid-template-columns: 1fr; }
        }
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
    <div class="form-section">
        <h1 class="form-title">
            <i class="fas fa-pen me-2"></i> Daftar SPMB
        </h1>
        <p class="form-subtitle">Isi form berikut untuk memulai pendaftaran Anda</p>
        
        <?php if ($success): ?>
        <div class="success-box">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Pendaftaran Berhasil!</strong><br>
                    <?php echo $success; ?>
                </div>
            </div>
            <p style="margin: 0; font-size: 13px;">
                Email konfirmasi telah dikirim ke alamat email Anda. 
                Silakan cek inbox email Anda untuk informasi lebih lanjut.
            </p>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="/siakad/spmb/upload-dokumen.php" class="btn btn-primary" style="background: #163A63; border: none;">
                    <i class="fas fa-upload me-2"></i> Upload Dokumen
                </a>
                <a href="/siakad/spmb/cek-status.php" class="btn btn-secondary">
                    <i class="fas fa-search me-2"></i> Cek Status
                </a>
            </div>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group mb-3">
                <label for="nama_lengkap">Nama Lengkap <span style="color: #E11D48;">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
            </div>
            
            <div class="form-row-cols">
                <div class="form-group mb-3">
                    <label for="nisn">NISN</label>
                    <input type="text" class="form-control" id="nisn" name="nisn" placeholder="Opsional">
                </div>
                <div class="form-group mb-3">
                    <label for="nik">NIK <span style="color: #E11D48;">*</span></label>
                    <input type="text" class="form-control" id="nik" name="nik" maxlength="16" required>
                </div>
            </div>
            
            <div class="form-row-cols">
                <div class="form-group mb-3">
                    <label for="tempat_lahir">Tempat Lahir</label>
                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir">
                </div>
                <div class="form-group mb-3">
                    <label for="tanggal_lahir">Tanggal Lahir <span style="color: #E11D48;">*</span></label>
                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
                </div>
            </div>
            
            <div class="form-row-cols">
                <div class="form-group mb-3">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select class="form-control" id="jenis_kelamin" name="jenis_kelamin">
                        <option value="">-- Pilih --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="asal_sekolah">Asal Sekolah <span style="color: #E11D48;">*</span></label>
                    <input type="text" class="form-control" id="asal_sekolah" name="asal_sekolah" required>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="alamat">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2"></textarea>
            </div>
            
            <div class="form-group mb-3">
                <label for="nama_ortu">Nama Orang Tua/Wali <span style="color: #E11D48;">*</span></label>
                <input type="text" class="form-control" id="nama_ortu" name="nama_ortu" required>
            </div>
            
            <div class="form-group mb-3">
                <label for="no_hp_ortu">No. HP Orang Tua <span style="color: #E11D48;">*</span></label>
                <input type="tel" class="form-control" id="no_hp_ortu" name="no_hp_ortu" required>
            </div>
            
            <div class="form-group mb-3">
                <label for="email">Email <span style="color: #E11D48;">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
                <small class="text-muted">Email untuk menerima nomor pendaftaran</small>
            </div>
            
            <div class="form-row-cols">
                <div class="form-group mb-3">
                    <label for="gelombang_id">Pilih Gelombang <span style="color: #E11D48;">*</span></label>
                    <select class="form-control" id="gelombang_id" name="gelombang_id" required>
                        <option value="">-- Pilih Gelombang --</option>
                        <?php 
                        if ($query_gelombang && mysqli_num_rows($query_gelombang) > 0):
                            while ($gel = mysqli_fetch_assoc($query_gelombang)):
                        ?>
                        <option value="<?php echo $gel['id']; ?>">
                            <?php echo e($gel['nama_gelombang']); ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="jalur_id">Pilih Jalur <span style="color: #E11D48;">*</span></label>
                    <select class="form-control" id="jalur_id" name="jalur_id" required>
                        <option value="">-- Pilih Jalur --</option>
                        <?php 
                        if ($query_jalur && mysqli_num_rows($query_jalur) > 0):
                            while ($jr = mysqli_fetch_assoc($query_jalur)):
                        ?>
                        <option value="<?php echo $jr['id']; ?>">
                            <?php echo e($jr['nama_jalur']); ?>
                        </option>
                        <?php 
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        Saya setuju dengan <strong>syarat dan ketentuan</strong> pendaftaran SPMB
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane me-2"></i> Daftar Sekarang
            </button>
        </form>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center;">
            <p style="color: #4A5568; font-size: 14px; margin: 0;">
                Sudah punya nomor pendaftaran? 
                <a href="/siakad/spmb/cek-status.php" style="color: #F09000; text-decoration: none; font-weight: 600;">
                    Cek Status
                </a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
