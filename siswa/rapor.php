<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_tahun_ajaran.php';
cekSiswa();
$title = "Nilai Rapor";

// ambil dinamis dari pengaturan (tahun pelajaran & semester aktif)
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$sys           = mysqli_fetch_assoc($query_setting);

// ambil id siswa dari session
$sid = $_SESSION['id_ref'];

// ambil data siswa buat dapet kelas_id akurat
$q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id = '$sid'");
$siswa   = mysqli_fetch_assoc($q_siswa);

// ambil parameter dari url
$mode     = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : '';
$ta       = isset($_GET['ta']) ? mysqli_real_escape_string($koneksi, $_GET['ta']) : '';

// kalo url ngirim 0/kosong, samain sama data pengaturan admin; semester bisa berisi teks '1 (Ganjil)', ambil angkanya aja
function ambil_angka_semester($val) {
    if (preg_match('/\d+/', (string)$val, $m)) return $m[0];
    return '1';
}
$fix_semester = (!empty($semester) && $semester !== '0')
    ? ambil_angka_semester($semester)
    : ambil_angka_semester($sys['semester'] ?? '1');
$fix_semester = (!empty($semester) && $semester !== '0')
    ? ambil_angka_semester($semester)
    : ambil_angka_semester($sys['semester'] ?? '1');

// ta berbasis id (source of truth) + kompatibilitas param lama 'ta'
$taId = (int)($_GET['tahun_ajaran_id'] ?? 0);
if ($taId <= 0 && !empty($ta)) {
    $qres = mysqli_query($koneksi, "SELECT id FROM tahun_ajaran WHERE nama_tahun_ajaran='$ta' LIMIT 1");
    if ($qres && $rres = mysqli_fetch_assoc($qres)) $taId = (int)$rres['id'];
}
if ($taId <= 0) $taId = (int)($siswa['tahun_ajaran_id'] ?? 0);
if ($taId <= 0) {
    try { $taActive = getTahunAjaranAktif(tahun_ajaran_pdo()); $taId = (int)$taActive['id']; }
    catch (Throwable $e) {}
}
$fix_ta = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT nama_tahun_ajaran v FROM tahun_ajaran WHERE id=$taId"))['v'] ?? $ta;

// ambil nama kelas
$nama_kelas = '-';
$id_kelas   = $siswa['kelas_id'] ?? $siswa['id_kelas'] ?? 0;
if ($id_kelas) {
    $rk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kelas WHERE id='$id_kelas'"));
    $nama_kelas = $rk['nama_kelas'] ?? '-';
    // ambil nama wali kelas
    $wali_id    = $rk['wali_kelas'] ?? 0;
    $nama_wali  = '-';
    if ($wali_id) {
        $rw = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM guru WHERE id='$wali_id'"));
        $nama_wali = $rw['nama_lengkap'] ?? $rw['nama'] ?? '-';
    }
}

