<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Pengumuman";

// QUERY SAPU BERSIH: Tanpa JOIN agar 100% bebas dari error kolom database mana pun
$data = mysqli_query($koneksi, "SELECT * FROM pengumuman ORDER BY tanggal DESC");

if (!$data) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>
<?php include '../../includes/topbar_guru.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-bullhorn text-icon me-2"></i>Pengumuman Sekolah</h4>
    </div>

    <?php if (mysqli_num_rows($data) > 0): ?>
        <?php while ($r = mysqli_fetch_assoc($data)): ?>
        <div class="card mb-3 border-start border-primary border-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="card-title mb-1">
                        <i class="fas fa-bullhorn text-primary"></i>
                        <?= e($r['judul']) ?>
                    </h6>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-calendar"></i>
                        <?= tanggal_indo_pendek($r['tanggal']) ?>
                    </span>
                </div>
                <small class="text-muted mb-2 d-block">
                    <i class="fas fa-user-shield"></i>
                    Oleh: Administrator Sekolah
                </small>
                <p class="card-text mb-0">
                    <?= nl2br(e($r['isi'])) ?>
                </p>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-bullhorn fa-3x mb-3"></i>
            <p>Belum ada pengumuman dari sekolah.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>