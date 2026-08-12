<?php
// Generate initials for avatar
$nama = e($_SESSION['nama'] ?? 'Siswa');
$nameParts = explode(' ', $nama);
$initials = '';
foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2);
?>

<div class="sidebar sidebar-modern" id="sidebar">
    
    <!-- Sidebar Header / Logo -->
    <div class="sidebar-header sidebar-header-modern">
        <div class="sidebar-logo-wrapper">
            <img src="/siakad/assets/img/logo-sekolah.png"
                 onerror="this.src='https://via.placeholder.com/50?text=Logo'"
                 alt="Logo SMAN 4 Palopo"
                 class="sidebar-logo-img">
            <div class="sidebar-logo-text sidebar-logo-text-modern">
                <h5>SMA Negeri 4<br>Palopo</h5>
                <small>Portal Siswa</small>
            </div>
        </div>
    </div>

        <!-- Sidebar User Profile -->
    <div class="sidebar-user sidebar-user-modern">
        <?php
        $nama = e($_SESSION['nama'] ?? 'Siswa');
        $nameParts = explode(' ', $nama);
        $initials = '';
        foreach ($nameParts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        $initials = substr($initials, 0, 2);
        ?>
        <div class="sidebar-user-avatar-placeholder"><?= $initials ?></div>
        <div class="sidebar-user-info">
            <span class="user-name"><?php echo e($nama) ?></span>
            <span class="user-role"><i class="bi bi-mortarboard-fill me-1" style="font-size: 8px;"></i> Siswa</span>
        </div>
    </div>

    <!-- Sidebar Navigation Menu --><!-- Sidebar Navigation Menu -->
    <div class="sidebar-menu sidebar-menu-modern">

        <div class="sidebar-section-label menu-label">Menu Utama</div>

        <a href="/siakad/siswa/dashboard.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <a href="/siakad/siswa/profil.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil Saya</span>
        </a>

        <div class="sidebar-section-label menu-label">Akademik</div>

        <a href="/siakad/siswa/jadwal.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-week"></i>
            <span>Jadwal Pelajaran</span>
        </a>

        <a href="/siakad/siswa/nilai.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'nilai.php' ? 'active' : '' ?>">
            <i class="bi bi-star"></i>
            <span>Nilai Saya</span>
        </a>

        <a href="/siakad/siswa/absensi.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'absensi.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check"></i>
            <span>Absensi Saya</span>
        </a>

        <a href="/siakad/siswa/rapor.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'rapor.php' ? 'active' : '' ?>">
            <i class="bi bi-file-text"></i>
            <span>Rapor</span>
        </a>

        <div class="sidebar-section-label menu-label">Informasi</div>

        <a href="/siakad/siswa/pengumuman.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'pengumuman.php' ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i>
            <span>Pengumuman</span>
        </a>

    </div>

<!-- Logout -->
    <div class="sidebar-logout sidebar-logout-modern">
        <a href="javascript:void(0)" class="sidebar-nav-link nav-link" onclick="event.stopPropagation(); siLogout();">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>

</div>

