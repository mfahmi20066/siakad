<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$action = isset($_GET['action']) ? $_GET['action'] : 'form';
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// halaman form: pilih format cetak
if ($action === 'form'):
?>
<?php
$title = "Cetak Data Guru";
include '../../includes/header.php';
include '../../includes/sidebar_admin.php';
?>

<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-print text-icon me-2"></i>Cetak / Export Data Guru</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-sliders-h"></i> Pilih Format Cetak
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 48px;"></i>
                            <h5 class="mt-3">Cetak / Unduh PDF</h5>
                            <p class="text-muted small">Tampilkan data semua guru lengkap dengan 
                               wali kelas dan mata pelajaran yang diampu.</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="cetak_guru.php?action=cetak&format=pdf" 
                                   class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-print"></i> Cetak
                                </a>
                                <a href="cetak_guru.php?action=cetak&format=pdf&download=1" 
                                   class="btn btn-danger">
                                    <i class="fas fa-file-pdf"></i> Unduh PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-excel text-success" style="font-size: 48px;"></i>
                            <h5 class="mt-3">Export Excel</h5>
                            <p class="text-muted small">Download data semua guru dalam format Excel (.xlsx).</p>
                            <a href="cetak_guru.php?action=cetak&format=excel" 
                               class="btn btn-success">
                                <i class="fas fa-download"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Data Guru
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<?php
exit;
endif;

// halaman cetak: menampilkan / download data guru

// ambil setting sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

// mode excel (xlsx)
if ($format === 'excel'):

require_once __DIR__ . '/../../config/helper_xlsx.php';

$rows = [];
$rows[] = ['DATA GURU SMA NEGERI 4 PALOPO'];
$rows[] = [];
$rows[] = ['No', 'NIP', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'No. HP', 'Email', 'Wali Kelas', 'Mata Pelajaran'];
$headerIdx = count($rows);

// ambil semua guru
$guru_query = mysqli_query($koneksi,
    "SELECT * FROM guru ORDER BY nama");

