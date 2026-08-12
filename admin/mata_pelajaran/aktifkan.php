<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = (int) $_GET['id'];

$mapel = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_mapel FROM mata_pelajaran WHERE id='$id'"));

if (!$mapel) {
    header("Location: index.php?error=Mata pelajaran tidak ditemukan.");
    exit();
}

mysqli_query($koneksi, "UPDATE mata_pelajaran SET status='aktif' WHERE id='$id'");

header("Location: index.php?success=Mata pelajaran {$mapel['nama_mapel']} berhasil diaktifkan kembali.");
exit();
