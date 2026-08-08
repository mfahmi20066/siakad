<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Mata Pelajaran";

// Mengambil data dari tabel mata_pelajaran secara langsung tanpa JOIN di awal
$data = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel DESC");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-book text-gold me-2"></i>Mata Pelajaran</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list"></i> Daftar Mata Pelajaran</span>
            <a href="tambah.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Mata Pelajaran
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Guru Pengampu</th>
                        <th>Jumlah Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada data.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                <?php
                    // 1. Ambil Nama Guru secara dinamis berdasarkan kolom foreign key yang tersedia
                    $nama_guru = "Belum ditentukan";
                    
                    // Kita periksa kolom mana yang ada di tabel mata_pelajaran milikmu
                    if (isset($r['id_guru']) && !empty($r['id_guru'])) {
                        $id_g = $r['id_guru'];
                        $q_guru = mysqli_query($koneksi, "SELECT nama FROM guru WHERE id = '$id_g'");
                        if ($g = mysqli_fetch_assoc($q_guru)) { $nama_guru = $g['nama']; }
                    } elseif (isset($r['guru_id']) && !empty($r['guru_id'])) {
                        $id_g = $r['guru_id'];
                        $q_guru = mysqli_query($koneksi, "SELECT nama FROM guru WHERE id = '$id_g'");
                        if ($g = mysqli_fetch_assoc($q_guru)) { $nama_guru = $g['nama']; }
                    }

                    // 2. Hitung berapa kelas yang menggunakan mapel ini
                    $jml_kelas = 0;
                    $q_kelas = mysqli_query($koneksi, "SELECT COUNT(DISTINCT kelas_id) FROM jadwal WHERE mapel_id = '{$r['id']}'");
                    if ($row_kelas = mysqli_fetch_row($q_kelas)) {
                        $jml_kelas = $row_kelas[0];
                    }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($r['kode_mapel']) ?></span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($r['nama_mapel']) ?></strong>
                    </td>
                    <td>
                        <?php
                        // Multi-guru: tampilkan semua guru yang mengajar mapel ini (dari tabel jadwal)
                        $list_guru_mapel = [];
                        $q_guru_mapel = mysqli_query(
                            $koneksi,
                            "SELECT DISTINCT j.guru_id, g.nama_lengkap AS nama_guru
                             FROM jadwal j
                             JOIN guru g ON g.id = j.guru_id
                             WHERE j.mapel_id = '{$r['id']}'"
                        );
                        if ($q_guru_mapel) {
                            while ($gm = mysqli_fetch_assoc($q_guru_mapel)) {
                                $nm = $gm['nama_guru'] ?? '';
                                if (!empty($nm)) $list_guru_mapel[] = $nm;
                            }
                        }
                        $list_guru_mapel = array_values(array_unique($list_guru_mapel));
                        ?>

                        <?php if (!empty($list_guru_mapel)): ?>
                            <i class="fas fa-user-tie text-success"></i>
                            <?= htmlspecialchars(implode(', ', $list_guru_mapel)) ?>
                        <?php else: ?>
                            <span class="text-muted">Belum ditentukan</span>
                        <?php endif; ?>
                    </td>


                    <td>
                        <span class="badge bg-info"><?= $jml_kelas ?> Kelas</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="edit.php?id=<?= $r['id'] ?>"
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $r['id'] ?>')"
                                    class="btn btn-danger btn-sm" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
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

<?php include '../../includes/footer.php'; ?>