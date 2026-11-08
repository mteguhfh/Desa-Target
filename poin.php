<?php
// Endpoint dompet poin warga (hanya membaca).
//
//   GET poin.php?nomor=DT-0001&kunci=...   -> { nomor, nama, saldo, riwayat, klaim }
//   POST poin.php  nomor=DT-0001&kunci=... -> sama (kunci tidak perlu terlihat
//                                             di alamat halaman)
//
// Nomor dan kunci harus cocok dengan kartu warga. Nomor KTP mudah ditebak,
// jadi nomor saja tidak cukup: tanpa kunci endpoint ini menolak, supaya saldo
// dan riwayat warga lain tidak bisa dibaca. Riwayat dibatasi 20 baris terbaru.
//
// Sifatnya hanya membaca, tidak ada yang ditulis.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$metode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metode !== 'GET' && $metode !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['galat' => 'Gunakan GET atau POST.']);
    exit;
}

$isi = $metode === 'POST' ? $_POST : $_GET;
$nomor = $isi['nomor'] ?? '';
$kunci = $isi['kunci'] ?? '';

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        http_response_code(403);
        echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
        exit;
    }

    $nomor = nomor_warga((int) $warga['id']);
    $daftar = daftar_misi($pdo, $nomor);
    $ringkasan = ringkasan_misi($daftar);
    echo json_encode(
        [
            'nomor' => $nomor,
            'nama' => $warga['nama'],
            'gang' => $warga['gang'],
            'saldo' => saldo_poin($pdo, $nomor),
            'riwayat' => riwayat_poin($pdo, $nomor, 20),
            'klaim' => sudah_klaim_harian($pdo, $nomor, date('Y-m-d')),
            'poinKlaimHarian' => POIN_KLAIM_HARIAN,
            // Misi hari ini beserta statusnya untuk warga ini. Tidak ada data
            // desa yang perlu dikirim ulang, cukup status per warga.
            'ringkasan' => $ringkasan,
            'streak' => streak_warga($pdo, $nomor),
            'koleksi' => koleksi_warga($pdo, $nomor),
            'daftar' => $daftar,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Dompet poin belum bisa dibaca.']);
}
