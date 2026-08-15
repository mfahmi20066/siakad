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
$title = "Cetak Data Siswa";
include '../../includes/header.php';
include '../../includes/sidebar_admin.php';
?>

<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-print text-icon me-2"></i>Cetak / Export Data Siswa</h4>
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
                            <p class="text-muted small">Tampilkan data semua siswa lengkap dengan 
                               kelas dan wali kelas.</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="cetak_siswa.php?action=cetak&format=pdf" 
                                   class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-print"></i> Cetak
                                </a>
                                <a href="cetak_siswa.php?action=cetak&format=pdf&download=1" 
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
                            <p class="text-muted small">Download data semua siswa dalam format Excel (.xlsx).</p>
                            <a href="cetak_siswa.php?action=cetak&format=excel" 
                               class="btn btn-success">
                                <i class="fas fa-download"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Data Siswa
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<?php
exit;
endif;

// halaman cetak: menampilkan / download data siswa

// ambil setting sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

// mode excel (xlsx)
if ($format === 'excel'):

require_once __DIR__ . '/../../config/helper_xlsx.php';

$rows = [];
$rows[] = ['PEMERINTAH KOTA PALOPO'];
$rows[] = ['DINAS PENDIDIKAN'];
$rows[] = ['SMA NEGERI 4 PALOPO'];
$rows[] = [$setting['alamat_sekolah'] ?? '-'];
$rows[] = ['DATA SISWA'];
$rows[] = ['Tahun Pelajaran ' . ($setting['tahun_pelajaran'] ?? (date('Y') . '/' . (date('Y')+1))) . ' - Dicetak pada ' . tanggal_indo()];
$rows[] = [];
$rows[] = ['No', 'NIS', 'NISN', 'Nama Lengkap', 'Jenis Kelamin', 'Kelas', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'No. HP', 'Orang Tua / Wali', 'No. HP Ortu', 'Wali Kelas'];
$headerIdx = count($rows);

