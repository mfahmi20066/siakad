<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tambah Jadwal";

// Ambil dropdown kelas, mapel, dan guru
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");
$mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$guru_list  = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

// Optional: set default dari request (kalau dibuka dari halaman lain)
$default_kelas = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';
$default_mapel = isset($_GET['mapel_id']) ? mysqli_real_escape_string($koneksi, $_GET['mapel_id']) : '';

// Tahun ajaran aktif (sumber kebenaran) — BUKAN dari POST bebas / date('Y').
$taId = null; $taTahun = '';
try {
    $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo());
    $taId    = (int) $taAktif['id'];
    $taTahun = $taAktif['tahun'];
} catch (Throwable $e) { $taId = null; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kid     = mysqli_real_escape_string($koneksi, $_POST['kelas_id']);
    $mid     = mysqli_real_escape_string($koneksi, $_POST['mapel_id']);
    $gid     = mysqli_real_escape_string($koneksi, $_POST['guru_id']);
    $hari    = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $mulai   = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);

    $error = null;

    // Validasi jam
    if (empty($kid) || empty($mid) || empty($hari) || empty($mulai) || empty($selesai)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (!in_array($hari, ['Senin','Selasa','Rabu','Kamis','Jumat'], true)) {
        $error = "Hari tidak valid. Hanya Senin s.d Jumat yang diizinkan (Sabtu/Minggu ditolak).";
    } elseif ($selesai <= $mulai) {
        $error = "Jam selesai harus lebih dari jam mulai.";
    } else {
        // Cek bentrok jadwal
        $cek_bentrok = mysqli_query($koneksi, "SELECT id FROM jadwal WHERE kelas_id='$kid' AND hari='$hari' AND (
            ('$mulai' < jam_selesai AND '$selesai' > jam_mulai)
        )");

        if ($cek_bentrok && mysqli_num_rows($cek_bentrok) > 0) {
            $error = "Jadwal bentrok! Kelas tersebut sudah memiliki jadwal di hari dan jam yang sama.";
        } else {
            // Validasi relasional: kelas harus berada pada tahun ajaran aktif.
            $kelasTa = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT tahun_ajaran_id FROM kelas WHERE id=".(int)$kid));
            if ($taId === null || $taTahun === '') {
                $error = "Tidak ada tahun ajaran aktif. Tetapkan tahun aktif di Modul Tahun Ajaran.";
            } elseif ($kelasTa && $kelasTa['tahun_ajaran_id'] !== null && (int)$kelasTa['tahun_ajaran_id'] !== $taId) {
                $error = "Kelas terpilih bukan pada tahun ajaran aktif ($taTahun).";
            } else {
                $ins = mysqli_query(
                    $koneksi,
                    "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, jam_mulai, jam_selesai,
                                         tahun_ajaran, tahun_ajaran_id, status, perlu_review)
                     VALUES ('$kid', '$mid', '$gid', '$hari', '$mulai', '$selesai',
                             '$taTahun', '$taId', 'aktif', 0)"
                );

                if ($ins) {
                    // Notifikasi otomatis ke guru yang bersangkutan
                    if (!function_exists('notifikasi_id_user_by_ref')) {
                        include __DIR__ . '/../../includes/notifikasi_functions.php';
                    }
                    $user_guru = notifikasi_id_user_by_ref($koneksi, $gid, 'guru');
                    if ($user_guru) {
                        $info = '';
                        $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id='$kid'");
                        if ($qk && $rk = mysqli_fetch_assoc($qk)) $info = $rk['nama_kelas'];
                        $qm = mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id='$mid'");
                        if ($qm && $rm = mysqli_fetch_assoc($qm)) $info = $info . ' - ' . $rm['nama_mapel'];
                        notifikasi_insert($koneksi, $user_guru,
                            'Jadwal mengajar baru',
                            "Anda mendapat jadwal baru: $info, hari $hari, $mulai-$selesai.",
                            '/siakad/guru/jadwal/index.php');
                    }
                    header("Location: index.php?success=Jadwal berhasil ditambahkan");
                    exit();
                }
                $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-gold me-2"></i>Tambah Jadwal</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="alert alert-danger alert-auto">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-alt"></i> Form Tambah Jadwal
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                    <option value="<?= (int)$k['id'] ?>" <?= (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $k['id']) || (!isset($_POST['kelas_id']) && $default_kelas !== '' && $default_kelas == $k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kelas']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="mapel_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                    <option value="<?= (int)$m['id'] ?>" <?= (isset($_POST['mapel_id']) && $_POST['mapel_id'] == $m['id']) || (!isset($_POST['mapel_id']) && $default_mapel !== '' && $default_mapel == $m['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nama_mapel']) ?> (<?= htmlspecialchars($m['kode_mapel']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guru Pengajar</label>
                            <select name="guru_id" id="guru_id" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                    <option value="<?= (int)$g['id'] ?>" <?= (isset($_POST['guru_id']) && $_POST['guru_id'] == $g['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nama']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted text-guru-otomatis">Mapel dipilih → guru otomatis terfilter.</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $h): ?>
                                    <option value="<?= $h ?>" <?= (isset($_POST['hari']) && $_POST['hari'] == $h) ? 'selected' : '' ?>><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" required value="<?= isset($_POST['jam_mulai']) ? htmlspecialchars($_POST['jam_mulai']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" required value="<?= isset($_POST['jam_selesai']) ? htmlspecialchars($_POST['jam_selesai']) : '' ?>">
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> Pastikan jam selesai lebih besar dari jam mulai.
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Jadwal
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapelSelect = document.querySelector('select[name="mapel_id"]');
    const guruSelect  = document.getElementById('guru_id');
    const guruHint    = document.querySelector('.text-guru-otomatis');

    // Simpan original options guru sebagai backup
    const originalOptions = guruSelect.innerHTML;

    mapelSelect.addEventListener('change', function() {
        const mapelId = this.value;

        // Reset ke semua guru kalau mapel belum dipilih
        if (!mapelId) {
            guruSelect.innerHTML = originalOptions;
            if (guruHint) guruHint.textContent = 'Mapel dipilih → guru otomatis terfilter.';
            return;
        }

        // Tampilkan loading
        guruSelect.innerHTML = '<option value="">Memuat data guru...</option>';

        fetch('get_guru_by_mapel.php?mapel_id=' + mapelId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || data.length === 0) {
                    guruSelect.innerHTML = '<option value="">-- Tidak ada guru --</option>';
                    if (guruHint) guruHint.textContent = 'Tidak ada guru yang mengajar mapel ini.';
                    return;
                }

                var html = '<option value="">-- Pilih Guru --</option>';
                data.forEach(function(g) {
                    html += '<option value="' + g.id + '">' + g.nama + '</option>';
                });
                guruSelect.innerHTML = html;
                if (guruHint) guruHint.textContent = 'Menampilkan ' + data.length + ' guru pengajar mapel ini.';
            })
            .catch(function(err) {
                console.error('Gagal memuat guru:', err);
                guruSelect.innerHTML = originalOptions;
                if (guruHint) guruHint.textContent = 'Gagal memuat, menampilkan semua guru.';
            });
    });
});
</script>
<?php include '../../includes/footer.php'; ?>

