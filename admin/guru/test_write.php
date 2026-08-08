<?php
$folders = [
    'rel_guru' => '../assets/img/foto_guru/',
    'abs_guru' => __DIR__ . '/assets/img/foto_guru/',
    'rel_siswa' => '../assets/img/foto_siswa/',
    'abs_siswa' => __DIR__ . '/assets/img/foto_siswa/',
];
echo "CWD: " . getcwd() . "\n";
foreach ($folders as $k => $f) {
    $full = $f . 'test_write_' . $k . '.txt';
    $ok = @file_put_contents($full, 'hello');
    echo "$k [$full] is_dir=" . var_export(is_dir($f), true) . " write=" . var_export($ok !== false, true);
    if ($ok !== false) { @unlink($full); }
    echo "\n";
}