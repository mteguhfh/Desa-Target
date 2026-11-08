<?php
// Koneksi dan inisialisasi database SQLite Desa Target.
// File database dibuat otomatis di data/desa-target.sqlite pada pemakaian pertama.

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/desa-target.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA busy_timeout = 5000');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS warga (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            gang TEXT NOT NULL,
            created_at TEXT NOT NULL,
            warna_baju TEXT,
            warna_kulit TEXT,
            warna_topi TEXT
        )"
    );

    // Warga yang mendaftar sebelum pilihan warna ada: tambahkan kolomnya ke
    // tabel lama tanpa menghapus data. ALTER TABLE ADD COLUMN aman diulang,
    // jadi cukup periksa daftar kolom dulu.
    $kolomWarga = [];
    foreach ($pdo->query('PRAGMA table_info(warga)') as $kolom) {
        $kolomWarga[$kolom['name']] = true;
    }
    foreach (['warna_baju', 'warna_kulit', 'warna_topi'] as $kolomBaru) {
        if (!isset($kolomWarga[$kolomBaru])) {
            $pdo->exec('ALTER TABLE warga ADD COLUMN ' . $kolomBaru . ' TEXT');
        }
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bangunan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama TEXT NOT NULL,
            gang TEXT,
            diminta_oleh TEXT,
            komentar TEXT,
            tanggal TEXT NOT NULL
        )"
    );

    // Misi harian. Satu baris per (kode misi, tanggal), jadi menandai misi
    // selesai untuk hari ini tidak menghapus riwayat kemarin. Kodenya dipakai
    // di kode PHP, judulnya dibaca dari tabel ini supaya bisa diubah tanpa
    // mengubah program.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS misi (
            kode TEXT PRIMARY KEY,
            judul TEXT NOT NULL,
            keterangan TEXT NOT NULL,
            urutan INTEGER NOT NULL DEFAULT 0
        )"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS misi_selesai (
            kode TEXT NOT NULL,
            tanggal TEXT NOT NULL,
            PRIMARY KEY (kode, tanggal)
        )"
    );
    $daftarMisi = [
        ['usulkan-usaha', 'Usulkan satu usaha impianmu', 'Komentari usaha yang ingin kamu dirikan di desa di postingan Instagram desa. Satu komentar saja sudah cukup.', 1],
        ['dukung-teman', 'Dukung komentar warga lain', 'Sukai komentar warga lain yang idenya bagus. Like terbanyak besok pagi dibangun jadi bangunan.', 2],
        ['bikin-ktp', 'Tinggalkan namamu di desa', 'Bikin KTP Warga supaya namamu berdiri di peta sebagai warga resmi Desa Target.', 3],
        ['keliling-desa', 'Keliling desa sekali', 'Buka peta desa, tekan Keliling Desa, dan jalankan Kamu sampai melihat Balai Desa dari dekat.', 4],
    ];
    $stmtMisi = $pdo->prepare(
        'INSERT INTO misi (kode, judul, keterangan, urutan) VALUES (?, ?, ?, ?)
         ON CONFLICT(kode) DO UPDATE SET judul = excluded.judul, keterangan = excluded.keterangan, urutan = excluded.urutan'
    );
    foreach ($daftarMisi as $m) {
        $stmtMisi->execute($m);
    }

    // Seed bangunan pertama: Balai Desa, kantornya Bang Uju.
    $jumlahBangunan = (int) $pdo->query('SELECT COUNT(*) FROM bangunan')->fetchColumn();
    if ($jumlahBangunan === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO bangunan (nama, gang, diminta_oleh, komentar, tanggal) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'Balai Desa (Kantor Desa Kejar Target)',
            'Pusat Desa',
            'Bang Uju',
            'Bangunan pertama Desa Target. Di sini warga mengurus surat, invoice, dan dokumen usaha lewat Kejar Target.',
            date('Y-m-d'),
        ]);
    }

    return $pdo;
}

