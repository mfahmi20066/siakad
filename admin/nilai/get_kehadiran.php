<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

// endpoint ajax: hitung persen kehadiran siswa per mapel, dari tabel absensi (bukan manual)

$sid = isset($_GET['siswa_id']) ? (int) $_GET['siswa_id'] : '';
$mid = isset($_GET['mapel_id']) ? (int) $_GET['mapel_id'] : '';

$result = [
    'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0,
    'total' => 0, 'persen' => 0
];

if ($sid !== '' && $mid !== '') {
    $stmt_kehadiran = mysqli_prepare($koneksi,
        "SELECT SUM(status = 'Hadir') AS hadir,
         SUM(status = 'Izin')  AS izin,
         SUM(status = 'Sakit') AS sakit,
         SUM(status = 'Alpa')  AS alpa,
         COUNT(*) AS total
        FROM absensi WHERE siswa_id = ? AND mapel_id = ?");
    mysqli_stmt_bind_param($stmt_kehadiran, "ss", $sid, $mid);
    mysqli_stmt_execute($stmt_kehadiran);
    mysqli_stmt_bind_result($stmt_kehadiran, $hadir, $izin, $sakit, $alpa, $total);
    mysqli_stmt_fetch($stmt_kehadiran);
    $result['hadir'] = (int) $hadir;
    $result['izin']  = (int) $izin;
    $result['sakit'] = (int) $sakit;
    $result['alpa']  = (int) $alpa;
    $result['total'] = (int) $total;
    $result['persen'] = $result['total'] > 0
        ? round(($result['hadir'] / $result['total']) * 100, 2)
        : 0;
}

header('Content-Type: application/json');
echo json_encode($result);