<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Tahun Ajaran";

$pdo = tahun_ajaran_pdo();
$data = listTahunAjaran($pdo);
$aktif = null;
try { $aktif = getTahunAjaranAktif($pdo); } catch (Throwable $e) { $aktif = null; }

// Map semester per tahun ajaran
$semesterMap = [];
foreach ($data as $ta) {
    $semesterMap[$ta['id']] = getSemestersByTahunAjaran($pdo, (int)$ta['id']);
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-calendar-alt text-gold me-2"></i>Tahun Ajaran</h4>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Tahun Ajaran
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <?php if ($aktif): ?>
    <div class="alert alert-info py-2">
        <i class="fas fa-info-circle"></i>
        Tahun Ajaran aktif saat ini: <strong><?= htmlspecialchars($aktif['tahun']) ?></strong>
    </div>
    <?php else: ?>
    <div class="alert alert-warning py-2">
        <i class="fas fa-exclamation-triangle"></i> Belum ada tahun ajaran yang berstatus aktif.
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <span><i class="fas fa-list"></i> Riwayat Tahun Ajaran</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    if ($data):
                        foreach ($data as $r):
                            $sem = $semesterMap[$r['id']] ?? [];
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($r['nama_tahun_ajaran']) ?></strong></td>
                        <td>
                            <?php foreach ($sem as $s): ?>
                                <span class="badge bg-<?= ($s['status'] == 'aktif') ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['nama']) ?></span>
                            <?php endforeach; ?>
                            <?php if (!$sem): ?><span class="text-muted">-</span><?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= $r['tanggal_mulai'] ? tanggal_indo_pendek($r['tanggal_mulai']) : '-' ?>
                                s/d
                                <?= $r['tanggal_selesai'] ? tanggal_indo_pendek($r['tanggal_selesai']) : '-' ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($r['status'] == 'aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <?php if ($r['status'] != 'aktif'): ?>
                                <a href="aktifkan.php?id=<?= $r['id'] ?>" class="btn btn-success btn-sm" title="Jadikan Aktif">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>', '<?= htmlspecialchars($r['nama_tahun_ajaran']) ?>')"
                                        class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state py-5">
                                <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                <p class="mt-3 mb-0">Belum ada data tahun ajaran.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>