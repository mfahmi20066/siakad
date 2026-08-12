<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Cegah hapus admin utama (ID=1)
if ($id <= 1) {
    header("Location: index.php?success=Gagal: Tidak bisa menghapus admin utama");
    exit();
}

mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
header("Location: index.php?success=hapus");
exit();
