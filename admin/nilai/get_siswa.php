<?php
// Perbaikan path include koneksi agar tidak salah folder
include '../../config/koneksi.php';

$kelas_id = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';

if (!empty($kelas_id)) {
    // Menarik data siswa berdasarkan kelas_id yang dipilih
    $query = mysqli_query($koneksi, "SELECT * FROM siswa WHERE kelas_id = '$kelas_id' ORDER BY nama");
    
    if (mysqli_num_rows($query) > 0) {
        echo '<option value="">-- Pilih Siswa --</option>';
        while ($s = mysqli_fetch_assoc($query)) {
            // Deteksi penamaan kolom nama secara fleksibel
            $nama_tampil = '';
            if (isset($s['nama'])) { $nama_tampil = $s['nama']; }
            elseif (isset($s['nama_siswa'])) { $nama_tampil = $s['nama_siswa']; }
            elseif (isset($s['nama_lengkap'])) { $nama_tampil = $s['nama_lengkap']; }
            else { $nama_tampil = "Siswa ID: " . $s['id']; }
            
            echo '<option value="' . $s['id'] . '">' . htmlspecialchars($nama_tampil) . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada siswa di kelas ini</option>';
    }
} else {
    echo '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
}
?>