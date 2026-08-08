<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Seleksi SPMB";

// Filter by jalur
$jalur_filter = isset($_GET['jalur']) ? (int)$_GET['jalur'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Query pendaftar yang sudah diverifikasi
$query = "SELECT sp.*, sj.nama_jalur, sg.nama_gelombang 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
          WHERE sp.status='diverifikasi'";

if (!empty($jalur_filter)) {
    $query .= " AND sp.jalur_id = $jalur_filter";
}

if (!empty($search)) {
    $query .= " AND (sp.nama_lengkap LIKE '%$search%' OR sp.no_pendaftaran LIKE '%$search%')";
}

$query .= " ORDER BY sp.skor_seleksi DESC, sp.created_at ASC";

if (isset($_GET['rank'])) {
    $data = mysqli_query($koneksi, $query);
} else {
    $data = mysqli_query($koneksi, $query);
}

// Ambil jalur
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-trophy text-gold me-2"></i>Seleksi SPMB</h4>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
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
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nama / No. Pendaftaran" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-4" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Ranking Pendaftar</span>
                <span class="badge bg-info ms-2">
                    <?php echo $data ? mysqli_num_rows($data) : 0; ?> pendaftar
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th width="60">Rank</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Jalur</th>
                            <th>Gelombang</th>
                            <th>Skor Seleksi</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($row = mysqli_fetch_assoc($data)):
                        ?>
                        <tr>
                            <td><span class="badge bg-info text-dark" style="font-size: 14px; padding: 6px 10px;"><?php echo $rank++; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['no_pendaftaran']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_jalur']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_gelombang']); ?></td>
                            <td>
                                <input type="number" class="form-control form-control-sm" style="width: 100px;" 
                                    value="<?php echo $row['skor_seleksi'] ?? ''; ?>" 
                                    placeholder="0.00" id="skor_<?php echo $row['id']; ?>">
                            </td>
                            <td>
                                <button type="button" class="btn btn-warning btn-sm" onclick="setLolos(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state py-5">
                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                <p>Belum ada pendaftar yang sudah diverifikasi.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <span><i class="fas fa-filter"></i> Set Batas Kuota Lolos</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="kuota" class="form-label">Batas Kuota Lolos</label>
                    <input type="number" class="form-control" id="kuota" placeholder="Contoh: 30" min="1">
                    <small class="text-muted">Pendaftar terbaik hingga batas ini akan lolos seleksi</small>
                </div>
            </div>
            <button type="button" class="btn btn-success" onclick="finalisasiLolos()">
                <i class="fas fa-check-double me-2"></i> Set Lolos Seleksi
            </button>
        </div>
    </div>
</div>

<script>
function setLolos(id) {
    const skor = document.getElementById('skor_' + id).value;
    if (skor === '' || skor < 0) {
        siToast('warning', 'Masukkan skor seleksi terlebih dahulu!');
        return;
    }
    
    siConfirm({
        icon: 'question',
        title: 'Set pendaftar ini sebagai LOLOS SELEKSI?',
        confirmText: 'Ya, Lolos'
    }).then(function (ok) {
        if (ok) window.location.href = 'seleksi.php?id=' + id + '&action=set_lolos&skor=' + skor;
    });
}

function finalisasiLolos() {
    const kuota = document.getElementById('kuota').value;
    if (!kuota || kuota <= 0) {
        siToast('warning', 'Masukkan batas kuota!');
        return;
    }
    
    siConfirm({
        icon: 'question',
        title: 'Set ' + kuota + ' pendaftar terbaik sebagai LOLOS SELEKSI?',
        confirmText: 'Ya, Lolos'
    }).then(function (ok) {
        if (ok) window.location.href = 'seleksi.php?action=finalisasi&kuota=' + kuota;
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
