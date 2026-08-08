<?php
session_start();

// Jika sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin')
        header("Location: /siakad/admin/dashboard.php");
    elseif ($_SESSION['role'] == 'guru')
        header("Location: /siakad/guru/dashboard.php");
    else
        header("Location: /siakad/siswa/dashboard.php");
    exit();
}

include 'config/koneksi.php';

// Ambil data pengaturan
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$setting = mysqli_fetch_assoc($query_setting);

// Cek SPMB aktif
$query_spmb = mysqli_query($koneksi, "SELECT spmb_aktif, spmb_tanggal_buka, spmb_tanggal_tutup, spmb_jalur, spmb_syarat FROM pengaturan WHERE id = 1");
$spmb_data = mysqli_fetch_assoc($query_spmb);
$spmb_aktif = $spmb_data['spmb_aktif'] ?? 0;
$spmb_tanggal_buka = $spmb_data['spmb_tanggal_buka'] ?? '';
$spmb_tanggal_tutup = $spmb_data['spmb_tanggal_tutup'] ?? '';

// Ambil berita publik (khusus berita_sekolah; pengumuman tidak tampil di section berita)
$query_berita = mysqli_query($koneksi,
    "SELECT id, judul, ringkasan, isi, kategori, gambar, tanggal
     FROM berita_sekolah
     ORDER BY tanggal DESC LIMIT 6");

// Nama sekolah
$nama_sekolah = "SMA Negeri 4 Palopo";
$alamat_sekolah = $setting['alamat_sekolah'] ?? "Jl. Bakau, Balandai, Kota Palopo, Sulawesi Selatan";
$kepala_sekolah = $setting['nama_kepsek'] ?? "Muzakkir, S.Pd., M.Pd";

// Foto kepala sekolah (kolom 'foto_kepsek' di tabel pengaturan, fallback ke file default)
$foto_kepsek = !empty($setting['foto_kepsek'])
    ? '/siakad/assets/img/' . htmlspecialchars($setting['foto_kepsek'])
    : '/siakad/assets/img/kepala-sekolah.jpg';

// Struktur organisasi (kolom 'foto_struktur' di tabel pengaturan, fallback ke file default)
$foto_struktur = !empty($setting['foto_struktur'])
    ? '/siakad/assets/img/' . htmlspecialchars($setting['foto_struktur'])
    : '/siakad/assets/img/struktur-organisasi.png';

// Sambutan kepala sekolah (auto-add kolom bila belum ada)
$cek_sambutan = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'sambutan_kepsek'");
if ($cek_sambutan && mysqli_num_rows($cek_sambutan) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN sambutan_kepsek TEXT NULL");
    $setting['sambutan_kepsek'] = '';
}
$sambutan_kepsek = trim($setting['sambutan_kepsek'] ?? '');
if ($sambutan_kepsek === '') {
    $sambutan_kepsek = 'Assalamualaikum warahmatullahi wabarakatuh. Selamat datang di website resmi '
        . 'SMA Negeri 4 Palopo. Kami berkomitmen menyelenggarakan pendidikan yang bermutu untuk membentuk '
        . 'generasi yang berkarakter, berakhlak mulia, serta unggul dalam bidang akademik maupun non-akademik. '
        . 'Semoga kehadiran website ini membawa manfaat bagi seluruh warga sekolah dan masyarakat. '
        . 'Wassalamualaikum warahmatullahi wabarakatuh.';
}

// Visi & Misi (dinamis dari pengaturan, fallback ke teks default)
$visi_sekolah = trim($setting['visi'] ?? '');
if ($visi_sekolah === '') {
    $visi_sekolah = 'Menciptakan siswa yang berkarakter, akademik berkompeten, '
        . 'dan berdampak positif bagi masyarakat.';
}
$misi_sekolah = trim($setting['misi'] ?? '');
if ($misi_sekolah === '') {
    $misi_sekolah = 'Menyelenggarakan pembelajaran berbasis teknologi dengan '
        . 'pendekatan inovatif dan inklusif.';
}

// Statistik sekolah (counter di landing)
$total_siswa = 0; $total_guru = 0; $total_kelas = 0; $total_prestasi = 0;
if (($q = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM siswa")) && $r = mysqli_fetch_assoc($q)) $total_siswa = (int)$r['c'];
if (($q = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM guru")) && $r = mysqli_fetch_assoc($q)) $total_guru = (int)$r['c'];
if (($q = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM kelas")) && $r = mysqli_fetch_assoc($q)) $total_kelas = (int)$r['c'];
if (($q = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM prestasi_siswa")) && $r = mysqli_fetch_assoc($q)) $total_prestasi = (int)$r['c'];

// Jalur SPMB dinamis dari tabel spmb_jalur (fallback ke daftar statis bila tabel kosong)
$spmb_jalur_list = [];
if ($res = mysqli_query($koneksi, "SELECT nama_jalur, kuota, keterangan FROM spmb_jalur ORDER BY kuota DESC")) {
    while ($row = mysqli_fetch_assoc($res)) $spmb_jalur_list[] = $row;
}
if (empty($spmb_jalur_list)) {
    $spmb_jalur_list = [
        ['nama_jalur' => 'Zonasi',   'kuota' => 50, 'keterangan' => 'Berdasarkan jarak domisili dari sekolah'],
        ['nama_jalur' => 'Prestasi', 'kuota' => 30, 'keterangan' => 'Berdasarkan prestasi akademik'],
        ['nama_jalur' => 'Afirmasi', 'kuota' => 20, 'keterangan' => 'Jalur khusus untuk siswa dari sekolah tertentu'],
        ['nama_jalur' => 'Reguler',  'kuota' => 40, 'keterangan' => 'Jalur umum untuk semua calon siswa'],
    ];
}
$spmb_jalur_list = array_slice($spmb_jalur_list, 0, 4);

// Prestasi unggulan (tampil di section prestasi)
$query_prestasi = mysqli_query($koneksi,
    "SELECT p.nama_prestasi, p.tingkat, p.kategori, p.tanggal, s.nama AS siswa_nama
     FROM prestasi_siswa p
     LEFT JOIN siswa s ON s.id = p.siswa_id
     ORDER BY p.tanggal DESC, p.id DESC LIMIT 6");

// ==== Helper: scan folder foto di assets/img/ ====
function scan_folder_foto($folder_path) {
    $hasil = [];
    if (is_dir($folder_path)) {
        $files = glob($folder_path . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
        natsort($files);
        foreach ($files as $file) {
            $hasil[] = basename($file);
        }
    }
    return $hasil;
}

// Galeri per kategori — memakai folder yang sudah ada di assets/img/
// Catatan privasi: kategori foto siswa & guru sengaja tidak ditampilkan di galeri publik.
// Hanya foto kepala sekolah (identitas resmi/publik sekolah) yang tetap tampil di section Profil.
$galeri_kategori = [
    'sekolah' => ['label' => 'Sekolah',           'folder' => 'foto_sekolah', 'files' => []],
    'berita'  => ['label' => 'Kegiatan & Berita', 'folder' => 'foto_berita',  'files' => []],
];
foreach ($galeri_kategori as $key => &$kat) {
    $kat['files'] = scan_folder_foto(__DIR__ . '/assets/img/' . $kat['folder'] . '/');
}
unset($kat);

// Dipakai untuk hero slider (kompatibel dengan variabel sebelumnya)
$galeri_foto = $galeri_kategori['sekolah']['files'];

// Foto untuk section Program Unggulan
$foto_program = scan_folder_foto(__DIR__ . '/assets/img/foto_program/');

// Daftar program unggulan (statis — silakan sambungkan ke tabel DB jika sudah tersedia)
$program_list = [
    ['title' => 'Program IPA', 'desc' => 'Peminatan Ilmu Pengetahuan Alam dengan praktikum laboratorium sains yang lengkap.', 'icon' => 'fa-flask'],
    ['title' => 'Program IPS', 'desc' => 'Peminatan Ilmu Pengetahuan Sosial dengan pendekatan analisis dan studi kasus nyata.', 'icon' => 'fa-landmark'],
    ['title' => 'Ekstrakurikuler', 'desc' => 'Beragam kegiatan pengembangan bakat, minat, dan karakter siswa di luar jam pelajaran.', 'icon' => 'fa-futbol'],
];

// Daftar fasilitas sekolah (statis)
$fasilitas_list = [
    ['title' => 'Laboratorium Komputer', 'desc' => 'Ruangan ber-AC dengan perangkat komputer terbaru dan koneksi internet cepat.', 'icon' => 'fa-laptop'],
    ['title' => 'Ruang Ibadah', 'desc' => 'Tempat ibadah yang nyaman dan representatif bagi seluruh warga sekolah.', 'icon' => 'fa-place-of-worship'],
    ['title' => 'Perpustakaan', 'desc' => 'Koleksi buku lengkap dengan ruang baca yang nyaman dan sistem digital.', 'icon' => 'fa-book'],
    ['title' => 'Laboratorium Sains', 'desc' => 'Peralatan praktikum lengkap untuk mata pelajaran fisika, kimia, dan biologi.', 'icon' => 'fa-microscope'],
    ['title' => 'Lapangan Olahraga', 'desc' => 'Lapangan basket, futsal, dan voli dengan standar kompetisi.', 'icon' => 'fa-basketball-ball'],
    ['title' => 'Akses WiFi', 'desc' => 'Akses internet nirkabel kecepatan tinggi di seluruh area sekolah.', 'icon' => 'fa-wifi'],
];

// Gambar utama untuk kartu flip Visi & Misi
$foto_profil_utama = !empty($galeri_foto[0])
    ? '/siakad/assets/img/foto_sekolah/' . htmlspecialchars($galeri_foto[0])
    : '/siakad/assets/img/logo-sekolah.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — <?php echo htmlspecialchars($nama_sekolah); ?></title>
    <link rel="icon" type="image/png" href="/siakad/assets/img/logo-sekolah.png">

    <meta name="description" content="Website resmi <?php echo htmlspecialchars($nama_sekolah); ?>. Informasi profil sekolah, program unggulan, fasilitas, galeri, berita terkini, dan pendaftaran siswa baru (SPMB) online.">
    <meta name="keywords" content="<?php echo htmlspecialchars($nama_sekolah); ?>, SPMB, penerimaan siswa baru, sekolah, Palopo">
    <meta name="theme-color" content="#163A63">
    <link rel="manifest" href="/siakad/manifest.json">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($nama_sekolah); ?> — Sistem Informasi Akademik">
    <meta property="og:description" content="Website resmi <?php echo htmlspecialchars($nama_sekolah); ?>. Informasi akademik, berita, galeri, dan SPMB online.">
    <meta property="og:image" content="/siakad/assets/img/logo-sekolah.png">
    <meta property="og:locale" content="id_ID">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <!-- Stylesheet landing page terpusat (token warna, hero slider, tombol, kartu, filter galeri, dsb) -->
    <link rel="stylesheet" href="/siakad/assets/css/landing.css">

    <style>
        /* Alias variabel & utility lama supaya kelas Tailwind kustom (bg-primary, text-accent, dst.)
           yang dipakai di seluruh halaman tetap konsisten dengan token warna landing.css */
        :root {
            --primary-color: var(--primary, #163A63);
            --secondary-color: var(--primary-dark, #0D2540);
            --accent-color: var(--gold, #F09000);
        }
        body { scroll-behavior: smooth; }

        .bg-primary { background-color: var(--primary-color); }
        .bg-secondary { background-color: var(--secondary-color); }
        .text-accent { color: var(--accent-color); }
        .border-accent { border-color: var(--accent-color); }

        footer { background: var(--secondary-color); }
    </style>
</head>
<body class="text-gray-800">

<!-- NAVBAR -->
<nav class="sticky top-0 z-50 shadow-lg" style="background: linear-gradient(135deg, #0D2540 0%, #163A63 55%, #2C5A8F 100%);">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center">
            <div class="w-12 h-12 mr-3 rounded-xl bg-white flex items-center justify-center p-1 shadow" style="box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                <img src="/siakad/assets/img/logo-sekolah.png" alt="Logo" class="w-full h-full object-contain" loading="lazy">
            </div>
            <div>
                <h1 class="text-lg font-bold text-white leading-tight"><?php echo htmlspecialchars($nama_sekolah); ?></h1>
                <p class="text-xs text-white/75">Sistem Informasi Akademik</p>
            </div>
        </div>

        <div class="hidden md:flex items-center space-x-8">
            <a href="#beranda" class="text-white/90 hover:text-white font-medium">Beranda</a>
            <a href="#profil" class="text-white/90 hover:text-white font-medium">Profil</a>
            <a href="#program" class="text-white/90 hover:text-white font-medium">Program</a>
            <a href="#fasilitas" class="text-white/90 hover:text-white font-medium">Fasilitas</a>
            <a href="#galeri" class="text-white/90 hover:text-white font-medium">Galeri</a>
            <a href="#prestasi" class="text-white/90 hover:text-white font-medium">Prestasi</a>
            <a href="#berita" class="text-white/90 hover:text-white font-medium">Berita</a>
            <?php if ($spmb_aktif == 1): ?>
            <a href="#spmb" class="text-white/90 hover:text-white font-medium">SPMB</a>
            <?php endif; ?>
            <a href="#kontak" class="text-white/90 hover:text-white font-medium">Kontak</a>
            <a href="/siakad/auth/login.php" class="btn-primary px-5 py-2 rounded-full font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>Login SIAKAD
            </a>
        </div>

        <button id="menu-toggle" class="md:hidden text-white focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <div id="mobile-menu" class="md:hidden hidden px-4 pb-4">
        <a href="#beranda" class="block py-2 text-white/90 hover:text-white font-medium">Beranda</a>
        <a href="#profil" class="block py-2 text-white/90 hover:text-white font-medium">Profil</a>
        <a href="#program" class="block py-2 text-white/90 hover:text-white font-medium">Program</a>
        <a href="#fasilitas" class="block py-2 text-white/90 hover:text-white font-medium">Fasilitas</a>
        <a href="#galeri" class="block py-2 text-white/90 hover:text-white font-medium">Galeri</a>
        <a href="#prestasi" class="block py-2 text-white/90 hover:text-white font-medium">Prestasi</a>
        <a href="#berita" class="block py-2 text-white/90 hover:text-white font-medium">Berita</a>
        <?php if ($spmb_aktif == 1): ?>
        <a href="#spmb" class="block py-2 text-white/90 hover:text-white font-medium">SPMB</a>
        <?php endif; ?>
        <a href="#kontak" class="block py-2 text-white/90 hover:text-white font-medium">Kontak</a>
        <a href="/siakad/auth/login.php" class="block mt-2 btn-primary text-center px-5 py-2 rounded-full font-medium">
            <i class="fas fa-sign-in-alt mr-2"></i>Login SIAKAD
        </a>
    </div>
</nav>

<!-- HERO SLIDER -->
<section id="beranda" class="hero-slider">
    <?php if (!empty($galeri_foto)): ?>
        <?php foreach (array_slice($galeri_foto, 0, 5) as $i => $foto): ?>
        <div class="slide <?php echo $i === 0 ? 'active' : ''; ?>">
            <img src="/siakad/assets/img/foto_sekolah/<?php echo htmlspecialchars($foto); ?>"
                 alt="Foto Sekolah <?php echo htmlspecialchars($nama_sekolah); ?>" class="w-full h-full object-cover" loading="lazy">
            <div class="slide-content">
                <div class="container mx-auto">
                    <h2 class="text-3xl md:text-4xl font-bold mb-3"><?php echo htmlspecialchars($nama_sekolah); ?></h2>
                    <p class="text-lg md:text-xl mb-6">Sistem Informasi Akademik Terpadu</p>
                    <div class="flex flex-wrap gap-3">
                        <?php if ($spmb_aktif == 1): ?>
                        <a href="/siakad/spmb/daftar.php" class="btn-primary px-6 py-3 rounded-full font-medium inline-block">
                            <i class="fas fa-user-plus mr-2"></i>Daftar SPMB
                        </a>
                        <?php endif; ?>
                        <a href="/siakad/auth/login.php" class="btn-outline px-6 py-3 rounded-full font-medium inline-block">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login SIAKAD
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="nav-dots">
            <?php foreach (array_slice($galeri_foto, 0, 5) as $i => $foto): ?>
            <div class="nav-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="slide active bg-primary flex items-center">
            <div class="slide-content" style="position: relative; background: none;">
                <div class="container mx-auto text-center">
                    <img src="/siakad/assets/img/logo-sekolah.png" alt="Logo" class="w-24 h-24 mx-auto mb-4" loading="lazy">
                    <h2 class="text-3xl md:text-4xl font-bold mb-3"><?php echo htmlspecialchars($nama_sekolah); ?></h2>
                    <p class="text-lg md:text-xl mb-6">Sistem Informasi Akademik Terpadu</p>
                    <p class="text-sm text-gray-200">Belum ada foto di folder assets/img/foto_sekolah.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- STATISTIK SEKOLAH -->
<section class="stat-section py-14" style="background: linear-gradient(135deg, #0D2540 0%, #163A63 55%, #2C5A8F 100%);">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="stat-card-item text-center" data-aos="zoom-in">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value" data-target="<?php echo $total_siswa; ?>">0</div>
                <div class="stat-label">Siswa Aktif</div>
            </div>
            <div class="stat-card-item text-center" data-aos="zoom-in" data-aos-delay="100">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value" data-target="<?php echo $total_guru; ?>">0</div>
                <div class="stat-label">Guru</div>
            </div>
            <div class="stat-card-item text-center" data-aos="zoom-in" data-aos-delay="200">
                <div class="stat-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-value" data-target="<?php echo $total_kelas; ?>">0</div>
                <div class="stat-label">Kelas</div>
            </div>
            <div class="stat-card-item text-center" data-aos="zoom-in" data-aos-delay="300">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-value" data-target="<?php echo $total_prestasi; ?>">0</div>
                <div class="stat-label">Prestasi</div>
            </div>
        </div>
    </div>
</section>

<!-- SPMB BANNER (Conditional) -->
<?php if ($spmb_aktif == 1): ?>
<section id="spmb" class="bg-primary py-16">
    <div class="container mx-auto px-4 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold" data-aos="fade-up">
            <i class="fas fa-graduation-cap mr-3"></i>Pendaftaran SPMB Online
        </h2>
        <p class="mt-3 text-white/90 max-w-2xl mx-auto">
            Daftar sebagai calon siswa baru melalui sistem pendaftaran online kami.
        </p>

        <?php if ($spmb_tanggal_buka && $spmb_tanggal_tutup): ?>
        <div class="inline-flex items-center mt-6 bg-white/10 px-5 py-2 rounded-full text-sm">
            <i class="fas fa-calendar-alt mr-2"></i>
            Periode: <?php echo tanggal_indo($spmb_tanggal_buka); ?> —
            <?php echo tanggal_indo($spmb_tanggal_tutup); ?>
        </div>
        <!-- Countdown -->
        <div id="spmb-countdown" class="mt-6" data-tutup="<?php echo $spmb_tanggal_tutup; ?>">
            <div class="text-white/80 text-sm font-medium mb-3"><i class="fas fa-hourglass-half mr-2"></i>Sisa waktu pendaftaran:</div>
            <div class="flex justify-center gap-4" id="spmb-countdown-box">
                <div class="countdown-unit"><span class="cd-num" id="cd-hari">0</span><span class="cd-lbl">Hari</span></div>
                <div class="countdown-unit"><span class="cd-num" id="cd-jam">0</span><span class="cd-lbl">Jam</span></div>
                <div class="countdown-unit"><span class="cd-num" id="cd-menit">0</span><span class="cd-lbl">Menit</span></div>
                <div class="countdown-unit"><span class="cd-num" id="cd-detik">0</span><span class="cd-lbl">Detik</span></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto mt-10">
            <?php foreach ($spmb_jalur_list as $ji => $jalur): ?>
            <div class="bg-white/10 rounded-xl p-5 border border-white/10 transition-all duration-300 hover:bg-white/15 hover:-translate-y-1" data-aos="zoom-in" data-aos-delay="<?php echo $ji * 100; ?>">
                <i class="fas fa-book-reader text-3xl mb-2 text-accent"></i>
                <div class="font-semibold text-sm">Jalur <?php echo htmlspecialchars($jalur['nama_jalur']); ?></div>
                <div class="mt-1 text-xs text-white/70">
                    Kuota: <?php echo (int)$jalur['kuota']; ?> siswa
                </div>
                <div class="mt-1 text-xs text-white/60 line-clamp-2"><?php echo htmlspecialchars($jalur['keterangan'] ?? ''); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="/siakad/spmb/daftar.php" class="bg-gold text-primary px-6 py-3 rounded-full font-medium">
                <i class="fas fa-arrow-right mr-2"></i>Daftar Sekarang
            </a>
            <a href="/siakad/spmb/cek-status.php" class="btn-outline px-6 py-3 rounded-full font-medium">
                <i class="fas fa-search mr-2"></i>Cek Status
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- PROFIL SEKOLAH -->
<section id="profil" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Tentang Sekolah Kami</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                <?php echo htmlspecialchars($nama_sekolah); ?> memiliki akreditasi A berdasarkan SK No. 614/BAN-SM/SK/2019, 
                dan berkomitmen untuk memberikan pendidikan berkualitas serta menoreh prestasi gemilang. 
                Sekolah ini menyelenggarakan pendidikan selama 5 hari dalam seminggu dengan sistem sehari penuh.
            </p>
        </div>

        <div class="flex flex-col md:flex-row gap-10 items-center mb-14">
            <div class="w-full md:w-1/2 flex justify-center" data-aos="fade-right">
                <div class="flip-perspective relative w-full h-[300px] sm:h-[350px] md:h-[400px]">
                    <div class="flip-inner relative w-full h-full">
                        <div class="flip-front">
                            <img src="<?php echo $foto_profil_utama; ?>" alt="Foto Sekolah" class="w-full h-full object-cover"
                                 onerror="this.src='/siakad/assets/img/logo-sekolah.png';this.classList.add('object-contain','p-10','bg-primary');">
                        </div>
                        <div class="flip-back bg-primary flex items-center justify-center p-6">
                            <p class="text-white font-semibold text-xl text-center leading-relaxed">
                                <?php echo htmlspecialchars($nama_sekolah); ?><br>
                                Pendidikan berkualitas untuk generasi masa depan
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/2" data-aos="fade-left">
                <h3 class="text-2xl font-bold text-gray-800 mb-3"><i class="fas fa-lightbulb text-accent mr-2"></i>Visi</h3>
                <p class="text-gray-700 mb-6"><?php echo nl2br(htmlspecialchars($visi_sekolah)); ?></p>

                <h3 class="text-2xl font-bold text-gray-800 mb-3"><i class="fas fa-bullseye text-accent mr-2"></i>Misi</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($misi_sekolah)); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover transition-transform duration-300 hover:-translate-y-2" data-aos="zoom-in">
                <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Akreditasi</h3>
                <p class="text-gray-600 text-sm">Terakreditasi A berdasarkan SK No. 614/BAN-SM/SK/2019.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover transition-transform duration-300 hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="200">
                <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Fasilitas Lengkap</h3>
                <p class="text-gray-600 text-sm">Laboratorium, ruang kelas modern, perpustakaan digital, dan berbagai fasilitas penunjang belajar.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 text-center card-hover transition-transform duration-300 hover:-translate-y-2" data-aos="zoom-in" data-aos-delay="300">
                <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Komunitas</h3>
                <p class="text-gray-600 text-sm">Ekstrakulikuler, organisasi siswa, dan kegiatan pengembangan karakter berkelanjutan.</p>
            </div>
        </div>

        <!-- Sambutan Kepala Sekolah -->
        <div class="mt-14 rounded-3xl overflow-hidden shadow-xl border border-gray-100" data-aos="fade-up">
            <div class="grid grid-cols-1 md:grid-cols-5">
                <!-- Panel Foto -->
                <div class="md:col-span-2 flex flex-col items-center justify-center p-10 relative"
                     style="background: linear-gradient(135deg, #0D2540 0%, #163A63 55%, #2C5A8F 100%);">
                    <i class="fas fa-quote-right absolute top-6 left-7 text-white/10" style="font-size:72px;"></i>
                    <div class="relative z-10 w-40 h-40 md:w-44 md:h-44 rounded-full p-2"
                         style="background:rgba(255,255,255,0.15); box-shadow:0 0 0 4px rgba(240,144,0,0.55), 0 18px 40px rgba(0,0,0,0.35);">
                        <img src="<?php echo $foto_kepsek; ?>" alt="Kepala Sekolah <?php echo htmlspecialchars($kepala_sekolah); ?>"
                             class="w-full h-full rounded-full object-cover" loading="lazy"
                             onerror="this.onerror=null;this.src='/siakad/assets/img/logo-sekolah.png';this.classList.add('object-contain','p-4','bg-white');">
                    </div>
                    <div class="relative z-10 text-center mt-6">
                        <div class="text-white font-bold text-lg leading-tight"><?php echo htmlspecialchars($kepala_sekolah); ?></div>
                        <div class="mt-2 inline-flex items-center gap-2 px-4 py-1 rounded-full text-xs font-semibold tracking-widest"
                             style="background:rgba(240,144,0,0.18); color:#FFB74D;">
                            <i class="fas fa-user-tie"></i> KEPALA SEKOLAH
                        </div>
                        <?php if (!empty($setting['nip_kepsek'])): ?>
                        <div class="text-white/70 text-xs mt-2">NIP. <?php echo htmlspecialchars($setting['nip_kepsek']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Panel Sambutan -->
                <div class="md:col-span-3 bg-white p-8 md:p-12">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-quote-left text-2xl" style="color:#F09000;"></i>
                        <span class="text-xs font-semibold tracking-widest uppercase" style="color:#F09000;">Sambutan</span>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Kepala Sekolah</h3>
                    <div class="w-16 h-1 rounded-full mb-5" style="background:linear-gradient(90deg,#F09000,#FFB74D);"></div>
                    <p class="text-gray-600 leading-relaxed text-justify text-[15px]"><?php echo nl2br(htmlspecialchars($sambutan_kepsek)); ?></p>
                </div>
            </div>
        </div>

        <!-- Struktur Organisasi -->
        <div class="mt-14" data-aos="fade-up">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-800">Struktur Organisasi Sekolah</h3>
                <div class="section-divider"></div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 max-w-4xl mx-auto">
                <img src="<?php echo $foto_struktur; ?>" alt="Struktur Organisasi Sekolah" class="w-full rounded-lg" loading="lazy"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <p style="display:none;" class="text-center text-gray-400 py-10">
                    Struktur organisasi belum diupload. Simpan gambar dengan nama
                    <code>struktur-organisasi.png</code> di folder <code>assets/img/</code>.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- PROGRAM UNGGULAN -->
<section id="program" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Program Unggulan</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Berbagai program unggulan yang kami tawarkan untuk pengembangan potensi siswa.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($program_list as $i => $prog): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl border border-transparent hover:border-accent/40"
                 data-aos="zoom-in" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="h-48 overflow-hidden bg-primary/5 flex items-center justify-center">
                    <?php if (!empty($foto_program[$i])): ?>
                    <img src="/siakad/assets/img/foto_program/<?php echo htmlspecialchars($foto_program[$i]); ?>"
                         alt="<?php echo htmlspecialchars($prog['title']); ?>" class="w-full h-full object-cover" loading="lazy">
                    <?php else: ?>
                    <i class="fas <?php echo $prog['icon']; ?> text-5xl text-primary/40"></i>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($prog['title']); ?></h3>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($prog['desc']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FASILITAS -->
<section id="fasilitas" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Fasilitas Sekolah</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Fasilitas modern dan lengkap untuk mendukung proses pembelajaran.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($fasilitas_list as $i => $fas): ?>
            <div class="bg-white rounded-lg p-6 shadow-md flex items-start transition-transform transform hover:-translate-y-2 hover:shadow-xl duration-300"
                 data-aos="zoom-in-up" data-aos-delay="<?php echo $i * 80; ?>">
                <div class="text-primary text-3xl mr-4"><i class="fas <?php echo $fas['icon']; ?>"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($fas['title']); ?></h3>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($fas['desc']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- GALERI (dengan filter kategori dari folder foto_*) -->
<section id="galeri" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Galeri Sekolah</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Dokumentasi suasana lingkungan dan kegiatan di <?php echo htmlspecialchars($nama_sekolah); ?>.
            </p>
        </div>

        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <button class="filter-btn active px-5 py-2 rounded-full border border-gray-200 font-medium text-sm" data-filter="all">Semua</button>
            <?php foreach ($galeri_kategori as $key => $kat): ?>
            <button class="filter-btn px-5 py-2 rounded-full border border-gray-200 font-medium text-sm" data-filter="<?php echo $key; ?>">
                <?php echo htmlspecialchars($kat['label']); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="galeri-grid">
            <?php
            $ada_foto = false;
            foreach ($galeri_kategori as $key => $kat):
                foreach ($kat['files'] as $foto):
                    $ada_foto = true;
            ?>
            <div class="gallery-item overflow-hidden rounded-lg cursor-pointer" data-category="<?php echo $key; ?>"
                     data-src="/siakad/assets/img/<?php echo $kat['folder']; ?>/<?php echo htmlspecialchars($foto); ?>"
                     data-caption="<?php echo htmlspecialchars($kat['label']); ?>" onclick="bukaLightbox(this)">
                <img src="/siakad/assets/img/<?php echo $kat['folder']; ?>/<?php echo htmlspecialchars($foto); ?>"
                     alt="<?php echo htmlspecialchars($kat['label']); ?>"
                     class="w-full h-48 object-cover hover:scale-110 transition duration-300" loading="lazy">
            </div>
            <?php
                endforeach;
            endforeach;
            ?>
            <?php if (!$ada_foto): ?>
            <div class="col-span-full text-center py-16">
                <i class="fas fa-images fa-2x text-gray-300 mb-3"></i>
                <p class="text-gray-400">Belum ada foto pada folder galeri.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Lightbox Galeri -->
<div id="lightbox" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-black/90" role="dialog" aria-modal="true">
    <button class="lightbox-close absolute top-5 right-5 text-white/80 hover:text-white text-3xl" onclick="tutupLightbox()" aria-label="Tutup">
        <i class="fas fa-times"></i>
    </button>
    <figure class="max-w-4xl w-full text-center" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" alt="" class="max-h-[80vh] w-auto mx-auto rounded-lg shadow-2xl">
        <figcaption id="lightbox-caption" class="mt-4 text-white/90 text-sm font-medium"></figcaption>
    </figure>
</div>

<!-- PRESTASI -->
<section id="prestasi" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Prestasi Siswa</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Kebanggaan <?php echo htmlspecialchars($nama_sekolah); ?> atas capaian prestasi siswa di berbagai bidang.
            </p>
        </div>

        <?php if ($query_prestasi && mysqli_num_rows($query_prestasi) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($prestasi = mysqli_fetch_assoc($query_prestasi)): ?>
            <div class="bg-white rounded-xl shadow-md p-6 card-hover transition-transform duration-300 hover:-translate-y-2" data-aos="zoom-in">
                <div class="flex items-start">
                    <div class="w-12 h-12 rounded-full <?php echo $prestasi['kategori'] === 'Akademik' ? 'bg-primary/10 text-primary' : 'bg-accent/10 text-accent'; ?> flex items-center justify-center text-xl mr-4 shrink-0">
                        <i class="fas <?php echo $prestasi['kategori'] === 'Akademik' ? 'fa-award' : 'fa-medal'; ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($prestasi['nama_prestasi']); ?></h3>
                        <div class="flex flex-wrap gap-2 mb-2 text-xs">
                            <span class="px-2.5 py-1 rounded-full font-semibold" style="background:rgba(240,144,0,0.12); color:#C97000;">
                                <i class="fas fa-database mr-1"></i><?php echo htmlspecialchars($prestasi['tingkat'] ?? 'Sekolah'); ?>
                            </span>
                            <span class="px-2.5 py-1 rounded-full font-semibold"
                                  style="<?php echo $prestasi['kategori'] === 'Akademik' ? 'background:rgba(22,58,99,0.10); color:#163A63;' : 'background:rgba(16,185,129,0.12); color:#0f9d76;'; ?>">
                                <?php echo htmlspecialchars($prestasi['kategori'] ?? 'Akademik'); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            <?php if (!empty($prestasi['siswa_nama'])): ?>
                            <i class="fas fa-user-graduate mr-1"></i><?php echo htmlspecialchars($prestasi['siswa_nama']); ?>
                            <?php endif; ?>
                            <?php if (!empty($prestasi['tanggal'])): ?>
                            <span class="text-gray-400">• <?php echo tanggal_indo($prestasi['tanggal']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-14">
            <i class="fas fa-trophy fa-3x text-gray-300 mb-4"></i>
            <p class="text-gray-400">Belum ada data prestasi yang tercatat.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- BERITA -->
<section id="berita" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Berita & Pengumuman</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Informasi terkini seputar kegiatan akademik dan pengumuman penting.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            if ($query_berita && mysqli_num_rows($query_berita) > 0):
                while ($berita = mysqli_fetch_assoc($query_berita)):
                    $berita_gambar = null;
                    if (!empty($berita['gambar']) && file_exists(__DIR__ . '/assets/img/foto_berita/' . $berita['gambar'])) {
                        $berita_gambar = '/siakad/assets/img/foto_berita/' . htmlspecialchars($berita['gambar']);
                    }
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden cursor-pointer transition-transform duration-300 hover:-translate-y-2 hover:shadow-xl"
                 data-aos="fade-up"
                 onclick="bukaBerita(this)"
                 data-judul="<?php echo htmlspecialchars($berita['judul'], ENT_QUOTES); ?>"
                 data-tanggal="<?php echo htmlspecialchars(tanggal_indo_pendek($berita['tanggal']), ENT_QUOTES); ?>"
                 data-ringkasan="<?php echo htmlspecialchars($berita['ringkasan'] ?? '', ENT_QUOTES); ?>"
                 data-isi="<?php echo htmlspecialchars($berita['isi'], ENT_QUOTES); ?>"
                 data-gambar="<?php echo $berita_gambar ? htmlspecialchars($berita_gambar, ENT_QUOTES) : ''; ?>">
                <div class="h-44 overflow-hidden bg-primary/5 flex items-center justify-center">
                    <?php if ($berita_gambar): ?>
                    <img src="<?php echo $berita_gambar; ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="w-full h-full object-cover" loading="lazy">
                    <?php else: ?>
                    <i class="fas fa-newspaper text-4xl text-primary/40"></i>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <div class="text-xs text-gray-400 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo tanggal_indo_pendek($berita['tanggal']); ?>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($berita['judul']); ?></h4>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars(mb_substr($berita['ringkasan'] ?? $berita['isi'], 0, 100)); ?>...</p>
                    <span class="inline-block mt-3 text-sm font-medium text-accent hover:text-accent-dark">
                        Baca selengkapnya <i class="fas fa-arrow-right ml-1"></i>
                    </span>
                </div>
            </div>
            <?php
                endwhile;
            else:
            ?>
            <div class="col-span-full text-center py-10">
                <p class="text-gray-400">Belum ada berita terbaru.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal Berita -->
<div id="modalBerita" class="modal-berita fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="tutupBerita()"></div>
    <div class="modal-panel relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[85vh] overflow-hidden flex flex-col">
        <div class="relative shrink-0">
            <div id="modalGambar" class="hidden h-60 md:h-72 shrink-0 bg-gradient-to-br from-primary to-primary-light relative">
                <img id="modalImg" src="" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/10"></div>
            </div>
            <button onclick="tutupBerita()" aria-label="Tutup"
                class="modal-close absolute top-4 right-4 w-9 h-9 rounded-full bg-white/20 backdrop-blur-md hover:bg-white/40 text-white text-lg flex items-center justify-center border border-white/30">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalBadge" class="absolute top-4 left-4 hidden items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/90 text-primary text-xs font-bold shadow"></div>
        </div>
        <div class="flex items-center justify-between px-6 md:px-8 py-4 border-b border-gray-100 shrink-0 bg-white">
            <h3 id="modalJudul" class="text-lg md:text-xl font-bold text-gray-800 pr-8 leading-snug"></h3>
        </div>
        <div class="overflow-y-auto px-6 md:px-8 py-6 bg-gray-50/50">
            <div id="modalTanggal" class="inline-flex items-center text-xs text-gray-400 mb-4"></div>
            <p id="modalRingkasan" class="text-gray-500 text-sm md:text-base font-medium mb-4 border-l-4 border-accent/60 pl-3"></p>
            <div id="modalIsi" class="text-gray-700 text-sm md:text-base leading-relaxed whitespace-pre-line"></div>
            <div class="mt-6 pt-4 border-t border-gray-200 flex items-center justify-between">
                <span class="text-xs text-gray-400"><i class="fas fa-newspaper mr-1"></i> Berita Sekolah</span>
                <button onclick="tutupBerita()" class="text-xs font-bold text-accent hover:text-accent-dark">
                    Tutup <i class="fas fa-times ml-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var escHandler = function(e) {
        if (e.key === 'Escape') tutupBerita();
    };

    function showPanel() {
        var p = document.querySelector('#modalBerita .modal-panel');
        var o = document.getElementById('modalBerita');
        o.classList.remove('hidden');
        void o.offsetWidth;
        o.classList.add('flex');
        p.classList.add('modal-enter');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', escHandler);
    }
    window.addEventListener('keydown', escHandler);

    window.bukaBerita = function(el) {
        var judul = el.dataset.judul || '';
        var tgl   = el.dataset.tanggal || '';
        var ring  = el.dataset.ringkasan || '';
        var isi   = el.dataset.isi || '';
        var img   = el.dataset.gambar || '';

        document.getElementById('modalJudul').textContent = judul;

        var tb = document.getElementById('modalBadge');
        tb.innerHTML = '<i class="fas fa-calendar-alt"></i> ' + tgl;
        tb.classList.remove('hidden');
        tb.classList.add('inline-flex');

        var g = document.getElementById('modalGambar');
        var ig = document.getElementById('modalImg');
        if (img) {
            g.classList.remove('hidden');
            ig.src = img;
            ig.alt = judul;
        } else {
            g.classList.add('hidden');
            ig.src = '';
        }

        var r = document.getElementById('modalRingkasan');
        if (ring) {
            r.textContent = ring;
            r.classList.remove('hidden');
        } else {
            r.textContent = '';
            r.classList.add('hidden');
        }
        document.getElementById('modalIsi').textContent = isi;

        showPanel();
    };

    window.tutupBerita = function() {
        var m = document.getElementById('modalBerita');
        var p = m.querySelector('.modal-panel');
        p.classList.remove('modal-enter');
        p.classList.add('modal-exit');
        setTimeout(function() {
            m.classList.add('hidden');
            m.classList.remove('flex');
            p.classList.remove('modal-exit');
            document.body.style.overflow = '';
            document.removeEventListener('keydown', escHandler);
        }, 180);
    };
})();
</script>

<!-- KONTAK -->
<section id="kontak" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Hubungi Kami</h2>
            <div class="section-divider"></div>
            <p class="text-gray-600 max-w-3xl mx-auto">
                Kami siap membantu menjawab pertanyaan Anda tentang pendaftaran dan akademik.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-1/2 space-y-6">
                <div class="bg-gray-50 p-8 rounded-lg shadow-md">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Informasi Kontak</h3>
                    <div class="space-y-5">
                        <div class="flex items-start">
                            <div class="text-primary text-xl mr-4 mt-1"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <h4 class="font-bold text-gray-800">Alamat</h4>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($alamat_sekolah); ?></p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="text-primary text-xl mr-4 mt-1"><i class="fas fa-phone-alt"></i></div>
                            <div>
                                <h4 class="font-bold text-gray-800">Telepon</h4>
                                <p class="text-gray-600 text-sm">(0471) 324567 / (0471) 328901</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="text-primary text-xl mr-4 mt-1"><i class="fas fa-envelope"></i></div>
                            <div>
                                <h4 class="font-bold text-gray-800">Email</h4>
                                <p class="text-gray-600 text-sm">sman04plp@gmail.com</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="text-primary text-xl mr-4 mt-1"><i class="fab fa-whatsapp"></i></div>
                            <div>
                                <h4 class="font-bold text-gray-800">WhatsApp</h4>
                                <p class="text-gray-600 text-sm">+62 812-3456-7890</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="text-primary text-xl mr-4 mt-1"><i class="fas fa-clock"></i></div>
                            <div>
                                <h4 class="font-bold text-gray-800">Jam Operasional</h4>
                                <p class="text-gray-600 text-sm">Senin - Jumat: 07:00 - 16:00 WIB<br>Sabtu: 07:00 - 13:00 WIB</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:w-1/2">
                <div class="bg-gray-50 rounded-lg shadow-md overflow-hidden h-full min-h-[380px]">
                   <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3984.4670449493487!2d120.18244617497051!3d-2.967824897008311!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2d915fc11a64e9e5%3A0xad87d93a44505aa8!2sSMA%20Negeri%204%20Palopo!5e0!3m2!1sid!2sid!4v1785961580243!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="text-white py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <h3 class="text-xl font-bold mb-4"><i class="fas fa-graduation-cap mr-2"></i><?php echo htmlspecialchars($nama_sekolah); ?></h3>
                <p class="text-gray-300 mb-4 max-w-md">
                    Institusi pendidikan berkualitas dengan komitmen mengembangkan potensi siswa secara optimal.
                </p>
                <div class="flex space-x-4">
                    <a href="https://facebook.com" target="_blank" class="text-gray-300 hover:text-accent text-xl"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://instagram.com" target="_blank" class="text-gray-300 hover:text-accent text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="https://twitter.com" target="_blank" class="text-gray-300 hover:text-accent text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="https://youtube.com" target="_blank" class="text-gray-300 hover:text-accent text-xl"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4">Menu Utama</h3>
                <ul class="space-y-2 text-gray-300 text-sm">
                    <li><a href="#beranda" class="hover:text-accent">Beranda</a></li>
                    <li><a href="#profil" class="hover:text-accent">Profil Sekolah</a></li>
                    <li><a href="#galeri" class="hover:text-accent">Galeri</a></li>
                    <li><a href="#berita" class="hover:text-accent">Berita</a></li>
                    <?php if ($spmb_aktif == 1): ?>
                    <li><a href="#spmb" class="hover:text-accent">Pendaftaran SPMB</a></li>
                    <?php endif; ?>
                    <li><a href="#kontak" class="hover:text-accent">Kontak</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4">Layanan</h3>
                <ul class="space-y-2 text-gray-300 text-sm">
                    <li><a href="/siakad/auth/login.php" class="hover:text-accent">Login SIAKAD</a></li>
                    <?php if ($spmb_aktif == 1): ?>
                    <li><a href="/siakad/spmb/daftar.php" class="hover:text-accent">Daftar SPMB</a></li>
                    <li><a href="/siakad/spmb/cek-status.php" class="hover:text-accent">Cek Status</a></li>
                    <?php endif; ?>
                    <li><a href="#kontak" class="hover:text-accent">Hubungi Kami</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10 mt-10 pt-6 text-center text-gray-400 text-sm">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nama_sekolah); ?>. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button id="backToTop" class="fixed right-8 bottom-24 bg-primary text-white p-3 rounded-full shadow-lg opacity-0 invisible transition-all duration-300 z-40">
    <i class="fas fa-arrow-up"></i>
</button>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init({ duration: 900, once: true });

    // Mobile menu toggle
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // Hero slider
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.nav-dot');
    let currentSlide = 0;
    function showSlide(n) {
        if (!slides.length) return;
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }
    dots.forEach((dot, index) => dot.addEventListener('click', () => showSlide(index)));
    if (slides.length > 1) {
        setInterval(() => showSlide(currentSlide + 1), 5000);
    }

    // Galeri filter
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;
            galleryItems.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.classList.remove('hidden-item');
                } else {
                    item.classList.add('hidden-item');
                }
            });
        });
    });

    // Back to top
    const backToTopBtn = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) backToTopBtn.classList.remove('opacity-0', 'invisible');
        else backToTopBtn.classList.add('opacity-0', 'invisible');
    });
    backToTopBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    // Galeri lightbox
    window.bukaLightbox = function (el) {
        var img = document.getElementById('lightbox-img');
        img.src = el.dataset.src || '';
        img.alt = el.dataset.caption || '';
        document.getElementById('lightbox-caption').textContent = el.dataset.caption || '';
        var lb = document.getElementById('lightbox');
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };
    window.tutupLightbox = function () {
        var lb = document.getElementById('lightbox');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
    };
    document.getElementById('lightbox').addEventListener('click', function (e) {
        if (e.target === this) tutupLightbox();
    });

    // Statistik counter (animasi saat section masuk viewport)
    function animateCounter(el) {
        var target = parseInt(el.dataset.target || '0', 10);
        if (target === 0) return;
        var dur = 1200, start = null;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(eased * target).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(step);
    }
    var statSection = document.querySelector('.stat-section');
    if (statSection && 'IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.querySelectorAll('.stat-value').forEach(animateCounter);
                    obs.disconnect();
                }
            });
        }, { threshold: 0.3 });
        obs.observe(statSection);
    }

    // Countdown SPMB
    var cdBox = document.getElementById('spmb-countdown');
    if (cdBox) {
        var tutup = new Date(cdBox.dataset.tutup + 'T23:59:59');
        function tickCountdown() {
            var now = new Date();
            var diff = tutup - now;
            if (diff <= 0) {
                cdBox.innerHTML = '<div class="text-white/90 font-semibold"><i class="fas fa-times-circle mr-2"></i>Pendaftaran telah ditutup.</div>';
                return;
            }
            var hari = Math.floor(diff / 86400000);
            var jam = Math.floor(diff % 86400000 / 3600000);
            var mnt = Math.floor(diff % 3600000 / 60000);
            var dtk = Math.floor(diff % 60000 / 1000);
            document.getElementById('cd-hari').textContent = hari;
            document.getElementById('cd-jam').textContent = jam;
            document.getElementById('cd-menit').textContent = mnt;
            document.getElementById('cd-detik').textContent = dtk;
        }
        tickCountdown();
        setInterval(tickCountdown, 1000);
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('mobile-menu').classList.add('hidden');
            }
        });
    });
