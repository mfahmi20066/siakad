<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/koneksi.php';

// auth: wajib login (admin/guru/siswa) atau token valid
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized: Silakan login terlebih dahulu.');
}

// validasi parameter input
$pendaftar_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$jenis_dokumen = isset($_GET['jenis']) ? preg_replace('/[^a-z_]/', '', $_GET['jenis']) : '';
$no_pendaftaran = isset($_GET['no']) ? mysqli_real_escape_string($koneksi, $_GET['no']) : '';
$tanggal_lahir = isset($_GET['tgl']) ? mysqli_real_escape_string($koneksi, $_GET['tgl']) : '';

// validasi parameter
if (!$pendaftar_id || !$jenis_dokumen || !$no_pendaftaran || !$tanggal_lahir) {
    http_response_code(400);
    die('Invalid parameters');
}

// verifikasi pendaftar pake no_pendaftaran + tanggal_lahir (security check)
$query = "SELECT sp.id FROM spmb_pendaftar sp 
          WHERE sp.id=$pendaftar_id 
          AND sp.no_pendaftaran='$no_pendaftaran' 
          AND sp.tanggal_lahir='$tanggal_lahir'";
$result = mysqli_query($koneksi, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    http_response_code(403);
    die('Unauthorized access');
}

// ambil path file dokumen dari db
$query_doc = "SELECT path_file FROM spmb_dokumen 
              WHERE pendaftar_id=$pendaftar_id 
              AND jenis_dokumen='$jenis_dokumen'";
$result_doc = mysqli_query($koneksi, $query_doc);

if (!$result_doc || mysqli_num_rows($result_doc) == 0) {
    http_response_code(404);
    die('Document not found');
}

$doc = mysqli_fetch_assoc($result_doc);
$file_path = $doc['path_file'];

// susun path file lengkap
$base_dir = dirname(__FILE__) . '/../uploads/spmb/' . $pendaftar_id . '/';
$full_path = $base_dir . $file_path;

// cegah directory traversal
$real_path = realpath($full_path);
$real_base = realpath($base_dir);

if (!$real_path || strpos($real_path, $real_base) !== 0 || !file_exists($real_path)) {
    http_response_code(404);
    die('File not found');
}

// ambil ekstensi file
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// set content type
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$content_type = $content_types[$file_ext] ?? 'application/octet-stream';

// kirim file
header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($real_path));
header('Content-Disposition: attachment; filename="' . $file_path . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// output file
readfile($real_path);
exit();
?>
