<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Rapor Siswa";

$data = mysqli_query($koneksi,
        "SELECT r.*, s.nama, s.nis, k.nama_kelas
         FROM rapor r
         JOIN siswa s ON r.siswa_id = s.id
         JOIN kelas k ON r.kelas_id = k.id
         ORDER BY k.nama_kelas, s.nama");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-file-alt text-icon me-2"></i>Rapor Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
<?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Rapor</span>
            <div>
                <a href="cetak_kelas.php?action=form" class="btn btn-warning btn-sm">
                    <i class="fas fa-print"></i> Cetak via Kelas
                </a>
                <a href="tambah.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Buat Rapor
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Semester</th>
                            <th>Tahun Ajaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php 
                $no = 1; 
                if ($data && mysqli_num_rows($data) > 0):
                    while ($r = mysqli_fetch_assoc($data)): 
                        
                        // pake status_kenaikan (aktif/naik/tinggal), bukan status (draft/final)
                        $raw_status = isset($r['status_kenaikan']) ? strtolower(trim($r['status_kenaikan'])) : '';
                        
                        $status_badge = [
                            'aktif'       => 'primary',
                            'naik kelas'  => 'success',
                            'tinggal kelas' => 'danger'
                        ];
                        
                        $status_label = [
                            'aktif'       => 'Aktif',
                            'naik kelas'  => 'Naik Kelas',
                            'tinggal kelas' => 'Tinggal Kelas'
                        ];

                        // kalo status_kenaikan ada di daftar opsi
                        if (array_key_exists($raw_status, $status_label)) {
                            $badge_class = $status_badge[$raw_status];
                            $text_display = $status_label[$raw_status];
                        } else {
                            // kalo status kosong/aneh, tampilkan default abu-abu
                            $badge_class = 'secondary';
                            $text_display = !empty($raw_status) ? ucfirst($r['status_kenaikan']) : 'Diproses';
                        }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($r['nis']) ?></td>
                    <td><?= e($r['nama']) ?></td>
                    <td>
                        <span class="badge bg-info text-dark"><?= e($r['nama_kelas']) ?></span>
                    </td>
                    <td>Semester <?= e($r['semester']) ?></td>
                    <td><?= e($r['tahun_ajaran']) ?></td>
                    <td>
                        <span class="badge bg-<?= $badge_class ?>">
                            <?= e($text_display) ?>
                        </span>
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
                    <td colspan="8">
                        <div class="empty-state py-5">
                            <i class="fas fa-file-alt fa-3x text-muted"></i>
                            <p class="mt-3 mb-0">Belum ada data rapor siswa.</p>
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