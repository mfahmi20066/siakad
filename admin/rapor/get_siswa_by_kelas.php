<?php
// Endpoint AJAX: Mengembalikan daftar siswa dalam bentuk <option> berdasarkan nama kelas, tahun ajaran & semester
include '../../config/koneksi.php';

$nama_kelas = isset($_GET['nama_kelas']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama_kelas'])) : '';
$ta         = isset($_GET['ta']) ? mysqli_real_escape_string($koneksi, trim($_GET['ta'])) : '';
$semester   = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '1';

if (!empty($nama_kelas)) {
    // Ambil id kelas berdasarkan nama_kelas
    $kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT id FROM kelas WHERE nama_kelas = '$nama_kelas' LIMIT 1"));

    if ($kelas) {
        // Ambil semua siswa di kelas tersebut (tanpa syarat punya data rapor),
        // sehingga setiap kelas bisa diproses untuk cetak.
        $where = "s.kelas_id = '{$kelas['id']}'";

        $query = mysqli_query($koneksi,
            "SELECT DISTINCT s.id, s.nis, s.nama
             FROM siswa s
             WHERE $where
             ORDER BY s.nama");

        if ($query && mysqli_num_rows($query) > 0) {
            echo '<option value="">-- Pilih Siswa --</option>';
            while ($s = mysqli_fetch_assoc($query)) {
                $nama_tampil = $s['nama'] ?? '';
                if (empty($nama_tampil)) $nama_tampil = "Siswa ID: " . $s['id'];
                $label = $nama_tampil . (!empty($s['nis']) ? ' (' . $s['nis'] . ')' : '');
                echo '<option value="' . htmlspecialchars($nama_tampil) . '">'
                   . htmlspecialchars($label) . '</option>';
            }
        } else {
            echo '<option value="">Tidak ada siswa terdaftar di kelas ini</option>';
        }
    } else {
        echo '<option value="">Kelas tidak ditemukan</option>';
    }
} else {
    echo '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
}
?>
