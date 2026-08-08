<?php
/* ================================================================
   Helper Fungsi Notifikasi — SIA SMA Negeri 4 Palopo
   ================================================================ */

/**
 * Sematkan notifikasi ke tabel notifikasi.
 * Semua insert dilakukan dengan statement yang sudah di-escape oleh periode.
 */
function notifikasi_insert($koneksi, $user_id, $judul, $pesan, $link = '') {
    $judul = mysqli_real_escape_string($koneksi, $judul);
    $pesan = mysqli_real_escape_string($koneksi, $pesan);
    $link  = mysqli_real_escape_string($koneksi, $link);
    $uid   = (int) $user_id;

    // Simpan per user. Untuk kecepatan, satu query per user.
    return mysqli_query($koneksi,
        "INSERT INTO notifikasi (user_id, judul, pesan, link)
         VALUES ('$uid', '$judul', '$pesan', '$link')");
}

/**
 * Ambil ID user berdasarkan role dan id_ref (relasi ke guru/siswa).
 * $id_ref adalah id di tabel guru / siswa.
 */
function notifikasi_id_user_by_ref($koneksi, $id_ref, $role) {
    $id_ref = (int) $id_ref;
    $role   = mysqli_real_escape_string($koneksi, $role);
    $q = mysqli_query($koneksi,
        "SELECT id FROM users WHERE id_ref='$id_ref' AND role='$role' LIMIT 1");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        return (int) $row['id'];
    }
    return null;
}

/**
 * Kirim notifikasi ke semua user dengan role tertentu.
 * Contoh: notifikasi_ke_role($koneksi, 'guru', $judul, $pesan, $link);
 */
function notifikasi_ke_role($koneksi, $role, $judul, $pesan, $link = '') {
    $role = mysqli_real_escape_string($koneksi, $role);
    $judul = mysqli_real_escape_string($koneksi, $judul);
    $pesan = mysqli_real_escape_string($koneksi, $pesan);
    $link  = mysqli_real_escape_string($koneksi, $link);

    $q = mysqli_query($koneksi,
        "SELECT id FROM users WHERE role='$role' AND status='aktif'");
    $sukses = 0;
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $uid = (int) $row['id'];
            if (mysqli_query($koneksi,
                "INSERT INTO notifikasi (user_id, judul, pesan, link)
                 VALUES ('$uid', '$judul', '$pesan', '$link')")) {
                $sukses++;
            }
        }
    }
    return $sukses;
}

/**
 * Ambil daftar notifikasi untuk satu user.
 */
function notifikasi_get($koneksi, $user_id, $limit = 10) {
    $user_id = (int) $user_id;
    $limit   = (int) $limit;
    $q = mysqli_query($koneksi,
        "SELECT * FROM notifikasi WHERE user_id='$user_id'
         ORDER BY id DESC LIMIT $limit");
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

/**
 * Ambil daftar notifikasi dengan filter & pagination (untuk halaman penuh).
 * $filter: 'all' | 'unread'
 */
function notifikasi_get_rows($koneksi, $user_id, $filter = 'all', $limit = 20, $offset = 0) {
    $user_id = (int) $user_id;
    $limit   = (int) $limit;
    $offset  = (int) $offset;
    $where   = "user_id='$user_id'";
    if ($filter === 'unread') $where .= " AND is_read=0";

    $q = mysqli_query($koneksi,
        "SELECT * FROM notifikasi WHERE $where
         ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $rows = [];
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

/**
 * Jumlah notifikasi belum dibaca untuk satu user.
 */
function notifikasi_belum_dibaca($koneksi, $user_id) {
    $user_id = (int) $user_id;
    $q = mysqli_query($koneksi,
        "SELECT COUNT(*) AS jml FROM notifikasi WHERE user_id='$user_id' AND is_read=0");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        return (int) $row['jml'];
    }
    return 0;
}

/**
 * Tandai satu notifikasi telah dibaca.
 */
function notifikasi_tandai_dibaca($koneksi, $id, $user_id) {
    $id      = (int) $id;
    $user_id = (int) $user_id;
    return mysqli_query($koneksi,
        "UPDATE notifikasi SET is_read=1 WHERE id='$id' AND user_id='$user_id'");
}

/**
 * Tandai SEMUA notifikasi user telah dibaca.
 */
function notifikasi_tandai_semua_dibaca($koneksi, $user_id) {
    $user_id = (int) $user_id;
    return mysqli_query($koneksi,
        "UPDATE notifikasi SET is_read=1 WHERE user_id='$user_id' AND is_read=0");
}
?>