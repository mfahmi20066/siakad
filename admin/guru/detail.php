<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Detail Guru";

$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

// 1. Ambil data guru secara mandiri tanpa JOIN agar aman
$data = [];
$q_guru = mysqli_query($koneksi, "SELECT * FROM guru WHERE id = '$id'");
if ($q_guru && mysqli_num_rows($q_guru) > 0) {
    $data = mysqli_fetch_assoc($q_guru);
}

// Ambil username secara aman dengan memeriksa nama kolom di tabel guru
$username = '-';
if (!empty($data)) {
    $id_u = '';
    if (isset($data['id_user'])) { $id_u = $data['id_user']; }
    elseif (isset($data['user_id'])) { $id_u = $data['user_id']; }
    elseif (isset($data['username'])) { $username = $data['username']; }

    if (!empty($id_u)) {
        $q_user = mysqli_query($koneksi, "SELECT username FROM users WHERE id = '$id_u'");
        if (!$q_user) {
            $q_user = mysqli_query($koneksi, "SELECT username FROM user WHERE id = '$id_u'");
        }
        if ($q_user && $u = mysqli_fetch_assoc($q_user)) {
            $username = $u['username'];
        }
    }
}

// 2. Ambil mata pelajaran dengan proteksi nama kolom otomatis
$array_mapel = [];
// Cek struktur kolom tabel mata_pelajaran terlebih dahulu
$cek_kolom_mapel = mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran");
$kolom_tersedia = [];
if ($cek_kolom_mapel) {
    while ($k = mysqli_fetch_assoc($cek_kolom_mapel)) {
        $kolom_tersedia[] = $k['Field'];
    }
}

$query_mapel_text = "";
if (in_array('guru_id', $kolom_tersedia)) {
    $query_mapel_text = "SELECT * FROM mata_pelajaran WHERE guru_id = '$id' ORDER BY nama_mapel";
} elseif (in_array('id_guru', $kolom_tersedia)) {
    $query_mapel_text = "SELECT * FROM mata_pelajaran WHERE id_guru = '$id' ORDER BY nama_mapel";
}

if (!empty($query_mapel_text)) {
    $q_mapel = mysqli_query($koneksi, $query_mapel_text);
    if ($q_mapel && mysqli_num_rows($q_mapel) > 0) {
        while ($row = mysqli_fetch_assoc($q_mapel)) {
            $array_mapel[] = $row;
        }
    }
}

// 3. Ambil jadwal mengajar guru ini (Proteksi nama kolom jadwal)
$array_jadwal = [];
$cek_kolom_jadwal = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal");
$kolom_jadwal_tersedia = [];
if ($cek_kolom_jadwal) {
    while ($kj = mysqli_fetch_assoc($cek_kolom_jadwal)) {
        $kolom_jadwal_tersedia[] = $kj['Field'];
    }
}

$query_jadwal_text = "";

// Bangun filter guru yang tahan variasi kolom relasi di tabel jadwal
$filters = [];
if (in_array('guru_id', $kolom_jadwal_tersedia)) {
    $filters[] = "j.guru_id = '$id'";
}
if (in_array('id_guru', $kolom_jadwal_tersedia)) {
    $filters[] = "j.id_guru = '$id'";
}

if (!empty($filters)) {
    $where_guru = '(' . implode(' OR ', $filters) . ')';

    $query_jadwal_text = "SELECT j.*, k.nama_kelas, m.nama_mapel
        FROM jadwal j
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE $where_guru
        ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat'), j.jam_mulai";

    $q_jadwal = mysqli_query($koneksi, $query_jadwal_text);
    if ($q_jadwal && mysqli_num_rows($q_jadwal) > 0) {
        while ($row = mysqli_fetch_assoc($q_jadwal)) {
            $array_jadwal[] = $row;
        }
    }
}


// 4. Hitung Statistik secara aman
$total_kelas = 0;
if (!empty($array_jadwal)) {
    $kelas_diajar = array_column($array_jadwal, 'kelas_id');
    $total_kelas = count(array_unique($kelas_diajar));
}

