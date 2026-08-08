<?php
include '../../config/koneksi.php';
include '../../config/session.php';
cekAdmin();

$title = "Export Data SPMB";

// Query dasar untuk filter
$query = "SELECT sp.*, sg.nama_gelombang, sj.nama_jalur 
          FROM spmb_pendaftar sp
          LEFT JOIN spmb_gelombang sg ON sp.gelombang_id = sg.id
          LEFT JOIN spmb_jalur sj ON sp.jalur_id = sj.id
          WHERE 1=1";

// Filter values
$gelombang_filter = isset($_GET['gelombang']) ? (int)$_GET['gelombang'] : '';
$jalur_filter = isset($_GET['jalur']) ? (int)$_GET['jalur'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Apply filters
if (!empty($gelombang_filter)) {
    $query .= " AND sp.gelombang_id = $gelombang_filter";
}
if (!empty($jalur_filter)) {
    $query .= " AND sp.jalur_id = $jalur_filter";
}
if (!empty($status_filter)) {
    $query .= " AND sp.status = '$status_filter'";
}
if (!empty($search)) {
    $query .= " AND (sp.nama_lengkap LIKE '%$search%' OR sp.no_pendaftaran LIKE '%$search%')";
}

$query .= " ORDER BY sp.created_at DESC";

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $data = mysqli_query($koneksi, $query);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="spmb-export-' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
    $setting   = mysqli_fetch_assoc($q_setting);
    
    echo "<table border='1'>";
    echo "<tr><td colspan='8' style='font-size:10px;text-align:center;border:none;'>PEMERINTAH KOTA PALOPO</td></tr>";
    echo "<tr><td colspan='8' style='font-size:10px;text-align:center;border:none;'>DINAS PENDIDIKAN</td></tr>";
    echo "<tr><td colspan='8' style='font-size:18px;font-weight:bold;text-align:center;border:none;color:#163A63;'>SMA NEGERI 4 PALOPO</td></tr>";
    echo "<tr><td colspan='8' style='font-size:10px;text-align:center;border:none;'>" . htmlspecialchars($setting['alamat_sekolah'] ?? '-') . "</td></tr>";
    echo "<tr><td colspan='8' style='border:none;'>&nbsp;</td></tr>";
    echo "<tr><td colspan='8' style='font-size:13px;font-weight:bold;text-align:center;border:none;'>REKAP DATA PENDAFTAR SPMB</td></tr>";
    echo "<tr><td colspan='8' style='font-size:10px;text-align:center;border:none;'>Dicetak pada: " . date('d-m-Y H:i') . " WITA</td></tr>";
    echo "<tr><td colspan='8' style='border:none;'>&nbsp;</td></tr>";
    echo "<tr>";
    echo "<th>No</th>";
    echo "<th>No. Pendaftaran</th>";
    echo "<th>Nama</th>";
    echo "<th>Email</th>";
    echo "<th>Jalur</th>";
    echo "<th>Gelombang</th>";
    echo "<th>Status</th>";
    echo "<th>Tgl. Daftar</th>";
    echo "</tr>";
    
    if ($data && mysqli_num_rows($data) > 0):
        $no = 1;
        while ($row = mysqli_fetch_assoc($data)):
            $status_labels = [
                'menunggu_dokumen' => 'Menunggu Dokumen',
                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                'diverifikasi' => 'Diverifikasi',
                'lolos_seleksi' => 'Lolos Seleksi',
                'diterima' => 'Diterima',
                'ditolak' => 'Ditolak',
            ];
            $status = $status_labels[$row['status']] ?? $row['status'];
            $tgl_daftar = date('d-m-Y H:i', strtotime($row['created_at']));
            
            echo "<tr>";
            echo "<td>$no</td>";
            echo "<td>" . htmlspecialchars($row['no_pendaftaran']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_jalur']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_gelombang']) . "</td>";
            echo "<td>$status</td>";
            echo "<td>$tgl_daftar</td>";
            echo "</tr>";
            $no++;
        endwhile;
    endif;
    
    echo "</table>";
    
    echo "<br><table border='0' width='100%'>";
    echo "<tr><td width='70%'></td><td style='text-align:center;'>Palopo, " . date('d-m-Y') . "</td></tr>";
    echo "<tr><td></td><td style='text-align:center;'>Mengetahui,<br>Kepala Sekolah,</td></tr>";
    echo "<tr><td></td><td style='text-align:center;'><br><br><br><br><br><u><strong>" . htmlspecialchars($setting['nama_kepsek'] ?? '-') . "</strong></u><br>NIP. " . htmlspecialchars($setting['nip_kepsek'] ?? '-') . "</td></tr>";
    echo "</table>";
    exit();
}

