<?php
// fix path include koneksi biar ga salah folder
include '../../config/koneksi.php';

$kelas_id = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : '';

if (!empty($kelas_id)) {
    // ambil siswa by kelas_id
    if (!isset($stmt_siswa) || $stmt_siswa === null) {
        $stmt_siswa = mysqli_prepare($koneksi, "SELECT * FROM siswa WHERE kelas_id = ? ORDER BY nama");
        mysqli_stmt_bind_param($stmt_siswa, "i");
    }
    mysqli_stmt_bind_param($stmt_siswa, "i", $kelas_id);
    mysqli_stmt_execute($stmt_siswa);
    $result = mysqli_stmt_get_result($stmt_siswa);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo '<option value="">-- Pilih Siswa --</option>';
        while ($s = mysqli_fetch_assoc($result)) {
            // deteksi penamaan kolom nama secara fleksibel
            $nama_tampil = '';
            if (isset($s['nama'])) { $nama_tampil = $s['nama']; }
            elseif (isset($s['nama_siswa'])) { $nama_tampil = $s['nama_siswa']; }
            elseif (isset($s['nama_lengkap'])) { $nama_tampil = $s['nama_lengkap']; }
            else { $nama_tampil = "Siswa ID: " . $s['id']; }
            
            echo '<option value="' . $s['id'] . '">' . e($nama_tampil) . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada siswa di kelas ini</option>';
    }
} else {
    echo '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
}