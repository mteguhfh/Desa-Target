<?php
// Isi basis data LOKAL dengan data contoh supaya peta terlihat hidup saat
// mengembangkan tampilan: banyak warga di tiap gang, bangunan mengisi sebagian
// kavling, dan misi harian ada yang sudah selesai.
//
// JANGAN dijalankan di production. Skrip ini menolak berjalan kalau
// APP_BASE_URL menunjuk ke alamat publik.
//
//   php tools/isi-data-contoh.php            -> isi data contoh
//   php tools/isi-data-contoh.php --hapus    -> kosongkan warga & bangunan
//                                               tambahan (Balai Desa tetap)
//
// Data warga sengaja dibuat fiktif dan umum; tidak ada nama orang sungguhan.

require_once __DIR__ . '/../db.php';

if ($argc > 1 && in_array('--hapus', $argv, true)) {
    $pdo = db();
    $pdo->exec('DELETE FROM warga');
    $pdo->exec("DELETE FROM bangunan WHERE id > 1");
    $pdo->exec('DELETE FROM misi_selesai');
    $pdo->exec('DELETE FROM poin_log');
    $pdo->exec('DELETE FROM klaim_harian');
    echo "Data contoh dihapus. Balai Desa dipertahankan.\n";
    exit(0);
}

if (!preg_match('#localhost|127\.0\.0\.1|\.test#', APP_BASE_URL)) {
    fwrite(STDERR, "Ditolak: APP_BASE_URL = " . APP_BASE_URL . " bukan alamat lokal.\n");
    fwrite(STDERR, "Skrip ini hanya untuk basis data pengembangan.\n");
    exit(1);
}

$pdo = db();

// --- Warga -----------------------------------------------------------------
// Disebar merata ke lima gang supaya papan peringkat dan zona gang terisi.
$namaWarga = [
    ['Sari Wulandari', 'Gang Dagang'], ['Budi Santoso', 'Gang Dagang'], ['Rina Kusuma', 'Gang Dagang'],
    ['Agus Prakoso', 'Gang Dagang'], ['Dewi Lestari', 'Gang Dagang'], ['Hendra Gunawan', 'Gang Dagang'],
    ['Maya Anggraini', 'Gang Dagang'], ['Rizky Ramadhan', 'Gang Dagang'],

    ['Dedi Kurniawan', 'Gang Kuliner'], ['Fitri Handayani', 'Gang Kuliner'], ['Joko Susilo', 'Gang Kuliner'],
    ['Anisa Putri', 'Gang Kuliner'], ['Bayu Setiawan', 'Gang Kuliner'], ['Nur Aisyah', 'Gang Kuliner'],
    ['Wahyu Hidayat', 'Gang Kuliner'],

    ['Slamet Riyadi', 'Gang Jasa'], ['Yuni Astuti', 'Gang Jasa'], ['Teguh Prasetyo', 'Gang Jasa'],
    ['Indah Permata', 'Gang Jasa'], ['Bambang Sutrisno', 'Gang Jasa'], ['Lilis Suryani', 'Gang Jasa'],

    ['Rian Aditya', 'Gang Freelancer'], ['Sarah Nabila', 'Gang Freelancer'], ['Fajar Nugroho', 'Gang Freelancer'],
    ['Intan Maharani', 'Gang Freelancer'], ['Gilang Ramadhan', 'Gang Freelancer'], ['Ayu Lestari', 'Gang Freelancer'],

    ['Siti Aminah', 'Gang Kerajinan'], ['Ahmad Fauzi', 'Gang Kerajinan'], ['Ratna Sari', 'Gang Kerajinan'],
    ['Eko Purnomo', 'Gang Kerajinan'], ['Wulan Sari', 'Gang Kerajinan'],
];

