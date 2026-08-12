<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
cekAdmin();
$title = "Tambah Guru";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip    = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jk     = $_POST['jenis_kelamin'];
    $ttl    = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl    = $_POST['tanggal_lahir'];
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $uname  = mysqli_real_escape_string($koneksi, $_POST['username']);
    $pass   = hashPassword($_POST['password']);

    // â”€â”€ Upload Foto â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    $foto_nama = '';
    if (!empty($_FILES['foto']['name'])) {
        $file      = $_FILES['foto'];
        $ekstensi  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allow     = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $folder    = __DIR__ . '/../../assets/img/foto_guru/';
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

    // Mapping guru: mata pelajaran (wali kelas dipindah ke halaman tambah mata pelajaran)
    $mapel_ids = isset($_POST['mapel_ids']) ? (array) $_POST['mapel_ids'] : [];
    $kelas_id  = 0;


    // sanitasi id mapel
    $mapel_ids_sanitized = [];
    foreach ($mapel_ids as $mid) {
        $mid = (int) $mid;
        if ($mid > 0) $mapel_ids_sanitized[] = $mid;
    }


    // 1. Cek apakah username sudah dipakai di tabel users
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$uname'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Username sudah digunakan, silakan pilih yang lain!";
    } elseif (isset($upload_gagal)) {
        // Biarkan $error dari validasi upload tampil
    } else {
        // 2. ALUR YANG BENAR: Simpan ke tabel guru terlebih dahulu (Tanpa kolom user_id)
        $query_guru = "INSERT INTO guru (nip, nama, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, foto)
                       VALUES ('$nip', '$nama', '$nama', '$jk', '$ttl', " . (!empty($tgl) ? "'$tgl'" : "NULL") . ", '$alamat', '$hp', '$foto_nama')";
        
        if (mysqli_query($koneksi, $query_guru)) {
            // Ambil ID guru yang baru saja didapatkan dari perintah di atas
            $guru_id = mysqli_insert_id($koneksi);

            // 3. Simpan ke tabel users menggunakan id_ref sebagai penghubung akun gurunya
            mysqli_query($koneksi, "INSERT INTO users (username, password, nama, role, id_ref, status)
                                    VALUES ('$uname', '$pass', '$nama', 'guru', '$guru_id', 'aktif')");

            // Update tugas mengajar (mapel) & wali kelas
            // Mata pelajaran diampu -> mata_pelajaran.guru_id
            if (!empty($mapel_ids_sanitized)) {
                $in = implode(',', $mapel_ids_sanitized);
                mysqli_query($koneksi, "UPDATE mata_pelajaran SET guru_id='$guru_id' WHERE id IN ($in)");
            }



            // Notifikasi ke admin bahwa ada data guru baru
            if (!function_exists('notifikasi_ke_role')) {
                include __DIR__ . '/../../includes/notifikasi_functions.php';
            }
            notifikasi_ke_role($koneksi, 'admin', 'Data guru baru',
                "Guru baru bernama '$nama' (NIP: $nip) telah ditambahkan.",
                '/siakad/admin/guru/index.php');

            header("Location: index.php?success=Data guru berhasil ditambahkan");
            exit();
        } else {
            $error = "Gagal menyimpan data guru: " . mysqli_error($koneksi);
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-user-plus text-icon me-2"></i>Tambah Guru</h4>
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
            <i class="fas fa-wpforms"></i> Form Tambah Guru
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">
                            <i class="fas fa-user"></i> Data Pribadi
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">NIP <span class="text-danger">*</span></label>
                            <input type="text" name="nip" class="form-control"
                                   placeholder="Nomor Induk Pegawai" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control"
                                   placeholder="Nama lengkap guru" required>
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
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">
                            <i class="fas fa-key"></i> Akun Login Guru
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Username untuk login" required>
                            <small class="text-muted">Contoh: budi.guru</small>
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

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle"></i>
                            <strong>Informasi:</strong> Username dan password ini
                            akan digunakan guru untuk login ke sistem.
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row g-3">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3 fw-bold">
                            <i class="fas fa-chalkboard-teacher"></i> Tugas Mengajar
                        </h6>

                        <div class="mb-3">
                            <label class="form-label d-block">Mata Pelajaran yang diampu</label>

                            <div class="mapel-checkbox-grid">
                                <?php
                                $mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");
                                while ($m = mysqli_fetch_assoc($mapel_list)):
                                ?>
                                <label class="mapel-checkbox">
                                    <input type="checkbox" name="mapel_ids[]" value="<?= (int)$m['id'] ?>">
                                    <span class="mapel-check">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <span class="mapel-name"><?= e($m['nama_mapel']) ?></span>
                                </label>
                                <?php endwhile; ?>
                            </div>
                            <small class="text-muted">Centang mata pelajaran yang diampu oleh guru.</small>
                        </div>


                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Data Guru
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>