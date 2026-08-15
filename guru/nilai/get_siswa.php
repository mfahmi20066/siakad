<?php
// path mundur disesuaikan buat letak folder guru/nilai/
include '../../config/koneksi.php';
include '../../config/session.php';
cekGuru();

$gid = isset($_SESSION['id_ref']) ? $_SESSION['id_ref'] : '';
$kelas_id = isset($_GET['kelas_id']) ? mysqli_real_escape_string($koneksi, $_GET['kelas_id']) : '';

if (!empty($kelas_id) && !empty($gid)) {
    // cuma siswa yang kelasnya terdaftar di pivot ngajar guru ini
    $query = mysqli_query($koneksi, 
        "SELECT DISTINCT s.* FROM siswa s
         JOIN kelas_mapel_guru kmg ON kmg.kelas_id = s.kelas_id
         WHERE s.kelas_id = '$kelas_id' AND kmg.guru_id = '$gid'
         ORDER BY s.nama");
    
    if (mysqli_num_rows($query) > 0) {
        echo '<option value="">-- Pilih Siswa --</option>';
        while ($s = mysqli_fetch_assoc($query)) {
            // cek fleksibel nama kolom di tabel siswa
            $nama_tampil = '';
            if (isset($s['nama'])) { $nama_tampil = $s['nama']; }
            elseif (isset($s['nama_siswa'])) { $nama_tampil = $s['nama_siswa']; }
            elseif (isset($s['nama_lengkap'])) { $nama_tampil = $s['nama_lengkap']; }
            else { $nama_tampil = "Siswa ID: " . $s['id']; }
            
            echo '<option value="' . $s['id'] . '">' . e($nama_tampil) . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada siswa / Anda tidak mengajar di kelas ini</option>';
    }
} else {
    echo '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
}
?>