<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];

$pengumuman = mysqli_fetch_assoc(mysqli_query($koneksi,
              "SELECT judul FROM pengumuman WHERE id='$id'"));

mysqli_query($koneksi, "DELETE FROM pengumuman WHERE id='$id'");

header("Location: index.php?success=Pengumuman '{$pengumuman['judul']}' berhasil dihapus");
exit();
?>