<?php
$nama = htmlspecialchars($_SESSION['nama'] ?? 'Siswa');
$nameParts = explode(' ', $nama);
$initials = '';
foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<div class="topbar">
<div class="topbar-left">
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Sembunyikan Menu" onclick="toggleSidebarCollapse()">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Cari menu, data, atau informasi..." id="topbarSearch">
    </div>
</div>
    <div class="topbar-right">
        <!-- Notifications -->
        <?php include __DIR__ . '/notifikasi_dropdown.php'; ?>
        <!-- Profile Dropdown -->
        <div class="topbar-profile position-relative" id="profileDropdownToggle" onclick="toggleProfileDropdown()">
            <div class="profile-placeholder"><?= $initials ?></div>
            <span class="profile-name"><?= $nama ?></span>
            <i class="bi bi-chevron-down"></i>
            
            <!-- Dropdown -->
            <div class="topbar-dropdown" id="profileDropdown">
                <a href="/siakad/siswa/profil.php" class="dropdown-item">
                    <i class="bi bi-person-circle"></i>
                    <span>Profil Saya</span>
                </a>
                <a href="/siakad/siswa/pengaturan_akun.php" class="dropdown-item">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan Akun</span>
                </a>
<div class="dropdown-divider"></div>
                <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="event.stopPropagation(); siLogout();">>
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleProfileDropdown() {
    var dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    var toggle = document.getElementById('profileDropdownToggle');
    var dropdown = document.getElementById('profileDropdown');
    if (toggle && !toggle.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});
</script>
