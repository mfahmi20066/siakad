<?php
include '../config/koneksi.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

// Ambil ID pendaftar dari URL
$pendaftar_id = (int) ($_GET['id'] ?? 0);

if ($pendaftar_id <= 0) {
    die("ID Pendaftar tidak valid");
}

// OTENTIKASI 2-FACTOR: wajib no_pendaftaran + tanggal_lahir cocok dengan data
// (tanpa ini ?id=N membocorkan biodata pendaftar mana pun)
$no_pendaftaran_cek = mysqli_real_escape_string($koneksi, $_GET['no_pendaftaran'] ?? '');
$tanggal_lahir_cek = mysqli_real_escape_string($koneksi, $_GET['tanggal_lahir'] ?? '');
if (empty($no_pendaftaran_cek) || empty($tanggal_lahir_cek)) {
    die("Akses ditolak: verifikasi nomor pendaftaran dan tanggal lahir diperlukan.");
}

// Query pendaftar
$query = mysqli_query($koneksi, "
    SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
    FROM spmb_pendaftar sp
    LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
    LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
    WHERE sp.id=$pendaftar_id 
      AND sp.no_pendaftaran='$no_pendaftaran_cek' 
      AND sp.tanggal_lahir='$tanggal_lahir_cek'
");

if (!$query || mysqli_num_rows($query) == 0) {
    die("Data pendaftar tidak ditemukan");
}

$pendaftar = mysqli_fetch_assoc($query);

// Generate HTML untuk PDF
$html = "
<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <style>
        @page { margin: 1.6cm; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: white;
            color: #1A202C;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #163A63;
            padding: 24px;
            background: white;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #163A63;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #163A63;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #4A5568;
            font-size: 12px;
        }
        .school-name {
            font-weight: bold;
            font-size: 16px;
            color: #163A63;
            margin-bottom: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background: #163A63;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table tr {
            border-bottom: 1px solid #E2E8F0;
        }
        .detail-table td {
            padding: 12px 0;
            font-size: 12px;
        }
        .detail-label {
            width: 35%;
            font-weight: bold;
            color: #163A63;
        }
        .detail-value {
            width: 65%;
            color: #4A5568;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
        }
        .status-diterima {
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        .status-menunggu {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FDE68A;
        }
        .status-ditolak {
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #163A63;
            text-align: center;
            font-size: 11px;
            color: #94A3B8;
        }
        .print-date {
            color: #94A3B8;
            font-size: 11px;
            margin-top: 10px;
        }
        .barcode-container {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
        }
        .no-pendaftaran-large {
            font-size: 20px;
            font-weight: bold;
            color: #163A63;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .warning-box {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin-top: 20px;
            font-size: 11px;
            color: #92400E;
        }
    </style>
</head>
<body>
    <div class='container'>
        <!-- HEADER -->
        <div class='header'>
            <div class='school-name'>SMA NEGERI 4 PALOPO</div>
            <h1>BUKTI PENDAFTARAN SPMB</h1>
            <p>Sistem Penerimaan Murid Baru Online Tahun 2026</p>
        </div>

        <!-- BIODATA PENDAFTAR -->
        <div class='section'>
            <div class='section-title'>BIODATA PENDAFTAR</div>
            <table class='detail-table'>
                <tr>
                    <td class='detail-label'>Nomor Pendaftaran</td>
                    <td class='detail-value'><strong>" . e($pendaftar['no_pendaftaran']) . "</strong></td>
                </tr>
                <tr>
                    <td class='detail-label'>Nama Lengkap</td>
                    <td class='detail-value'>" . e($pendaftar['nama_lengkap']) . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>NIK</td>
                    <td class='detail-value'>" . e($pendaftar['nik']) . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>NISN</td>
                    <td class='detail-value'>" . e($pendaftar['nisn'] ?? '-') . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Tempat / Tanggal Lahir</td>
                    <td class='detail-value'>" . e($pendaftar['tempat_lahir']) . " / " . date('d-m-Y', strtotime($pendaftar['tanggal_lahir'])) . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Jenis Kelamin</td>
                    <td class='detail-value'>" . ($pendaftar['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Email</td>
                    <td class='detail-value'>" . e($pendaftar['email']) . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>No. HP Orang Tua</td>
                    <td class='detail-value'>" . e($pendaftar['no_hp_ortu']) . "</td>
                </tr>
            </table>
        </div>

        <!-- DATA PENDAFTARAN -->
        <div class='section'>
            <div class='section-title'>DATA PENDAFTARAN</div>
            <table class='detail-table'>
                <tr>
                    <td class='detail-label'>Jalur Pendaftaran</td>
                    <td class='detail-value'>" . e($pendaftar['nama_jalur'] ?? 'N/A') . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Gelombang</td>
                    <td class='detail-value'>" . e($pendaftar['nama_gelombang'] ?? 'N/A') . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Tanggal Pendaftaran</td>
                    <td class='detail-value'>" . date('d-m-Y H:i', strtotime($pendaftar['created_at'])) . "</td>
                </tr>
                <tr>
                    <td class='detail-label'>Asal Sekolah</td>
                    <td class='detail-value'>" . e($pendaftar['asal_sekolah']) . "</td>
                </tr>
            </table>
        </div>

        <!-- STATUS PENDAFTARAN -->
        <div class='section'>
            <div class='section-title'>STATUS PENDAFTARAN</div>
            <table class='detail-table'>
                <tr>
                    <td class='detail-label'>Status Terkini</td>
                    <td class='detail-value'>
                        " . getStatusBadge($pendaftar['status']) . "
                    </td>
                </tr>
                <tr>
                    <td class='detail-label'>Terakhir Diupdate</td>
                    <td class='detail-value'>" . date('d-m-Y H:i', strtotime($pendaftar['updated_at'])) . "</td>
                </tr>
            </table>
        </div>

        <!-- CATATAN PENTING -->
        <div class='warning-box'>
            <span class='badge badge-danger mb-2'><i class='fas fa-exclamation-triangle'></i> CATATAN PENTING:</span><br>
            1. Bukti pendaftaran ini adalah dokumen sementara yang dicetak dari sistem online.<br>
            2. Calon siswa yang diterima akan menerima surat penerimaan resmi dari sekolah.<br>
            3. Silakan cek status pendaftaran secara berkala di halaman cek status online kami.<br>
            4. Hubungi bagian admisi jika ada pertanyaan: (0471) 324567
        </div>

        <!-- BARCODE -->
        <div class='barcode-container'>
            <div class='no-pendaftaran-large'>" . e($pendaftar['no_pendaftaran']) . "</div>
            <p style='margin: 0; color: #94A3B8; font-size: 10px;'>Simpan nomor ini dengan baik untuk referensi</p>
        </div>

        <!-- FOOTER -->
        <div class='footer'>
            <p>Bukti Pendaftaran SPMB Online SMA Negeri 4 Palopo</p>
            <div class='print-date'>
                Dicetak pada: " . date('d-m-Y H:i:s') . "
            </div>
        </div>
    </div>
</body>
</html>
";

// Helper function untuk status badge
function getStatusBadge($status) {
    $status_config = [
        'menunggu_dokumen' => 'Menunggu Dokumen',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'diverifikasi' => 'Diverifikasi',
        'lolos_seleksi' => 'Lolos Seleksi',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
    ];
    
    $label = $status_config[$status] ?? 'Tidak Dikenal';
    $class = 'status-menunggu';
    
    if ($status == 'diterima') $class = 'status-diterima';
    elseif ($status == 'ditolak') $class = 'status-ditolak';
    
    return "<span class='status-badge $class'>$label</span>";
}

// Generate PDF menggunakan Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$filename = 'bukti-pendaftaran-' . $pendaftar['no_pendaftaran'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
?>
