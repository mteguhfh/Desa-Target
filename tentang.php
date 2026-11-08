<?php
// Halaman Tentang: isi beranda lama setelah beranda menjadi peta 3D
// (ADR-0005). Cerita desa, statistik, Pembangunan Hari Ini, Cara Main, dan
// Papan Gang tinggal di sini.
//
// Halaman ini juga jalan keluar untuk perangkat yang berat menjalankan 3D
// dan untuk peramban tanpa WebGL: tanpa kanvas, isinya tetap terbaca.
require_once __DIR__ . '/db.php';

$pdo = db();
$totalWarga = hitung_warga($pdo);
$totalBangunan = hitung_bangunan_aktif($pdo);
$terbaru = bangunan_terbaru($pdo);
$papanGang = papan_gang($pdo);

$judulHalaman = 'Tentang Desa';
$deskripsiHalaman = 'Cerita Desa Target, desa fiktif para UMKM hebat: cara main, statistik warga, dan papan gang.';
$halamanAktif = 'tentang';
require __DIR__ . '/includes/header.php';

require __DIR__ . '/tentang-markup.php';

require __DIR__ . '/includes/footer.php';
