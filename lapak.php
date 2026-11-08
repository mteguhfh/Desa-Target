<?php
// Endpoint Pasar Malam dan CFD (Fitur 4). Lapak gratis, tanpa pembayaran.
//
//   GET  lapak.php?acara=pasar-malam&tanggal=2026-11-14
//        -> { acara, namaAcara, aktif, sekarang, berikutnya, jadwal,
//             lapak, batas }
//   POST lapak.php  acara=pasar-malam&nomor=DT-0001&kunci=...
//        -> 201 { ok, lapak, saldoTidakBerubah }
//
// Kalau warga sudah punya ruko, nama usaha dan tautannya diambil dari ruko
// itu. Warga tanpa ruko mengirim nama_usaha dan tautan di formulir panel.
// Tautan wajib https dan diperiksa dengan tautan_usaha_sah() yang sama
// dengan ruko.
//
// Galat:
//   400 acara atau tanggal tidak sah
//   403 nomor/kunci tidak cocok (jawaban sama untuk nomor tidak dikenal)
//   409 acara belum berlangsung, sudah punya lapak, atau lapak penuh
//   422 nama usaha/tautan tidak sah
//
// Sifatnya hanya membaca pada GET; tidak ada pembayaran atau poin yang
// dipotong, jadi lapak selalu gratis.

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

try {
    require_once __DIR__ . '/db.php';
    $pdo = db();

    $acara = $isi['acara'] ?? '';
    if (acara_sah($acara) === null) {
        http_response_code(400);
        echo json_encode(['galat' => 'Acara tidak dikenal.']);
        exit;
    }

    if ($metode === 'GET') {
        // Aktivitas acara mengikuti jam server, sama dengan acara_aktif().
        $sekarang = date('Y-m-d H:i');
        $aktif = acara_aktif($sekarang);
        $tanggal = (string) ($_GET['tanggal'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1) {
            $berikut = acara_berikutnya($sekarang);
            $tanggal = $berikut !== null && $berikut['acara'] === $acara ? $berikut['tanggal'] : date('Y-m-d');
        }
        $lapak = daftar_lapak($pdo, $acara, $tanggal);
        echo json_encode(
            [
                'acara' => $acara,
                'namaAcara' => ACARA_LAPAK[$acara]['nama'],
                'tanggal' => $tanggal,
                'aktif' => $aktif === $acara,
                'sekarang' => $sekarang,
                'berikutnya' => acara_berikutnya($sekarang),
                'jadwal' => ACARA_LAPAK[$acara],
                'lapak' => $lapak,
                'batas' => (int) ACARA_LAPAK[$acara]['batas'],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $hasil = klaim_lapak(
        $pdo,
        $isi['nomor'] ?? '',
        $isi['kunci'] ?? '',
        $acara,
        null,
        isset($isi['nama_usaha']) ? (string) $isi['nama_usaha'] : null,
        isset($isi['tautan']) ? (string) $isi['tautan'] : null,
        isset($isi['gambar']) ? (string) $isi['gambar'] : null,
        $isi['kategori'] ?? 'lainnya',
        $isi['harga'] ?? null
    );

    switch ($hasil['galat'] ?? null) {
        case null:
            http_response_code(201);
            echo json_encode(
                ['ok' => true, 'lapak' => $hasil['lapak']],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            exit;
        case 'tutup':
            http_response_code(409);
            echo json_encode(['galat' => 'Lapak hanya bisa diambil saat acara berlangsung.']);
            exit;
        case 'sudah':
            http_response_code(409);
            echo json_encode(['galat' => 'Kamu sudah punya lapak di acara ini.']);
            exit;
        case 'penuh':
            http_response_code(409);
            echo json_encode(['galat' => 'Semua lapak acara ini sudah terisi. Coba acara berikutnya.']);
            exit;
        case 'nama':
            http_response_code(422);
            echo json_encode(['galat' => 'Nama usaha diisi 3 sampai 60 karakter.']);
            exit;
        case 'tautan':
            http_response_code(422);
            echo json_encode(['galat' => 'Tautan jualan harus diawali https:// dan berisi alamat situs yang benar.']);
            exit;
        case 'gambar':
            http_response_code(422);
            echo json_encode(['galat' => 'Gambar itu tidak tersedia. Pilih salah satu gambar di daftar.']);
            exit;
        default:
            http_response_code(403);
            echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Pasar belum bisa dibaca.']);
}
