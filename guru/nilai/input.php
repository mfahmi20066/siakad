<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../../config/helper_tahun_ajaran.php';
cekGuru();
$title = "Input Nilai";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

// SINKRONISASI SESSION: Menggunakan id_ref sebagai ID Guru yang login
$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : '';

// Tampilkan semua kelas
$kelas_list = mysqli_query($koneksi,
    "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");

// Hanya mapel yang diajar guru ini
$mapel_list = mysqli_query($koneksi,
    "SELECT DISTINCT m.*
     FROM mata_pelajaran m
     JOIN jadwal j ON j.mapel_id = m.id
     WHERE j.guru_id = '$gid'
     ORDER BY m.nama_mapel");

// FITUR AMAN: Cek struktur nama kolom tabel nilai saat ini demi memuaskan Trigger DB
$cek_uh = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_uh'");
$pake_uh = (mysqli_num_rows($cek_uh) > 0);

$cek_harian = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_harian'");
$pake_harian = (mysqli_num_rows($cek_harian) > 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sid  = $_POST['siswa_id'];
    $mid  = $_POST['mapel_id'];
    $kid  = $_POST['kelas_id'];
    $sem  = $_POST['semester'];
    $ta   = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $nh   = $_POST['nilai_harian'];
    $uts  = $_POST['nilai_uts'];
    $uas  = $_POST['nilai_uas'];

    // Tahun ajaran diambil dari MASTER tahun aktif (bukan POST) + validasi relasi.
    $taNilaiId = null;
    if ($taId === null || $taTahun === '') {
        $error = "Tidak ada tahun ajaran aktif.";
    } else {
        $taNilaiId = $taId;
        $kelasTa  = mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT tahun_ajaran_id FROM kelas WHERE id=".(int)$kid));
        $siswaTa  = mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT kelas_id, tahun_ajaran_id FROM siswa WHERE id=".(int)$sid));
        if ($kelasTa && $kelasTa['tahun_ajaran_id'] !== null && (int)$kelasTa['tahun_ajaran_id'] !== $taId) {
            $error = "Kelas terpilih bukan pada tahun ajaran aktif.";
        } elseif ($siswaTa && $siswaTa['tahun_ajaran_id'] !== null && (int)$siswaTa['tahun_ajaran_id'] !== $taId) {
            $error = "Siswa terpilih bukan pada tahun ajaran aktif.";
        }
    }

    // Cek duplikat (berbasis tahun_ajaran_id)
    if ($taNilaiId === null) {
        // $error sudah di-set
        $cek = null;
    } else {
        $cek = mysqli_query($koneksi,
               "SELECT id FROM nilai
                WHERE siswa_id='$sid' AND mapel_id='$mid'
                AND semester='$sem' AND tahun_ajaran_id='$taNilaiId'");
    }

    if ($taNilaiId !== null && $cek !== null && mysqli_num_rows($cek) > 0) {
        $error = "Nilai untuk siswa, mapel, dan semester ini sudah ada!";
    } elseif ($taNilaiId === null) {
        // biarkan pesan error dari validasi aktif tampil
    } else {
        $akhir = round(($nh * 0.3) + ($uts * 0.3) + ($uas * 0.4), 2);

        $kolom_insert = ["siswa_id", "mapel_id", "kelas_id", "guru_id", "semester", "tahun_ajaran", "tahun_ajaran_id", "nilai_uts", "nilai_uas", "nilai_akhir"];
        $values_insert = ["'$sid'", "'$mid'", "'$kid'", "'$gid'", "'$sem'", "'$taTahun'", "'$taNilaiId'", "'$uts'", "'$uas'", "'$akhir'"];

        if ($pake_uh) {
            $kolom_insert[] = "nilai_uh";
            $values_insert[] = "'$nh'";
        }
        if ($pake_harian) {
            $kolom_insert[] = "nilai_harian";
            $values_insert[] = "'$nh'";
        }

        $query_insert = "INSERT INTO nilai (" . implode(", ", $kolom_insert) . ") VALUES (" . implode(", ", $values_insert) . ")";
        mysqli_query($koneksi, $query_insert);

        // Notifikasi otomatis ke siswa atas nilai baru
        if (!function_exists('notifikasi_id_user_by_ref')) {
            include __DIR__ . '/../../includes/notifikasi_functions.php';
        }
        $user_siswa = notifikasi_id_user_by_ref($koneksi, $sid, 'siswa');
        if ($user_siswa) {
            $nama_mapel = '';
            $qm = mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id='$mid'");
            if ($qm && $rm = mysqli_fetch_assoc($qm)) $nama_mapel = $rm['nama_mapel'];
            notifikasi_insert($koneksi, $user_siswa,
                'Nilai baru telah diinput',
                "Nilai untuk mata pelajaran " . ($nama_mapel ?: 'terkait') . " semester $sem sudah tersedia.",
                '/siakad/siswa/nilai.php');
        }

        header("Location: index.php?success=Nilai berhasil diinput");
        exit();
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-gold me-2"></i>Input Nilai</h4>
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
        <div class="card-header">Form Input Nilai</div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Nilai Akhir = (Harian × 30%) + (UTS × 30%) + (UAS × 40%).
                Daftar siswa, kelas, dan mapel disesuaikan dengan jadwal mengajar Anda.
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        
                        <div class="mb-3">
                            <label class="form-label">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas_id" id="kelas_id" class="form-select" onchange="getSiswaPerKelas()" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>">
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required>
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="mapel_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['nama_mapel']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Semester</label>
                                    <select name="semester" class="form-select">
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran" class="form-control" value="<?= htmlspecialchars($taTahun) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nilai Harian <small class="text-muted">(30%)</small></label>
                            <input type="number" name="nilai_harian" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UTS <small class="text-muted">(30%)</small></label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UAS <small class="text-muted">(40%)</small></label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preview Nilai Akhir</label>
                            <div class="input-group">
                                <input type="text" id="preview" class="form-control fw-bold" placeholder="Otomatis" readonly>
                                <span class="input-group-text" id="predikat">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Nilai
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi AJAX mengambil data siswa berdasarkan kelas di folder guru
function getSiswaPerKelas() {
    const kelasId = document.getElementById('kelas_id').value;
    const siswaSelect = document.getElementById('siswa_id');
    
    if (!kelasId) {
        siswaSelect.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
        return;
    }
    
    siswaSelect.innerHTML = '<option value="">Sedang memuat data siswa...</option>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_siswa.php?kelas_id=' + kelasId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            siswaSelect.innerHTML = xhr.responseText;
        } else {
            siswaSelect.innerHTML = '<option value="">Gagal memuat data siswa</option>';
        }
    };
    xhr.send();
}

function hitungAkhir() {
    const nh  = parseFloat(document.querySelector('[name="nilai_harian"]').value) || 0;
    const uts = parseFloat(document.querySelector('[name="nilai_uts"]').value) || 0;
    const uas = parseFloat(document.querySelector('[name="nilai_uas"]').value) || 0;
    const val = Math.round(((nh * 0.3) + (uts * 0.3) + (uas * 0.4)) * 100) / 100;

    let p = '-', w = 'secondary';
    if (nh > 0 || uts > 0 || uas > 0) {
        if (val >= 90)      { p = 'A'; w = 'success'; }
        else if (val >= 80) { p = 'B'; w = 'primary'; }
        else if (val >= 70) { p = 'C'; w = 'info';    }
        else if (val >= 60) { p = 'D'; w = 'warning'; }
        else                { p = 'E'; w = 'danger';  }
    }

    document.getElementById('preview').value     = val > 0 ? val : '';
    document.getElementById('preview').className = `form-control fw-bold text-${val >= 75 ? 'success' : 'danger'}`;
    document.getElementById('predikat').textContent  = p;
    document.getElementById('predikat').className    = `input-group-text bg-${w} text-white fw-bold`;
}
</script>

<?php include '../../includes/footer.php'; ?>