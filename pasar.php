<?php
// Halaman Pasar Desa: jadwal Pasar Malam dan CFD, hitung mundur acara
// berikutnya, dan daftar lapak yang sudah diambil (Fitur 4).
//
// Halaman ini hanya membaca. Lapak diambil dari panel Pasar di peta (lewat
// lapak.php) supaya aturan "hanya saat acara berlangsung" ditegakkan di satu
// tempat. Semua waktu dihitung dari jam server dengan fungsi yang sama
// dengan acara_aktif(), jadi jadwal di sini tidak bisa berbeda dengan peta.
require_once __DIR__ . '/db.php';

$pdo = db();
$sekarang = date('Y-m-d H:i');
$pasar = keadaan_pasar($pdo, $sekarang);

// Daftar lapak acara berikutnya, supaya pengunjung bisa melihat siapa saja
// yang sudah mendaftar sebelum harinya tiba.
$lapakBerikut = [];
if ($pasar['berikutnya'] !== null) {
    $lapakBerikut = daftar_lapak($pdo, $pasar['berikutnya']['acara'], $pasar['berikutnya']['tanggal']);
}

$judulHalaman = 'Pasar Desa';
$halamanAktif = 'pasar';
require __DIR__ . '/includes/header.php';

$pasarData = $pasar;
$pasarLapakBerikut = $lapakBerikut;
require __DIR__ . '/pasar-markup.php';

require __DIR__ . '/includes/footer.php';
