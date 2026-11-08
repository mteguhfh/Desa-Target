<?php
// Bikin KTP Warga langsung dari panel Aku, tanpa meninggalkan peta.
//
//   POST ktp.php?json=1  nama=...&gang=...&token=...
//   -> 200 { ok, nomor, kunci, nama, gang }   kartu terbit
//   -> 422 { galat }                          isian kurang pas
//   -> 409 { galat }                          formulir kedaluwarsa atau ganda
//   -> 400 { galat }                          bukan POST
//
// Formulir di halaman ktp.php tetap ada dan memakai logika yang sama; jalur
// ini hanya mengembalikan jawaban JSON untuk panel, supaya pengunjung tidak
// dipindah ke halaman lain.
//
// Kunci rahasia ikut dikirim di jawaban, sama seperti yang tercetak di
// kartu warga. Tanpa kunci, peramban tidak bisa memakai fitur poin, ruko,
// atau lapak atas nama warganya.
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jawab_ktp(int $status, array $isi): void
{
    http_response_code($status);
    echo json_encode($isi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jawab_ktp(405, ['galat' => 'Gunakan POST.']);
}

$tokenForm = (string) ($_POST['token'] ?? '');
$tokenSesi = (string) ($_SESSION['token_ktp'] ?? '');
if ($tokenSesi === '' || !hash_equals($tokenSesi, $tokenForm)) {
    jawab_ktp(409, ['galat' => 'Formulir ini sepertinya sudah terkirim sebelumnya. Buka lagi tab Aku untuk mencoba.']);
}

$nama = trim((string) ($_POST['nama'] ?? ''));
$gang = trim((string) ($_POST['gang'] ?? ''));

$galat = [];
if (function_exists('mb_strlen')) {
    $panjang = mb_strlen($nama);
} else {
    $panjang = (int) preg_match_all('/./us', $nama);
}
if ($panjang < 3 || $panjang > 50) {
    $galat[] = 'Nama lengkap diisi 3 sampai 50 karakter ya.';
} elseif (!preg_match("/^[\p{L} .'-]+$/u", $nama)) {
    $galat[] = 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.';
}
if (!in_array($gang, GANG_WARGA, true)) {
    $galat[] = 'Pilih dulu gang profesimu.';
}
if ($galat !== []) {
    jawab_ktp(422, ['galat' => $galat]);
}

$pdo = db();
$warna = [];
foreach (['baju', 'kulit', 'topi'] as $bagian) {
    $warna[$bagian] = warna_sah($bagian, $_POST['warna_' . $bagian] ?? null) ?? WARNA_BAWAAN[$bagian];
}

$stmt = $pdo->prepare(
    'INSERT INTO warga (nama, gang, created_at, warna_baju, warna_kulit, warna_topi) VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $nama, $gang, date('Y-m-d H:i:s'),
    $warna['baju'], $warna['kulit'], $warna['topi'],
]);
$idBaru = (int) $pdo->lastInsertId();

// Kunci rahasia terbit untuk warga baru. Di beberapa lingkungan PDO SQLite
// nilai bawaan kolom tidak terbaca pada baris yang baru disisipkan, jadi
// kuncinya ditulis eksplisit.
$kunciBaru = bin2hex(random_bytes(16));
$pdo->prepare('UPDATE warga SET kunci = ? WHERE id = ?')->execute([$kunciBaru, $idBaru]);
$kodePulih = buat_kode_pulih($pdo, nomor_warga($idBaru), $kunciBaru);

// Token lama hangus, ganti baru untuk pengiriman berikutnya. Kunci warga
// ikut terbit saat baris dibuat (lihat skema warga.kunci di db.php).
$_SESSION['token_ktp'] = bin2hex(random_bytes(16));
// Kunci dibaca langsung dari basis data, bukan lewat cari_warga(), supaya
// kolom kunci pasti ikut terbaca (fungsi itu bisa saja tidak mengembalikan
// kolom kunci di versi skema tertentu).
$warga = cari_warga($pdo, $idBaru);
$_SESSION['pesan_ktp'] = 'Selamat datang di Desa Target! Ini KTP Wargamu, tersimpan rapi di balai desa.';

jawab_ktp(200, [
    'ok' => true,
    'nomor' => nomor_warga($idBaru),
    'kunci' => $kunciBaru,
    'kode_pulih' => $kodePulih,
    'nama' => (string) ($warga['nama'] ?? $nama),
    'gang' => (string) ($warga['gang'] ?? $gang),
]);
