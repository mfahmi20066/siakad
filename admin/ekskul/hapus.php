<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ekstrakurikuler WHERE id=$id"));

if (!$row) {
    header("Location: index.php?error=Data ekstrakurikuler tidak ditemukan");
    exit();
}

mysqli_query($koneksi, "DELETE FROM ekstrakurikuler_anggota WHERE ekskul_id=$id");

if (mysqli_query($koneksi, "DELETE FROM ekstrakurikuler WHERE id=$id")) {
    header("Location: index.php?success=" . urlencode("Ekstrakurikuler '{$row['nama_ekskul']}' berhasil dihapus"));
} else {
    header("Location: index.php?error=" . urlencode("Gagal menghapus: " . mysqli_error($koneksi)));
}
exit();
