<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'akademik';

// catat log export
if (isset($_SESSION['user_id'])) {
    $jenis_e = mysqli_real_escape_string($koneksi, $jenis);
    mysqli_query($koneksi,
        "INSERT INTO laporan_log (jenis_laporan, parameter, dibuat_oleh)
         VALUES ('$jenis_e', '', '{$_SESSION['user_id']}')");
}

require_once __DIR__ . '/../../config/helper_xlsx.php';

$q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting   = mysqli_fetch_assoc($q_setting);

$rows = [];
$rows[] = ['PEMERINTAH KOTA PALOPO'];
$rows[] = ['DINAS PENDIDIKAN'];
$rows[] = ['SMA NEGERI 4 PALOPO'];
$rows[] = [$setting['alamat_sekolah'] ?? '-'];
$rows[] = ['Dicetak pada: ' . tanggal_indo() . ' - ' . date('H:i') . ' WITA'];
$rows[] = [];
$headerIdx = [];

if ($jenis === 'akademik'):
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

    $rows[] = ['LAPORAN AKADEMIK SMA NEGERI 4 PALOPO'];
    $rows[] = [];
    $rows[] = ['REKAPITULASI'];
    $rows[] = ['Total Siswa', $stat['siswa']];
    $rows[] = ['Total Guru', $stat['guru']];
    $rows[] = ['Total Kelas', $stat['kelas']];
    $rows[] = ['Total Mata Pelajaran', $stat['mapel']];
    $rows[] = [];
    $rows[] = ['No', 'Kelas', 'Mata Pelajaran', 'Rata-rata Nilai', 'Jumlah Data'];
    $headerIdx[] = count($rows);
    $no = 1;
    while ($r = mysqli_fetch_assoc($rekap)):
        $rows[] = [$no++, $r['nama_kelas'] ?: '-', $r['nama_mapel'], $r['rata'] !== null ? number_format($r['rata'], 2, ',', '.') : '-', (int) $r['jml_data']];
    endwhile;

elseif ($jenis === 'statistik'):
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

    $rows[] = ['LAPORAN STATISTIK SMA NEGERI 4 PALOPO'];
    $rows[] = [];
    $rows[] = ['DISTRIBUSI NILAI AKHIR'];
    $rows[] = ['Sangat Baik (90+)', (int) ($dist['a'] ?? 0)];
    $rows[] = ['Baik (80-89)', (int) ($dist['b'] ?? 0)];
    $rows[] = ['Cukup (70-79)', (int) ($dist['c'] ?? 0)];
    $rows[] = ['Perlu Bimbingan (<70)', (int) ($dist['d'] ?? 0)];
    $rows[] = [];
    $rows[] = ['5 SISWA PRESTASI TERBANYAK'];
    $rows[] = ['No', 'Siswa', 'Jumlah Prestasi'];
    $headerIdx[] = count($rows);
    $no = 1;
    while ($r = mysqli_fetch_assoc($top_prestasi)):
        $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
        $rows[] = [$no++, ($nama_s ?: '-') . ' (NIS: ' . ($r['nis'] ?: '-') . ')', (int) $r['jml']];
    endwhile;
    $rows[] = [];
    $rows[] = ['5 SISWA PELANGGARAN TERBANYAK'];
    $rows[] = ['No', 'Siswa', 'Total Poin'];
    $headerIdx[] = count($rows);
    $no = 1;
    while ($r = mysqli_fetch_assoc($top_pelanggaran)):
        $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
        $rows[] = [$no++, ($nama_s ?: '-') . ' (NIS: ' . ($r['nis'] ?: '-') . ')', (int) $r['total_poin']];
    endwhile;

elseif ($jenis === 'kesiswaan'):
    $prestasi = mysqli_query($koneksi,
        "SELECT p.*, s.nis, s.nama_lengkap, s.nama AS nama_siswa
         FROM prestasi_siswa p LEFT JOIN siswa s ON p.siswa_id = s.id
         ORDER BY p.tanggal DESC, p.id DESC");

    $rows[] = ['DATA PRESTASI SISWA SMA NEGERI 4 PALOPO'];
    $rows[] = [];
    $rows[] = ['No', 'Siswa', 'Prestasi', 'Kategori', 'Tingkat', 'Tanggal'];
    $headerIdx[] = count($rows);
    $no = 1;
    while ($r = mysqli_fetch_assoc($prestasi)):
        $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
        $rows[] = [$no++, $nama_s ?: '-', $r['nama_prestasi'], $r['kategori'], $r['tingkat'], $r['tanggal'] ?: '-'];
    endwhile;

else:
    $rows[] = ['Jenis laporan tidak dikenal.'];
endif;

// tanda tangan
$rows[] = [];
$rows[] = ['', '', '', '', 'Palopo, ' . tanggal_indo()];
$rows[] = ['', '', '', '', 'Mengetahui,'];
$rows[] = ['', '', '', '', 'Kepala Sekolah,'];
$rows[] = [];
$rows[] = [];
$rows[] = ['', '', '', '', ($setting['nama_kepsek'] ?? '-')];
$rows[] = ['', '', '', '', 'NIP. ' . ($setting['nip_kepsek'] ?? '-')];

export_xlsx('Laporan_' . ucfirst($jenis) . '_' . date('Y-m-d'), [
    'Laporan' => ['rows' => $rows, 'header_row' => $headerIdx],
]);
exit;
