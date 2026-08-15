<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Mata Pelajaran";

// amankan parameter id dari url
$id   = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE id='$id'"));

// query aman: select * aja, ga pake order by kolom spesifik biar bebas error nama kolom
$guru_list = mysqli_query($koneksi, "SELECT * FROM guru");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode = mysqli_real_escape_string($koneksi, strtoupper($_POST['kode_mapel']));
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
    $guru = mysqli_real_escape_string($koneksi, $_POST['guru_id']);
    $kelompok = mysqli_real_escape_string($koneksi, $_POST['kelompok'] ?? 'Umum');
    $kkm  = (int) ($_POST['kkm'] ?? 75);
    $kategori = in_array($_POST['kategori'] ?? 'wajib', ['wajib', 'pilihan', 'projek']) ? $_POST['kategori'] : 'wajib';
    $status   = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    // cek duplikat kode, kecuali mapel ini sendiri
    $cek_kode = mysqli_query($koneksi, "SELECT id FROM mata_pelajaran WHERE kode_mapel='$kode' AND id != '$id'");

    // cek duplikat nama, kecuali mapel ini sendiri
    $cek_nama = mysqli_query($koneksi, "SELECT id FROM mata_pelajaran WHERE nama_mapel='$nama' AND id != '$id'");

    if ($cek_kode && mysqli_num_rows($cek_kode) > 0) {
        $error = "Kode mapel <strong>$kode</strong> sudah digunakan mapel lain!";
    } elseif ($cek_nama && mysqli_num_rows($cek_nama) > 0) {
        $error = "Nama mata pelajaran <strong>$nama</strong> sudah ada!";
    } else {
        // simpan guru_id ke db (kolomnya udah dibuat di phpmyadmin)
        $val_guru = !empty($guru) ? "'$guru'" : "NULL";

        // cek kolom kelompok/kkm ada ga, biar ga error kalo migrasi belum jalan
        $cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran LIKE 'kelompok'");
        $ada_kolom_kelompok = mysqli_num_rows($cek_kolom) > 0;

        // kolom kurikulum merdeka (kategori, status) kalo ada
        $ada_kategori = mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran LIKE 'kategori'")) > 0;
        $ada_status   = mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM mata_pelajaran LIKE 'status'")) > 0;
        $kolom_extra = ($ada_kategori ? ", kategori='$kategori'" : '') . ($ada_status ? ", status='$status'" : '');

        if ($ada_kolom_kelompok) {
            $update_action = mysqli_query($koneksi,
                "UPDATE mata_pelajaran 
                 SET kode_mapel='$kode', nama_mapel='$nama', guru_id=$val_guru,
                     kelompok='$kelompok', kkm='$kkm' $kolom_extra
                 WHERE id='$id'");
        } else {
            $update_action = mysqli_query($koneksi, "UPDATE mata_pelajaran SET kode_mapel='$kode', nama_mapel='$nama', guru_id=$val_guru $kolom_extra WHERE id='$id'");
        }

        if ($update_action) {
            header("Location: index.php?success=Mata pelajaran " . urlencode($nama) . " berhasil diupdate");
            exit();
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-edit text-icon me-2"></i>Edit Mata Pelajaran</h4>
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
        <div class="card-header">Form Edit Mata Pelajaran</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Kode Mata Pelajaran</label>
                            <input type="text" name="kode_mapel" class="form-control"
                                   value="<?= e($data['kode_mapel'] ?? '') ?>"
                                   style="text-transform:uppercase"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" class="form-control"
                                   value="<?= e($data['nama_mapel'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Guru Pengampu</label>
                            <select name="guru_id" class="form-select">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                <?php if ($guru_list): ?>
                                    <?php while ($g = mysqli_fetch_assoc($guru_list)): ?>
                                    <?php 
                                        // deteksi otomatis kolom nama di tabel guru
                                        $nama_tampil = $g['nama_guru'] ?? $g['nama_lengkap'] ?? $g['nama'] ?? 'Nama Tidak Terdeteksi';
                                    ?>
                                    <option value="<?= $g['id'] ?>"
                                        <?= (isset($data['guru_id']) && $data['guru_id'] == $g['id']) ? 'selected' : '' ?>>
                                        <?= e($nama_tampil) ?>
                                    </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Kelompok Mapel
                                        <small class="text-muted">(untuk pengelompokan di cetak Rapor)</small>
                                    </label>
                                    <select name="kelompok" class="form-select">
                                        <?php
                                        $kelompok_saat_ini = $data['kelompok'] ?? 'Umum';
                                        $pilihan_kelompok = ['Umum', 'Normatif', 'Adaptif', 'Produktif', 'Muatan Lokal'];
                                        foreach ($pilihan_kelompok as $opt):
                                        ?>
                                        <option value="<?= $opt ?>" <?= $kelompok_saat_ini == $opt ? 'selected' : '' ?>>
                                            <?= $opt ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">KKM</label>
                                    <input type="number" name="kkm" class="form-control"
                                           value="<?= e($data['kkm'] ?? 75) ?>"
                                           min="0" max="100">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label">Kategori (Kurikulum Merdeka)</label>
                                    <select name="kategori" class="form-select">
                                        <?php
                                        $kat = $data['kategori'] ?? 'wajib';
                                        foreach (['wajib', 'pilihan', 'projek'] as $opt):
                                        ?>
                                        <option value="<?= $opt ?>" <?= $kat == $opt ? 'selected' : '' ?>>
                                            <?= ucfirst($opt) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">wajib = muatan nasional/umum, pilihan = mapel pilihan siswa, projek = P5.</small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <?php $st = $data['status'] ?? 'aktif'; ?>
                                        <option value="aktif" <?= $st == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="nonaktif" <?= $st == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-6">

                        <?php
                        $jml_jadwal = 0;
                        $jml_nilai  = 0;
                        
                        if (!empty($id)) {
                            $res_jadwal = @mysqli_query($koneksi, "SELECT id FROM jadwal WHERE mapel_id='$id'");
                            if ($res_jadwal instanceof mysqli_result) {
                                $jml_jadwal = mysqli_num_rows($res_jadwal);
                            } else {
                                $res_jadwal_alt = @mysqli_query($koneksi, "SELECT id FROM jadwal_pelajaran WHERE mapel_id='$id'");
                                if ($res_jadwal_alt instanceof mysqli_result) {
                                    $jml_jadwal = mysqli_num_rows($res_jadwal_alt);
                                }
                            }
                            
                            $res_nilai = @mysqli_query($koneksi, "SELECT id FROM nilai WHERE mapel_id='$id'");
                            if ($res_nilai instanceof mysqli_result) {
                                $jml_nilai = mysqli_num_rows($res_nilai);
                            }
                        }
                        ?>
                        <div class="alert alert-warning">
                            <h6>
                                <i class="fas fa-exclamation-triangle"></i>
                                Info Penggunaan Mapel
                            </h6>
                            <p class="mb-1">
                                Mata pelajaran ini digunakan di:
                            </p>
                            <ul class="mb-0 small">
                                <li><strong><?= $jml_jadwal ?></strong> jadwal pelajaran</li>
                                <li><strong><?= $jml_nilai ?></strong> data nilai siswa</li>
                            </ul>
                        </div>

                    </div>
                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>