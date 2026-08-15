<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

$action = isset($_GET['action']) ? $_GET['action'] : 'form';

// halaman form: pilih kelas & semester
if ($action === 'form'):

$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$sys       = mysqli_fetch_assoc($q_setting);

// tahun ajaran dari data rapor, berbasis tahun_ajaran_id + join master
$tahun_list = mysqli_query($koneksi,
    "SELECT DISTINCT ta.id AS ta_id, ta.nama_tahun_ajaran AS tahun_ajaran
     FROM rapor r JOIN tahun_ajaran ta ON ta.id = r.tahun_ajaran_id
     ORDER BY ta.id DESC");
// tahun default dari master tahun aktif (bukan pengaturan teks / date)
$q_master = mysqli_query($koneksi, "SELECT nama_tahun_ajaran FROM tahun_ajaran WHERE status='aktif' LIMIT 1");
$tahun_def = ($q_master && $m = mysqli_fetch_assoc($q_master)) ? $m['nama_tahun_ajaran']
            : ($sys['tahun_pelajaran'] ?? (date('Y') . '/' . (date('Y')+1)));

// semua kelas, ga cuma yang udah punya rapor
$kelas_list = mysqli_query($koneksi,
    "SELECT k.id, k.nama_kelas, k.tingkat
     FROM kelas k
     ORDER BY k.tingkat, k.nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-print text-icon me-2"></i>Cetak Rapor Individu</h4>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-sliders-h"></i> Pilih Parameter
        </div>
        <div class="card-body">
            <form method="GET" action="cetak_kelas.php" target="_blank">
                <input type="hidden" name="action" value="cetak">
                <div class="row g-3">
<div class="col-md-4">
                        <label class="form-label fw-bold">Jenis Output</label>
                        <select name="format" id="format" class="form-select">
                            <option value="print">Cetak / Print</option>
                            <option value="pdf">Unduh PDF</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nama Kelas <span class="text-danger">*</span></label>
                        <select name="nama_kelas" id="nama_kelas" class="form-select"
                                onchange="muatSiswaPerKelas()" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php
                            if ($kelas_list && mysqli_num_rows($kelas_list) > 0) {
                                while ($kl = mysqli_fetch_assoc($kelas_list)) {
                                    echo '<option value="' . e($kl['nama_kelas']) . '">'
                                       . e($kl['nama_kelas'])
                                       . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nama Siswa <span class="text-danger">*</span></label>
                        <select name="nama" id="nama_siswa" class="form-select" required>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                    </div>
<div class="col-md-4">
                        <label class="form-label fw-bold">Semester</label>
                        <select name="semester" id="semester_ta" class="form-select"
                                onchange="muatSiswaPerKelas()">
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </select>
</div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tahun Ajaran</label>
                        <select name="ta" id="select_ta" class="form-select" required>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-danger" id="btnCetak">
                    <i class="fas fa-print"></i> Cetak Rapor
                </button>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

<script>
// muat tahun ajaran & siswa otomatis sesuai kelas & semester
function muatSiswaPerKelas() {
    const namaKelas = document.getElementById('nama_kelas').value;
    const semester = document.getElementById('semester_ta').value;
    const taSelect = document.getElementById('select_ta');
    const siswaSelect = document.getElementById('nama_siswa');

    if (!namaKelas) {
        taSelect.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
        siswaSelect.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
        return;
    }

    taSelect.innerHTML = '<option value="">Memuat tahun ajaran...</option>';
    siswaSelect.innerHTML = '<option value="">Sedang memuat data...</option>';

    // 1. muat tahun ajaran buat kelas & semester
    const xhrTa = new XMLHttpRequest();
    xhrTa.open('GET', 'get_ta_by_kelas.php?nama_kelas=' + encodeURIComponent(namaKelas) + '&semester=' + encodeURIComponent(semester), true);
    xhrTa.onload = function() {
        if (xhrTa.status === 200) {
            let listTa = [];
            try { listTa = JSON.parse(xhrTa.responseText) || []; } catch (e) { listTa = []; }

            if (listTa.length > 0) {
                let opts = '';
                listTa.forEach(function(ta) {
                    opts += '<option value="' + ta + '">' + ta + '</option>';
                });
                taSelect.innerHTML = opts;
            } else {
                taSelect.innerHTML = '<option value="">Tidak ada tahun ajaran</option>';
            }
        } else {
            taSelect.innerHTML = '<option value="">Gagal memuat tahun ajaran</option>';
        }

        // 2. abis ta dipilih, muat siswa
        muatSiswa();
    };
    xhrTa.send();
}

// muat siswa by kelas, semester & ta terpilih
function muatSiswa() {
    const namaKelas = document.getElementById('nama_kelas').value;
    const semester = document.getElementById('semester_ta').value;
    const ta = document.getElementById('select_ta').value;
    const siswaSelect = document.getElementById('nama_siswa');

    if (!namaKelas || !ta) {
        siswaSelect.innerHTML = '<option value="">-- Pilih Kelas & Tahun Ajaran --</option>';
        return;
    }

    siswaSelect.innerHTML = '<option value="">Sedang memuat data...</option>';

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_siswa_by_kelas.php?nama_kelas=' + encodeURIComponent(namaKelas)
        + '&ta=' + encodeURIComponent(ta)
        + '&semester=' + encodeURIComponent(semester), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            siswaSelect.innerHTML = xhr.responseText;
        } else {
            siswaSelect.innerHTML = '<option value="">Gagal memuat data siswa</option>';
        }
    };
    xhr.send();
}

// pas ta berubah, muat ulang siswa
document.getElementById('select_ta').addEventListener('change', muatSiswa);

// alihkan action form antara cetak (print) dan pdf
function ubahAction() {
    var fmt = document.getElementById('format').value;
    var form = document.querySelector('form');
    if (fmt === 'pdf') {
        var act = form.querySelector('input[name="action"]');
        form.setAttribute('action', 'cetak_pdf.php');
        if (act) act.disabled = true;
        var btn = document.getElementById('btnCetak');
        btn.innerHTML = '<i class="fas fa-file-pdf"></i> Unduh PDF';
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-primary');
    } else {
        var act = form.querySelector('input[name="action"]');
        form.setAttribute('action', 'cetak_kelas.php');
        if (act) act.disabled = false;
        var btn = document.getElementById('btnCetak');
        btn.innerHTML = '<i class="fas fa-print"></i> Cetak Rapor';
        btn.classList.add('btn-danger');
        btn.classList.remove('btn-primary');
    }
}
document.getElementById('format').addEventListener('change', ubahAction);
</script>

<?php include '../../includes/footer.php'; ?>

<?php
exit;
endif;

// halaman cetak: rapor individu by kelas & nama siswa

$nama_kelas = isset($_GET['nama_kelas']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama_kelas'])) : '';
$nama       = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama'])) : '';
$semester   = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '1';
$ta         = isset($_GET['ta']) ? mysqli_real_escape_string($koneksi, $_GET['ta']) : '';

// resolve tahun (string legacy) ke id master; query utama tetep pake id
$taId = 0;
if ($ta !== '') {
    $rq = mysqli_query($koneksi, "SELECT id FROM tahun_ajaran WHERE nama_tahun_ajaran='$ta' LIMIT 1");
    if ($rq && $rr = mysqli_fetch_assoc($rq)) $taId = (int) $rr['id'];
}
if ($taId === 0) {
    $rq = mysqli_query($koneksi, "SELECT id FROM tahun_ajaran WHERE status='aktif' LIMIT 1");
    if ($rq && $rr = mysqli_fetch_assoc($rq)) $taId = (int) $rr['id'];
}
$taNama = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT nama_tahun_ajaran v FROM tahun_ajaran WHERE id=$taId"))['v'] ?? $ta;

if (!$nama_kelas || !$nama) {
    die('Nama kelas dan nama siswa wajib diisi.');
}

// ambil info kelas by nama
$info_kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT k.*, g.nama AS wali_kelas
     FROM kelas k
     LEFT JOIN guru g ON k.wali_kelas = g.id
     WHERE k.nama_kelas = '$nama_kelas'"));

if (!$info_kelas) {
    die('Kelas "' . e($nama_kelas) . '" tidak ditemukan.');
}

// ambil semua siswa di kelas yang cocok (kiri), lengkapi data rapor kalo ada, biar semua kelas tetep bisa dicetak
$siswa_list = mysqli_query($koneksi,
    "SELECT s.id AS id_siswa, s.nis, s.nisn, s.nama, s.nama_lengkap,
            s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir, s.alamat, s.no_hp,
            s.nama_ortu, s.no_hp_ortu,
            r.id AS rapor_id, r.semester, r.tahun_ajaran, r.tahun_ajaran_id,
            ta.nama_tahun_ajaran AS nama_tahun, r.status_kenaikan,
            r.status, r.catatan, r.kerajinan, r.kelakuan, r.kerapihan, r.ekstrakurikuler
     FROM siswa s
     LEFT JOIN rapor r ON r.siswa_id = s.id
       AND r.kelas_id = '{$info_kelas['id']}'
       AND (r.semester = '$semester' OR r.semester = '0')
       AND r.tahun_ajaran_id = '$taId'
     LEFT JOIN tahun_ajaran ta ON ta.id = r.tahun_ajaran_id
     WHERE s.kelas_id = '{$info_kelas['id']}'
       AND s.nama LIKE '%$nama%'
     ORDER BY s.nama");

// ambil setting sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

// cek kolom kelompok/kkm udah ada
$cek_kelompok = mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran LIKE 'kelompok'");
$ada_kelompok = mysqli_num_rows($cek_kelompok) > 0;
$select_mapel_extra = $ada_kelompok ? "m.kelompok, m.kkm," : "'Umum' AS kelompok, 75 AS kkm,";
$select_urutan = $ada_kelompok
    ? "CASE m.kelompok WHEN 'Normatif' THEN 1 WHEN 'Adaptif' THEN 2 WHEN 'Produktif' THEN 3 WHEN 'Muatan Lokal' THEN 4 ELSE 5 END AS urutan_kelompok"
    : "5 AS urutan_kelompok";

function romawi($n) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
    return $map[$n] ?? (string)$n;
}

function format_semester($s) {
    if ($s == 1 || $s == '1' || strtolower($s) == 'ganjil') return 'Ganjil';
    if ($s == 2 || $s == '2' || strtolower($s) == 'genap') return 'Genap';
    return (string)$s;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rapor Kelas <?= e($info_kelas['nama_kelas']) ?> — <?= e($ta) ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    font-size: 11.5px;
    padding: 15px 20px;
    color: #000;
}

.rapor-page {
    page-break-after: always;
    padding: 10px 15px;
}
.rapor-page:last-child {
    page-break-after: avoid;
}

.btn-print {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 20px;
    background: #163A63;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
}
.btn-pdf {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    margin-left: 8px;
    width: 36px;
    height: 36px;
    background: #163A63;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    vertical-align: middle;
}
.btn-pdf svg { width: 18px; height: 18px; }
.btn-pdf:hover { background: #0D2540; }
.btn-back {
    display: inline-block;
    margin-bottom: 15px;
    margin-right: 8px;
    padding: 8px 20px;
    background: #4A5568;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
}

.kop {
    display: flex;
    align-items: center;
    border-bottom: 3px double #000;
    padding-bottom: 8px;
    margin-bottom: 8px;
}
.kop img { width: 86px; height: 86px; object-fit: contain; }
.kop .logo-kiri  { margin-right: 16px; }
.kop .logo-kanan { margin-left: 10px; }
.kop-text { text-align: center; flex: 1; line-height: 1.25; padding-right: 102px; }
.kop-text .instansi { font-size: 13px; }
.kop-text .sekolah  { font-size: 21px; font-weight: bold; text-transform: uppercase; }
.kop-text .alamat   { font-size: 12px; }

.judul {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    text-decoration: underline;
    margin: 12px 0 12px;
    letter-spacing: 0.5px;
}

.identitas { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
.identitas td { padding: 2px 4px; font-size: 11px; vertical-align: top; }
.identitas .label { width: 135px; }
.identitas .titik { width: 8px; }

table.rapor-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.rapor-table th, .rapor-table td { border: 1px solid #000; padding: 3px 6px; font-size: 10.5px; }
.rapor-table th { text-align: center; font-weight: bold; background: #F5F7FB; }
.text-center { text-align: center; }
.grup-row td { font-weight: bold; background: #F5F7FB; }
.merah { color: #E11D48; font-weight: bold; }

.seksi-judul { font-weight: bold; margin: 14px 0 5px; font-size: 12px; }

.catatan-box {
    border: 1px solid #000;
    min-height: 40px;
    padding: 8px;
    margin-bottom: 12px;
    font-size: 11px;
}

.footer-ttd { margin-top: 20px; width: 100%; }
.footer-ttd .tgl { text-align: right; margin-bottom: 3px; font-size: 11px; }
.footer-ttd table { width: 100%; border-collapse: collapse; margin-top: 22px; }
.footer-ttd td { text-align: center; font-size: 11px; vertical-align: top; padding: 0 8px; }
.footer-ttd .garis { margin-top: 45px; border-bottom: 1px solid #000; }
.footer-ttd .nama { margin-top: 2px; font-weight: bold; text-decoration: underline; }
.footer-ttd .tgl-table { width: auto; margin-left: auto; margin-top: 0; border-collapse: collapse; }
.footer-ttd .tgl-table td { padding: 0 2px; text-align: left; vertical-align: top; border: none; }
.footer-ttd .tgl-table .tgl-label { width: 110px; text-align: left; white-space: nowrap; }
.footer-ttd .tgl-table .tgl-titik { width: 12px; text-align: center; white-space: nowrap; }

@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    .btn-print, .btn-back, .btn-pdf, .no-print { display: none !important; }
    body { padding: 5px 10px; }
    .rapor-page { page-break-after: always; }
}

@page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:15px; display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
    <a href="cetak_kelas.php?action=form" class="btn-back" style="margin-bottom:0;">&larr; Kembali</a>
    <button class="btn-print" style="margin-bottom:0;" onclick="window.print()">Cetak</button>
    <a class="btn-pdf" style="margin-bottom:0;" href="cetak_pdf.php?nama_kelas=<?= urlencode($nama_kelas) ?>&nama=<?= urlencode($nama) ?>&semester=<?= urlencode($semester) ?>&ta=<?= urlencode($ta) ?>&download=1&v=2" title="Simpan sebagai PDF">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    </a>
    <span style="font-size:13px;color:#4A5568;">
        Rapor siswa <strong><?= e($nama) ?></strong>
        Kelas <strong><?= e($info_kelas['nama_kelas']) ?></strong>
        Semester <?= format_semester($semester) ?> TA <?= e($ta) ?>
    </span>
</div>

<?php if ($siswa_list && mysqli_num_rows($siswa_list) > 0): ?>
<?php
$counter = 0;
while ($rapor = mysqli_fetch_assoc($siswa_list)):
    $counter++;

    // pake id siswa sebagai acuan, bukan r.siswa_id yang bisa null
    $sid_siswa = $rapor['id_siswa'];
    // Semester & tahun ajaran fallback ke nilai dari URL bila belum ada record rapor
    $smt_efektif  = !empty($rapor['semester']) ? $rapor['semester'] : $semester;
    $taEfektifId  = !empty($rapor['tahun_ajaran_id']) ? $rapor['tahun_ajaran_id'] : $taId;
    $taEfektifNama = !empty($rapor['nama_tahun']) ? $rapor['nama_tahun'] : ($taNama ?: $ta);

    // Ambil nilai per siswa
    $nilai = mysqli_query($koneksi,
        "SELECT n.*, m.nama_mapel, m.kode_mapel, $select_mapel_extra
                $select_urutan
         FROM nilai n
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         WHERE n.siswa_id = '$sid_siswa'
           AND n.semester  = '$smt_efektif'
           AND n.tahun_ajaran_id = '$taEfektifId'
         ORDER BY urutan_kelompok, m.nama_mapel");

    // Absensi per siswa
    $hadir = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM absensi WHERE siswa_id='$sid_siswa' AND status='Hadir'"))[0] ?? 0;
    $sakit = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM absensi WHERE siswa_id='$sid_siswa' AND status='Sakit'"))[0] ?? 0;
    $izin  = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM absensi WHERE siswa_id='$sid_siswa' AND status='Izin'"))[0] ?? 0;
    $alpa  = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM absensi WHERE siswa_id='$sid_siswa' AND status='Alpa'"))[0] ?? 0;

    $kerajinan = $rapor['kerajinan'] ?? 'Baik';
    $kelakuan  = $rapor['kelakuan']  ?? 'Baik';
    $kerapihan = $rapor['kerapihan'] ?? 'Baik';
    $eskul     = $rapor['ekstrakurikuler'] ?? '';
?>
<div class="rapor-page">

<div class="kop">
    <img class="logo-kiri" src="../../assets/img/logo-sekolah.png"
         onerror="this.style.display='none'" alt="Logo">
    <div class="kop-text">
        <div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>
        <div class="sekolah">SMA NEGERI 4 PALOPO</div>
        <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div>
        <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div><div class="alamat"><?= e(trim((!empty($setting['telepon']) ? 'Telp. ' . $setting['telepon'] : '') . (!empty($setting['email']) ? ' | Email: ' . $setting['email'] : ''))) ?></div>
    </div></div>

<div class="judul">LAPORAN HASIL BELAJAR</div>

<table class="identitas">
    <tr>
        <td class="label">NAMA</td><td class="titik">:</td>
        <td style="font-weight:bold"><?= e($rapor['nama'] ?? '-') ?></td>
        <td class="label" style="width:100px">Kelas</td><td class="titik">:</td>
        <td><?= e($info_kelas['nama_kelas'] ?? '-') ?></td>
    </tr>
    <tr>
        <td class="label">NIS</td><td class="titik">:</td>
        <td><?= e($rapor['nis'] ?? '-') ?></td>
        <td class="label">Semester</td><td class="titik">:</td>
        <td><?= format_semester($rapor['semester'] ?? $semester) ?></td>
    </tr>
    <tr>
        <td class="label">TEMPAT, TANGGAL LAHIR</td><td class="titik">:</td>
        <td><?= e($rapor['tempat_lahir'] ?? '-') ?>,
            <?= isset($rapor['tanggal_lahir']) ? tanggal_indo($rapor['tanggal_lahir']) : '-' ?></td>
        <td class="label">Tahun Pelajaran</td><td class="titik">:</td>
        <td><?= e($taEfektifNama) ?></td>
    </tr>
    <tr>
        <td class="label">JENIS KELAMIN</td><td class="titik">:</td>
        <td><?= (isset($rapor['jenis_kelamin']) && $rapor['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
        <td class="label">Wali Kelas</td><td class="titik">:</td>
        <td><?= e($info_kelas['wali_kelas'] ?? '-') ?></td>
    </tr>
</table>

<div class="seksi-judul">I. Nilai Hasil Belajar</div>
<table class="rapor-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:24px">No</th>
            <th rowspan="2">Mata Pelajaran</th>
            <th rowspan="2" style="width:40px">KKM</th>
            <th colspan="2">Nilai Hasil Belajar</th>
        </tr>
        <tr>
            <th style="width:50px">Angka</th>
            <th style="width:75px">Predikat</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 1; $total_na = 0; $jml_mapel = 0;
    $kelompok_aktif = null; $urutan_grup = 0;

    if ($nilai && mysqli_num_rows($nilai) > 0):
        while ($n = mysqli_fetch_assoc($nilai)):
            $na  = $n['nilai_akhir'] ?? 0;
            $kkm = $n['kkm'] ?? 75;
            $kel = $n['kelompok'] ?? 'Umum';

            if ($kel !== $kelompok_aktif):
                $kelompok_aktif = $kel;
                $urutan_grup++;
    ?>
        <tr class="grup-row">
            <td colspan="5"><?= romawi($urutan_grup) ?>. <?= strtoupper(e($kel)) ?></td>
        </tr>
    <?php endif; ?>
    <?php
            if (strtolower($kel) === 'produktif') {
                $predikat = $na >= $kkm ? 'Kompeten' : 'Belum Kompeten';
            } else {
                if ($na >= 75)      $predikat = 'Baik';
                elseif ($na >= 60)  $predikat = 'Cukup';
                else                $predikat = 'Kurang';
            }
            $dibawah_kkm = $na < $kkm;
            $total_na += $na; $jml_mapel++;
    ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= e($n['nama_mapel']) ?></td>
            <td class="text-center"><?= $kkm ?></td>
            <td class="text-center <?= $dibawah_kkm ? 'merah' : '' ?>"><?= number_format((float)$na, 0) ?></td>
            <td class="text-center <?= $dibawah_kkm ? 'merah' : '' ?>"><?= $predikat ?></td>
        </tr>
    <?php
        endwhile;
    else:
    ?>
        <tr><td colspan="5" class="text-center">Belum ada data nilai akademik.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<p style="font-size:10.5px; margin-bottom:8px;">
    Rata-rata Nilai: <strong><?= $jml_mapel > 0 ? round($total_na / $jml_mapel, 2) : 0 ?></strong>
</p>

<div class="seksi-judul">II. Pengembangan Diri, Kepribadian dan Ketidakhadiran</div>
<table class="rapor-table">
    <thead>
        <tr>
            <th colspan="2">Komponen</th>
            <th style="width:100px">Predikat</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="width:155px">Kegiatan Pengembangan Diri</td>
            <td>1. <?= !empty($eskul) ? e($eskul) : '-' ?></td>
            <td class="text-center"><?= !empty($eskul) ? 'Baik' : '-' ?></td>
        </tr>
        <tr>
            <td rowspan="3">Kepribadian</td>
            <td>1. Kerajinan</td>
            <td class="text-center"><?= e($kerajinan) ?></td>
        </tr>
        <tr><td>2. Kelakuan</td><td class="text-center"><?= e($kelakuan) ?></td></tr>
        <tr><td>3. Kerapihan</td><td class="text-center"><?= e($kerapihan) ?></td></tr>
        <tr>
            <td rowspan="3">Ketidakhadiran</td>
            <td>1. Sakit</td><td class="text-center"><?= $sakit ?> hari</td>
        </tr>
        <tr><td>2. Izin</td><td class="text-center"><?= $izin ?> hari</td></tr>
        <tr><td>3. Tanpa Keterangan</td><td class="text-center"><?= $alpa ?> hari</td></tr>
    </tbody>
</table>

<div class="seksi-judul">III. Catatan Untuk Orang Tua / Wali</div>
<div class="catatan-box">
    <?= !empty($rapor['catatan']) ? nl2br(e($rapor['catatan'])) : 'Tingkatkan terus prestasimu dan pertahankan semangat belajarmu.' ?>
</div>

<?php
$status_rapor = isset($rapor['status']) ? strtolower(trim($rapor['status'])) : '';
if ($status_rapor === 'naik' || $status_rapor === 'tinggal'):
?>
<div class="seksi-judul">IV. Status Kenaikan</div>
<div class="catatan-box" style="min-height:auto;">
    <?php if ($status_rapor === 'naik'): ?>
        <strong style="color:green">NAIK KELAS</strong> — Siswa dinyatakan naik ke kelas berikutnya.
    <?php else: ?>
        <strong style="color:#E11D48">TINGGAL KELAS</strong> — Siswa dinyatakan mengulang di kelas yang sama.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="footer-ttd">
    <div class="tgl">
        <table class="tgl-table">
            <tr>
                <td class="tgl-label">Diberikan di</td>
                <td class="tgl-titik">:</td>
                <td>Palopo</td>
            </tr>
            <tr>
                <td class="tgl-label">Tanggal</td>
                <td class="tgl-titik">:</td>
                <td><?= tanggal_indo() ?></td>
            </tr>
        </table>
    </div>
    <table>
        <tr>
            <td style="width:33%">Mengetahui,<br>Kepala Sekolah,</td>
            <td style="width:34%">Orang Tua / Wali,</td>
            <td style="width:33%">Wali Kelas,</td>
        </tr>
        <tr>
            <td>
                <div class="garis"></div>
                <div class="nama"><?= e($setting['nama_kepsek'] ?? 'Nama Belum Diatur') ?></div>
                <div>NIP. <?= e($setting['nip_kepsek'] ?? '-') ?></div>
            </td>
            <td>
                <div class="garis"></div>
                <div class="nama"><?= e($rapor['nama_ortu'] ?? '') ?></div>
                <?php if (!empty($rapor['no_hp_ortu'])): ?><div><?= e($rapor['no_hp_ortu']) ?></div><?php endif; ?>
            </td>
            <td>
                <div class="garis"></div>
                <div class="nama"><?= e($info_kelas['wali_kelas'] ?? '____________________') ?></div>
            </td>
        </tr>
    </table>
</div>

</div><!-- .rapor-page -->

<?php endwhile; ?>

<?php else: ?>
<div class="no-print" style="padding:30px;font-family:Arial;">
    <p>Belum ada data rapor untuk siswa <strong><?= e($nama) ?></strong>
    di kelas <strong><?= e($info_kelas['nama_kelas']) ?></strong>
    semester <?= format_semester($semester) ?> tahun ajaran <?= e($ta) ?>.</p>
    <p><a href="cetak_kelas.php?action=form">&larr; Kembali ke pencarian</a></p>
</div>
<?php endif; ?>

</body>
</html>

