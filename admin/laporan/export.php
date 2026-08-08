<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'akademik';

// Catat log export
if (isset($_SESSION['user_id'])) {
    $jenis_e = mysqli_real_escape_string($koneksi, $jenis);
    mysqli_query($koneksi,
        "INSERT INTO laporan_log (jenis_laporan, parameter, dibuat_oleh)
         VALUES ('$jenis_e', '', '{$_SESSION['user_id']}')");
}

$filename = "Laporan_" . ucfirst($jenis) . "_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");

$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    th { background-color: #163A63; color: #ffffff; font-weight: bold; text-align: center; }
    td { vertical-align: top; }
    h2 { text-align: center; }
</style>
</head>
<body>

<!-- Kop surat -->
<table border="0" width="100%" style="margin-bottom:10px;">
    <tr>
        <td colspan="6" style="font-size:10px;text-align:center;border:none;">PEMERINTAH KOTA PALOPO</td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10px;text-align:center;border:none;">DINAS PENDIDIKAN</td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:18px;font-weight:bold;text-align:center;border:none;color:#163A63;">SMA NEGERI 4 PALOPO</td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:10px;text-align:center;border:none;"><?= htmlspecialchars($setting['alamat_sekolah'] ?? '-') ?></td>
    </tr>
    <tr>
        <td colspan="6" style="border:none;">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:12px;text-align:center;border:none;">Dicetak pada: <?= tanggal_indo() ?> &mdash; <?= date('H:i') ?> WITA</td>
    </tr>
    <tr>
        <td colspan="6" style="border:none;">&nbsp;</td>
    </tr>
</table>

<?php if ($jenis === 'akademik'): ?>
    <?php
    $stat = [
        'siswa' => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM siswa"))[0],
        'guru'  => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM guru"))[0],
        'kelas' => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kelas"))[0],
        'mapel' => (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM mata_pelajaran"))[0],
    ];
    $rekap = mysqli_query($koneksi,
        "SELECT k.nama_kelas, m.nama_mapel, ROUND(AVG(n.nilai_akhir), 2) AS rata, COUNT(n.id) AS jml_data
         FROM nilai n
         LEFT JOIN kelas k ON n.kelas_id = k.id
         LEFT JOIN mata_pelajaran m ON n.mapel_id = m.id
         GROUP BY n.kelas_id, n.mapel_id
         ORDER BY k.nama_kelas, m.nama_mapel");
    ?>
    <h2>LAPORAN AKADEMIK SMA NEGERI 4 PALOPO</h2>
    <table border="1">
        <tr><th colspan="2">REKAPITULASI</th></tr>
        <tr><td>Total Siswa</td><td><?= $stat['siswa'] ?></td></tr>
        <tr><td>Total Guru</td><td><?= $stat['guru'] ?></td></tr>
        <tr><td>Total Kelas</td><td><?= $stat['kelas'] ?></td></tr>
        <tr><td>Total Mata Pelajaran</td><td><?= $stat['mapel'] ?></td></tr>
    </table>
    <br>
    <table border="1">
        <tr>
            <th>No</th><th>Kelas</th><th>Mata Pelajaran</th><th>Rata-rata Nilai</th><th>Jumlah Data</th>
        </tr>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($rekap)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($r['nama_kelas'] ?: '-') ?></td>
            <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
            <td><?= $r['rata'] !== null ? number_format($r['rata'], 2) : '-' ?></td>
            <td><?= (int) $r['jml_data'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

<?php elseif ($jenis === 'statistik'): ?>
    <?php
    $dist = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT
             SUM(CASE WHEN nilai_akhir >= 90 THEN 1 ELSE 0 END) AS a,
             SUM(CASE WHEN nilai_akhir >= 80 AND nilai_akhir < 90 THEN 1 ELSE 0 END) AS b,
             SUM(CASE WHEN nilai_akhir >= 70 AND nilai_akhir < 80 THEN 1 ELSE 0 END) AS c,
             SUM(CASE WHEN nilai_akhir < 70 THEN 1 ELSE 0 END) AS d
         FROM nilai"));
    $top_prestasi = mysqli_query($koneksi,
        "SELECT s.nis, s.nama_lengkap, s.nama AS nama_siswa, COUNT(p.id) AS jml
         FROM prestasi_siswa p
         LEFT JOIN siswa s ON p.siswa_id = s.id
         GROUP BY p.siswa_id ORDER BY jml DESC LIMIT 5");
    $top_pelanggaran = mysqli_query($koneksi,
        "SELECT s.nis, s.nama_lengkap, s.nama AS nama_siswa, SUM(pg.poin) AS total_poin
         FROM pelanggaran pg
         LEFT JOIN siswa s ON pg.siswa_id = s.id
         GROUP BY pg.siswa_id ORDER BY total_poin DESC LIMIT 5");
    ?>
    <h2>LAPORAN STATISTIK SMA NEGERI 4 PALOPO</h2>
    <table border="1">
        <tr><th colspan="2">DISTRIBUSI NILAI AKHIR</th></tr>
        <tr><td>Sangat Baik (90+)</td><td><?= (int) ($dist['a'] ?? 0) ?></td></tr>
        <tr><td>Baik (80-89)</td><td><?= (int) ($dist['b'] ?? 0) ?></td></tr>
        <tr><td>Cukup (70-79)</td><td><?= (int) ($dist['c'] ?? 0) ?></td></tr>
        <tr><td>Perlu Bimbingan (&lt;70)</td><td><?= (int) ($dist['d'] ?? 0) ?></td></tr>
    </table>
    <br>
    <table border="1">
        <tr><th colspan="3">5 SISWA PRESTASI TERBANYAK</th></tr>
        <tr><th>No</th><th>Siswa</th><th>Jumlah Prestasi</th></tr>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($top_prestasi)): ?>
            <?php $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa']; ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($nama_s ?: '-') ?> (NIS: <?= htmlspecialchars($r['nis'] ?: '-') ?>)</td>
            <td><?= (int) $r['jml'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <br>
    <table border="1">
        <tr><th colspan="3">5 SISWA PELANGGARAN TERBANYAK</th></tr>
        <tr><th>No</th><th>Siswa</th><th>Total Poin</th></tr>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($top_pelanggaran)): ?>
            <?php $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa']; ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($nama_s ?: '-') ?> (NIS: <?= htmlspecialchars($r['nis'] ?: '-') ?>)</td>
            <td><?= (int) $r['total_poin'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

<?php elseif ($jenis === 'kesiswaan'): ?>
    <?php
    $prestasi = mysqli_query($koneksi,
        "SELECT p.*, s.nis, s.nama_lengkap, s.nama AS nama_siswa
         FROM prestasi_siswa p LEFT JOIN siswa s ON p.siswa_id = s.id
         ORDER BY p.tanggal DESC, p.id DESC");
    ?>
    <h2>DATA PRESTASI SISWA SMA NEGERI 4 PALOPO</h2>
    <table border="1">
        <tr>
            <th>No</th><th>Siswa</th><th>Prestasi</th><th>Kategori</th><th>Tingkat</th><th>Tanggal</th>
        </tr>
        <?php $no = 1; while ($r = mysqli_fetch_assoc($prestasi)): ?>
            <?php $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa']; ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($nama_s ?: '-') ?></td>
            <td><?= htmlspecialchars($r['nama_prestasi']) ?></td>
            <td><?= htmlspecialchars($r['kategori']) ?></td>
            <td><?= htmlspecialchars($r['tingkat']) ?></td>
            <td><?= htmlspecialchars($r['tanggal'] ?: '-') ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

<?php else: ?>
    <p>Jenis laporan tidak dikenal.</p>
<?php endif; ?>

<!-- Tanda tangan -->
<br>
<table border="0" width="100%">
    <tr>
        <td width="70%"></td>
        <td style="text-align:center;">Palopo, <?= tanggal_indo() ?></td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:center;">Mengetahui,<br>Kepala Sekolah,</td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align:center;"><br><br><br><br><br><u><strong><?= htmlspecialchars($setting['nama_kepsek'] ?? '-') ?></strong></u><br>NIP. <?= htmlspecialchars($setting['nip_kepsek'] ?? '-') ?></td>
    </tr>
</table>

</body>
</html>
