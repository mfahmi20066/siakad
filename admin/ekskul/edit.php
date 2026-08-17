<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();
$title = "Edit Ekstrakurikuler";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ekstrakurikuler WHERE id=$id"));

if (!$data) {
    header("Location: index.php?error=Data ekstrakurikuler tidak ditemukan");
    exit();
}

$data['jam_mulai_prefill']   = '';
$data['jam_selesai_prefill'] = '';
if (!empty($data['pukul'])) {
    $pecah = explode(' - ', $data['pukul']);
    $data['jam_mulai_prefill']   = isset($pecah[0]) ? trim($pecah[0]) : '';
    $data['jam_selesai_prefill'] = isset($pecah[1]) ? trim($pecah[1]) : '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ekskul = mysqli_real_escape_string($koneksi, $_POST['nama_ekskul']);
    $pembina     = mysqli_real_escape_string($koneksi, $_POST['pembina']);
    $hari        = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $jam_mulai   = mysqli_real_escape_string($koneksi, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($koneksi, $_POST['jam_selesai']);
    $deskripsi   = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    $pukul = '';
    if ($jam_mulai !== '' && $jam_selesai !== '') {
        $pukul = $jam_mulai . ' - ' . $jam_selesai;
    } elseif ($jam_mulai !== '') {
        $pukul = $jam_mulai;
    }

    $pembina_sql = $pembina === '' ? 'NULL' : "'$pembina'";
    $hari_sql  = $hari === '' ? 'NULL' : "'$hari'";
    $pukul_sql = $pukul === '' ? 'NULL' : "'$pukul'";
    $q = mysqli_query($koneksi,
        "UPDATE ekstrakurikuler SET
             nama_ekskul = '$nama_ekskul',
             pembina = $pembina_sql,
             hari = $hari_sql,
             pukul = $pukul_sql,
             deskripsi = '$deskripsi'
         WHERE id = $id");
    if ($q) {
        header("Location: index.php?success=" . urlencode("Ekstrakurikuler berhasil diperbarui"));
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
        <h4><i class="fas fa-futbol text-icon me-2"></i>Edit Ekstrakurikuler</h4>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-futbol"></i> Form Edit Ekskul
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Ekstrakurikuler</label>
                            <input type="text" name="nama_ekskul" class="form-control"
                                   value="<?= e($data['nama_ekskul']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pembina</label>
                            <input type="text" name="pembina" class="form-control"
                                   value="<?= e($data['pembina']) ?>"
                                   placeholder="Nama pembina ekskul (bisa dari guru atau bukan)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select">
                                <option value="">-- Pilih Hari --</option>
                                <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?>
                                    <option value="<?= $h ?>" <?= $data['hari'] == $h ? 'selected' : '' ?>><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pukul Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control"
                                   value="<?= e($data['jam_mulai_prefill'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pukul Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control"
                                   value="<?= e($data['jam_selesai_prefill'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4"><?= e($data['deskripsi']) ?></textarea>
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
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
