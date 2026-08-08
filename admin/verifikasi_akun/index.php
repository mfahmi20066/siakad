<?php
include '../../config/koneksi.php';
include '../../config/session.php';
include '../../config/helper_auth.php';
include '../../config/mailer.php';
include '../../config/helper_tahun_ajaran.php';
include '../../config/database.php';
cekAdmin();
$title = "Verifikasi Akun";

// ── Aksi setujui / tolak ──────────────────────────────────────
// Setujui via GET link; tolak via POST modal (atau GET fallback).
if ((isset($_GET['aksi']) && isset($_GET['id'])) || (isset($_POST['id_tolak']))) {
    $aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'tolak';
    $id   = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id_tolak'];

    $upd = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id=$id"));

    if ($upd && $upd['status'] === 'pending') {
        $nama  = $upd['nama'];
        $email = $upd['email'];

        if ($aksi === 'setujui') {
            // Insert record terkait di tabel guru/siswa sesuai role
            $id_ref = null;
            if ($upd['role'] === 'guru') {
                $nama_e = mysqli_real_escape_string($koneksi, $nama);
                $email_e = mysqli_real_escape_string($koneksi, $email);
                mysqli_query($koneksi,
                    "INSERT INTO guru (nip, nama, nama_lengkap, email)
                     VALUES (NULL, '$nama_e', '$nama_e', '$email_e')");
                $id_ref = mysqli_insert_id($koneksi);
            } elseif ($upd['role'] === 'siswa') {
                // Tahun ajaran diambil dari master tahun aktif (sumber kebenaran),
                // bukan nilai bebas/hardcode; simpan id + teks legacy agar tidak NULL.
                $taSI = null; $taST = '';
                try {
                    $taV = getTahunAjaranAktif(tahun_ajaran_pdo());
                    $taSI = (int) $taV['id'];
                    $taST = $taV['tahun'];
                } catch (Throwable $e) { /* biar $taSI tetap NULL; insert dibiarkan tanpa tahun */ }
                $nama_e = mysqli_real_escape_string($koneksi, $nama);
                $email_e = mysqli_real_escape_string($koneksi, $email);
                // NIS otomatis dari NisGeneratorService (tahun masuk = tahun ajaran aktif).
                $tahunMasuk = ($taST !== '') ? (int) explode('/', $taST)[0] : (int) date('Y');
                $nis = app_generate_nis_sementara($tahunMasuk);
                if ($taSI !== null) {
                    mysqli_query($koneksi,
                        "INSERT INTO siswa (nis, nisn, nama, nama_lengkap, email, tahun_ajaran, tahun_ajaran_id)
                         VALUES ('$nis', NULL, '$nama_e', '$nama_e', '$email_e', '$taST', '$taSI')");
                } else {
                    mysqli_query($koneksi,
                        "INSERT INTO siswa (nis, nisn, nama, nama_lengkap, email, tahun_ajaran)
                         VALUES ('$nis', NULL, '$nama_e', '$nama_e', '$email_e', '$taST')");
                }
                $id_ref = mysqli_insert_id($koneksi);
            }

            $id_ref_sql = $id_ref ? (int) $id_ref : 'NULL';
            mysqli_query($koneksi,
                "UPDATE users SET status='aktif', id_ref=$id_ref_sql WHERE id=$id");

            if ($email) {
                $body = templatEmail('Akun Anda Disetujui',
                    '<p>Halo <strong>' . htmlspecialchars($nama) . '</strong>,</p>'
                    . '<p>Pendaftaran akun Anda di Sistem Informasi Akademik SMA Negeri 4 Palopo '
                    . 'telah <strong>disetujui</strong> oleh admin.</p>'
                    . '<p>Anda sekarang dapat login menggunakan username dan password yang '
                    . 'sudah didaftarkan.</p>'
                    . '<p><a href="/siakad/auth/login.php" '
                    . 'style="display:inline-block;background:#F09000;color:#0D2540;'
                    . 'padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;">'
                    . 'Login Sekarang</a></p>');
                kirimEmail($email, 'Akun Anda Disetujui — SIA SMAN 4 Palopo', $body);
            }

            header("Location: index.php?success=" . urlencode("Akun $nama disetujui."));
            exit();
        }

        if ($aksi === 'tolak') {
            $alasan = trim($_POST['alasan'] ?? '');
            if ($alasan === '') $alasan = 'Tidak ada alasan yang diberikan.';

            $alasan_e = mysqli_real_escape_string($koneksi, $alasan);
            mysqli_query($koneksi,
                "UPDATE users SET status='ditolak' WHERE id=$id");

            if ($email) {
                $body = templatEmail('Pendaftaran Akun Ditolak',
                    '<p>Halo <strong>' . htmlspecialchars($nama) . '</strong>,</p>'
                    . '<p>Mohon maaf, pendaftaran akun Anda di Sistem Informasi Akademik '
                    . 'SMA Negeri 4 Palopo <strong>ditolak</strong> oleh admin.</p>'
                    . '<p><strong>Alasan:</strong> ' . htmlspecialchars($alasan) . '</p>'
                    . '<p>Jika Anda merasa ini sebuah kesalahan, silakan hubungi admin sekolah.</p>');
                kirimEmail($email, 'Pendaftaran Akun Ditolak — SIA SMAN 4 Palopo', $body);
            }

            header("Location: index.php?error=" . urlencode("Akun $nama ditolak."));
            exit();
        }
    }
    header("Location: index.php");
    exit();
}

// ── Ambil daftar akun pending ─────────────────────────────────
$data = mysqli_query($koneksi, "SELECT * FROM users WHERE status='pending' ORDER BY created_at DESC");
$jml_pending = mysqli_num_rows($data);
?>
<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar_admin.php'; ?>

<div class="main-content">
    <?php include '../../includes/topbar_admin.php'; ?>

    <div class="page-header">
        <h4><i class="fas fa-user-check text-gold me-2"></i>Verifikasi Akun</h4>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-auto">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-auto">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-user-clock"></i> Akun Menunggu Verifikasi
                <span class="badge bg-warning ms-1"><?= $jml_pending ?></span>
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelVerifikasi" class="table table-hover dataTable align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($r = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($r['nama']) ?></strong></td>
                        <td><?= htmlspecialchars($r['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td>
                            <?php if ($r['role'] == 'guru'): ?>
                                <span class="badge bg-success">Guru</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Siswa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= $r['created_at'] ? tanggal_waktu_indo($r['created_at']) : '-' ?>
                            </small>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="?aksi=setujui&id=<?= $r['id'] ?>"
                                   class="btn btn-success btn-sm" title="Setujui">
                                    <i class="fas fa-check"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        title="Tolak"
                                        onclick="openTolak(<?= $r['id'] ?>, '<?= htmlspecialchars($r['nama'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($jml_pending == 0): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-circle fs-1 d-block mb-2"></i>
                Tidak ada akun yang menunggu verifikasi.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tolak Akun</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_tolak" id="idTolak">
                    <p>Anda akan menolak pendaftaran akun:
                        <strong id="namaTolak"></strong>
                    </p>
                    <label class="form-label">Alasan Penolakan (opsional)</label>
                    <textarea name="alasan" class="form-control" rows="3"
                              placeholder="Contoh: data tidak lengkap"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-times"></i> Tolak Akun
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openTolak(id, nama) {
    document.getElementById('idTolak').value = id;
    document.getElementById('namaTolak').textContent = nama;
    var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>
