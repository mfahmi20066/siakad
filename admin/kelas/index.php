<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Kelas";

$data = mysqli_query($koneksi,
        "SELECT k.*, g.nama AS wali 
         FROM kelas k 
         LEFT JOIN guru g ON k.wali_kelas = g.id 
         ORDER BY k.tingkat, k.nama_kelas");

// Hitung jumlah siswa per kelas
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-school text-gold me-2"></i>Data Kelas</h4>
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Kelas</span>
            <div class="btn-group">
                <a href="tambah.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Kelas
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Wali Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Siswa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <?php
                    // Hitung siswa di kelas ini
                    $jml_siswa = mysqli_fetch_row(mysqli_query($koneksi,
                        "SELECT COUNT(*) FROM siswa WHERE kelas_id='{$r['id']}'"))[0];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['nama_kelas']) ?></strong>
                    </td>
                    <td>
                        <span class="badge bg-secondary">Kelas <?= $r['tingkat'] ?></span>
                    </td>
                    <td>
                        <?php if ($r['wali']): ?>
                            <i class="fas fa-user-tie text-success"></i>
                            <?= htmlspecialchars($r['wali']) ?>
                        <?php else: ?>
                            <span class="text-muted">Belum ditentukan</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['tahun_ajaran'] ?></td>
                    <td>
                        <span class="badge bg-info"><?= $jml_siswa ?> Siswa</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="edit.php?id=<?= $r['id'] ?>"
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>')"
                                    class="btn btn-danger btn-sm" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>