$no = 0;
while ($guru = mysqli_fetch_assoc($guru_query)):
    $no++;
    $id = $guru['id'];
    $jk = ($guru['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
    $tgl = (!empty($guru['tanggal_lahir']) && $guru['tanggal_lahir'] != '0000-00-00')
           ? tanggal_indo($guru['tanggal_lahir']) : '-';

    // kelas yang diwalikan
    $wali_query = mysqli_query($koneksi,
        "SELECT nama_kelas FROM kelas WHERE wali_kelas = '$id' ORDER BY tingkat, nama_kelas");
    $wali_kelas = [];
    while ($w = mysqli_fetch_assoc($wali_query)) {
        $wali_kelas[] = $w['nama_kelas'];
    }
    $wali_str = !empty($wali_kelas) ? implode(', ', $wali_kelas) : '-';

    // mapel yang diampu
    $mapel_query = mysqli_query($koneksi,
        "SELECT m.nama_mapel 
         FROM mata_pelajaran m 
         WHERE m.guru_id = '$id' 
         ORDER BY m.nama_mapel");
    $mapel_list = [];
    while ($m = mysqli_fetch_assoc($mapel_query)) {
        $mapel_list[] = $m['nama_mapel'];
    }
    // ambil juga dari jadwal, jaga-jaga kalo ada mapel cuma di jadwal
    if (!empty($mapel_list)) {
        $escaped = array_map(function($v) { return mysqli_real_escape_string($GLOBALS['koneksi'], $v); }, $mapel_list);
        $exclude_sql = "AND mp.nama_mapel NOT IN ('" . implode("','", $escaped) . "')";
    } else {
        $exclude_sql = "";
    }
    $jadwal_mapel_query = mysqli_query($koneksi,
        "SELECT DISTINCT mp.nama_mapel 
         FROM jadwal j 
         JOIN mata_pelajaran mp ON j.mapel_id = mp.id 
         WHERE j.guru_id = '$id' $exclude_sql
         ORDER BY mp.nama_mapel");
    while ($jm = mysqli_fetch_assoc($jadwal_mapel_query)) {
        $mapel_list[] = $jm['nama_mapel'];
    }
    $mapel_str = !empty($mapel_list) ? implode(', ', $mapel_list) : '-';

    $rows[] = [$no, $guru['nip'] ?? '-', $guru['nama_lengkap'] ?? $guru['nama'] ?? '-', $jk, $guru['tempat_lahir'] ?? '-', $tgl, $guru['alamat'] ?? '-', $guru['no_hp'] ?? '-', $guru['email'] ?? '-', $wali_str, $mapel_str];
endwhile;

if ($no == 0):
    $rows[] = ['Tidak ada data guru.'];
endif;

export_xlsx('Data_Guru_' . date('Y-m-d'), ['Data Guru' => ['rows' => $rows, 'header_row' => $headerIdx]]);
exit;
endif;

// mode unduh pdf (dompdf), formatnya seragam sama cetak rapor
if ($format === 'pdf' && isset($_GET['download']) && $_GET['download'] === '1'):

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../config/helper_pdf.php';
$logo_src = pdf_logo_data_uri(__DIR__ . '/../../assets/img/logo-sekolah.png');

$guru_query = mysqli_query($koneksi,
    "SELECT * FROM guru ORDER BY nama");
$total_guru = mysqli_num_rows($guru_query);

$tbody = '';
$no = 0;
while ($guru = mysqli_fetch_assoc($guru_query)):
    $no++;
    $id = $guru['id'];
    $jk = ($guru['jenis_kelamin'] == 'L') ? 'L' : 'P';
    $tgl = (!empty($guru['tanggal_lahir']) && $guru['tanggal_lahir'] != '0000-00-00')
           ? tanggal_indo($guru['tanggal_lahir']) : '-';

    // kelas yang diwalikan
    $wali_query = mysqli_query($koneksi,
        "SELECT nama_kelas FROM kelas WHERE wali_kelas = '$id' ORDER BY tingkat, nama_kelas");
    $wali_kelas = [];
    while ($w = mysqli_fetch_assoc($wali_query)) $wali_kelas[] = $w['nama_kelas'];
    $wali_str = !empty($wali_kelas) ? implode(', ', $wali_kelas) : '-';

    // mapel yang diampu (dari mata_pelajaran + jadwal)
    $mapel_query = mysqli_query($koneksi,
        "SELECT m.nama_mapel FROM mata_pelajaran m WHERE m.guru_id = '$id' ORDER BY m.nama_mapel");
    $mapel_list = [];
    while ($m = mysqli_fetch_assoc($mapel_query)) $mapel_list[] = $m['nama_mapel'];
    $exclude = !empty($mapel_list)
        ? "AND mp.nama_mapel NOT IN ('" . implode("','", array_map(function($v) use ($koneksi) {
            return mysqli_real_escape_string($koneksi, $v);
        }, $mapel_list)) . "')"
        : '';
    $jadwal_mapel_query = mysqli_query($koneksi,
        "SELECT DISTINCT mp.nama_mapel
         FROM jadwal j JOIN mata_pelajaran mp ON j.mapel_id = mp.id
         WHERE j.guru_id = '$id' $exclude ORDER BY mp.nama_mapel");
    while ($jm = mysqli_fetch_assoc($jadwal_mapel_query)) $mapel_list[] = $jm['nama_mapel'];
    $mapel_str = !empty($mapel_list) ? implode(', ', $mapel_list) : '-';

    $tbody .= '<tr>'
        . '<td class="text-center">' . $no . '</td>'
        . '<td>' . e($guru['nip'] ?? '-') . '</td>'
        . '<td><strong>' . e($guru['nama_lengkap'] ?? $guru['nama'] ?? '-') . '</strong></td>'
        . '<td class="text-center">' . $jk . '</td>'
        . '<td>' . e($guru['tempat_lahir'] ?? '-') . '</td>'
        . '<td>' . $tgl . '</td>'
        . '<td>' . e($guru['alamat'] ?? '-') . '</td>'
        . '<td>' . e($guru['no_hp'] ?? '-') . '</td>'
        . '<td>' . e($guru['email'] ?? '-') . '</td>'
        . '<td>' . e($wali_str) . '</td>'
        . '<td>' . e($mapel_str) . '</td>'
        . '</tr>';
endwhile;
if ($no == 0):
    $tbody .= '<tr><td colspan="11" class="text-center">Tidak ada data guru.</td></tr>';
endif;

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size:10pt; color:#000; line-height:1.4; }
@page { size: A4 landscape; margin: 1.2cm; }
.kop-table { width:100%; border-collapse:collapse; border-bottom:3px double #000; padding-bottom:8px; margin-bottom:12px; }
.kop-table td { border:none; vertical-align:middle; padding:0 4px; }
.kop-table .logo-kiri { width:90px; text-align:left; }
.kop-table img { width:86px; height:86px; }
.kop-table .kop-text { text-align:center; line-height:1.3; padding-right:90px; }
.kop-text .instansi { font-size:13px; }
.kop-text .sekolah { font-size:21px; font-weight:bold; text-transform:uppercase; }
.kop-text .alamat { font-size:12px; }
.judul { text-align:center; font-size:15pt; font-weight:bold; text-decoration:underline; margin:10px 0 8px; }
.info { text-align:center; margin-bottom:10px; font-size:10.5pt; }
table.data-table { width:100%; border-collapse:collapse; margin-bottom:8px; }
.data-table th, .data-table td { border:1px solid #000; padding:3px 5px; font-size:8.5pt; }
.data-table th { background:#F5F7FB; text-align:center; font-weight:bold; }
.data-table td { vertical-align:top; }
.text-center { text-align:center; }
.footer-ttd { margin-top:20px; width:100%; }
.footer-ttd .tgl { text-align:right; margin-bottom:3px; font-size:10pt; }
.footer-ttd table { width:100%; border-collapse:collapse; margin-top:6px; }
.footer-ttd td { text-align:center; font-size:10pt; vertical-align:top; padding:0 8px; width:50%; }
.footer-ttd .garis { margin-top:50px; border-bottom:1px solid #000; }
.footer-ttd .nama { margin-top:2px; font-weight:bold; text-decoration:underline; }
</style></head><body>';

$html .= '<table class="kop-table"><tr>'
    . '<td class="logo-kiri">' . ($logo_src ? '<img src="' . $logo_src . '">' : '') . '</td>'
    . '<td class="kop-text"><div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>'
    . '<div class="sekolah">SMA NEGERI 4 PALOPO</div>'
    . '<div class="alamat">' . e($setting['alamat_sekolah'] ?? '-') . '</div>'
    . (trim(($setting['telepon'] ?? '') . ($setting['email'] ?? '')) !== '' ? '<div class="alamat">' . e(trim((($setting['telepon'] ?? '') ? 'Telp. ' . $setting['telepon'] : '') . (($setting['email'] ?? '') ? ' | Email: ' . $setting['email'] : ''))) . '</div>' : '')
    . '</td></tr></table>';

$html .= '<div class="judul">DATA GURU</div>';
$html .= '<div class="info">Total Guru: <strong>' . $total_guru . '</strong> Orang</div>';

$html .= '<table class="data-table"><thead><tr>'
    . '<th style="width:20px">No</th><th style="width:60px">NIP</th><th>Nama Lengkap</th>'
    . '<th style="width:24px">JK</th><th style="width:60px">Tempat Lahir</th><th style="width:62px">Tgl Lahir</th>'
    . '<th style="width:110px">Alamat</th><th style="width:55px">No. HP</th><th style="width:90px">Email</th>'
    . '<th style="width:70px">Wali Kelas</th><th>Mata Pelajaran</th>'
    . '</tr></thead><tbody>' . $tbody . '</tbody></table>';

$html .= '<div class="footer-ttd"><div class="tgl">Dikeluarkan di : Palopo<br>Tanggal : ' . tanggal_indo() . '</div>';
$html .= '<table><tr><td>Mengetahui,<br>Kepala Sekolah,</td><td>Staf Tata Usaha,</td></tr>';
$html .= '<tr><td><div class="garis"></div><div class="nama">' . e($setting['nama_kepsek'] ?? 'Nama Belum Diatur') . '</div><div>NIP. ' . e($setting['nip_kepsek'] ?? '-') . '</div></td>';
$html .= '<td><div class="garis"></div><div class="nama">&nbsp;</div></td></tr></table></div>';

$html .= '</body></html>';

$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('Data_Guru_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
exit;
endif;

// mode pdf (html + css print-friendly)
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Guru — SMA Negeri 4 Palopo</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    padding: 15px 20px;
    color: #000;
}

/* tombol non-print */
.no-print { margin-bottom: 15px; }
.btn-print {
    display: inline-block;
    padding: 8px 20px;
    background: #163A63;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
}
.btn-back {
    display: inline-block;
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

/* kop surat */
.kop {
    display: flex;
    align-items: center;
    border-bottom: 3px double #000;
    padding-bottom: 8px;
    margin-bottom: 15px;
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
    font-size: 14px;
    font-weight: bold;
    text-decoration: underline;
    margin: 10px 0 15px;
}

/* info ringkasan */
.info-ringkasan {
    text-align: center;
    margin-bottom: 15px;
    font-size: 11px;
}

/* tabel */
table.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5px;
}
.data-table th, .data-table td {
    border: 1px solid #000;
    padding: 3px 5px;
    font-size: 10px;
}
.data-table th {
    background: #F5F7FB;
    text-align: center;
    font-weight: bold;
}
.data-table td {
    vertical-align: top;
}
.text-center { text-align: center; }
.text-muted { color: #888; font-style: italic; }

/* footer ttd */
.footer-ttd {
    margin-top: 40px;
    width: 100%;
}
.footer-ttd .tgl {
    text-align: right;
    margin-bottom: 3px;
    font-size: 11px;
}
.footer-ttd table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 3px;
}
.footer-ttd td {
    text-align: center;
    font-size: 11px;
    vertical-align: top;
    padding: 0 8px;
    width: 50%;
}
.footer-ttd .garis {
    margin-top: 50px;
    border-bottom: 1px solid #000;
}
.footer-ttd .nama {
    margin-top: 2px;
    font-weight: bold;
    text-decoration: underline;
}

@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    .btn-print, .btn-back, .no-print { display: none !important; }
    body { padding: 5px 10px; }
}

@page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
</style>
</head>
<body>

<div class="no-print">
    <a href="cetak_guru.php?action=form" class="btn-back">&larr; Kembali</a>
    <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<!-- Kop surat -->
<div class="kop">
    <img class="logo-kiri" src="../../assets/img/logo-sekolah.png"
         onerror="this.style.display='none'" alt="Logo">
    <div class="kop-text">
        <div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>
        <div class="sekolah">SMA NEGERI 4 PALOPO</div>
        <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div>
        <div class="alamat"><?= e(trim((!empty($setting['telepon']) ? 'Telp. ' . $setting['telepon'] : '') . (!empty($setting['email']) ? ' | Email: ' . $setting['email'] : ''))) ?></div>
    </div></div>

<div class="judul">DATA GURU</div>

<?php
// ambil semua guru
$guru_query = mysqli_query($koneksi,
    "SELECT * FROM guru ORDER BY nama");

$total_guru = mysqli_num_rows($guru_query);
?>
<div class="info-ringkasan">
    Total Guru: <strong><?= $total_guru ?></strong> Orang
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:25px">No</th>
            <th style="width:70px">NIP</th>
            <th>Nama Lengkap</th>
            <th style="width:55px">JK</th>
            <th style="width:65px">Tempat Lahir</th>
            <th style="width:70px">Tgl Lahir</th>
            <th style="width:110px">Alamat</th>
            <th style="width:60px">No. HP</th>
            <th>Wali Kelas</th>
            <th>Mata Pelajaran</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 0;
    while ($guru = mysqli_fetch_assoc($guru_query)):
        $no++;
        $id = $guru['id'];
        $jk = ($guru['jenis_kelamin'] == 'L') ? 'L' : 'P';
        $tgl = (!empty($guru['tanggal_lahir']) && $guru['tanggal_lahir'] != '0000-00-00') 
               ? tanggal_indo($guru['tanggal_lahir']) : '-';

        // kelas yang diwalikan
        $wali_query = mysqli_query($koneksi,
            "SELECT nama_kelas FROM kelas WHERE wali_kelas = '$id' ORDER BY tingkat, nama_kelas");
        $wali_kelas = [];
        while ($w = mysqli_fetch_assoc($wali_query)) {
            $wali_kelas[] = $w['nama_kelas'];
        }
        $wali_str = !empty($wali_kelas) ? implode(', ', $wali_kelas) : '<span class="text-muted">-</span>';

        // mapel yang diampu
        $mapel_query = mysqli_query($koneksi,
            "SELECT m.nama_mapel 
             FROM mata_pelajaran m 
             WHERE m.guru_id = '$id' 
             ORDER BY m.nama_mapel");
        $mapel_list = [];
        while ($m = mysqli_fetch_assoc($mapel_query)) {
            $mapel_list[] = $m['nama_mapel'];
        }
        // plus dari jadwal
        $exclude = !empty($mapel_list) 
            ? "AND mp.nama_mapel NOT IN ('" . implode("','", array_map(function($v) use ($koneksi) { 
                return mysqli_real_escape_string($koneksi, $v); 
            }, $mapel_list)) . "')" 
            : '';
        $jadwal_mapel_query = mysqli_query($koneksi,
            "SELECT DISTINCT mp.nama_mapel 
             FROM jadwal j 
             JOIN mata_pelajaran mp ON j.mapel_id = mp.id 
             WHERE j.guru_id = '$id' $exclude
             ORDER BY mp.nama_mapel");
        while ($jm = mysqli_fetch_assoc($jadwal_mapel_query)) {
            $mapel_list[] = $jm['nama_mapel'];
        }
        $mapel_str = !empty($mapel_list) ? implode(', ', $mapel_list) : '<span class="text-muted">-</span>';
    ?>
    <tr>
        <td class="text-center"><?= $no ?></td>
        <td><?= e($guru['nip'] ?? '-') ?></td>
        <td><strong><?= e($guru['nama_lengkap'] ?? $guru['nama'] ?? '-') ?></strong></td>
        <td class="text-center"><?= $jk ?></td>
        <td><?= e($guru['tempat_lahir'] ?? '-') ?></td>
        <td><?= $tgl ?></td>
        <td><?= e($guru['alamat'] ?? '-') ?></td>
        <td><?= e($guru['no_hp'] ?? '-') ?></td>
        <td><?= e($wali_str) ?></td>
        <td><?= e($mapel_str) ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<div class="footer-ttd">
    <div class="tgl">
        Dikeluarkan di : Palopo<br>
        Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= tanggal_indo() ?>
    </div>
    <table>
        <tr>
            <td>Mengetahui,<br>Kepala Sekolah,</td>
            <td>Staf Tata Usaha,</td>
        </tr>
        <tr>
            <td>
                <div class="garis"></div>
                <div class="nama"><?= e($setting['nama_kepsek'] ?? 'Nama Belum Diatur') ?></div>
                <div>NIP. <?= e($setting['nip_kepsek'] ?? '-') ?></div>
            </td>
            <td>
                <div class="garis"></div>
                <div class="nama">&nbsp;</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>

