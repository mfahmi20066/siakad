<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';
cekSiswa();

$id_siswa   = $_SESSION['id_ref'] ?? $_SESSION['id_siswa'] ?? $_SESSION['user_id'] ?? 0;

// fix: paksa bulan jadi integer (buang nol di depan, '05' -> 5)
$bulan      = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n'); 
$tahun      = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// ambil data siswa
$res   = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id='$id_siswa'");
$siswa = mysqli_fetch_assoc($res) ?: [];
$siswa['nama_lengkap'] = $siswa['nama_lengkap'] ?? $siswa['nama'] ?? $_SESSION['nama'] ?? '-';
$siswa['nis']          = $siswa['nis'] ?? '-';

// ambil nama kelas
$nama_kelas = '-';
$id_kelas   = $siswa['id_kelas'] ?? $siswa['kelas_id'] ?? 0;
if ($id_kelas) {
    $rk = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id='$id_kelas'");
    $dk = mysqli_fetch_assoc($rk);
    $nama_kelas = $dk['nama_kelas'] ?? $dk['nama'] ?? '-';
}

// deteksi nama kolom di tabel absensi
$cols_res = mysqli_query($koneksi, "SHOW COLUMNS FROM absensi");
$cols = [];
while ($c = mysqli_fetch_assoc($cols_res)) $cols[] = $c['Field'];

// tentuin nama kolom id siswa
$col_siswa = 'id_siswa';
foreach (['id_siswa','siswa_id','id_murid','murid_id','nis'] as $try) {
    if (in_array($try, $cols)) { $col_siswa = $try; break; }
}

// tentuin nama kolom tanggal
$col_tgl = 'tanggal';
foreach (['tanggal','tgl','tgl_absen','date','tanggal_absen'] as $try) {
    if (in_array($try, $cols)) { $col_tgl = $try; break; }
}

// tentuin nama kolom status
$col_status = 'status';
foreach (['status','keterangan_absen','hadir','kehadiran'] as $try) {
    if (in_array($try, $cols)) { $col_status = $try; break; }
}

// tentuin kolom mapel (opsional)
$col_mapel = null;
foreach (['id_mapel','mapel_id','id_mata_pelajaran'] as $try) {
    if (in_array($try, $cols)) { $col_mapel = $try; break; }
}

// tentuin kolom keterangan (opsional)
$col_ket = null;
foreach (['keterangan','catatan','note'] as $try) {
    if (in_array($try, $cols)) { $col_ket = $try; break; }
}

// ambil absensi siswa
$data_absen = [];
$jumlah     = ['H'=>0,'I'=>0,'S'=>0,'A'=>0];

$join_mapel = $col_mapel ? "LEFT JOIN mata_pelajaran mp ON a.$col_mapel = mp.id" : "";
$sel_mapel  = $col_mapel ? ", mp.nama_mapel" : ", '-' as nama_mapel";
$sel_ket    = $col_ket   ? ", a.$col_ket as keterangan" : ", '' as keterangan";

$sql_absen = "SELECT a.*, a.$col_tgl as tgl_absen, a.$col_status as status_absen
              $sel_mapel $sel_ket
              FROM absensi a $join_mapel
              WHERE a.$col_siswa = '$id_siswa'
              AND MONTH(a.$col_tgl) = '$bulan'
              AND YEAR(a.$col_tgl) = '$tahun'
              ORDER BY a.$col_tgl ASC";

$ra = mysqli_query($koneksi, $sql_absen);

if ($ra) {
    while ($a = mysqli_fetch_assoc($ra)) {
        $st  = strtoupper(substr($a['status_absen'] ?? $a[$col_status] ?? '', 0, 1));
        $tgl = $a['tgl_absen'] ?? $a[$col_tgl];
        $data_absen[] = [
            'tgl'        => $tgl,
            'tgl_fmt'    => tanggal_indo_pendek($tgl),
            'hari'       => ['','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'][date('N', strtotime($tgl))],
            'nama_mapel' => $a['nama_mapel'] ?? '-',
            'status'     => $st,
            'keterangan' => $a['keterangan'] ?? '',
        ];
        if (isset($jumlah[$st])) $jumlah[$st]++;
    }
}

