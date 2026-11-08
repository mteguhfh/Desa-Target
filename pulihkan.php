<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['galat' => 'Gunakan POST.']);
    exit;
}
$token = $_POST['token'] ?? null;
if (!is_string($token) || empty($_SESSION['token_ktp']) || !hash_equals($_SESSION['token_ktp'], $token)) {
    http_response_code(409);
    echo json_encode(['galat' => 'Muat ulang halaman sebelum mencoba lagi.']);
    exit;
}
$nomor = is_string($_POST['nomor'] ?? null) ? $_POST['nomor'] : '';
$aksi = $_POST['aksi'] ?? 'pulihkan';
$hasil = null;
if ($aksi === 'buat') {
    $kunci = is_string($_POST['kunci'] ?? null) ? $_POST['kunci'] : '';
    $kode = buat_kode_pulih(db(), $nomor, $kunci);
    if ($kode !== null) $hasil = ['kode' => $kode];
} elseif ($aksi === 'pulihkan') {
    $kode = is_string($_POST['kode'] ?? null) ? $_POST['kode'] : '';
    $hasil = pulihkan_kartu(db(), $nomor, $kode);
}
if ($hasil === null) {
    http_response_code(403);
    echo json_encode(['galat' => 'Nomor atau kode pemulihan tidak cocok.']);
    exit;
}
echo json_encode(['ok' => true] + $hasil);