</script>

<!-- ===== Chatbot AI (SiA Bot) ===== -->
<link rel="stylesheet" href="/siakad/assets/css/chatbot.css?v=3">
<button type="button" class="chatbot-fab" id="chatbotToggle" aria-label="Buka chatbot">
    <i class="fas fa-comments fab-open"></i>
    <i class="fas fa-times fab-close"></i>
</button>

<div class="chatbot-panel" id="chatbotPanel" role="dialog" aria-label="Chatbot SiA Bot">
    <div class="chatbot-header">
        <div class="bot-avatar"><i class="fas fa-robot"></i></div>
        <div class="bot-info">
            <h6>SiA Bot</h6>
            <small>Asisten Virtual SMA Negeri 4 Palopo</small>
        </div>
    </div>

    <div class="chat-quick-chips" id="chatbotQuickChips">
        <span class="chat-chip" data-q="Bagaimana cara daftar SPMB?">Daftar SPMB</span>
        <span class="chat-chip" data-q="Bagaimana cara cek status SPMB?">Cek status SPMB</span>
        <span class="chat-chip" data-q="Berapa jam belajar dimulai?">Jam belajar</span>
        <span class="chat-chip" data-q="Bagaimana cara melihat rapor?">Lihat rapor</span>
        <span class="chat-chip" data-q="Siapa kepala sekolah sekarang?">Kepala sekolah</span>
    </div>

    <div class="chatbot-messages" id="chatbotMessages">
        <div id="chatbotAnchor"></div>
    </div>

    <div class="chat-typing" id="chatbotTyping">
        <div class="chat-bubble">
            <div class="chat-avatar"><i class="fas fa-robot"></i></div>
            <div class="chat-text">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        </div>
    </div>

    <form class="chatbot-form" id="chatbotForm" autocomplete="off">
        <input type="text" class="chatbot-input" id="chatbotInput"
               placeholder="Ketik pertanyaanmu..." maxlength="2000">
        <button type="submit" class="chat-send-btn" aria-label="Kirim">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>
</div>

<script>window.SIA_CHAT_USER = 'guest';</script>
<script src="/siakad/assets/js/chatbot.js"></script>
</body>
</html>