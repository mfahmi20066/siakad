<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Nilai Siswa";

$filter_kelas = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$filter_sem   = isset($_GET['semester']) ? $_GET['semester'] : '';

// Memperbaiki klausa filter agar mengacu ke tabel siswa (s.kelas_id)
$where = "WHERE 1=1";
if ($filter_kelas) $where .= " AND s.kelas_id = '$filter_kelas'";
if ($filter_sem)   $where .= " AND n.semester = '$filter_sem'";

// Memperbaiki query utama dengan melakukan JOIN kelas melalui tabel siswa (s.kelas_id)
$data = mysqli_query($koneksi,
        "SELECT n.*, s.nama AS nama_siswa, s.nis,
                m.nama_mapel, k.nama_kelas, k.tingkat,
                guru_map.nama_guru
         FROM nilai n
         JOIN siswa s ON n.siswa_id = s.id
         JOIN mata_pelajaran m ON n.mapel_id = m.id
         LEFT JOIN kelas k ON s.kelas_id = k.id
         LEFT JOIN (
             SELECT kmg.mapel_id, kmg.kelas_id, GROUP_CONCAT(g.nama SEPARATOR ', ') AS nama_guru
             FROM kelas_mapel_guru kmg
             JOIN guru g ON g.id = kmg.guru_id
             GROUP BY kmg.mapel_id, kmg.kelas_id
         ) guru_map ON guru_map.mapel_id = n.mapel_id AND guru_map.kelas_id = s.kelas_id
         $where
         ORDER BY k.tingkat, k.nama_kelas, s.nama, m.nama_mapel");

$kelas_list = mysqli_query($koneksi,
              "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-star text-icon me-2"></i>Nilai Siswa</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?>
    </div>
    <?php endif; ?>

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
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select form-select-sm">
                        <option value="">Semua Semester</option>
                        <option value="1" <?= $filter_sem == '1' ? 'selected' : '' ?>>
                            Semester 1
                        </option>
                        <option value="2" <?= $filter_sem == '2' ? 'selected' : '' ?>>
                            Semester 2
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <a href="input.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Input Nilai
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Daftar Nilai Siswa
        </div>
        <div class="card-body">

            <!-- table-responsive: tabel bisa di-scroll horizontal di layar kecil,
                 supaya kolom tidak berantakan / bertabrakan -->
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle" style="min-width:1100px;">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th style="width:40px;">#</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th class="text-center">Harian</th>
                            <th class="text-center">UTS</th>
                            <th class="text-center">UAS</th>
                            <th class="text-center">Kehadiran</th>
                            <th class="text-center">Nilai Akhir</th>
                            <th class="text-center">Smt</th>
                            <th class="text-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    $kelas_aktif = null; // untuk mendeteksi pergantian kelas
                    if ($data && mysqli_num_rows($data) > 0):
                        while ($r = mysqli_fetch_assoc($data)):
                            // Mengamankan pemanggilan komponen nilai menggunakan null coalescing
                            $harian = $r['nilai_harian'] ?? $r['harian'] ?? $r['tugas'] ?? 0;
                            $uts    = $r['nilai_uts'] ?? $r['uts'] ?? 0;
                            $uas    = $r['nilai_uas'] ?? $r['uas'] ?? 0;
                            $na     = $r['nilai_akhir'] ?? $r['akhir'] ?? 0;
                            $sem    = $r['semester'] ?? '-';
                            $nama_k = $r['nama_kelas'] ?? 'Tanpa Kelas';

                            if ($na >= 90) $predikat = 'A';
                            elseif ($na >= 80) $predikat = 'B';
                            elseif ($na >= 70) $predikat = 'C';
                            elseif ($na >= 60) $predikat = 'D';
                            else $predikat = 'E';

                            // Baris header pemisah setiap kali kelas berganti
                            if ($nama_k !== $kelas_aktif):
                                $kelas_aktif = $nama_k;
                    ?>
                        <tr class="table-primary">
                            <td colspan="13">
                                <i class="fas fa-chalkboard"></i>
                                <strong>Kelas <?= e($nama_k) ?></strong>
                            </td>
                        </tr>
                    <?php endif; ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="text-nowrap"><?= e($r['nis']) ?></td>
                            <td class="text-nowrap"><?= e($r['nama_siswa']) ?></td>
                            <td class="text-nowrap">
                                <span class="badge bg-info text-dark"><?= e($nama_k) ?></span>
                            </td>
                            <td class="text-nowrap"><?= e($r['nama_mapel']) ?></td>
                            <td class="text-nowrap"><?= e($r['nama_guru'] ?? '-') ?></td>
                            <td class="text-center"><?= number_format((float)$harian, 2) ?></td>
                            <td class="text-center"><?= number_format((float)$uts, 2) ?></td>
                            <td class="text-center"><?= number_format((float)$uas, 2) ?></td>
                            <td class="text-center">
                                <?php if (isset($r['nilai_kehadiran']) && $r['nilai_kehadiran'] !== null): ?>
                                <span class="badge bg-<?= $r['nilai_kehadiran'] >= 75 ? 'success' : 'danger' ?>">
                                    <?= number_format((float)$r['nilai_kehadiran'], 0) ?>%
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <strong class="<?= $na >= 75 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format((float)$na, 2) ?>
                                </strong>
                                <span class="badge bg-<?= $na >= 75 ? 'success' : 'danger' ?> ms-1">
                                    <?= $predikat ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?= e($sem) ?></span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="edit.php?id=<?= $r['id'] ?>"
                                   class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>')"
                                        class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="13" class="text-center text-muted py-3">Tidak ada data nilai yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>