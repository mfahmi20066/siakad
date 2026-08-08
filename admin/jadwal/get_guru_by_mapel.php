<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
header('Content-Type: application/json');

$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;

if ($mapel_id <= 0) {
    echo json_encode([]);
    exit();
}

$guru      = [];
$seen_ids  = [];

// 1) Guru yang terdaftar langsung di tabel mata_pelajaran.guru_id
$q = mysqli_query($koneksi,
    "SELECT g.id, g.nama
     FROM guru g
     JOIN mata_pelajaran mp ON mp.guru_id = g.id
     WHERE mp.id = '$mapel_id'
     ORDER BY g.nama");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $guru[] = $r;
        $seen_ids[(int)$r['id']] = true;
    }
}

// 2) Guru yang mengajar mapel ini di tabel jadwal (mapel_id)
$q2 = mysqli_query($koneksi,
    "SELECT DISTINCT g.id, g.nama
     FROM jadwal j
     JOIN guru g ON g.id = j.guru_id
     WHERE j.mapel_id = '$mapel_id'
     ORDER BY g.nama");

if ($q2) {
    while ($r = mysqli_fetch_assoc($q2)) {
        if (!isset($seen_ids[(int)$r['id']])) {
            $guru[] = $r;
            $seen_ids[(int)$r['id']] = true;
        }
    }
}

// 3) Fallback: jika sama sekali tidak ada relasi, tampilkan semua guru
if (empty($guru)) {
    $q3 = mysqli_query($koneksi, "SELECT id, nama FROM guru ORDER BY nama");
    if ($q3) {
        while ($r = mysqli_fetch_assoc($q3)) {
            $guru[] = $r;
        }
    }
}

echo json_encode($guru);

