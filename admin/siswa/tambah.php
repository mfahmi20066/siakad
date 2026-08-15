<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tambah Siswa";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

$kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis       = mysqli_real_escape_string($koneksi, $_POST['nis']);
    // nis otomatis (NisGeneratorService) kalo field nis dikosongin
    if (trim($nis) === '') {
        require_once __DIR__ . '/../../config/database.php';
        $tahunMasuk = ($taTahun !== '') ? (int) explode('/', $taTahun)[0] : (int) date('Y');
        $nis = app_generate_nis_sementara($tahunMasuk);
    }
    $nama      = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jk        = $_POST['jenis_kelamin'];
    $ttl       = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl       = $_POST['tanggal_lahir'];
    $alamat    = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $hp        = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $nama_ortu = mysqli_real_escape_string($koneksi, trim($_POST['nama_ortu'] ?? ''));
    $hp_ortu   = mysqli_real_escape_string($koneksi, trim($_POST['no_hp_ortu'] ?? ''));
    $kelas_id  = $_POST['kelas_id'];
    $ta        = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $username  = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password  = hashPassword($_POST['password']);
    $foto_nama = '';

    // tentuin tahun_ajaran_id dari kelas terpilih (validasi relasional); jangan percaya post tahun_ajaran
    // Jangan percaya nilai $_POST['tahun_ajaran']. Kelas adalah sumber kebenaran relasi.
    $kls_id        = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    $taSiswaId     = null;
    $taSiswaTxt    = null;
    $taSiswaError  = null;

    if ($taId === null || $taTahun === '') {
        $taSiswaError = "Tidak ada tahun ajaran aktif. Tetapkan tahun ajaran aktif di Modul Tahun Ajaran.";
    } else {
        $taSiswaId  = $taId;
        $taSiswaTxt = $taTahun;
        if ($kls_id > 0) {
            $kR = mysqli_fetch_assoc(mysqli_query($koneksi,
                    "SELECT tahun_ajaran_id, tahun_ajaran FROM kelas WHERE id=$kls_id"));
            if ($kR) {
                if ($kR['tahun_ajaran_id'] !== null && (int)$kR['tahun_ajaran_id'] !== $taId) {
                    $taSiswaError = "Kelas terpilih bukan pada tahun ajaran aktif ($taTahun). Siswa aktif wajib di kelas tahun aktif.";
                } else {
                    $taSiswaId  = $kR['tahun_ajaran_id'] !== null ? (int)$kR['tahun_ajaran_id'] : $taId;
                    $taSiswaTxt = !empty($kR['tahun_ajaran']) ? $kR['tahun_ajaran'] : $taTahun;
                }
            }
        }
    }

    // upload foto
    if (!empty($_FILES['foto']['name'])) {
        $file      = $_FILES['foto'];
        $ekstensi  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allow     = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $folder    = __DIR__ . '/../../assets/img/foto_siswa/';
        if (!in_array($ekstensi, $allow)) {
            $error = "Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            $upload_gagal = true;
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "Ukuran foto maksimal 5 MB.";
            $upload_gagal = true;
        } else {
            $nama_file = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '-', basename($file['name']));
            if (!is_dir($folder)) mkdir($folder, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $folder . $nama_file)) {
                $foto_nama = $nama_file;
            }
        }
    }

    // cek username udah kepake atau belum
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Username sudah digunakan, silakan pilih yang lain!";
    } elseif (isset($upload_gagal)) {
        // biarkan $error dari validasi upload tampil
    } elseif ($taSiswaError !== null) {
        $error = $taSiswaError;
    } else {
        // simpan ke users dulu
        mysqli_query($koneksi, "INSERT INTO users (nama, username, password, role) 
                                VALUES ('$nama', '$username', '$password', 'siswa')");

        // simpan ke siswa (ga pake user_id biar ga error unknown column)
        mysqli_query($koneksi, "INSERT INTO siswa 
                                (nis, nama, nama_lengkap, jenis_kelamin, tempat_lahir, 
                                tanggal_lahir, alamat, no_hp, nama_ortu, no_hp_ortu, kelas_id, tahun_ajaran, tahun_ajaran_id, tahun_masuk, foto) 
                                VALUES 
                                ('$nis', '$nama', '$nama', '$jk', '$ttl', 
                                " . (!empty($tgl) ? "'$tgl'" : "NULL") . ", '$alamat', '$hp', " . (!empty($nama_ortu) ? "'$nama_ortu'" : "NULL") . ", " . (!empty($hp_ortu) ? "'$hp_ortu'" : "NULL") . ", '$kls_id', '$taSiswaTxt', '$taSiswaId', " . ($taSiswaTxt ? "'" . (int) explode('/', $taSiswaTxt)[0] . "'" : "NULL") . ", '$foto_nama')");

        // hubungkan akun users ke siswa via id_ref (pola sama kayak guru)
        $siswa_id_baru = mysqli_insert_id($koneksi);
        if ($siswa_id_baru) {
            mysqli_query($koneksi, "UPDATE users SET id_ref='$siswa_id_baru' 
                                    WHERE username='$username' AND role='siswa'");
        }

        // notif ke admin ada siswa baru
        if (!function_exists('notifikasi_ke_role')) {
            include __DIR__ . '/../../includes/notifikasi_functions.php';
        }
        notifikasi_ke_role($koneksi, 'admin', 'Data siswa baru',
            "Siswa baru bernama '$nama' (NIS: $nis) telah ditambahkan.",
            '/siakad/admin/siswa/index.php');

        header("Location: index.php?success=Data siswa berhasil ditambahkan");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-user-plus text-icon me-2"></i>Tambah Siswa</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-wpforms"></i> Form Tambah Siswa
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">
                            <i class="fas fa-user"></i> Data Pribadi
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control"
                                   placeholder="Nomor Induk Siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control"
                                   placeholder="Nama lengkap siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                   placeholder="Kota tempat lahir">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"
                                      placeholder="Alamat lengkap"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <input type="text" name="nama_ortu" class="form-control"
                                   placeholder="Nama orang tua/wali siswa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP Orang Tua / Wali</label>
                            <input type="text" name="no_hp_ortu" class="form-control"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 mt-4 fw-bold">
                            <i class="fas fa-school"></i> Data Akademik
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select">
                                <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                                <option value="<?= $k['id'] ?>">
                                    <?= $k['nama_kelas'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control"
                                   value="<?= e($taTahun) ?>" placeholder="<?= e($taTahun) ?>" readonly>
                            <small class="text-muted">Otomatis mengikuti tahun ajaran aktif dan/atau kelas terpilih.</small>
                        </div>

                        <h6 class="text-muted mb-3 mt-4 fw-bold">
                            <i class="fas fa-key"></i> Akun Login Siswa
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Username untuk login" required>
                            <small class="text-muted">Contoh: ahmad.siswa</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Password login" required>
                        </div>

                        <h6 class="text-muted mb-3 mt-4 fw-bold">
                            <i class="fas fa-camera"></i> Foto Profil (opsional)
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <input type="file" name="foto" class="form-control"
                                   accept="image/*">
                            <small class="text-muted">Format: JPG/PNG/GIF/WEBP, maks. 5 MB.</small>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>