<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Siswa";

$data = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas, k.tingkat
        FROM siswa s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        ORDER BY CONVERT(k.tingkat, UNSIGNED), k.nama_kelas, s.nama");

$kelas_list = mysqli_query($koneksi, "SELECT DISTINCT nama_kelas FROM kelas WHERE status='aktif' ORDER BY nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-user-graduate text-icon me-2"></i>Data Siswa</h4>
    </div>

    <!-- Notifikasi sukses -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Siswa</span>
            <div class="btn-group">
                <a href="tambah.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Siswa
                </a>
                <a href="cetak_siswa.php" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Cetak/Export
                </a>
            </div>
        </div>
        <div class="card-body">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Filter Kelas</label>
                    <select class="form-select form-select-sm table-filter-select"
                            data-table="#tabelSiswa" data-column="4">
                        <option value="">-- Semua Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= e($k['nama_kelas']) ?>">
                            <?= e($k['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Urutkan</label>
                    <select class="form-select form-select-sm table-sort-select"
                            data-table="#tabelSiswa">
                        <option value="">-- Default --</option>
                        <option value="3:asc">Nama (A-Z)</option>
                        <option value="3:desc">Nama (Z-A)</option>
                        <option value="4:asc">Kelas (A-Z)</option>
                        <option value="4:desc">Kelas (Z-A)</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
            <table id="tabelSiswa" class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jenis Kelamin</th>
                        <th>No HP</th>
                        <th>Orang Tua / Wali</th>
                        <th>No. HP Ortu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="10">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php
                        $foto_s = $r['foto'] ?? '';
                        $foto_s_src = (!empty($foto_s) && file_exists(__DIR__ . '/../../assets/img/foto_siswa/' . $foto_s))
                            ? '/siakad/assets/img/foto_siswa/' . $foto_s
                            : '/siakad/assets/img/default-avatar.png';
                        ?>
                        <img src="<?= $foto_s_src ?>" alt="Foto" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                    </td>
                    <td><?= $r['nis'] ?></td>
                    <td><?= e($r['nama']) ?></td>
                    <td>
                        <span class="badge bg-info"><?= $r['nama_kelas'] ?></span>
                    </td>
                    <td>
                        <?php if ($r['jenis_kelamin'] == 'L'): ?>
                            <span class="badge bg-primary">Laki-laki</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Perempuan</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['no_hp'] ?></td>
                    <td><?= e($r['nama_ortu'] ?? '-') ?></td>
                    <td><?= e($r['no_hp_ortu'] ?? '-') ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="detail.php?id=<?= $r['id'] ?>"
                               class="btn btn-info btn-sm" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
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