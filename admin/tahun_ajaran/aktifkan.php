<?php
// aktifkan satu tahun ajaran: transaction, semua nonaktif dulu baru target aktif. ga ada delete/truncate
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
verifyCsrf();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pdo = tahun_ajaran_pdo();

if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('ID tahun ajaran tidak valid.'));
    exit();
}

try {
    $ta = getTahunAjaranById($pdo, $id);
    if (!$ta) {
        header('Location: index.php?error=' . urlencode('Tahun ajaran tidak ditemukan.'));
        exit();
    }

    $pdo->beginTransaction();
    try {
        $nonaktif = $pdo->exec("UPDATE tahun_ajaran SET status = 'nonaktif' WHERE status = 'aktif'");
        $aktif    = $pdo->prepare('UPDATE tahun_ajaran SET status = \'aktif\' WHERE id = :id');
        $aktif->execute([':id' => $id]);

        if ($aktif->rowCount() === 0) {
            throw new RuntimeException('Gagal mengaktifkan tahun ajaran target.');
        }

        // sinkron label ke pengaturan (tahun aja; semester tetep dari tabel semester)
        $sync = $pdo->prepare("UPDATE pengaturan SET tahun_pelajaran = :ta, semester = '1 (Ganjil)' WHERE id = 1");
        $sync->execute([':ta' => $ta['nama_tahun_ajaran']]);

        $pdo->commit();
        header('Location: index.php?success=' . urlencode("Tahun ajaran {$ta['nama_tahun_ajaran']} dijadikan aktif. Tahun lain otomatis nonaktif."));
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header('Location: index.php?error=' . urlencode('Gagal mengaktifkan: ' . $e->getMessage()));
        exit();
    }
} catch (Throwable $e) {
    header('Location: index.php?error=' . urlencode('Gagal mengaktifkan: ' . $e->getMessage()));
    exit();
}