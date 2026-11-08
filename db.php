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
    foreach (kolom_tabel($pdo, 'warga') as $namaKolom => $_) {
        $kolomWarga[$namaKolom] = true;
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

    // Misi harian. Satu baris per (kode misi, tanggal, nomor warga), jadi
    // menandai misi selesai untuk hari ini tidak menghapus riwayat kemarin
    // dan tidak tercampur dengan warga lain. Kodenya dipakai di kode PHP,
    // judulnya dibaca dari tabel ini supaya bisa diubah tanpa mengubah
    // program.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS misi (
            kode TEXT PRIMARY KEY,
            judul TEXT NOT NULL,
            keterangan TEXT NOT NULL,
            urutan INTEGER NOT NULL DEFAULT 0
        )"
    );
    // Misi dulu dicatat GLOBAL per (kode, tanggal) tanpa tahu warga mana yang
    // menyelesaikannya. Sekarang per warga, dikunci nomor kartunya (DT-0001).
    // Tabel lama dibangun ulang menjadi misi_selesai_baru lalu ditukar, supaya
    // kunci utamanya benar-benar (kode, tanggal, nomor) dan baris lama tetap
    // utuh dengan nomor kosong sebagai riwayat global. Kalau migrasinya
    // terputus di tengah jalan, jalan berikutnya menyelesaikannya.
    $kolomMisiSelesai = kolom_tabel($pdo, 'misi_selesai');
    if (!isset($kolomMisiSelesai['nomor'])) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS misi_selesai_baru (
                kode TEXT NOT NULL,
                tanggal TEXT NOT NULL,
                nomor TEXT NOT NULL DEFAULT '',
                PRIMARY KEY (kode, tanggal, nomor)
            )"
        );
        $sisa = (int) $pdo->query('SELECT COUNT(*) FROM misi_selesai')->fetchColumn();
        $sudah = (int) $pdo->query('SELECT COUNT(*) FROM misi_selesai_baru')->fetchColumn();
        if ($sisa > 0 && $sudah === 0) {
            $pdo->exec("INSERT OR IGNORE INTO misi_selesai_baru (kode, tanggal, nomor) SELECT kode, tanggal, '' FROM misi_selesai");
        }
        $pdo->exec('DROP TABLE misi_selesai');
        $pdo->exec('ALTER TABLE misi_selesai_baru RENAME TO misi_selesai');
    }
    // Poin yang diberikan satu misi. Tabel misi dibuat sebelum kolom ini ada,
    // jadi kolomnya ditambahkan belakangan.
    $kolomMisi = kolom_tabel($pdo, 'misi');
    if (!isset($kolomMisi['poin'])) {
        $pdo->exec('ALTER TABLE misi ADD COLUMN poin INTEGER NOT NULL DEFAULT ' . (int) POIN_PER_MISI);
    }

    // Dompet poin warga. Saldo tinggal di kolom warga.poin supaya kartu warga
    // cepat dibaca; setiap perubahannya wajib juga tercatat di poin_log
    // lewat tambah_poin(), jadi nilai kolom dan riwayat tidak bisa berbeda.
    $kolomWarga = kolom_tabel($pdo);
    if (!isset($kolomWarga['poin'])) {
        $pdo->exec('ALTER TABLE warga ADD COLUMN poin INTEGER NOT NULL DEFAULT 0');
    }
    // Kunci rahasia warga. Nomor KTP mudah ditebak (DT-0001, DT-0002, ...),
    // jadi nomor saja tidak cukup untuk membuktikan si pengunjung memang
    // pemilik kartu itu. Pengunjung menyimpan nomor DAN kuncinya di
    // localStorage; endpoint tulis memeriksa keduanya.
    if (!isset($kolomWarga['kunci'])) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN kunci TEXT NOT NULL DEFAULT ''");
    }
    // Warga lama belum punya kunci: beri masing-masing satu kunci acak.
    $tanpaKunci = $pdo->query("SELECT id FROM warga WHERE kunci = ''")->fetchAll(PDO::FETCH_COLUMN);
    if ($tanpaKunci !== []) {
        $isiKunci = $pdo->prepare('UPDATE warga SET kunci = ? WHERE id = ?');
        foreach ($tanpaKunci as $idTanpaKunci) {
            $isiKunci->execute([bin2hex(random_bytes(16)), (int) $idTanpaKunci]);
        }
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS poin_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor TEXT NOT NULL,
            delta INTEGER NOT NULL,
            sumber TEXT NOT NULL,
            ref TEXT,
            tanggal TEXT NOT NULL,
            dibuat_pada TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_poin_log_nomor ON poin_log (nomor, id)');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS klaim_harian (
            nomor TEXT NOT NULL,
            tanggal TEXT NOT NULL,
            PRIMARY KEY (nomor, tanggal)
        )"
    );

    // Chat antar gang: publik, satu kanal umum dan satu kanal per gang.
    // Isinya hanya kode frase preset dari CHAT_FRASE (config.php), bukan teks
    // bebas. nama dan gang disalin dari kartu warga saat pesan dikirim,
    // supaya riwayat lama tetap terbaca walau datanya berubah kemudian.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chat_pesan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kanal TEXT NOT NULL,
            nomor TEXT NOT NULL,
            nama TEXT NOT NULL,
            gang TEXT NOT NULL,
            frase TEXT NOT NULL,
            dibuat_pada TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_chat_kanal_id ON chat_pesan (kanal, id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_chat_nomor ON chat_pesan (nomor, id)');

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

// Daftar kolom satu tabel: ['nama_kolom' => true]. Dipakai migrasi supaya
// ALTER TABLE hanya jalan bila kolomnya memang belum ada.
function kolom_tabel(PDO $pdo, string $tabel = 'warga'): array
{
    $hasil = [];
    foreach ($pdo->query('PRAGMA table_info(' . $tabel . ')') as $kolom) {
        $hasil[$kolom['name']] = true;
    }
    return $hasil;
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
// Misi harian dan dompet poin warga
//
// Setiap warga punya saldo poin sendiri (kolom warga.poin) dan riwayatnya
// (tabel poin_log). Poin hanya berubah lewat tambah_poin(), yang menulis
// saldo dan log dalam satu transaksi, jadi tidak ada saldo yang bisa berbeda
// dari riwayatnya.
//
// Misi sekarang dicatat PER WARGA. Bukti otomatis desa (bangunan hari ini,
// jumlah bangunan, jumlah warga) berlaku untuk semua warga; sisanya, seperti
// keliling-desa, hanya berlaku bagi warga yang menandainya di misi_selesai.
// Poin misi diberikan sekali per warga per misi per hari.
// ---------------------------------------------------------------------------

// Nomor KTP yang sah: DT-0001. Huruf besar saja, karena begitulah nomor
// dicetak di kartu; server membuatnya sendiri dan tidak menerima variasi.
function nomor_warga_sah($nomor): ?string
{
    return is_string($nomor) && preg_match('/^DT-\d{4,9}$/', $nomor) === 1 ? $nomor : null;
}

// Baris warga dari nomor KTP, atau null bila tidak ada.
function warga_dari_nomor(PDO $pdo, string $nomor): ?array
{
    if (nomor_warga_sah($nomor) === null) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM warga WHERE id = ?');
    $stmt->execute([(int) substr($nomor, 3)]);
    $baris = $stmt->fetch();
    // Pastikan baris yang terbaca memang warga pemilik nomor itu.
    if ($baris === false || nomor_warga((int) $baris['id']) !== $nomor) {
        return null;
    }
    return $baris;
}

// Nomor dan kunci rahasia milik warga, atau null bila salah satunya tidak
// cocok. Kunci tidak pernah dikirim dalam bentuk apa pun selain ke pemilik
// kartunya sendiri (lihat ktp.php).
function warga_dari_nomor_dan_kunci(PDO $pdo, $nomor, $kunci): ?array
{
    if (!is_string($kunci) || preg_match('/^[0-9a-f]{32}$/', $kunci) !== 1) {
        return null;
    }
    $warga = warga_dari_nomor($pdo, is_string($nomor) ? $nomor : '');
    if ($warga === null || !hash_equals((string) $warga['kunci'], $kunci)) {
        return null;
    }
    return $warga;
}

// Saldo poin seorang warga. Nomor tidak dikenal = 0.
function saldo_poin(PDO $pdo, string $nomor): int
{
    $warga = warga_dari_nomor($pdo, $nomor);
    return $warga === null ? 0 : (int) $warga['poin'];
}

// Tambah (atau kurangi, bila delta negatif) poin warga lalu catat di
// poin_log. Dua tulisan ini satu transaksi supaya tidak ada saldo yang
// berubah tanpa riwayat. Mengembalikan saldo baru, atau null bila warga
// tidak dikenal atau transaksinya gagal.
function tambah_poin(PDO $pdo, string $nomor, int $delta, string $sumber, ?string $ref = null): ?int
{
    $warga = warga_dari_nomor($pdo, $nomor);
    if ($warga === null) {
        return null;
    }
    // Sumber berasal dari kode program, bukan dari klien; tetap dijaga.
    if (!in_array($sumber, ['klaim-harian', 'misi', 'materi', 'admin'], true)) {
        return null;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE warga SET poin = poin + ? WHERE id = ?')
            ->execute([$delta, (int) $warga['id']]);
        $pdo->prepare(
            'INSERT INTO poin_log (nomor, delta, sumber, ref, tanggal) VALUES (?, ?, ?, ?, ?)'
        )->execute([$nomor, $delta, $sumber, $ref, date('Y-m-d')]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return null;
    }

    return saldo_poin($pdo, $nomor);
}

// Riwayat poin terbaru seorang warga, paling banyak $batas baris.
function riwayat_poin(PDO $pdo, string $nomor, int $batas = 20): array
{
    if (warga_dari_nomor($pdo, $nomor) === null) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT delta, sumber, ref, tanggal FROM poin_log WHERE nomor = ? ORDER BY id DESC LIMIT ' . max(1, min(50, $batas))
    );
    $stmt->execute([$nomor]);
    $hasil = [];
    foreach ($stmt->fetchAll() as $r) {
        $hasil[] = [
            'delta' => (int) $r['delta'],
            'sumber' => $r['sumber'],
            'ref' => $r['ref'],
            'tanggal' => $r['tanggal'],
        ];
    }
    return $hasil;
}

// Apakah warga ini sudah mengklaim poin harian pada tanggal itu.
function sudah_klaim_harian(PDO $pdo, string $nomor, string $tanggal): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM klaim_harian WHERE nomor = ? AND tanggal = ?');
    $stmt->execute([$nomor, $tanggal]);
    return $stmt->fetchColumn() !== false;
}

// Klaim poin harian gratis. Mengembalikan ['saldo' => n] bila berhasil, atau
// ['galat' => 'sudah'] bila hari itu sudah diklaim, ['galat' => 'warga'] bila
// nomornya tidak dikenal. Tanggal boleh diisi untuk uji; kosong = hari ini.
function klaim_harian(PDO $pdo, string $nomor, ?string $tanggal = null): array
{
    if (warga_dari_nomor($pdo, $nomor) === null) {
        return ['galat' => 'warga'];
    }
    $tanggal = $tanggal ?? date('Y-m-d');

    $pdo->beginTransaction();
    try {
        // Kunci utama (nomor, tanggal) membuat klaim kedua gagal di tingkat
        // basis data, bukan hanya hasil pemeriksaan sebelumnya.
        $pdo->prepare('INSERT INTO klaim_harian (nomor, tanggal) VALUES (?, ?)')
            ->execute([$nomor, $tanggal]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['galat' => 'sudah'];
    }

    $saldo = tambah_poin($pdo, $nomor, POIN_KLAIM_HARIAN, 'klaim-harian', $tanggal);
    return $saldo === null ? ['galat' => 'gagal'] : ['saldo' => $saldo];
}

// Daftar misi hari ini, dengan status selesai untuk warga $nomor (bila
// diisi). Tanpa nomor, status dihitung dari bukti otomatis desa saja.
function daftar_misi(PDO $pdo, ?string $nomor = null): array
{
    $hariIni = date('Y-m-d');
    $nomor = $nomor !== null ? nomor_warga_sah($nomor) : null;
    $warga = $nomor !== null ? warga_dari_nomor($pdo, $nomor) : null;
    if ($warga === null) {
        $nomor = null;
    }

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
    if ($nomor !== null) {
        $stmt = $pdo->prepare('SELECT kode FROM misi_selesai WHERE tanggal = ? AND nomor = ?');
        $stmt->execute([$hariIni, $nomor]);
        foreach ($stmt->fetchAll() as $r) {
            $ditandai[$r['kode']] = true;
        }
    }

    $hasil = [];
    foreach ($pdo->query('SELECT kode, judul, keterangan, poin, urutan FROM misi ORDER BY urutan ASC') as $m) {
        $selesai = $buktiOtomatis[$m['kode']] ?? isset($ditandai[$m['kode']]);
        $hasil[] = [
            'kode' => $m['kode'],
            'judul' => $m['judul'],
            'keterangan' => $m['keterangan'],
            'poin' => (int) $m['poin'],
            'selesai' => $selesai,
        ];
    }
    return $hasil;
}

// Tandai satu misi selesai hari ini untuk seorang warga, lalu beri poin
// misi itu SEKALI per warga per hari. Mengembalikan:
//   ['ok' => true, 'baru' => bool, 'poin' => n, 'saldo' => n]
//   ['galat' => 'warga' | 'kode']
function tandai_misi(PDO $pdo, string $kode, string $nomor): array
{
    if (preg_match('/^[a-z0-9-]{1,40}$/', $kode) !== 1) {
        return ['galat' => 'kode'];
    }
    if (warga_dari_nomor($pdo, $nomor) === null) {
        return ['galat' => 'warga'];
    }
    $stmt = $pdo->prepare('SELECT poin FROM misi WHERE kode = ?');
    $stmt->execute([$kode]);
    $poinMisi = $stmt->fetchColumn();
    if ($poinMisi === false) {
        return ['galat' => 'kode'];
    }

    $tanggal = date('Y-m-d');
    $insert = $pdo->prepare(
        'INSERT INTO misi_selesai (kode, tanggal, nomor) VALUES (?, ?, ?)
         ON CONFLICT(kode, tanggal, nomor) DO NOTHING'
    );
    $insert->execute([$kode, $tanggal, $nomor]);
    // Baris baru berarti misi ini belum pernah selesai untuk warga ini hari
    // ini, jadi hanya saat itulah poin diberikan.
    $baru = $insert->rowCount() > 0;

    $poin = 0;
    if ($baru) {
        $poin = (int) $poinMisi;
        tambah_poin($pdo, $nomor, $poin, 'misi', $kode);
    }

    return ['ok' => true, 'baru' => $baru, 'poin' => $poin, 'saldo' => saldo_poin($pdo, $nomor)];
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

// ---------------------------------------------------------------------------
// Chat antar gang (Fitur 2). Publik total: tidak ada DM, tidak ada teks bebas.
// Yang boleh dikirim hanya kode frase dari CHAT_FRASE, dan nama serta gang
// pengirim selalu diambil dari tabel warga, bukan dari kiriman peramban.
// ---------------------------------------------------------------------------

// Daftar kanal chat: kanal umum lebih dulu, lalu lima gang dengan nama persis
// seperti di tabel warga. Nama gang tidak disimpan terpisah, jadi kalau daftar
// gang berubah, kanalnya ikut menyesuaikan dengan sendirinya.
function daftar_kanal_chat(): array
{
    return array_merge([CHAT_KANAL_UMUM], GANG_WARGA);
}

// Kanal yang sah: 'alun-alun' atau satu nama gang persis. Selain itu null.
function kanal_chat_sah($kanal): ?string
{
    return is_string($kanal) && in_array($kanal, daftar_kanal_chat(), true) ? $kanal : null;
}

// Kode frase yang sah, atau null bila tidak ada di CHAT_FRASE.
function frase_chat_sah($frase): ?string
{
    return is_string($frase) && array_key_exists($frase, CHAT_FRASE) ? $frase : null;
}

// Daftar frase untuk dikirim ke peramban: [{ kode, teks }].
function daftar_frase_chat(): array
{
    $daftar = [];
    foreach (CHAT_FRASE as $kode => $teks) {
        $daftar[] = ['kode' => (string) $kode, 'teks' => $teks];
    }
    return $daftar;
}

// Pesan di satu kanal dengan id lebih besar dari $sejak, urut naik (paling
// lama dulu), paling banyak $batas. Paling ujung hanya 50 pesan terbaru yang
// diambil supaya jawaban tidak pernah membengkak.
function pesan_chat(PDO $pdo, string $kanal, int $sejak = 0, int $batas = 50): array
{
    $batas = max(1, min(50, $batas));
    $sejak = max(0, $sejak);
    $stmt = $pdo->prepare(
        'SELECT * FROM (
            SELECT id, kanal, nomor, nama, gang, frase, dibuat_pada
            FROM chat_pesan
            WHERE kanal = ? AND id > ?
            ORDER BY id DESC
            LIMIT ' . $batas . '
         ) ORDER BY id ASC'
    );
    $stmt->execute([$kanal, $sejak]);

    $hasil = [];
    foreach ($stmt->fetchAll() as $baris) {
        $kode = (string) $baris['frase'];
        $hasil[] = [
            'id' => (int) $baris['id'],
            'kanal' => $baris['kanal'],
            'nomor' => $baris['nomor'],
            'nama' => $baris['nama'],
            'gang' => $baris['gang'],
            'frase' => $kode,
            // Frase yang sudah dihapus dari CHAT_FRASE tetap terbaca netral,
            // bukan kosong, supaya riwayat lama tidak jadi baris tanpa arti.
            'teks' => CHAT_FRASE[$kode] ?? 'Pesan lama',
            'dibuat_pada' => $baris['dibuat_pada'],
        ];
    }
    return $hasil;
}

// Kirim satu pesan frase atas nama warga pemilik nomor + kunci.
// Mengembalikan:
//   ['ok' => true, 'pesan' => [baris, ...pesan baru]]
//   ['galat' => 'kanal' | 'frase' | 'warga' | 'kanal-lain' | 'terlalu-cepat']
function kirim_chat(PDO $pdo, $kanal, $frase, $nomor, $kunci): array
{
    $kanal = kanal_chat_sah($kanal);
    if ($kanal === null) {
        return ['galat' => 'kanal'];
    }
    $frase = frase_chat_sah($frase);
    if ($frase === null) {
        return ['galat' => 'frase'];
    }
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        return ['galat' => 'warga'];
    }
    // Menulis hanya di kanal umum atau gang sendiri. Kanal gang lain tetap
    // bisa dibaca, tapi tidak bisa ditulisi dari luar gang itu.
    if ($kanal !== CHAT_KANAL_UMUM && $kanal !== $warga['gang']) {
        return ['galat' => 'kanal-lain'];
    }

    $nomorWarga = nomor_warga((int) $warga['id']);
    $terakhir = $pdo->prepare('SELECT dibuat_pada FROM chat_pesan WHERE nomor = ? ORDER BY id DESC LIMIT 1');
    $terakhir->execute([$nomorWarga]);
    $waktuTerakhir = $terakhir->fetchColumn();
    if ($waktuTerakhir !== false) {
        $jeda = time() - (int) strtotime((string) $waktuTerakhir . ' UTC');
        if ($jeda < CHAT_JEDA_DETIK) {
            return ['galat' => 'terlalu-cepat'];
        }
    }

    // nama dan gang diambil dari baris warga, tidak pernah dari klien.
    $insert = $pdo->prepare(
        'INSERT INTO chat_pesan (kanal, nomor, nama, gang, frase) VALUES (?, ?, ?, ?, ?)'
    );
    $insert->execute([$kanal, $nomorWarga, $warga['nama'], $warga['gang'], $frase]);
    $id = (int) $pdo->lastInsertId();

    $baru = [];
    foreach (pesan_chat($pdo, $kanal, $id - 1, 1) as $pesan) {
        if ($pesan['id'] === $id) {
            $baru[] = $pesan;
        }
    }
    return ['ok' => true, 'pesan' => $baru];
}
