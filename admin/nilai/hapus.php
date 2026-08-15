<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
verifyCsrf();

$id = (int) $_GET['id'];
// hapus data
$stmt_delete = mysqli_prepare($koneksi, "DELETE FROM nilai WHERE id=?");
mysqli_stmt_bind_param($stmt_delete, "i", $id);
mysqli_stmt_execute($stmt_delete);

header("Location: index.php?success=Nilai berhasil dihapus");
exit();
?>