<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/mailer.php';

cekAdmin();

$title = "Pengumuman SPMB";

// ta aktif (dinamis dari master, bukan hardcode)
include '../../config/helper_tahun_ajaran.php';
include '../../config/database.php';
try { $taSpmb = getTahunAjaranAktif(tahun_ajaran_pdo()); $ta_spmb = $taSpmb['tahun']; $taId_spmb = (int)$taSpmb['id']; }
catch (Throwable $e) { $ta_spmb = date('Y') . '/' . (date('Y') + 1); $taId_spmb = 0; }

// cek pengumuman aktif
$query_setting = mysqli_query($koneksi, "SELECT spmb_pengumuman_aktif FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);
$pengumuman_aktif = $setting['spmb_pengumuman_aktif'] ?? 0;

// proses finalize
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalize'])) {
    $pendaftar_ids = $_POST['pendaftar_id'] ?? [];
    $action = $_POST['action'] ?? '';
    
    if (empty($pendaftar_ids)) {
        $error = "Pilih pendaftar terlebih dahulu!";
    } else {
        $processed = 0;
        $errors = [];
        
        foreach ($pendaftar_ids as $id) {
            $id = (int)$id;
            
            // ambil data pendaftar
            $query = mysqli_query($koneksi, "SELECT * FROM spmb_pendaftar WHERE id=$id");
            $pendaftar = mysqli_fetch_assoc($query);
            
            if (!$pendaftar) continue;
            
            // cegah state parsial: email pendaftar ga boleh kepake akun lain (users.email unique, insert bakal gagal diam-diam)
            if ($action == 'diterima' && !empty($pendaftar['email'])) {
                $email_cek = mysqli_real_escape_string($koneksi, $pendaftar['email']);
                $q_dup = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email_cek'");
                if ($q_dup && mysqli_num_rows($q_dup) > 0) {
                    $errors[] = "{$pendaftar['nama_lengkap']} (No. {$pendaftar['no_pendaftaran']}): email {$pendaftar['email']} sudah digunakan akun SIAKAD lain — akun tidak dibuat.";
                    continue;
                }
            }
            $status_baru = ($action == 'diterima') ? 'diterima' : 'ditolak';
            $update = mysqli_query($koneksi, "UPDATE spmb_pendaftar SET status='$status_baru' WHERE id=$id");
            
            if ($update) {
                $processed++;
                
                if ($action == 'diterima' && $pendaftar['email']) {
                    // generate username & password
                    $username = 'spmb' . date('Y') . $id;
                    $password = 'SiaSPMB' . rand(1000, 9999);
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // cek username unique
                    $cek_username = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username'");
                    if (mysqli_num_rows($cek_username) > 0) {
                        $username = 'spmb' . date('Y') . $id . rand(1, 99);
                    }
                    
                    // insert ke users
                    $insert_user = mysqli_query($koneksi, "INSERT INTO users 
                        (username, password, nama, role, status, email, wajib_ganti_password, created_at)
                        VALUES ('$username', '$password_hash', '{$pendaftar['nama_lengkap']}', 'siswa', 'aktif', '{$pendaftar['email']}', 1, NOW())");
                    
                    if ($insert_user) {
                        $user_id = mysqli_insert_id($koneksi);
                        
                        // update pendaftar pake user_id_hasil
                        mysqli_query($koneksi, "UPDATE spmb_pendaftar SET user_id_hasil=$user_id WHERE id=$id");
                        
                        // insert ke siswa: kelas belum ditentukan (NULL), ta = tahun aktif (source of truth), nis otomatis dari NisGeneratorService
                        $tahunMasuk = ($ta_spmb !== '') ? (int) explode('/', $ta_spmb)[0] : (int) date('Y');
                        $nis_spmb = app_generate_nis_sementara($tahunMasuk);
                        $insert_siswa = mysqli_query($koneksi, "INSERT INTO siswa 
                            (nis, nama, nama_lengkap, nisn, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, email, no_hp, nama_ortu, no_hp_ortu, tahun_ajaran, tahun_ajaran_id, tahun_masuk, foto, created_at)
                            VALUES (
                                '$nis_spmb',
                                '{$pendaftar['nama_lengkap']}', 
                                '{$pendaftar['nama_lengkap']}', 
                                '{$pendaftar['nisn']}', 
                                '{$pendaftar['jenis_kelamin']}', 
                                '{$pendaftar['tempat_lahir']}', 
                                '{$pendaftar['tanggal_lahir']}', 
                                '{$pendaftar['alamat']}', 
                                '{$pendaftar['email']}', 
                                '{$pendaftar['no_hp_ortu']}', 
                                " . (!empty($pendaftar['nama_ortu']) ? "'" . mysqli_real_escape_string($koneksi, $pendaftar['nama_ortu']) . "'" : "NULL") . ",
                                " . (!empty($pendaftar['no_hp_ortu']) ? "'" . mysqli_real_escape_string($koneksi, $pendaftar['no_hp_ortu']) . "'" : "NULL") . ",
                                '$ta_spmb',
                                '$taId_spmb',
                                $tahunMasuk,
                                NULL, 
                                NOW()
                            )");
                    
                    if ($insert_siswa) {
                        // hubungkan akun users ke data siswa via id_ref (pola sama kayak akun siswa dari form admin)
                        $siswa_id = mysqli_insert_id($koneksi);
                        mysqli_query($koneksi, "UPDATE users SET id_ref=$siswa_id WHERE id=$user_id AND role='siswa'");
                    }
                    }
                    
                    // kirim email
                    $subject = "Selamat! Anda Diterima di SMA Negeri 4 Palopo";
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    $body = "
                    Halo {$pendaftar['nama_lengkap']},<br><br>
                    
                    <strong>Selamat!</strong> Anda telah diterima sebagai calon siswa SMA Negeri 4 Palopo untuk tahun ajaran $ta_spmb.<br><br>
                    
                    <strong>Login ke SIAKAD:</strong><br>
                    URL: $base_url/siakad/auth/login.php<br>
                    Username: $username<br>
                    Password: $password<br><br>
                    
                    <strong>PENTING:</strong> Anda wajib mengganti password saat login pertama kali.<br><br>
                    
                    <strong>Langkah Selanjutnya:</strong><br>
                    1. Login ke SIAKAD dengan kredensial di atas<br>
                    2. Cek data diri Anda<br>
                    3. Tunggu informasi selanjutnya dari sekolah<br><br>
                    
                    Terima kasih,<br>
                    Tim SPMB SMA Negeri 4 Palopo
                    ";
                    
                    try {
                        kirimEmail($pendaftar['email'], $subject, $body);
                    } catch (\RuntimeException $e) {
                        error_log("[SPMB Pengumuman] Gagal kirim email lolos ke {$pendaftar['email']}: " . $e->getMessage());
                    }
                } elseif ($action == 'ditolak' && $pendaftar['email']) {
                    // kirim email ditolak
                    $subject = "Hasil Seleksi SPMB - SMA Negeri 4 Palopo";
                    $body = "
                    Halo {$pendaftar['nama_lengkap']},<br><br>
                    
                    Terima kasih telah mendaftar SPMB SMA Negeri 4 Palopo.<br><br>
                    
                    <strong>Kami mohon maaf,</strong> pendaftaran Anda tidak lolos dalam seleksi tahun ini.<br><br>
                    
                    Semoga keberuntungan menyertai Anda di kesempatan berikutnya.<br><br>
                    
                    Terima kasih,<br>
                    Tim SPMB SMA Negeri 4 Palopo
                    ";
                    
                    try {
                        kirimEmail($pendaftar['email'], $subject, $body);
                    } catch (\RuntimeException $e) {
                        error_log("[SPMB Pengumuman] Gagal kirim email ditolak ke {$pendaftar['email']}: " . $e->getMessage());
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
        
        if ($processed > 0) {
            $success = "$processed pendaftar berhasil diproses. Email notifikasi telah dikirim.";
        }
    }
}

// Query pendaftar yang lolos seleksi (belum diumumkan)
$query = "SELECT sp.*, sj.nama_jalur 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          WHERE sp.status='lolos_seleksi'
          ORDER BY sj.nama_jalur, sp.nama_lengkap";
$data = mysqli_query($koneksi, $query);
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-bullhorn text-icon me-2"></i>Pengumuman SPMB</h4>
    </div>

    <?php if ($pengumuman_aktif != 1): ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Pengumuman belum aktif.</strong> Silakan aktifkan di <a href="index.php">Pengaturan SPMB</a>.
    </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Pendaftar Lolos Seleksi</span>
                <span class="badge bg-info ms-2">
                    <?php echo $data ? mysqli_num_rows($data) : 0; ?> pendaftar
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select_all" class="select_all">
                            </th>
                            <th>No.</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Jalur</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($data && mysqli_num_rows($data) > 0):
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($data)):
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="pendaftar_id[]" value="<?php echo $row['id']; ?>" class="pendaftar_checkbox">
                            </td>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo e($row['no_pendaftaran']); ?></strong></td>
                            <td><?php echo e($row['nama_lengkap']); ?></td>
                            <td><?php echo e($row['nama_jalur']); ?></td>
                            <td><span class="badge bg-info">Lolos Seleksi</span></td>
                            <td>
                                <select class="form-select form-select-sm" style="width: auto;" data-id="<?php echo $row['id']; ?>">
                                    <option value="">Pilih</option>
                                    <option value="diterima">Diterima</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state py-5">
                                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                    <p>Belum ada pendaftar yang lolos seleksi.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <span><i class="fas fa-check-double"></i> Finalisasi Pengumuman</span>
        </div>
        <div class="card-body">
            <p class="text-muted">Pilih pendaftar yang akan diumumkan dan tentukan statusnya.</p>
            
            <button type="button" class="btn btn-success" onclick="finalizeSelection('diterima')">
                <i class="fas fa-check-circle me-2"></i> Terima Semua Pendaftar Terpilih
            </button>
            <button type="button" class="btn btn-danger" onclick="finalizeSelection('ditolak')">
                <i class="fas fa-times-circle me-2"></i> Tolak Semua Pendaftar Terpilih
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
        </div>
    </div>
</div>

<script>
function finalizeSelection(action) {
    const checkboxes = document.querySelectorAll('.pendaftar_checkbox:checked');
    
    if (checkboxes.length === 0) {
        siToast('warning', 'Pilih pendaftar yang akan diproses!');
        return;
    }
    
    siConfirm({
        icon: 'question',
        title: 'Apakah Anda yakin ingin ' + (action === 'diterima' ? 'menerima' : 'menolak') + ' ' + checkboxes.length + ' pendaftar?',
        confirmText: action === 'diterima' ? 'Ya, Terima' : 'Ya, Tolak',
        danger: action !== 'diterima'
    }).then(function (ok) {
        if (!ok) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'pendaftar_id[]';
            input.value = cb.value;
            form.appendChild(input);
        });
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = action;
        form.appendChild(inputAction);
        
        const inputFinalize = document.createElement('input');
        inputFinalize.type = 'hidden';
        inputFinalize.name = 'finalize';
        inputFinalize.value = '1';
        form.appendChild(inputFinalize);
        
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) {
            const t = document.createElement('input');
            t.type = 'hidden';
            t.name = 'csrf_token';
            t.value = meta.getAttribute('content');
            form.appendChild(t);
        }
        
        document.body.appendChild(form);
        form.submit();
    });
}

// Handle select all
document.getElementById('select_all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.pendaftar_checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include '../../includes/footer.php'; ?>
