<?php
// Endpoint penanda misi harian untuk SATU WARGA.
//
//   POST misi.php  kode=keliling-desa&nomor=DT-0001&kunci=...
//   -> 200 { ringkasan, daftar, saldo, poin }
//
// Misi ditandai per warga dan poin misi diberikan sekali per warga per hari
// (lihat tandai_misi() di db.php). Nomor dan kunci harus cocok dengan kartu
// warga, jadi tidak ada yang bisa mengklaim misi atas nama orang lain.
//
// Jawaban galat sengaja sama untuk nomor tidak dikenal maupun kunci salah,
// supaya endpoint ini tidak bisa dipakai menebak nomor warga yang ada.
// Tanpa nomor sama sekali, jawabannya tetap daftar misi desa (hanya baca),
// seperti perilaku lama, supaya peta yang belum punya warga tetap jalan.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['galat' => 'Gunakan POST.']);
    exit;
}

$kode = (string) ($_POST['kode'] ?? '');
$nomor = $_POST['nomor'] ?? '';
$kunci = $_POST['kunci'] ?? '';

if (!preg_match('/^[a-z0-9-]{1,40}$/', $kode)) {
    http_response_code(400);
    echo json_encode(['galat' => 'Kode misi tidak sah.']);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    if ($nomor === '' && $kunci === '') {
        $daftar = daftar_misi($pdo);
        echo json_encode(
            ['ringkasan' => ringkasan_misi($daftar), 'daftar' => $daftar],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        http_response_code(403);
        echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
        exit;
    }

    $nomorWarga = nomor_warga((int) $warga['id']);
    $hasil = tandai_misi($pdo, $kode, $nomorWarga);
    if (($hasil['galat'] ?? null) === 'kode') {
        http_response_code(404);
        echo json_encode(['galat' => 'Misi tidak dikenal.']);
        exit;
    }
    if (($hasil['galat'] ?? null) !== null) {
        http_response_code(403);
        echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
        exit;
    }

    $daftar = daftar_misi($pdo, $nomorWarga);
    echo json_encode(
        [
            'ringkasan' => ringkasan_misi($daftar),
            'daftar' => $daftar,
            'saldo' => (int) $hasil['saldo'],
            'poin' => (int) $hasil['poin'],
            'streak' => streak_warga($pdo, $nomorWarga),
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Misi belum bisa dicatat.']);
}
