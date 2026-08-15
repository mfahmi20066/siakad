<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Jadwal";

$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data       = mysqli_fetch_assoc(mysqli_query($koneksi,
              "SELECT * FROM jadwal WHERE id='$id'"));

if (!$data) {
    header("Location: index.php?error=Data jadwal tidak ditemukan");
    exit();
}
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");
$mapel_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");
$guru_list  = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kid     = $_POST['kelas_id'];
    $mid     = $_POST['mapel_id'];
    $gid     = $_POST['guru_id'];
    $hari    = $_POST['hari'];
    $mulai   = $_POST['jam_mulai'];
    $selesai = $_POST['jam_selesai'];
    $error   = null;

    // validasi jam
    if (!in_array($hari, ['Senin','Selasa','Rabu','Kamis','Jumat'], true)) {
        $error = "Hari tidak valid. Hanya Senin s.d Jumat yang diizinkan (Sabtu/Minggu ditolak).";
    } elseif ($selesai <= $mulai) {
        $error = "Jam selesai harus lebih dari jam mulai!";
    } else {
        // cek bentrok jadwal kelas, kecuali record sendiri
        $cek_bentrok = mysqli_query($koneksi,
            "SELECT id FROM jadwal
             WHERE kelas_id = '$kid'
             AND hari = '$hari'
             AND id != '$id'
             AND (
                 '$mulai' < jam_selesai AND '$selesai' > jam_mulai
             )");

        if (mysqli_num_rows($cek_bentrok) > 0) {
            $error = "Jadwal bentrok! Kelas tersebut sudah memiliki jadwal 
                      di hari dan jam yang sama.";
        } else {
            // cek bentrok jadwal guru (di kelas lain), kecuali record sendiri
            if (!empty($gid)) {
                $cek_guru = mysqli_query($koneksi,
                    "SELECT j.id, k.nama_kelas FROM jadwal j
                     LEFT JOIN kelas k ON k.id = j.kelas_id
                     WHERE j.guru_id = '$gid'
                     AND j.hari = '$hari'
                     AND j.id != '$id'
                     AND j.tahun_ajaran_id = '{$data['tahun_ajaran_id']}'
                     AND (
                         '$mulai' < j.jam_selesai AND '$selesai' > j.jam_mulai
                     )");
                if (mysqli_num_rows($cek_guru) > 0) {
                    $rg = mysqli_fetch_assoc($cek_guru);
                    $error = "Jadwal bentrok! Guru tersebut sudah mengajar di kelas " . e($rg['nama_kelas'] ?? '-') . " pada hari dan jam yang sama.";
                }
            }
            if ($error === null) {
            mysqli_query($koneksi,
                "UPDATE jadwal
                 SET kelas_id = '$kid', mapel_id = '$mid', guru_id = '$gid',
                     hari = '$hari', jam_mulai = '$mulai', jam_selesai = '$selesai'
                 WHERE id = '$id'");

            // auto-sinkron ke pivot kelas_mapel_guru (jadwal => penugasan)
            $taId = (int) $data['tahun_ajaran_id'];
            if ($taId > 0) {
                $cek_pivot = mysqli_query($koneksi,
                    "SELECT id FROM kelas_mapel_guru WHERE kelas_id='$kid' AND mapel_id='$mid' AND guru_id='$gid' AND tahun_ajaran_id='$taId' LIMIT 1");
                if (mysqli_num_rows($cek_pivot) == 0) {
                    $km = mysqli_fetch_row(mysqli_query($koneksi, "SELECT kkm FROM mata_pelajaran WHERE id='$mid'"));
                    $kkm_pivot = $km ? (int) $km[0] : 75;
                    mysqli_query($koneksi,
                        "INSERT INTO kelas_mapel_guru (kelas_id, mapel_id, guru_id, tahun_ajaran_id, kkm, jam_per_minggu)
                         VALUES ('$kid', '$mid', '$gid', '$taId', $kkm_pivot, 2)");
                }
            }

            header("Location: index.php?success=Jadwal berhasil diupdate");
            exit();
            }
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Jadwal</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Form Edit Jadwal</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">

                    <!-- Kolom Kiri -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Data Jadwal</h6>

                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" id="kelas_id" class="form-select" required>
                                <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>>
                                    <?= e($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="mapel_id" id="mapel_id" class="form-select" required>
                                <?php while ($m = mysqli_fetch_assoc($mapel_list)): ?>
                                <option value="<?= $m['id'] ?>"
                                    <?= $data['mapel_id'] == $m['id'] ? 'selected' : '' ?>>
                                    <?= e($m['nama_mapel']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guru Pengajar</label>
                            <select name="guru_id" id="guru_id" class="form-select" required>
                                <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                <option value="<?= $g['id'] ?>"
                                    <?= $data['guru_id'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted text-guru-otomatis">Kelas & mapel dipilih â†’ guru otomatis terfilter.</small>
                        </div>
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3 fw-bold">Waktu Jadwal</h6>

                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat'] as $h): ?>
                                <option value="<?= $h ?>"
                                    <?= $data['hari'] == $h ? 'selected' : '' ?>>
                                    <?= $h ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control"
                                   value="<?= $data['jam_mulai'] ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control"
                                   value="<?= $data['jam_selesai'] ?>" required>
                        </div>
                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kelasSelect = document.getElementById('kelas_id');
    const mapelSelect = document.getElementById('mapel_id');
    const guruSelect  = document.getElementById('guru_id');
    const guruHint    = document.querySelector('.text-guru-otomatis');

    function muatGuruByPivot() {
        const kelasId = kelasSelect.value;
        const mapelId = mapelSelect.value;
        if (!mapelId) return;

        fetch('get_guru_by_mapel.php?mapel_id=' + mapelId + '&kelas_id=' + kelasId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var html = '<option value="">-- Pilih Guru --</option>';
                (data || []).forEach(function(g) {
                    html += '<option value="' + g.id + '">' + g.nama + '</option>';
                });
                guruSelect.innerHTML = html;
                if (guruHint) guruHint.textContent = 'Menampilkan ' + (data ? data.length : 0) + ' guru pengajar mapel ini.';
            })
            .catch(function() {});
    }

    kelasSelect.addEventListener('change', muatGuruByPivot);
    mapelSelect.addEventListener('change', muatGuruByPivot);
});
</script>
<?php include '../../includes/footer.php'; ?>