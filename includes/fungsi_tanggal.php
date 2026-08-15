<?php
// helper format tanggal indo. contoh: 5 Agustus 2026 (full) / 5 Agu 2026 (pendek)

$GLOBALS['BULAN_INDO'] = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$GLOBALS['BULAN_INDO_PENDEK'] = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$GLOBALS['HARI_INDO'] = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu'
];

// bulan angka -> nama bulan indo lengkap
function bulan_indo($n) {
    $n = (int)$n;
    return $GLOBALS['BULAN_INDO'][$n] ?? (string)$n;
}

// versi singkatnya (Agu, Okt, dst)
function bulan_indo_pendek($n) {
    $n = (int)$n;
    return $GLOBALS['BULAN_INDO_PENDEK'][$n] ?? (string)$n;
}

// hari inggris -> indo
function hari_indo($nama) {
    return $GLOBALS['HARI_INDO'][$nama] ?? $nama;
}

// format lengkap: 5 Agustus 2026, bisa sekalian hari (Senin, 5 Agustus 2026)
function tanggal_indo($tgl = null, $dengan_hari = false) {
    if ($tgl === null || $tgl === '') {
        $tgl = date('Y-m-d');
    }
    if ($tgl === '0000-00-00' || $tgl === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = is_numeric($tgl) ? (int)$tgl : strtotime($tgl);
    $hasil = (int)date('j', $ts) . ' ' . bulan_indo(date('n', $ts)) . ' ' . date('Y', $ts);
    if ($dengan_hari) {
        $hasil = hari_indo(date('l', $ts)) . ', ' . $hasil;
    }
    return $hasil;
}

// format pendek: 5 Agu 2026
function tanggal_indo_pendek($tgl = null, $dengan_hari = false) {
    if ($tgl === null || $tgl === '') {
        $tgl = date('Y-m-d');
    }
    if ($tgl === '0000-00-00' || $tgl === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = is_numeric($tgl) ? (int)$tgl : strtotime($tgl);
    $hasil = (int)date('j', $ts) . ' ' . bulan_indo_pendek(date('n', $ts)) . ' ' . date('Y', $ts);
    if ($dengan_hari) {
        $hasil = hari_indo(date('l', $ts)) . ', ' . $hasil;
    }
    return $hasil;
}

// tanggal + jam, misal: 5 Agu 2026, 14:30
function tanggal_waktu_indo($tgl = null, $dengan_detik = false) {
    if ($tgl === null || $tgl === '') {
        $tgl = date('Y-m-d H:i:s');
    }
    if ($tgl === '0000-00-00 00:00:00' || $tgl === '0000-00-00') {
        return '-';
    }
    $ts = is_numeric($tgl) ? (int)$tgl : strtotime($tgl);
    $fmt = $dengan_detik ? 'H:i:s' : 'H:i';
    return tanggal_indo_pendek($ts) . ', ' . date($fmt, $ts);
}
