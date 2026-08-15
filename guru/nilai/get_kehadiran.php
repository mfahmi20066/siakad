<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();

// endpoint ajax: persen kehadiran siswa per mapel (dari absensi), cuma buat kelas yang guru ini ampu

$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : '';
$sid = isset($_GET['siswa_id']) ? mysqli_real_escape_string($koneksi, $_GET['siswa_id']) : '';
$mid = isset($_GET['mapel_id']) ? mysqli_real_escape_string($koneksi, $_GET['mapel_id']) : '';

$result = [
    'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0,
    'total' => 0, 'persen' => 0
];

if ($sid !== '' && $mid !== '' && $gid !== '') {
    // validasi hak akses: siswa di kelas ampu + mapel diajarkan
    $q_izin = mysqli_query($koneksi,
        "SELECT s.kelas_id
         FROM siswa s
         JOIN kelas_mapel_guru kmg ON kmg.kelas_id = s.kelas_id AND kmg.mapel_id = '$mid'
         WHERE s.id = '$sid' AND kmg.guru_id = '$gid'
         LIMIT 1");

    if ($q_izin && mysqli_num_rows($q_izin) > 0) {
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
}

header('Content-Type: application/json');
echo json_encode($result);