// Nomor warga resmi, contoh: DT-0001, DT-0027, DT-10342.
function nomor_warga(int $id): string
{
    return 'DT-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

// Format tanggal panjang Bahasa Indonesia, contoh: 7 November 2026.
function format_tanggal(string $tanggal): string
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $ts = strtotime($tanggal);
    if ($ts === false) {
        return $tanggal;
    }
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function hitung_warga(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM warga')->fetchColumn();
}

function hitung_bangunan(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM bangunan')->fetchColumn();
}

// Jumlah warga per gang untuk papan gang. Semua gang selalu tampil,
// termasuk yang masih nol warga. Urut: jumlah terbanyak dulu.
function papan_gang(PDO $pdo): array
{
    $hitung = [];
    foreach ($pdo->query('SELECT gang, COUNT(*) AS jumlah FROM warga GROUP BY gang') as $row) {
        $hitung[$row['gang']] = (int) $row['jumlah'];
    }
    $papan = [];
    foreach (GANG_WARGA as $gang) {
        $papan[] = ['gang' => $gang, 'jumlah' => $hitung[$gang] ?? 0];
    }
    // Kelompok lain di luar lima gang (kalau suatu saat ada) tetap ditampilkan.
    foreach ($hitung as $gang => $jumlah) {
        if (!in_array($gang, GANG_WARGA, true)) {
            $papan[] = ['gang' => $gang, 'jumlah' => $jumlah];
        }
    }
    usort($papan, fn($a, $b) => $b['jumlah'] <=> $a['jumlah']);
    return $papan;
}

function semua_bangunan(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM bangunan ORDER BY id ASC')->fetchAll();
}

function bangunan_terbaru(PDO $pdo): ?array
{
    $row = $pdo->query('SELECT * FROM bangunan ORDER BY id DESC LIMIT 1')->fetch();
    return $row === false ? null : $row;
}

function cari_warga(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM warga WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

// Cari warga berdasarkan nama ATAU nomor warga.
//
// Warga mengetik nama seperti "sari", atau nomor seperti "DT-0007" atau
// "7" saja. Ketiga bentuk itu harus menemukan orang yang sama, karena nomor
// di kartu mereka tertulis DT-0007.
//
// Hasil diurutkan: cocok persis nama dulu, lalu yang namanya diawali kata
// kunci, lalu sisanya. Di dalam kelompok yang sama, pendaftar terlama dulu.
// Batas $batas menjaga query tetap ringan walau warga desa nanti ribuan.
// ---------------------------------------------------------------------------
// Warna warga
//
// Warga memilih baju, kulit, dan topi saat bikin KTP. Yang disimpan adalah
// KUNCINYA ('biru', 'sawo'), bukan kode warna, supaya palet bisa diperbaiki
// tanpa mengubah data warga. Kolom lama yang kosong atau berisi kunci yang
// tidak dikenal jatuh ke warna bawaan.
// ---------------------------------------------------------------------------

// Kunci yang sah untuk satu bagian, atau null bila tidak dikenal.
function warna_sah(string $bagian, $nilai): ?string
{
    $palet = ['baju' => WARNA_BAJU, 'kulit' => WARNA_KULIT, 'topi' => WARNA_TOPI][$bagian] ?? [];
    return is_string($nilai) && isset($palet[$nilai]) ? $nilai : null;
}

// Kunci warna yang akhirnya dipakai untuk seorang warga (selalu sah).
function warna_warga(array $warga): array
{
    $hasil = [];
    foreach (['baju', 'kulit', 'topi'] as $bagian) {
        $tersimpan = $warga['warna_' . $bagian] ?? null;
        $hasil[$bagian] = warna_sah($bagian, $tersimpan) ?: WARNA_BAWAAN[$bagian];
    }
    return $hasil;
}

// Kode hex untuk satu bagian warna warga.
function hex_warna(array $warga, string $bagian): string
{
    $palet = ['baju' => WARNA_BAJU, 'kulit' => WARNA_KULIT, 'topi' => WARNA_TOPI][$bagian] ?? [];
    $kunci = warna_warga($warga)[$bagian];
    return $palet[$kunci]['hex'] ?? '#64748b';
}

// Acak tiga warna yang berbeda-beda gaya, untuk data contoh. Memakai
// generator acak yang bisa diulang supaya tampilan uji tetap sama.
function warna_acak(callable $acak): array
{
    $pilih = static function (array $palet) use ($acak): string {
        $kunci = array_keys($palet);
        return $kunci[(int) floor($acak() * count($kunci)) % count($kunci)];
    };
    return [
        'baju' => $pilih(WARNA_BAJU),
        'kulit' => $pilih(WARNA_KULIT),
        'topi' => $pilih(WARNA_TOPI),
    ];
}

function cari_warga_berdasarkan(PDO $pdo, string $kunci, int $batas = 40): array
{
    $kunci = trim($kunci);
    if ($kunci === '') {
        return [];
    }

    // Nomor warga: "DT-0007", "dt-7", atau "7" -> id 7.
    $idDariNomor = null;
    if (preg_match('/^(?:dt)?[\s-]*0*(\d{1,9})$/i', $kunci, $cocok)) {
        $idDariNomor = (int) $cocok[1];
    }

    $pola = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $kunci) . '%';

    $stmt = $pdo->prepare(
        "SELECT * FROM warga
         WHERE nama LIKE :pola ESCAPE '\\'" . ($idDariNomor !== null ? ' OR id = :id' : '') . "
         ORDER BY
             CASE WHEN LOWER(nama) = LOWER(:kunci) THEN 0
                  WHEN LOWER(nama) LIKE LOWER(:awal) ESCAPE '\\' THEN 1
                  ELSE 2 END,
             id ASC
         LIMIT :batas"
    );
    $stmt->bindValue(':pola', $pola, PDO::PARAM_STR);
    $stmt->bindValue(':kunci', $kunci, PDO::PARAM_STR);
    $stmt->bindValue(':awal', str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $kunci) . '%', PDO::PARAM_STR);
    $stmt->bindValue(':batas', $batas, PDO::PARAM_INT);
    if ($idDariNomor !== null) {
        $stmt->bindValue(':id', $idDariNomor, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------------
// Misi harian
//
// Misi dihitung dari tindakan nyata yang sudah tercatat di basis data, bukan
// dari tombol "selesai" yang bisa diklik bebas. Aturannya:
//   usulkan-usaha : sudah ada bangunan yang berdiri hari ini
//   dukung-teman  : ada minimal dua bangunan (berarti ada ide yang didukung
//                   lalu dipilih untuk dibangun)
//   bikin-ktp     : ada warga terdaftar
//   keliling-desa : ditandai bila pengunjung menekan Keliling Desa hari ini
//
// Baris di misi_selesai hanya dipakai untuk yang tidak bisa dibuktikan dari
// data (keliling-desa). Sisanya dihitung langsung, jadi tidak ada angka
// yang bisa berbeda dengan kenyataan desa.
// ---------------------------------------------------------------------------

function daftar_misi(PDO $pdo): array
{
    $hariIni = date('Y-m-d');

    $bangunanHariIni = (int) $pdo->query(
        'SELECT COUNT(*) FROM bangunan WHERE tanggal = ' . $pdo->quote($hariIni)
    )->fetchColumn();
    $totalBangunan = hitung_bangunan($pdo);
    $totalWarga = hitung_warga($pdo);

    $buktiOtomatis = [
        'usulkan-usaha' => $bangunanHariIni > 0,
        'dukung-teman' => $totalBangunan >= 2,
        'bikin-ktp' => $totalWarga > 0,
    ];

    $ditandai = [];
    $stmt = $pdo->prepare('SELECT kode FROM misi_selesai WHERE tanggal = ?');
    $stmt->execute([$hariIni]);
    foreach ($stmt->fetchAll() as $r) {
        $ditandai[$r['kode']] = true;
    }

    $hasil = [];
    foreach ($pdo->query('SELECT kode, judul, keterangan, urutan FROM misi ORDER BY urutan ASC') as $m) {
        $selesai = $buktiOtomatis[$m['kode']] ?? isset($ditandai[$m['kode']]);
        $hasil[] = [
            'kode' => $m['kode'],
            'judul' => $m['judul'],
            'keterangan' => $m['keterangan'],
            'selesai' => $selesai,
        ];
    }
    return $hasil;
}

// Tandai satu misi selesai hari ini. Hanya boleh untuk kode yang memang ada.
function tandai_misi(PDO $pdo, string $kode): bool
{
    $ada = $pdo->prepare('SELECT 1 FROM misi WHERE kode = ?');
    $ada->execute([$kode]);
    if ($ada->fetchColumn() === false) {
        return false;
    }
    $stmt = $pdo->prepare('INSERT INTO misi_selesai (kode, tanggal) VALUES (?, ?) ON CONFLICT(kode, tanggal) DO NOTHING');
    $stmt->execute([$kode, date('Y-m-d')]);
    return true;
}

function ringkasan_misi(array $daftar): array
{
    $selesai = 0;
    foreach ($daftar as $m) {
        if ($m['selesai']) {
            $selesai++;
        }
    }
    return ['selesai' => $selesai, 'total' => count($daftar)];
}
