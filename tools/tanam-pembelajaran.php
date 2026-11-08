<?php
// Tanam bangunan Pusat Pembelajaran (Fitur 5) sebagai fasilitas desa.
//
// Pemakaian (CLI saja, pola yang sama dengan tools/tambah-bangunan.php):
//   php tools/tanam-pembelajaran.php
//
// Aman dijalankan berulang: migrasi db() sendiri menanam bangunan ini
// sekali, jadi skrip ini biasanya hanya melaporkan bahwa bangunan sudah
// ada. Berguna sebagai pemeriksaan setelah penarikan kode baru di server.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Hanya untuk CLI.');
}

require __DIR__ . '/../db.php';

$pdo = db();
$stmt = $pdo->prepare("SELECT id, tanggal FROM bangunan WHERE jenis = 'fasilitas' AND nama = 'Pusat Pembelajaran' ORDER BY id ASC LIMIT 1");
$stmt->execute();
$baris = $stmt->fetch();
if ($baris === false) {
    fwrite(STDERR, "Gagal: bangunan Pusat Pembelajaran tidak tertanam.\n");
    exit(1);
}
echo 'Pusat Pembelajaran sudah tertanam sebagai bangunan #' . (int) $baris['id'] . ' sejak ' . $baris['tanggal'] . ".\n";
