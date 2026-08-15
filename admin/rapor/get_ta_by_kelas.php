<?php
// endpoint ajax: daftar ta yang tersedia buat sebuah kelas (json)
include '../../config/koneksi.php';

$nama_kelas = isset($_GET['nama_kelas']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama_kelas'])) : '';
$semester   = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '1';

$data = array();

if (!empty($nama_kelas)) {
    $kelas = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT id FROM kelas WHERE nama_kelas = '$nama_kelas' LIMIT 1"));

    if ($kelas) {
        // ta dari rapor kelas tsb, berbasis tahun_ajaran_id + join master (bukan select distinct)
        $q = mysqli_query($koneksi,
            "SELECT DISTINCT r.tahun_ajaran_id AS ta_id, ta.nama_tahun_ajaran AS nama
             FROM rapor r
             JOIN tahun_ajaran ta ON ta.id = r.tahun_ajaran_id
             WHERE r.kelas_id = '{$kelas['id']}'
               AND (r.semester = '$semester' OR r.semester = '0')
             ORDER BY ta_id DESC");

        if ($q) {
            while ($row = mysqli_fetch_assoc($q)) {
                $data[] = $row['nama'];
            }
        }

        // belum ada rapor? fallback ke ta nilai kelas
        if (empty($data)) {
            $qn = mysqli_query($koneksi,
                "SELECT DISTINCT n.tahun_ajaran_id AS ta_id, ta.nama_tahun_ajaran AS nama
                 FROM nilai n
                 JOIN siswa s ON n.siswa_id = s.id
                 JOIN tahun_ajaran ta ON ta.id = n.tahun_ajaran_id
                 WHERE s.kelas_id = '{$kelas['id']}'
                   AND (n.semester = '$semester' OR n.semester = '0')
                 ORDER BY ta_id DESC");

            if ($qn) {
                while ($row = mysqli_fetch_assoc($qn)) {
                    $data[] = $row['nama'];
                }
            }
        }

        // fallback terakhir: ta berjalan dari master (bukan pengaturan teks)
        if (empty($data)) {
            $qs = mysqli_query($koneksi,
                "SELECT nama_tahun_ajaran FROM tahun_ajaran WHERE status='aktif' LIMIT 1");
            $sys = $qs ? mysqli_fetch_assoc($qs) : null;
            if (!empty($sys['nama_tahun_ajaran'])) {
                $data[] = $sys['nama_tahun_ajaran'];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>
