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
            created_at TEXT NOT NULL
        )"
    );
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
