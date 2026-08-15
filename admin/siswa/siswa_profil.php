<?php
require_once '../../config/session.php';
require_once '../../config/koneksi.php';
require_once '../../config/helper_auth.php';
require_once '../../config/helper_tahun_ajaran.php';
cekSiswa();

try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taTahun = date('Y') . '/' . (date('Y') + 1); }

$id_siswa = $_SESSION['id_ref']; // id siswa dari session
$pesan    = '';
$error    = '';

// ambil data siswa
$query = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas 
                               FROM siswa s 
                               LEFT JOIN kelas k ON s.kelas_id = k.id
                               WHERE s.id = '$id_siswa'");
$data  = mysqli_fetch_assoc($query);

// proses upload foto
if (isset($_POST['simpan_foto']) && !empty($_FILES['foto']['name'])) {
    $file    = $_FILES['foto'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        $error = "Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.";
    } elseif ($file['size'] > 2048000) {
        $error = "Ukuran file terlalu besar. Maksimal 2MB.";
    } else {
        // hapus foto lama kalo ada
        if (!empty($data['foto'])) {
            $old = __DIR__ . "/../../assets/img/foto_siswa/" . $data['foto'];
            if (file_exists($old)) unlink($old);
        }

        $nama_file = "siswa_" . $id_siswa . "_" . time() . "." . $ext;
        $tujuan    = __DIR__ . "/../../assets/img/foto_siswa/" . $nama_file;

        // pastikan folder ada
        if (!is_dir(__DIR__ . "/../../assets/img/foto_siswa/")) {
            mkdir(__DIR__ . "/../../assets/img/foto_siswa/", 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $tujuan)) {
            mysqli_query($koneksi, "UPDATE siswa SET foto='$nama_file' WHERE id='$id_siswa'");
            $_SESSION['foto'] = $nama_file;
            $pesan = "Foto profil berhasil diperbarui.";
            // refresh data
            $query = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas 
                                          FROM siswa s 
                                          LEFT JOIN kelas k ON s.kelas_id = k.id
                                          WHERE s.id = '$id_siswa'");
            $data  = mysqli_fetch_assoc($query);
        } else {
            $error = "Gagal mengupload foto. Silakan coba lagi.";
        }
    }
}

// proses update kontak
if (isset($_POST['simpan_kontak'])) {
    $no_hp  = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    mysqli_query($koneksi, "UPDATE siswa SET no_hp='$no_hp', alamat='$alamat' WHERE id='$id_siswa'");
    $pesan = "Data kontak berhasil diperbarui.";
}

// proses ganti password
if (isset($_POST['simpan_password'])) {
    $pass_baru   = $_POST['password_baru'];
    $pass_konfirm = $_POST['password_konfirm'];

    if ($pass_baru !== $pass_konfirm) {
        $error = "Password baru dan konfirmasi password tidak cocok.";
    } elseif (strlen($pass_baru) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $hash = hashPassword($pass_baru);
        // update di tabel users
        $id_user = $_SESSION['user_id'];
        mysqli_query($koneksi, "UPDATE users SET password='$hash' WHERE id='$id_user'");
        $pesan = "Password berhasil diperbarui.";
    }
}

// path foto
$foto_file = $data['foto'] ?? '';
$foto_src  = (!empty($foto_file) && file_exists(__DIR__ . "/../../assets/img/foto_siswa/" . $foto_file))
             ? "/siakad/assets/img/foto_siswa/" . $foto_file
             : "/siakad/assets/img/default-avatar.png";

$title = "Profil Saya";
$icon  = "fa-user";
require_once '../../includes/header.php';
?>

<?php require_once '../../includes/sidebar_siswa.php'; ?>
        <?php include '../../includes/topbar_siswa.php'; ?>


