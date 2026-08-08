<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$action = isset($_GET['action']) ? $_GET['action'] : 'form';
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// ===================================================================
// Halaman FORM — Pilih format cetak
// ===================================================================
if ($action === 'form'):
?>
<?php
$title = "Cetak Data Siswa";
include '../../includes/header.php';
include '../../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-print text-gold me-2"></i>Cetak / Export Data Siswa</h4>
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
                            <h5 class="mt-3">Cetak PDF</h5>
                            <p class="text-muted small">Tampilkan data semua siswa lengkap dengan 
                               kelas dan wali kelas.</p>
                            <a href="cetak_siswa.php?action=cetak&format=pdf" 
                               class="btn btn-danger" target="_blank">
                                <i class="fas fa-print"></i> Cetak PDF
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-excel text-success" style="font-size: 48px;"></i>
                            <h5 class="mt-3">Export Excel</h5>
                            <p class="text-muted small">Download data semua siswa dalam format Excel (.xls).</p>
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

// ===================================================================
// Halaman CETAK — Menampilkan / mendownload data siswa
// ===================================================================

// Ambil setting sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

// ===================================================================
// MODE EXCEL
// ===================================================================
if ($format === 'excel'):

$filename = "Data_Siswa_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    th { background-color: #163A63; color: #ffffff; font-weight: bold; text-align: center; }
    td { vertical-align: top; }
</style>
</head>
<body>
    <table border="1">
        <tr>
            <td colspan="11" style="font-size: 10px; text-align: center; border: none;">
                PEMERINTAH KOTA PALOPO
            </td>
        </tr>
        <tr>
            <td colspan="11" style="font-size: 10px; text-align: center; border: none;">
                DINAS PENDIDIKAN
            </td>
        </tr>
        <tr>
            <td colspan="11" style="font-size: 18px; font-weight: bold; text-align: center; border: none; color: #163A63;">
                SMA NEGERI 4 PALOPO
            </td>
        </tr>
        <tr>
            <td colspan="11" style="font-size: 10px; text-align: center; border: none;">
                <?= htmlspecialchars($setting['alamat_sekolah'] ?? '-') ?>
            </td>
        </tr>
        <tr>
            <td colspan="11" style="border: none;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="11" style="font-size: 14px; font-weight: bold; text-align: center; border: none;">
                DATA SISWA
            </td>
        </tr>
        <tr>
            <td colspan="11" style="text-align: center; border: none;">
                Tahun Pelajaran <?= htmlspecialchars($setting['tahun_pelajaran'] ?? date('Y') . '/' . (date('Y')+1)) ?>
                &mdash; Dicetak pada <?= tanggal_indo() ?>
            </td>
        </tr>
        <tr>
            <td colspan="11" style="border: none;"></td>
        </tr>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>NISN</th>
            <th>Nama Lengkap</th>
            <th>Jenis Kelamin</th>
            <th>Kelas</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Alamat</th>
            <th>No. HP</th>
            <th>Wali Kelas</th>
        </tr>

        <?php
        $siswa_query = mysqli_query($koneksi,
            "SELECT s.*, k.nama_kelas, k.tingkat, g.nama AS wali_kelas
             FROM siswa s
             LEFT JOIN kelas k ON s.kelas_id = k.id
             LEFT JOIN guru g ON k.wali_kelas = g.id
             ORDER BY k.tingkat, k.nama_kelas, s.nama");

        $no = 0;
        while ($siswa = mysqli_fetch_assoc($siswa_query)):
            $no++;
            $jk = ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
            $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00') 
                   ? tanggal_indo($siswa['tanggal_lahir']) : '-';
        ?>
        <tr>
            <td align="right"><?= $no ?></td>
            <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></td>
            <td><?= $jk ?></td>
            <td><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></td>
            <td><?= $tgl ?></td>
            <td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['wali_kelas'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
        <?php
        if ($no == 0):
        ?>
        <tr>
            <td colspan="11" align="center">Tidak ada data siswa.</td>
        </tr>
        <?php endif; ?>
    </table>
</body>
</html>
<?php
exit;
endif;

// ===================================================================
// MODE PDF (HTML + CSS print-friendly)
// ===================================================================
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
.kop img { width: 55px; height: 55px; object-fit: contain; }
.kop .logo-kiri  { margin-right: 10px; }
.kop .logo-kanan { margin-left: 10px; }
.kop-text { text-align: center; flex: 1; line-height: 1.25; }
.kop-text .instansi { font-size: 10px; }
.kop-text .sekolah  { font-size: 14px; font-weight: bold; text-transform: uppercase; }
.kop-text .alamat   { font-size: 9px; }

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
        <div class="alamat"><?= htmlspecialchars($setting['alamat_sekolah'] ?? '-') ?></div>
    </div>
    <img class="logo-kanan" src="../../assets/img/logo-sekolah.png"
         onerror="this.style.display='none'" alt="Logo">
</div>

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
        <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
        <td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></td>
        <td><strong><?= htmlspecialchars($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></strong></td>
        <td class="text-center"><?= $jk ?></td>
        <td><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
        <td><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></td>
        <td><?= $tgl ?></td>
        <td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td>
        <td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td>
        <td><?= htmlspecialchars($siswa['wali_kelas'] ?? '-') ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if ($no == 0): ?>
    <tr>
        <td colspan="11" class="text-center">Tidak ada data siswa.</td>
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
                <div class="nama"><?= htmlspecialchars($setting['nama_kepsek'] ?? 'Nama Belum Diatur') ?></div>
                <div>NIP. <?= htmlspecialchars($setting['nip_kepsek'] ?? '-') ?></div>
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

