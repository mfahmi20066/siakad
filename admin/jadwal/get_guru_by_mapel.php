<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
header('Content-Type: application/json');

$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;

if ($mapel_id <= 0) {
    echo json_encode([]);
    exit();
}

// Sumber kebenaran: pivot kelas_mapel_guru.
// Jika kelas dipilih, tampilkan guru yang mengajar mapel tersebut DI KELAS ITU.
// Jika kelas tidak dipilih, tampilkan semua guru yang mengajar mapel tersebut.
$where = "kmg.mapel_id = $mapel_id";
if ($kelas_id > 0) $where .= " AND kmg.kelas_id = $kelas_id";

$guru = [];
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM guru LIKE 'nama_lengkap'");
$nama_guru = (mysqli_num_rows($cek_kolom) > 0) ? "g.nama_lengkap AS nama" : "g.nama";

$q = mysqli_query($koneksi,
    "SELECT DISTINCT g.id, $nama_guru
     FROM kelas_mapel_guru kmg
     JOIN guru g ON g.id = kmg.guru_id
     WHERE $where
     ORDER BY nama");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $guru[] = ['id' => (int)$r['id'], 'nama' => $r['nama']];
    }
}

// Fallback: bila belum ada penugasan di pivot, tampilkan semua guru agar admin tetap bisa mengisi.
if (empty($guru)) {
    $q3 = mysqli_query($koneksi,
        "SELECT g.id, $nama_guru FROM guru g ORDER BY nama");
    if ($q3) {
        while ($r = mysqli_fetch_assoc($q3)) {
            $guru[] = ['id' => (int)$r['id'], 'nama' => $r['nama']];
        }
    }
}

echo json_encode($guru);
