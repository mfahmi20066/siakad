<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
include '../../config/helper_periode_nilai.php';
cekGuru();
$title = "Input Nilai";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

// pake id_ref sebagai id guru yang login
$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : '';

// cuma kelas yang diajar guru ini (dari pivot)
if (!isset($stmt_guru_kelas) || $stmt_guru_kelas === null) {
    $stmt_guru_kelas = mysqli_prepare($koneksi,
        "SELECT DISTINCT k.* FROM kelas k JOIN kelas_mapel_guru kmg ON kmg.kelas_id = k.id WHERE kmg.guru_id = ? AND k.status = 'aktif'");
    mysqli_stmt_bind_param($stmt_guru_kelas, "i", $gid);
}
mysqli_stmt_execute($stmt_guru_kelas);
$kelas_list = mysqli_stmt_get_result($stmt_guru_kelas);

// cuma mapel yang diajar guru ini (dari pivot)
if (!isset($stmt_guru_mapel) || $stmt_guru_mapel === null) {
    $stmt_guru_mapel = mysqli_prepare($koneksi,
        "SELECT DISTINCT m.* FROM mata_pelajaran m JOIN kelas_mapel_guru kmg ON kmg.mapel_id = m.id WHERE kmg.guru_id = ? AND m.status = 'aktif'");
    mysqli_stmt_bind_param($stmt_guru_mapel, "i", $gid);
}
mysqli_stmt_execute($stmt_guru_mapel);
$mapel_list = mysqli_stmt_get_result($stmt_guru_mapel);

// cek struktur kolom nilai biar ga bentrok trigger db
$cek_uh = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_uh'");
$pake_uh = (mysqli_num_rows($cek_uh) > 0);

