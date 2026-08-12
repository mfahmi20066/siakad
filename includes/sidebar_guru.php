<?php
// Pastikan jembatan koneksi aman agar tidak memicu error duplikat atau undefined
if (!isset($koneksi)) {
    include __DIR__ . '/../config/koneksi.php';
}

// Mengambil total pengumuman sekolah secara independen dan aman tanpa menggunakan JOIN
$q_count_pengumuman = mysqli_query($koneksi, "SELECT COUNT(*) FROM pengumuman");
$jml_pengumuman = ($q_count_pengumuman) ? mysqli_fetch_row($q_count_pengumuman)[0] : 0;

// Generate initials for avatar
$nama = e($_SESSION['nama'] ?? 'Guru');
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
                <small>Portal Guru</small>
            </div>
        </div>
    </div>

        <!-- Sidebar User Profile -->
    <div class="sidebar-user sidebar-user-modern">
        <?php
        $nama = e($_SESSION['nama'] ?? 'Guru');
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
            <span class="user-role"><i class="bi bi-mortarboard-fill me-1" style="font-size: 8px;"></i> Guru</span>
        </div>
    </div>

    <!-- Sidebar Navigation Menu --><!-- Sidebar Navigation Menu -->
    <div class="sidebar-menu sidebar-menu-modern">

        <div class="sidebar-section-label menu-label">Menu Utama</div>

        <a href="/siakad/guru/dashboard.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <a href="/siakad/guru/profil.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil Saya</span>
        </a>

        <div class="sidebar-section-label menu-label">Akademik</div>

        <a href="/siakad/guru/jadwal/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/jadwal/') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar-week"></i>
            <span>Jadwal Mengajar</span>
        </a>

        <a href="/siakad/guru/nilai/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/nilai/') !== false ? 'active' : '' ?>">
            <i class="bi bi-star"></i>
            <span>Input Nilai</span>
        </a>

        <a href="/siakad/guru/absensi/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/absensi/') !== false ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check"></i>
            <span>Absensi Siswa</span>
        </a>

        <div class="sidebar-section-label menu-label">Informasi</div>

        <a href="/siakad/guru/pengumuman/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/pengumuman/') !== false ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i>
            <span>Pengumuman</span>
            <?php if ($jml_pengumuman > 0): ?>
                <span class="badge bg-danger ms-auto" style="font-size: 10px; padding: 3px 7px; border-radius: 50px;">
                    <?= $jml_pengumuman ?>
                </span>
            <?php endif; ?>
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

