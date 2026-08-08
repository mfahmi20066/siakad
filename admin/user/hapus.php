<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];

// Proteksi: tidak bisa hapus akun sendiri
if ($id == $_SESSION['user_id']) {
    header("Location: index.php?error=Tidak bisa menghapus akun sendiri!");
    exit();
}

// Ambil nama user untuk pesan notifikasi
$user = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT nama, role FROM users WHERE id='$id'"));

// Cek role: jika admin terakhir, tidak boleh dihapus
if ($user['role'] == 'admin') {
    $jml_admin = mysqli_fetch_row(mysqli_query($koneksi,
                 "SELECT COUNT(*) FROM users WHERE role='admin'"))[0];

    if ($jml_admin <= 1) {
        header("Location: index.php?error=Tidak bisa menghapus admin terakhir di sistem!");
        exit();
    }
}

mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");

header("Location: index.php?success=User {$user['nama']} berhasil dihapus");
exit();
?>