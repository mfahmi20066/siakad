<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = (int) $_GET['id'];

$kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_kelas FROM kelas WHERE id='$id'"));

if (!$kelas) {
    header("Location: index.php?error=Kelas tidak ditemukan.");
    exit();
}

mysqli_query($koneksi, "UPDATE kelas SET status='aktif' WHERE id='$id'");

header("Location: index.php?success=Kelas {$kelas['nama_kelas']} berhasil diaktifkan kembali.");
exit();
