<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];

// Cek apakah masih ada siswa di kelas ini
$cek_siswa = mysqli_fetch_row(mysqli_query($koneksi,
             "SELECT COUNT(*) FROM siswa WHERE kelas_id='$id'"))[0];

if ($cek_siswa > 0) {
    // Tidak boleh dihapus jika masih ada siswa
    header("Location: index.php?error=Kelas tidak bisa dihapus karena masih ada $cek_siswa siswa di kelas ini!");
    exit();
}

// Aman dihapus
$kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_kelas FROM kelas WHERE id='$id'"));

mysqli_query($koneksi, "DELETE FROM kelas WHERE id='$id'");

header("Location: index.php?success=Kelas {$kelas['nama_kelas']} berhasil dihapus");
exit();
?>