<?php
// Beranda desa: peta 3D langsung (ADR-0005).
//
// Halaman ini menjalankan jalur yang sama dengan peta.php ($dariAkar = true)
// supaya alamat utama langsung memperlihatkan dunia 3D, tanpa halaman
// perantara. Isi beranda lama (statistik, Pembangunan Hari Ini, Cara Main,
// Papan Gang) tinggal di tentang.php.
//
// Judul, meta deskripsi, dan OG untuk halaman ini ditulis di dalam
// peta3d/index.php lewat variabel $metaBeranda di bawah, sehingga tautan
// yang dibagikan ke Instagram atau WhatsApp tetap tampil rapi.

require_once __DIR__ . '/config.php';

$dariAkar = true;
$dariBeranda = true;
$metaBeranda = [
    'judul' => 'Desanya UMKM hebat',
    'deskripsi' => 'Masuk langsung ke desa 3D tempat para UMKM hebat tinggal. Bangunan di sini lahir dari komentar warga. Bikin KTP Warga gratis, kumpulkan poin, dan buka ruko usahamu.',
    'kanonis' => APP_BASE_URL . '/',
];

require __DIR__ . '/peta3d/index.php';
