<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Pengaturan Sekolah";

try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taTahun = date('Y') . '/' . (date('Y') + 1); }

// 1. OTOMATISASI STRUKTUR: Buat tabel jika belum ada (Dipastikan kolom langsung lengkap)
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengaturan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kepsek VARCHAR(150) NOT NULL,
    nip_kepsek VARCHAR(50) NOT NULL,
    alamat_sekolah TEXT NULL,
    tahun_pelajaran VARCHAR(20) DEFAULT '2024/2025',
    semester VARCHAR(20) DEFAULT '1 (Ganjil)'
)");

// AUTO-CREATE: Tabel agenda untuk menyimpan agenda sekolah yang dapat diatur
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS agenda (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    jam_mulai VARCHAR(10) NOT NULL,
    jam_selesai VARCHAR(10) NOT NULL,
    deskripsi VARCHAR(255) DEFAULT NULL,
    status_label VARCHAR(20) DEFAULT 'Terjadwal',
    urutan INT DEFAULT 0,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NULL,
    kategori ENUM('siswa','guru','semua') NOT NULL DEFAULT 'semua',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Deteksi apakah kolom hari/kategori sudah tersedia (guard utk tabel lama
// yang belum dimigrasi — jalankan database/migration_agenda_hari_kategori.sql
// untuk mengaktifkan kolom & notifikasi berbasis kategori).
$agenda_has_hari     = false;
$agenda_has_kategori = false;
$qAgCols = mysqli_query($koneksi, "SHOW COLUMNS FROM agenda");
if ($qAgCols) {
    while ($agcol = mysqli_fetch_assoc($qAgCols)) {
        if ($agcol['Field'] === 'hari')     $agenda_has_hari = true;
        if ($agcol['Field'] === 'kategori') $agenda_has_kategori = true;
    }
}

// 2. PAKSA TAMBAH KOLOM (Double check jika tabel lama sudah terlanjur terbentuk tanpa kolom baru)
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'alamat_sekolah'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN alamat_sekolah TEXT NULL");
}

$cek_tapel = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'tahun_pelajaran'");
if (mysqli_num_rows($cek_tapel) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN tahun_pelajaran VARCHAR(20) DEFAULT '2024/2025'");
}

$cek_semester = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'semester'");
if (mysqli_num_rows($cek_semester) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN semester VARCHAR(20) DEFAULT '1 (Ganjil)'");
}

// 3. Ambil data baris pertama, jika kosong (tabel baru) isi data default awal
$cek_isi = mysqli_query($koneksi, "SELECT id FROM pengaturan WHERE id = 1");
if (mysqli_num_rows($cek_isi) == 0) {
    mysqli_query($koneksi, "INSERT INTO pengaturan (id, nama_kepsek, nip_kepsek, alamat_sekolah, tahun_pelajaran, semester) 
        VALUES (1, 'Hj. Sukmawati, S.Pd., M.Si.', '19710512 199702 2 003', 'Jl. Andi Pangerang Pettarani, Kota Palopo, Sulawesi Selatan', '2024/2025', '1 (Ganjil)')");
}

// 4. Proses simpan data saat tombol diklik (Ditaruh sebelum penarikan data form agar perubahan langsung tampil)
// Guard: hanya proses identitas sekolah bila form pengaturan benar-benar di-submit
// (mencegah warning saat POST dari form lain seperti tambah/edit agenda).
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nama_kepsek'])) {
    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama_kepsek'] ?? '');
    $nip    = mysqli_real_escape_string($koneksi, $_POST['nip_kepsek'] ?? '');
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat_sekolah'] ?? '');
    // Tahun pelajaran = MIRROR dari master tahun ajaran aktif (bukan sumber kebenaran).
    // User TIDAK boleh mengubahnya menjadi tahun berbeda.
    $tapel  = $taTahun;
    $sem    = mysqli_real_escape_string($koneksi, $_POST['semester'] ?? '1 (Ganjil)');

    // Query update menyatukan data identitas sekolah beserta status akademik aktif
    $update = mysqli_query($koneksi, "UPDATE pengaturan SET nama_kepsek='$nama', nip_kepsek='$nip', alamat_sekolah='$alamat', tahun_pelajaran='$tapel', semester='$sem' WHERE id=1");

    if ($update) {
        $success = "Pengaturan sekolah & data akademik aktif berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui pengaturan: " . mysqli_error($koneksi);
    }
}

// 5. Ambil data pengaturan terbaru untuk ditampilkan di form
$query = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$skala = mysqli_fetch_assoc($query);

// =====================================================
// PROSES CRUD AGENDA SEKOLAH
// =====================================================

// TAMBAH AGENDA
if (isset($_POST['tambah_agenda'])) {
    $judul   = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $jam_mulai = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $status_label = mysqli_real_escape_string($koneksi, $_POST['status_label']);
    $urutan  = intval($_POST['urutan']);

    // Kolom baru (hari & kategori) hanya dipakai bila kolomnya sudah ada
    // (setelah migration_agenda_hari_kategori.sql dijalankan).
    $agenda_cols_extra = '';
    $agenda_vals_extra = '';
    $agenda_kategori   = 'semua';
    if ($agenda_has_hari) {
        $agenda_cols_extra .= ', hari';
        $agenda_vals_extra .= ", '" . mysqli_real_escape_string($koneksi, $_POST['hari'] ?? '') . "'";
    }
    if ($agenda_has_kategori) {
        $agenda_cols_extra .= ', kategori';
        $agenda_kategori = in_array($_POST['kategori'] ?? 'semua', ['siswa','guru','semua'], true)
                         ? $_POST['kategori'] : 'semua';
        $agenda_vals_extra .= ", '" . mysqli_real_escape_string($koneksi, $agenda_kategori) . "'";
    }

    $insert = mysqli_query($koneksi, "INSERT INTO agenda (judul, jam_mulai, jam_selesai, deskripsi, status_label, urutan{$agenda_cols_extra}) 
        VALUES ('$judul', '$jam_mulai', '$jam_selesai', '$deskripsi', '$status_label', $urutan{$agenda_vals_extra})");

    if ($insert) {
        $success_agenda = "Agenda berhasil ditambahkan!";

        // NOTIFIKASI berbasis kategori (fungsi yang sudah ada — tidak dibuat baru).
        // guru -> hanya guru | siswa -> hanya siswa | semua -> guru & siswa.
        if (!function_exists('notifikasi_ke_role')) {
            include __DIR__ . '/../includes/notifikasi_functions.php';
        }
        $judulNotif = 'Agenda baru: ' . $judul;
        $infoWaktu  = $jam_mulai . '-' . $jam_selesai;
        $hariNotif  = ($agenda_has_hari && !empty($_POST['hari'])) ? ' • ' . $_POST['hari'] : '';
        $pesanNotif = 'Agenda: ' . $judul . ' • ' . $infoWaktu . $hariNotif
                    . ($deskripsi !== '' ? ' • ' . $deskripsi : '');
        $linkNotif  = '/siakad/index.php';
        if ($agenda_kategori === 'guru') {
            notifikasi_ke_role($koneksi, 'guru', $judulNotif, $pesanNotif, $linkNotif);
        } elseif ($agenda_kategori === 'siswa') {
            notifikasi_ke_role($koneksi, 'siswa', $judulNotif, $pesanNotif, $linkNotif);
        } else {
            notifikasi_ke_role($koneksi, 'guru',  $judulNotif, $pesanNotif, $linkNotif);
            notifikasi_ke_role($koneksi, 'siswa', $judulNotif, $pesanNotif, $linkNotif);
        }
    } else {
        $error_agenda = "Gagal menambah agenda: " . mysqli_error($koneksi);
    }
}

// EDIT AGENDA
if (isset($_POST['edit_agenda'])) {
    $id_edit   = intval($_POST['id_agenda']);
    $judul     = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $jam_mulai = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $status_label = mysqli_real_escape_string($koneksi, $_POST['status_label']);
    $urutan    = intval($_POST['urutan']);

    // Kolom baru hanya diset bila sudah ada di tabel.
    $agenda_set_extra = '';
    if ($agenda_has_hari) {
        $agenda_set_extra .= ", hari='" . mysqli_real_escape_string($koneksi, $_POST['hari'] ?? '') . "'";
    }
    if ($agenda_has_kategori) {
        $kat = in_array($_POST['kategori'] ?? 'semua', ['siswa','guru','semua'], true)
             ? $_POST['kategori'] : 'semua';
        $agenda_set_extra .= ", kategori='" . mysqli_real_escape_string($koneksi, $kat) . "'";
    }

    $update = mysqli_query($koneksi, "UPDATE agenda SET 
        judul='$judul', jam_mulai='$jam_mulai', jam_selesai='$jam_selesai', 
        deskripsi='$deskripsi', status_label='$status_label', urutan=$urutan{$agenda_set_extra} 
        WHERE id=$id_edit");

    if ($update) {
        $success_agenda = "Agenda berhasil diperbarui!";
    } else {
        $error_agenda = "Gagal mengupdate agenda: " . mysqli_error($koneksi);
    }
}

// HAPUS AGENDA
if (isset($_GET['hapus_agenda'])) {
    $id_hapus = intval($_GET['hapus_agenda']);
    $delete = mysqli_query($koneksi, "DELETE FROM agenda WHERE id=$id_hapus");
    if ($delete) {
        $success_agenda = "Agenda berhasil dihapus!";
    } else {
        $error_agenda = "Gagal menghapus agenda: " . mysqli_error($koneksi);
    }
}

// Ambil semua agenda untuk ditampilkan, diurutkan berdasarkan urutan
$agenda_list = mysqli_query($koneksi, "SELECT * FROM agenda ORDER BY urutan ASC, id ASC");

// Jika ada data agenda untuk diedit, ambil detailnya
$edit_data = null;
if (isset($_GET['edit_agenda_id'])) {
    $edit_id = intval($_GET['edit_agenda_id']);
    $q_edit = mysqli_query($koneksi, "SELECT * FROM agenda WHERE id=$edit_id");
    $edit_data = mysqli_fetch_assoc($q_edit);
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-cog text-gold me-2"></i>Pengaturan Sistem & Akademik</h4>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $success ?>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card-header">
            <i class="fas fa-school"></i> Identitas & Pengaturan Akademik Sekolah
        </div>
        <div class="card-body">
            <form method="POST">
                
                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-user-tie"></i> Kepala Sekolah</h6>
                <div class="mb-3">
                    <label class="form-label">Nama Kepala Sekolah</label>
                    <input type="text" name="nama_kepsek" class="form-control" 
                           value="<?= htmlspecialchars($skala['nama_kepsek'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIP Kepala Sekolah</label>
                    <input type="text" name="nip_kepsek" class="form-control" 
                           value="<?= htmlspecialchars($skala['nip_kepsek'] ?? '') ?>" required>
                </div>
                
                <hr class="my-4">
                <h6 class="text-success fw-bold mb-3"><i class="fas fa-calendar-alt"></i> Periode Academic Aktif</h6>
                <div class="mb-3">
                    <label class="form-label">Tahun Pelajaran</label>
                    <input type="text" name="tahun_pelajaran" class="form-control" 
                           value="<?= htmlspecialchars($taTahun) ?>" readonly>
                           <small class="text-muted">Mengikuti tahun ajaran aktif (master). Tidak dapat diubah manual.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Semester Aktif</label>
                    <select name="semester" class="form-select" required>
                        <option value="1 (Ganjil)" <?= ($skala['semester'] ?? '1 (Ganjil)') == '1 (Ganjil)' ? 'selected' : '' ?>>1 (Ganjil)</option>
                        <option value="2 (Genap)" <?= ($skala['semester'] ?? '') == '2 (Genap)' ? 'selected' : '' ?>>2 (Genap)</option>
                    </select>
                </div>

                <hr class="my-4">
                <h6 class="text-secondary fw-bold mb-3"><i class="fas fa-map-marked-alt"></i> Lokasi</h6>
                <div class="mb-3">
                    <label class="form-label">Alamat Sekolah</label>
                    <textarea name="alamat_sekolah" class="form-control" rows="3" required><?= htmlspecialchars($skala['alamat_sekolah'] ?? '') ?></textarea>
                </div>
                
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- ========== AGENDA SEKOLAH (Pengaturan) ========== -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="fas fa-calendar-check"></i> Agenda Sekolah
        </div>
        <div class="card-body">

            <?php if (isset($success_agenda)): ?>
            <div class="alert alert-success alert-auto"><i class="fas fa-check-circle"></i> <?= $success_agenda ?></div>
            <?php endif; ?>
            <?php if (isset($error_agenda)): ?>
            <div class="alert alert-danger alert-auto"><i class="fas fa-exclamation-circle"></i> <?= $error_agenda ?></div>
            <?php endif; ?>

            <!-- Form Tambah / Edit Agenda -->
            <form method="POST" class="row g-2 align-items-end mb-4 pb-3 border-bottom">
                <input type="hidden" name="id_agenda" value="<?= $edit_data['id'] ?? '' ?>">

                <div class="col-md-4">
                    <label class="form-label">Judul Agenda <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Rapat Koordinasi Guru" required
                           value="<?= htmlspecialchars($edit_data['judul'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_mulai" class="form-control" 
                           value="<?= htmlspecialchars($edit_data['jam_mulai'] ?? '07:30') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_selesai" class="form-control" 
                           value="<?= htmlspecialchars($edit_data['jam_selesai'] ?? '13:00') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status_label" class="form-select">
                        <option value="Aktif" <?= ($edit_data['status_label'] ?? '') == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Akan Datang" <?= ($edit_data['status_label'] ?? '') == 'Akan Datang' ? 'selected' : '' ?>>Akan Datang</option>
                        <option value="Terjadwal" <?= ($edit_data['status_label'] ?? 'Terjadwal') == 'Terjadwal' ? 'selected' : '' ?>>Terjadwal</option>
                        <option value="Selesai" <?= ($edit_data['status_label'] ?? '') == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="urutan" class="form-control" min="0" value="<?= intval($edit_data['urutan'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hari (opsional)</label>
                    <select name="hari" class="form-select">
                        <option value="">-- Pilih Hari --</option>
                        <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?>
                        <option value="<?= $h ?>" <?= ($edit_data['hari'] ?? '') == $h ? 'selected' : '' ?>><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori Notifikasi</label>
                    <select name="kategori" class="form-select">
                        <option value="semua" <?= ($edit_data['kategori'] ?? 'semua') == 'semua' ? 'selected' : '' ?>>Semua (Guru &amp; Siswa)</option>
                        <option value="guru" <?= ($edit_data['kategori'] ?? '') == 'guru' ? 'selected' : '' ?>>Guru</option>
                        <option value="siswa" <?= ($edit_data['kategori'] ?? '') == 'siswa' ? 'selected' : '' ?>>Siswa</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Deskripsi (opsional)</label>
                    <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Ruang Guru • Semua guru diharapkan hadir"
                           value="<?= htmlspecialchars($edit_data['deskripsi'] ?? '') ?>">
                </div>
                <div class="col-md-12 mt-2">
                    <?php if ($edit_data): ?>
                        <button type="submit" name="edit_agenda" class="btn btn-success btn-sm">
                            <i class="fas fa-save"></i> Simpan Perubahan Agenda
                        </button>
                        <a href="pengaturan.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    <?php else: ?>
                        <button type="submit" name="tambah_agenda" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tambah Agenda
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Daftar Agenda -->
            <h6 class="fw-bold mb-3"><i class="fas fa-list"></i> Daftar Agenda</h6>
            <?php if ($agenda_list && mysqli_num_rows($agenda_list) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Jam</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Hari</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($ag = mysqli_fetch_assoc($agenda_list)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($ag['judul']) ?></strong></td>
                            <td><?= htmlspecialchars($ag['jam_mulai']) ?> - <?= htmlspecialchars($ag['jam_selesai']) ?></td>
                            <td><?= htmlspecialchars($ag['deskripsi'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $ag['status_label'] == 'Aktif' ? 'bg-success' : ($ag['status_label'] == 'Akan Datang' ? 'bg-warning text-dark' : ($ag['status_label'] == 'Selesai' ? 'bg-secondary' : 'bg-info text-dark')) ?>">
                                    <?= htmlspecialchars($ag['status_label']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($ag['hari'] ?? '-') ?></td>
                            <td>
                                <?php
                                $agk = $ag['kategori'] ?? 'semua';
                                $agkBadge = $agk == 'guru' ? 'bg-primary' : ($agk == 'siswa' ? 'bg-info' : 'bg-secondary');
                                ?>
                                <span class="badge <?= $agkBadge ?>"><?= htmlspecialchars(ucfirst($agk)) ?></span>
                            </td>
                            <td>
                                <a href="pengaturan.php?edit_agenda_id=<?= $ag['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="pengaturan.php?hapus_agenda=<?= $ag['id'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return siHapus('pengaturan.php?hapus_agenda=<?= $ag['id'] ?>', '<?= addslashes(htmlspecialchars($ag['judul'])) ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-3">
                <i class="fas fa-calendar-alt" style="font-size: 32px; color: var(--text-light);"></i>
                <p class="text-muted mt-2 mb-0">Belum ada agenda. Silakan tambah agenda baru di atas.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

