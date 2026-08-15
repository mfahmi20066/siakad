<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Penugasan Mapel per Kelas";

// tahun ajaran aktif
$taId = null;
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int) $taAktif['id']; }
catch (Throwable $e) { $taId = null; }

// kelas aktif buat dropdown filter
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");

$kid = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;

// data penugasan kelas terpilih
$data = null;
$nama_kelas = '';
$jurusan = '';
if ($kid > 0) {
    $kinfo = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas, jurusan FROM kelas WHERE id=$kid"));
    if ($kinfo) {
        $nama_kelas = $kinfo['nama_kelas'];
        $jurusan    = $kinfo['jurusan'];
        $data = mysqli_query($koneksi,
            "SELECT kmg.id, kmg.kelas_id, kmg.mapel_id, kmg.guru_id, kmg.kkm, kmg.jam_per_minggu,
                    mp.nama_mapel, mp.kode_mapel, mp.kategori,
                    g.nama AS nama_guru
             FROM kelas_mapel_guru kmg
             JOIN mata_pelajaran mp ON mp.id = kmg.mapel_id
             LEFT JOIN guru g ON g.id = kmg.guru_id
             WHERE kmg.kelas_id = $kid" . ($taId ? " AND kmg.tahun_ajaran_id = $taId" : '') . "
             ORDER BY mp.kategori, mp.nama_mapel");
    } else {
        $kid = 0;
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-book-open text-icon me-2"></i>Penugasan Mapel per Kelas</h4>
        <a href="tambah.php<?= $kid ? '?kelas_id=' . $kid : '' ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Tambah Penugasan
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto"><i class="fas fa-check-circle"></i> <?= e($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto"><i class="fas fa-exclamation-circle"></i> <?= e($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pilih Kelas</label>
                    <select name="kelas_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Pilih Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= $k['id'] ?>" <?= $kid == $k['id'] ? 'selected' : '' ?>>
                            <?= e($k['nama_kelas']) ?> (<?= $k['jurusan'] ?? 'Umum' ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?php if ($kid > 0): ?>
                    <span class="badge bg-info text-dark fs-6"><?= e($nama_kelas) ?> — <?= $jurusan ?? 'Umum' ?></span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($kid > 0): ?>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Mapel Kelas <?= e($nama_kelas) ?>
            <span class="badge bg-secondary ms-1"><?= $data ? mysqli_num_rows($data) : 0 ?> mapel</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Mata Pelajaran</th>
                            <th>Kategori</th>
                            <th>Guru Pengampu</th>
                            <th>KKM</th>
                            <th>Jam/Minggu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($data && mysqli_num_rows($data) > 0): $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <?php
                        $k_badge = $r['kategori'] === 'pilihan' ? 'info' : ($r['kategori'] === 'projek' ? 'warning' : 'secondary');
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><span class="badge bg-secondary"><?= e($r['kode_mapel']) ?></span></td>
                        <td><strong><?= e($r['nama_mapel']) ?></strong></td>
                        <td><span class="badge bg-<?= $k_badge ?>"><?= e(ucfirst($r['kategori'])) ?></span></td>
                        <td><?= e($r['nama_guru'] ?? '-') ?></td>
                        <td><?= (int) $r['kkm'] ?></td>
                        <td><?= (int) $r['jam_per_minggu'] ?> jam</td>
                        <td>
                            <div class="table-actions">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="hapusPenugasan(<?= $r['id'] ?>, '<?= e($r['nama_mapel'], ENT_QUOTES) ?>')"
                                        class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada penugasan mapel untuk kelas ini.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Pilih kelas terlebih dahulu untuk melihat / mengelola penugasan mapel.
    </div>
    <?php endif; ?>
</div>

<script>
function _csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}
function hapusPenugasan(id, nama) {
    siConfirm({
        icon: 'delete',
        title: 'Hapus Penugasan?',
        text: 'Penugasan "' + nama + '" di kelas ini akan dihapus. Nilai yang sudah terhubung tetap tersimpan.',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        danger: true
    }).then(function (ok) {
        if (ok) window.location.href = 'hapus.php?id=' + id + '&csrf_token=' + encodeURIComponent(_csrfToken());
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
