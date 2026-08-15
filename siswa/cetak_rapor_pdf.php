<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_tahun_ajaran.php';
cekSiswa();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// pdf rapor milik siswa yang login aja (siswa_id dikunci dari session); format identik admin/rapor/cetak_pdf.php

$sid = $_SESSION['id_ref'];

$semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '';
$ta       = isset($_GET['ta']) ? mysqli_real_escape_string($koneksi, $_GET['ta']) : '';

$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$sys       = mysqli_fetch_assoc($q_setting);

function ambil_angka_semester($val) {
    if (preg_match('/\d+/', (string)$val, $m)) return $m[0];
    return '1';
}
$fix_semester = (!empty($semester) && $semester !== '0')
    ? ambil_angka_semester($semester)
    : ambil_angka_semester($sys['semester'] ?? '1');
$fix_ta = (!empty($ta) && $ta !== '0') ? $ta : '';

// tahun berbasis id (source of truth) + kompatibilitas param lama 'ta'
$taId = (int)($_GET['tahun_ajaran_id'] ?? 0);
if ($taId <= 0 && !empty($ta)) {
    $qres = mysqli_query($koneksi, "SELECT id FROM tahun_ajaran WHERE nama_tahun_ajaran='$ta' LIMIT 1");
    if ($qres && $rres = mysqli_fetch_assoc($qres)) $taId = (int)$rres['id'];
}
if ($taId <= 0) {
    $q = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT tahun_ajaran_id FROM siswa WHERE id='$sid'"));
    $taId = (int)($q['tahun_ajaran_id'] ?? 0);
}
if ($taId <= 0) {
    try { $taActive = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taActive['id']; }
    catch (Throwable $e) {}
}
$fix_ta = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT nama_tahun_ajaran v FROM tahun_ajaran WHERE id=$taId"))['v'] ?? $fix_ta;

// rapor milik siswa yang login aja (fallback semester '0' data lama)
$rapor = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT r.*, ta.nama_tahun_ajaran AS nama_tahun, s.nama, s.nis, s.nisn, s.jenis_kelamin,
                 s.tempat_lahir, s.tanggal_lahir, s.alamat, s.no_hp,
                 s.nama_ortu, s.no_hp_ortu,
                 k.nama_kelas, k.tingkat,
                 g.nama_lengkap AS wali_kelas, g.nip AS wali_nip
          FROM rapor r
          JOIN siswa s ON r.siswa_id = s.id
          JOIN tahun_ajaran ta ON ta.id = r.tahun_ajaran_id
          LEFT JOIN kelas k ON r.kelas_id = k.id
          LEFT JOIN guru g ON k.wali_kelas = g.id
          WHERE r.siswa_id = '$sid'
            AND (r.semester='$fix_semester' OR r.semester='$semester' OR r.semester='0' OR r.semester='')
            AND r.tahun_ajaran_id = '$taId'
          ORDER BY r.semester DESC
          LIMIT 1"));

if (!$rapor) {
    die('<p style="font-family:Arial;padding:30px;">Rapor untuk semester/tahun ajaran ini belum tersedia. <a href="rapor.php">Kembali</a></p>');
}

// siswa cuma boleh cetak rapor final
if (strtolower(trim($rapor['status'] ?? 'draft')) !== 'final') {
    header("Location: rapor.php?error=" . urlencode("Rapor belum difinalisasi. Cetak dapat dilakukan setelah rapor final."));
    exit();
}

$cek_kelompok = mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran LIKE 'kelompok'");
$ada_kelompok = mysqli_num_rows($cek_kelompok) > 0;
$select_mapel_extra = $ada_kelompok ? "m.kelompok, m.kkm," : "'Umum' AS kelompok, 75 AS kkm,";
$select_urutan = $ada_kelompok
    ? "CASE m.kelompok WHEN 'Normatif' THEN 1 WHEN 'Adaptif' THEN 2 WHEN 'Produktif' THEN 3 WHEN 'Muatan Lokal' THEN 4 ELSE 5 END AS urutan_kelompok"
    : "5 AS urutan_kelompok";