$siswa_query = mysqli_query($koneksi,
    "SELECT s.*, k.nama_kelas, k.tingkat, g.nama AS wali_kelas
     FROM siswa s
     LEFT JOIN kelas k ON s.kelas_id = k.id
     LEFT JOIN guru g ON k.wali_kelas = g.id
     ORDER BY k.tingkat, k.nama_kelas, s.nama");

$no = 0;
while ($siswa = mysqli_fetch_assoc($siswa_query)):
    $no++;
    $jk  = ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
    $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00')
           ? tanggal_indo($siswa['tanggal_lahir']) : '-';
    $rows[] = [$no, $siswa['nis'] ?? '-', $siswa['nisn'] ?? '-', $siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-', $jk, $siswa['nama_kelas'] ?? '-', $siswa['tempat_lahir'] ?? '-', $tgl, $siswa['alamat'] ?? '-', $siswa['no_hp'] ?? '-', $siswa['nama_ortu'] ?? '-', $siswa['no_hp_ortu'] ?? '-', $siswa['wali_kelas'] ?? '-'];
endwhile;
if ($no == 0):
    $rows[] = ['Tidak ada data siswa.'];
endif;

export_xlsx('Data_Siswa_' . date('Y-m-d'), ['Data Siswa' => ['rows' => $rows, 'header_row' => $headerIdx]]);
exit;
endif;

// mode unduh pdf (dompdf), format seragam sama cetak rapor
if ($format === 'pdf' && isset($_GET['download']) && $_GET['download'] === '1'):

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../config/helper_pdf.php';
$logo_src = pdf_logo_data_uri(__DIR__ . '/../../assets/img/logo-sekolah.png');

$siswa_query = mysqli_query($koneksi,
    "SELECT s.*, k.nama_kelas, k.tingkat, g.nama AS wali_kelas
     FROM siswa s
     LEFT JOIN kelas k ON s.kelas_id = k.id
     LEFT JOIN guru g ON k.wali_kelas = g.id
     ORDER BY k.tingkat, k.nama_kelas, s.nama");
$total_siswa = mysqli_num_rows($siswa_query);

$tbody = '';
$no = 0;
while ($siswa = mysqli_fetch_assoc($siswa_query)):
    $no++;
    $jk = ($siswa['jenis_kelamin'] == 'L') ? 'L' : 'P';
    $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00')
           ? tanggal_indo($siswa['tanggal_lahir']) : '-';
    $tbody .= '<tr>'
        . '<td class="text-center">' . $no . '</td>'
        . '<td>' . e($siswa['nis'] ?? '-') . '</td>'
        . '<td>' . e($siswa['nisn'] ?? '-') . '</td>'
        . '<td><strong>' . e($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') . '</strong></td>'
        . '<td class="text-center">' . $jk . '</td>'
        . '<td>' . e($siswa['nama_kelas'] ?? '-') . '</td>'
        . '<td>' . e($siswa['tempat_lahir'] ?? '-') . '</td>'
        . '<td>' . $tgl . '</td>'
        . '<td>' . e($siswa['alamat'] ?? '-') . '</td>'
        . '<td>' . e($siswa['no_hp'] ?? '-') . '</td>'
        . '<td>' . e($siswa['nama_ortu'] ?? '-') . '</td>'
        . '<td>' . e($siswa['no_hp_ortu'] ?? '-') . '</td>'
        . '<td>' . e($siswa['wali_kelas'] ?? '-') . '</td>'
        . '</tr>';
endwhile;
if ($no == 0):
    $tbody .= '<tr><td colspan="13" class="text-center">Tidak ada data siswa.</td></tr>';
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

$html .= '<div class="judul">DATA SISWA</div>';
$html .= '<div class="info">Total Siswa: <strong>' . $total_siswa . '</strong> Orang</div>';

$html .= '<table class="data-table"><thead><tr>'
    . '<th style="width:20px">No</th><th style="width:55px">NIS</th><th style="width:50px">NISN</th><th>Nama Lengkap</th>'
    . '<th style="width:24px">JK</th><th style="width:42px">Kelas</th><th style="width:60px">Tempat Lahir</th>'
    . '<th style="width:62px">Tgl Lahir</th><th style="width:110px">Alamat</th><th style="width:55px">No. HP</th>'
    . '<th style="width:70px">Orang Tua / Wali</th><th style="width:55px">No. HP Ortu</th><th style="width:80px">Wali Kelas</th>'
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
$dompdf->stream('Data_Siswa_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
exit;
endif;

// mode pdf (html + css print-friendly)
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Siswa — SMA Negeri 4 Palopo</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    font-size: 10.5px;
    padding: 15px 20px;
    color: #000;
}

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

.info-ringkasan {
    text-align: center;
    margin-bottom: 15px;
    font-size: 11px;
}

table.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5px;
}
.data-table th, .data-table td {
    border: 1px solid #000;
    padding: 2px 4px;
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
    <a href="cetak_siswa.php?action=form" class="btn-back">&larr; Kembali</a>
    <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="kop">
    <img class="logo-kiri" src="../../assets/img/logo-sekolah.png"
         onerror="this.style.display='none'" alt="Logo">
    <div class="kop-text">
        <div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>
        <div class="sekolah">SMA NEGERI 4 PALOPO</div>
        <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div>
        <div class="alamat"><?= e(trim((!empty($setting['telepon']) ? 'Telp. ' . $setting['telepon'] : '') . (!empty($setting['email']) ? ' | Email: ' . $setting['email'] : ''))) ?></div>
    </div></div>

<div class="judul">DATA SISWA</div>

<?php
$siswa_query = mysqli_query($koneksi,
    "SELECT s.*, k.nama_kelas, k.tingkat, g.nama AS wali_kelas
     FROM siswa s
     LEFT JOIN kelas k ON s.kelas_id = k.id
     LEFT JOIN guru g ON k.wali_kelas = g.id
     ORDER BY k.tingkat, k.nama_kelas, s.nama");

$total_siswa = mysqli_num_rows($siswa_query);
?>
<div class="info-ringkasan">
    Total Siswa: <strong><?= $total_siswa ?></strong> Orang
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width:22px">No</th>
            <th style="width:60px">NIS</th>
            <th style="width:50px">NISN</th>
            <th>Nama Lengkap</th>
            <th style="width:50px">JK</th>
            <th style="width:50px">Kelas</th>
            <th style="width:60px">Tempat Lahir</th>
            <th style="width:65px">Tgl Lahir</th>
            <th style="width:100px">Alamat</th>
            <th style="width:55px">No. HP</th>
            <th style="width:80px">Orang Tua / Wali</th>
            <th style="width:55px">No. HP Ortu</th>
            <th>Wali Kelas</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 0;
    while ($siswa = mysqli_fetch_assoc($siswa_query)):
        $no++;
        $jk = ($siswa['jenis_kelamin'] == 'L') ? 'L' : 'P';
        $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00') 
               ? tanggal_indo($siswa['tanggal_lahir']) : '-';
    ?>
    <tr>
        <td class="text-center"><?= $no ?></td>
        <td><?= e($siswa['nis'] ?? '-') ?></td>
        <td><?= e($siswa['nisn'] ?? '-') ?></td>
        <td><strong><?= e($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></strong></td>
        <td class="text-center"><?= $jk ?></td>
        <td><?= e($siswa['nama_kelas'] ?? '-') ?></td>
        <td><?= e($siswa['tempat_lahir'] ?? '-') ?></td>
        <td><?= $tgl ?></td>
        <td><?= e($siswa['alamat'] ?? '-') ?></td>
        <td><?= e($siswa['no_hp'] ?? '-') ?></td>
        <td><?= e($siswa['nama_ortu'] ?? '-') ?></td>
        <td><?= e($siswa['no_hp_ortu'] ?? '-') ?></td>
        <td><?= e($siswa['wali_kelas'] ?? '-') ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if ($no == 0): ?>
    <tr>
        <td colspan="13" class="text-center">Tidak ada data siswa.</td>
    </tr>
    <?php endif; ?>
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

