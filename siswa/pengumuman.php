<?php
include '../config/koneksi.php';
include '../config/session.php';
cekSiswa();
$title = "Pengumuman";

// Query alternatif tanpa JOIN langsung pada kolom id admin yang bermasalah
$data = mysqli_query($koneksi,
        "SELECT *, 'Admin Sekolah' AS admin 
         FROM pengumuman 
         ORDER BY tanggal DESC");
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_siswa.php'; ?>

<div class="main-content">
    <?php include '../includes/topbar_siswa.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-bullhorn text-gold me-2"></i>Pengumuman Sekolah</h4>
    </div>

    <?php if ($data && mysqli_num_rows($data) > 0): ?>
        <?php while ($r = mysqli_fetch_assoc($data)): ?>
        <div class="card mb-3 border-start border-primary border-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bullhorn text-primary"></i>
                        <?= htmlspecialchars($r['judul']) ?>
                    </h6>
                    <span class="badge bg-light text-dark ms-2 text-nowrap">
                        <i class="fas fa-calendar"></i>
                        <?= tanggal_indo_pendek($r['tanggal']) ?>
                    </span>
                </div>
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-user-shield"></i>
                    Diumumkan oleh: <?= htmlspecialchars($r['admin']) ?>
                </small>
                <p class="card-text mb-0">
                    <?= nl2br(htmlspecialchars($r['isi'])) ?>
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

<?php include '../includes/footer.php'; ?>