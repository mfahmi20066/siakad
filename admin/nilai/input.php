<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Input Nilai";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

// Mengambil list dasar untuk dropdown form (Cadangan jika AJAX tidak mengembalikan data)
$mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
$guru_list  = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

// FITUR AMAN: Cek struktur nama kolom tabel nilai saat ini
$cek_uh = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai LIKE 'nilai_uh'");
$kolom_uh = (mysqli_num_rows($cek_uh) > 0) ? "nilai_uh" : "nilai_harian";

// Siapkan prepared statements reusable
$stmt_tahun_ajaran = null;
$stmt_siswa = null;
$stmt_cek_nilai = null;
$stmt_kmg = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sid  = $_POST['siswa_id'];
    $mid  = $_POST['mapel_id'];
    $kid  = $_POST['kelas_id'];
    $gid  = $_POST['guru_id'];
    $sem  = $_POST['semester'];
    $ta   = mysqli_real_escape_string($koneksi, $_POST['tahun_ajaran']);
    $nh   = $_POST['nilai_harian'];
    $uts  = $_POST['nilai_uts'];
    $uas  = $_POST['nilai_uas'];

    // Tahun ajaran diambil dari MASTER tahun aktif (bukan POST) + validasi relasi kelas/siswa.
    $taNilaiId = null;
    if ($taId === null || $taTahun === '') {
        $error = "Tidak ada tahun ajaran aktif. Tetapkan tahun aktif di Modul Tahun Ajaran.";
    } else {
        $taNilaiId = $taId;
        // Prepared statement untuk kelas
        if (!isset($stmt_tahun_ajaran) || $stmt_tahun_ajaran === null) {
            $stmt_tahun_ajaran = mysqli_prepare($koneksi, "SELECT tahun_ajaran_id FROM kelas WHERE id=?");
            mysqli_stmt_bind_param($stmt_tahun_ajaran, "i");
        }
        mysqli_stmt_bind_param($stmt_tahun_ajaran, "i", $kid);
        mysqli_stmt_execute($stmt_tahun_ajaran);
        mysqli_stmt_bind_result($stmt_tahun_ajaran, $kmg_tahun_id);
        mysqli_stmt_fetch($stmt_tahun_ajaran);
        $kelasTa = ($kmg_tahun_id !== null && (int)$kmg_tahun_id !== $taId);

        // Prepared statement untuk siswa
        if (!isset($stmt_siswa) || $stmt_siswa === null) {
            $stmt_siswa = mysqli_prepare($koneksi, "SELECT kelas_id, tahun_ajaran_id FROM siswa WHERE id=?");
            mysqli_stmt_bind_param($stmt_siswa, "i");
        }
        mysqli_stmt_bind_param($stmt_siswa, "i", $sid);
        mysqli_stmt_execute($stmt_siswa);
        mysqli_stmt_bind_result($stmt_siswa, $siswa_kelas_id, $siswa_ta_id);
        mysqli_stmt_fetch($stmt_siswa);
        $siswaTa = ($siswa_kelas_id !== null && $siswa_ta_id !== null && (int)$siswa_ta_id !== $taId);

        if ($kelasTa && $kelasTa !== false && (int)$kelasTa !== $taId) {
            $error = "Kelas terpilih bukan pada tahun ajaran aktif.";
        } elseif ($siswaTa && $siswaTa !== false && (int)$siswaTa !== $taId) {
            $error = "Siswa terpilih bukan pada tahun ajaran aktif.";
        }
    }

    if ($nh < 0 || $nh > 100 || $uts < 0 || $uts > 100 || $uas < 0 || $uas > 100) {
        $error = "Nilai harus antara 0 sampai 100!";
    } elseif ($taNilaiId === null) {
        // $error sudah di-set oleh blok validasi di atas
    } else {
        // Prepared statement cek duplicate nilai
        if (!isset($stmt_cek_nilai) || $stmt_cek_nilai === null) {
            $stmt_cek_nilai = mysqli_prepare($koneksi,
                "SELECT id FROM nilai 
                 WHERE siswa_id=? AND mapel_id=? 
                 AND semester=? AND tahun_ajaran_id=?");
            mysqli_stmt_bind_param($stmt_cek_nilai, "ssis", $sid, $mid, $sem, $taNilaiId);
        }
        mysqli_stmt_execute($stmt_cek_nilai);
        mysqli_stmt_bind_result($stmt_cek_nilai, $cek_id);
        mysqli_stmt_fetch($stmt_cek_nilai);
        $duplicate = ($cek_id !== null);

        if ($duplicate) {
            $error = "Nilai siswa ini untuk mata pelajaran dan semester tersebut sudah ada!";
        } else {

            // RUMUS NILAI AKHIR: Harian 20% + UTS 25% + UAS 35% + Kehadiran 20%
            $akhir = round(($nh * 0.20) + ($uts * 0.25) + ($uas * 0.35) + ($kehadiran * 0.20), 2);

            // Referensi pivot kelas_mapel_guru (kelas + mapel + guru + TA) — sumber kebenaran relasi
            $kmg_sql = 'NULL';
            // Prepared statement cek KMG existence
            if (!isset($stmt_kmg_check) || $stmt_kmg_check === null) {
                $stmt_kmg_check = mysqli_prepare($koneksi,
                    "SELECT id FROM kelas_mapel_guru
                     WHERE kelas_id=? AND mapel_id=? AND guru_id=? AND tahun_ajaran_id=? LIMIT 1");
                mysqli_stmt_bind_param($stmt_kmg_check, "iiii", $kid, $mid, $gid, $taNilaiId);
            }
            mysqli_stmt_execute($stmt_kmg_check);
            mysqli_stmt_bind_result($stmt_kmg_check, $kmg_exist_id);
            mysqli_stmt_fetch($stmt_kmg_check);
            $kmg_sql = ($kmg_exist_id !== null) ? (int)$kmg_exist_id : 'NULL';
            if ($kmg_sql === 'NULL') {
                // Auto-sinkron: buat penugasan pivot bila belum ada, agar nilai selalu terhubung
                // (lanjut ke blok INSERT yang sudah di-migrasi di atas)
            }
                if (!isset($stmt_kmg_kkm) || $stmt_kmg_kkm === null) {
                    $stmt_kmg_kkm = mysqli_prepare($koneksi, "SELECT kkm FROM mata_pelajaran WHERE id=?");
                    mysqli_stmt_bind_param($stmt_kmg_kkm, "i");
                }
                mysqli_stmt_bind_param($stmt_kmg_kkm, "i", $mid);
                mysqli_stmt_execute($stmt_kmg_kkm);
                mysqli_stmt_bind_result($stmt_kmg_kkm, $kkm_result);
                mysqli_stmt_fetch($stmt_kmg_kkm);
                $kkm_p = ($kkm_result !== null) ? (int)$kkm_result : 75;

                // Prepared statement INSERT INTO kelas_mapel_guru
                if (!isset($stmt_kmg_insert) || $stmt_kmg_insert === null) {
                    $stmt_kmg_insert = mysqli_prepare($koneksi,
                        "INSERT INTO kelas_mapel_guru (kelas_id, mapel_id, guru_id, tahun_ajaran_id, kkm, jam_per_minggu)
                         VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_kmg_insert, "iiiiii", $kid, $mid, $gid, $taNilaiId, $kkm_p, 2);
                }
                mysqli_stmt_execute($stmt_kmg_insert);
                $new_id = mysqli_insert_id($koneksi);
                if ($new_id) $kmg_sql = (int) $new_id;
            }

            // SINKRONISASI COCOK: Memasukkan semua data utuh sesuai struktur database penyeimbang Anda
            if ($ada_kolom_kehadiran) {
                // Prepared statement INSERT INTO nilai dengan kolom kehadiran
                if (!isset($stmt_nilai_ch) || $stmt_nilai_ch === null) {
                    $stmt_nilai_ch = mysqli_prepare($koneksi,
                        "INSERT INTO nilai 
                         (siswa_id, mapel_id, kelas_mapel_guru_id, kelas_id, guru_id, semester, tahun_ajaran, tahun_ajaran_id,
                          $kolom_uh, nilai_uts, nilai_uas, nilai_kehadiran, nilai_akhir)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_nilai_ch, "issiiiiiiiiii", $sid, $mid, $kmg_sql, $kid, $gid, $sem, $taTahun, $taNilaiId, $nh, $uts, $uas, $kehadiran, $akhir);
                }
                mysqli_stmt_execute($stmt_nilai_ch);
            } else {
                // Prepared statement INSERT INTO nilai tanpa kolom kehadiran
                if (!isset($stmt_nilai_nc) || $stmt_nilai_nc === null) {
                    $stmt_nilai_nc = mysqli_prepare($koneksi,
                        "INSERT INTO nilai 
                         (siswa_id, mapel_id, kelas_mapel_guru_id, kelas_id, guru_id, semester, tahun_ajaran, tahun_ajaran_id,
                          $kolom_uh, nilai_uts, nilai_uas, nilai_akhir)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_nilai_nc, "issiiiiiiii", $sid, $mid, $kmg_sql, $kid, $gid, $sem, $taTahun, $taNilaiId, $nh, $uts, $uas, $akhir);
                }
                mysqli_stmt_execute($stmt_nilai_nc);
            }

            header("Location: index.php?success=Nilai berhasil diinput");
            exit();
        }
    }
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


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
        <div class="card-header">
            <i class="fa fa-star"></i> Form Input Nilai
        </div>
        <div class="card-body">

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Rumus Nilai Akhir:</strong>
                (Nilai Harian — 20%) + (Nilai UTS — 25%) + (Nilai UAS — 35%) + (Kehadiran — 20%)
            </div>

            <form method="POST">
                <div class="row g-3">

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Data Academic</h6>

                        <div class="mb-3">
                            <label class="form-label">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas_id" id="kelas_id" class="form-select" onchange="jalankanOtomatisasiKelas()" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $k['id']) ? 'selected' : '' ?>>
                                    <?= e($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select name="siswa_id" id="siswa_id" class="form-select"
                                    onchange="muatKehadiranOtomatis()" required>
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="mapel_id" id="mapel_id" class="form-select" required>
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guru <span class="text-danger">*</span></label>
                            <select name="guru_id" id="guru_id" class="form-select" required>
                                <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Semester</label>
                                    <select name="semester" class="form-select">
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran"
                                           class="form-control" value="<?= e($taTahun) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Input Nilai</h6>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai Harian <span class="text-danger">*</span>
                                <small class="text-muted">(bobot 30%)</small>
                            </label>
                            <input type="number" name="nilai_harian" class="form-control"
                                   min="0" max="100" step="0.01"
                                   placeholder="0 - 100"
                                   value="<?= isset($_POST['nilai_harian']) ? $_POST['nilai_harian'] : '' ?>"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai UTS <span class="text-danger">*</span>
                                <small class="text-muted">(bobot 30%)</small>
                            </label>
                            <input type="number" name="nilai_uts" class="form-control"
                                   min="0" max="100" step="0.01"
                                   placeholder="0 - 100"
                                   value="<?= isset($_POST['nilai_uts']) ? $_POST['nilai_uts'] : '' ?>"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai UAS <span class="text-danger">*</span>
                                <small class="text-muted">(bobot 40%)</small>
                            </label>
                            <input type="number" name="nilai_uas" class="form-control"
                                   min="0" max="100" step="0.01"
                                   placeholder="0 - 100"
                                   value="<?= isset($_POST['nilai_uas']) ? $_POST['nilai_uas'] : '' ?>"
                                   oninput="hitungNilaiAkhir()" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preview Nilai Akhir</label>
                            <div class="input-group">
                                <input type="text" id="preview_akhir"
                                       class="form-control fw-bold"
                                       placeholder="Otomatis dihitung" readonly>
                                <span class="input-group-text" id="preview_predikat">-</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nilai Kehadiran <small class="text-muted">(otomatis dari data Absensi, ikut menentukan 20% Nilai Akhir)</small>
                            </label>
                            <div id="info_kehadiran" class="alert alert-secondary mb-0 py-2 px-3">
                                Pilih Siswa dan Mata Pelajaran dulu untuk melihat rekap kehadiran.
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
// Fungsi Induk untuk memicu semua pengambilan data real-time saat kelas diganti
function jalankanOtomatisasiKelas() {
    getSiswaPerKelas();
    getMapelDanGuruPerKelas();
    muatKehadiranOtomatis(); // reset info kehadiran karena siswa/mapel berubah
}

// 1. Ambil data siswa berdasarkan kelas (Fungsi bawaan Anda sebelumnya)
function getSiswaPerKelas() {
    const kelasId = document.getElementById('kelas_id').value;
    const siswaSelect = document.getElementById('siswa_id');
    
    if (!kelasId) {
        siswaSelect.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
        return;
    }
    
    siswaSelect.innerHTML = '<option value="">Sedang memuat data...</option>';
    
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

// 1b. Ambil rekap kehadiran otomatis dari tabel absensi (Siswa + Mapel harus sudah dipilih)
function muatKehadiranOtomatis() {
    const sid = document.getElementById('siswa_id').value;
    const mid = document.getElementById('mapel_id').value;
    const box = document.getElementById('info_kehadiran');

    if (!sid || !mid) {
        box.className = 'alert alert-secondary mb-0 py-2 px-3';
        box.innerHTML = 'Pilih Siswa dan Mata Pelajaran dulu untuk melihat rekap kehadiran.';
        window.__kehadiranPersen = 0;
        hitungNilaiAkhir();
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
                hitungNilaiAkhir();
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
            hitungNilaiAkhir();
        } catch (e) {
            box.className = 'alert alert-danger mb-0 py-2 px-3';
            box.innerHTML = 'Gagal memuat data kehadiran.';
            window.__kehadiranPersen = 0;
            hitungNilaiAkhir();
        }
    };
    xhr.send();
}

// 2. Ambil data Mata Pelajaran dan Guru secara otomatis berdasarkan jadwal di kelas tersebut
function getMapelDanGuruPerKelas() {
    const kelasId = document.getElementById('kelas_id').value;
    const mapelSelect = document.getElementById('mapel_id');
    const guruSelect = document.getElementById('guru_id');

    if (!kelasId) {
        mapelSelect.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
        guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
        return;
    }

    mapelSelect.innerHTML = '<option value="">Memuat mata pelajaran...</option>';
    guruSelect.innerHTML = '<option value="">Memuat daftar guru...</option>';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'get_mapel_guru.php', true);

    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);

                // Atur ulang teks bawaan awal
                mapelSelect.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';

                window.__mapelGuruData = response || [];

                if (response.length > 0) {
                    // Loop 1: Tempel opsi mata pelajaran otomatis
                    response.forEach(function(data) {
                        let optMapel = document.createElement('option');
                        optMapel.value = data.mapel_id;
                        optMapel.textContent = data.nama_mapel;
                        mapelSelect.appendChild(optMapel);
                    });

                    // Loop 2: isi guru sesuai mapel terpilih sekarang (atau kosong)
                    updateGuruDropdown();

                    // Pastikan ketika mapel dipilih, guru otomatis ikut berubah
                    // sekaligus muat ulang rekap kehadiran otomatis
                    mapelSelect.onchange = function() {
                        updateGuruDropdown();
                        muatKehadiranOtomatis();
                    };
                } else {
                    mapelSelect.innerHTML = '<option value="">-- Tidak ada mapel di kelas ini --</option>';
                    guruSelect.innerHTML = '<option value="">-- Tidak ada guru di kelas ini --</option>';
                }
            } catch (e) {
                mapelSelect.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
            }
        } else {
            siToast('error', 'Gagal mensinkronisasikan data akademik.');
        }
    };
    xhr.send('kelas_id=' + encodeURIComponent(kelasId));
}

