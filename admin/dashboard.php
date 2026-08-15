<?php
include '../config/koneksi.php';
include '../config/session.php';
include '../config/helper_tahun_ajaran.php';
cekAdmin();
$title = "Dashboard Admin";

// tahun ajaran aktif sebagai fallback (dari db, bukan hardcode)
$fallbackTahun = null;
try {
    $fallbackTahun = getTahunAjaranAktif(tahun_ajaran_pdo())['tahun'];
} catch (Throwable $e) {
    $fallbackTahun = null;
}

// auto-buat tabel mapel kalo belum ada di db
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS mapel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_mapel VARCHAR(20) NOT NULL,
    nama_mapel VARCHAR(100) NOT NULL
)");

// auto-buat tabel pengumuman kalo belum ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengumuman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    admin_id INT NULL,
    user_id INT NULL,
    tanggal DATE NOT NULL
)");

// auto-buat tabel agenda kalo belum ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS agenda (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    jam_mulai VARCHAR(10) NOT NULL,
    jam_selesai VARCHAR(10) NOT NULL,
    deskripsi VARCHAR(255) DEFAULT NULL,
    status_label VARCHAR(20) DEFAULT 'Terjadwal',
    urutan INT DEFAULT 0,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NULL,
    kategori ENUM('siswa','guru','semua') NOT NULL DEFAULT 'semua',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ambil total master buat counter box (error handling minimal biar aman)
$siswa_query = mysqli_query($koneksi, "SELECT id FROM siswa");
$siswa = $siswa_query ? mysqli_num_rows($siswa_query) : 0;

$guru_query = mysqli_query($koneksi, "SELECT id FROM guru");
$guru = $guru_query ? mysqli_num_rows($guru_query) : 0;

$kelas_query = mysqli_query($koneksi, "SELECT id FROM kelas");
$kelas = $kelas_query ? mysqli_num_rows($kelas_query) : 0;

$mapel_query = mysqli_query($koneksi, "SELECT id FROM mata_pelajaran");
$mapel = $mapel_query ? mysqli_num_rows($mapel_query) : 0;

// ambil dari pengaturan biar tahun pelajaran & semester dinamis
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id = 1");
$sys           = mysqli_fetch_assoc($query_setting);

// pengumuman terbaru
$pengumuman = mysqli_query($koneksi, "SELECT * FROM pengumuman ORDER BY tanggal DESC LIMIT 4");

