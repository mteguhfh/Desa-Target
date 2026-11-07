<?php
// Endpoint pencarian warga untuk panel bersayap di peta 3D dan 2D.
//
// Dipakai supaya mencari warga TIDAK memuat ulang halaman: dunia peta tetap
// terlihat saat pengunjung mengetik. Halaman daftar lengkap tetap tersedia di
// warga.php untuk pengunjung tanpa JavaScript dan untuk tautan langsung.
//
// Jawaban JSON:
//   { cari: "kunci", jumlah: 2, hasil: [{ nomor, nama, gang }] }
//
// Sifatnya hanya membaca. Kunci disaring dan dipotong supaya tidak ada
// masukan pengguna yang masuk ke SQL selain lewat parameter terikat.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$kunci = (string) ($_GET['cari'] ?? '');
// Batasi panjangnya: nama warga sendiri paling panjang 50 karakter.
$kunci = function_exists('mb_substr') ? mb_substr($kunci, 0, 50) : substr($kunci, 0, 50);
$kunci = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $kunci) ?? '');

if ($kunci === '') {
    echo json_encode(['cari' => '', 'jumlah' => 0, 'hasil' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();
    $baris = cari_warga_berdasarkan($pdo, $kunci, 20);

    $hasil = [];
    foreach ($baris as $w) {
        $hasil[] = [
            'nomor' => nomor_warga((int) $w['id']),
            'nama' => $w['nama'],
            'gang' => $w['gang'],
        ];
    }

    echo json_encode(
        ['cari' => $kunci, 'jumlah' => count($hasil), 'hasil' => $hasil],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Daftar warga belum bisa dibaca.']);
}
