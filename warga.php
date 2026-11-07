<?php
// Daftar warga desa dan hasil pencarian.
//
// Halaman ini memakai cari_warga_berdasarkan() yang sama dengan panel di peta,
// jadi hasil di sini dan di panel tidak mungkin berbeda. Tetap berfungsi tanpa
// JavaScript karena pencarian dikirim lewat formulir biasa (GET).
//
// Logika di sini; markup-nya di warga-markup.php.
require_once __DIR__ . '/db.php';

$pdo = db();

$kunci = trim((string) ($_GET['cari'] ?? ''));
$kunci = function_exists('mb_substr') ? mb_substr($kunci, 0, 50) : substr($kunci, 0, 50);
$mencari = $kunci !== '';

$batas = 100;
if ($mencari) {
    $warga = cari_warga_berdasarkan($pdo, $kunci, $batas);
} else {
    $stmt = $pdo->prepare('SELECT * FROM warga ORDER BY id ASC LIMIT ?');
    $stmt->bindValue(1, $batas, PDO::PARAM_INT);
    $stmt->execute();
    $warga = $stmt->fetchAll();
}

$totalWarga = hitung_warga($pdo);
$papanGang = papan_gang($pdo);
$adaLebihBanyak = count($warga) === $batas;
$batasTampil = $batas;

$judulHalaman = $mencari ? 'Cari warga: ' . $kunci : 'Daftar Warga Desa';
$halamanAktif = 'warga';
require __DIR__ . '/includes/header.php';

require __DIR__ . '/warga-markup.php';

require __DIR__ . '/includes/footer.php';
