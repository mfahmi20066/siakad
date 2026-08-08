<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];

// Ambil info jadwal untuk pesan notifikasi
$jadwal = mysqli_fetch_assoc(mysqli_query($koneksi,
          "SELECT j.*, k.nama_kelas, m.nama_mapel
           FROM jadwal j
           JOIN kelas k ON j.kelas_id = k.id
           JOIN mata_pelajaran m ON j.mapel_id = m.id
           WHERE j.id = '$id'"));

mysqli_query($koneksi, "DELETE FROM jadwal WHERE id='$id'");

header("Location: index.php?success=Jadwal {$jadwal['nama_mapel']} - {$jadwal['nama_kelas']} berhasil dihapus");
exit();
?>