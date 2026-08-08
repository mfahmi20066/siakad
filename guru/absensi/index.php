<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();
$title = "Absensi";

// SINKRONISASI SESSION: Menggunakan id_ref sebagai ID Guru yang sah
$gid  = $_SESSION['id_ref'];

$filter_kelas = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$filter_tgl   = isset($_GET['tanggal'])  ? $_GET['tanggal']  : '';

// Perbaikan pembentukan klausa WHERE tanpa menembak langsung kolom a.guru_id
$where = "WHERE j.guru_id = '$gid'";
if ($filter_kelas) $where .= " AND a.kelas_id = '$filter_kelas'";
if ($filter_tgl)   $where .= " AND a.tanggal   = '$filter_tgl'";

// PERBAIKAN QUERY UTAMA: Join ke tabel jadwal agar bisa menyaring data berdasarkan guru yang mengajar.
// Data lama yang mapel_id-nya NULL (hasil input admin) juga tetap tampil selama kelasnya diajar guru ini.
$data = mysqli_query($koneksi,
        "SELECT a.*, s.nama, s.nis, k.nama_kelas
         FROM absensi a
         JOIN siswa s ON a.siswa_id = s.id
         JOIN kelas k ON a.kelas_id = k.id
         JOIN jadwal j ON a.kelas_id = j.kelas_id
                       AND j.guru_id = '$gid'
                       AND (a.mapel_id IS NULL OR a.mapel_id = j.mapel_id)
         $where
         ORDER BY a.tanggal DESC, s.nama");

if (!$data) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Kelompokkan data per tanggal & kelas
$grouped = [];
while ($r = mysqli_fetch_assoc($data)) {
    $grouped[$r['tanggal']][$r['kelas_id']][] = $r;
}

// Tampilkan semua kelas untuk filter (tidak dibatasi jadwal)
$kelas_list = mysqli_query($koneksi,
    "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");

// Helper untuk mengubah status singkat (H/S/I/A) menjadi teks panjang
function normalStatus($st) {
    if ($st == 'H') return 'Hadir';
    if ($st == 'S') return 'Sakit';
    if ($st == 'I') return 'Izin';
    if ($st == 'A') return 'Alpa';
    return $st;
}

?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_guru.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_guru.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-clipboard-check text-gold me-2"></i>Absensi Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Filter Kelas</label>
                    <select name="kelas_id" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= $k['id'] ?>"
                            <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kelas']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter Tanggal</label>
                    <input type="date" name="tanggal"
                           class="form-control form-control-sm"
                           value="<?= $filter_tgl ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-4 text-end">
                    <a href="input.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Input Absensi
                    </a>
                    <a href="rekap.php" class="btn btn-info btn-sm">
                        <i class="fas fa-chart-bar"></i> Rekap
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Absensi yang Saya Input
            <span class="badge bg-info ms-2"><?= array_sum(array_map('count', $grouped)) ?> Data</span>
        </div>
        <div class="card-body p-0">

            <?php if (empty($grouped)): ?>
            <div class="alert alert-info text-center m-3 mb-0">
                <i class="fas fa-info-circle"></i> Belum ada data absensi.
            </div>
            <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Tanggal</th>
                            <th style="width:130px">Kelas</th>
                            <th style="width:100px">Siswa</th>
                            <th class="text-center" style="width:70px">Hadir</th>
                            <th class="text-center" style="width:70px">Sakit</th>
                            <th class="text-center" style="width:70px">Izin</th>
                            <th class="text-center" style="width:70px">Alpa</th>
                            <th style="width:100px">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $no_group = 0;
                        foreach ($grouped as $tgl => $perKelas) {
                            foreach ($perKelas as $kid => $rows) {
                                $no_group++;
                                $total = count($rows);
                                $h = $s = $i = $a = 0;
                                foreach ($rows as $r) {
                                    $st = normalStatus($r['status']);
                                    if ($st == 'Hadir') $h++;
                                    elseif ($st == 'Sakit') $s++;
                                    elseif ($st == 'Izin') $i++;
                                    elseif ($st == 'Alpa') $a++;
                                }
                                $nama_kelas = htmlspecialchars($rows[0]['nama_kelas']);
                    ?>
                    <tr>
                        <td><?= $no_group ?></td>
                        <td>
                            <i class="fas fa-calendar-day text-primary me-1"></i>
                            <strong><?= tanggal_indo($tgl, true) ?></strong>
                        </td>
                        <td><span class="badge bg-secondary"><?= $nama_kelas ?></span></td>
                        <td><span class="badge bg-primary"><?= $total ?> Siswa</span></td>
                        <td class="text-center"><span class="badge bg-success"><?= $h ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= $s ?></span></td>
                        <td class="text-center"><span class="badge bg-info"><?= $i ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= $a ?></span></td>
                        <td>
                            <a href="detail.php?tanggal=<?= urlencode($tgl) ?>&kelas_id=<?= $kid ?>"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    <?php
                            }
                        }
                    ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

