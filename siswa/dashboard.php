<?php
include '../config/koneksi.php';
include '../config/session.php';
cekSiswa();
$title = "Dashboard Siswa";

// ── Ambil session ID siswa ────────
$sid = $_SESSION['siswa_id'] ?? $_SESSION['id_ref'] ?? $_SESSION['id_siswa'] ?? $_SESSION['user_id'] ?? 0;

// ── Ambil data siswa dengan deteksi kolom struktur secara aman ────────
$cols_siswa = [];
$cs = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa");
while ($c = mysqli_fetch_assoc($cs)) {
    $cols_siswa[] = $c['Field'];
}

// Tentukan kondisi JOIN berdasarkan kolom yang tersedia di database Anda
if (in_array('kelas_id', $cols_siswa)) {
    $join_condition = "s.kelas_id = k.id";
} elseif (in_array('id_kelas', $cols_siswa)) {
    $join_condition = "s.id_kelas = k.id";
} else {
    $join_condition = "1=1"; // Fallback aman jika relasi kelas disimpan di tabel lain
}

// Jalankan query siswa
$query_siswa = "SELECT s.*, k.nama_kelas
                FROM siswa s
                LEFT JOIN kelas k ON $join_condition
                WHERE s.id = '$sid'";
$siswa = mysqli_fetch_assoc(mysqli_query($koneksi, $query_siswa));
$siswa = $siswa ?: [];
$nama = $_SESSION['nama'] ?? ($siswa['nama_lengkap'] ?? ($siswa['nama'] ?? 'Siswa'));

// Deteksi nama variabel kelas_id untuk query selanjutnya
$id_kelas = $siswa['kelas_id'] ?? $siswa['id_kelas'] ?? 0;

// Ambil nama kelas manual jika kolom JOIN ternyata tidak terhubung otomatis
if (empty($siswa['nama_kelas']) && $id_kelas) {
    $rk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kelas WHERE id='$id_kelas'"));
    $siswa['nama_kelas'] = $rk['nama_kelas'] ?? $rk['nama'] ?? '-';
}
$siswa['nama_kelas'] = $siswa['nama_kelas'] ?? '-';

// ── Statistik nilai ──────────────────────────────────────────
$cols_nilai = [];
$cn = mysqli_query($koneksi, "SHOW COLUMNS FROM nilai");
while ($c = mysqli_fetch_assoc($cn)) $cols_nilai[] = $c['Field'];
$col_nilai_siswa = in_array('siswa_id',$cols_nilai) ? 'siswa_id' : (in_array('id_siswa',$cols_nilai) ? 'id_siswa' : 'siswa_id');
$col_nilai_mapel = in_array('mapel_id',$cols_nilai) ? 'mapel_id' : (in_array('id_mapel',$cols_nilai) ? 'id_mapel' : 'mapel_id');

$jml_nilai = mysqli_fetch_row(mysqli_query($koneksi,
    "SELECT COUNT(*) FROM nilai WHERE $col_nilai_siswa='$sid'"))[0] ?? 0;

// ── Statistik absensi ────────────────────────────────────────
$cols_abs = [];
$ca = mysqli_query($koneksi, "SHOW COLUMNS FROM absensi");
while ($c = mysqli_fetch_assoc($ca)) $cols_abs[] = $c['Field'];
$col_abs_siswa = in_array('siswa_id',$cols_abs) ? 'siswa_id' : (in_array('id_siswa',$cols_abs) ? 'id_siswa' : 'siswa_id');
$col_abs_status = in_array('status',$cols_abs) ? 'status' : 'status';

$hadir = mysqli_fetch_row(mysqli_query($koneksi,
    "SELECT COUNT(*) FROM absensi WHERE $col_abs_siswa='$sid' AND $col_abs_status IN ('Hadir','H','hadir')"))[0] ?? 0;
$alpa = mysqli_fetch_row(mysqli_query($koneksi,
    "SELECT COUNT(*) FROM absensi WHERE $col_abs_siswa='$sid' AND $col_abs_status IN ('Alpa','A','Alpa','alpa','alpa')"))[0] ?? 0;
