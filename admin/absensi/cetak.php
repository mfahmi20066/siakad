<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$kid        = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';
$filter_bln = isset($_GET['bulan'])    ? mysqli_real_escape_string($koneksi, $_GET['bulan'])    : '';

if (!$kid) {
    echo "Akses ditolak. Kelas belum dipilih.";
    exit;
}

$where_bln = $filter_bln ? "AND MONTH(a.tanggal) = '$filter_bln'" : '';

$data = mysqli_query($koneksi,
        "SELECT s.nis, s.nama,
            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN a.status = 'Izin'  THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN a.status = 'Alpa'  THEN 1 ELSE 0 END) AS alpa,
            COUNT(a.id) AS total
         FROM siswa s
         LEFT JOIN absensi a ON s.id = a.siswa_id AND a.kelas_id = '$kid' $where_bln
         WHERE s.kelas_id = '$kid'
         GROUP BY s.id
         ORDER BY s.nama");

$nama_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id='$kid'"))['nama_kelas'];

$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

$bulan = ['1'=>'Januari','2'=>'Februari','3'=>'Maret','4'=>'April','5'=>'Mei','6'=>'Juni','7'=>'Juli','8'=>'Agustus','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$bulan_nama = $filter_bln ? ($bulan[$filter_bln] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Absensi - <?= e($nama_kelas) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11.5px; padding: 22px 30px; color: #000; }

        .kop { display: flex; align-items: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 10px; }
        .kop img { width: 86px; height: 86px; object-fit: contain; }
        .kop .logo-kiri  { margin-right: 16px; }
        .kop .logo-kanan { margin-left: 12px; }
        .kop-text { text-align: center; flex: 1; line-height: 1.3; padding-right: 102px; }
        .kop-text .instansi { font-size: 13px; }
        .kop-text .sekolah  { font-size: 21px; font-weight: bold; text-transform: uppercase; }
        .kop-text .alamat   { font-size: 12px; }

        .judul { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin: 12px 0 4px; }
        .sub-judul { text-align: center; font-size: 12px; margin-bottom: 10px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        .data-table th { text-align: center; font-weight: bold; background: #F5F7FB; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .merah { color: #E11D48; font-weight: bold; }
        .hijau { color: #15803D; font-weight: bold; }

        .footer-ttd { margin-top: 30px; width: 100%; }
        .footer-ttd .tgl { text-align: right; margin-bottom: 4px; font-size: 11.5px; }
        .footer-ttd table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .footer-ttd td { text-align: center; font-size: 11.5px; vertical-align: top; padding: 0 10px; }
        .footer-ttd .garis { margin-top: 60px; border-bottom: 1px solid #000; }
        .footer-ttd .nama { margin-top: 3px; font-weight: bold; text-decoration: underline; }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            body { padding: 8px 16px; }
        }
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 15mm; }
    </style>
</head>
<body>

    <div class="kop">
        <img class="logo-kiri" src="../../assets/img/logo-sekolah.png"
             onerror="this.style.display='none'" alt="Logo">
        <div class="kop-text">
            <div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>
            <div class="sekolah">SMA NEGERI 4 PALOPO</div>
            <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div>
            <div class="alamat"><?= e($setting['alamat_sekolah'] ?? '-') ?></div><div class="alamat"><?= e(trim((!empty($setting['telepon']) ? 'Telp. ' . $setting['telepon'] : '') . (!empty($setting['email']) ? ' | Email: ' . $setting['email'] : ''))) ?></div>
        </div></div>

    <div class="judul">REKAPITULASI ABSENSI SISWA</div>
    <div class="sub-judul">
        Kelas: <strong><?= e($nama_kelas) ?></strong>
        <?php if ($bulan_nama): ?> &mdash; Bulan: <strong><?= $bulan_nama ?></strong><?php endif; ?>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:70px">NIS</th>
                <th class="text-left">Nama Siswa</th>
                <th style="width:50px">Hadir</th>
                <th style="width:50px">Sakit</th>
                <th style="width:50px">Izin</th>
                <th style="width:50px">Alpa</th>
                <th style="width:55px">Total Hari</th>
                <th style="width:65px">% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($data && mysqli_num_rows($data) > 0): ?>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($data)):
            $persen = $r['total'] > 0 ? round(($r['hadir'] / $r['total']) * 100, 1) : 0;
        ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= e($r['nis']) ?></td>
                <td><?= e($r['nama']) ?></td>
                <td class="text-center"><?= $r['hadir'] ?></td>
                <td class="text-center"><?= $r['sakit'] ?></td>
                <td class="text-center"><?= $r['izin'] ?></td>
                <td class="text-center"><?= $r['alpa'] ?></td>
                <td class="text-center"><?= $r['total'] ?></td>
                <td class="text-center <?= $persen >= 75 ? 'hijau' : 'merah' ?>"><?= $persen ?>%</td>
            </tr>
        <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center">Belum ada data absensi.</td></tr>
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
                <td style="width:33%">Mengetahui,<br>Kepala Sekolah,</td>
                <td style="width:34%"></td>
                <td style="width:33%">Wali Kelas,</td>
            </tr>
            <tr>
                <td>
                    <div class="garis"></div>
                    <div class="nama"><?= e($setting['nama_kepsek'] ?? 'Nama Belum Diatur') ?></div>
                    <div>NIP. <?= e($setting['nip_kepsek'] ?? '-') ?></div>
                </td>
                <td></td>
                <td>
                    <div class="garis"></div>
                    <div class="nama">&nbsp;</div>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
