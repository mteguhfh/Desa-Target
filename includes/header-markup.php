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
        <a href="index.php"<?= in_array($halamanAktif, ['beranda', 'peta'], true) ? ' class="aktif"' : '' ?>>Beranda</a>
        <a href="tentang.php"<?= $halamanAktif === 'tentang' ? ' class="aktif"' : '' ?>>Tentang</a>
        <a href="ktp.php"<?= $halamanAktif === 'ktp' ? ' class="aktif"' : '' ?>>Bikin KTP Warga</a>
        <a href="ruko.php"<?= $halamanAktif === 'ruko' ? ' class="aktif"' : '' ?>>Buka Ruko</a>
        <a href="pasar.php"<?= $halamanAktif === 'pasar' ? ' class="aktif"' : '' ?>>Pasar Desa</a>
        <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">Instagram</a>
    </nav>
</header>
<?php endif; ?>
<main<?= $tanpaHeaderSitus ? ' class="main-penuh"' : '' ?>>
