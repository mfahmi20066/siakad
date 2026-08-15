<?php
// widget notif dropdown, dipakai di topbar admin/guru/siswa. butuh $koneksi + session login
if (!function_exists('notifikasi_get')) {
    include __DIR__ . '/notifikasi_functions.php';
}
if (!function_exists('tanggal_indo')) {
    include __DIR__ . '/fungsi_tanggal.php';
}

$uid            = (int) $_SESSION['user_id'];
$notif_belum    = notifikasi_belum_dibaca($koneksi, $uid);
$notif_list     = notifikasi_get($koneksi, $uid, 8);
$notif_display  = $notif_belum > 99 ? '99+' : $notif_belum;
?>
<div class="position-relative">
    <button type="button" class="topbar-icon-btn" id="notificationToggle" title="Notifikasi">
        <i class="bi bi-bell-fill"></i>
        <?php if ($notif_belum > 0): ?>
            <span class="badge-notif" id="notifBadge"><?= $notif_display ?></span>
        <?php endif; ?>
    </button>

    <div class="topbar-dropdown notif-dropdown" id="notificationDropdown">
        <div class="notif-header">
            <strong>Notifikasi</strong>
            <?php if ($notif_belum > 0): ?>
                <button type="button" class="btn-notif-readall" id="notifReadAll">Tandai semua dibaca</button>
            <?php endif; ?>
        </div>

        <div class="notif-list" id="notifList">
            <?php if (empty($notif_list)): ?>
                <div class="notif-empty">
                    <i class="bi bi-bell-slash"></i>
                    <span>Belum ada notifikasi.</span>
                </div>
            <?php else: ?>
                <?php foreach ($notif_list as $n): ?>
                    <?php
                    $link = $n['link'] ? e($n['link']) : '#';
                    $unread = (int)$n['is_read'] === 0;
                    $waktu = tanggal_waktu_indo($n['created_at']);
                    ?>
                    <a href="<?= $link ?>" class="notif-item <?= $unread ? 'unread' : '' ?>"
                       data-id="<?= (int)$n['id'] ?>">
                        <div class="notif-item-text">
                            <strong><?= e(strip_tags($n['judul'])) ?></strong>
                            <span class="notif-msg"><?= e(strip_tags($n['pesan'])) ?></span>
                            <span class="notif-time"><?= $waktu ?></span>
                        </div>
                        <?php if ($unread): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="/siakad/auth/notification.php" class="notif-see-all">
            <i class="bi bi-box-arrow-right"></i> Lihat semua notifikasi
        </a>
    </div>
</div>