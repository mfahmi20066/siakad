<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();

$pdo = tahun_ajaran_pdo();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = getTahunAjaranById($pdo, $id);

if (!$row) {
    header("Location: index.php?error=" . urlencode("Data tahun ajaran tidak ditemukan"));
    exit();
}

if ($row['status'] === 'aktif') {
    header("Location: index.php?error=" . urlencode("Tahun ajaran yang sedang aktif tidak bisa dihapus. Jadikan tahun lain aktif terlebih dahulu."));
    exit();
}

// Cek referensi dari tabel lain (semester/jadwal/kelas/nilai/rapor)
$terpakai = tahunAjaranTerkait($pdo, $id);

if ($terpakai) {
    $daftar = implode(', ', $terpakai);
    header("Location: index.php?error=" . urlencode("Tahun ajaran masih dipakai oleh data: $daftar. Untuk menjaga histori, hapus tidak dibolehkan. Jadikan nonaktif saja."));
    exit();
}

try {
    $del = $pdo->prepare('DELETE FROM tahun_ajaran WHERE id = :id');
    $del->execute([':id' => $id]);
    header("Location: index.php?success=" . urlencode("Tahun ajaran '{$row['nama_tahun_ajaran']}' berhasil dihapus"));
} catch (Throwable $e) {
    header("Location: index.php?error=" . urlencode("Gagal menghapus: " . $e->getMessage()));
}
exit();