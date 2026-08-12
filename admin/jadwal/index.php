<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Jadwal Pelajaran";

// Filter berdasarkan kelas
$filter_kelas = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$filter_hari  = isset($_GET['hari'])     ? $_GET['hari']     : '';

$where = "WHERE 1=1";
if ($filter_kelas) $where .= " AND j.kelas_id = '$filter_kelas'";
if ($filter_hari)  $where .= " AND j.hari = '$filter_hari'";

$data = mysqli_query($koneksi,
        "SELECT j.*, k.nama_kelas, m.nama_mapel, g.nama AS nama_guru
         FROM jadwal j
         JOIN kelas k ON j.kelas_id = k.id
         JOIN mata_pelajaran m ON j.mapel_id = m.id
         JOIN guru g ON j.guru_id = g.id
         $where
         ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat'),
                  j.jam_mulai");

$kelas_list = mysqli_query($koneksi,
              "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-calendar-alt text-icon me-2"></i>Jadwal Pelajaran</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= e($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Filter Kelas</label>
                    <select name="kelas_id" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= $k['id'] ?>"
                            <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>>
                            <?= e($k['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter Hari</label>
                    <select name="hari" class="form-select form-select-sm">
                        <option value="">Semua Hari</option>
                        <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $h): ?>
                        <option value="<?= $h ?>"
                            <?= $filter_hari == $h ? 'selected' : '' ?>>
                            <?= $h ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <a href="tambah.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Jadwal
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Jadwal -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Jadwal Pelajaran
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
                        <th>Hari</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Durasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <?php /* tbody dibiarkan kosong: DataTables menampilkan "Tidak ada data" (zeroRecords).
                            Jangan buat <tr><td colspan=N> tunggal di dalam DataTables — memicu
                            "Incorrect column count" (jumlah sel != jumlah kolom). */ ?>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <?php
                    // Hitung durasi
                    $mulai   = strtotime($r['jam_mulai']);
                    $selesai = strtotime($r['jam_selesai']);
                    $durasi  = round(($selesai - $mulai) / 60) . ' mnt';

                    // Warna badge hari
                    $warna_hari = [
                        'Senin'  => 'primary',
                        'Selasa' => 'success',
                        'Rabu'   => 'warning',
                        'Kamis'  => 'info',
                        'Jumat'  => 'danger'
                    ];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <span class="badge bg-info">
                            <?= e($r['nama_kelas']) ?>
                        </span>
                    </td>
                    <td><?= e($r['nama_mapel']) ?></td>
                    <td>
                        <i class="fas fa-user-tie text-success"></i>
                        <?= e($r['nama_guru']) ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $warna_hari[$r['hari']] ?? 'secondary' ?>">
                            <?= $r['hari'] ?>
                        </span>
                    </td>
                    <td><?= substr($r['jam_mulai'], 0, 5) ?></td>
                    <td><?= substr($r['jam_selesai'], 0, 5) ?></td>
                    <td>
                        <span class="text-muted small"><?= $durasi ?></span>
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