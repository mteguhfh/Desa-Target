<?php
// Alat admin baris perintah: menambahkan bangunan hasil komentar pemenang harian.
//
// Pakai:
//   php tools/tambah-bangunan.php "Nama Bangunan" "Gang" "Nama Peminta" "Komentar asal"
//
// Contoh:
//   php tools/tambah-bangunan.php "Warung Kopi Senja" "Gang Kuliner" "Sari Wulandari" "Bangun warung kopi dong, Bang!"
//
// Script ini hanya boleh jalan dari terminal. Dari web, akses ditolak.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Script ini hanya untuk baris perintah.\n");
}

require_once __DIR__ . '/../db.php';

if ($argc !== 5) {
    fwrite(STDERR, "Pakai: php tools/tambah-bangunan.php \"Nama Bangunan\" \"Gang\" \"Nama Peminta\" \"Komentar asal\"\n");
    exit(1);
}

[, $nama, $gang, $peminta, $komentar] = $argv;
$nama = trim($nama);
$gang = trim($gang);
$peminta = trim($peminta);
$komentar = trim($komentar);

if ($nama === '' || $gang === '' || $peminta === '') {
    fwrite(STDERR, "Nama bangunan, gang, dan nama peminta tidak boleh kosong.\n");
    exit(1);
}

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO bangunan (nama, gang, diminta_oleh, komentar, tanggal) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$nama, $gang, $peminta, $komentar, date('Y-m-d')]);
$id = (int) $pdo->lastInsertId();

echo "Bangunan baru berdiri di Desa Target:\n";
echo "  ID        : {$id}\n";
echo "  Nama      : {$nama}\n";
echo "  Gang      : {$gang}\n";
echo "  Diminta oleh : {$peminta}\n";
echo "  Tanggal   : " . format_tanggal(date('Y-m-d')) . "\n";
echo "Cek hasilnya di halaman Peta Desa (peta.php).\n";
