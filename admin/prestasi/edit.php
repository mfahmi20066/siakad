<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Prestasi";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM prestasi_siswa WHERE id=$id"));

if (!$data) {
    header("Location: index.php?error=Data prestasi tidak ditemukan");
    exit();
}

$daftar_siswa = mysqli_query($koneksi,
    "SELECT s.id, s.nis, s.nama_lengkap, s.nama, k.nama_kelas
     FROM siswa s
     LEFT JOIN kelas k ON s.kelas_id = k.id
     ORDER BY s.nama_lengkap");

$data_siswa = [];
$nama_siswa_terpilih = '';
while ($s = mysqli_fetch_assoc($daftar_siswa)) {
    $data_siswa[] = [
        'id'    => (int) $s['id'],
        'nama'  => $s['nama_lengkap'] ?: $s['nama'],
        'nis'   => $s['nis'],
        'kelas' => $s['nama_kelas'] ?: ''
    ];
    if ($s['id'] == $data['siswa_id']) {
        $nama_siswa_terpilih = $s['nama_lengkap'] ?: $s['nama'];
    }
}
$json_siswa = json_encode($data_siswa, JSON_UNESCAPED_UNICODE);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $siswa_id      = (int) $_POST['siswa_id'];
    $nama_prestasi = mysqli_real_escape_string($koneksi, $_POST['nama_prestasi']);
    $kategori      = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $tingkat       = mysqli_real_escape_string($koneksi, $_POST['tingkat']);
    $tanggal       = !empty($_POST['tanggal']) ? mysqli_real_escape_string($koneksi, $_POST['tanggal']) : 'NULL';
    $keterangan    = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $tanggal_sql = $tanggal === 'NULL' ? 'NULL' : "'$tanggal'";
    $q = mysqli_query($koneksi,
        "UPDATE prestasi_siswa SET
             siswa_id = $siswa_id,
             nama_prestasi = '$nama_prestasi',
             kategori = '$kategori',
             tingkat = '$tingkat',
             tanggal = $tanggal_sql,
             keterangan = '$keterangan'
         WHERE id = $id");
    if ($q) {
        header("Location: index.php?success=" . urlencode("Prestasi berhasil diperbarui"));
        exit();
    } else {
        $error = "Gagal menyimpan: " . mysqli_error($koneksi);
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-trophy text-icon me-2"></i>Edit Prestasi</h4>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-trophy"></i> Form Edit Prestasi
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Siswa</label>
                            <div class="siswa-search">
                                <input type="text" id="pencarian_siswa" class="form-control"
                                       placeholder="Ketik nama siswa, NIS, atau kelas..." autocomplete="off"
                                       value="<?= e($nama_siswa_terpilih) ?>">
                                <input type="hidden" name="siswa_id" id="siswa_id" value="<?= (int) $data['siswa_id'] ?>">
                                <div class="siswa-search-results" id="hasil_pencarian_siswa"></div>
                            </div>
                            <div class="form-text" id="info_siswa_terpilih"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Prestasi</label>
                            <input type="text" name="nama_prestasi" class="form-control"
                                   value="<?= e($data['nama_prestasi']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <option value="Akademik" <?= $data['kategori'] == 'Akademik' ? 'selected' : '' ?>>Akademik</option>
                                <option value="Non-Akademik" <?= $data['kategori'] == 'Non-Akademik' ? 'selected' : '' ?>>Non-Akademik</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select" required>
                                <?php foreach (['Sekolah','Kecamatan','Kota','Provinsi','Nasional','Internasional'] as $t): ?>
                                    <option value="<?= $t ?>" <?= $data['tingkat'] == $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control"
                                   value="<?= e($data['tanggal']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="4"><?= e($data['keterangan']) ?></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
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

<?php include '../../includes/footer.php'; ?>
