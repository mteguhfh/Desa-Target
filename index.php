<?php
// Halaman beranda. Berkas ini menampung logika dan variabel halamannya;
// markup-nya dibaca dari index-markup.php supaya bagian PHP tetap murni.
require_once __DIR__ . '/db.php';

$pdo = db();
$totalWarga = hitung_warga($pdo);
$totalBangunan = hitung_bangunan_aktif($pdo);
$terbaru = bangunan_terbaru($pdo);
$papanGang = papan_gang($pdo);

$judulHalaman = 'Beranda';
$halamanAktif = 'beranda';
require __DIR__ . '/includes/header.php';

require __DIR__ . '/index-markup.php';

require __DIR__ . '/includes/footer.php';
