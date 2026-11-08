<?php
// Endpoint sapaan untuk pop-up sambutan di beranda peta 3D (ADR-0005).
//
//   GET sapa.php?nomor=DT-0007&kunci=...
//   -> 200 { nama, gang }       nomor dan kunci cocok
//   -> 403 { galat }            nomor tidak dikenal ATAU kunci salah
//
// Hanya membaca. Tidak ada data lain yang dikirim balik: kunci warga tidak
// pernah ikut di jawaban, dan jawaban galat sengaja sama untuk nomor tidak
// dikenal maupun kunci salah, supaya endpoint ini tidak bisa dipakai menebak
// nomor warga yang ada.
//
// Pop-up di beranda memakai ini untuk menyapa warga dengan namanya. Bila
// gagal, pop-up cukup menampilkan versi tamu, jadi kegagalan di sini bukan
// masalah: tidak ada yang rusak dan tidak ada yang perlu dicoba ulang.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$nomor = $_GET['nomor'] ?? '';
$kunci = $_GET['kunci'] ?? '';
$galatSama = ['galat' => 'Nomor atau kunci warga tidak cocok.'];

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        http_response_code(403);
        echo json_encode($galatSama);
        exit;
    }

    echo json_encode(
        ['nama' => (string) $warga['nama'], 'gang' => (string) $warga['gang']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    // Galat basis data dijawab sama seperti nomor yang tidak cocok, supaya
    // isi basis data tidak pernah bocor lewat pesan galat.
    http_response_code(403);
    echo json_encode($galatSama);
}
