<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];

// Ambil nama mapel untuk pesan notifikasi
$mapel = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_mapel FROM mata_pelajaran WHERE id='$id'"));

// Cek apakah mapel masih digunakan di jadwal
$cek_jadwal = mysqli_fetch_row(mysqli_query($koneksi,
              "SELECT COUNT(*) FROM jadwal WHERE mapel_id='$id'"))[0];

// Cek apakah mapel masih digunakan di data nilai
$cek_nilai = mysqli_fetch_row(mysqli_query($koneksi,
             "SELECT COUNT(*) FROM nilai WHERE mapel_id='$id'"))[0];

if ($cek_jadwal > 0) {
    header("Location: index.php?error=Mata pelajaran tidak bisa dihapus karena masih digunakan di $cek_jadwal jadwal pelajaran!");
    exit();
}

if ($cek_nilai > 0) {
    header("Location: index.php?error=Mata pelajaran tidak bisa dihapus karena masih ada $cek_nilai data nilai siswa!");
    exit();
}

// Aman dihapus
mysqli_query($koneksi, "DELETE FROM mata_pelajaran WHERE id='$id'");

header("Location: index.php?success=Mata pelajaran {$mapel['nama_mapel']} berhasil dihapus");
exit();
?>