function updateGuruDropdown() {
    const mapelSelect = document.getElementById('mapel_id');
    const guruSelect = document.getElementById('guru_id');

    const mid = mapelSelect.value;
    guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';

    if (!mid || !window.__mapelGuruData || window.__mapelGuruData.length === 0) {
        return;
    }

    const item = window.__mapelGuruData.find(x => String(x.mapel_id) === String(mid));
    const listGuru = (item && item.list_guru) ? item.list_guru : [];

    if (!listGuru || listGuru.length === 0) {
        guruSelect.innerHTML = '<option value="">-- Tidak ada guru untuk mapel ini --</option>';
        return;
    }

    listGuru.forEach(function(guru) {
        let optGuru = document.createElement('option');
        optGuru.value = guru.guru_id;
        optGuru.textContent = guru.nama_guru;
        guruSelect.appendChild(optGuru);
    });
}


// 3. Fungsi hitung otomatis nilai akhir bawaan Anda
// Rumus: Harian 20% + UTS 25% + UAS 35% + Kehadiran 20%
function hitungNilaiAkhir() {
    const nh        = parseFloat(document.querySelector('[name="nilai_harian"]').value) || 0;
    const uts       = parseFloat(document.querySelector('[name="nilai_uts"]').value) || 0;
    const uas       = parseFloat(document.querySelector('[name="nilai_uas"]').value) || 0;
    const kehadiran = window.__kehadiranPersen || 0;

    const akhir = Math.round(((nh * 0.20) + (uts * 0.25) + (uas * 0.35) + (kehadiran * 0.20)) * 100) / 100;

    let predikat = '-';
    let warna    = 'secondary';
    if (nh > 0 || uts > 0 || uas > 0) {
        if (akhir >= 90)      { predikat = 'A'; warna = 'success'; }
        else if (akhir >= 80) { predikat = 'B'; warna = 'primary'; }
        else if (akhir >= 70) { predikat = 'C'; warna = 'info';    }
        else if (akhir >= 60) { predikat = 'D'; warna = 'warning'; }
        else                  { predikat = 'E'; warna = 'danger';  }
    }

    const preview    = document.getElementById('preview_akhir');
    const predikatEl = document.getElementById('preview_predikat');

    preview.value      = akhir > 0 ? akhir : '';
    preview.className  = `form-control fw-bold text-${akhir >= 75 ? 'success' : 'danger'}`;
    predikatEl.textContent  = predikat;
    predikatEl.className    = `input-group-text bg-${warna} text-white fw-bold`;
}
</script>

<?php include '../../includes/footer.php'; ?>