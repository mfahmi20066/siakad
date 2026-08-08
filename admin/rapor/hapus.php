<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php?error=ID tidak valid");
    exit();
}

// Cek rapor ada atau tidak
$cek = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT r.id, s.nama, s.nis, k.nama_kelas
     FROM rapor r
     JOIN siswa s ON r.siswa_id = s.id
     JOIN kelas k ON r.kelas_id = k.id
     WHERE r.id='$id'"));

if (!$cek) {
    header("Location: index.php?error=Data rapor tidak ditemukan");
    exit();
}

$nama  = $cek['nama'] ?? '-';
$nis   = $cek['nis'] ?? '-';
$kelas = $cek['nama_kelas'] ?? '-';

// Hapus rapor
mysqli_query($koneksi, "DELETE FROM rapor WHERE id='$id'");

header("Location: index.php?success=" . urlencode("Rapor $nama (NIS: $nis, Kelas: $kelas) berhasil dihapus"));
exit();