$total_jadwal = count($array_jadwal);
$total_mapel  = count($array_mapel);
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-chalkboard-teacher text-gold me-2"></i>Detail Guru</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <?php
                    $foto_g = $data['foto'] ?? '';
                    $foto_g_src = (!empty($foto_g) && file_exists(__DIR__ . '/../../assets/img/foto_guru/' . $foto_g))
                        ? '/siakad/assets/img/foto_guru/' . $foto_g
                        : '/siakad/assets/img/default-avatar.png';
                    ?>
                    <img src="<?= $foto_g_src ?>"
                         onerror="this.src='/siakad/assets/img/default-avatar.png'"
                         class="rounded-circle mb-3"
                         width="120" height="120"
                         style="object-fit:cover; border:3px solid #10B981; cursor:pointer"
                         data-bs-toggle="modal" data-bs-target="#modalFoto"
                         data-src="<?= $foto_g_src ?>"
                         title="Klik untuk memperbesar">
                    <h5><?= htmlspecialchars($data['nama'] ?? 'Tidak Diketahui') ?></h5>
                    <p class="text-muted mb-1">NIP: <strong><?= $data['nip'] ?? '-' ?></strong></p>
                    <span class="badge bg-success">Guru</span>
                    <hr>

                    <div class="alert alert-info mb-2" style="border-radius:10px;">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        <strong>Mengajar Mata Pelajaran di Kelas:</strong>
                        <div class="small mt-1">
                            <?= $total_kelas > 0 ? $total_kelas : 0 ?> kelas • <?= $total_jadwal > 0 ? $total_jadwal : 0 ?> jadwal
                        </div>
                    </div>

                    <table class="table table-sm text-start">

                        <tr>
                            <td><strong>Jenis Kelamin</strong></td>
                            <td>: <?= isset($data['jenis_kelamin']) && $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tempat Lahir</strong></td>
                            <td>: <?= htmlspecialchars($data['tempat_lahir'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Lahir</strong></td>
                            <td>: <?= isset($data['tanggal_lahir']) ? tanggal_indo($data['tanggal_lahir']) : '-' ?></td>
                        </tr>
                        <tr>
                            <td><strong>No HP</strong></td>
                            <td>: <?= $data['no_hp'] ?? '-' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Username</strong></td>
                            <td>: <?= htmlspecialchars($username) ?></td>
                        </tr>
                    </table>
                    <p class="text-muted small">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($data['alamat'] ?? '-') ?>
                    </p>
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm w-100">
                        <i class="fas fa-edit"></i> Edit Data
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Statistik Mengajar
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-around text-center">
                        <div>
                            <h4 class="text-primary mb-0"><?= $total_kelas ?></h4>
                            <small class="text-muted">Kelas</small>
                        </div>
                        <div>
                            <h4 class="text-success mb-0"><?= $total_mapel ?></h4>
                            <small class="text-muted">Mata Pelajaran</small>
                        </div>
                        <div>
                            <h4 class="text-warning mb-0"><?= $total_jadwal ?></h4>
                            <small class="text-muted">Jadwal</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-book"></i> Mata Pelajaran Diampu
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                    <?php if ($total_mapel > 0): ?>
                        <?php foreach ($array_mapel as $m): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= htmlspecialchars($m['nama_mapel']) ?>
                            <span class="badge bg-secondary"><?= $m['kode_mapel'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">Belum ada mapel</li>
                    <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Jadwal Mengajar
                </div>
                <div class="card-body">
                    <?php if ($total_jadwal > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Hari</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; foreach ($array_jadwal as $j): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= $j['hari'] ?></span>
                            </td>
                            <td><?= htmlspecialchars($j['nama_mapel']) ?></td>
                            <td>
                                <span class="badge bg-info"><?= $j['nama_kelas'] ?></span>
                            </td>
                            <td><?= substr($j['jam_mulai'], 0, 5) ?></td>
                            <td><?= substr($j['jam_selesai'], 0, 5) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">Belum ada jadwal mengajar.</p>
                    <?php endif; ?>
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