<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = (int) $_GET['id'];

// ambil nama mapel buat pesan notif
$mapel = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_mapel FROM mata_pelajaran WHERE id='$id'"));

if (!$mapel) {
    header("Location: index.php?error=Mata pelajaran tidak ditemukan.");
    exit();
}

// arsipkan (soft-delete): status nonaktif; mapel yang kepake jadwal/nilai tetep aman
mysqli_query($koneksi, "UPDATE mata_pelajaran SET status='nonaktif' WHERE id='$id'");

header("Location: index.php?success=Mata pelajaran {$mapel['nama_mapel']} berhasil diarsipkan (nonaktif). Dapat diaktifkan kembali via Edit.");
exit();
?>