<?php
// Endpoint penanda misi harian.
//
// Menerima POST kode=... lalu menandai misi itu selesai untuk hari ini.
// Sifatnya menulis, tapi sangat sempit: hanya kode yang ada di tabel misi
// yang diterima, dan hanya tanggal hari ini yang bisa ditandai. Tidak ada
// data pengguna yang disimpan, jadi tidak perlu sesi.
//
// Jawaban: { ringkasan: { selesai, total }, daftar: [...] }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['galat' => 'Gunakan POST.']);
    exit;
}

$kode = (string) ($_POST['kode'] ?? '');
if (!preg_match('/^[a-z0-9-]{1,40}$/', $kode)) {
    http_response_code(400);
    echo json_encode(['galat' => 'Kode misi tidak sah.']);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    if (!tandai_misi($pdo, $kode)) {
        http_response_code(404);
        echo json_encode(['galat' => 'Misi tidak dikenal.']);
        exit;
    }

    $daftar = daftar_misi($pdo);
    echo json_encode(
        ['ringkasan' => ringkasan_misi($daftar), 'daftar' => $daftar],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Misi belum bisa dicatat.']);
}
