<?php
// Kepala halaman bersama untuk semua halaman publik.
// Variabel yang bisa diatur halaman pemanggil: $judulHalaman, $deskripsiHalaman, $halamanAktif,
// dan $tanpaHeaderSitus (halaman sepenuh layar seperti peta menggambar bilah navigasinya sendiri).
// Markup kepala ada di header-markup.php.
require_once __DIR__ . '/../config.php';

if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$judulHalaman = $judulHalaman ?? APP_NAME;
$deskripsiHalaman = $deskripsiHalaman ?? 'Desa Target, desa fiktif para UMKM hebat. Follow berarti jadi warga, komen paling banyak disukai dibangun besok oleh Bang Uju.';
$halamanAktif = $halamanAktif ?? '';
$tanpaHeaderSitus = $tanpaHeaderSitus ?? false;
// Penanda versi stylesheet dari waktu ubah berkasnya. Setiap deploy yang
// mengubah style.css otomatis mengganti URL-nya, jadi peramban warga tidak
// lagi menyajikan CSS lama dari cache.
$versiStyle = @filemtime(__DIR__ . '/../assets/style.css') ?: 1;

require __DIR__ . '/header-markup.php';
