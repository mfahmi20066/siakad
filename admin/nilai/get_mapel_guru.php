<?php
// Mengabaikan error reporting berupa HTML agar tidak merusak fungsi JSON Javascript
error_reporting(0);
ini_set('display_errors', 0);

// Set header bahwa file ini wajib mengeluarkan output JSON murni
header('Content-Type: application/json; charset=utf-8');

// Sesuaikan path koneksi database Anda
// Jika file ini berada di dalam folder 'admin/nilai/', gunakan '../../'
// Jika file ini berada di dalam folder 'admin/', gunakan '../'
if (file_exists('../../config/koneksi.php')) {
    include '../../config/koneksi.php';
} elseif (file_exists('../config/koneksi.php')) {
    include '../config/koneksi.php';
} else {
    echo json_encode([['mapel_id' => '', 'nama_mapel' => 'Koneksi Gagal', 'list_guru' => []]]);
    exit();
}

if (isset($_POST['kelas_id'])) {
    $kelas_id = mysqli_real_escape_string($koneksi, $_POST['kelas_id']);

    // Ambil mapel + guru dari tabel jadwal berdasarkan kelas_id.
    // Ini jauh lebih cepat dibanding mengambil semua mapel dan semua guru lalu meng-copy list guru ke tiap mapel.
    $data_mapel = [];

    $cek_kolom_guru = mysqli_query($koneksi, "SHOW COLUMNS FROM guru LIKE 'nama_lengkap'");
    $kolom_nama_guru = (mysqli_num_rows($cek_kolom_guru) > 0) ? "nama_lengkap" : "nama";

    // Ambil mapel + guru untuk kelas yang dipilih dari tabel jadwal
    $sql = "SELECT j.mapel_id, mp.nama_mapel,
                   j.guru_id, g.$kolom_nama_guru AS nama_guru
            FROM jadwal j
            JOIN mata_pelajaran mp ON mp.id = j.mapel_id
            JOIN guru g ON g.id = j.guru_id
            WHERE j.kelas_id = '$kelas_id'
            ORDER BY mp.nama_mapel";

    $result = mysqli_query($koneksi, $sql);

    $mapel_seen = [];
    $list_guru_by_mapel = []; // mapel_id => [guru_id => ['guru_id'=>..., 'nama_guru'=>...]]

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $mapel_id = (int)$row['mapel_id'];
            $guru_id  = (int)$row['guru_id'];

            if (!isset($mapel_seen[$mapel_id])) {
                $mapel_seen[$mapel_id] = true;
                $data_mapel[] = [
                    'mapel_id' => $mapel_id,
                    'nama_mapel' => $row['nama_mapel'],
                    'list_guru' => []
                ];
                $list_guru_by_mapel[$mapel_id] = [];
            }

            if (!isset($list_guru_by_mapel[$mapel_id][$guru_id])) {
                $list_guru_by_mapel[$mapel_id][$guru_id] = [
                    'guru_id' => $guru_id,
                    'nama_guru' => $row['nama_guru']
                ];
            }
        }

        // Isi list_guru per mapel (biar multi-guru tidak kacau)
        foreach ($data_mapel as &$item) {
            $mid = (int)$item['mapel_id'];
            if (isset($list_guru_by_mapel[$mid])) {
                $item['list_guru'] = array_values($list_guru_by_mapel[$mid]);
            } else {
                $item['list_guru'] = [];
            }
        }
        unset($item);

        echo json_encode($data_mapel);
        exit();
    }

    echo json_encode([]);
    exit();
} else {
    echo json_encode([]);
    exit();
}
?>


