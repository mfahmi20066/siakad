<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Data Kelas";

$data = mysqli_query($koneksi,
        "SELECT k.*, g.nama AS wali 
         FROM kelas k 
         LEFT JOIN guru g ON k.wali_kelas = g.id 
         ORDER BY k.tingkat, k.nama_kelas");

// hitung jumlah siswa per kelas
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-school text-icon me-2"></i>Data Kelas</h4>
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Kelas</span>
            <div class="btn-group">
                <a href="tambah.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Kelas
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Jurusan</th>
                        <th>Wali Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Siswa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <?php
                    // hitung siswa di kelas ini
                    $jml_siswa = mysqli_fetch_row(mysqli_query($koneksi,
                        "SELECT COUNT(*) FROM siswa WHERE kelas_id='{$r['id']}'"))[0];
                    $jurusan = $r['jurusan'] ?? 'Umum';
                    $j_badge = $jurusan === 'IPA' ? 'primary' : ($jurusan === 'IPS' ? 'success' : 'secondary');
                    $status  = $r['status'] ?? 'aktif';
                    $s_badge = $status === 'nonaktif' ? 'secondary' : 'success';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <strong><?= e($r['nama_kelas']) ?></strong>
                    </td>
                    <td>
                        <span class="badge bg-secondary">Kelas <?= $r['tingkat'] ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $j_badge ?>"><?= e($jurusan) ?></span>
                    </td>
                    <td>
                        <?php if ($r['wali']): ?>
                            <i class="fas fa-user-tie text-success"></i>
                            <?= e($r['wali']) ?>
                        <?php else: ?>
                            <span class="text-muted">Belum ditentukan</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['tahun_ajaran'] ?></td>
                    <td>
                        <span class="badge bg-info"><?= $jml_siswa ?> Siswa</span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $s_badge ?>"><?= e(ucfirst($status)) ?></span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="edit.php?id=<?= $r['id'] ?>"
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($status === 'nonaktif'): ?>
                            <button onclick="aktifkanKelas(<?= $r['id'] ?>, '<?= e($r['nama_kelas'], ENT_QUOTES) ?>')"
                                    class="btn btn-success btn-sm" title="Aktifkan kembali">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <?php else: ?>
                            <button onclick="arsipkanKelas(<?= $r['id'] ?>, '<?= e($r['nama_kelas'], ENT_QUOTES) ?>')"
                                    class="btn btn-secondary btn-sm" title="Arsipkan (nonaktif)">
                                <i class="fas fa-archive"></i>
                            </button>
                            <?php endif; ?>
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

<script>
function _csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}
function arsipkanKelas(id, nama) {
    siConfirm({
        icon: 'warning',
        title: 'Arsipkan Kelas?',
        text: 'Kelas "' + nama + '" akan dinonaktifkan sehingga tidak muncul di dropdown. Data & riwayatnya tetap tersimpan dan bisa diaktifkan kembali.',
        confirmText: 'Ya, Arsipkan',
        cancelText: 'Batal',
        danger: true
    }).then(function (ok) {
        if (ok) window.location.href = 'hapus.php?id=' + id + '&csrf_token=' + encodeURIComponent(_csrf());
    });
}
function aktifkanKelas(id, nama) {
    siConfirm({
        icon: 'question',
        title: 'Aktifkan Kembali?',
        text: 'Kelas "' + nama + '" akan diaktifkan kembali dan muncul di dropdown.',
        confirmText: 'Ya, Aktifkan',
        cancelText: 'Batal',
        danger: false
    }).then(function (ok) {
        if (ok) window.location.href = 'aktifkan.php?id=' + id + '&csrf_token=' + encodeURIComponent(_csrf());
    });
}
</script>

<?php include '../../includes/footer.php'; ?>