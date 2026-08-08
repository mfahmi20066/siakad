<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Edit Guru";

// Tahun ajaran aktif untuk jadwal baru (dinamis)
$taAktif = null;
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); }
catch (Throwable $e) { $taAktif = null; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM guru WHERE id='$id'"));
if (!$data) {
    header("Location: index.php?error=Data guru tidak ditemukan");
    exit();
}

// Ambil daftar kelas & mapel yang terkait guru dari tabel jadwal (untuk ditampilkan di halaman edit)
$array_mapel_guru = [];
$array_kelas_guru = [];
$q_jadwal = mysqli_query(
    $koneksi,
    "SELECT DISTINCT j.mapel_id, mp.nama_mapel, k.id AS kelas_id, k.nama_kelas
     FROM jadwal j
     JOIN mata_pelajaran mp ON mp.id = j.mapel_id
     JOIN kelas k ON k.id = j.kelas_id
     WHERE j.guru_id = '$id'"
);
if ($q_jadwal) {
    while ($j = mysqli_fetch_assoc($q_jadwal)) {
        $array_mapel_guru[] = ['mapel_id' => (int)$j['mapel_id'], 'nama_mapel' => $j['nama_mapel']];
        $array_kelas_guru[] = ['kelas_id' => (int)$j['kelas_id'], 'nama_kelas' => $j['nama_kelas']];
    }
}

// Unikkan berdasarkan id
$mapel_unique = [];
foreach ($array_mapel_guru as $m) {
    $mapel_unique[$m['mapel_id']] = $m;
}
$array_mapel_guru = array_values($mapel_unique);

$kelas_unique = [];
foreach ($array_kelas_guru as $k) {
    $kelas_unique[$k['kelas_id']] = $k;
}
$array_kelas_guru = array_values($kelas_unique);

$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");
$mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel");

// Proses tambah jadwal dari halaman edit guru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi_jadwal']) && $_POST['aksi_jadwal'] === 'tambah_jadwal') {
    $kid     = (int)($_POST['kelas_id'] ?? 0);
    $mid     = (int)($_POST['mapel_id'] ?? 0);
    $hari    = mysqli_real_escape_string($koneksi, $_POST['hari'] ?? '');
    $mulai   = mysqli_real_escape_string($koneksi, $_POST['jam_mulai'] ?? '00:00:00');
    $selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai'] ?? '00:00:00');

    if (!in_array($hari, ['Senin','Selasa','Rabu','Kamis','Jumat'], true)) {
        $error = "Hari tidak valid. Hanya Senin s.d Jumat yang diizinkan (Sabtu/Minggu ditolak).";
    } elseif ($selesai <= $mulai) {
        $error = "Jam selesai harus lebih dari jam mulai!";
    } else {
        // Bentrok jadwal (cek jam di kelas yang sama)
        $cek_bentrok = mysqli_query($koneksi,
            "SELECT id FROM jadwal
             WHERE kelas_id = '$kid'
             AND hari = '$hari'
             AND (
                 ('$mulai' >= jam_mulai AND '$mulai' < jam_selesai) OR
                 ('$selesai' > jam_mulai AND '$selesai' <= jam_selesai) OR
                 ('$mulai' <= jam_mulai AND '$selesai' >= jam_selesai)
             )");

        if (mysqli_num_rows($cek_bentrok) > 0) {
            $error = "Jadwal bentrok! Kelas tersebut sudah memiliki jadwal di hari dan jam yang sama.";
        } else {
            // Pastikan sinkron dengan aplikasi lain (tahun ajaran & validasi bentrok dipakai dari tabel yang sama)
            $taId  = $taAktif ? (int)$taAktif['id'] : 'NULL';
            $taName = $taAktif ? $taAktif['tahun'] : (date('Y') . '/' . (date('Y') + 1));
            mysqli_query($koneksi,
                "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, jam_mulai, jam_selesai,
                                     tahun_ajaran, tahun_ajaran_id, semester_id, jam_pelajaran_id, status, perlu_review)
                 VALUES ('$kid', '$mid', '$id', '$hari', '$mulai', '$selesai', '$taName', $taId, NULL, NULL, 'aktif', 0)"
            );
            header("Location: edit.php?id=$id&success=Jadwal berhasil ditambahkan");

            exit();
        }
    }
}

