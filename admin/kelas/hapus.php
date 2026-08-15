<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = (int) $_GET['id'];

// cek masih ada siswa di kelas ini ga
$cek_siswa = mysqli_fetch_row(mysqli_query($koneksi,
             "SELECT COUNT(*) FROM siswa WHERE kelas_id='$id'"))[0];

if ($cek_siswa > 0) {
    // ga boleh diarsip kalo masih ada siswa
    header("Location: index.php?error=Kelas tidak bisa diarsipkan karena masih ada $cek_siswa siswa di kelas ini! Pindahkan siswa terlebih dahulu.");
    exit();
}

// arsipkan (soft-delete): status = nonaktif, bukan hapus permanen
$kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
         "SELECT nama_kelas FROM kelas WHERE id='$id'"));

mysqli_query($koneksi, "UPDATE kelas SET status='nonaktif' WHERE id='$id'");

header("Location: index.php?success=Kelas {$kelas['nama_kelas']} berhasil diarsipkan (nonaktif). Dapat diaktifkan kembali via Edit.");
exit();
?>