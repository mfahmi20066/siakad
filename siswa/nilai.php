<?php
include '../config/koneksi.php';
include '../config/session.php';
cekSiswa();
$title = "Nilai Saya";

$sid        = $_SESSION['siswa_id'] ?? $_SESSION['id_ref'] ?? $_SESSION['user_id'] ?? 0;
$filter_sem = $_GET['semester'] ?? '';

$where = "WHERE n.siswa_id = '$sid'";
if ($filter_sem) $where .= " AND n.semester = '$filter_sem'";

$data = mysqli_query($koneksi,
        "SELECT n.*, m.nama_mapel
         FROM nilai n JOIN mata_pelajaran m ON n.mapel_id = m.id
         $where
         ORDER BY n.semester, m.nama_mapel");

$avg    = mysqli_fetch_row(mysqli_query($koneksi,
          "SELECT AVG(nilai_akhir) FROM nilai WHERE siswa_id='$sid'"))[0] ?? 0;
$lulus  = mysqli_fetch_row(mysqli_query($koneksi,
          "SELECT COUNT(*) FROM nilai WHERE siswa_id='$sid' AND nilai_akhir>=75"))[0] ?? 0;
$remidi = mysqli_fetch_row(mysqli_query($koneksi,
          "SELECT COUNT(*) FROM nilai WHERE siswa_id='$sid' AND nilai_akhir<75"))[0] ?? 0;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_siswa.php'; ?>
<?php include '../includes/topbar_siswa.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-star text-icon me-2"></i>Nilai Saya</h4>
    </div>

    <!-- Ringkasan -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center text-white bg-<?= round($avg) >= 75 ? 'success' : 'warning' ?>">
                <div class="card-body py-3">
                    <h3><?= round($avg, 2) ?></h3>
                    <p class="mb-0">Rata-rata Nilai</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center text-white bg-success">
                <div class="card-body py-3">
                    <h3><?= $lulus ?></h3>
                    <p class="mb-0">Mapel Lulus (&ge;75)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center text-white bg-danger">
                <div class="card-body py-3">
                    <h3><?= $remidi ?></h3>
                    <p class="mb-0">Perlu Remidi (&lt;75)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Filter Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">Semua Semester</option>
                        <option value="1" <?= $filter_sem=='1'?'selected':'' ?>>Semester 1</option>
                        <option value="2" <?= $filter_sem=='2'?'selected':'' ?>>Semester 2</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="nilai.php" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Nilai -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Nilai
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Nilai Harian</th>
                            <th>Nilai UTS</th>
                            <th>Nilai UAS</th>
                            <th>Nilai Akhir</th>
                            <th>Predikat</th>
                            <th>Semester</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)):
                        $na = $r['nilai_akhir'] ?? 0;

                        // fix: deteksi otomatis nama kolom nilai harian
                        $nh = $r['nilai_harian'] ?? $r['nilai_uh'] ?? $r['uh'] ?? $r['nilai_ulangan'] ?? 0;
                        $nu = $r['nilai_uts']    ?? $r['uts'] ?? 0;
                        $nuas = $r['nilai_uas']  ?? $r['uas'] ?? 0;

                        if ($na >= 90)     $p = 'A';
                        elseif ($na >= 80) $p = 'B';
                        elseif ($na >= 70) $p = 'C';
                        elseif ($na >= 60) $p = 'D';
                        else               $p = 'E';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($r['nama_mapel']) ?></td>
                        <td><?= $nh ?></td>
                        <td><?= $nu ?></td>
                        <td><?= $nuas ?></td>
                        <td>
                            <strong class="<?= $na >= 75 ? 'text-success' : 'text-danger' ?>">
                                <?= $na ?>
                            </strong>
                        </td>
                        <td>
                            <span class="badge bg-<?= $na >= 75 ? 'success' : 'danger' ?>">
                                <?= $p ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary">Smt <?= $r['semester'] ?></span>
                        </td>
                        <td>
                            <?php if ($na >= 75): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> Lulus
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> Remidi
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($data) == 0): ?>
            <div class="empty-state py-5">
                <i class="fas fa-star fa-3x text-muted"></i>
                <p class="mt-3 mb-0">Belum ada data nilai.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>