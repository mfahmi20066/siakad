<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Edit Siswa";

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM siswa WHERE id='$id'"));
$kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");

if (!$data) {
    header("Location: index.php?error=Data siswa tidak ditemukan");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis    = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jk     = $_POST['jenis_kelamin'];
    $ttl    = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl    = !empty(trim($_POST['tanggal_lahir'] ?? ''))
              ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']) . "'"
              : "NULL";
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $nama_ortu = mysqli_real_escape_string($koneksi, trim($_POST['nama_ortu'] ?? ''));
    $hp_ortu   = mysqli_real_escape_string($koneksi, trim($_POST['no_hp_ortu'] ?? ''));
    $kid    = $_POST['kelas_id'];
    $ta     = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);

    // email: validasi format + cek duplikat di users
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Format email tidak valid!";
    } elseif (!empty($email)) {
        $cek_email = mysqli_fetch_row(mysqli_query($koneksi,
            "SELECT COUNT(*) FROM users WHERE email='$email'
             AND NOT (id_ref='$id' AND role='siswa')
             AND NOT (id='$id' AND role='siswa')"))[0];
        if ($cek_email > 0) {
            $emailError = "Email sudah digunakan oleh pengguna lain!";
        }
    }

    // tentuin tahun_ajaran_id dari kelas terpilih (validasi relasional); jangan percaya nilai tahun dari post
    // Jangan percaya nilai tahun dari POST; kelas adalah sumber kebenaran relasi.
    $aktifId = null;
    try { $aktifId = (int) getTahunAjaranAktif(tahun_ajaran_pdo())['id']; }
    catch (Throwable $e) { $aktifId = null; }

    $kidEdit    = !empty($kid) ? (int)$kid : 0;
    $taSiswaId  = null;
    $taSiswaTxt = $data['tahun_ajaran'] ?? null;
    $kelasError = null;

    if ($kidEdit > 0) {
        $kR = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT tahun_ajaran_id, tahun_ajaran FROM kelas WHERE id=$kidEdit"));
        if ($kR) {
            $taSiswaId  = $kR['tahun_ajaran_id'] !== null ? (int)$kR['tahun_ajaran_id'] : null;
            if (!empty($kR['tahun_ajaran'])) $taSiswaTxt = $kR['tahun_ajaran'];
            if ($aktifId !== null && $taSiswaId !== null && $taSiswaId !== $aktifId) {
                $kelasError = 'Kelas terpilih bukan pada tahun ajaran aktif. Siswa aktif wajib di kelas tahun aktif.';
            }
        }
    }
    if ($taSiswaId === null) {
        $taSiswaId  = $data['tahun_ajaran_id'] ?? null;
        if ($taSiswaTxt === null) $taSiswaTxt = $data['tahun_ajaran'] ?? '';
    }

    // upload / hapus foto
    $foto_update = $data['foto'] ?? '';
    $folder_siswa = __DIR__ . '/../../assets/img/foto_siswa/';

    if (!empty($_FILES['foto']['name'])) {
        $file = $_FILES['foto'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allow    = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ekstensi, $allow)) {
            $upload_error = "Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $upload_error = "Ukuran foto maksimal 5 MB.";
        } else {
            $nama_file = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '-', basename($file['name']));
            if (!is_dir($folder_siswa)) mkdir($folder_siswa, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $folder_siswa . $nama_file)) {
                if (!empty($foto_update) && file_exists($folder_siswa . $foto_update)) {
                    unlink($folder_siswa . $foto_update);
                }
                $foto_update = $nama_file;
            }
        }
    } elseif (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
        if (!empty($foto_update) && file_exists($folder_siswa . $foto_update)) {
            unlink($folder_siswa . $foto_update);
        }
        $foto_update = '';
    }

    // update tabel siswa
    $taSiswaIdSql = ($taSiswaId !== null && $taSiswaId !== '') ? "'".(int)$taSiswaId."'" : 'NULL';
    $taSiswaTxtSql = "'".mysqli_real_escape_string($koneksi, $taSiswaTxt)."'";

    if ($kelasError !== null || !empty($emailError)) {
        $error = $kelasError ?? $emailError;
    } else {
        $emailSql = $email !== '' ? "'$email'" : "NULL";
        mysqli_query($koneksi, "UPDATE siswa 
                                SET nis='$nis', nama='$nama', nama_lengkap='$nama', jenis_kelamin='$jk',
                                    tempat_lahir='$ttl', tanggal_lahir=$tgl,
                                    alamat='$alamat', no_hp='$hp', email=$emailSql,
                                    nama_ortu=" . (!empty($nama_ortu) ? "'$nama_ortu'" : "NULL") . ",
                                    no_hp_ortu=" . (!empty($hp_ortu) ? "'$hp_ortu'" : "NULL") . ",
                                    kelas_id='$kidEdit', tahun_ajaran=$taSiswaTxtSql, 
                                    tahun_ajaran_id=$taSiswaIdSql, foto='$foto_update'
                                WHERE id='$id'");

        // sinkron nama & email ke users (akun terhubung via id_ref)
        mysqli_query($koneksi, "UPDATE users SET nama='$nama', email=$emailSql 
                                WHERE id_ref='$id' AND role='siswa'");
        mysqli_query($koneksi, "UPDATE users SET nama='$nama', email=$emailSql 
                                WHERE id='$id' AND role='siswa'");

        // update password cuma kalo diisi
        if (!empty($_POST['password'])) {
            $pass = hashPassword($_POST['password']);
            mysqli_query($koneksi, "UPDATE users SET password='$pass' 
                                    WHERE id_ref='$id' AND role='siswa'");
            mysqli_query($koneksi, "UPDATE users SET password='$pass' 
                                    WHERE id='$id' AND role='siswa'");
        }

        header("Location: index.php?success=Data siswa berhasil diupdate");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Siswa</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">Form Edit Siswa</div>
        <div class="card-body">
            <?php if (isset($upload_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $upload_error ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">

                    <!-- Kolom Kiri -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Data Pribadi</h6>
                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control"
                                   value="<?= $data['nis'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control"
                                   value="<?= e($data['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L" <?= $data['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>
                                    Laki-laki
                                </option>
                                <option value="P" <?= $data['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>
                                    Perempuan
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                   value="<?= e($data['tempat_lahir'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control"
                                   value="<?= $data['tanggal_lahir'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"><?= e($data['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control"
                                   value="<?= e($data['no_hp'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($data['email'] ?? '') ?>"
                                   placeholder="nama@email.com">
                            <small class="text-muted">Tersinkron otomatis dengan akun login siswa.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <input type="text" name="nama_ortu" class="form-control"
                                   value="<?= e($data['nama_ortu'] ?? '') ?>"
                                   placeholder="Nama orang tua/wali siswa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP Orang Tua / Wali</label>
                            <input type="text" name="no_hp_ortu" class="form-control"
                                   value="<?= e($data['no_hp_ortu'] ?? '') ?>"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Data Akademik</h6>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select">
                                <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>>
                                    <?= $k['nama_kelas'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control"
                                   value="<?= e($data['tahun_ajaran'] ?? '') ?>" readonly>
                        </div>

                        <h6 class="text-muted mb-3 mt-4 fw-bold">Foto Profil</h6>

                        <?php
                        $foto_siswa  = $data['foto'] ?? '';
                        $foto_siswa_src = (!empty($foto_siswa) && file_exists(__DIR__ . '/../../assets/img/foto_siswa/' . $foto_siswa))
                            ? '/siakad/assets/img/foto_siswa/' . $foto_siswa
                            : '/siakad/assets/img/default-avatar.png';
                        ?>

                        <div class="foto-dropzone mb-1" id="dropzoneFoto">
                            <div class="dz-preview">
                                <img src="<?= $foto_siswa_src ?>" id="previewFoto" alt="Foto Siswa">
                                <div class="dz-icon"><i class="fas fa-camera"></i></div>
                                <div class="dz-text">Seret & lepas foto di sini<br>
                                    <span>atau klik untuk memilih file (maks. 5 MB)</span>
                                </div>
                            </div>
                            <input type="file" name="foto" id="inputFoto" accept="image/*" hidden>
                        </div>
                                <div class="d-flex gap-2 mt-2">
                                    <label class="btn btn-sm btn-outline-primary flex-fill text-center" for="inputFoto">
                                        <i class="fas fa-upload me-1"></i>Pilih Foto
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-fill" id="btnHapusFoto">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                                <input type="hidden" name="hapus_foto" id="hapus_foto" value="">

                        <h6 class="text-muted mb-3 mt-4 fw-bold">Ganti Password</h6>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="Kosongkan jika tidak ingin mengubah password">
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var dz     = document.getElementById('dropzoneFoto');
    var input  = document.getElementById('inputFoto');
    var img    = document.getElementById('previewFoto');
    var inputHapus = document.getElementById('hapus_foto');

    if (!dz || !input) return;

    dz.addEventListener('click', function (e) { input.click(); });
    dz.addEventListener('dragover', function (e) { e.preventDefault(); dz.classList.add('dragover'); });
    dz.addEventListener('dragleave', function () { dz.classList.remove('dragover'); });
    dz.addEventListener('drop', function (e) {
        e.preventDefault();
        dz.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            tampilkanPreview(e.dataTransfer.files[0]);
        }
    });
    input.addEventListener('change', function () {
        if (this.files.length) tampilkanPreview(this.files[0]);
    });
    document.getElementById('btnHapusFoto').addEventListener('click', function () {
        inputHapus.value = '1';
        img.src = '/siakad/assets/img/default-avatar.png';
    });

    function tampilkanPreview(file) {
        if (!file.type.match('image.*')) return;
        var reader = new FileReader();
        reader.onload = function (e) { img.src = e.target.result; };
        reader.readAsDataURL(file);
    }
});
</script>