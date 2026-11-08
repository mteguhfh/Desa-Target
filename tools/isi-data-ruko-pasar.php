<?php
// Alat bantu lokal: mengisi data contoh ruko dan lapak pasar supaya tampilan
// panel, peta 2D/3D, dan halaman Pasar/Ruko bisa dilihat dalam keadaan hidup.
//
// Pakai:
//   php tools/isi-data-ruko-pasar.php
//   php tools/isi-data-ruko-pasar.php --hapus    (buang lagi data contohnya)
//
// Yang dibuat:
//   - tiga warga contoh (kalau belum ada) dengan kartu dan kunci sendiri
//   - tiga ruko warga, masing-masing dengan cerita dan tautan jualan
//   - lapak Pasar Malam dan CFD untuk acara terdekat, diisi nama ruko dan
//     nama usaha tanpa ruko
//
// Semua data diberi penanda "contoh" di cerita/nama supaya mudah dibuang.
// Jalankan hanya di mesin lokal atau sebelum presentasi, bukan di production.
// Ruko dan lapak tidak boleh telanjur masuk ke basis data desa sungguhan.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Alat ini hanya untuk baris perintah.\n");
}

require_once __DIR__ . '/../db.php';

$pdo = db();
$daftarKavling = count(require __DIR__ . '/../includes/kavling.php');
$penanda = 'contoh-tampilan';

if (in_array('--hapus', $argv, true)) {
    $nomorContoh = [];
    foreach ($pdo->query("SELECT id, nama FROM warga WHERE nama LIKE '" . $penanda . "%'") as $w) {
        $nomorContoh[] = nomor_warga((int) $w['id']);
    }
    $hapusBangunan = $pdo->prepare("DELETE FROM bangunan WHERE nomor IN (" . implode(',', array_fill(0, max(1, count($nomorContoh)), '?')) . ")");
    $hapusBangunan->execute($nomorContoh ?: ['-']);
    $hapusLapak = $pdo->prepare("DELETE FROM lapak WHERE nomor IN (" . implode(',', array_fill(0, max(1, count($nomorContoh)), '?')) . ")");
    $hapusLapak->execute($nomorContoh ?: ['-']);
    echo "Data contoh dihapus: " . $hapusBangunan->rowCount() . " ruko, " . $hapusLapak->rowCount() . " lapak.\n";
    exit;
}

// ---------------------------------------------------------------------------
// 1. Warga contoh dengan kartu dan kunci
// ---------------------------------------------------------------------------
$calon = [
    ['Ibu Sari ' . $penanda, 'Gang Kuliner'],
    ['Mas Bima ' . $penanda, 'Gang Jasa'],
    ['Kak Nada ' . $penanda, 'Gang Kerajinan'],
];