$nilai = mysqli_query($koneksi,
         "SELECT n.*, m.nama_mapel, m.kode_mapel, $select_mapel_extra
                 $select_urutan
          FROM nilai n
          JOIN mata_pelajaran m ON n.mapel_id = m.id
          WHERE n.siswa_id = '{$rapor['siswa_id']}'
            AND n.semester  = '{$rapor['semester']}'
            AND n.tahun_ajaran_id = '{$rapor['tahun_ajaran_id']}'
          ORDER BY urutan_kelompok, m.nama_mapel");

$count = function($status) use ($koneksi, $rapor) {
    $r = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM absensi
         WHERE siswa_id='{$rapor['siswa_id']}' AND status='$status'"));
    return $r[0] ?? 0;
};
$hadir = $count('Hadir'); $sakit = $count('Sakit'); $izin = $count('Izin'); $alpa = $count('Alpa');

$setting = $sys;

$kerajinan = $rapor['kerajinan'] ?? 'Baik';
$kelakuan  = $rapor['kelakuan']  ?? 'Baik';
$kerapihan = $rapor['kerapihan'] ?? 'Baik';
$eskul     = $rapor['ekstrakurikuler'] ?? '';

function romawi_pdf($n) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
    return $map[$n] ?? (string)$n;
}

// bangun baris nilai
$rows = '';
$no = 1; $total_na = 0; $jml_mapel = 0;
$kelompok_aktif = null; $urutan_grup = 0;
if ($nilai && mysqli_num_rows($nilai) > 0):
    while ($n = mysqli_fetch_assoc($nilai)):
        $na  = $n['nilai_akhir'] ?? 0;
        $kkm = $n['kkm'] ?? 75;
        $kel = $n['kelompok'] ?? 'Umum';
        if ($kel !== $kelompok_aktif):
            $kelompok_aktif = $kel; $urutan_grup++;
            $rows .= '<tr class="grup-row"><td colspan="5">' . romawi_pdf($urutan_grup) . '. ' . strtoupper(e($kel)) . '</td></tr>';
        endif;
        if (strtolower($kel) === 'produktif') {
            $predikat = $na >= $kkm ? 'Kompeten' : 'Belum Kompeten';
        } else {
            if ($na >= 75) $predikat = 'Baik'; elseif ($na >= 60) $predikat = 'Cukup'; else $predikat = 'Kurang';
        }
        $dibawah_kkm = $na < $kkm;
        $total_na += $na; $jml_mapel++;
        $rows .= '<tr><td class="text-center">' . $no++ . '</td>'
              . '<td>' . e($n['nama_mapel']) . '</td>'
              . '<td class="text-center">' . $kkm . '</td>'
              . '<td class="text-center' . ($dibawah_kkm?' merah':'') . '">' . number_format((float)$na,0) . '</td>'
              . '<td class="text-center' . ($dibawah_kkm?' merah':'') . '">' . $predikat . '</td></tr>';
    endwhile;
else:
    $rows = '<tr><td colspan="5" class="text-center">Belum ada data nilai akademik.</td></tr>';
endif;
$rata2 = $jml_mapel > 0 ? round($total_na / $jml_mapel, 2) : 0;

$status_rapor = isset($rapor['status']) ? strtolower(trim($rapor['status'])) : '';
$status_html = '';
if ($status_rapor === 'naik' || $status_rapor === 'tinggal'):
    $tw = ($status_rapor === 'naik')
        ? '<span style="color:green">NAIK KELAS</span> — Siswa dinyatakan naik ke kelas berikutnya.'
        : '<span style="color:#E11D48">TINGGAL KELAS</span> — Siswa dinyatakan mengulang di kelas yang sama.';
    $status_html = '<div class="seksi-judul">IV. Status Kenaikan</div><div class="catatan-box" style="min-height:auto;">' . $tw . '</div>';
endif;

