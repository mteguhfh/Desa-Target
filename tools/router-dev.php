<?php
// Router untuk server bawaan PHP saat pengembangan lokal. Meniru aturan
// alamat tanpa .php di .htaccess akar, yang tidak dibaca oleh php -S:
//   php -S localhost:8000 tools/router-dev.php
// Alamat /ktp dilayani ktp.php, /peta3d/data-desa dilayani
// peta3d/data-desa.php. Alamat lain (berkas statis, direktori, dan alamat
// lama berakhiran .php) diserahkan kembali ke server bawaan.

$jalur = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$akar = dirname(__DIR__);
$berkas = $akar . $jalur;

if ($jalur === '/' || str_ends_with($jalur, '/') || str_contains($jalur, '..') || is_dir($berkas)) {
    return false;
}

if (is_file($berkas . '.php')) {
    $_SERVER['SCRIPT_NAME'] = $jalur . '.php';
    $_SERVER['SCRIPT_FILENAME'] = $berkas . '.php';
    $_SERVER['PHP_SELF'] = $jalur . '.php';
    chdir(dirname($berkas));
    require $berkas . '.php';
    return true;
}

return false;
