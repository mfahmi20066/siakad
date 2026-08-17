<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Pengumuman";

// 1. cek kolom relasi yang tersedia di tabel pengumuman
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM pengumuman");
$kolom_pengumuman = [];
if ($cek_kolom) {
    while ($k = mysqli_fetch_assoc($cek_kolom)) {
        $kolom_pengumuman[] = $k['Field'];
    }
}

// 2. tentuin query sesuai kolom foreign key yang ada
$query_text = "SELECT p.*, 'Admin' AS admin FROM pengumuman p"; // fallback kalo kolom user ga ketemu

if (in_array('id_user', $kolom_pengumuman)) {
    $query_text = "SELECT p.*, u.nama AS admin FROM pengumuman p LEFT JOIN users u ON p.id_user = u.id";
} elseif (in_array('user_id', $kolom_pengumuman)) {
    $query_text = "SELECT p.*, u.nama AS admin FROM pengumuman p LEFT JOIN users u ON p.user_id = u.id";
} elseif (in_array('admin_id', $kolom_pengumuman)) {
    $query_text = "SELECT p.*, u.nama AS admin FROM pengumuman p LEFT JOIN users u ON p.admin_id = u.id";
}

// urutkan tanggal
$query_text .= " ORDER BY p.tanggal DESC";
$data = mysqli_query($koneksi, $query_text);
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-bullhorn text-icon me-2"></i>Pengumuman</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Pengumuman</span>
            <a href="tambah.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Buat Pengumuman
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul & Isi</th>
                            <th>Tanggal</th>
                            <th>Dibuat Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                <tbody>
                <?php 
                $no = 1; 
                if ($data && mysqli_num_rows($data) > 0):
                    while ($r = mysqli_fetch_assoc($data)): 
                        $pembuat = !empty($r['admin']) ? $r['admin'] : 'Administrator';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <strong><?= e($r['judul']) ?></strong>
                        <br>
                        <small class="text-muted">
                            <?= substr(e($r['isi']), 0, 100) ?>...
                        </small>
                    </td>
                    <td>
                        <i class="fas fa-calendar text-muted"></i>
                        <?= tanggal_indo_pendek($r['tanggal']) ?>
                    </td>
                    <td>
                        <i class="fas fa-user-shield text-danger"></i>
                        <?= e($pembuat) ?>
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
                <?php 
                    endwhile; 
                else:
                ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state py-5">
                            <i class="fas fa-bullhorn fa-3x text-muted"></i>
                            <p class="mt-3 mb-0">Belum ada data pengumuman.</p>
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