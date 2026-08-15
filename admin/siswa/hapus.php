<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (!$id) {
    header("Location: index.php?error=ID tidak valid");
    exit();
}

// cek siswa ada ga
$cek = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT id, nama_lengkap, nama, nis FROM siswa WHERE id='$id'"));

if (!$cek) {
    header("Location: index.php?error=Data siswa tidak ditemukan");
    exit();
}

$nama = $cek['nama_lengkap'] ?? $cek['nama'] ?? '-';
$nis  = $cek['nis'] ?? '-';

// ambil foto sebelum data dihapus
$foto_row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT foto FROM siswa WHERE id='$id'"));
$foto_file = $foto_row['foto'] ?? '';

// hapus data terkait siswa (urut dari child dulu)
// nonaktifin fk sementara biar hapus lancar
mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");

// hapus data terkait
mysqli_query($koneksi, "DELETE FROM rapor   WHERE siswa_id = '$id'");
mysqli_query($koneksi, "DELETE FROM absensi WHERE siswa_id = '$id'");
mysqli_query($koneksi, "DELETE FROM nilai   WHERE siswa_id = '$id'");

// hapus akun user terkait
mysqli_query($koneksi, "DELETE FROM users WHERE id_ref='$id' AND role='siswa'");
mysqli_query($koneksi, "DELETE FROM users WHERE id='$id' AND role='siswa'");

// hapus siswa
mysqli_query($koneksi, "DELETE FROM siswa WHERE id = '$id'");

// aktifin lagi fk
mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");

// hapus file foto dari server
if (!empty($foto_file)) {
    $foto_path = __DIR__ . '/../../assets/img/foto_siswa/' . $foto_file;
    if (file_exists($foto_path)) {
        unlink($foto_path);
    }
}

header("Location: index.php?success=" . urlencode("Siswa $nama (NIS: $nis) berhasil dihapus"));
exit();