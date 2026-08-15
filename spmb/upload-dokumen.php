<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/csrf.php';
include '../config/koneksi.php';
include '../config/mailer.php';

$title = "Upload Dokumen";
$pendaftar = null;
$error = '';
$success = '';

// gate: spmb nonaktif -> tutup akses upload
$query_setting = mysqli_query($koneksi, "SELECT spmb_aktif FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);
if (($setting['spmb_aktif'] ?? 0) != 1) {
    header("Location: /siakad/spmb/index.php");
    exit();
}

// proses form pencarian
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['cari'])) {
    $no_pendaftaran = mysqli_real_escape_string($koneksi, $_GET['no_pendaftaran'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_GET['tanggal_lahir'] ?? '');
    
    if (empty($no_pendaftaran) || empty($tanggal_lahir)) {
        $error = "Harap isi semua field!";
    } else {
        // query pendaftar
        $query = mysqli_query($koneksi, "
            SELECT sp.*, sj.dokumen_wajib 
            FROM spmb_pendaftar sp
            LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
            WHERE sp.no_pendaftaran='$no_pendaftaran' AND sp.tanggal_lahir='$tanggal_lahir'
        ");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $pendaftar = mysqli_fetch_assoc($query);
            // set session buat tracking upload
            $_SESSION['spmb_pendaftar_id'] = $pendaftar['id'];
        } else {
            $error = "Data tidak ditemukan. Periksa nomor pendaftaran dan tanggal lahir Anda.";
        }
    }
}

// proses upload dokumen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_dokumen'])) {
    verifyCsrf();
    $pendaftar_id = (int) $_POST['pendaftar_id'];
    
    // validasi sesi: cuma pendaftar yang login (via pencarian 2fa) boleh upload — cegah idor ngirim ke pendaftar lain
    $session_pendaftar_id = (int) ($_SESSION['spmb_pendaftar_id'] ?? 0);
    if ($session_pendaftar_id <= 0 || $session_pendaftar_id !== $pendaftar_id) {
        $error = "Sesi tidak valid. Silakan lakukan pencarian ulang nomor pendaftaran Anda.";
    } else {
        // query ulang buat validasi
        $cek = mysqli_query($koneksi, "
            SELECT sp.id, sp.status, sj.dokumen_wajib 
            FROM spmb_pendaftar sp
            LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
            WHERE sp.id=$pendaftar_id
        ");
        if (!$cek || mysqli_num_rows($cek) == 0) {
            $error = "Data pendaftar tidak ditemukan!";
        } else {
            $pen = mysqli_fetch_assoc($cek);
            
            // cegah downgrade status: upload cuma boleh pas menunggu_dokumen / menunggu_verifikasi
            if (!in_array($pen['status'], ['menunggu_dokumen', 'menunggu_verifikasi'])) {
                $error = "Status pendaftaran Anda sudah " . ucfirst(str_replace('_', ' ', $pen['status'])) . ". Anda tidak dapat mengunggah dokumen lagi.";
            } else {
                $upload_dir = "../uploads/spmb/$pendaftar_id/";
        
        // bikin folder kalo belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $uploaded_count = 0;
        $upload_errors = [];
        $missing_required = [];
        
        // dokumen wajib dari spmb_jalur.dokumen_wajib (json), fallback default
        $required_docs = ['kk', 'akta', 'ijazah', 'foto'];
        if (!empty($pen['dokumen_wajib'])) {
            $decoded = json_decode($pen['dokumen_wajib'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $required_docs = array_values(array_map('strval', $decoded));
            }
        }
        
        // proses tiap file yang diupload
        if (isset($_FILES['dokumen']) && is_array($_FILES['dokumen']) && isset($_FILES['dokumen']['error'])) {
            $file_keys = array_keys($_FILES['dokumen']['error']);
            
            foreach ($file_keys as $key) {
                // skip kalo ga ada file
                if ($_FILES['dokumen']['error'][$key] == UPLOAD_ERR_NO_FILE) {
                    // cek dokumen ini wajib ga
                    if (in_array($key, $required_docs)) {
                        $missing_required[] = ucfirst($key);
                    }
                    continue;
                }
                
                $jenis_dokumen = sanitize_filename($key);
                $tmp_file = $_FILES['dokumen']['tmp_name'][$key] ?? null;
                $file_name = $_FILES['dokumen']['name'][$key] ?? null;
                $file_size = $_FILES['dokumen']['size'][$key] ?? 0;
                $file_error = $_FILES['dokumen']['error'][$key];
                
                // validasi file ada
                if (empty($tmp_file) || empty($file_name)) {
                    if (in_array($key, $required_docs)) {
                        $missing_required[] = ucfirst($key);
                    }
                    continue;
                }
                
                // validasi error upload
                if ($file_error != UPLOAD_ERR_OK) {
                    $upload_errors[] = "Error upload $jenis_dokumen: " . get_upload_error_message($file_error);
                    continue;
                }
                
                // validasi ukuran file
                if ($file_size > 2 * 1024 * 1024) { // max 2mb
                    $upload_errors[] = "$jenis_dokumen terlalu besar (max 2MB)";
                    continue;
                }
                
                // validasi tipe file
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $upload_errors[] = "$jenis_dokumen format tidak diizinkan (jpg/png/pdf saja)";
                    continue;
                }
                
                // generate nama file
                $new_filename = $jenis_dokumen . '_' . time() . '.' . $file_ext;
                $new_filepath = $upload_dir . $new_filename;
                
                if (move_uploaded_file($tmp_file, $new_filepath)) {
                    // simpan ke database
                    $insert = mysqli_query($koneksi, "
                        INSERT INTO spmb_dokumen (pendaftar_id, jenis_dokumen, path_file, status_verifikasi)
                        VALUES ($pendaftar_id, '$jenis_dokumen', '$new_filename', 'menunggu')
                        ON DUPLICATE KEY UPDATE path_file='$new_filename', status_verifikasi='menunggu'
                    ");
                    
                    if ($insert) {
                        $uploaded_count++;
                    } else {
                        $upload_errors[] = "Error menyimpan data $jenis_dokumen ke database";
                    }
                } else {
                    $upload_errors[] = "Error menyimpan file $jenis_dokumen";
                }
            }
        }
        
        // cek dokumen wajib
        if (count($missing_required) > 0) {
            $error = "Dokumen wajib berikut belum diunggah: <strong>" . implode(", ", $missing_required) . "</strong>";
        }
        
        if ($uploaded_count > 0 && count($missing_required) == 0 && count($upload_errors) == 0) {
            // cek semua dokumen wajib udah keupload
            $query_cek = mysqli_query($koneksi, "
                SELECT COUNT(*) as total FROM spmb_dokumen WHERE pendaftar_id=$pendaftar_id
            ");
            $result = mysqli_fetch_assoc($query_cek);
            
            if ($result['total'] > 0) {
                // update status pendaftar jadi menunggu_verifikasi
                mysqli_query($koneksi, "UPDATE spmb_pendaftar SET status='menunggu_verifikasi' WHERE id=$pendaftar_id");
                
                // ambil data pendaftar buat email
                $q_pen = mysqli_query($koneksi, "SELECT * FROM spmb_pendaftar WHERE id=$pendaftar_id");
                $pen_data = mysqli_fetch_assoc($q_pen);
                
                // Kirim email notifikasi ke pendaftar
                $subject = "Dokumen Pendaftaran SPMB Diterima";
                $body = "
                Halo {$pen_data['nama_lengkap']},<br><br>
                
                Dokumen pendaftaran SPMB Anda telah berhasil diunggah dan diterima sistem kami.<br><br>
                
                <strong>Detail:</strong><br>
                No. Pendaftaran: {$pen_data['no_pendaftaran']}<br>
                Status: Menunggu Verifikasi<br><br>
                
                Tim admin kami akan memverifikasi dokumen Anda dalam waktu 3-5 hari kerja. 
                Silakan cek status pendaftaran Anda secara berkala di halaman cek status.<br><br>
                
                Terima kasih,<br>
                Tim SPMB SMA Negeri 4 Palopo
                ";
                
                try {
                    kirimEmail($pen_data['email'], $subject, $body);
                } catch (\RuntimeException $e) {
                    error_log("[SPMB Upload] Gagal kirim email notifikasi ke {$pen_data['email']}: " . $e->getMessage());
                }
                
                // Tambahkan notifikasi admin
                addAdminNotification(
                    $koneksi,
                    "Dokumen Pendaftaran Diunggah - {$pen_data['nama_lengkap']}",
                    "Pendaftar <strong>{$pen_data['nama_lengkap']}</strong> (No. {$pen_data['no_pendaftaran']}) telah mengunggah dokumen pendaftaran SPMB. " .
                    "<br><br><strong>Detail Pendaftar:</strong><br>" .
                    "Nama: {$pen_data['nama_lengkap']}<br>" .
                    "No. Pendaftaran: {$pen_data['no_pendaftaran']}<br>" .
                    "Email: {$pen_data['email']}<br>" .
                    "No. HP: {$pen_data['no_hp_ortu']}<br>" .
                    "Tanggal Lahir: {$pen_data['tanggal_lahir']}<br>" .
                    "Alamat: {$pen_data['alamat']}<br><br>" .
                    "Total dokumen: $uploaded_count file<br>" .
                    "Status: Menunggu Verifikasi",
                    "/siakad/admin/spmb/pendaftar/index.php"
                );
                
                $success = "$uploaded_count dokumen berhasil diunggah. Status Anda diubah menjadi 'Menunggu Verifikasi'.";
                $pendaftar = null; // Reset form
            }
        }
        
        if (count($upload_errors) > 0) {
            $error = "Beberapa file gagal diunggah:<br>" . implode("<br>", $upload_errors);
        }
            }
        }
    }
}

// Helper functions
function sanitize_filename($str) {
    return preg_replace("/[^a-z0-9_]/i", '', $str);
}

function get_upload_error_message($error_code) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => "File terlalu besar (melampaui limit server)",
        UPLOAD_ERR_FORM_SIZE => "File terlalu besar",
        UPLOAD_ERR_PARTIAL => "File hanya ter-upload sebagian",
        UPLOAD_ERR_NO_FILE => "Tidak ada file yang dipilih",
        UPLOAD_ERR_NO_TMP_DIR => "Folder temporary tidak tersedia",
        UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk",
        UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh extension"
    ];
    return $messages[$error_code] ?? "Error tidak dikenal";
}

// Function untuk menambahkan notifikasi ke admin
function addAdminNotification($koneksi, $judul, $pesan, $link = null) {
    // Ambil ID admin (user dengan role admin, biasanya user_id = 1)
    $query_admin = mysqli_query($koneksi, "
        SELECT u.id FROM users u 
        WHERE u.role = 'admin' 
        LIMIT 1
    ");
    
    if ($query_admin && mysqli_num_rows($query_admin) > 0) {
        $admin = mysqli_fetch_assoc($query_admin);
        $admin_id = $admin['id'];
        
        // Simpan pesan sebagai PLAIN TEXT (buang tag HTML agar tidak bocor
        // ke tampilan dropdown notifikasi yang men-escape HTML).
        $judul_clean = trim(strip_tags($judul));
        $pesan_clean = trim(strip_tags($pesan));
        
        // Escape strings untuk keamanan
        $judul_escaped = mysqli_real_escape_string($koneksi, $judul_clean);
        $pesan_escaped = mysqli_real_escape_string($koneksi, $pesan_clean);
        $link_escaped = $link ? mysqli_real_escape_string($koneksi, $link) : null;
        
        // Insert notifikasi ke database
        $query = "
            INSERT INTO notifikasi (user_id, judul, pesan, link, is_read, created_at)
            VALUES ($admin_id, '$judul_escaped', '$pesan_escaped', " . 
            ($link_escaped ? "'$link_escaped'" : "NULL") . 
            ", 0, NOW())
        ";
        
        return mysqli_query($koneksi, $query);
    }
    
    return false;
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
    <link rel="stylesheet" href="/siakad/assets/css/alert.css?v=1.0">
    
    <style>
        body { font-family: 'Roboto', sans-serif; background: #F5F7FB; }
        .form-section { max-width: 700px; margin: 0 auto; background: white; padding: 40px; border-radius: 18px; box-shadow: 0 8px 24px rgba(13, 37, 64, 0.08); margin-top: 40px; }
        .form-title { color: #163A63; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
        .form-subtitle { color: #4A5568; margin-bottom: 32px; }
        .form-group label { color: #163A63; font-weight: 600; margin-bottom: 8px; }
        .form-control:focus, .form-select:focus { border-color: #F09000; box-shadow: 0 0 0 0.2rem rgba(240, 144, 0, 0.25); }
        .upload-area { border: 2px dashed #E2E8F0; padding: 40px; border-radius: 14px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #F9FAFB; }
        .upload-area:hover { border-color: #F09000; background: #FFFBF0; }
        .upload-area.dragover { border-color: #F09000; background: #FFFBF0; }
        
        /* Progress Bar */
        .progress-section { margin-bottom: 24px; }
        .progress-title { color: #163A63; font-weight: 600; font-size: 14px; margin-bottom: 8px; display: flex; justify-content: space-between; }
        .progress-count { color: #94A3B8; font-size: 12px; }
        .progress { height: 8px; background: #E2E8F0; border-radius: 10px; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #10B981, #059669); height: 100%; transition: width 0.3s ease; }
        
        /* Upload Items */
        .upload-item { background: #F5F7FB; padding: 16px; border-radius: 10px; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; transition: all 0.2s ease; border: 2px solid #E2E8F0; }
        .upload-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 37, 64, 0.08); }
        .upload-item.uploaded { background: #D4EDDA; border-color: #10B981; }
        .upload-item.invalid { background: #F8D7DA; border-color: #DC3545; }
        .upload-item.verifying { background: #FFF3CD; border-color: #FFC107; }
        .upload-item input[type="file"] { display: none; }
        .upload-item label { margin: 0; cursor: pointer; flex: 1; }
        .upload-item-name { color: #163A63; font-weight: 600; font-size: 14px; }
        .upload-item-size { color: #94A3B8; font-size: 12px; margin-top: 4px; }
        
        /* Status Badge */
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-valid { background: #10B981; color: white; }
        .badge-invalid { background: #DC3545; color: white; }
        .badge-verifying { background: #FFC107; color: #000; }
        .badge-pending { background: #E2E8F0; color: #4A5568; }
        
        /* Status Icons */
        .status-icon { font-size: 18px; min-width: 24px; text-align: center; }
        .icon-pending { color: #94A3B8; }
        .icon-uploading { color: #0284C7; animation: pulse 1.5s infinite; }
        .icon-uploaded { color: #10B981; }
        .icon-invalid { color: #DC3545; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Summary Section */
        .summary-section { background: #F9FAFB; padding: 16px; border-radius: 10px; margin-bottom: 24px; border: 1px solid #E2E8F0; }
        .summary-title { color: #163A63; font-weight: 600; margin-bottom: 12px; font-size: 14px; }
        .summary-stats { display: flex; gap: 16px; flex-wrap: wrap; }
        .stat { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .stat-value { font-weight: 600; color: #163A63; }
        .stat-label { color: #4A5568; }
        
        .btn-submit { background: #163A63; color: white; padding: 12px 32px; border: none; border-radius: 12px; font-weight: 600; width: 100%; transition: all 0.3s ease; }
        .btn-submit:hover { background: #2C5A8F; transform: translateY(-2px); }
        .btn-submit:disabled { background: #94A3B8; cursor: not-allowed; }
        .success-box { background: #D4EDDA; border: 1px solid #C3E6CB; color: #155724; padding: 20px; border-radius: 12px; margin-bottom: 24px; }
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
        <?php if (!$pendaftar && !$success): ?>
        
        <!-- FORM LOGIN -->
        <h1 class="form-title">
            <i class="fas fa-lock me-2"></i> Upload Dokumen
        </h1>
        <p class="form-subtitle">Masukkan nomor pendaftaran dan tanggal lahir untuk akses</p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="GET">
            <input type="hidden" name="cari" value="1">
            
            <div class="form-group mb-3">
                <label for="no_pendaftaran">Nomor Pendaftaran <span style="color: #E11D48;">*</span></label>
                <input type="text" class="form-control" id="no_pendaftaran" name="no_pendaftaran" 
                    placeholder="Contoh: SPMB-2026-00001" required>
            </div>
            
            <div class="form-group mb-4">
                <label for="tanggal_lahir">Tanggal Lahir <span style="color: #E11D48;">*</span></label>
                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-search me-2"></i> Lanjutkan
            </button>
        </form>
        
        <?php elseif ($pendaftar): ?>
        
        <!-- FORM UPLOAD -->
        <h1 class="form-title">
            <i class="fas fa-upload me-2"></i> Upload Dokumen
        </h1>
        <p class="form-subtitle">Pendaftar: <?php echo e($pendaftar['nama_lengkap']); ?></p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div style="background: #E0F2FE; border-left: 4px solid #0284C7; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <p style="margin: 0; color: #0C4A6E; font-size: 13px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Status Pendaftaran:</strong> <?php echo ucfirst(str_replace('_', ' ', $pendaftar['status'])); ?>
            </p>
        </div>
        
        <?php
        // Ambil status upload yang sudah ada dari database
        $query_uploads = mysqli_query($koneksi, "
            SELECT jenis_dokumen, status_verifikasi 
            FROM spmb_dokumen 
            WHERE pendaftar_id=" . $pendaftar['id']
        );
        $uploaded_docs = [];
        $uploaded_status = [];
        if ($query_uploads) {
            while ($row = mysqli_fetch_assoc($query_uploads)) {
                $uploaded_docs[] = $row['jenis_dokumen'];
                $uploaded_status[$row['jenis_dokumen']] = $row['status_verifikasi'];
            }
        }
        
        // Dokumen wajib dari jalur (JSON) — fallback default
        $required_docs_list = ['kk', 'akta', 'ijazah', 'foto'];
        if (!empty($pendaftar['dokumen_wajib'])) {
            $decoded = json_decode($pendaftar['dokumen_wajib'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $required_docs_list = array_values(array_map('strval', $decoded));
            }
        }
        $total_required = count($required_docs_list);
        $uploaded_required = count(array_intersect($uploaded_docs, $required_docs_list));
        $valid_docs = 0;
        $invalid_docs = 0;
        $verifying_docs = 0;
        
        foreach ($uploaded_docs as $doc) {
            if ($uploaded_status[$doc] === 'valid') {
                $valid_docs++;
            } elseif ($uploaded_status[$doc] === 'tidak_valid') {
                $invalid_docs++;
            } else {
                $verifying_docs++;
            }
        }
        
        $progress_percentage = ($uploaded_required / $total_required) * 100;
        ?>
        
        <!-- PROGRESS BAR -->
        <div class="progress-section">
            <div class="progress-title">
                <span>Progress Upload Dokumen</span>
                <span class="progress-count"><?php echo $uploaded_required; ?>/<?php echo $total_required; ?> dokumen wajib</span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%;"></div>
            </div>
        </div>
        
        <!-- SUMMARY SECTION -->
        <div class="summary-section">
            <div class="summary-title">
                <i class="fas fa-chart-pie me-2"></i> Ringkasan Status Dokumen
            </div>
            <div class="summary-stats">
                <div class="stat">
                    <i class="fas fa-check-circle" style="color: #10B981;"></i>
                    <span class="stat-label">Valid:</span>
                    <span class="stat-value"><?php echo $valid_docs; ?></span>
                </div>
                <div class="stat">
                    <i class="fas fa-hourglass-half" style="color: #FFC107;"></i>
                    <span class="stat-label">Verifikasi:</span>
                    <span class="stat-value"><?php echo $verifying_docs; ?></span>
                </div>
                <div class="stat">
                    <i class="fas fa-times-circle" style="color: #DC3545;"></i>
                    <span class="stat-label">Ditolak:</span>
                    <span class="stat-value"><?php echo $invalid_docs; ?></span>
                </div>
                <div class="stat">
                    <i class="fas fa-upload" style="color: #94A3B8;"></i>
                    <span class="stat-label">Belum Upload:</span>
                    <span class="stat-value"><?php echo $total_required - $uploaded_required; ?></span>
                </div>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pendaftar_id" value="<?php echo $pendaftar['id']; ?>">
            <input type="hidden" name="upload_dokumen" value="1">
            
            <div class="form-group mb-4">
                <label class="form-label" style="display: block; margin-bottom: 16px;">
                    <strong>Dokumen Wajib</strong>
                </label>
                
                <!-- KK -->
                <div class="upload-item <?php echo in_array('kk', $uploaded_docs) ? (($uploaded_status['kk'] === 'tidak_valid') ? 'invalid' : 'uploaded') : ''; ?>">
                    <div class="status-icon">
                        <?php if (!in_array('kk', $uploaded_docs)): ?>
                            <i class="fas fa-file-alt icon-pending"></i>
                        <?php elseif ($uploaded_status['kk'] === 'valid'): ?>
                            <i class="fas fa-check-circle icon-uploaded"></i>
                        <?php elseif ($uploaded_status['kk'] === 'tidak_valid'): ?>
                            <i class="fas fa-times-circle icon-invalid"></i>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half icon-uploading"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="upload-item-name">
                            Kartu Keluarga (KK)
                        </div>
                        <div class="upload-item-size">
                            <?php if (in_array('kk', $uploaded_docs)): ?>
                                <span><i class="fas fa-check me-1"></i>File terupload</span>
                                <?php if ($uploaded_status['kk'] == 'valid'): ?>
                                    <span class="status-badge badge-valid ms-2"><i class="fas fa-check-circle"></i> Valid</span>
                                <?php elseif ($uploaded_status['kk'] == 'tidak_valid'): ?>
                                    <span class="status-badge badge-invalid ms-2"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php else: ?>
                                    <span class="status-badge badge-verifying ms-2"><i class="fas fa-hourglass-half"></i> Diverifikasi</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94A3B8;">PDF, JPG, PNG (Max 2MB)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="kk" name="dokumen[kk]" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileLabel(this, 'kk-label');">
                    <button type="button" class="btn btn-sm <?php echo in_array('kk', $uploaded_docs) ? 'btn-success' : 'btn-outline-primary'; ?>" onclick="document.getElementById('kk').click();">
                        <i class="fas <?php echo in_array('kk', $uploaded_docs) ? 'fa-check' : 'fa-cloud-upload-alt'; ?> me-1"></i>
                        <span id="kk-label"><?php echo in_array('kk', $uploaded_docs) ? 'Terupload' : 'Pilih'; ?></span>
                    </button>
                </div>
                
                <!-- AKTA -->
                <div class="upload-item <?php echo in_array('akta', $uploaded_docs) ? (($uploaded_status['akta'] === 'tidak_valid') ? 'invalid' : 'uploaded') : ''; ?>">
                    <div class="status-icon">
                        <?php if (!in_array('akta', $uploaded_docs)): ?>
                            <i class="fas fa-file-alt icon-pending"></i>
                        <?php elseif ($uploaded_status['akta'] === 'valid'): ?>
                            <i class="fas fa-check-circle icon-uploaded"></i>
                        <?php elseif ($uploaded_status['akta'] === 'tidak_valid'): ?>
                            <i class="fas fa-times-circle icon-invalid"></i>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half icon-uploading"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="upload-item-name">
                            Akta Kelahiran
                        </div>
                        <div class="upload-item-size">
                            <?php if (in_array('akta', $uploaded_docs)): ?>
                                <span><i class="fas fa-check me-1"></i>File terupload</span>
                                <?php if ($uploaded_status['akta'] == 'valid'): ?>
                                    <span class="status-badge badge-valid ms-2"><i class="fas fa-check-circle"></i> Valid</span>
                                <?php elseif ($uploaded_status['akta'] == 'tidak_valid'): ?>
                                    <span class="status-badge badge-invalid ms-2"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php else: ?>
                                    <span class="status-badge badge-verifying ms-2"><i class="fas fa-hourglass-half"></i> Diverifikasi</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94A3B8;">PDF, JPG, PNG (Max 2MB)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="akta" name="dokumen[akta]" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileLabel(this, 'akta-label');">
                    <button type="button" class="btn btn-sm <?php echo in_array('akta', $uploaded_docs) ? 'btn-success' : 'btn-outline-primary'; ?>" onclick="document.getElementById('akta').click();">
                        <i class="fas <?php echo in_array('akta', $uploaded_docs) ? 'fa-check' : 'fa-cloud-upload-alt'; ?> me-1"></i>
                        <span id="akta-label"><?php echo in_array('akta', $uploaded_docs) ? 'Terupload' : 'Pilih'; ?></span>
                    </button>
                </div>
                
                <!-- IJAZAH -->
                <div class="upload-item <?php echo in_array('ijazah', $uploaded_docs) ? (($uploaded_status['ijazah'] === 'tidak_valid') ? 'invalid' : 'uploaded') : ''; ?>">
                    <div class="status-icon">
                        <?php if (!in_array('ijazah', $uploaded_docs)): ?>
                            <i class="fas fa-file-alt icon-pending"></i>
                        <?php elseif ($uploaded_status['ijazah'] === 'valid'): ?>
                            <i class="fas fa-check-circle icon-uploaded"></i>
                        <?php elseif ($uploaded_status['ijazah'] === 'tidak_valid'): ?>
                            <i class="fas fa-times-circle icon-invalid"></i>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half icon-uploading"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="upload-item-name">
                            Ijazah / SKL
                        </div>
                        <div class="upload-item-size">
                            <?php if (in_array('ijazah', $uploaded_docs)): ?>
                                <span><i class="fas fa-check me-1"></i>File terupload</span>
                                <?php if ($uploaded_status['ijazah'] == 'valid'): ?>
                                    <span class="status-badge badge-valid ms-2"><i class="fas fa-check-circle"></i> Valid</span>
                                <?php elseif ($uploaded_status['ijazah'] == 'tidak_valid'): ?>
                                    <span class="status-badge badge-invalid ms-2"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php else: ?>
                                    <span class="status-badge badge-verifying ms-2"><i class="fas fa-hourglass-half"></i> Diverifikasi</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94A3B8;">PDF, JPG, PNG (Max 2MB)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="ijazah" name="dokumen[ijazah]" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileLabel(this, 'ijazah-label');">
                    <button type="button" class="btn btn-sm <?php echo in_array('ijazah', $uploaded_docs) ? 'btn-success' : 'btn-outline-primary'; ?>" onclick="document.getElementById('ijazah').click();">
                        <i class="fas <?php echo in_array('ijazah', $uploaded_docs) ? 'fa-check' : 'fa-cloud-upload-alt'; ?> me-1"></i>
                        <span id="ijazah-label"><?php echo in_array('ijazah', $uploaded_docs) ? 'Terupload' : 'Pilih'; ?></span>
                    </button>
                </div>
                
                <!-- FOTO -->
                <div class="upload-item <?php echo in_array('foto', $uploaded_docs) ? (($uploaded_status['foto'] === 'tidak_valid') ? 'invalid' : 'uploaded') : ''; ?>">
                    <div class="status-icon">
                        <?php if (!in_array('foto', $uploaded_docs)): ?>
                            <i class="fas fa-image icon-pending"></i>
                        <?php elseif ($uploaded_status['foto'] === 'valid'): ?>
                            <i class="fas fa-check-circle icon-uploaded"></i>
                        <?php elseif ($uploaded_status['foto'] === 'tidak_valid'): ?>
                            <i class="fas fa-times-circle icon-invalid"></i>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half icon-uploading"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="upload-item-name">
                            Pas Foto (4x6)
                        </div>
                        <div class="upload-item-size">
                            <?php if (in_array('foto', $uploaded_docs)): ?>
                                <span><i class="fas fa-check me-1"></i>File terupload</span>
                                <?php if ($uploaded_status['foto'] == 'valid'): ?>
                                    <span class="status-badge badge-valid ms-2"><i class="fas fa-check-circle"></i> Valid</span>
                                <?php elseif ($uploaded_status['foto'] == 'tidak_valid'): ?>
                                    <span class="status-badge badge-invalid ms-2"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php else: ?>
                                    <span class="status-badge badge-verifying ms-2"><i class="fas fa-hourglass-half"></i> Diverifikasi</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94A3B8;">JPG, PNG (Max 2MB)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="foto" name="dokumen[foto]" accept=".jpg,.jpeg,.png" onchange="updateFileLabel(this, 'foto-label');">
                    <button type="button" class="btn btn-sm <?php echo in_array('foto', $uploaded_docs) ? 'btn-success' : 'btn-outline-primary'; ?>" onclick="document.getElementById('foto').click();">
                        <i class="fas <?php echo in_array('foto', $uploaded_docs) ? 'fa-check' : 'fa-cloud-upload-alt'; ?> me-1"></i>
                        <span id="foto-label"><?php echo in_array('foto', $uploaded_docs) ? 'Terupload' : 'Pilih'; ?></span>
                    </button>
                </div>
                
                <!-- RAPOR (Optional) -->
                <div class="upload-item <?php echo in_array('rapor', $uploaded_docs) ? (($uploaded_status['rapor'] === 'tidak_valid') ? 'invalid' : 'uploaded') : ''; ?>">
                    <div class="status-icon">
                        <?php if (!in_array('rapor', $uploaded_docs)): ?>
                            <i class="fas fa-file-alt icon-pending"></i>
                        <?php elseif ($uploaded_status['rapor'] === 'valid'): ?>
                            <i class="fas fa-check-circle icon-uploaded"></i>
                        <?php elseif ($uploaded_status['rapor'] === 'tidak_valid'): ?>
                            <i class="fas fa-times-circle icon-invalid"></i>
                        <?php else: ?>
                            <i class="fas fa-hourglass-half icon-uploading"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="upload-item-name">
                            Rapor (Opsional)
                        </div>
                        <div class="upload-item-size">
                            <?php if (in_array('rapor', $uploaded_docs)): ?>
                                <span><i class="fas fa-check me-1"></i>File terupload</span>
                                <?php if ($uploaded_status['rapor'] == 'valid'): ?>
                                    <span class="status-badge badge-valid ms-2"><i class="fas fa-check-circle"></i> Valid</span>
                                <?php elseif ($uploaded_status['rapor'] == 'tidak_valid'): ?>
                                    <span class="status-badge badge-invalid ms-2"><i class="fas fa-times-circle"></i> Ditolak</span>
                                <?php else: ?>
                                    <span class="status-badge badge-verifying ms-2"><i class="fas fa-hourglass-half"></i> Diverifikasi</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94A3B8;">PDF, JPG, PNG (Max 2MB)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="rapor" name="dokumen[rapor]" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileLabel(this, 'rapor-label');">
                    <button type="button" class="btn btn-sm <?php echo in_array('rapor', $uploaded_docs) ? 'btn-success' : 'btn-outline-primary'; ?>" onclick="document.getElementById('rapor').click();">
                        <i class="fas <?php echo in_array('rapor', $uploaded_docs) ? 'fa-check' : 'fa-cloud-upload-alt'; ?> me-1"></i>
                        <span id="rapor-label"><?php echo in_array('rapor', $uploaded_docs) ? 'Terupload' : 'Pilih'; ?></span>
                    </button>
                </div>
            </div>
            
            <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="margin: 0; color: #92400E; font-size: 13px;">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tips:</strong> Pastikan semua dokumen asli atau fotokopi yang jelas dan terbaca. 
                    Upload berhasil akan mengubah status Anda menjadi "Menunggu Verifikasi".
                </p>
            </div>
            
            <?php
            // Izinkan upload ulang saat: belum upload (menunggu_dokumen) ATAU ada dokumen yang ditolak
            // (menunggu_verifikasi dengan dokumen tidak_valid) — perbaiki deadlock dokumen ditolak
            $boleh_upload_ulang = ($pendaftar['status'] == 'menunggu_dokumen')
                || ($pendaftar['status'] == 'menunggu_verifikasi' && $invalid_docs > 0);
            ?>
            <?php if ($boleh_upload_ulang): ?>
            <button type="submit" class="btn-submit">
                <i class="fas fa-upload me-2"></i> Unggah Dokumen
            </button>
            <?php else: ?>
            <div style="background: #D4EDDA; border-left: 4px solid #10B981; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="margin: 0; color: #155724; font-size: 13px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Status dokumen Anda saat ini: <?php echo ucfirst(str_replace('_', ' ', $pendaftar['status'])); ?></strong><br>
                    Anda tidak perlu upload ulang. Tim admin sedang memverifikasi dokumen Anda.
                </p>
            </div>
            <?php endif; ?>
        </form>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center;">
            <p style="color: #4A5568; font-size: 14px; margin: 0;">
                <a href="/siakad/spmb/cek-status.php" style="color: #F09000; text-decoration: none; font-weight: 600;">
                    Cek Status Pendaftaran Anda
                </a>
            </p>
        </div>
        
        <?php elseif ($success): ?>
        
        <!-- SUCCESS MESSAGE -->
        <div class="success-box">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Upload Berhasil!</strong><br>
                    <?php echo $success; ?>
                </div>
            </div>
            <p style="margin: 0; font-size: 13px;">
                Anda akan menerima email notifikasi. Tim admin akan memverifikasi dokumen Anda dalam 3-5 hari kerja.
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
            <a href="/siakad/spmb/cek-status.php" class="btn btn-primary" style="background: #163A63; border: none;">
                <i class="fas fa-search me-2"></i> Cek Status
            </a>
            <a href="/siakad/spmb/index.php" class="btn btn-secondary">
                <i class="fas fa-home me-2"></i> Kembali ke SPMB
            </a>
        </div>
        
        <?php endif; ?>
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

    <!-- Alert System (modern modal + toast) -->
    <script src="/siakad/assets/js/alert.js?v=1.0"></script>

    <script>
// Function untuk update label file ketika file dipilih
function updateFileLabel(input, labelId) {
    const label = document.getElementById(labelId);
    const button = input.parentElement.querySelector('button');
    
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        
        // Update label text
        label.textContent = 'Siap Upload';
        
        // Change button style
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
        button.innerHTML = '<i class="fas fa-cloud-upload-alt me-1"></i> <span id="' + labelId + '">Siap Upload</span>';
        
        // Show file info temporarily
        const uploadItem = input.parentElement;
        const sizeDiv = uploadItem.querySelector('.upload-item-size');
        if (sizeDiv) {
            sizeDiv.innerHTML = '<small style="color: #0284C7;"><i class="fas fa-info-circle me-1"></i>' + fileName + ' (' + fileSize + 'MB)</small>';
        }
    }
}

// Animasi smooth scroll ke section
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

// Real-time validation untuk form upload
document.addEventListener('change', function(e) {
    if (e.target.type === 'file') {
        const file = e.target.files[0];
        if (file) {
            // Validasi ukuran file
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                siToast('warning', 'File terlalu besar! Maksimal 2MB.');
                e.target.value = '';
                return;
            }
            
            // Validasi tipe file
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                siToast('warning', 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF.');
                e.target.value = '';
                return;
            }
        }
    }
}, true);
</script>
</body>
</html>
