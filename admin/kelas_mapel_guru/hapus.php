<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$row = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT kmg.*, mp.nama_mapel, k.nama_kelas
     FROM kelas_mapel_guru kmg
     JOIN mata_pelajaran mp ON mp.id = kmg.mapel_id
     JOIN kelas k ON k.id = kmg.kelas_id
     WHERE kmg.id = $id"));

if (!$row) {
    header("Location: index.php?error=Penugasan tidak ditemukan");
    exit();
}

// Cek apakah penugasan sudah dipakai nilai (FK SET NULL, nilai tetap aman)
$jml_nilai = mysqli_fetch_row(mysqli_query($koneksi,
    "SELECT COUNT(*) FROM nilai WHERE kelas_mapel_guru_id = $id"))[0];

$kelas_id = (int) $row['kelas_id'];
mysqli_query($koneksi, "DELETE FROM kelas_mapel_guru WHERE id = $id");

$msg = "Penugasan {$row['nama_mapel']} di kelas {$row['nama_kelas']} berhasil dihapus.";
if ($jml_nilai > 0) {
    $msg .= " ($jml_nilai data nilai terkait tetap tersimpan, hanya referensinya dilepas.)";
}
header("Location: index.php?kelas_id=$kelas_id&success=" . urlencode($msg));
exit();
