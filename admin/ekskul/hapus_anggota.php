<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id       = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ekskul_id = isset($_GET['ekskul_id']) ? (int) $_GET['ekskul_id'] : 0;

$row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ekstrakurikuler_anggota WHERE id=$id"));

if (!$row) {
    header("Location: index.php?error=Data anggota ekskul tidak ditemukan");
    exit();
}

if (mysqli_query($koneksi, "DELETE FROM ekstrakurikuler_anggota WHERE id=$id")) {
    header("Location: anggota.php?id=$ekskul_id&success=" . urlencode("Anggota berhasil dihapus"));
} else {
    header("Location: anggota.php?id=$ekskul_id&error=" . urlencode("Gagal menghapus: " . mysqli_error($koneksi)));
}
exit();
