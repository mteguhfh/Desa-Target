<?php
// Endpoint data desa untuk Peta Desa 3D (peta3d/index.php).
//
// Ini satu-satunya jalur data untuk tampilan 3D (ADR-0004). Aturan
// penempatan ada di includes/peta-posisi.php, berkas yang sama yang dipakai
// peta 2D; klien 3D tidak menyimpan salinan koordinat apa pun.
//
// Yang dikirim:
//   warga:      [{ nomor, nama, gang }]           urut pendaftaran (id ASC)
//   bangunan:   [{ kavling, nama, gang, komentar, dari, tanggal, jenis,
//                  tautan, x, y }]
//               kavling = indeks urutan id ASC. Posisi (x, y) sudah dihitung
//               server dari POSISI_KAVLING[kavling]; sudut = arah hadap
//               bangunan dalam radian mengikuti sisi kavlingnya.
//   papanGang:  [{ gang, jumlah }] dari helper papan_gang() situs induk.
//   kavling:    [[x, y], ...] seluruh titik jangkar, termasuk yang masih
//               tanah kosong.
//   zonaGang:   { gang: { tambak, papan, awal, dx, dy, kolom, baris,
//                         wara: {x,y,w,h} } }
//               wara = kotak batas jalan warga, sudah dikalikan SKALA_DUNIA
//               dan diberi margin, persis seperti di peta 2D.
//   fokus:      { x, y } titik kamera awal (Balai Desa).
//   kolam:      { x, y, r } elips kolam dalam koordinat dunia.
//   acara:      nama acara pasar yang sedang berlangsung ('pasar-malam' |
//               'cfd') atau null bila tidak ada.
//   namaAcara:  nama panjang acara aktif, mis. 'Pasar Malam'.
//   acaraBerikutnya: { acara, nama, tanggal, mulai, selesai } atau null.
//   lapak:      [{ id, nomor, namaUsaha, tautan, kavling }] lapak acara
//               aktif saja, urut klaim; kavling = urutan tenda.
//   batasLapak: jumlah lapak maksimal acara aktif.
//   versi:      penanda versi dari waktu ubah berkas posisi dan skema,
//               supaya klien bisa membedakan data basi dari data baru.
//
// Sifatnya hanya membaca. Satu-satunya efek samping yang mungkin adalah
// inisialisasi skema + seed Balai Desa oleh db() sendiri, persis seperti
// yang terjadi saat halaman situs mana pun dibuka pertama kali.
// Bila basis data tidak bisa dibaca, endpoint menjawab JSON galat dengan
// status 500 supaya peta 3D menampilkan keadaan kosong, bukan halaman rusak.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../includes/peta-posisi.php';

    $pdo = db();

    // Pusat Pembelajaran (Fitur 5): ringkasan kelas untuk strip tautan
    // cepat di dunia 3D. Konten dan progres tetap dari belajar.php
    // (ADR-0004); di sini hanya slug dan judul.
    $kelasBelajar = [];
    foreach (daftar_kelas($pdo) as $k) {
        $kelasBelajar[] = ['slug' => (string) $k['slug'], 'judul' => (string) $k['judul']];
    }

    $warga = [];
    foreach ($pdo->query('SELECT * FROM warga ORDER BY id ASC') as $baris) {
        $warna = warna_warga($baris);
        $warga[] = [
            'nomor' => nomor_warga((int) $baris['id']),
            'nama' => $baris['nama'],
            'bentuk' => $baris['bentuk_topi'] ?? 'pet',
            'gang' => $baris['gang'],
            // Warna sudah dalam kode hex supaya klien tidak perlu tahu palet.
            'baju' => hex_warna($baris, 'baju'),
            'kulit' => hex_warna($baris, 'kulit'),
            'topi' => hex_warna($baris, 'topi'),
            // Nama warna untuk keterangan di kartu popover.
            'warnaNama' => [
                'baju' => WARNA_BAJU[$warna['baju']]['nama'],
                'kulit' => WARNA_KULIT[$warna['kulit']]['nama'],
                'topi' => WARNA_TOPI[$warna['topi']]['nama'],
            ],
        ];
    }

    $bangunan = [];
    foreach (semua_bangunan_aktif($pdo) as $indeks => $baris) {
        $titik = POSISI_KAVLING[$indeks] ?? null;
        $bangunan[] = [
            'kavling' => $indeks,
            'nama' => $baris['nama'],
            'gang' => $baris['gang'],
            'komentar' => $baris['komentar'],
            'dari' => $baris['diminta_oleh'],
            // Ruko warga (Fitur 3): jenis membedakan dari bangunan komentar,
            // tautan hanya berisi URL https yang sudah diperiksa server.
            'jenis' => (string) ($baris['jenis'] ?? 'komentar'),
            'kategori' => (string) ($baris['kategori'] ?? 'lainnya'),
            'harga' => $baris['harga'] === null ? null : (int) $baris['harga'],
            'tautan' => $baris['tautan'] ?? null,
            // Gambar usaha ruko (jalur relatif assets/img/, sudah disaring
            // server saat disimpan).
            'gambar' => (string) ($baris['gambar'] ?? ''),
            'tanggal' => $baris['tanggal'] ? format_tanggal($baris['tanggal']) : null,
            // Kavling di luar tabel posisi tidak dikirim posisinya: klien
            // menaruhnya di tanah kosong terakhir, bukan menebak sendiri.
            'x' => $titik === null ? null : (int) $titik[0],
            'y' => $titik === null ? null : (int) $titik[1],
        ];
    }

    // Kotak jalan warga dihitung persis seperti $zonaUntukJs di peta 2D.
    $marginZonaX = 14 * SKALA_DUNIA;
    $marginZonaAtas = 18 * SKALA_DUNIA;
    $marginZonaBawah = 12 * SKALA_DUNIA;
    $zonaGang = [];
    foreach (ZONA_GANG as $namaGang => $zona) {
        $tambak = $zona['tambak'];
        $zonaGang[$namaGang] = [
            'tambak' => $tambak,
            'papan' => $zona['papan'],
            'awal' => $zona['awal'],
            'dx' => $zona['dx'],
            'dy' => $zona['dy'],
            'kolom' => $zona['kolom'],
            'baris' => $zona['baris'],
            'wara' => [
                'x' => (int) round($tambak[0] * SKALA_DUNIA + $marginZonaX),
                'y' => (int) round($tambak[1] * SKALA_DUNIA + $marginZonaAtas),
                'w' => (int) round($tambak[2] * SKALA_DUNIA - 2 * $marginZonaX),
                'h' => (int) round($tambak[3] * SKALA_DUNIA - $marginZonaAtas - $marginZonaBawah),
            ],
        ];
    }

    // Pasar Malam dan CFD (Fitur 4): hanya lapak acara yang sedang
    // berlangsung. Di luar jam acara, acara bernilai null dan lapak kosong,
    // jadi peta tidak menggambar tenda sama sekali.
    $pasar = keadaan_pasar($pdo);

    echo json_encode(
        [
            'kelasBelajar' => $kelasBelajar,
            'acara' => $pasar['acara'],
            'namaAcara' => $pasar['namaAcara'],
            'acaraBerikutnya' => $pasar['berikutnya'],
            'lapak' => $pasar['lapak'],
            'batasLapak' => $pasar['batas'],
            'warga' => $warga,
            'bangunan' => $bangunan,
            'papanGang' => papan_gang($pdo),
            'kavling' => POSISI_KAVLING,
            'zonaGang' => $zonaGang,
            'fokus' => ['x' => (int) POSISI_KAVLING[0][0], 'y' => (int) POSISI_KAVLING[0][1]],
            // Elips kolam peta 2D (1065, 718) berjari 112 pada skala lokal.
            'kolam' => [
                'x' => (int) round(1065 * SKALA_DUNIA),
                'y' => (int) round(718 * SKALA_DUNIA),
                'r' => (int) round(112 * SKALA_DUNIA),
            ],
            'versi' => (string) max(
                (int) @filemtime(__DIR__ . '/../includes/peta-posisi.php'),
                (int) @filemtime(__DIR__ . '/../db.php')
            ),
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['galat' => 'Basis data desa belum bisa dibaca.']);
}