require_once __DIR__ . '/../config/helper_pdf.php';
$logo_src = pdf_logo_data_uri(__DIR__ . '/../assets/img/logo-sekolah.png');

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size:10.5pt; color:#000; line-height:1.45; }
@page { margin: 1.8cm; }
.page-break { page-break-before: always; }
.kop-table { width:100%; border-collapse:collapse; border-bottom:3px double #000; padding-bottom:8px; margin-bottom:12px; }
.kop-table td { border:none; vertical-align:middle; padding:0 4px; }
.kop-table .logo-kiri { width:90px; text-align:left; }
.kop-table .logo-kanan { width:62px; text-align:right; }
.kop-table img { width:86px; height:86px; }
.kop-table .kop-text { text-align:center; line-height:1.3; padding-right:90px; }
.kop-text .instansi { font-size:13px; }
.kop-text .sekolah { font-size:21px; font-weight:bold; text-transform:uppercase; }
.kop-text .alamat { font-size:12px; }
.judul { text-align:center; font-size:15pt; font-weight:bold; text-decoration:underline; margin:14px 0 16px; letter-spacing:0.5px; }
.identitas { width:100%; margin-bottom:14px; border-collapse:collapse; }
.identitas td { padding:2px 4px; font-size:10.5pt; vertical-align:top; }
.identitas .label { width:150px; }
.identitas .titik { width:10px; }
table.rapor-table { width:100%; border-collapse:collapse; margin-bottom:6px; }
.rapor-table th, .rapor-table td { border:1px solid #000; padding:4px 8px; font-size:10pt; }
.rapor-table th { text-align:center; font-weight:bold; background:#F5F7FB; }
.text-center { text-align:center; }
.grup-row td { font-weight:bold; background:#F5F7FB; }
.merah { color:#E11D48; font-weight:bold; }
.seksi-judul { font-weight:bold; margin:18px 0 6px; font-size:12.5pt; }
.catatan-box { border:1px solid #000; min-height:55px; padding:10px; margin-bottom:14px; font-size:10.5pt; }
.footer-ttd { margin-top:24px; width:100%; }
.footer-ttd .tgl { text-align:right; margin-bottom:4px; font-size:10.5pt; }
.footer-ttd table { width:100%; border-collapse:collapse; margin-top:24px; }
.footer-ttd td { text-align:center; font-size:10.5pt; vertical-align:top; padding:0 10px; }
.footer-ttd .garis { margin-top:55px; border-bottom:1px solid #000; }
.footer-ttd .nama { margin-top:3px; font-weight:bold; text-decoration:underline; font-size:11pt; }
.footer-ttd .tgl-table { width:auto; margin-left:auto; margin-top:0; border-collapse:collapse; }
.footer-ttd .tgl-table td { padding:0 2px; text-align:left; vertical-align:top; border:none; }
.footer-ttd .tgl-table .tgl-label { width:110px; text-align:left; white-space:nowrap; }
.footer-ttd .tgl-table .tgl-titik { width:12px; text-align:center; white-space:nowrap; }
</style></head><body>';

$html .= '<table class="kop-table"><tr>'
    . '<td class="logo-kiri">' . ($logo_src ? '<img src="' . $logo_src . '">' : '') . '</td>'
    . '<td class="kop-text"><div class="instansi">PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>'
    . '<div class="sekolah">SMA NEGERI 4 PALOPO</div>'
    . '<div class="alamat">' . e(trim((($setting['alamat_sekolah'] ?? '') ? $setting['alamat_sekolah'] : '')
        . (trim(($setting['telepon'] ?? '') . ($setting['email'] ?? '')) !== '' ? ' | Telp. ' . ($setting['telepon'] ?? '') . ' | Email: ' . ($setting['email'] ?? '') : ''))) . '</div>'
    . '</td>'
    . ''
    . '</tr></table>';

$html .= '<div class="judul">LAPORAN HASIL BELAJAR</div>';

$html .= '<table class="identitas">
<tr><td class="label">NAMA</td><td class="titik">:</td><td style="font-weight:bold">' . e($rapor['nama'] ?? '-') . '</td><td class="label" style="width:110px">Kelas</td><td class="titik">:</td><td>' . e($rapor['nama_kelas'] ?? '-') . '</td></tr>
<tr><td class="label">NIS</td><td class="titik">:</td><td>' . e($rapor['nis'] ?? '-') . '</td><td class="label">NISN</td><td class="titik">:</td><td>' . e($rapor['nisn'] ?? '-') . '</td></tr>
<tr><td class="label">TEMPAT, TANGGAL LAHIR</td><td class="titik">:</td><td>' . e($rapor['tempat_lahir'] ?? '-') . ', ' . (isset($rapor['tanggal_lahir']) ? tanggal_indo($rapor['tanggal_lahir']) : '-') . '</td><td class="label">Semester</td><td class="titik">:</td><td>' . ($rapor['semester'] == 1 ? 'Ganjil' : 'Genap') . '</td></tr>
<tr><td class="label">JENIS KELAMIN</td><td class="titik">:</td><td>' . ((isset($rapor['jenis_kelamin']) && $rapor['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan') . '</td><td class="label">Tahun Pelajaran</td><td class="titik">:</td><td>' . e($rapor['nama_tahun'] ?? $rapor['tahun_ajaran'] ?? '-') . '</td></tr>
<tr><td class="label">WALI KELAS</td><td class="titik">:</td><td>' . e($rapor['wali_kelas'] ?? '-') . '</td><td></td><td></td><td></td></tr>
</table>';

$html .= '<div class="seksi-judul">I. Nilai Hasil Belajar</div>';
$html .= '<table class="rapor-table">
<thead><tr><th rowspan="2" style="width:26px">No</th><th rowspan="2">Mata Pelajaran</th><th rowspan="2" style="width:45px">KKM</th><th colspan="2">Nilai Hasil Belajar</th></tr>
<tr><th style="width:55px">Angka</th><th style="width:80px">Predikat</th></tr></thead><tbody>' . $rows . '</tbody></table>';
$html .= '<p style="font-size:10.5pt; margin:6px 0;">Rata-rata Nilai: <strong>' . $rata2 . '</strong></p>';

$html .= '<div style="page-break-inside: avoid;"><div class="seksi-judul">II. Pengembangan Diri, Kepribadian dan Ketidakhadiran</div>';
$html .= '<table class="rapor-table"><thead><tr><th colspan="2">Komponen</th><th style="width:110px">Predikat</th></tr></thead><tbody>'
    . '<tr><td rowspan="1" style="width:170px">Kegiatan Pengembangan Diri</td><td>1. ' . (!empty($eskul) ? e($eskul) : '-') . '</td><td class="text-center">' . (!empty($eskul) ? 'Baik' : '-') . '</td></tr>'
    . '<tr><td rowspan="3">Kepribadian</td><td>1. Kerajinan</td><td class="text-center">' . e($kerajinan) . '</td></tr>'
    . '<tr><td>2. Kelakuan</td><td class="text-center">' . e($kelakuan) . '</td></tr>'
    . '<tr><td>3. Kerapihan</td><td class="text-center">' . e($kerapihan) . '</td></tr>'
    . '<tr><td rowspan="3">Ketidakhadiran</td><td>1. Sakit</td><td class="text-center">' . $sakit . ' hari</td></tr>'
    . '<tr><td>2. Izin</td><td class="text-center">' . $izin . ' hari</td></tr>'
    . '<tr><td>3. Tanpa Keterangan</td><td class="text-center">' . $alpa . ' hari</td></tr>'
    . '</tbody></table></div>';

$html .= '<div class="seksi-judul">III. Catatan Untuk Orang Tua / Wali</div>';
$html .= '<div class="catatan-box">' . (nl2br(e($rapor['catatan'] ?? 'Tingkatkan terus prestasimu dan pertahankan semangat belajarmu.'))) . '</div>';

$html .= $status_html;

$html .= '<div class="footer-ttd" style="page-break-inside: avoid;"><div class="tgl"><table class="tgl-table"><tr><td class="tgl-label">Diberikan di</td><td class="tgl-titik">:</td><td>Palopo</td></tr><tr><td class="tgl-label">Tanggal</td><td class="tgl-titik">:</td><td>' . tanggal_indo() . '</td></tr></table></div>';
$html .= '<table><tr><td style="width:33%">Mengetahui,<br>Kepala Sekolah,</td><td style="width:34%">Orang Tua / Wali,</td><td style="width:33%">Wali Kelas,</td></tr>';
$html .= '<tr><td><div class="garis"></div><div class="nama">' . e($setting['nama_kepsek'] ?? 'Nama Belum Diatur') . '</div><div>NIP. ' . e($setting['nip_kepsek'] ?? '-') . '</div></td>';
$html .= '<td><div class="garis"></div><div class="nama">' . e($rapor['nama_ortu'] ?? '') . '</div>' . (!empty($rapor['no_hp_ortu']) ? '<div>' . e($rapor['no_hp_ortu']) . '</div>' : '') . '</td>';
$html .= '<td><div class="garis"></div><div class="nama">' . e($rapor['wali_kelas'] ?? '____________________') . '</div>' . (!empty($rapor['wali_nip']) ? '<div>NIP. ' . e($rapor['wali_nip']) . '</div>' : '') . '</td></tr></table></div>';

$html .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Rapor_' . preg_replace('/[^A-Za-z0-9]/', '_', $rapor['nama'] ?? 'Siswa') . '_Semester' . $fix_semester . '.pdf', ['Attachment' => true]);
exit;
