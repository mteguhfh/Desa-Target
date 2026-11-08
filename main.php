<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['galat' => 'Gunakan POST.']);
    exit;
}
require_once __DIR__ . '/db.php';
try {
    $pdo = db();
    $warga = warga_dari_nomor_dan_kunci($pdo, $_POST['nomor'] ?? '', $_POST['kunci'] ?? '');
    if (!$warga) {
        http_response_code(403);
        echo json_encode(['galat' => 'Kartu warga tidak cocok.']);
        exit;
    }
    $nomor = nomor_warga((int) $warga['id']);
    if (($_POST['aksi'] ?? '') === 'mulai') {
        $token = bin2hex(random_bytes(16));
        $_SESSION['panen'] = ['token' => $token, 'nomor' => $nomor, 'mulai' => microtime(true)];
        echo json_encode(['ok' => true, 'token' => $token]);
        exit;
    }
    $sesi = $_SESSION['panen'] ?? null;
    if (!$sesi || $sesi['nomor'] !== $nomor || !hash_equals($sesi['token'], (string) ($_POST['token'] ?? ''))) {
        http_response_code(400);
        echo json_encode(['galat' => 'Mulai permainan lagi.']);
        exit;
    }
    unset($_SESSION['panen']);
    $durasi = microtime(true) - $sesi['mulai'];
    if ($durasi < 0.4 || $durasi > 60) {
        http_response_code(400);
        echo json_encode(['galat' => 'Waktu panen habis. Mulai lagi.']);
        exit;
    }
    $posisi = 8 + (sin($durasi / .9 * M_PI * 2) + 1) * 42;
    $skor = max(0, (int) round(100 - abs($posisi - 50) * 3));
    echo json_encode(simpan_panen($pdo, $nomor, $skor));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Panen belum bisa dicatat.']);
}
