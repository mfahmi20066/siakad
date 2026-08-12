<?php
$nama = e($_SESSION['nama'] ?? 'Guru');
$nameParts = explode(' ', $nama);
$initials = '';
foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2);

// Foto profil (dari session saat login / upload)
$foto_profil = trim($_SESSION['foto'] ?? '');
$foto_path   = '';
if ($foto_profil !== '') {
    $foto_file = __DIR__ . '/../assets/img/foto_guru/' . $foto_profil;
    if (file_exists($foto_file)) {
        $foto_path = '/siakad/assets/img/foto_guru/' . rawurlencode($foto_profil);
    }
}
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
        <!-- Chat Internal -->
        <a class="topbar-icon-btn" href="/siakad/chat/index.php" id="chatTopLink" title="Pesan">
            <i class="bi bi-chat-dots-fill"></i>
            <span class="badge-notif" id="chatTopBadge" style="display:none">0</span>
        </a>
        <!-- Profile Dropdown -->
        <div class="topbar-profile position-relative" id="profileDropdownToggle" onclick="toggleProfileDropdown()">
            <?php if ($foto_path !== ''): ?>
                <img src="<?= $foto_path ?>" alt="Foto Profil" class="profile-placeholder" style="border-radius:50%;">
            <?php else: ?>
                <div class="profile-placeholder"><?= $initials ?></div>
            <?php endif; ?>
            <span class="profile-name"><?= $nama ?></span>
            <i class="bi bi-chevron-down"></i>
            
            <!-- Dropdown -->
            <div class="topbar-dropdown" id="profileDropdown">
                <a href="/siakad/guru/profil.php" class="dropdown-item">
                    <i class="bi bi-person-circle"></i>
                    <span>Profil Saya</span>
                </a>
                <a href="/siakad/guru/pengaturan_akun.php" class="dropdown-item">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan Akun</span>
                </a>
<div class="dropdown-divider"></div>
                <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="event.stopPropagation(); siLogout();">
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
