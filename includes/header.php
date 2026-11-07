<?php
// Kepala halaman bersama untuk semua halaman publik.
// Variabel yang bisa diatur halaman pemanggil: $judulHalaman, $deskripsiHalaman, $halamanAktif,
// dan $tanpaHeaderSitus (halaman sepenuh layar seperti peta menggambar bilah navigasinya sendiri).
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($judulHalaman) ?> | Desa Target</title>
<meta name="description" content="<?= e($deskripsiHalaman) ?>">
<link rel="canonical" href="<?= e(APP_BASE_URL) ?>/">
<meta property="og:title" content="<?= e($judulHalaman) ?> | Desa Target">
<meta property="og:description" content="<?= e($deskripsiHalaman) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(APP_BASE_URL) ?>/">
<meta property="og:image" content="<?= e(APP_BASE_URL) ?>/assets/bang-uju.png">
<link rel="icon" type="image/png" href="assets/bang-uju.png">
<link rel="stylesheet" href="assets/style.css?v=<?= (int) $versiStyle ?>">
</head>
<body>
<?php if (!$tanpaHeaderSitus): ?>
<header class="situs-header">
    <a class="merek" href="index.php">
        <img src="assets/bang-uju.png" alt="Bang Uju, Kepala Desa Target" class="merek-logo">
        <span class="merek-teks">Desa <strong>Target</strong></span>
    </a>
    <nav class="situs-nav">
        <a href="index.php"<?= $halamanAktif === 'beranda' ? ' class="aktif"' : '' ?>>Beranda</a>
        <a href="peta.php"<?= $halamanAktif === 'peta' ? ' class="aktif"' : '' ?>>Peta Desa</a>
        <a href="ktp.php"<?= $halamanAktif === 'ktp' ? ' class="aktif"' : '' ?>>Bikin KTP Warga</a>
        <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">Instagram</a>
    </nav>
</header>
<?php endif; ?>
<main<?= $tanpaHeaderSitus ? ' class="main-penuh"' : '' ?>>
