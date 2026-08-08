<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

// Endpoint AJAX: hitung persentase kehadiran siswa untuk 1 mata pelajaran,
// diambil otomatis dari tabel absensi (bukan input manual).

$sid = isset($_GET['siswa_id']) ? mysqli_real_escape_string($koneksi, $_GET['siswa_id']) : '';
$mid = isset($_GET['mapel_id']) ? mysqli_real_escape_string($koneksi, $_GET['mapel_id']) : '';

$result = [
    'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0,
    'total' => 0, 'persen' => 0
];

if ($sid !== '' && $mid !== '') {
    $q = mysqli_query($koneksi, "SELECT
            SUM(status = 'Hadir') AS hadir,
            SUM(status = 'Izin')  AS izin,
            SUM(status = 'Sakit') AS sakit,
            SUM(status = 'Alpa')  AS alpa,
            COUNT(*) AS total
        FROM absensi
        WHERE siswa_id = '$sid' AND mapel_id = '$mid'");

    if ($q && ($row = mysqli_fetch_assoc($q))) {
        $result['hadir'] = (int) $row['hadir'];
        $result['izin']  = (int) $row['izin'];
        $result['sakit'] = (int) $row['sakit'];
        $result['alpa']  = (int) $row['alpa'];
        $result['total'] = (int) $row['total'];
        $result['persen'] = $result['total'] > 0
            ? round(($result['hadir'] / $result['total']) * 100, 2)
            : 0;
    }
}

header('Content-Type: application/json');
echo json_encode($result);