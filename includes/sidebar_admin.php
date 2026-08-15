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
                <small>Sistem Informasi Akademik</small>
            </div>
        </div>
    </div>

        <!-- Sidebar User Profile -->
    <div class="sidebar-user sidebar-user-modern">
        <?php
        $initials = '';
        $nama = e($_SESSION['nama'] ?? 'Admin');
        $nameParts = explode(' ', $nama);
        foreach ($nameParts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        $initials = substr($initials, 0, 2);
        ?>
        <div class="sidebar-user-avatar-placeholder"><?= $initials ?></div>
        <div class="sidebar-user-info">
            <span class="user-name"><?= $nama ?></span>
            <span class="user-role"><i class="bi bi-shield-fill-check me-1" style="font-size: 8px;"></i> Administrator</span>
        </div>
    </div>

    <!-- Sidebar Navigation Menu -->
    <div class="sidebar-menu sidebar-menu-modern">

        <div class="sidebar-section-label menu-label">Utama</div>

        <a href="/siakad/admin/dashboard.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-label menu-label">Data Master</div>

        <a href="/siakad/admin/guru/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/admin/guru/') !== false ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Data Guru</span>
        </a>

        <a href="/siakad/admin/siswa/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/admin/siswa/') !== false ? 'active' : '' ?>">
            <i class="bi bi-person-vcard"></i>
            <span>Data Siswa</span>
        </a>

        <a href="/siakad/admin/kelas/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/kelas/') !== false ? 'active' : '' ?>">
            <i class="bi bi-building"></i>
            <span>Data Kelas</span>
        </a>

        <a href="/siakad/admin/mata_pelajaran/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/mata_pelajaran/') !== false ? 'active' : '' ?>">
            <i class="bi bi-book"></i>
            <span>Mata Pelajaran</span>
        </a>

        <a href="/siakad/admin/kelas_mapel_guru/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/kelas_mapel_guru/') !== false ? 'active' : '' ?>">
            <i class="bi bi-kanban"></i>
            <span>Penugasan Mapel</span>
        </a>

        <div class="sidebar-section-label menu-label">Akademik</div>

        <a href="/siakad/admin/jadwal/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/jadwal/') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar-week"></i>
            <span>Jadwal Pelajaran</span>
        </a>

        <a href="/siakad/admin/nilai/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/nilai/') !== false && strpos($_SERVER['PHP_SELF'], '/nilai/periode.php') === false ? 'active' : '' ?>">
            <i class="bi bi-star"></i>
            <span>Nilai</span>
        </a>

        <a href="/siakad/admin/nilai/periode.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/nilai/periode.php') !== false ? 'active' : '' ?>">
            <i class="bi bi-lock"></i>
            <span>Periode Nilai</span>
        </a>

        <a href="/siakad/admin/absensi/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/absensi/') !== false ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check"></i>
            <span>Absensi</span>
        </a>

        <a href="/siakad/admin/rapor/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/rapor/') !== false ? 'active' : '' ?>">
            <i class="bi bi-file-text"></i>
            <span>Rapor</span>
        </a>

        <div class="sidebar-section-label menu-label">Kesiswaan</div>

        <a href="/siakad/admin/prestasi/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/prestasi/') !== false ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i>
            <span>Prestasi</span>
        </a>

        <a href="/siakad/admin/bk/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/bk/') !== false ? 'active' : '' ?>">
            <i class="bi bi-exclamation-triangle"></i>
            <span>BK / Pelanggaran</span>
        </a>

        <a href="/siakad/admin/ekskul/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/ekskul/') !== false ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Ekstrakurikuler</span>
        </a>

        <div class="sidebar-section-label menu-label">Penerimaan Siswa Baru</div>

        <?php
        // badge pendaftar spmb yang belum diverifikasi
        $jml_spmb_pending = 0;
        if (isset($koneksi)) {
            $q_spmb_pending = mysqli_query($koneksi,
                "SELECT COUNT(*) AS jml FROM spmb_pendaftar WHERE status='menunggu_verifikasi' LIMIT 1");
            if ($q_spmb_pending) {
                $result = mysqli_fetch_assoc($q_spmb_pending);
                $jml_spmb_pending = (int) ($result['jml'] ?? 0);
            }
        }
        ?>

        <a href="/siakad/admin/spmb/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/spmb/') !== false ? 'active' : '' ?>">
            <i class="bi bi-mortarboard"></i>
            <span>SPMB Online</span>
            <?php if ($jml_spmb_pending > 0): ?>
                <span class="badge bg-warning ms-auto" style="font-size: 10px; padding: 3px 7px; border-radius: 50px; color: #000;">
                    <?= $jml_spmb_pending ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="/siakad/admin/spmb/pendaftar/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/spmb/pendaftar/') !== false ? 'active' : '' ?>" style="padding-left: 3rem;">
            <i class="bi bi-person-fill"></i>
            <span>Data Pendaftar</span>
            <?php if ($jml_spmb_pending > 0): ?>
                <span class="badge bg-info ms-auto" style="font-size: 10px; padding: 3px 7px; border-radius: 50px;">
                    <?= $jml_spmb_pending ?>
                </span>
            <?php endif; ?>
        </a>

        <div class="sidebar-section-label menu-label">Website Publik</div>

        <a href="/siakad/admin/beranda/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/beranda/') !== false ? 'active' : '' ?>">
            <i class="bi bi-house"></i>
            <span>Kelola Beranda</span>
        </a>

        <a href="/siakad/admin/beranda/kelola_berita.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'kelola_berita.php' ? 'active' : '' ?>" style="padding-left: 3rem;">
            <i class="bi bi-newspaper"></i>
            <span>Berita & Pengumuman</span>
        </a>

        <a href="/siakad/admin/beranda/kelola_galeri.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'kelola_galeri.php' ? 'active' : '' ?>" style="padding-left: 3rem;">
            <i class="bi bi-images"></i>
            <span>Galeri Sekolah</span>
        </a>

        <a href="/siakad/admin/beranda/index.php#struktur"
           class="sidebar-nav-link nav-link" style="padding-left: 3rem;">
            <i class="bi bi-diagram-3"></i>
            <span>Struktur Organisasi</span>
        </a>

        <div class="sidebar-section-label menu-label">Lainnya</div>

        <a href="/siakad/admin/laporan/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/laporan/') !== false ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Laporan</span>
        </a>

        <a href="/siakad/admin/tahun_ajaran/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/tahun_ajaran/') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar2-week"></i>
            <span>Tahun Ajaran</span>
        </a>

        <a href="/siakad/admin/pengumuman/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/pengumuman/') !== false ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i>
            <span>Pengumuman</span>
        </a>

        <a href="/siakad/admin/users/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Pengguna</span>
        </a>

        <?php
        // badge akun pending buat menu verifikasi akun
        $jml_pending = 0;
        if (isset($koneksi)) {
            $q_pending = mysqli_query($koneksi,
                "SELECT COUNT(*) AS jml FROM users WHERE status='pending'");
            if ($q_pending) $jml_pending = (int) mysqli_fetch_assoc($q_pending)['jml'];
        }
        ?>

        <a href="/siakad/admin/verifikasi_akun/index.php"
           class="sidebar-nav-link nav-link <?= strpos($_SERVER['PHP_SELF'], '/verifikasi_akun/') !== false ? 'active' : '' ?>">
            <i class="bi bi-person-check"></i>
            <span>Verifikasi Akun</span>
            <?php if ($jml_pending > 0): ?>
                <span class="badge bg-danger ms-auto" style="font-size: 10px; padding: 3px 7px; border-radius: 50px;">
                    <?= $jml_pending ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="/siakad/admin/pengaturan.php"
           class="sidebar-nav-link nav-link <?= basename($_SERVER['PHP_SELF']) == 'pengaturan.php' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i>
            <span>Pengaturan</span>
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

