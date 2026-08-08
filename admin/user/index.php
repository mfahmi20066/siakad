<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Siswa";

$data = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas 
        FROM siswa s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        ORDER BY s.nama");

$kelas_list = mysqli_query($koneksi, "SELECT DISTINCT nama_kelas FROM kelas ORDER BY nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-user-graduate text-gold me-2"></i>Data Siswa</h4>
    </div>

    <!-- Notifikasi sukses -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Siswa</span>
            <a href="tambah.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Filter Kelas</label>
                    <select class="form-select form-select-sm table-filter-select"
                            data-table="#tabelSiswa" data-column="3">
                        <option value="">-- Semua Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= htmlspecialchars($k['nama_kelas']) ?>">
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Urutkan</label>
                    <select class="form-select form-select-sm table-sort-select"
                            data-table="#tabelSiswa">
                        <option value="">-- Default --</option>
                        <option value="2:asc">Nama (A-Z)</option>
                        <option value="2:desc">Nama (Z-A)</option>
                        <option value="3:asc">Kelas (A-Z)</option>
                        <option value="3:desc">Kelas (Z-A)</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tabelSiswa" class="table table-hover dataTable align-middle"
                       data-export="excel" data-export-title="Data Siswa">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Jenis Kelamin</th>
                            <th>No HP</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $r['nis'] ?></td>
                        <td><?= htmlspecialchars($r['nama']) ?></td>
                        <td>
                            <span class="badge bg-info"><?= $r['nama_kelas'] ?></span>
                        </td>
                        <td>
                            <?php if ($r['jenis_kelamin'] == 'L'): ?>
                                <span class="badge bg-primary">Laki-laki</span>
                            <?php else: ?>
                                <span class="badge" style="background:#E11D48">Perempuan</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $r['no_hp'] ?></td>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>