$total_abs = mysqli_fetch_row(mysqli_query($koneksi,
    "SELECT COUNT(*) FROM absensi WHERE $col_abs_siswa='$sid'"))[0] ?? 0;
$persen_hadir = $total_abs > 0 ? round(($hadir / $total_abs) * 100, 1) : 0;

// ── Pengumuman ───────────────────────────────────────────────
$pengumuman = mysqli_query($koneksi,
    "SELECT * FROM pengumuman ORDER BY tanggal DESC LIMIT 3");

// ── Nilai terbaru ────────────────────────────────────────────
$nilai_terbaru = mysqli_query($koneksi,
    "SELECT n.*, mp.nama_mapel
     FROM nilai n
     LEFT JOIN mata_pelajaran mp ON n.$col_nilai_mapel = mp.id
     WHERE n.$col_nilai_siswa = '$sid'
     ORDER BY n.id DESC LIMIT 5");

// ── Jadwal hari ini ──────────────────────────────────────────
$jadwal_hari_ini = null;
$hari_map = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
             'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];
$hari_id  = $hari_map[date('l')] ?? '';

if ($hari_id && $id_kelas) {
    $cols_jdw = [];
    $cj = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal");
    while ($c = mysqli_fetch_assoc($cj)) $cols_jdw[] = $c['Field'];
    $col_jdw_kelas = in_array('kelas_id',$cols_jdw) ? 'kelas_id' : (in_array('id_kelas',$cols_jdw) ? 'id_kelas' : 'kelas_id');
    $col_jdw_mapel = in_array('mapel_id',$cols_jdw) ? 'mapel_id' : (in_array('id_mapel',$cols_jdw) ? 'id_mapel' : 'mapel_id');
    $col_jdw_guru  = in_array('guru_id',$cols_jdw) ? 'guru_id' : (in_array('id_guru',$cols_jdw) ? 'id_guru' : 'guru_id');

    $jadwal_hari_ini = mysqli_query($koneksi,
        "SELECT j.*, mp.nama_mapel,
                g.nama_lengkap AS nama_guru,
                g.nama AS nama_guru2
         FROM jadwal j
         LEFT JOIN mata_pelajaran mp ON j.$col_jdw_mapel = mp.id
         LEFT JOIN guru g ON j.$col_jdw_guru = g.id
         WHERE j.$col_jdw_kelas = '$id_kelas' AND j.hari = '$hari_id'
         ORDER BY j.jam_mulai");
}

// ── Jadwal pelajaran lengkap (backend sama seperti siswa/jadwal.php) ──
$jadwal_pelajaran = [];
if ($id_kelas) {
    $q_jp = mysqli_query($koneksi,
        "SELECT j.*, mp.nama_mapel, g.nama_lengkap AS nama_guru
         FROM jadwal j
         LEFT JOIN mata_pelajaran mp ON j.$col_jdw_mapel = mp.id
         LEFT JOIN guru g ON j.$col_jdw_guru = g.id
         WHERE j.$col_jdw_kelas = '$id_kelas'
         ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat'), j.jam_mulai");
    if ($q_jp) {
        while ($r = mysqli_fetch_assoc($q_jp)) $jadwal_pelajaran[] = $r;
    }
}

// Kelompokkan jadwal per hari (urutan Senin–Jumat)
$urutan_hari = ['Senin','Selasa','Rabu','Kamis','Jumat'];
$jadwal_group = [];
foreach ($urutan_hari as $h) $jadwal_group[$h] = [];
foreach ($jadwal_pelajaran as $jp) {
    if (isset($jadwal_group[$jp['hari']])) $jadwal_group[$jp['hari']][] = $jp;
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_siswa.php'; ?>

<div class="main-content">
  <?php include '../includes/topbar_siswa.php'; ?>

  <!-- Selamat Datang -->
  <div class="welcome-banner mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3 p-4"
       style="background:linear-gradient(135deg,#0D2540 0%,#163A63 55%,#2C5A8F 100%);border-radius:18px;box-shadow:0 18px 44px rgba(13,37,64,.14);border:1px solid rgba(255,255,255,.08);">
    <div>
      <h4 class="mb-1 fw-bold" style="color:#fff;font-size:22px;">
        <i class="fas fa-hand-sparkles me-2" style="color:#F09000;"></i>
        Selamat datang, <?= htmlspecialchars($nama) ?>!
      </h4>
      <p class="mb-0" style="color:rgba(255,255,255,.78);font-size:13.5px;">
        Sistem Informasi Akademik SMA Negeri 4 Palopo
      </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="badge" style="background:rgba(240,144,0,.18);color:#FFB74D;padding:8px 14px;font-size:12.5px;border-radius:20px;">
        <i class="fas fa-user-graduate me-1"></i> Siswa
      </span>
      <span class="badge" style="background:rgba(255,255,255,.12);color:#fff;padding:8px 14px;font-size:12.5px;border-radius:20px;">
        <i class="fas fa-calendar-day me-1"></i> <?= tanggal_indo_pendek(date('Y-m-d')) ?>
      </span>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-3">
      <div class="stat-card bg-blue">
        <i class="fas fa-school stat-icon"></i>
        <div class="stat-info">
          <h3><?= htmlspecialchars($siswa['nama_kelas']) ?></h3>
          <p>Kelas Saya</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card bg-green">
        <i class="fas fa-star stat-icon"></i>
        <div class="stat-info">
          <h3><?= $jml_nilai ?></h3>
          <p>Total Nilai</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card bg-orange">
        <i class="fas fa-check stat-icon"></i>
        <div class="stat-info">
          <h3><?= $hadir ?></h3>
          <p>Hari Hadir</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card bg-red">
        <i class="fas fa-times stat-icon"></i>
        <div class="stat-info">
          <h3><?= $alpa ?></h3>
          <p>Alpa</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3 mt-4">
    <div class="card-body py-2">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="fw-bold"><i class="fas fa-chart-line"></i> Persentase Kehadiran</small>
        <small class="fw-bold <?= $persen_hadir >= 75 ? 'text-success' : 'text-danger' ?>"><?= $persen_hadir ?>%</small>
      </div>
      <div class="progress" style="height:10px">
        <div class="progress-bar bg-<?= $persen_hadir >= 75 ? 'success' : 'danger' ?>"
             style="width:<?= $persen_hadir ?>%"></div>
      </div>
      <?php if ($persen_hadir < 75): ?>
      <small class="text-danger">
        <i class="fas fa-exclamation-triangle"></i> Kehadiran Anda di bawah 75%. Harap perhatikan kehadiran!
      </small>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">

      <div class="card mb-3">
        <div class="card-header">
          <i class="fas fa-calendar-day"></i> Jadwal Hari Ini
          <span class="badge bg-secondary ms-1"><?= $hari_id ?: date('l') ?></span>
        </div>
        <div class="card-body">
          <?php if ($jadwal_hari_ini && mysqli_num_rows($jadwal_hari_ini) > 0): ?>
            <?php while ($j = mysqli_fetch_assoc($jadwal_hari_ini)): ?>
            <div class="d-flex align-items-center p-2 mb-2 bg-light rounded">
              <div class="me-3 text-center" style="min-width:65px">
                <strong class="text-primary small"><?= substr($j['jam_mulai'], 0, 5) ?></strong><br>
                <small class="text-muted"><?= substr($j['jam_selesai'], 0, 5) ?></small>
              </div>
              <div>
                <div class="fw-bold small"><?= htmlspecialchars($j['nama_mapel'] ?? '-') ?></div>
                <small class="text-muted">
                  <i class="fas fa-user-tie"></i>
                  <?= htmlspecialchars($j['nama_guru'] ?? $j['nama_guru2'] ?? '-') ?>
                </small>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-muted text-center py-3 mb-0">
              <i class="fas fa-coffee"></i> Tidak ada pelajaran hari ini
            </p>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><i class="fas fa-star"></i> Nilai Terbaru</div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th class="ps-3">Mata Pelajaran</th>
                <th>Nilai Akhir</th>
                <th>Predikat</th>
                <th>Semester</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($nilai_terbaru && mysqli_num_rows($nilai_terbaru) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($nilai_terbaru)):
                $na = $n['nilai_akhir'] ?? $n['nilai'] ?? 0;
                $p = $na>=90?'A':($na>=80?'B':($na>=70?'C':($na>=60?'D':'E')));
            ?>
            <tr>
              <td class="ps-3"><?= htmlspecialchars($n['nama_mapel'] ?? '-') ?></td>
              <td><strong class="<?= $na>=75?'text-success':'text-danger' ?>"><?= $na ?></strong></td>
              <td><span class="badge bg-<?= $na>=75?'success':'danger' ?>"><?= $p ?></span></td>
              <td><small>Smt <?= $n['semester'] ?? '-' ?></small></td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada nilai</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
          <div class="p-2 text-end">
            <a href="nilai.php" class="btn btn-outline-primary btn-sm">Lihat Semua Nilai →</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-5">

      <!-- Jadwal Pelajaran Siswa -->
      <div class="card mb-3">
        <div class="card-header">
          <i class="fas fa-calendar-alt"></i> Jadwal Pelajaran Siswa
          <span class="badge bg-secondary ms-1"><?= count($jadwal_pelajaran) ?> sesi</span>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($jadwal_pelajaran)): ?>
            <div style="max-height:300px;overflow-y:auto;">
              <?php foreach ($jadwal_group as $hari => $sesi): if (empty($sesi)) continue; ?>
              <div class="px-3 py-2 border-bottom">
                <strong class="small d-block mb-1" style="color:#163A63;">
                  <i class="fas fa-circle me-1" style="font-size:8px;color:#F09000;"></i><?= $hari ?>
                </strong>
                <?php foreach ($sesi as $s): ?>
                <div class="d-flex align-items-center py-1">
                  <span class="badge bg-light text-dark me-2" style="min-width:50px;font-weight:600;">
                    <?= substr($s['jam_mulai'], 0, 5) ?>
                  </span>
                  <span class="small text-dark"><?= htmlspecialchars($s['nama_mapel'] ?? '-') ?></span>
                  <small class="text-muted ms-auto">
                    <i class="fas fa-user-tie"></i> <?= htmlspecialchars($s['nama_guru'] ?? '-') ?>
                  </small>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="p-2 text-center">
              <a href="jadwal.php" class="btn btn-link btn-sm">Lihat Jadwal Lengkap →</a>
            </div>
          <?php else: ?>
            <p class="text-muted p-3 mb-0">
              <i class="fas fa-calendar-times me-1"></i> Jadwal pelajaran belum tersedia.
            </p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pengumuman Terbaru -->
      <div class="card">
        <div class="card-header"><i class="fas fa-bullhorn"></i> Pengumuman Terbaru</div>
        <div class="card-body p-0">
          <?php if ($pengumuman && mysqli_num_rows($pengumuman) > 0): ?>
            <?php while ($pg = mysqli_fetch_assoc($pengumuman)): ?>
            <div class="p-3 border-bottom">
              <strong class="small d-block"><?= htmlspecialchars($pg['judul'] ?? '') ?></strong>
              <small class="text-muted">
                <i class="fas fa-calendar"></i>
                <?= tanggal_indo_pendek($pg['tanggal'] ?? 'now') ?>
              </small>
              <p class="small mb-0 mt-1 text-muted">
                <?= substr(htmlspecialchars($pg['isi'] ?? ''), 0, 80) ?>...
              </p>
            </div>
            <?php endwhile; ?>
            <div class="p-2 text-center">
              <a href="pengumuman.php" class="btn btn-link btn-sm">Lihat semua →</a>
            </div>
          <?php else: ?>
            <p class="text-muted p-3">Belum ada pengumuman.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>