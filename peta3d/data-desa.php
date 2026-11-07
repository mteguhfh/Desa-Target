<?php
// Endpoint data desa untuk Peta Desa 3D (peta3d/index.php).
//
// Membaca basis data SQLite produksi lewat db.php situs induk (folder
// peta3d berada di dalam docroot, jadi jalur relatif ../ benar), lalu
// mengembalikan JSON:
//   warga:     [{ nomor, nama, gang }]            urut pendaftaran (id ASC)
//   bangunan:  [{ kavling, nama, gang, komentar, dari, tanggal }]
//              kavling = indeks urutan id ASC, sama seperti peta.php 2D:
//              bangunan pertama menempati POSISI_KAVLING[0], dan seterusnya.
//              Balai Desa adalah baris seed pertama, jadi ikut terbaca
//              dari basis data seperti bangunan lain, bukan hardcode.
//   papanGang: [{ gang, jumlah }] dari helper papan_gang() situs induk.
//
// Sifatnya hanya membaca. Satu-satunya efek samping yang mungkin adalah
// inisialisasi skema + seed Balai Desa oleh db() sendiri, persis seperti
// yang terjadi saat halaman situs mana pun dibuka pertama kali.
// Bila basis data tidak bisa dibaca, endpoint menjawab JSON galat dengan
// status 500 supaya peta 3D jatuh ke data contoh, bukan halaman rusak.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../db.php';

    $pdo = db();

    $warga = [];
    foreach ($pdo->query('SELECT id, nama, gang FROM warga ORDER BY id ASC') as $baris) {
        $warga[] = [
            'nomor' => nomor_warga((int) $baris['id']),
            'nama' => $baris['nama'],
            'gang' => $baris['gang'],
        ];
    }

    $bangunan = [];
    foreach (semua_bangunan($pdo) as $indeks => $baris) {
        $bangunan[] = [
            'kavling' => $indeks,
            'nama' => $baris['nama'],
            'gang' => $baris['gang'],
            'komentar' => $baris['komentar'],
            'dari' => $baris['diminta_oleh'],
            'tanggal' => $baris['tanggal'] ? format_tanggal($baris['tanggal']) : null,
        ];
    }

    echo json_encode(
        [
            'warga' => $warga,
            'bangunan' => $bangunan,
            'papanGang' => papan_gang($pdo),
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Basis data desa belum bisa dibaca.']);
}