$cek_harian = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_harian'");
$pake_harian = (mysqli_num_rows($cek_harian) > 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sid  = (int) ($_POST['siswa_id'] ?? 0);
    $mid  = (int) ($_POST['mapel_id'] ?? 0);
    $kid  = (int) ($_POST['kelas_id'] ?? 0);
    $sem  = (int) ($_POST['semester'] ?? 1);
    if ($sem !== 1 && $sem !== 2) $sem = 1;
    $ta   = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $nh   = (float) ($_POST['nilai_harian'] ?? 0);
    $uts  = (float) ($_POST['nilai_uts'] ?? 0);
    $uas  = (float) ($_POST['nilai_uas'] ?? 0);

    // ta dari master tahun aktif + validasi relasi
    $taNilaiId = null;
    if ($taId === null || $taTahun === '') {
        $error = "Tidak ada tahun ajaran aktif.";
    } else {
        $taNilaiId = $taId;
        // prepared statement kelas
        if (!isset($stmt_tahun_kelas) || $stmt_tahun_kelas === null) {
            $stmt_tahun_kelas = mysqli_prepare($koneksi, "SELECT tahun_ajaran_id FROM kelas WHERE id=?");
            mysqli_stmt_bind_param($stmt_tahun_kelas, "i");
        }
        mysqli_stmt_bind_param($stmt_tahun_kelas, "i", $kid);
        mysqli_stmt_execute($stmt_tahun_kelas);
        mysqli_stmt_bind_result($stmt_tahun_kelas, $kmg_tahun_id);
        mysqli_stmt_fetch($stmt_tahun_kelas);
        $kelasTa = ($kmg_tahun_id !== null && (int)$kmg_tahun_id !== $taId);

        // prepared statement siswa
        if (!isset($stmt_siswa_tahun) || $stmt_siswa_tahun === null) {
            $stmt_siswa_tahun = mysqli_prepare($koneksi, "SELECT kelas_id, tahun_ajaran_id FROM siswa WHERE id=?");
            mysqli_stmt_bind_param($stmt_siswa_tahun, "i");
        }
        mysqli_stmt_bind_param($stmt_siswa_tahun, "i", $sid);
        mysqli_stmt_execute($stmt_siswa_tahun);
        mysqli_stmt_bind_result($stmt_siswa_tahun, $siswa_kelas_id, $siswa_ta_id);
        mysqli_stmt_fetch($stmt_siswa_tahun);
        $siswaTa = ($siswa_kelas_id !== null && $siswa_ta_id !== null && (int)$siswa_ta_id !== $taId);

        if ($kelasTa && $kelasTa !== false && (int)$kelasTa !== $taId) {
            $error = "Kelas terpilih bukan pada tahun ajaran aktif.";
        } elseif ($siswaTa && $siswaTa !== false && (int)$siswaTa !== $taId) {
            $error = "Siswa terpilih bukan pada tahun ajaran aktif.";
        } elseif ($sid <= 0 || $mid <= 0 || $kid <= 0) {
            $error = "Siswa, mata pelajaran, dan kelas wajib dipilih.";
        }
    }

    // lock periode: guru cuma bisa input kalo periode dibuka admin
    if ($taNilaiId !== null && !isset($error)) {
        if (!isPeriodeBuka($koneksi, $taNilaiId, $sem, $kid)) {
            $error = pesanNilaiTerkunci();
        }
    }

    // rapor final? nilai ga boleh berubah
    if ($taNilaiId !== null && !isset($error)) {
        // cek rapor final
        if (!isset($stmt_rapor_final) || $stmt_rapor_final === null) {
            $stmt_rapor_final = mysqli_prepare($koneksi,
                "SELECT id FROM rapor WHERE siswa_id=? AND semester=? AND tahun_ajaran_id=? AND status='final' LIMIT 1");
            mysqli_stmt_bind_param($stmt_rapor_final, "issi", $sid, $sem, $taNilaiId);
        }
        mysqli_stmt_execute($stmt_rapor_final);
        mysqli_stmt_bind_result($stmt_rapor_final, $rapor_id);
        mysqli_stmt_fetch($stmt_rapor_final);
        $rapor_final = ($rapor_id !== null);

        if ($rapor_final) {
            $error = "Nilai tidak dapat diubah: rapor semester ini sudah difinalisasi.";
        }
    }

    // cek duplikat (by tahun_ajaran_id)
    if ($taNilaiId === null) {
        $cek = null;
    } else {
        // cek duplikat nilai
        if (!isset($stmt_cek_duplicate) || $stmt_cek_duplicate === null) {
            $stmt_cek_duplicate = mysqli_prepare($koneksi,
                "SELECT id FROM nilai WHERE siswa_id=? AND mapel_id=? AND semester=? AND tahun_ajaran_id=?");
            mysqli_stmt_bind_param($stmt_cek_duplicate, "issi", $sid, $mid, $sem, $taNilaiId);
        }
        mysqli_stmt_execute($stmt_cek_duplicate);
        mysqli_stmt_bind_result($stmt_cek_duplicate, $cek_id);
        mysqli_stmt_fetch($stmt_cek_duplicate);
        $duplicate = ($cek_id !== null);
    }

    // prioritas pesan: error yang udah ada (periode terkunci, rapor final, validasi) ga boleh ditimpa pesan duplikat; insert cuma kalo belum ada error
    if (!isset($error) && $taNilaiId !== null && $cek !== null && mysqli_num_rows($cek) > 0) {
        $error = "Nilai untuk siswa, mapel, dan semester ini sudah ada!";
    } elseif ($taNilaiId === null) {
        // biarkan pesan error validasi tampil
    } elseif (!isset($error)) {
        // otorisasi: kelas + mapel harus milik guru ini (via pivot)
        if (!isset($stmt_kmg_check) || $stmt_kmg_check === null) {
            $stmt_kmg_check = mysqli_prepare($koneksi,
                "SELECT id FROM kelas_mapel_guru WHERE kelas_id=? AND mapel_id=? AND guru_id=? AND tahun_ajaran_id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt_kmg_check, "iiii", $kid, $mid, $gid, $taNilaiId);
        }
        mysqli_stmt_execute($stmt_kmg_check);
        mysqli_stmt_bind_result($stmt_kmg_check, $kmg_exist_id);
        mysqli_stmt_fetch($stmt_kmg_check);
        $kmg_sql = ($kmg_exist_id !== null) ? (int)$kmg_exist_id : null;

        if ($kmg_sql === null) {
            $error = "Anda tidak mengajar mata pelajaran ini di kelas tersebut.";
        } else {

            // kehadiran dari absensi (20% nilai akhir)
            $kehadiran = 0;
            // ambil rekap kehadiran
            if (!isset($stmt_abs_guru) || $stmt_abs_guru === null) {
                $stmt_abs_guru = mysqli_prepare($koneksi,
                    "SELECT SUM(status = 'Hadir') AS hadir, COUNT(*) AS total
                    FROM absensi WHERE siswa_id = ? AND mapel_id = ?");
                mysqli_stmt_bind_param($stmt_abs_guru, "ss", $sid, $mid);
            }
            mysqli_stmt_execute($stmt_abs_guru);
            mysqli_stmt_bind_result($stmt_abs_guru, $hadir, $total_abs);
            mysqli_stmt_fetch($stmt_abs_guru);
            $kehadiran = $total_abs > 0
                ? round(((int) $hadir / $total_abs) * 100, 2)
                : 0;

            // rumus nilai akhir: harian 20% + uts 25% + uas 35% + kehadiran 20%
            $akhir = round(($nh * 0.20) + ($uts * 0.25) + ($uas * 0.35) + ($kehadiran * 0.20), 2);

            // tentuin kolom yang mau diinsert
            $columns = ["siswa_id", "mapel_id", "kelas_mapel_guru_id", "kelas_id", "guru_id", "semester", "tahun_ajaran", "tahun_ajaran_id", "nilai_harian", "nilai_uts", "nilai_uas", "nilai_akhir"];
            $values = ["'$sid'", "'$mid'", $kmg_sql, "'$kid'", "'$gid'", "'$sem'", "'$taTahun'", "'$taNilaiId'", "'$nh'", "'$uts'", "'$uas'", "'$akhir'"];

            if ($pake_uh) {
                $columns[] = "nilai_uh";
                $values[] = "'$nh'";
            }
            $cek_kolom_kehadiran = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_kehadiran'");
            if (mysqli_num_rows($cek_kolom_kehadiran) > 0) {
                $columns[] = "nilai_kehadiran";
                $values[] = "'$kehadiran'";
            }

            // insert nilai (kolom dinamis)
            $columns_str = implode(", ", $columns);
            $placeholders = implode(", ", array_fill(0, count($values), "?"));
            
            if (!isset($stmt_insert_nilai) || $stmt_insert_nilai === null) {
                $stmt_insert_nilai = mysqli_prepare($koneksi, "INSERT INTO nilai ($columns_str) VALUES ($placeholders)");
            }
            // semua nilai dikirim string (s) karena float diproses sebagai string
            $types = str_repeat("s", count($values));
            $param_list = [];
            foreach ($values as $v) { $param_list[] = $v; }
            call_user_func_array([$stmt_insert_nilai, 'bind_param'], array_merge([$types], $param_list));
            mysqli_stmt_execute($stmt_insert_nilai);

            // notif otomatis ke siswa pas nilai baru
            if (!function_exists('notifikasi_id_user_by_ref')) {
                include __DIR__ . '/../../includes/notifikasi_functions.php';
            }
            $user_siswa = notifikasi_id_user_by_ref($koneksi, $sid, 'siswa');
            if ($user_siswa) {
                $nama_mapel = '';
                // ambil nama mapel
                if (!isset($stmt_nama_mapel) || $stmt_nama_mapel === null) {
                    $stmt_nama_mapel = mysqli_prepare($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id=?");
                    mysqli_stmt_bind_param($stmt_nama_mapel, "i");
                }
                mysqli_stmt_bind_param($stmt_nama_mapel, "i", $mid);
                mysqli_stmt_execute($stmt_nama_mapel);
                mysqli_stmt_bind_result($stmt_nama_mapel, $nm_mapel);
                mysqli_stmt_fetch($stmt_nama_mapel);
                $nama_mapel = $nm_mapel ?: '';
                notifikasi_insert($koneksi, $user_siswa,
                    'Nilai baru telah diinput',
                    "Nilai untuk mata pelajaran " . ($nama_mapel ?: 'terkait') . " semester $sem sudah tersedia.",
                    '/siakad/siswa/nilai.php');
            }

            header("Location: index.php?success=Nilai berhasil diinput");
            exit();
        }
    }
}

?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-plus text-icon me-2"></i>Input Nilai</h4>
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
                Nilai Akhir = (Harian — 20%) + (UTS — 25%) + (UAS — 35%) + (Kehadiran — 20%).
                Kehadiran dihitung otomatis dari data absensi.
                Daftar siswa, kelas, dan mapel disesuaikan dengan jadwal mengajar Anda.
                Penginputan hanya dapat dilakukan saat periode nilai dibuka oleh admin.
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
                                    <?= e($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select name="siswa_id" id="siswa_id" class="form-select" onchange="muatKehadiranOtomatis()" required>
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="mapel_id" id="mapel_id" class="form-select" onchange="muatKehadiranOtomatis()" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= e($m['nama_mapel']) ?>
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
                                    <input type="text" name="tahun_ajaran" class="form-control" value="<?= e($taTahun) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nilai Harian <small class="text-muted">(20%)</small></label>
                            <input type="number" name="nilai_harian" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UTS <small class="text-muted">(25%)</small></label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai UAS <small class="text-muted">(35%)</small></label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   min="0" max="100" step="0.01" placeholder="0-100" oninput="hitungAkhir()" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Nilai Kehadiran <small class="text-muted">(otomatis dari data Absensi, ikut menentukan 20% Nilai Akhir)</small>
                            </label>
                            <div id="info_kehadiran" class="alert alert-secondary mb-0 py-2 px-3">
                                Pilih Siswa dan Mata Pelajaran dulu untuk melihat rekap kehadiran.
                            </div>
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
// fungsi ajax: ambil data siswa by kelas
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
        muatKehadiranOtomatis();
    };
    xhr.send();
}

function muatKehadiranOtomatis() {
    const sid = document.getElementById('siswa_id').value;
    const mid = document.getElementById('mapel_id').value;
    const box = document.getElementById('info_kehadiran');

    if (!sid || !mid) {
        box.className = 'alert alert-secondary mb-0 py-2 px-3';
        box.innerHTML = 'Pilih Siswa dan Mata Pelajaran dulu untuk melihat rekap kehadiran.';
        window.__kehadiranPersen = 0;
        hitungAkhir();
        return;
    }

    box.className = 'alert alert-secondary mb-0 py-2 px-3';
    box.innerHTML = 'Memuat rekap kehadiran...';

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_kehadiran.php?siswa_id=' + encodeURIComponent(sid) + '&mapel_id=' + encodeURIComponent(mid), true);
    xhr.onload = function () {
        if (xhr.status !== 200) {
            box.className = 'alert alert-danger mb-0 py-2 px-3';
            box.innerHTML = 'Gagal memuat data kehadiran.';
            return;
        }
        try {
            const d = JSON.parse(xhr.responseText);
            window.__kehadiranPersen = d.persen || 0;

            if (d.total === 0) {
                box.className = 'alert alert-warning mb-0 py-2 px-3';
                box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Belum ada data absensi untuk siswa & mapel ini (dihitung 0 untuk sementara).';
                hitungAkhir();
                return;
            }
            const warna = d.persen >= 75 ? 'success' : 'danger';
            box.className = 'alert alert-' + warna + ' mb-0 py-2 px-3';
            box.innerHTML =
                '<strong>' + d.persen + '%</strong> kehadiran ' +
                '<span class="text-muted">(dari ' + d.total + 'x pertemuan)</span><br>' +
                '<span class="badge bg-success me-1">Hadir: ' + d.hadir + '</span>' +
                '<span class="badge bg-info me-1">Izin: ' + d.izin + '</span>' +
                '<span class="badge bg-warning text-dark me-1">Sakit: ' + d.sakit + '</span>' +
                '<span class="badge bg-danger">Alpa: ' + d.alpa + '</span>';
            hitungAkhir();
        } catch (e) {
            box.className = 'alert alert-danger mb-0 py-2 px-3';
            box.innerHTML = 'Gagal memuat data kehadiran.';
            window.__kehadiranPersen = 0;
            hitungAkhir();
        }
    };
    xhr.send();
}

function hitungAkhir() {
    const nh  = parseFloat(document.querySelector('[name="nilai_harian"]').value) || 0;
    const uts = parseFloat(document.querySelector('[name="nilai_uts"]').value) || 0;
    const uas = parseFloat(document.querySelector('[name="nilai_uas"]').value) || 0;
    const kehadiran = window.__kehadiranPersen || 0;
    const val = Math.round(((nh * 0.20) + (uts * 0.25) + (uas * 0.35) + (kehadiran * 0.20)) * 100) / 100;

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