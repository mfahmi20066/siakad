<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Buat Rapor Massal";

$taId = null; $taTahun = '';
try { $taAktif = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taAktif['id']; $taTahun = $taAktif['tahun']; }
catch (Throwable $e) { $taId = null; }

// daftar kelas buat dropdown filter
$kelas_list = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY tingkat, nama_kelas");

// ajax: list nama siswa by kelas terpilih
if (isset($_GET['get_siswa_by_kelas'])) {
    $kelas_id = mysqli_real_escape_string($koneksi, $_GET['get_siswa_by_kelas']);
    $siswa_query = mysqli_query($koneksi, "SELECT id, nis, nama FROM siswa WHERE kelas_id = '$kelas_id' ORDER BY nama");
    
    $result = [];
    while ($s = mysqli_fetch_assoc($siswa_query)) {
        $result[] = $s;
    }
    echo json_encode($result);
    exit;
}

// proses simpan rapor massal saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kelas_id      = $_POST['kelas_id'];
    $semester      = $_POST['semester'];
    $data_rapor    = $_POST['rapor'] ?? []; // menampung array input dari tabel siswa

    // ta dari master tahun aktif + validasi kelas
    $taRaporId = null;
    if ($taId === null || $taTahun === '') {
        $raporError = "Tidak ada tahun ajaran aktif. Tetapkan tahun aktif di Modul Tahun Ajaran.";
    } else {
        $taRaporId = $taId;
        $kR = mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT tahun_ajaran_id FROM kelas WHERE id=".(int)$kelas_id));
        if ($kR && $kR['tahun_ajaran_id'] !== null && (int)$kR['tahun_ajaran_id'] !== $taId) {
            $raporError = "Kelas terpilih bukan pada tahun ajaran aktif.";
        }
    }

    if (!empty($data_rapor) && $kelas_id && isset($raporError) === false) {
        $sukses_count = 0;
        foreach ($data_rapor as $siswa_id => $val) {
            $status_kenaikan = mysqli_real_escape_string($koneksi, $val['status_kenaikan']);
            $catatan         = mysqli_real_escape_string($koneksi, $val['catatan']);
            
            // cek rapor siswa ini udah pernah diinput (biar ga duplikat)
            $cek = mysqli_query($koneksi, "SELECT id FROM rapor WHERE siswa_id = '$siswa_id' AND semester = '$semester' AND tahun_ajaran_id = '$taRaporId'");
            
            if (mysqli_num_rows($cek) > 0) {
                // udah ada? update
                $query = "UPDATE rapor SET status_kenaikan = '$status_kenaikan', catatan = '$catatan' WHERE siswa_id = '$siswa_id' AND semester = '$semester' AND tahun_ajaran_id = '$taRaporId'";
            } else {
                // belum ada? insert baru
                $query = "INSERT INTO rapor (siswa_id, kelas_id, semester, tahun_ajaran, tahun_ajaran_id, status_kenaikan, catatan) VALUES ('$siswa_id', '$kelas_id', '$semester', '$taTahun', '$taRaporId', '$status_kenaikan', '$catatan')";
            }
            
            if (mysqli_query($koneksi, $query)) {
                $sukses_count++;

                // notif otomatis ke siswa
                if (!function_exists('notifikasi_id_user_by_ref')) {
                    include __DIR__ . '/../../includes/notifikasi_functions.php';
                }
                $user_siswa = notifikasi_id_user_by_ref($koneksi, $siswa_id, 'siswa');
                if ($user_siswa) {
                    notifikasi_insert($koneksi, $user_siswa,
                        'Rapor sudah terbit',
                        "Rapor semester $semester tahun ajaran $taTahun sudah tersedia.",
                        '/siakad/siswa/rapor.php');
                }
            }
        }
        
        // redirect dengan pesan sukses
        header("Location: index.php?success=" . urlencode("Berhasil menyimpan $sukses_count data rapor siswa."));
        exit;
    }
}
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>


<div class="main-content">
        <div class="page-header">
        <h4><i class="fas fa-plus text-icon me-2"></i>Buat Rapor Massal</h4>
    </div>

    <form method="POST" action="">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-sliders-h"></i> Atur Parameter Rapor</span>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pilih Kelas <span class="text-danger">*</span></label>
                        <select name="kelas_id" id="filter-kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option value="<?= $k['id'] ?>"><?= e($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fw-bold">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="1">Semester 1 (Ganjil)</option>
                            <option value="2">Semester 2 (Genap)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fw-bold">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" value="<?= e($taTahun) ?>" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="card d-none" id="container-tabel-siswa">
            <div class="card-header">
                <i class="fas fa-users"></i> Daftar Pengisian Rapor Siswa
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">NIS</th>
                                <th style="width: 25%">Nama Siswa</th>
                                <th style="width: 20%">Status Kenaikan/Kelulusan</th>
                                <th style="width: 35%">Catatan Wali Kelas</th>
                            </tr>
                        </thead>
                        <tbody id="wrapper-baris-siswa">
                            </tbody>
                    </table>
                </div>
                
                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Semua Rapor
                    </button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const filterKelas = document.getElementById("filter-kelas");
    const containerTabel = document.getElementById("container-tabel-siswa");
    const wrapperBarisSiswa = document.getElementById("wrapper-baris-siswa");

    filterKelas.addEventListener("change", function() {
        const kelasId = this.value;

        // kalo pilihan dikosongkan lagi
        if (!kelasId) {
            containerTabel.classList.add("d-none");
            wrapperBarisSiswa.innerHTML = "";
            return;
        }

        // jalankan ajax fetch ke file ini sendiri
        fetch(`tambah.php?get_siswa_by_kelas=${kelasId}`)
            .then(response => response.json())
            .then(data => {
                wrapperBarisSiswa.innerHTML = ""; // bersihin tabel lama
                
                if (data.length > 0) {
                    // tampilkan tabel penampung
                    containerTabel.classList.remove("d-none");
                    
                    // render baris form per siswa otomatis
                    data.forEach((siswa, index) => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td class="text-center">${index + 1}</td>
                            <td class="text-center fw-bold">${siswa.nis}</td>
                            <td>${siswa.nama}</td>
                            <td>
                                <select name="rapor[${siswa.id}][status_kenaikan]" class="form-select form-select-sm">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Naik Kelas">Naik Kelas</option>
                                    <option value="Tinggal Kelas">Tinggal Kelas</option>
                                    <option value="Lulus">Lulus</option>
                                </select>
                            </td>
                            <td>
                                <textarea name="rapor[${siswa.id}][catatan]" class="form-control form-control-sm" rows="2" placeholder="Tulis perkembangan hasil belajar siswa..."></textarea>
                            </td>
                        `;
                        wrapperBarisSiswa.appendChild(tr);
                    });
                } else {
                    containerTabel.classList.remove("d-none");
                    wrapperBarisSiswa.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-folder-open d-block mb-2 fs-4"></i> Tidak ada siswa yang terdaftar di kelas ini.
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error("Error AJAX:", error);
                siToast('error', 'Gagal memuat data siswa. Silakan coba lagi.');
            });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>