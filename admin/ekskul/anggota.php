<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Kelola Anggota Ekskul";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ekskul = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ekstrakurikuler WHERE id=$id"));

if (!$ekskul) {
    header("Location: index.php?error=Ekstrakurikuler tidak ditemukan");
    exit();
}

// ── Tambah anggota ────────────────────────────────────────────
if (isset($_POST['tambah_anggota'])) {
    $siswa_id = (int) $_POST['siswa_id'];
    $tgl      = !empty($_POST['tanggal_bergabung']) ? mysqli_real_escape_string($koneksi, $_POST['tanggal_bergabung']) : 'NULL';
    $tgl_sql  = $tgl === 'NULL' ? 'NULL' : "'$tgl'";

    $cek = mysqli_fetch_row(mysqli_query($koneksi,
        "SELECT COUNT(*) FROM ekstrakurikuler_anggota WHERE ekskul_id=$id AND siswa_id=$siswa_id"))[0];
    if ($cek > 0) {
        $error = "Siswa sudah terdaftar sebagai anggota ekskul ini.";
    } else {
        mysqli_query($koneksi,
            "INSERT INTO ekstrakurikuler_anggota (ekskul_id, siswa_id, tanggal_bergabung)
             VALUES ($id, $siswa_id, $tgl_sql)");
        if (mysqli_error($koneksi)) {
            $error = "Gagal menambahkan: " . mysqli_error($koneksi);
        } else {
            header("Location: anggota.php?id=$id&success=Anggota berhasil ditambahkan");
            exit();
        }
    }
}

$daftar_siswa = mysqli_query($koneksi,
    "SELECT s.id, s.nis, s.nama_lengkap, s.nama, k.nama_kelas
     FROM siswa s
     LEFT JOIN kelas k ON s.kelas_id = k.id
     ORDER BY s.nama_lengkap");

$data_siswa = [];
while ($s = mysqli_fetch_assoc($daftar_siswa)) {
    $data_siswa[] = [
        'id'    => (int) $s['id'],
        'nama'  => $s['nama_lengkap'] ?: $s['nama'],
        'nis'   => $s['nis'],
        'kelas' => $s['nama_kelas'] ?: ''
    ];
}
$json_siswa = json_encode($data_siswa, JSON_UNESCAPED_UNICODE);

$anggota = mysqli_query($koneksi,
    "SELECT ea.*, s.nis, s.nama_lengkap, s.nama AS nama_siswa
     FROM ekstrakurikuler_anggota ea
     LEFT JOIN siswa s ON ea.siswa_id = s.id
     WHERE ea.ekskul_id = $id
     ORDER BY s.nama_lengkap");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-users text-gold me-2"></i>Anggota — <?= htmlspecialchars($ekskul['nama_ekskul']) ?></h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Tambah Anggota
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Siswa</label>
                            <div class="siswa-search">
                                <input type="text" id="pencarian_siswa" class="form-control"
                                       placeholder="Ketik nama siswa, NIS, atau kelas..." autocomplete="off">
                                <input type="hidden" name="siswa_id" id="siswa_id" value="">
                                <div class="siswa-search-results" id="hasil_pencarian_siswa"></div>
                            </div>
                            <div class="form-text" id="info_siswa_terpilih"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" class="form-control">
                        </div>
                        <button type="submit" name="tambah_anggota" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus"></i> Tambah
                        </button>
                    </form>

                    <script>
                    const dataSiswa = <?= $json_siswa ?: '[]' ?>;

                    const inputCari   = document.getElementById('pencarian_siswa');
                    const inputId     = document.getElementById('siswa_id');
                    const hasilBox    = document.getElementById('hasil_pencarian_siswa');
                    const infoBox     = document.getElementById('info_siswa_terpilih');

                    function esc(s) {
                        const d = document.createElement('div');
                        d.textContent = s;
                        return d.innerHTML;
                    }

                    function tampilkanHasil(items) {
                        if (!items.length) {
                            hasilBox.innerHTML = '<div class="siswa-search-empty">Tidak ditemukan</div>';
                            hasilBox.classList.add('show');
                            return;
                        }
                        hasilBox.innerHTML = items.map(s =>
                            '<div class="siswa-search-item" data-id="' + s.id + '">' +
                                '<span class="siswa-search-nama">' + esc(s.nama) + '</span>' +
                                '<span class="siswa-search-detail">NIS: ' + esc(s.nis) + ' &middot; ' + esc(s.kelas) + '</span>' +
                            '</div>'
                        ).join('');
                        hasilBox.classList.add('show');
                    }

                    inputCari.addEventListener('input', function () {
                        const q = this.value.trim().toLowerCase();
                        if (!q) {
                            hasilBox.classList.remove('show');
                            return;
                        }
                        const hasil = dataSiswa.filter(s =>
                            s.nama.toLowerCase().includes(q) ||
                            s.nis.toLowerCase().includes(q) ||
                            s.kelas.toLowerCase().includes(q)
                        ).slice(0, 15);
                        tampilkanHasil(hasil);
                    });

                    hasilBox.addEventListener('click', function (e) {
                        const item = e.target.closest('.siswa-search-item');
                        if (!item) return;
                        const id = item.getAttribute('data-id');
                        const s  = dataSiswa.find(x => x.id === parseInt(id, 10));
                        if (!s) return;
                        inputId.value = s.id;
                        inputCari.value = s.nama;
                        infoBox.innerHTML = 'Dipilih: <strong>' + esc(s.nama) + '</strong> (NIS: ' + esc(s.nis) + ' &middot; Kelas ' + esc(s.kelas) + ')';
                        hasilBox.classList.remove('show');
                    });

                    document.addEventListener('click', function (e) {
                        if (!e.target.closest('.siswa-search')) {
                            hasilBox.classList.remove('show');
                        }
                    });
                    </script>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-users"></i> Daftar Anggota (<?= mysqli_num_rows($anggota) ?>)</span>
                </div>
                <div class="card-body">
                    <?php if ($anggota && mysqli_num_rows($anggota) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover dataTable align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Siswa</th>
                                    <th>Tanggal Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $no = 1;
                            while ($r = mysqli_fetch_assoc($anggota)):
                                $nama_s = $r['nama_lengkap'] ?: $r['nama_siswa'];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($nama_s ?: '-') ?></strong>
                                    <br><small class="text-muted">NIS: <?= htmlspecialchars($r['nis'] ?: '-') ?></small>
                                </td>
                                <td><?= $r['tanggal_bergabung'] ? tanggal_indo_pendek($r['tanggal_bergabung']) : '-' ?></td>
                                <td>
                                    <button onclick="konfirmasiHapus('hapus_anggota.php?id=<?= $r['id'] ?>&ekskul_id=<?= $id ?>', '<?= htmlspecialchars($nama_s ?: 'siswa') ?>')"
                                            class="btn btn-danger btn-sm" title="Hapus Anggota">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state py-5 text-center">
                        <i class="fas fa-users fa-3x text-muted"></i>
                        <p class="mt-3 mb-0">Belum ada anggota ekskul ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
