<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (!$id) {
    header("Location: index.php?error=ID tidak valid");
    exit();
}

// Cek guru ada
$cek = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT id, nama_lengkap, nama, nip FROM guru WHERE id='$id'"));

if (!$cek) {
    header("Location: index.php?error=Data guru tidak ditemukan");
    exit();
}

$nama = $cek['nama_lengkap'] ?? $cek['nama'] ?? '-';
$nip  = $cek['nip'] ?? '-';

// Ambil foto sebelum data dihapus
$foto_row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT foto FROM guru WHERE id='$id'"));
$foto_file = $foto_row['foto'] ?? '';

// ── Hapus semua data terkait guru ───────────────────────────
mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");

// Kosongkan wali_kelas di tabel kelas
mysqli_query($koneksi, "UPDATE kelas SET wali_kelas = NULL WHERE wali_kelas = '$id'");

// Hapus jadwal terkait guru
mysqli_query($koneksi, "DELETE FROM jadwal WHERE guru_id = '$id'");

// Hapus akun user terkait guru
mysqli_query($koneksi, "DELETE FROM users WHERE id_ref='$id' AND role='guru'");

// Hapus guru
mysqli_query($koneksi, "DELETE FROM guru WHERE id = '$id'");

mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");

// Hapus file foto dari server
if (!empty($foto_file)) {
    $foto_path = __DIR__ . '/../../assets/img/foto_guru/' . $foto_file;
    if (file_exists($foto_path)) {
        unlink($foto_path);
    }
}

header("Location: index.php?success=" . urlencode("Guru $nama (NIP: $nip) berhasil dihapus"));
exit();