$total  = array_sum($jumlah);
$persen = $total > 0 ? round(($jumlah['H'] / $total) * 100) : 0;

$title = "Absensi Saya"; $icon = "fa-clipboard-check";
require_once '../includes/header.php';
?>
<?php require_once '../includes/sidebar_siswa.php'; ?>
<?php include '../includes/topbar_siswa.php'; ?>


<div class="main-content">
<div class="page-header">
  <h4><i class="fas fa-clipboard-check text-icon me-2"></i>Absensi Saya</h4>
</div>

<div class="container-fluid">

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white fw-bold">
    <i class="fas fa-filter text-primary me-2"></i>Filter Absensi Saya
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">Bulan</label>
        <select name="bulan" class="form-select">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?=$i?>" <?=$bulan==$i?'selected':''?>><?=$bulan_nama[$i]?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Tahun</label>
        <select name="tahun" class="form-select">
          <?php for($y=date('Y')-1;$y<=date('Y')+1;$y++): ?>
          <option value="<?=$y?>" <?=$tahun==$y?'selected':''?>><?=$y?></option>
          <?php endfor; ?>
        </select>
      </div>
<div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-1"></i>Tampilkan
        </button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="display-6 fw-bold text-success"><?=$jumlah['H']?></div>
      <div class="text-muted small">Hadir</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="display-6 fw-bold text-info"><?=$jumlah['I']?></div>
      <div class="text-muted small">Izin</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="display-6 fw-bold text-warning"><?=$jumlah['S']?></div>
      <div class="text-muted small">Sakit</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3">
      <div class="display-6 fw-bold text-danger"><?=$jumlah['A']?></div>
      <div class="text-muted small">Alpa</div>
    </div>
  </div>
</div>

<?php if($total>0): ?>
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-1">
      <span class="fw-semibold">Persentase Kehadiran</span>
<?php
      $kelasPersen = ($persen >= 75) ? 'success' : (($persen >= 50) ? 'warning' : 'danger');
    ?>
    <span class="fw-bold text-<?=$kelasPersen?>">
      <?=$persen?>%
    </span>
  </div>
  <div class="progress" style="height:12px;">
    <div class="progress-bar bg-<?=$kelasPersen?>" style="width:<?=$persen?>%"></div>
    </div>
    <small class="text-muted mt-1 d-block">
      Total <?=$total?> pertemuan | <?=$jumlah['H']?> hadir | <?=$jumlah['I']?> izin | <?=$jumlah['S']?> sakit | <?=$jumlah['A']?> alpa
    </small>
  </div>
</div>
<?php endif; ?>

<div class="card shadow-sm" id="tabelAbsen">
  <div class="card-header bg-white fw-bold">
    <i class="fas fa-list me-2"></i>
    Detail Absensi — <?=$bulan_nama[$bulan]?> <?=$tahun?>
  </div>
  <div class="card-body p-0">
    <?php if(count($data_absen)>0): ?>
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0" style="font-size:13px;">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width:40px;">No</th>
            <th>Tanggal</th>
            <th>Hari</th>
            <th>Mata Pelajaran</th>
            <th class="text-center" style="width:90px;">Status</th>
            <th>Keterangan</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; foreach($data_absen as $a):
            $badge = match($a['status']) {
              'H'=>'success','I'=>'info','S'=>'warning','A'=>'danger',default=>'secondary'
            };
            $label = match($a['status']) {
              'H'=>'Hadir','I'=>'Izin','S'=>'Sakit','A'=>'Alpa',default=>$a['status']
            };
          ?>
          <tr>
            <td class="text-center"><?=$no++?></td>
            <td><?=$a['tgl_fmt']?></td>
            <td><?=$a['hari']?></td>
            <td><?=e($a['nama_mapel'])?></td>
            <td class="text-center">
              <span class="badge bg-<?=$badge?> px-3 py-1"><?=$label?></span>
            </td>
            <td><?=e($a['keterangan'])?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
      <i class="fas fa-clipboard fa-3x mb-3 opacity-25"></i>
      <p class="mb-0">Tidak ada data absensi untuk <strong><?=$bulan_nama[$bulan]?> <?=$tahun?></strong></p>
    </div>
    <?php endif; ?>
  </div>
</div>

</div></div>

<?php require_once '../includes/footer.php'; ?>