$warga = [];
foreach ($calon as [$nama, $gang]) {
    $stmt = $pdo->prepare('SELECT * FROM warga WHERE nama = ?');
    $stmt->execute([$nama]);
    $baris = $stmt->fetch();
    if ($baris === false) {
        $pdo->prepare('INSERT INTO warga (nama, gang, created_at, warna_baju, warna_kulit, warna_topi) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$nama, $gang, date("Y-m-d H:i:s"), WARNA_BAWAAN["baju"], WARNA_BAWAAN["kulit"], WARNA_BAWAAN["topi"]]);
        $baris = cari_warga($pdo, (int) $pdo->lastInsertId());
    }
    // Warga lama bisa saja belum punya kunci (dibuat sebelum kolomnya ada);
    // beri kunci acak dulu supaya kartunya bisa dipakai.
    if ((string) ($baris['kunci'] ?? '') === '') {
        $pdo->prepare('UPDATE warga SET kunci = ? WHERE id = ?')
            ->execute([bin2hex(random_bytes(16)), (int) $baris['id']]);
        $baris = cari_warga($pdo, (int) $baris['id']);
    }
    $warga[] = [
        'nomor' => nomor_warga((int) $baris['id']),
        'kunci' => (string) $baris['kunci'],
        'gang' => (string) $baris['gang'],
    ];
}

// ---------------------------------------------------------------------------
// 2. Ruko contoh
// ---------------------------------------------------------------------------
$rukoContoh = [
    ['Warung Sari Pagi', 'Nasi uduk, ayam geprek, dan kopi tubruk. Buka dari jam 6 pagi sampai habis.', 'https://wa.me/6281200000001', 'assets/img/contoh-warung.png'],
    ['Servis Motor Bima', 'Servis ringan, ganti oli, dan cek rem. Bisa antre lewat WhatsApp dulu.', 'https://wa.me/6281200000002', 'assets/img/contoh-servis.png'],
    ['Rajut Nada', 'Tas dan dompet rajut buatan tangan, bisa pesan warna sendiri.', 'https://wa.me/6281200000003', 'assets/img/contoh-rajut.png'],
];

foreach ($rukoContoh as $i => [$namaUsaha, $cerita, $tautan, $gambar]) {
    $milik = ruko_milik($pdo, $warga[$i]['nomor']);
    if ($milik !== null) {
        // Ruko sudah ada (mis. dari jalannya alat sebelumnya): cukup
        // lengkapi gambarnya.
        ubah_ruko($pdo, $warga[$i]['nomor'], $warga[$i]['kunci'], (string) $milik['nama'], (string) ($milik['komentar'] ?? ''), (string) $milik['tautan'], $gambar);
        echo "Ruko {$namaUsaha}: sudah ada, gambar disesuaikan\n";
        continue;
    }
    $hasil = klaim_ruko($pdo, $warga[$i]['nomor'], $warga[$i]['kunci'], $namaUsaha, $cerita, $tautan, $warga[$i]['gang'], $daftarKavling + 3, $gambar);
    echo "Ruko {$namaUsaha}: " . ($hasil['galat'] ?? 'ok') . "\n";
}

// ---------------------------------------------------------------------------
// 3. Lapak Pasar Malam dan CFD
//
// Lapak hanya bisa diklaim saat acaranya berlangsung, jadi setiap acara
// diklaim dengan waktu acara masing-masing. Pesan gagal "tutup" berarti
// waktu acaranya belum tiba, jadi dilewati saja.
// ---------------------------------------------------------------------------
$mulaiAcara = [];
foreach (ACARA_LAPAK as $kode => $acara) {
    // Tanggal terdekat yang hari-nya cocok, termasuk hari ini.
    for ($i = 0; $i <= 7; $i++) {
        $hari = new DateTimeImmutable("+{$i} day");
        if ((int) $hari->format('N') === (int) $acara['hari']) {
            $mulaiAcara[$kode] = $hari->format('Y-m-d') . ' ' . $acara['mulai'] . ':00';
            break;
        }
    }
}

// Dua pedagang yang sengaja tidak punya ruko, supaya jalur "lapak tanpa
// ruko" (nama usaha + gambar sendiri) ikut terlihat.
$tanggalAcara = [];
foreach ($mulaiAcara as $kode => $waktu) {
    $tanggalAcara[$kode] = substr($waktu, 0, 10);
}

$tanpaRuko = [
    ['acara' => 'pasar-malam', 'nama' => 'Kue Bolu Kukus', 'tautan' => 'https://wa.me/6281200000004', 'gambar' => 'assets/img/contoh-kue.png'],
    ['acara' => 'cfd', 'nama' => 'Es Teh Jumbo', 'tautan' => 'https://wa.me/6281200000005', 'gambar' => 'assets/img/contoh-es.png'],
];

$lengkapLapak = function (PDO $pdo, string $acara, string $nomor, string $nama, string $tautan, string $gambar) {
    $pdo->prepare(
        'INSERT INTO lapak (acara, tanggal, nomor, nama_usaha, tautan, gambar, kavling)
         SELECT ?, ?, ?, ?, ?, ?, COALESCE(MAX(kavling) + 1, 0) FROM lapak WHERE acara = ? AND tanggal = ?'
    )->execute([$acara, $GLOBALS['tanggalAcara'][$acara] ?? date('Y-m-d'), $nomor, $nama, $tautan, $gambar, $acara, $GLOBALS['tanggalAcara'][$acara] ?? date('Y-m-d')]);
};

foreach (array_keys(ACARA_LAPAK) as $kode) {
    $waktu = $mulaiAcara[$kode] ?? null;
    if ($waktu === null) {
        continue;
    }
    foreach ($warga as $w) {
        $hasil = klaim_lapak($pdo, $w['nomor'], $w['kunci'], $kode, $waktu);
        echo "Lapak {$kode} {$w['nomor']}: " . ($hasil['galat'] ?? 'ok') . "\n";
    }
}
// Warga keempat tanpa ruko untuk dua lapak ini.
$namaTanpaRuko = 'Pak Tarno ' . $penanda;
$stmt = $pdo->prepare('SELECT * FROM warga WHERE nama = ?');
$stmt->execute([$namaTanpaRuko]);
$baris4 = $stmt->fetch();
if ($baris4 === false) {
    $pdo->prepare('INSERT INTO warga (nama, gang, created_at, warna_baju, warna_kulit, warna_topi) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$namaTanpaRuko, 'Gang Dagang', date('Y-m-d H:i:s'), WARNA_BAWAAN['baju'], WARNA_BAWAAN['kulit'], WARNA_BAWAAN['topi']]);
    $baris4 = cari_warga($pdo, (int) $pdo->lastInsertId());
}
if ((string) ($baris4['kunci'] ?? '') === '') {
    $pdo->prepare('UPDATE warga SET kunci = ? WHERE id = ?')->execute([bin2hex(random_bytes(16)), (int) $baris4['id']]);
    $baris4 = cari_warga($pdo, (int) $baris4['id']);
}
$wTanpa = ['nomor' => nomor_warga((int) $baris4['id']), 'kunci' => (string) $baris4['kunci']];

foreach ($tanpaRuko as $l) {
    $ada = $pdo->prepare('SELECT COUNT(*) FROM lapak WHERE acara = ? AND tanggal = ? AND nomor = ?');
    $ada->execute([$l['acara'], $tanggalAcara[$l['acara']] ?? date('Y-m-d'), $wTanpa['nomor']]);
    if ((int) $ada->fetchColumn() > 0) {
        echo "Lapak {$l['acara']} {$l['nama']}: sudah\n";
        continue;
    }
    $hasil = klaim_lapak($pdo, $wTanpa['nomor'], $wTanpa['kunci'], $l['acara'], $mulaiAcara[$l['acara']] ?? null, $l['nama'], $l['tautan'], $l['gambar']);
    echo "Lapak {$l['acara']} {$l['nama']}: " . ($hasil['galat'] ?? 'ok') . "\n";
}
$warga[] = ['nomor' => $wTanpa['nomor'], 'kunci' => $wTanpa['kunci'], 'gang' => 'Gang Dagang'];

echo "\nAkun contoh untuk mencoba di peramban:\n";
foreach ($warga as $w) {
    echo "  {$w['nomor']}  kunci {$w['kunci']}  ({$w['gang']})\n";
}
echo "Isi di console peramban:\n";
echo "  localStorage.setItem('dt_nomor', '{$warga[0]['nomor']}'); localStorage.setItem('dt_kunci', '{$warga[0]['kunci']}');\n";