// Export to PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    $dompdf = new Dompdf\Dompdf();
    
    $data = mysqli_query($koneksi, $query);

    $q_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
    $setting   = mysqli_fetch_assoc($q_setting);
    $logo = realpath(__DIR__ . '/../../assets/img/logo-sekolah.png');
    $logo_src = $logo ? 'file://' . str_replace('\\','/',$logo) : '';
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #163A63; color: white; padding: 10px; text-align: left; }
            td { border: 1px solid #E2E8F0; padding: 8px; }
            h2 { color: #163A63; }
            .header { text-align: center; margin-bottom: 20px; }
            .kop { display:flex; align-items:center; border-bottom:3px double #000; padding-bottom:10px; margin-bottom:10px; }
            .kop img { width:60px; height:60px; object-fit:contain; }
            .kop .logo-kiri { margin-right:12px; }
            .kop .logo-kanan { margin-left:12px; }
            .kop-text { text-align:center; flex:1; line-height:1.3; }
            .kop-text .instansi { font-size:10px; }
            .kop-text .sekolah { font-size:15px; font-weight:bold; text-transform:uppercase; color:#163A63; }
            .kop-text .alamat { font-size:10px; }
            .judul { text-align:center; font-size:15px; font-weight:bold; text-decoration:underline; margin:12px 0; }
            .ttd { margin-top:40px; width:100%; }
            .ttd .tgl { text-align:right; font-size:11px; }
            .ttd table { width:100%; border-collapse:collapse; margin-top:40px; }
            .ttd td { text-align:center; font-size:11px; vertical-align:top; padding:0 10px; }
            .ttd .garis { margin-top:55px; border-bottom:1px solid #000; }
            .ttd .nama { margin-top:3px; font-weight:bold; text-decoration:underline; }
        </style>
    </head>
    <body>
        <div class='kop'>
            " . ($logo_src ? "<img class='logo-kiri' src='$logo_src'>" : '') . "
            <div class='kop-text'>
                <div class='instansi'>PEMERINTAH KOTA PALOPO<br>DINAS PENDIDIKAN</div>
                <div class='sekolah'>SMA NEGERI 4 PALOPO</div>
                <div class='alamat'>" . htmlspecialchars($setting['alamat_sekolah'] ?? '-') . "</div>
            </div>
            " . ($logo_src ? "<img class='logo-kanan' src='$logo_src'>" : '') . "
        </div>
        <div class='judul'>REKAP DATA PENDAFTAR SPMB</div>
        <p style='text-align:center; font-size:11px;'>Dicetak pada: " . date('d-m-Y H:i:s') . " WITA</p>
        
        <table>
            <tr>
                <th>No</th>
                <th>No. Pendaftaran</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Jalur</th>
                <th>Gelombang</th>
                <th>Status</th>
                <th>Tgl. Daftar</th>
            </tr>
    ";
    
    if ($data && mysqli_num_rows($data) > 0):
        $no = 1;
        while ($row = mysqli_fetch_assoc($data)):
            $status_labels = [
                'menunggu_dokumen' => 'Menunggu Dokumen',
                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                'diverifikasi' => 'Diverifikasi',
                'lolos_seleksi' => 'Lolos Seleksi',
                'diterima' => 'Diterima',
                'ditolak' => 'Ditolak',
            ];
            $status = $status_labels[$row['status']] ?? $row['status'];
            $tgl_daftar = date('d-m-Y H:i', strtotime($row['created_at']));
            
            $html .= "
            <tr>
                <td>$no</td>
                <td>" . htmlspecialchars($row['no_pendaftaran']) . "</td>
                <td>" . htmlspecialchars($row['nama_lengkap']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
                <td>" . htmlspecialchars($row['nama_jalur']) . "</td>
                <td>" . htmlspecialchars($row['nama_gelombang']) . "</td>
                <td>$status</td>
                <td>$tgl_daftar</td>
            </tr>
            ";
            $no++;
        endwhile;
    endif;
    
    $html .= "</table>";
    $html .= "<div class='ttd' style='page-break-inside:avoid;'><div class='tgl'>Palopo, " . date('d-m-Y') . "</div>";
    $html .= "<table><tr><td>Mengetahui,<br>Kepala Sekolah,</td><td></td></tr>";
    $html .= "<tr><td><div class='garis'></div><div class='nama'>" . htmlspecialchars($setting['nama_kepsek'] ?? '-') . "</div><div>NIP. " . htmlspecialchars($setting['nip_kepsek'] ?? '-') . "</div></td><td><div class='garis'></div><div class='nama'>&nbsp;</div></td></tr></table></div>";
    $html .= "</body></html>";
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = 'spmb-laporan-' . date('Y-m-d') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit();
}

// Ambil gelombang & jalur untuk filter (hanya saat tidak export)
$query_gelombang = mysqli_query($koneksi, "SELECT * FROM spmb_gelombang WHERE status='aktif' ORDER BY tanggal_mulai ASC");
$query_jalur = mysqli_query($koneksi, "SELECT * FROM spmb_jalur ORDER BY id ASC");
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-file-export text-gold me-2"></i>Export Data SPMB</h4>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="gelombang" class="form-label">Gelombang</label>
                    <select class="form-select" id="gelombang" name="gelombang">
                        <option value="">Semua Gelombang</option>
                        <?php while ($g = mysqli_fetch_assoc($query_gelombang)): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $gelombang_filter == $g['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['nama_gelombang']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="jalur" class="form-label">Jalur</label>
                    <select class="form-select" id="jalur" name="jalur">
                        <option value="">Semua Jalur</option>
                        <?php while ($j = mysqli_fetch_assoc($query_jalur)): ?>
                        <option value="<?php echo $j['id']; ?>" <?php echo $jalur_filter == $j['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($j['nama_jalur']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="diterima" <?php echo $status_filter == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                        <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nama / No." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-12 mt-3">
                    <a href="?export=excel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel me-2"></i> Export Excel (.xls)
                    </a>
                    <a href="?export=pdf" class="btn btn-danger">
                        <i class="fas fa-file-pdf me-2"></i> Export PDF (.pdf)
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info -->
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-info-circle"></i> Informasi Export</h5>
            <ul>
                <li><strong>Excel (.xls):</strong> Format terbuka yang bisa dibuka di Microsoft Excel, Google Sheets, dll.</li>
                <li><strong>PDF (.pdf):</strong> Format dokumen statis yang siap dicetak atau dibagikan.</li>
                <li>Data yang di-export: No. Pendaftaran, Nama, Email, Jalur, Gelombang, Status, Tgl. Daftar</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