// jadwal hari ini (otomatis sesuai hari)
$hari_ini = date('l'); // nama hari (inggris)
$hari_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari_ini_id = $hari_map[$hari_ini] ?? 'Senin';
$jadwal_hari_ini = mysqli_query($koneksi, "
    SELECT j.*, mp.nama_mapel, k.nama_kelas, g.nama_lengkap AS nama_guru
    FROM jadwal j
    LEFT JOIN mata_pelajaran mp ON j.mapel_id = mp.id
    LEFT JOIN kelas k ON j.kelas_id = k.id
    LEFT JOIN guru g ON j.guru_id = g.id
    WHERE j.hari = '$hari_ini_id'
      AND j.status = 'aktif'
      AND j.tahun_ajaran_id = 1
    ORDER BY j.jam_mulai ASC, k.nama_kelas ASC
");

// agenda sesuai hari ini; yang hari NULL = agenda umum, tampil tiap hari
$agenda_hari_ini = mysqli_query($koneksi, "
    SELECT * FROM agenda
    WHERE hari IS NULL OR hari = '$hari_ini_id'
    ORDER BY urutan ASC, jam_mulai ASC, id ASC
");

// jumlah siswa per tahun buat grafik
$tahunPelajaran = $fallbackTahun ?? ($sys['tahun_pelajaran'] ?? '');
$tahun_ajaran = explode('/', $tahunPelajaran ?? '');
$tahun_awal = $tahun_ajaran ? intval($tahun_ajaran[0]) : (int)date('Y');
$tahun_data = [];
$siswa_data = [];
for ($i = 4; $i >= 0; $i--) {
    $tahun_data[] = ($tahun_awal - $i) . '/' . ($tahun_awal - $i + 1);
    // data simulasi, bisa diganti query riil nanti
    $siswa_data[] = max(0, $siswa - ($i * rand(5, 15)));
}
$siswa_data = array_reverse($siswa_data);
$tahun_data = array_reverse($tahun_data);
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar_admin.php'; ?>
<?php include '../includes/topbar_admin.php'; ?>


<div class="main-content">
        <!-- ===== WELCOME CARD ===== -->
    <div class="welcome-card">
        <div class="welcome-content">
            <div class="welcome-badge">
                <i class="bi bi-hand-wave-fill"></i>
                Selamat Datang di Sistem Informasi Akademik
            </div>
            <h2>Selamat Datang, <?= e($_SESSION['nama'] ?? 'Administrator') ?>!</h2>
            <p class="welcome-subtitle">SMA Negeri 4 Palopo Jl. Bakau, Balandai, Kec. Bara, Kota Palopo, Sulawesi Selatan.</p>
            <div class="d-flex flex-wrap">
                <div class="school-badge">
                    <i class="bi bi-calendar3"></i>
                    <span>Tahun Pelajaran: <strong><?= e($fallbackTahun ?? $sys['tahun_pelajaran'] ?? '—') ?></strong></span>
                </div>
                <div class="school-badge">
                    <i class="bi bi-clock"></i>
                    <span>Semester: <strong><?= e($sys['semester'] ?? '1 (Ganjil)') ?></strong></span>
                </div>
            </div>
        </div>
        <div class="welcome-decoration">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
    </div>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="row g-3 mb-4">
        <!-- Siswa -->
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="card-top">
                    <div class="card-icon blue">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>
                    <span class="card-trend up">
                        <i class="bi bi-arrow-up-short"></i> +12%
                    </span>
                </div>
                <div class="card-stat"><?= $siswa ?></div>
                <p class="card-label">Total Siswa</p>
                <div class="card-progress">
                    <div class="progress-bar blue" style="width: <?= min(100, $siswa > 0 ? ($siswa / 500) * 100 : 0) ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Guru -->
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="card-top">
                    <div class="card-icon gold">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span class="card-trend up">
                        <i class="bi bi-arrow-up-short"></i> +5%
                    </span>
                </div>
                <div class="card-stat"><?= $guru ?></div>
                <p class="card-label">Total Guru</p>
                <div class="card-progress">
                    <div class="progress-bar gold" style="width: <?= min(100, $guru > 0 ? ($guru / 100) * 100 : 0) ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Kelas -->
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="card-top">
                    <div class="card-icon green">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <span class="card-trend down">
                        <i class="bi bi-arrow-down-short"></i> 0%
                    </span>
                </div>
                <div class="card-stat"><?= $kelas ?></div>
                <p class="card-label">Total Kelas</p>
                <div class="card-progress">
                    <div class="progress-bar green" style="width: <?= min(100, $kelas > 0 ? ($kelas / 30) * 100 : 0) ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Mata Pelajaran -->
        <div class="col-xl-3 col-md-6">
            <div class="summary-card">
                <div class="card-top">
                    <div class="card-icon purple">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <span class="card-trend up">
                        <i class="bi bi-arrow-up-short"></i> +8%
                    </span>
                </div>
                <div class="card-stat"><?= $mapel ?></div>
                <p class="card-label">Mata Pelajaran</p>
                <div class="card-progress">
                    <div class="progress-bar purple" style="width: <?= min(100, $mapel > 0 ? ($mapel / 50) * 100 : 0) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS + RIGHT PANELS ===== -->
    <div class="row g-3 mb-4">
        <!-- Left Column: Charts -->
        <div class="col-lg-8">
            <div class="row g-3">
                <!-- Grafik Perkembangan Siswa -->
                <div class="col-12 mb-3">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h6><i class="bi bi-graph-up-arrow me-2" style="color: var(--primary);"></i> Perkembangan Jumlah Siswa</h6>
                            <span class="chart-period">5 Tahun Terakhir</span>
                        </div>
                        <div class="chart-body">
                            <canvas id="studentGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Grafik Kehadiran -->
                <div class="col-12">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h6><i class="bi bi-clipboard-data me-2" style="color: var(--primary);"></i> Grafik Kehadiran Siswa</h6>
                            <span class="chart-period">Bulan Ini</span>
                        </div>
                        <div class="chart-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Panels -->
        <div class="col-lg-4">
            <div class="row g-3">
                <!-- Pengumuman Sekolah -->
                <div class="col-12">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-icon gold-bg">
                                <i class="bi bi-megaphone-fill"></i>
                            </div>
                            <h6>Pengumuman Sekolah</h6>
                            <span class="panel-count">
                                <?= $pengumuman ? mysqli_num_rows($pengumuman) : 0 ?> baru
                            </span>
                        </div>
                        <div class="panel-body">
                            <?php if ($pengumuman && mysqli_num_rows($pengumuman) > 0): ?>
                                <?php mysqli_data_seek($pengumuman, 0); ?>
                                <?php while($p = mysqli_fetch_assoc($pengumuman)): ?>
                                <div class="panel-item">
                                    <div class="item-title">
                                        <i class="bi bi-dot"></i>
                                        <?= e($p['judul']) ?>
                                    </div>
                                    <div class="item-meta">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= tanggal_indo_pendek($p['tanggal']) ?></span>
                                        <span class="item-tag info">Informasi</span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 32px; color: var(--text-light);"></i>
                                    <p class="text-muted mt-2 mb-0" style="font-size: 12px;">Belum ada pengumuman.</p>
                                </div>
                            <?php endif; ?>
                            <a href="/siakad/admin/pengumuman/index.php" class="btn btn-sm btn-outline-primary w-100 mt-2" style="border-radius: 10px;">
                                <i class="bi bi-eye me-1"></i> Lihat Semua
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Agenda Hari Ini (Dinamis dari Database) -->
                <div class="col-12">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-icon blue-bg">
                                <i class="bi bi-calendar-check-fill"></i>
                            </div>
                            <h6>Agenda Hari Ini</h6>
                            <span class="panel-count"><?= tanggal_indo_pendek() ?></span>
                        </div>
                        <div class="panel-body">
                            <?php
                            // jadwal pelajaran hari ini
                            $jadwal_count = 0;
                            if ($jadwal_hari_ini && mysqli_num_rows($jadwal_hari_ini) > 0):
                                while ($j = mysqli_fetch_assoc($jadwal_hari_ini)):
                                    $jadwal_count++;
                            ?>
                            <div class="panel-item">
                                <div class="item-title">
                                    <i class="bi bi-dot blue-dot"></i>
                                    Belajar Mengajar <?= e($j['nama_kelas'] ?? '') ?>
                                </div>
                                <div class="item-meta">
                                    <i class="bi bi-clock"></i>
                                    <span><?= e($j['jam_mulai']) ?> - <?= e($j['jam_selesai']) ?> WITA</span>
                                    <span class="item-tag success">Aktif</span>
                                </div>
                                <span class="item-desc"><?= e($j['nama_mapel'] ?? '') ?> • <?= e($j['nama_guru'] ?? '') ?></span>
                            </div>
                            <?php
                                endwhile;
                            endif;
                            // kalo ga ada jadwal, tampilkan teks default
                            if ($jadwal_count == 0):
                            ?>
                            <div class="panel-item">
                                <div class="item-title">
                                    <i class="bi bi-dot blue-dot"></i>
                                    Belajar Mengajar Kelas X - XII
                                </div>
                                <div class="item-meta">
                                    <i class="bi bi-clock"></i>
                                    <span>07:30 - 13:00 WITA</span>
                                    <span class="item-tag success">Aktif</span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // agenda dari db (custom agenda)
                            if ($agenda_hari_ini && mysqli_num_rows($agenda_hari_ini) > 0):
                                while ($ag = mysqli_fetch_assoc($agenda_hari_ini)):
                                    $label_class = 'info';
                                    if ($ag['status_label'] == 'Aktif') $label_class = 'success';
                                    elseif ($ag['status_label'] == 'Akan Datang') $label_class = 'warning';
                                    elseif ($ag['status_label'] == 'Selesai') $label_class = 'secondary';
                            ?>
                            <div class="panel-item">
                                <div class="item-title">
                                    <i class="bi bi-dot blue-dot"></i>
                                    <?= e($ag['judul']) ?>
                                </div>
                                <div class="item-meta">
                                    <i class="bi bi-clock"></i>
                                    <span><?= e($ag['jam_mulai']) ?> - <?= e($ag['jam_selesai']) ?> WITA</span>
                                    <span class="item-tag <?= $label_class ?>"><?= e($ag['status_label']) ?></span>
                                </div>
                                <?php if (!empty($ag['deskripsi'])): ?>
                                <span class="item-desc"><?= e($ag['deskripsi']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                                endwhile;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// chart.js — grafik pertumbuhan siswa (line)
const studentCtx = document.getElementById('studentGrowthChart').getContext('2d');

// gradient fill
const studentGradient = studentCtx.createLinearGradient(0, 0, 0, 250);
studentGradient.addColorStop(0, 'rgba(22, 58, 99, 0.25)');
studentGradient.addColorStop(1, 'rgba(22, 58, 99, 0.01)');

new Chart(studentCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($tahun_data) ?>,
        datasets: [{
            label: 'Jumlah Siswa',
            data: <?= json_encode($siswa_data) ?>,
            fill: true,
            backgroundColor: studentGradient,
            borderColor: '#163A63',
            borderWidth: 3,
            pointBackgroundColor: '#163A63',
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#163A63',
                titleColor: '#FFFFFF',
                bodyColor: '#FFFFFF',
                padding: 12,
                cornerRadius: 10,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Jumlah: ' + context.raw + ' siswa';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.04)',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        family: 'Roboto',
                        size: 11
                    },
                    color: '#94A3B8'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Roboto',
                        size: 11
                    },
                    color: '#94A3B8',
                    maxRotation: 0
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// chart.js — grafik absensi (bar)
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');

// data absensi
const weeks = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
const hadir = [92, 88, 95, 90];
const izin = [4, 6, 3, 5];
const sakit = [3, 4, 2, 3];
const alpa = [1, 2, 0, 2];

// gradient fill per dataset
function barGradient(hex) {
    const g = attendanceCtx.createLinearGradient(0, 0, 0, 300);
    g.addColorStop(0, hex + 'D9');
    g.addColorStop(1, hex + '1F');
    return g;
}

new Chart(attendanceCtx, {
    type: 'bar',
    data: {
        labels: weeks,
        datasets: [
            {
                label: 'Hadir',
                data: hadir,
                backgroundColor: barGradient('#163A63'),
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            },
            {
                label: 'Izin',
                data: izin,
                backgroundColor: barGradient('#F09000'),
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            },
            {
                label: 'Sakit',
                data: sakit,
                backgroundColor: barGradient('#10B981'),
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            },
            {
                label: 'Alpa',
                data: alpa,
                backgroundColor: barGradient('#E11D48'),
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        family: 'Roboto',
                        size: 11
                    },
                    color: '#4A5568',
                    boxWidth: 12,
                    padding: 16,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: '#0D2540',
                titleColor: '#FFFFFF',
                bodyColor: '#FFFFFF',
                padding: 12,
                cornerRadius: 10,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.raw + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                grid: {
                    color: 'rgba(0, 0, 0, 0.04)',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        family: 'Roboto',
                        size: 11
                    },
                    color: '#94A3B8',
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Roboto',
                        size: 11
                    },
                    color: '#94A3B8'
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
</script>

<?php
// Tampilkan widget chatbot SiA Bot di halaman ini (footer.php bersifat kondisional)
$show_chatbot = true;
include '../includes/footer.php';
?>