// Proses update data guru (form utama)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (!isset($_POST['aksi_jadwal']) || $_POST['aksi_jadwal'] !== 'tambah_jadwal')) {
    $nip    = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jk     = $_POST['jenis_kelamin'];
    $ttl    = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl    = !empty(trim($_POST['tanggal_lahir'] ?? ''))
              ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']) . "'"
              : "NULL";
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['no_hp']);

    // ── Upload / Hapus Foto ───────────────────────────────
    $foto_update = $data['foto'] ?? '';
    $folder_guru = __DIR__ . '/../../assets/img/foto_guru/';

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
            if (!is_dir($folder_guru)) mkdir($folder_guru, 0777, true);
            if (move_uploaded_file($file['tmp_name'], $folder_guru . $nama_file)) {
                if (!empty($foto_update) && file_exists($folder_guru . $foto_update)) {
                    unlink($folder_guru . $foto_update);
                }
                $foto_update = $nama_file;
            }
        }
    } elseif (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
        if (!empty($foto_update) && file_exists($folder_guru . $foto_update)) {
            unlink($folder_guru . $foto_update);
        }
        $foto_update = '';
    }

    mysqli_query($koneksi, "UPDATE guru 
                            SET nip='$nip', nama='$nama', jenis_kelamin='$jk',
                                tempat_lahir='$ttl', tanggal_lahir=$tgl,
                                alamat='$alamat', no_hp='$hp', foto='$foto_update'
                            WHERE id='$id'");

    $user_id = $data['user_id'] ?? ($data['id_user'] ?? null);
    if ($user_id !== null) {
        mysqli_query($koneksi, "UPDATE users SET nama='$nama' WHERE id='$user_id'");
    } else {
        mysqli_query($koneksi, "UPDATE users SET nama='$nama' WHERE id_ref='$id' AND role='guru'");
    }

    if (!empty($_POST['password'])) {
        $pass = hashPassword($_POST['password']);
        if ($user_id !== null) {
            mysqli_query($koneksi, "UPDATE users SET password='$pass' WHERE id='$user_id'");
        } else {
            mysqli_query($koneksi, "UPDATE users SET password='$pass' WHERE id_ref='$id' AND role='guru'");
        }
    }

    header("Location: index.php?success=Data guru berhasil diupdate");
    exit();
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-gold me-2"></i>Edit Guru</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-book"></i> Mapel & Kelas yang diampu
                </div>
                <div class="card-body">
                            <div class="mb-3">
                        <strong>Mapel</strong>
                        <ul class="list-group list-group-flush mt-2">
                            <?php if (!empty($array_mapel_guru)): ?>
                                <?php foreach ($array_mapel_guru as $m): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($m['nama_mapel']) ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">Tidak ada mapel (hanya wali kelas)</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div>
                        <strong>Kelas</strong>
                        <ul class="list-group list-group-flush mt-2">
                            <?php if (!empty($array_kelas_guru)): ?>
                                <?php foreach ($array_kelas_guru as $k): ?>
                                    <li class="list-group-item"><?= htmlspecialchars($k['nama_kelas']) ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">Belum ada kelas</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Form Edit Guru</div>
                <div class="card-body">
                    <?php if (isset($upload_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= $upload_error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">

                            <!-- Kolom Kiri: Data Pribadi -->
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3 fw-bold">Data Pribadi</h6>
                                <div class="mb-3">
                                    <label class="form-label">NIP</label>
                                    <input type="text" name="nip" class="form-control"
                                           value="<?= htmlspecialchars($data['nip'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control"
                                           value="<?= htmlspecialchars($data['nama'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select">
                                        <option value="L" <?= ($data['jenis_kelamin'] ?? '') == 'L' ? 'selected' : '' ?>>
                                            Laki-laki
                                        </option>
                                        <option value="P" <?= ($data['jenis_kelamin'] ?? '') == 'P' ? 'selected' : '' ?>>
                                            Perempuan
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" class="form-control"
                                           value="<?= htmlspecialchars($data['tempat_lahir'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" class="form-control"
                                           value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="3">
                                        <?= htmlspecialchars($data['alamat'] ?? '') ?>
                                    </textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">No HP</label>
                                    <input type="text" name="no_hp" class="form-control"
                                           value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Kolom Kanan: Foto + Ganti Password -->
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3 fw-bold">Foto Profil</h6>

                                <?php
                                $foto_guru  = $data['foto'] ?? '';
                                $foto_guru_src = (!empty($foto_guru) && file_exists(__DIR__ . '/../../assets/img/foto_guru/' . $foto_guru))
                                    ? '/siakad/assets/img/foto_guru/' . $foto_guru
                                    : '/siakad/assets/img/default-avatar.png';
                                ?>

                                <div class="foto-dropzone mb-1" id="dropzoneFoto"
                                     data-folder="assets/img/foto_guru">
                                    <div class="dz-preview" id="dzPreview">
                                        <img src="<?= $foto_guru_src ?>" id="previewFoto" alt="Foto Profil">
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

                                <hr>
                                <h6 class="text-muted mb-3 fw-bold">Ganti Password</h6>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" name="password" class="form-control"
                                           placeholder="Kosongkan jika tidak ingin mengubah password">
                                </div>

                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Pastikan guru sudah diberitahu jika password diubah.
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

            <!-- Form Tambah Jadwal untuk Guru (agar bisa edit mapel & kelas langsung dari halaman edit guru) -->
            <div class="card mt-3">
                <div class="card-header">Tambah / Edit Jadwal Guru</div>
                <div class="card-body">
                    <?php if (isset($error)) : ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="aksi_jadwal" value="tambah_jadwal">

                        <div class="row g-3">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Kelas</label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php if ($kelas_list) : ?>
                                        <?php while ($k = mysqli_fetch_assoc($kelas_list)) : ?>
                                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Mata Pelajaran</label>
                                <select name="mapel_id" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php if ($mapel_list) : ?>
                                        <?php while ($m = mysqli_fetch_assoc($mapel_list)) : ?>
                                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Hari</label>
                                <select name="hari" class="form-select" required>
                                    <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $h) : ?>
                                        <option value="<?= $h ?>"><?= $h ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control" value="07:30" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control" value="09:00" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Simpan Jadwal
                        </button>
                    </form>
                </div>
            </div>
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


