<?php
// Data dan persiapan halaman peta interaktif, dipisah dari markup dan
// skripnya (includes/peta-markup.php dan includes/peta-skrip.php).
// Berkas ini murni kode: tidak mencetak apa pun, hanya menyiapkan
// variabel yang dipakai kedua berkas itu.
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/peta-posisi.php';
require_once __DIR__ . '/bangunan-svg.php';
require_once __DIR__ . '/interior-balai.php';

$pdo = db();
$bangunan = semua_bangunan($pdo);
$papanGang = papan_gang($pdo);
$totalKavling = count(POSISI_KAVLING); // Peta interaktif: 12 kavling di dunia 2000 x 1333.
$terbaruId = $bangunan !== [] ? (int) $bangunan[count($bangunan) - 1]['id'] : null;
$bangunanTerbaru = bangunan_terbaru($pdo);

// Warga versi 4: setiap warga terdaftar berdiri sebagai avatar di zona
// gang-nya. Warga paling awal mendaftar didahulukan; yang dirender di
// peta dibatasi 80 avatar dan dibatasi lagi oleh kapasitas grid zona.
// Sisanya dihitung supaya papan zona bisa menulis "+N warga lainnya".
$semuaWarga = $pdo->query('SELECT * FROM warga ORDER BY id ASC')->fetchAll();
$totalPerGang = [];
foreach ($semuaWarga as $w) {
    $totalPerGang[$w['gang']] = ($totalPerGang[$w['gang']] ?? 0) + 1;
}
$wargaPerGang = [];
$berdiriTotal = 0;
foreach ($semuaWarga as $w) {
    $gangWarga = (string) $w['gang'];
    if (!isset(ZONA_GANG[$gangWarga]) || $berdiriTotal >= 80) {
        continue;
    }
    $zonaWarga = ZONA_GANG[$gangWarga];
    $indeks = count($wargaPerGang[$gangWarga] ?? []);
    if ($indeks >= $zonaWarga['kolom'] * $zonaWarga['baris']) {
        continue;
    }
    $wargaPerGang[$gangWarga][] = [
        'warga' => $w,
        'x' => $zonaWarga['awal'][0] + ($indeks % $zonaWarga['kolom']) * $zonaWarga['dx'],
        'y' => $zonaWarga['awal'][1] + intdiv($indeks, $zonaWarga['kolom']) * $zonaWarga['dy'],
    ];
    $berdiriTotal++;
}

// Batas aman wara-wiri warga versi 4.3, dalam koordinat dunia: petak
// zona gang diperkecil dengan margin dari tepinya (margin atas lebih
// besar karena di sana papan nama gang berdiri), supaya avatar selalu
// berada di dalam wilayah gang-nya dan tidak menabrak papan. Skrip
// halaman memakai tabel ini untuk memilih titik tujuan jalan warga.
$marginZonaX = 14 * SKALA_DUNIA;
$marginZonaAtas = 18 * SKALA_DUNIA;
$marginZonaBawah = 12 * SKALA_DUNIA;
$zonaUntukJs = [];
foreach (ZONA_GANG as $gangZona => $zonaGang) {
    $zonaUntukJs[$gangZona] = [
        'x' => (int) round($zonaGang['tambak'][0] * SKALA_DUNIA + $marginZonaX),
        'y' => (int) round($zonaGang['tambak'][1] * SKALA_DUNIA + $marginZonaAtas),
        'w' => (int) round($zonaGang['tambak'][2] * SKALA_DUNIA - 2 * $marginZonaX),
        'h' => (int) round($zonaGang['tambak'][3] * SKALA_DUNIA - $marginZonaAtas - $marginZonaBawah),
    ];
}

// Titik fokus kamera saat halaman dibuka: kavling 0, tempat Balai Desa berdiri.
$fokusX = POSISI_KAVLING[0][0];
$fokusY = POSISI_KAVLING[0][1];
// Bang Uju berdiri di samping Balai Desa, sedikit ke kanan dan ke depan alun-alun.
$ujuX = $fokusX + 211;
$ujuY = $fokusY + 23;

$judulHalaman = 'Peta Desa';
$halamanAktif = 'peta';
// Peta adalah layar pertama itu sendiri (versi 4.1): kepala situs biasa
// tidak digambar, diganti bilah merek dan navigasi yang melayang di atas peta.
$tanpaHeaderSitus = true;
require __DIR__ . '/header.php';
