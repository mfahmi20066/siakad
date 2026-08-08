<?php
include '../../../config/koneksi.php';
include '../../../config/session.php';
cekAdmin();

$title = "Data Pendaftar SPMB";

// Filter
$gelombang_filter = isset($_GET['gelombang']) ? (int)$_GET['gelombang'] : '';
$jalur_filter = isset($_GET['jalur']) ? (int)$_GET['jalur'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Query dasar
$query = "SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          WHERE 1=1";

// Filter by gelombang
if (!empty($gelombang_filter)) {
    $query .= " AND sp.gelombang_id = $gelombang_filter";
}

// Filter by jalur
if (!empty($jalur_filter)) {
    $query .= " AND sp.jalur_id = $jalur_filter";
}

// Filter by status
if (!empty($status_filter)) {
    $query .= " AND sp.status = '$status_filter'";
}

// Search
if (!empty($search)) {
    $query .= " AND (sp.nama_lengkap LIKE '%$search%' OR sp.no_pendaftaran LIKE '%$search%')";
}

$query .= " ORDER BY sp.created_at DESC";
$data = mysqli_query($koneksi, $query);

// Ambil gelombang
$query_gelombang = mysqli_query($koneksi, "SELECT * FROM spmb_gelombang WHERE status='aktif' ORDER BY tanggal_mulai ASC");

// Ambil jalur
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");
?>
<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-user-graduate text-gold me-2"></i>Data Pendaftar SPMB</h4>
    </div>

    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="gelombang" class="form-label">Gelombang</label>
                    <select class="form-select" id="gelombang" name="gelombang">
                        <option value="">Semua Gelombang</option>
                        <?php while ($g = mysqli_fetch_assoc($query_gelombang)): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $gelombang_filter == $g['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['nama_gelombang']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="jalur" class="form-label">Jalur</label>
                    <select class="form-select" id="jalur" name="jalur">
                        <option value="">Semua Jalur</option>
                        <?php while ($j = mysqli_fetch_assoc($query_jalur)): ?>
                        <option value="<?php echo $j['id']; ?>" <?php echo $jalur_filter == $j['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($j['nama_jalur']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="menunggu_dokumen" <?php echo $status_filter == 'menunggu_dokumen' ? 'selected' : ''; ?>>Menunggu Dokumen</option>
                        <option value="menunggu_verifikasi" <?php echo $status_filter == 'menunggu_verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                        <option value="diverifikasi" <?php echo $status_filter == 'diverifikasi' ? 'selected' : ''; ?>>Diverifikasi</option>
                        <option value="lolos_seleksi" <?php echo $status_filter == 'lolos_seleksi' ? 'selected' : ''; ?>>Lolos Seleksi</option>
                        <option value="diterima" <?php echo $status_filter == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                        <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nama / No. Pendaftaran" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Filter & Cari
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Daftar Pendaftar</span>
                <span class="badge bg-primary ms-2"><?php echo mysqli_num_rows($data); ?> pendaftar</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Jalur</th>
                            <th>Gelombang</th>
                            <th>Status</th>
                            <th>Tgl. Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($data && mysqli_num_rows($data) > 0):
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($data)):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['no_pendaftaran']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_jalur']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_gelombang']); ?></td>
                            <td>
                                <?php
                                $status_config = [
                                    'menunggu_dokumen' => ['label' => 'Menunggu Dokumen', 'color' => 'warning'],
                                    'menunggu_verifikasi' => ['label' => 'Menunggu Verifikasi', 'color' => 'warning'],
                                    'diverifikasi' => ['label' => 'Diverifikasi', 'color' => 'info'],
                                    'lolos_seleksi' => ['label' => 'Lolos Seleksi', 'color' => 'info'],
                                    'diterima' => ['label' => 'Diterima', 'color' => 'success'],
                                    'ditolak' => ['label' => 'Ditolak', 'color' => 'danger'],
                                ];
                                
                                $config = $status_config[$row['status']] ?? ['label' => $row['status'], 'color' => 'secondary'];
                                echo '<span class="badge bg-' . $config['color'] . '">' . $config['label'] . '</span>';
                                ?>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="verifikasi.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" title="Verifikasi">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state py-5">
                                    <i class="fas fa-user-graduate fa-3x text-muted"></i>
                                    <p class="mt-3 mb-0">Belum ada data pendaftar.</p>
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

<?php include '../../../includes/footer.php'; ?>