// daftar rapor
$daftar_rapor = mysqli_query($koneksi,
    "SELECT r.*, k.nama_kelas
     FROM rapor r
     LEFT JOIN kelas k ON r.kelas_id = k.id
     WHERE r.siswa_id = '$sid'
     ORDER BY r.tahun_ajaran_id DESC, r.semester ASC");

// detail rapor
$nilai_rapor  = null;
$info_rapor   = null;
$rekap_absen  = null;

if ($mode === 'detail') {
    // info rapor; terima juga semester '0' sebagai fallback karena bug input data lama
    $info_rapor = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT r.*, k.nama_kelas
         FROM rapor r
         LEFT JOIN kelas k ON r.kelas_id = k.id
         WHERE r.siswa_id='$sid' AND (r.semester='$fix_semester' OR r.semester='$semester' OR r.semester='0') AND r.tahun_ajaran_id='$taId'
         ORDER BY r.semester DESC
         LIMIT 1"));

    // nilai per mapel (pake $fix_semester & $fix_ta biar ga error '0')
    $nilai_rapor = mysqli_query($koneksi,
        "SELECT n.*, mp.nama_mapel, mp.kode_mapel
         FROM nilai n
         JOIN mata_pelajaran mp ON n.mapel_id = mp.id
         WHERE n.siswa_id='$sid' AND (n.semester='$fix_semester' OR n.semester='1' OR n.semester='Ganjil') AND n.tahun_ajaran_id='$taId'
         ORDER BY mp.nama_mapel");

    // rekap absensi semester ini
    $rekap_absen = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT
            SUM(CASE WHEN status IN ('Hadir','H') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status IN ('Izin','I')  THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status IN ('Sakit','S') THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status IN ('Alpa','A','Alpa') THEN 1 ELSE 0 END) as alpa
         FROM absensi
         WHERE siswa_id='$sid'"));
    $rekap_absen = $rekap_absen ?: ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpa'=>0];
}

include '../includes/header.php';
?>
<?php include '../includes/sidebar_siswa.php'; ?>
<?php include '../includes/topbar_siswa.php'; ?>


<div class="main-content">
<div class="page-header">
  <h4><i class="fas fa-file-alt text-icon me-2"></i>Nilai Rapor</h4>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-auto">
    <i class="fas fa-exclamation-circle"></i> <?= e($_GET['error']) ?>
</div>
<?php endif; ?>

<div class="container-fluid">

<?php if ($mode === 'list'): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">
      <i class="fas fa-file-alt text-primary me-2"></i>Daftar Rapor
    </div>
    <div class="card-body">
      <?php if ($daftar_rapor && mysqli_num_rows($daftar_rapor) > 0): ?>
        <?php while ($r = mysqli_fetch_assoc($daftar_rapor)): ?>
        <div class="card border mb-3">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h6 class="fw-bold mb-1">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Rapor Semester <?= $r['semester'] ?>
              </h6>
              <small class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                Tahun Pelajaran <?= e($r['tahun_ajaran']) ?>
                &nbsp;|&nbsp;
                <i class="fas fa-school me-1"></i>
                <?= e($r['nama_kelas'] ?? $nama_kelas) ?>
              </small>
              <br>
              <span class="badge bg-<?= $r['status']=='final' ? 'success' : 'warning' ?> mt-1">
                <?= ucfirst($r['status'] ?? 'draft') ?>
              </span>
            </div>
            <a href="rapor.php?mode=detail&semester=<?= $r['semester'] ?>&tahun_ajaran_id=<?= (int)$r['tahun_ajaran_id'] ?>"
               class="btn btn-primary">
              <i class="fas fa-eye me-1"></i>Lihat Rapor
            </a>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-center py-5 text-muted">
          <i class="fas fa-file-alt fa-3x mb-3 opacity-25"></i>
          <p>Belum ada rapor yang tersedia.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($mode === 'detail'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="rapor.php" class="btn btn-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
    <?php if (strtolower(trim($info_rapor['status'] ?? 'draft')) === 'final'): ?>
    <a href="cetak_rapor.php?semester=<?= urlencode($fix_semester) ?>&ta=<?= urlencode($fix_ta) ?>"
       target="_blank" class="btn btn-danger btn-sm">
      <i class="fas fa-print me-1"></i>Cetak / Download PDF
    </a>
    <?php else: ?>
    <span class="text-muted small">
      <i class="fas fa-lock me-1"></i>Cetak tersedia setelah rapor difinalisasi
    </span>
    <?php endif; ?>
  </div>

  <div class="card shadow-sm" id="rapor-cetak">
    <div class="card-body p-4">

      <div class="text-center mb-4">
        <h5 class="fw-bold mb-0">RAPOR SISWA</h5>
        <h6 class="fw-bold">SMA NEGERI 4 PALOPO</h6>
        <p class="text-muted mb-0" style="font-size:13px;">
          Semester <?= e($fix_semester) ?> | Tahun Pelajaran <?= e($fix_ta) ?>
        </p>
      </div>

      <hr>

      <div class="row mb-4">
        <div class="col-md-6">
          <table class="table table-sm table-borderless" style="font-size:13px;">
            <tr><td style="width:130px;">Nama Siswa</td><td>: <strong><?= e($siswa['nama_lengkap'] ?? $siswa['nama'] ?? '-') ?></strong></td></tr>
            <tr><td>NIS</td><td>: <?= e($siswa['nis'] ?? $siswa['username'] ?? '-') ?></td></tr>
            <tr><td>Kelas</td><td>: <?= e($nama_kelas) ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm table-borderless" style="font-size:13px;">
            <tr><td style="width:130px;">Wali Kelas</td><td>: <?= e($nama_wali ?? '-') ?></td></tr>
            <tr><td>Tahun Ajaran</td><td>: <?= e($fix_ta) ?></td></tr>
            <tr><td>Semester</td><td>: <?= e($fix_semester) ?></td></tr>
          </table>
        </div>
      </div>

      <h6 class="fw-bold mb-2"><i class="fas fa-star me-1"></i>Nilai Mata Pelajaran</h6>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm" style="font-size:13px;">
          <thead class="table-dark">
            <tr>
              <th class="text-center" style="width:40px;">No</th>
              <th>Mata Pelajaran</th>
              <th class="text-center">Nilai Harian</th>
              <th class="text-center">UTS</th>
              <th class="text-center">UAS</th>
              <th class="text-center">Nilai Akhir</th>
              <th class="text-center">Predikat</th>
              <th class="text-center">Ket.</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1; $total_na = 0; $jml = 0;
            if ($nilai_rapor && mysqli_num_rows($nilai_rapor) > 0):
              while ($n = mysqli_fetch_assoc($nilai_rapor)):
                $na  = $n['nilai_akhir'] ?? $n['na'] ?? 0;
                $nh  = $n['nilai_uh'] ?? $n['nilai_harian'] ?? $n['uh'] ?? 0;
                $nu  = $n['nilai_uts'] ?? $n['uts'] ?? 0;
                $nua = $n['nilai_uas'] ?? $n['uas'] ?? 0;
                $p   = $na>=90?'A':($na>=80?'B':($na>=70?'C':($na>=60?'D':'E')));
                $total_na += $na; $jml++;
            ?>
            <tr>
              <td class="text-center"><?= $no++ ?></td>
              <td><?= e($n['nama_mapel']) ?></td>
              <td class="text-center"><?= $nh ?></td>
              <td class="text-center"><?= $nu ?></td>
              <td class="text-center"><?= $nua ?></td>
              <td class="text-center fw-bold <?= $na>=75?'text-success':'text-danger' ?>"><?= $na ?></td>
              <td class="text-center"><span class="badge bg-<?= $na>=75?'success':'danger' ?>"><?= $p ?></span></td>
              <td class="text-center"><?= $na>=75?'Lulus':'Remidi' ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="text-center text-muted">Belum ada nilai untuk Semester <?= $fix_semester ?></td></tr>
            <?php endif; ?>
          </tbody>
          <?php if ($jml > 0): ?>
          <tfoot class="table-light">
            <tr>
              <td colspan="5" class="text-end">Rata-rata Nilai Akhir</td>
              <td class="text-center fw-bold"><?= round($total_na/$jml, 2) ?></td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>

      <h6 class="fw-bold mb-2"><i class="fas fa-clipboard-check me-1"></i>Rekap Kehadiran</h6>
      <div class="row mb-4">
        <div class="col-3 col-md-2">
          <div class="card text-center border-success">
            <div class="card-body py-2">
              <div class="fw-bold text-success fs-5"><?= $rekap_absen['hadir'] ?? 0 ?></div>
              <small>Hadir</small>
            </div>
          </div>
        </div>
        <div class="col-3 col-md-2">
          <div class="card text-center border-info">
            <div class="card-body py-2">
              <div class="fw-bold text-info fs-5"><?= $rekap_absen['izin'] ?? 0 ?></div>
              <small>Izin</small>
            </div>
          </div>
        </div>
        <div class="col-3 col-md-2">
          <div class="card text-center border-warning">
            <div class="card-body py-2">
              <div class="fw-bold text-warning fs-5"><?= $rekap_absen['sakit'] ?? 0 ?></div>
              <small>Sakit</small>
            </div>
          </div>
        </div>
        <div class="col-3 col-md-2">
          <div class="card text-center border-danger">
            <div class="card-body py-2">
              <div class="fw-bold text-danger fs-5"><?= $rekap_absen['alpa'] ?? 0 ?></div>
              <small>Alpa</small>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($info_rapor['catatan'])): ?>
      <div class="mb-4">
        <h6 class="fw-bold mb-2"><i class="fas fa-comment me-1"></i>Catatan Wali Kelas</h6>
        <div class="p-3 bg-light rounded" style="font-size:13px;">
          <?= nl2br(e($info_rapor['catatan'])) ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="row mt-4">
        <div class="col-md-4 text-center">
          <p style="font-size:12px;">Orang Tua / Wali</p>
          <div style="height:60px;"></div>
          <p style="font-size:12px;">
            <strong><?= e($siswa['nama_ortu'] ?? '-') ?></strong>
            <?php if (!empty($siswa['no_hp_ortu'])): ?><br><small><?= e($siswa['no_hp_ortu']) ?></small><?php endif; ?>
          </p>
        </div>
        <div class="col-md-4"></div>
        <div class="col-md-4 text-center">
          <p style="font-size:12px;">
            Palopo, <?= tanggal_indo() ?><br>Wali Kelas
          </p>
          <div style="height:60px;"></div>
          <p style="font-size:12px;">
            <strong><?= e($nama_wali ?? '-') ?></strong>
          </p>
        </div>
      </div>

    </div>
  </div>

<?php endif; ?>
</div>
</div>

<?php include '../includes/footer.php'; ?>