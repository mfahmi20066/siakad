<?php
// includes/sidebar_guru.php
// Ambil foto dari database jika session foto belum ada
if (!isset($_SESSION['foto'])) {
    $id_g  = $_SESSION['id_ref'] ?? 0;
    $qf    = mysqli_query($koneksi, "SELECT foto FROM guru WHERE id='$id_g'");
    $rowf  = mysqli_fetch_assoc($qf);
    $_SESSION['foto'] = $rowf['foto'] ?? '';
}

$foto_sesi = $_SESSION['foto'] ?? '';
$foto_path = (!empty($foto_sesi) && file_exists(__DIR__ . "/../../assets/img/foto_guru/" . $foto_sesi))
             ? "/siakad/assets/img/foto_guru/" . $foto_sesi
             : "/siakad/assets/img/default-avatar.png";
?>

<div class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="logo-area text-center py-3">
    <img src="/siakad/assets/img/logo-sekolah.png"
         alt="Logo" style="width:55px;" class="mb-1">
    <div class="text-white fw-bold" style="font-size:13px;">SMA Negeri 4 Palopo</div>
    <div class="text-white-50" style="font-size:11px;">Portal Guru</div>
  </div>

  <!-- User Info + Foto -->
  <div class="sidebar-user text-center py-3 px-2 border-bottom border-secondary">
    <div class="position-relative d-inline-block">
      <img src="<?= $foto_path ?>"
           class="rounded-circle border border-2 border-white shadow-sm"
           style="width:70px; height:70px; object-fit:cover;"
           alt="Foto Profil">
    </div>
    <div class="text-white fw-semibold mt-2" style="font-size:13px;">
      <?= htmlspecialchars($_SESSION['nama'] ?? '') ?>
    </div>
    <span class="badge bg-success mt-1">Guru</span>
  </div>

  <!-- Menu -->
  <div class="sidebar-menu">
    <div class="menu-title px-3 py-2 text-uppercase" style="font-size:10px; color:rgba(255,255,255,0.5); letter-spacing:1px;">Menu Utama</div>
    <nav class="nav flex-column px-2">
      <a href="/siakad/guru/dashboard.php"
         class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
      </a>
      <a href="/siakad/guru/profil.php"
         class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
        <i class="fas fa-user-tie me-2"></i>Profil Saya
      </a>
    </nav>

    <div class="menu-title px-3 py-2 text-uppercase mt-2" style="font-size:10px; color:rgba(255,255,255,0.5); letter-spacing:1px;">Akademik</div>
    <nav class="nav flex-column px-2">
      <a href="/siakad/guru/jadwal/index.php"
         class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'jadwal') !== false ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt me-2"></i>Jadwal Mengajar
      </a>
      <a href="/siakad/guru/nilai/index.php"
         class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'nilai') !== false ? 'active' : '' ?>">
        <i class="fas fa-star me-2"></i>Nilai Siswa
      </a>
      <a href="/siakad/guru/absensi/index.php"
         class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'absensi') !== false ? 'active' : '' ?>">
        <i class="fas fa-clipboard-check me-2"></i>Absensi Siswa
      </a>
    </nav>

    <div class="menu-title px-3 py-2 text-uppercase mt-2" style="font-size:10px; color:rgba(255,255,255,0.5); letter-spacing:1px;">Informasi</div>
    <nav class="nav flex-column px-2">
      <a href="/siakad/guru/pengumuman/index.php"
         class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'pengumuman') !== false ? 'active' : '' ?>">
        <i class="fas fa-bullhorn me-2"></i>Pengumuman
      </a>
    </nav>
  </div>

  <!-- Logout -->
  <div class="sidebar-logout px-2 py-2">
    <a href="javascript:void(0)"
       class="nav-link text-danger fw-semibold"
       onclick="siLogout()">
      <i class="fas fa-sign-out-alt me-2"></i>Logout
    </a>
  </div>

</div>