<div class="main-content">
    <div class="page-header">
    <h4><i class="fas fa-user text-icon me-2"></i>Profil Saya</h4>
  </div>
  <div class="container-fluid">

    <?php if ($pesan): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= $pesan ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- â”€â”€ Kartu Foto & Info Singkat â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body text-center py-4">

            <!-- Foto Profil -->
            <div class="position-relative d-inline-block mb-3">
              <img src="<?= $foto_src ?>"
                   id="previewFoto"
                   class="rounded-circle border border-3 border-primary shadow"
                   style="width:130px; height:130px; object-fit:cover;">
              <!-- Tombol overlay ganti foto -->
              <label for="inputFoto"
                     class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                     style="width:32px; height:32px; cursor:pointer;"
                     title="Ganti Foto">
                <i class="fas fa-camera" style="font-size:13px;"></i>
              </label>
            </div>

            <h5 class="fw-bold mb-1"><?= e($data['nama_lengkap']) ?></h5>
            <p class="text-muted mb-2" style="font-size:13px;">
              NIS: <strong><?= e($data['nis']) ?></strong>
            </p>
            <span class="badge bg-info me-1"><?= e($data['nama_kelas'] ?? '-') ?></span>
            <span class="badge bg-primary">Siswa</span>

            <hr>

            <!-- Info Detail -->
            <table class="table table-sm text-start" style="font-size:13px;">
              <tr>
                <td class="fw-semibold text-muted">Jenis Kelamin</td>
                <td>: <?= e($data['jenis_kelamin'] ?? '-') ?></td>
              </tr>
              <tr>
                <td class="fw-semibold text-muted">Tempat Lahir</td>
                <td>: <?= e($data['tempat_lahir'] ?? '-') ?></td>
              </tr>
              <tr>
                <td class="fw-semibold text-muted">Tanggal Lahir</td>
                <td>: <?= !empty($data['tanggal_lahir']) ? tanggal_indo($data['tanggal_lahir']) : '-' ?></td>
              </tr>
              <tr>
                <td class="fw-semibold text-muted">Tahun Ajaran</td>
                <td>: <?= e($data['tahun_ajaran'] ?? $taTahun) ?></td>
              </tr>
              <tr>
                <td class="fw-semibold text-muted">Username</td>
                <td>: <code><?= e($_SESSION['username'] ?? '-') ?></code></td>
              </tr>
            </table>

            <!-- Form Upload Foto (hidden input, trigger dari label kamera) -->
            <form method="POST" enctype="multipart/form-data" id="formFoto">
              <input type="file" name="foto" id="inputFoto"
                     accept="image/jpg, image/jpeg, image/png, image/webp"
                     hidden onchange="previewDanSubmit(this)">
              <input type="hidden" name="simpan_foto" value="1">
            </form>

            <!-- Info size -->
            <small class="text-muted d-block mt-2">
              <i class="fas fa-info-circle"></i>
              Klik ikon kamera untuk mengganti foto.<br>
              Format: JPG/PNG/WEBP, Maks. 2MB.
            </small>

          </div>
        </div>
      </div>

      <!-- â”€â”€ Kartu Update Info â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
      <div class="col-md-8">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-white fw-bold">
            <i class="fas fa-edit text-primary me-2"></i>Update Informasi
          </div>
          <div class="card-body">

            <!-- Data Pribadi (read-only) -->
            <h6 class="fw-bold mb-3">Data Pribadi</h6>
            <div class="row g-2 mb-4" style="font-size:14px;">
              <div class="col-md-6">
                <span class="text-muted">Nama Lengkap</span><br>
                <strong>: <?= e($data['nama_lengkap']) ?></strong>
              </div>
              <div class="col-md-6">
                <span class="text-muted">NIS</span><br>
                <strong>: <?= e($data['nis']) ?></strong>
              </div>
              <div class="col-md-6">
                <span class="text-muted">Kelas</span><br>
                <strong>: <span class="badge bg-info"><?= e($data['nama_kelas'] ?? '-') ?></span></strong>
              </div>
              <div class="col-md-6">
                <span class="text-muted">Alamat Saat Ini</span><br>
                <strong>: <?= e($data['alamat'] ?? '-') ?></strong>
              </div>
              <div class="col-md-6">
                <span class="text-muted">No HP Saat Ini</span><br>
                <strong>: <?= e($data['no_hp'] ?? '-') ?></strong>
              </div>
            </div>

            <hr>

            <!-- Update Kontak -->
            <h6 class="fw-bold mb-3">Update Kontak</h6>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">No HP</label>
                <input type="text" name="no_hp" class="form-control"
                       value="<?= e($data['no_hp'] ?? '') ?>"
                       placeholder="Contoh: 08123456789">
              </div>
              <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"
                          placeholder="Masukkan alamat lengkap"><?= e($data['alamat'] ?? '') ?></textarea>
              </div>
              <button type="submit" name="simpan_kontak" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Simpan Kontak
              </button>
            </form>

          </div>
        </div>

        <!-- Kartu Ganti Password -->
        <div class="card shadow-sm">
          <div class="card-header bg-white fw-bold">
            <i class="fas fa-lock text-warning me-2"></i>Ganti Password
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Password Baru</label>
                  <input type="password" name="password_baru" class="form-control"
                         placeholder="Minimal 6 karakter" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Konfirmasi Password</label>
                  <input type="password" name="password_konfirm" class="form-control"
                         placeholder="Ulangi password baru" required>
                </div>
              </div>
              <button type="submit" name="simpan_password" class="btn btn-warning mt-3">
                <i class="fas fa-key me-1"></i>Perbarui Password
              </button>
            </form>
          </div>
        </div>

      </div><!-- end col-md-8 -->
    </div><!-- end row -->
  </div>
</div>

<script>
// preview foto langsung + auto submit
function previewDanSubmit(input) {
  if (input.files && input.files[0]) {
    var file   = input.files[0];
    var maxSize = 2 * 1024 * 1024; // 2mb

    if (file.size > maxSize) {
      siToast('warning', 'Ukuran file terlalu besar! Maksimal 2MB.');
      input.value = '';
      return;
    }

    // preview sebelum upload
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewFoto').src = e.target.result;
    };
    reader.readAsDataURL(file);

    // auto submit form foto
    siConfirm({
        icon: 'question',
        title: 'Upload foto ini sebagai foto profil?',
        confirmText: 'Ya, Upload'
    }).then(function (ok) {
      if (ok) {
        document.getElementById('formFoto').submit();
      } else {
        input.value = '';
        // reset preview
        document.getElementById('previewFoto').src = '<?= $foto_src ?>';
      }
    });
  }
}
</script>

<?php require_once '../../includes/footer.php'; ?>