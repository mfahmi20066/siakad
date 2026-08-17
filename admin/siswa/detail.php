<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Detail Siswa";

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// 1. ambil data siswa & kelasnya
$query_siswa = mysqli_query($koneksi, "
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.id = '$id'
");
$data = mysqli_fetch_assoc($query_siswa);

// 2. ambil nilai siswa secara fleksibel
$query_nilai = mysqli_query($koneksi, "
    SELECT n.*, m.nama_mapel, m.kode_mapel 
    FROM nilai n 
    JOIN mata_pelajaran m ON n.mapel_id = m.id 
    WHERE n.siswa_id = '$id'
");

// 3. ambil rekap absensi (kalo ada)
$q_absensi = mysqli_query($koneksi, "SELECT status, COUNT(*) as jumlah FROM absensi WHERE siswa_id = '$id' GROUP BY status");
$rekap_absen = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];
if ($q_absensi) {
    while ($ab = mysqli_fetch_assoc($q_absensi)) {
        if ($ab['status'] == 'H') $rekap_absen['Hadir'] = $ab['jumlah'];
        if ($ab['status'] == 'S') $rekap_absen['Sakit'] = $ab['jumlah'];
        if ($ab['status'] == 'I') $rekap_absen['Izin'] = $ab['jumlah'];
        if ($ab['status'] == 'A') $rekap_absen['Alfa'] = $ab['jumlah'];
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-user-graduate text-icon me-2"></i>Detail Siswa</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card text-center mb-4">
                <div class="card-body">
                    <?php
                    $foto_s = $data['foto'] ?? '';
                    $foto_s_src = (!empty($foto_s) && file_exists(__DIR__ . '/../../assets/img/foto_siswa/' . $foto_s))
                        ? '/siakad/assets/img/foto_siswa/' . $foto_s
                        : '/siakad/assets/img/default-avatar.png';
                    ?>
                    <img src="<?= $foto_s_src ?>"
                         onerror="this.src='/siakad/assets/img/default-avatar.png'"
                         class="rounded-circle mb-3"
                         width="120" height="120"
                         style="object-fit:cover; border:3px solid #163A63; cursor:pointer"
                         data-bs-toggle="modal" data-bs-target="#modalFoto"
                         data-src="<?= $foto_s_src ?>"
                         title="Klik untuk memperbesar">
                    <h5><?= e($data['nama'] ?? 'Tidak Diketahui') ?></h5>
                    <p class="text-muted mb-1">NIS: <strong><?= $data['nis'] ?? '-' ?></strong></p>
                    <span class="badge bg-info text-dark mb-3"><?= e($data['nama_kelas'] ?? 'Belum Ada Kelas') ?></span>
                    
                    <table class="table table-sm text-start mt-2">
                        <tr>
                            <td><strong>Jenis Kelamin</strong></td>
                            <td>: <?= (isset($data['jenis_kelamin']) && $data['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tempat Lahir</strong></td>
                            <td>: <?= e($data['tempat_lahir'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Lahir</strong></td>
                            <td>: <?= isset($data['tanggal_lahir']) && $data['tanggal_lahir'] != '0000-00-00' ? tanggal_indo($data['tanggal_lahir']) : '-' ?></td>
                        </tr>
                        <tr>
                            <td><strong>No HP</strong></td>
                            <td>: <?= e($data['no_hp'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Orang Tua / Wali</strong></td>
                            <td>: <?= e($data['nama_ortu'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>No. HP Ortu</strong></td>
                            <td>: <?= e($data['no_hp_ortu'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tahun Ajaran</strong></td>
                            <td>: <?= e($data['tahun_ajaran'] ?? '-') ?></td>
                        </tr>
                    </table>
                    <p class="text-muted small text-start">
                        <i class="fas fa-map-marker-alt text-danger"></i> <?= e($data['alamat'] ?? '-') ?>
                    </p>
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm w-100">
                        <i class="fas fa-edit"></i> Edit Data
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-check"></i> Rekap Absensi
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <h5 class="text-success mb-0"><?= $rekap_absen['Hadir'] ?></h5>
                            <small class="text-muted">Hadir</small>
                        </div>
                        <div class="col-3">
                            <h5 class="text-primary mb-0"><?= $rekap_absen['Sakit'] ?></h5>
                            <small class="text-muted">Sakit</small>
                        </div>
                        <div class="col-3">
                            <h5 class="text-warning mb-0"><?= $rekap_absen['Izin'] ?></h5>
                            <small class="text-muted">Izin</small>
                        </div>
                        <div class="col-3">
                            <h5 class="text-danger mb-0"><?= $rekap_absen['Alfa'] ?></h5>
                            <small class="text-muted">Alfa</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-star"></i> Data Nilai Siswa
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Harian</th>
                                    <th>UTS</th>
                                    <th>UAS</th>
                                    <th>Nilai Akhir</th>
                                    <th>Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $no = 1; 
                            if ($query_nilai && mysqli_num_rows($query_nilai) > 0):
                                while ($r = mysqli_fetch_assoc($query_nilai)): 
                                    // amankan akses array key biar ga warning undefined key
                                    $harian = $r['nilai_harian'] ?? $r['harian'] ?? $r['tugas'] ?? 0;
                                    $uts    = $r['uts'] ?? $r['nilai_uts'] ?? 0;
                                    $uas    = $r['uas'] ?? $r['nilai_uas'] ?? 0;
                                    $akhir  = $r['nilai_akhir'] ?? $r['akhir'] ?? 0;
                                    $sem    = $r['semester'] ?? '-';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= e($r['nama_mapel']) ?></strong>
                                </td>
                                <td><?= number_format((float)$harian, 2) ?></td>
                                <td><?= number_format((float)$uts, 2) ?></td>
                                <td><?= number_format((float)$uas, 2) ?></td>
                                <td>
                                    <span class="badge bg-success fs-6"><?= number_format((float)$akhir, 2) ?></span>
                                </td>
                                <td>Semester <?= e($sem) ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Belum ada data nilai untuk siswa ini.</td>
                            </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:transparent;border:none;box-shadow:none">
            <div class="modal-body text-center">
                <img id="modalFotoImg" src="" class="img-fluid rounded shadow-lg"
                     style="max-height:85vh;object-fit:contain;background:#fff">
            </div>
            <div class="text-center mt-2">
                <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var foto = document.getElementById('modalFotoImg');
    document.querySelectorAll('[data-bs-target="#modalFoto"]').forEach(function (img) {
        img.addEventListener('click', function () {
            foto.src = img.getAttribute('data-src');
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>