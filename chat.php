<?php
// Endpoint chat antar gang. Publik total, tanpa DM dan tanpa teks bebas.
//
//   GET  chat.php?kanal=alun-alun&sejak=0
//        -> { kanal, sejak, terakhir, ada_lagi, frase, pesan: [...] }
//   POST chat.php  kanal=alun-alun&frase=halo&nomor=DT-0001&kunci=...
//        -> 201 { ok, pesan: [pesan baru] }
//
// Siapa pun (tanpa kartu warga) boleh MEMBACA. Untuk menulis wajib nomor dan
// kunci kartu; nama dan gang pengirim diambil dari tabel warga, bukan dari
// isi permintaan, jadi tidak bisa dipalsukan.
//
// Yang dikirim hanya kode frase dari CHAT_FRASE (config.php). Endpoint ini
// tidak punya kolom teks bebas: field lain seperti "teks" atau "pesan"
// diabaikan sepenuhnya.
//
// Jawaban galat sengaja sama untuk nomor tidak dikenal maupun kunci salah,
// supaya endpoint ini tidak bisa dipakai menebak nomor warga yang ada.
// Kanal gang lain boleh dibaca tetapi hanya pemilik gang itu yang menulis.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/db.php';

$metode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metode !== 'GET' && $metode !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['galat' => 'Gunakan GET atau POST.']);
    exit;
}

// Kanal bawaan: kanal umum, supaya panel chat bisa membuka pesan pertama
// tanpa parameter apa pun.
$isi = $metode === 'POST' ? $_POST : $_GET;
$kanalMasuk = $isi['kanal'] ?? CHAT_KANAL_UMUM;

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    $kanal = kanal_chat_sah($kanalMasuk);
    if ($kanal === null) {
        http_response_code(400);
        echo json_encode(['galat' => 'Kanal chat tidak dikenal.']);
        exit;
    }

    if ($metode === 'POST') {
        $frase = isset($isi['teks']) && is_string($isi['teks']) ? 'teks:' . trim($isi['teks']) : ($isi['frase'] ?? '');
        $hasil = kirim_chat($pdo, $kanal, $frase, $isi['nomor'] ?? '', $isi['kunci'] ?? '');
        switch ($hasil['galat'] ?? null) {
            case null:
                http_response_code(201);
                echo json_encode(['ok' => true, 'pesan' => $hasil['pesan']], JSON_UNESCAPED_UNICODE);
                exit;
            case 'frase':
                http_response_code(400);
                echo json_encode(['galat' => 'Tulis pesan 1 sampai 280 karakter atau pilih kalimat cepat.']);
                exit;
            case 'kanal-lain':
                http_response_code(403);
                echo json_encode(['galat' => 'Kamu hanya bisa menulis di kanal gangmu sendiri atau Alun-Alun.']);
                exit;
            case 'terlalu-cepat':
                http_response_code(429);
                header('Retry-After: ' . CHAT_JEDA_DETIK);
                echo json_encode(['galat' => 'Terlalu cepat. Tunggu beberapa detik lagi.'], JSON_UNESCAPED_UNICODE);
                exit;
            default:
                http_response_code(403);
                echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
                exit;
        }
    }

    $sejak = (int) ($_GET['sejak'] ?? 0);
    $sejak = max(0, min(1000000000, $sejak));
    $pesan = pesan_chat($pdo, $kanal, $sejak, 50);
    $terakhir = $sejak;
    foreach ($pesan as $p) {
        $terakhir = max($terakhir, (int) $p['id']);
    }

    echo json_encode(
        [
            'kanal' => $kanal,
            'sejak' => $sejak,
            'terakhir' => $terakhir,
            // Ada lagi berarti jawaban penuh 50 baris: peramban perlu meminta
            // sekali lagi dengan sejak terakhir supaya tidak ada pesan bolong.
            'ada_lagi' => count($pesan) === 50,
            'frase' => daftar_frase_chat(),
            'pesan' => $pesan,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Chat belum bisa dibaca.']);
}
