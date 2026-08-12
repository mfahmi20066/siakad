<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM prestasi_siswa WHERE id=$id"));

if (!$row) {
    header("Location: index.php?error=Data prestasi tidak ditemukan");
    exit();
}

if (mysqli_query($koneksi, "DELETE FROM prestasi_siswa WHERE id=$id")) {
    header("Location: index.php?success=" . urlencode("Prestasi '{$row['nama_prestasi']}' berhasil dihapus"));
} else {
    header("Location: index.php?error=" . urlencode("Gagal menghapus: " . mysqli_error($koneksi)));
}
exit();
