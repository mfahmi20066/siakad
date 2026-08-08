<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM nilai WHERE id='$id'");

header("Location: index.php?success=Nilai berhasil dihapus");
exit();
?>