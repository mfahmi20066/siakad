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
$title = "Cetak Data Siswa per Kelas";
include '../../includes/header.php';
include '../../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-print text-gold me-2"></i>Cetak / Export Data Siswa per Kelas</h4>
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
                            <p class="text-muted small">Tampilkan data semua siswa dikelompokkan per kelas, 
                               dapat dicetak atau disimpan sebagai PDF melalui browser.</p>
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
                            <p class="text-muted small">Download data semua siswa dalam format Excel (.xls). 
                               Data dikelompokkan per kelas dalam sheet yang sama.</p>
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
                <i class="fas fa-arrow-left"></i> Kembali ke Data Kelas
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<?php
exit;
endif;

// ===================================================================
// Halaman CETAK — Menampilkan / mendownload data siswa per kelas
// ===================================================================

// Ambil semua kelas diurutkan berdasarkan tingkat dan nama_kelas
$kelas_list = mysqli_query($koneksi,
    "SELECT k.*, g.nama AS wali 
     FROM kelas k 
     LEFT JOIN guru g ON k.wali_kelas = g.id 
     ORDER BY k.tingkat, k.nama_kelas");

// Ambil setting sekolah
$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

// ===================================================================
// MODE EXCEL
// ===================================================================
if ($format === 'excel'):

// Set header untuk download file Excel
$filename = "Data_Siswa_per_Kelas_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    /* Excel styling */
    th { background-color: #163A63; color: #ffffff; font-weight: bold; text-align: center; }
    td { mso-number-format:'\@'; vertical-align: top; }
    .kelas-header { background-color: #E2E8F0; font-weight: bold; font-size: 14px; }
    .rata-kanan { mso-number-format:'0'; text-align: right; }
</style>
</head>
<body>
    <table border="1">
        <tr>
            <td colspan="10" style="font-size: 10px; text-align: center; border: none;">
                PEMERINTAH KOTA PALOPO
            </td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 10px; text-align: center; border: none;">
                DINAS PENDIDIKAN
            </td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 18px; font-weight: bold; text-align: center; border: none; color: #163A63;">
                SMA NEGERI 4 PALOPO
            </td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 10px; text-align: center; border: none;">
                <?= htmlspecialchars($setting['alamat_sekolah'] ?? '-') ?>
            </td>
        </tr>
        <tr>
            <td colspan="10" style="border: none;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 14px; font-weight: bold; text-align: center; border: none;">
                DATA SISWA PER KELAS
            </td>
        </tr>
        <tr>
            <td colspan="10" style="text-align: center; border: none;">
                Tahun Pelajaran <?= htmlspecialchars($setting['tahun_pelajaran'] ?? date('Y') . '/' . (date('Y')+1)) ?>
                &mdash; Dicetak pada <?= tanggal_indo() ?>
            </td>
        </tr>
        <tr>
            <td colspan="10" style="border: none;"></td>
        </tr>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>NISN</th>
            <th>Nama Lengkap</th>
            <th>Jenis Kelamin</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Alamat</th>
            <th>No. HP</th>
            <th>Wali Kelas</th>
        </tr>

        <?php
        $no_global = 0;
        while ($kelas = mysqli_fetch_assoc($kelas_list)):
            // Ambil siswa per kelas
            $siswa_query = mysqli_query($koneksi,
                "SELECT * FROM siswa 
                 WHERE kelas_id = '{$kelas['id']}' 
                 ORDER BY nama");
            
            $jml_siswa = mysqli_num_rows($siswa_query);
            if ($jml_siswa == 0) continue;
        ?>
        <!-- Baris header kelas -->
        <tr>
            <td colspan="10" class="kelas-header">
                KELAS <?= htmlspecialchars($kelas['nama_kelas']) ?> (Tingkat <?= $kelas['tingkat'] ?>) 
                — Wali Kelas: <?= htmlspecialchars($kelas['wali'] ?? '-') ?>
                — Jumlah: <?= $jml_siswa ?> Siswa
            </td>
        </tr>
        <?php while ($siswa = mysqli_fetch_assoc($siswa_query)): 
            $no_global++;
            $jk = ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
            $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00') 
                   ? tanggal_indo($siswa['tanggal_lahir']) : '-';
        ?>
        <tr>
            <td align="right"><?= $no_global ?></td>
            <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></td>
            <td><?= $jk ?></td>
            <td><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></td>
            <td><?= $tgl ?></td>
            <td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td>
            <td><?= htmlspecialchars($kelas['wali'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
        <?php endwhile; ?>
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
<title>Data Siswa per Kelas — SMA Negeri 4 Palopo</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    padding: 15px 20px;
    color: #000;
}

/* ── Tombol non-print ── */
.no-print {
    margin-bottom: 15px;
}
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

/* ── Kop surat ── */
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

/* ── Blok per kelas ── */
.kelas-section {
    margin-bottom: 25px;
    page-break-inside: avoid;
}
.kelas-header {
    background: #163A63;
    color: #fff;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 5px;
    border-radius: 3px;
}
.kelas-header .badge {
    float: right;
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
}

/* ── Tabel ── */
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

/* ── Footer ttd ── */
.footer-ttd {
    margin-top: 30px;
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
    width: 33%;
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
    .kelas-section { page-break-inside: avoid; }
}

@page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:15px;">
    <a href="cetak_siswa.php?action=form" class="btn-back">&larr; Kembali</a>
    <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<!-- Kop surat -->
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

<div class="judul">DATA SISWA PER KELAS</div>
<p style="text-align:center; margin-bottom:15px; font-size:11px;">
    Tahun Pelajaran <?= htmlspecialchars($setting['tahun_pelajaran'] ?? date('Y') . '/' . (date('Y')+1)) ?>
</p>

<?php
$no_global = 0;
$total_seluruh = 0;

while ($kelas = mysqli_fetch_assoc($kelas_list)):
    // Ambil siswa per kelas
    $siswa_query = mysqli_query($koneksi,
        "SELECT * FROM siswa 
         WHERE kelas_id = '{$kelas['id']}' 
         ORDER BY nama");
    
    $jml_siswa = mysqli_num_rows($siswa_query);
    if ($jml_siswa == 0) continue;
    
    $total_seluruh += $jml_siswa;
?>

<div class="kelas-section">
    <div class="kelas-header">
        KELAS <?= htmlspecialchars($kelas['nama_kelas']) ?> 
        (Tingkat <?= $kelas['tingkat'] ?>)
        <span class="badge"><?= $jml_siswa ?> Siswa</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:80px">NIS</th>
                <th style="width:50px">NISN</th>
                <th>Nama Lengkap</th>
                <th style="width:70px">JK</th>
                <th style="width:80px">Tempat Lahir</th>
                <th style="width:80px">Tgl Lahir</th>
                <th style="width:130px">Alamat</th>
                <th style="width:80px">No. HP</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $no = 1;
        while ($siswa = mysqli_fetch_assoc($siswa_query)): 
            $no_global++;
            $jk = ($siswa['jenis_kelamin'] == 'L') ? 'L' : 'P';
            $tgl = (!empty($siswa['tanggal_lahir']) && $siswa['tanggal_lahir'] != '0000-00-00') 
                   ? tanggal_indo($siswa['tanggal_lahir']) : '-';
        ?>
        <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($siswa['nis'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></td>
            <td><strong><?= htmlspecialchars($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></strong></td>
            <td class="text-center"><?= $jk ?></td>
            <td><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></td>
            <td><?= $tgl ?></td>
            <td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td>
            <td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    
    <p style="font-size:10px; text-align:right; margin-bottom:5px;">
        Wali Kelas: <strong><?= htmlspecialchars($kelas['wali'] ?? 'Belum ditentukan') ?></strong>
    </p>
</div>
<?php endwhile; ?>

<!-- Ringkasan total -->
<p style="text-align:center; font-weight:bold; font-size:12px; margin:15px 0;">
    Total Seluruh Siswa: <?= $total_seluruh ?> Siswa
</p>

<!-- Tanda tangan -->
<div class="footer-ttd">
    <div class="tgl">
        Dikeluarkan di : Palopo<br>
        Tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= tanggal_indo() ?>
    </div>
    <table>
        <tr>
            <td>Mengetahui,<br>Kepala Sekolah,</td>
            <td>Wali Kelas,</td>
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
            <td>
                <div class="garis"></div>
                <div class="nama">&nbsp;</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>