$pdo->exec('DELETE FROM warga');
$stmt = $pdo->prepare(
    'INSERT INTO warga (nama, gang, created_at, warna_baju, warna_kulit, warna_topi) VALUES (?, ?, ?, ?, ?, ?)'
);
$hariIni = new DateTimeImmutable('today');
// Acak yang bisa diulang: setiap kali data contoh diisi ulang, hasilnya sama.
// Tanpa ini, tiap isi ulang menghasilkan desa yang berbeda dan uji jadi goyah.
mt_srand(20261108);
$acak = static fn (): float => mt_rand(0, 100000) / 100000;
foreach ($namaWarga as $i => [$nama, $gang]) {
    // Tanggal daftar mundur beberapa hari supaya terlihat bervariasi.
    $tanggal = $hariIni->modify('-' . (count($namaWarga) - $i) . ' days')->format('Y-m-d H:i:s');
    $warna = warna_acak($acak);
    $stmt->execute([$nama, $gang, $tanggal, $warna['baju'], $warna['kulit'], $warna['topi']]);
}

// --- Bangunan --------------------------------------------------------------
// Mengisi 8 dari 12 kavling. Sisanya sengaja dibiarkan kosong supaya penanda
// "Tanah Kosong" tetap terlihat dan desa terasa masih berkembang.
$pdo->exec('DELETE FROM bangunan WHERE id > 1');

$bangunanContoh = [
    ['Warung Kopi Senja', 'Gang Kuliner', 'Sari Wulandari', 'Bangun warung kopi dong, Bang! Biar bisa nongkrong sambil kerja.', '-3 days'],
    ['Bengkel Motor Berkah', 'Gang Jasa', 'Slamet Riyadi', 'Bengkel motor yang buka sampai malam, banyak yang butuh.', '-3 days'],
    ['Toko Kue Bu Fitri', 'Gang Kuliner', 'Fitri Handayani', 'Toko kue kering buat hampers lebaran, Bang.', '-2 days'],
    ['Jasa Desain Kilat', 'Gang Freelancer', 'Rian Aditya', 'Butuh tempat buat desainer freelance ngumpul dan terima order.', '-2 days'],
    ['Kerajinan Anyaman Bambu', 'Gang Kerajinan', 'Siti Aminah', 'Pengrajin anyaman bambu dari kampung kami, Bang Uju.', '-1 day'],
    ['Laundry Kiloan Bersih', 'Gang Jasa', 'Yuni Astuti', 'Laundry kiloan yang antar jemput, banyak mahasiswa butuh.', '-1 day'],
    ['Toko Bangunan Pak Budi', 'Gang Dagang', 'Budi Santoso', 'Toko bangunan lengkap, dari semen sampai cat.', 'today'],
];

$stmtB = $pdo->prepare(
    'INSERT INTO bangunan (nama, gang, diminta_oleh, komentar, tanggal) VALUES (?, ?, ?, ?, ?)'
);
foreach ($bangunanContoh as [$nama, $gang, $peminta, $komentar, $kapan]) {
    $stmtB->execute([$nama, $gang, $peminta, $komentar, $hariIni->modify($kapan)->format('Y-m-d')]);
}

// --- Misi dan dompet poin ---------------------------------------------------
// Satu misi ditandai selesai untuk warga pertama supaya tampilan "sudah
// selesai" dan saldo poin per warga ikut terlihat. Warga lain tetap kosong,
// jadi isolasi antar warga juga kelihatan di data contoh.
$pdo->exec('DELETE FROM misi_selesai');
$pdo->exec('DELETE FROM poin_log');
$pdo->exec('DELETE FROM klaim_harian');
$pdo->exec('UPDATE warga SET poin = 0');
$wargaPertama = $pdo->query('SELECT id FROM warga ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($wargaPertama !== false) {
    tandai_misi($pdo, 'keliling-desa', nomor_warga((int) $wargaPertama));
}

echo "Data contoh terisi:\n";
echo "  warga    : " . hitung_warga($pdo) . "\n";
echo "  bangunan : " . hitung_bangunan($pdo) . "\n";
$r = ringkasan_misi(daftar_misi($pdo, $wargaPertama === false ? null : nomor_warga((int) $wargaPertama)));
echo "  misi     : " . $r['selesai'] . '/' . $r['total'] . " selesai hari ini\n";
echo "  poin     : " . $pdo->query('SELECT COALESCE(SUM(poin), 0) FROM warga')->fetchColumn() . " poin tersebar\n";
