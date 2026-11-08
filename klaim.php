<?php
// Endpoint klaim poin harian gratis.
//
//   POST klaim.php  nomor=DT-0001&kunci=...
//   -> 200 { saldo, poin }        klaim berhasil
//   -> 409 { galat }              hari ini sudah klaim
//   -> 403 { galat }              nomor dan kunci tidak cocok
//
// Semacam misi.php: sangat sempit, hanya menerima nomor + kunci, tidak ada
// data lain yang disimpan dari kiriman klien. Tanggal klaim diambil dari jam
// server, bukan dari klien, jadi klaim besok tidak bisa dimajukan.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['galat' => 'Gunakan POST.']);
    exit;
}

$nomor = $_POST['nomor'] ?? '';
$kunci = $_POST['kunci'] ?? '';

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        http_response_code(403);
        echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
        exit;
    }

    $hasil = klaim_harian($pdo, nomor_warga((int) $warga['id']));
    if (isset($hasil['galat']) && $hasil['galat'] === 'sudah') {
        http_response_code(409);
        echo json_encode(['galat' => 'Poin harian hari ini sudah kamu klaim. Kembali besok ya.']);
        exit;
    }
    if (!isset($hasil['saldo'])) {
        http_response_code(500);
        echo json_encode(['galat' => 'Poin harian belum bisa diklaim.']);
        exit;
    }

    echo json_encode(
        ['saldo' => (int) $hasil['saldo'], 'poin' => POIN_KLAIM_HARIAN],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Poin harian belum bisa diklaim.']);
}
