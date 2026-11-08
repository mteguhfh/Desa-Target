<?php
require_once __DIR__ . '/includes/katalog.php';
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
    if (!isset($kolomWarga['kode_pulih'])) {
        $pdo->exec('ALTER TABLE warga ADD COLUMN kode_pulih TEXT');
    }
    if (!isset($kolomWarga['bentuk_topi'])) $pdo->exec("ALTER TABLE warga ADD COLUMN bentuk_topi TEXT NOT NULL DEFAULT 'pet'");
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS panen_harian (nomor TEXT NOT NULL, tanggal TEXT NOT NULL, skor INTEGER NOT NULL, PRIMARY KEY (nomor, tanggal))");
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

    // Ruko komersil warga (Fitur 3). Satu tabel bangunan dipakai bersama:
    // jenis membedakan bangunan komentar (dibuat admin), ruko (dibuat warga
    // sendiri), dan fasilitas desa. Ruko yang dilepas pemiliknya ditandai
    // nonaktif supaya riwayat dan komentarnya tidak hilang.
    $kolomBangunan = kolom_tabel($pdo, 'bangunan');
    foreach ([
        'jenis' => "TEXT NOT NULL DEFAULT 'komentar'",
        'nomor' => 'TEXT',
        'tautan' => 'TEXT',
        'aktif' => 'INTEGER NOT NULL DEFAULT 1',
        'kategori' => "TEXT NOT NULL DEFAULT 'lainnya'",
        'harga' => 'INTEGER DEFAULT NULL',
    ] as $kolomBaru => $definisi) {
        if (!isset($kolomBangunan[$kolomBaru])) {
            $pdo->exec('ALTER TABLE bangunan ADD COLUMN ' . $kolomBaru . ' ' . $definisi);
        }
    }
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bangunan_nomor ON bangunan (nomor)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_bangunan_aktif ON bangunan (aktif, id)');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS ruko_klaim (
            nomor TEXT PRIMARY KEY,
            bangunan_id INTEGER NOT NULL,
            dibuat_pada TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    // Lapak Pasar Malam dan CFD (Fitur 4). Gratis, satu lapak per warga per
    // acara per tanggal. Kavling tenda dihitung sistem saat lapak diambil,
    // jadi tidak ada posisi tenda yang disimpan terpisah dari urutannya.
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS lapak (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            acara TEXT NOT NULL,
            tanggal TEXT NOT NULL,
            nomor TEXT NOT NULL,
            nama_usaha TEXT NOT NULL,
            tautan TEXT NOT NULL,
            kavling INTEGER,
            dibuat_pada TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (acara, tanggal, nomor)
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_lapak_acara ON lapak (acara, tanggal, id)');
    // Gambar usaha untuk lapak (fitur gambar jualan). Ruko memakai kolom
    // bangunan.gambar; lapak tanpa ruko menyimpan gambarnya sendiri.
    $kolomLapak = kolom_tabel($pdo, 'lapak');
    if (!isset($kolomLapak['kategori'])) $pdo->exec("ALTER TABLE lapak ADD COLUMN kategori TEXT NOT NULL DEFAULT 'lainnya'");
    if (!isset($kolomLapak['harga'])) $pdo->exec('ALTER TABLE lapak ADD COLUMN harga INTEGER DEFAULT NULL');
    if (!isset($kolomLapak['gambar'])) {
        $pdo->exec("ALTER TABLE lapak ADD COLUMN gambar TEXT NOT NULL DEFAULT ''");
    }
    // Gambar usaha untuk ruko: jalur relatif di assets/img/, hanya berkas
    // yang sudah ada. Lihat gambar_usaha_sah().
    $kolomBangunan = kolom_tabel($pdo, 'bangunan');
    if (!isset($kolomBangunan['gambar'])) {
        $pdo->exec("ALTER TABLE bangunan ADD COLUMN gambar TEXT NOT NULL DEFAULT ''");
    }

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

    // -----------------------------------------------------------------
    // Fitur 5 (SPESIFIKASI-FITUR-BELUM-DIBANGUN.md): LMS Pusat Pembelajaran.
    // Kelas berisi materi berurutan (video, artikel, kuis). Materi hanya
    // terbuka setelah materi sebelumnya selesai untuk warga yang sama.
    // Progres disimpan per warga; kunci jawaban kuis tidak pernah dikirim
    // ke klien. Menyelesaikan materi memberi poin lewat jalur 'materi'.
    // -----------------------------------------------------------------
    $pdo->exec('CREATE TABLE IF NOT EXISTS kelas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        judul TEXT NOT NULL,
        keterangan TEXT NOT NULL DEFAULT \'\',
        urutan INTEGER NOT NULL DEFAULT 0
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS materi (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kelas_id INTEGER NOT NULL REFERENCES kelas(id),
        judul TEXT NOT NULL,
        tipe TEXT NOT NULL,
        youtube_id TEXT,
        isi TEXT NOT NULL DEFAULT \'\',
        poin INTEGER NOT NULL DEFAULT 10,
        urutan INTEGER NOT NULL DEFAULT 0,
        UNIQUE (kelas_id, urutan, judul)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS progres_materi (
        nomor TEXT NOT NULL,
        materi_id INTEGER NOT NULL REFERENCES materi(id),
        selesai_pada TEXT NOT NULL,
        PRIMARY KEY (nomor, materi_id)
    )');

    // Kurikulum bawaan sebagai data (bukan kode di halaman): kelas di-upsert
    // menurut slug, materi menurut (kelas, urutan, judul). Boleh diedit atau
    // ditambah lewat basis data tanpa mengubah naskah halaman belajar.
    $seedBelajar = $pdo->prepare(
        'INSERT INTO kelas (slug, judul, keterangan, urutan) VALUES (?, ?, ?, ?)
         ON CONFLICT(slug) DO UPDATE SET judul = excluded.judul,
             keterangan = excluded.keterangan, urutan = excluded.urutan'
    );
    $cariKelas = $pdo->prepare('SELECT id FROM kelas WHERE slug = ?');
    $seedMateri = $pdo->prepare(
        'INSERT INTO materi (kelas_id, judul, tipe, youtube_id, isi, poin, urutan)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT(kelas_id, urutan, judul) DO UPDATE SET tipe = excluded.tipe,
             youtube_id = excluded.youtube_id, isi = excluded.isi, poin = excluded.poin'
    );
    foreach (kurikulum_belajar() as $kelasSeed) {
        $materiSeed = $kelasSeed['materi'];
        unset($kelasSeed['materi']);
        $seedBelajar->execute([$kelasSeed['slug'], $kelasSeed['judul'], $kelasSeed['keterangan'], $kelasSeed['urutan']]);
        $cariKelas->execute([$kelasSeed['slug']]);
        $kelasId = (int) $cariKelas->fetchColumn();
        foreach ($materiSeed as $i => $m) {
            $seedMateri->execute([$kelasId, $m['judul'], $m['tipe'], $m['youtube_id'] ?? null, $m['isi'] ?? '', $m['poin'] ?? 10, $i + 1]);
        }
    }

    // Bangunan pembelajaran sebagai fasilitas desa. Ditanam sekali dengan
    // jenis fasilitas supaya mengisi kavling setelah Balai Desa dan tampil
    // di peta 2D dan 3D serta daftar bangunan; kavling selebihnya masih
    // muat (12 kavling, bangunan komentar menyusul dari antrean warga).
    $cekBelajar = $pdo->prepare("SELECT COUNT(*) FROM bangunan WHERE jenis = 'fasilitas' AND nama = 'Pusat Pembelajaran'");
    $cekBelajar->execute();
    if ((int) $cekBelajar->fetchColumn() === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO bangunan (nama, gang, diminta_oleh, komentar, tanggal, jenis) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'Pusat Pembelajaran',
            null,
            'Bang Uju',
            'Tempat warga belajar dokumen usaha, konten jualan, dan keuangan. Masuk lewat halaman Belajar.',
            date('Y-m-d'),
            'fasilitas',
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

function panen_hari_ini(PDO $pdo, string $nomor): ?int
{
    $stmt = $pdo->prepare('SELECT skor FROM panen_harian WHERE nomor = ? AND tanggal = ?');
    $stmt->execute([$nomor, date('Y-m-d')]);
    $skor = $stmt->fetchColumn();
    return $skor === false ? null : (int) $skor;
}

function simpan_panen(PDO $pdo, string $nomor, int $skor): array
{
    $warga = warga_dari_nomor($pdo, $nomor);
    if (!$warga || $skor < 0 || $skor > 100) return ['ok' => false, 'galat' => 'Hasil panen tidak sah.'];
    if (panen_hari_ini($pdo, $nomor) !== null) return ['ok' => false, 'sudah' => true];
    $skor = max(0, min(100, $skor));
    $hadiah = $skor >= 60 ? 5 : 0;
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO panen_harian (nomor, tanggal, skor) VALUES (?, ?, ?)')->execute([$nomor, date('Y-m-d'), $skor]);
        if ($hadiah) {
            $pdo->prepare('UPDATE warga SET poin = poin + ? WHERE id = ?')->execute([$hadiah, (int) $warga['id']]);
            $pdo->prepare('INSERT INTO poin_log (nomor, delta, sumber, ref, tanggal) VALUES (?, ?, ?, ?, ?)')->execute([$nomor, $hadiah, 'materi', 'panen-' . date('Y-m-d'), date('Y-m-d')]);
        }
        $pdo->commit();
        return ['ok' => true, 'skor' => $skor, 'hadiah' => $hadiah, 'saldo' => saldo_poin($pdo, $nomor)];
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); return ['ok' => false]; }
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
    return ['selesai' => $selesai, 'total' => count($daftar), 'persen' => count($daftar) > 0 ? (int) round($selesai / count($daftar) * 100) : 0];
}

function streak_warga(PDO $pdo, string $nomor): int
{
    $stmt = $pdo->prepare("SELECT tanggal FROM (SELECT tanggal FROM misi_selesai WHERE nomor = ? UNION SELECT tanggal FROM klaim_harian WHERE nomor = ?) GROUP BY tanggal ORDER BY tanggal DESC");
    $stmt->execute([$nomor, $nomor]);
    $streak = 0;
    $cek = new DateTimeImmutable('today');
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $tanggal) {
        if ($tanggal !== $cek->format('Y-m-d')) break;
        $streak++;
        $cek = $cek->modify('-1 day');
    }
    return $streak;
}

function koleksi_warga(PDO $pdo, string $nomor): array
{
    $warga = warga_dari_nomor($pdo, $nomor);
    $hari = date('Y-m-d');
    $klaim = $pdo->prepare('SELECT COUNT(*) FROM klaim_harian WHERE nomor = ?');
    $klaim->execute([$nomor]);
    $misiHari = $pdo->prepare('SELECT COUNT(*) FROM misi_selesai WHERE nomor = ? AND tanggal = ?');
    $misiHari->execute([$nomor, $hari]);
    $chat = $pdo->prepare('SELECT COUNT(*) FROM chat_pesan WHERE nomor = ?');
    $chat->execute([$nomor]);
    $lapak = $pdo->prepare('SELECT COUNT(*) FROM lapak WHERE nomor = ?');
    $lapak->execute([$nomor]);
    $lencana = [
        ['kode' => 'warga-baru', 'nama' => 'Warga Baru', 'syarat' => 'Punya KTP Warga', 'terbuka' => $warga !== null],
        ['kode' => 'rajin-klaim', 'nama' => 'Rajin Klaim', 'syarat' => 'Klaim poin 7 hari beruntun', 'terbuka' => streak_warga($pdo, $nomor) >= 7],
        ['kode' => 'tukang-usaha', 'nama' => 'Tukang Usaha', 'syarat' => 'Punya ruko aktif', 'terbuka' => ruko_milik($pdo, $nomor) !== null],
        ['kode' => 'pedagang-pasar', 'nama' => 'Pedagang Pasar', 'syarat' => 'Pernah mengambil lapak', 'terbuka' => (int) $lapak->fetchColumn() > 0],
        ['kode' => 'suara-gang', 'nama' => 'Suara Gang', 'syarat' => 'Pernah mengirim pesan', 'terbuka' => (int) $chat->fetchColumn() > 0],
        ['kode' => 'penjelajah', 'nama' => 'Penjelajah', 'syarat' => 'Menyelesaikan Keliling Desa', 'terbuka' => (int) $pdo->prepare('SELECT COUNT(*) FROM misi_selesai WHERE nomor = ? AND kode = ?')->execute([$nomor, 'keliling-desa'])],
        ['kode' => 'misi-tuntas', 'nama' => 'Misi Tuntas', 'syarat' => 'Menyelesaikan 5 misi dalam sehari', 'terbuka' => (int) $misiHari->fetchColumn() >= 5],
        ['kode' => 'warga-cerdas', 'nama' => 'Warga Cerdas', 'syarat' => 'Menuntaskan satu kelas di Pusat Pembelajaran', 'terbuka' => kelas_selesai_warga($pdo, $nomor) > 0],
    ];
    // PDOStatement::execute mengembalikan bool, jadi hitung lencana penjelajah ulang.
    $cekJelajah = $pdo->prepare('SELECT COUNT(*) FROM misi_selesai WHERE nomor = ? AND kode = ?');
    $cekJelajah->execute([$nomor, 'keliling-desa']);
    $lencana[5]['terbuka'] = (int) $cekJelajah->fetchColumn() > 0;
    return $lencana;
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
            'teks' => str_starts_with($kode, 'teks:') ? substr($kode, 5) : (CHAT_FRASE[$kode] ?? 'Pesan lama'),
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
    $frase = is_string($frase) && str_starts_with($frase, 'teks:') && preg_match('//u', $frase) && preg_match_all('/./us', substr($frase, 5)) <= 280 && trim(substr($frase, 5)) !== '' && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $frase) ? $frase : frase_chat_sah($frase);
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

// ---------------------------------------------------------------------------
// Ruko komersil warga (Fitur 3).
//
// Ruko adalah BANGUNAN yang dibuat warga sendiri, beda dari bangunan komentar
// yang dibuat admin dari pemenang komentar. Keduanya di tabel yang sama
// supaya daftar bangunan, sorot, popover, dan pencarian tidak butuh jalur
// kedua; kolom jenis ('komentar' | 'ruko' | 'fasilitas') yang membedakan.
//
// Satu warga satu ruko (ruko_klaim). Melepas ruko menandai bangunan nonaktif:
// baris dan ceritanya tetap tersimpan, hanya tidak tampil lagi di peta.
// Kavling dihitung dari urutan bangunan aktif, jadi tidak ada logika kavling
// kedua di sini.
// ---------------------------------------------------------------------------

// Bangunan yang tampil di peta: semua kecuali ruko yang sudah dilepas.
function semua_bangunan_aktif(PDO $pdo): array
{
    return $pdo->query(
        "SELECT * FROM bangunan WHERE aktif = 1 ORDER BY id ASC"
    )->fetchAll();
}

function hitung_bangunan_aktif(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM bangunan WHERE aktif = 1')->fetchColumn();
}

// URL tempat berjualan. Hanya https, host wajib ada, tanpa nama pengguna
// atau sandi di dalam alamat. Selain itu null, supaya tautan apa pun seperti
// javascript: atau data: tidak pernah tersimpan.
function tautan_usaha_sah($tautan): ?string
{
    if (!is_string($tautan)) {
        return null;
    }
    $tautan = trim($tautan);
    if ($tautan === '' || strlen($tautan) > 300) {
        return null;
    }
    if (preg_match('/[\x00-\x20\x7F]/', $tautan) === 1) {
        return null;
    }
    $bagian = parse_url($tautan);
    if ($bagian === false || !isset($bagian['scheme'], $bagian['host'])) {
        return null;
    }
    if (strtolower((string) $bagian['scheme']) !== 'https') {
        return null;
    }
    if ($bagian['host'] === '' || isset($bagian['user']) || isset($bagian['pass'])) {
        return null;
    }
    return $tautan;
}

// Ruko aktif milik satu warga, atau null.
function ruko_milik(PDO $pdo, string $nomor): ?array
{
    $stmt = $pdo->prepare(
        "SELECT b.* FROM ruko_klaim k
         JOIN bangunan b ON b.id = k.bangunan_id
         WHERE k.nomor = ? AND b.aktif = 1"
    );
    $stmt->execute([$nomor]);
    $baris = $stmt->fetch();
    return $baris === false ? null : $baris;
}

// Klaim satu ruko untuk warga pemilik nomor + kunci.
// Mengembalikan:
//   ['ok' => true, 'ruko' => baris bangunan, 'saldo' => n]
//   ['galat' => 'warga' | 'nama' | 'cerita' | 'gang' | 'tautan' | 'punya' | 'penuh' | 'poin']
function klaim_ruko(PDO $pdo, $nomor, $kunci, string $namaUsaha, string $cerita, $tautan, string $gang, ?int $totalKavling = null, $gambar = null): array
{
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        return ['galat' => 'warga'];
    }
    $nomorWarga = nomor_warga((int) $warga['id']);

    $namaUsaha = trim($namaUsaha);
    $panjangNama = function_exists('mb_strlen') ? mb_strlen($namaUsaha) : strlen($namaUsaha);
    if ($panjangNama < 3 || $panjangNama > 60 || preg_match('/[\x00-\x1F\x7F]/u', $namaUsaha) === 1) {
        return ['galat' => 'nama'];
    }
    $cerita = trim($cerita);
    $panjangCerita = function_exists('mb_strlen') ? mb_strlen($cerita) : strlen($cerita);
    if ($panjangCerita > 280) {
        return ['galat' => 'cerita'];
    }
    // Gang ruko selalu gang pemilik menurut kartunya, bukan pilihan bebas.
    if ($gang !== (string) $warga['gang']) {
        return ['galat' => 'gang'];
    }
    $tautanSah = tautan_usaha_sah($tautan);
    if ($tautanSah === null) {
        return ['galat' => 'tautan'];
    }
    if (ruko_milik($pdo, $nomorWarga) !== null) {
        return ['galat' => 'punya'];
    }
    if ($totalKavling !== null && hitung_bangunan_aktif($pdo) >= $totalKavling) {
        return ['galat' => 'penuh'];
    }
    if (RUKO_BIAYA_POIN > 0 && saldo_poin($pdo, $nomorWarga) < RUKO_BIAYA_POIN) {
        return ['galat' => 'poin'];
    }
    // Gambar usaha opsional, tapi kalau diisi wajib salah satu dari
    // daftar_gambar_usaha(): tidak ada URL bebas yang masuk ke src gambar.
    $gambarSah = '';
    if (is_string($gambar) && trim($gambar) !== '') {
        $gambarSah = gambar_usaha_sah(trim($gambar), $nomorWarga) ?? '';
        if ($gambarSah === '') {
            return ['galat' => 'gambar'];
        }
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO bangunan (nama, gang, diminta_oleh, komentar, tanggal, jenis, nomor, tautan, gambar, aktif)
             VALUES (?, ?, ?, ?, ?, 'ruko', ?, ?, ?, 1)"
        )->execute([
            $namaUsaha,
            $gang,
            (string) $warga['nama'],
            $cerita === '' ? null : $cerita,
            date('Y-m-d'),
            $nomorWarga,
            $tautanSah,
            $gambarSah,
        ]);
        $idRuko = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO ruko_klaim (nomor, bangunan_id) VALUES (?, ?)')
            ->execute([$nomorWarga, $idRuko]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['galat' => 'punya'];
    }

    if (RUKO_BIAYA_POIN > 0) {
        tambah_poin($pdo, $nomorWarga, -RUKO_BIAYA_POIN, 'admin', 'ruko-' . $idRuko);
    }

    $stmt = $pdo->prepare('SELECT * FROM bangunan WHERE id = ?');
    $stmt->execute([$idRuko]);
    return ['ok' => true, 'ruko' => $stmt->fetch(), 'saldo' => saldo_poin($pdo, $nomorWarga)];
}

// Lepas ruko milik warga: bangunan ditandai nonaktif, ceritanya tetap ada.
// Mengembalikan ['ok' => true] atau ['galat' => 'warga' | 'tidak-ada'].
function lepas_ruko(PDO $pdo, $nomor, $kunci): array
{
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        return ['galat' => 'warga'];
    }
    $nomorWarga = nomor_warga((int) $warga['id']);
    $ruko = ruko_milik($pdo, $nomorWarga);
    if ($ruko === null) {
        return ['galat' => 'tidak-ada'];
    }
    $pdo->prepare("UPDATE bangunan SET aktif = 0 WHERE id = ?")->execute([(int) $ruko['id']]);
    return ['ok' => true];
}

// Ubah ruko milik warga: nama usaha, cerita, tautan, dan gambar. Aturan
// validasinya sama persis dengan klaim_ruko(), jadi tidak ada jalan pintas
// untuk menyimpan nama panjang atau tautan http://.
// Mengembalikan ['ok' => true, 'ruko' => baris] atau ['galat' => ...].
function ubah_ruko(PDO $pdo, $nomor, $kunci, string $namaUsaha, string $cerita, $tautan, $gambar): array
{
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        return ['galat' => 'warga'];
    }
    $nomorWarga = nomor_warga((int) $warga['id']);
    $ruko = ruko_milik($pdo, $nomorWarga);
    if ($ruko === null) {
        return ['galat' => 'tidak-ada'];
    }
    $namaUsaha = trim($namaUsaha);
    $panjang = function_exists('mb_strlen') ? mb_strlen($namaUsaha) : strlen($namaUsaha);
    if ($panjang < 3 || $panjang > 60 || preg_match('/[\x00-\x1F\x7F]/u', $namaUsaha) === 1) {
        return ['galat' => 'nama'];
    }
    $cerita = trim($cerita);
    $panjangCerita = function_exists('mb_strlen') ? mb_strlen($cerita) : strlen($cerita);
    if ($panjangCerita > RUKO_CERITA_MAKS) {
        return ['galat' => 'cerita'];
    }
    $tautanSah = tautan_usaha_sah($tautan);
    if ($tautanSah === null) {
        return ['galat' => 'tautan'];
    }
    // Kosong berarti menghapus gambar. Selain kosong wajib gambar yang sah.
    $gambarSah = '';
    if (is_string($gambar) && trim($gambar) !== '') {
        $gambarSah = gambar_usaha_sah(trim($gambar), $nomorWarga) ?? '';
        if ($gambarSah === '') {
            return ['galat' => 'gambar'];
        }
    }
    $pdo->prepare('UPDATE bangunan SET nama = ?, komentar = ?, tautan = ?, gambar = ? WHERE id = ?')
        ->execute([$namaUsaha, $cerita, $tautanSah, $gambarSah, (int) $ruko['id']]);
    $stmt = $pdo->prepare('SELECT * FROM bangunan WHERE id = ?');
    $stmt->execute([(int) $ruko['id']]);
    return ['ok' => true, 'ruko' => $stmt->fetch()];
}

// Kunci rahasia warga sebagai teks, dibaca langsung dari basis data. Dipakai
// saat kunci baru saja terbit (mis. KTP dibuat lewat panel peta) sebelum
// barisnya dibaca ulang. Mengembalikan '' bila warga tidak ada.
function kunci_warga(PDO $pdo, int $id): string
{
    $stmt = $pdo->prepare('SELECT kunci FROM warga WHERE id = ?');
    $stmt->execute([$id]);
    $nilai = $stmt->fetchColumn();
    return $nilai === false ? '' : (string) $nilai;
}

// Kode acak 128 bit disimpan sebagai hash; UPDATE bersyarat membuatnya
// sekali pakai sekaligus mencabut kunci perangkat sebelumnya.
function buat_kode_pulih(PDO $pdo, string $nomor, string $kunci): ?string
{
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) return null;
    $kode = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare('UPDATE warga SET kode_pulih = ? WHERE id = ? AND kunci = ?');
    $stmt->execute([hash('sha256', $kode), (int) $warga['id'], $kunci]);
    return $stmt->rowCount() === 1 ? $kode : null;
}

function pulihkan_kartu(PDO $pdo, string $nomor, string $kode): ?array
{
    if (!preg_match('/^DT-\d{4,9}$/D', $nomor) || !preg_match('/^[a-f0-9]{32}$/D', $kode)) return null;
    $kunci = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare('UPDATE warga SET kunci = ?, kode_pulih = NULL WHERE id = ? AND kode_pulih = ?');
    $stmt->execute([$kunci, (int) substr($nomor, 3), hash('sha256', $kode)]);
    if ($stmt->rowCount() !== 1) return null;
    return ['nomor' => $nomor, 'kunci' => $kunci];
}

// Hanya gambar lokal yang disediakan untuk usaha, tanpa URL bebas.
function daftar_gambar_usaha(): array
{
    $wadah = __DIR__ . '/assets/img';
    if (!is_dir($wadah)) {
        return [];
    }
    $hasil = [];
    foreach (scandir($wadah) ?: [] as $berkas) {
        // Gambar contoh untuk pemilih gambar usaha; logo, maskot, dan berkas
        // lain di assets/img/ bukan pilihan gambar jualan.
        if (preg_match('/^contoh-[a-z0-9-]{2,50}\.(?:png|jpg|jpeg|webp)$/', $berkas) === 1) {
            $hasil[] = 'assets/img/' . $berkas;
        }
    }
    sort($hasil);
    return $hasil;
}

function gambar_usaha_sah($gambar, ?string $pemilik = null): ?string
{
    if (!is_string($gambar) || $gambar === '') {
        return null;
    }
    if (preg_match('~^assets/uploads/usaha/(DT-[0-9]+)-[a-f0-9]{24}\.jpg$~D', $gambar, $cocok) && ($pemilik === null || $pemilik === $cocok[1]) && is_file(__DIR__ . '/' . $gambar)) return $gambar;
    return in_array($gambar, daftar_gambar_usaha(), true) ? $gambar : null;
}

// ---------------------------------------------------------------------------
// Pasar Malam dan CFD (Fitur 4). Lapak gratis, tanpa pembayaran.
//
// Jadwal ada di ACARA_LAPAK (config.php). Semua fungsi di sini menerima
// waktu sekarang, jadi jadwal bisa diuji untuk hari dan jam mana pun tanpa
// menunggu Sabtu atau Minggu.
// ---------------------------------------------------------------------------

// Kode acara yang sah, atau null.
function acara_sah($acara): ?string
{
    return is_string($acara) && isset(ACARA_LAPAK[$acara]) ? $acara : null;
}

// Acara yang sedang berlangsung pada waktu $sekarang, atau null.
// $sekarang format 'Y-m-d H:i' (atau jam lengkap, bagian detik diabaikan).
function acara_aktif(?string $sekarang = null): ?string
{
    $sekarang = $sekarang ?? date('Y-m-d H:i');
    $hari = (int) date('N', (int) strtotime($sekarang));
    $jam = date('H:i', (int) strtotime($sekarang));
    foreach (ACARA_LAPAK as $kode => $acara) {
        // Jam selesai tidak termasuk, jadi Pasar Malam berakhir pukul 22.00.
        if ($hari === (int) $acara['hari'] && $jam >= $acara['mulai'] && $jam < $acara['selesai']) {
            return (string) $kode;
        }
    }
    return null;
}

// Awal acara berikutnya: ['acara', 'tanggal', 'mulai'] atau null.
function acara_berikutnya(?string $sekarang = null): ?array
{
    $sekarang = $sekarang ?? date('Y-m-d H:i');
    $awal = new DateTimeImmutable($sekarang);
    $terdekat = null;
    // Cari dalam dua pekan ke depan: cukup untuk setiap acara mingguan.
    for ($i = 0; $i <= 14; $i++) {
        $hariIni = $awal->modify('+' . $i . ' day');
        foreach (ACARA_LAPAK as $kode => $acara) {
            if ((int) $hariIni->format('N') !== (int) $acara['hari']) {
                continue;
            }
            $mulai = new DateTimeImmutable($hariIni->format('Y-m-d') . ' ' . $acara['mulai']);
            if ($mulai <= $awal) {
                continue;
            }
            if ($terdekat === null || $mulai < $terdekat['mulai']) {
                $terdekat = [
                    'acara' => (string) $kode,
                    'nama' => $acara['nama'],
                    'tanggal' => $mulai->format('Y-m-d'),
                    'mulai' => $acara['mulai'],
                    'selesai' => $acara['selesai'],
                    '_waktu' => $mulai,
                ];
            }
        }
    }
    if ($terdekat === null) {
        return null;
    }
    unset($terdekat['_waktu']);
    return $terdekat;
}

// Daftar lapak satu acara pada satu tanggal, urut waktu klaim.
// Kavling tenda mengikuti urutan ini (0, 1, 2, ...).
function daftar_lapak(PDO $pdo, string $acara, string $tanggal): array
{
    if (acara_sah($acara) === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT id, acara, tanggal, nomor, nama_usaha, tautan, kavling, gambar, kategori, harga, dibuat_pada
          FROM lapak WHERE acara = ? AND tanggal = ? ORDER BY id ASC'
    );
    $stmt->execute([$acara, $tanggal]);
    $hasil = [];
    foreach ($stmt->fetchAll() as $i => $baris) {
        $hasil[] = [
            'id' => (int) $baris['id'],
            'acara' => $baris['acara'],
            'tanggal' => $baris['tanggal'],
            'nomor' => $baris['nomor'],
            'namaUsaha' => $baris['nama_usaha'],
            'kategori' => $baris['kategori'],
            'harga' => $baris['harga'] === null ? null : (int) $baris['harga'],
            'tautan' => $baris['tautan'],
            'gambar' => (string) ($baris['gambar'] ?? ''),
            'kavling' => $i,
        ];
    }
    return $hasil;
}

// Ambil satu lapak untuk acara yang sedang berlangsung.
// Mengembalikan:
//   ['ok' => true, 'lapak' => [...], 'sudah' => bool]
//   ['galat' => 'warga' | 'acara' | 'tutup' | 'tautan' | 'nama' | 'penuh' | 'sudah']
function klaim_lapak(PDO $pdo, $nomor, $kunci, $acara, ?string $sekarang = null, ?string $namaUsaha = null, ?string $tautan = null, $gambar = null, $kategori = 'lainnya', $harga = null): array
{
    $warga = warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
    if ($warga === null) {
        return ['galat' => 'warga'];
    }
    $kode = acara_sah($acara);
    if ($kode === null) {
        return ['galat' => 'acara'];
    }
    $sekarang = $sekarang ?? date('Y-m-d H:i');
    // Lapak hanya bisa diambil saat acaranya berlangsung. Di luar jam acara
    // daftar lapak acara berikutnya tetap bisa dilihat, tapi belum diklaim.
    if (acara_aktif($sekarang) !== $kode) {
        return ['galat' => 'tutup'];
    }
    $tanggal = date('Y-m-d', (int) strtotime($sekarang));
    $nomorWarga = nomor_warga((int) $warga['id']);

    // Ruko warga menjadi sumber nama usaha dan tautan. Tanpa ruko, formulir
    // ringkas di panel yang dipakai.
    $ruko = ruko_milik($pdo, $nomorWarga);
    if ($ruko !== null) {
        $namaUsaha = (string) $ruko['nama'];
        $tautan = (string) $ruko['tautan'];
    }
    $namaUsaha = trim((string) $namaUsaha);
    $panjangNama = function_exists('mb_strlen') ? mb_strlen($namaUsaha) : strlen($namaUsaha);
    if ($panjangNama < 3 || $panjangNama > 60) {
        return ['galat' => 'nama'];
    }
    $tautanSah = tautan_usaha_sah($tautan);
    if ($tautanSah === null) {
        return ['galat' => 'tautan'];
    }

    $sudah = $pdo->prepare('SELECT COUNT(*) FROM lapak WHERE acara = ? AND tanggal = ? AND nomor = ?');
    $sudah->execute([$kode, $tanggal, $nomorWarga]);
    if ((int) $sudah->fetchColumn() > 0) {
        return ['galat' => 'sudah'];
    }

    // Gambar lapak: dari ruko bila pemilik punya ruko, selain itu dari
    // isian panel. Kalau ruko tidak punya gambar, lapak boleh memakai gambar
    // pilihan sendiri.
    $gambarSah = '';
    $gambarRuko = $ruko !== null ? (string) ($ruko['gambar'] ?? '') : '';
    if ($gambarRuko !== '') {
        $gambarSah = $gambarRuko;
    } elseif (is_string($gambar) && trim($gambar) !== '') {
        $gambarSah = gambar_usaha_sah(trim($gambar), $nomorWarga) ?? '';
        if ($gambarSah === '') {
            return ['galat' => 'gambar'];
        }
    }

    $lapak = daftar_lapak($pdo, $kode, $tanggal);
    $batas = (int) (ACARA_LAPAK[$kode]['batas'] ?? 12);
    if (count($lapak) >= $batas) {
        return ['galat' => 'penuh'];
    }

    $kategori = $ruko['kategori'] ?? $kategori;
    if (!is_string($kategori) || !isset(KATEGORI_USAHA[$kategori])) $kategori = 'lainnya';
    $harga = filter_var($ruko !== null ? $ruko['harga'] : $harga, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000000]]);
    $pdo->prepare(
        'INSERT INTO lapak (acara, tanggal, nomor, nama_usaha, tautan, gambar, kavling, kategori, harga) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([$kode, $tanggal, $nomorWarga, $namaUsaha, $tautanSah, $gambarSah, count($lapak), $kategori, $harga === false ? null : $harga]);

    return ['ok' => true, 'lapak' => daftar_lapak($pdo, $kode, $tanggal)];
}

// Keadaan pasar untuk satu waktu: acara aktif, acara berikutnya, dan lapak
// acara aktif saja. Dipakai panel Pasar dan data-desa.php supaya peta dan
// panel membaca angka yang sama.
function keadaan_pasar(PDO $pdo, ?string $sekarang = null): array
{
    $sekarang = $sekarang ?? date('Y-m-d H:i');
    $aktif = acara_aktif($sekarang);
    $jadwal = [];
    foreach (ACARA_LAPAK as $kode => $acara) {
        $jadwal[] = [
            'acara' => (string) $kode,
            'nama' => $acara['nama'],
            'hari' => (int) $acara['hari'],
            'mulai' => $acara['mulai'],
            'selesai' => $acara['selesai'],
            'batas' => (int) $acara['batas'],
        ];
    }
    $tanggalAktif = $aktif === null ? null : date('Y-m-d', (int) strtotime($sekarang));
    return [
        'sekarang' => date('Y-m-d H:i', (int) strtotime($sekarang)),
        'acara' => $aktif,
        'namaAcara' => $aktif === null ? null : ACARA_LAPAK[$aktif]['nama'],
        'tanggal' => $tanggalAktif,
        'berikutnya' => acara_berikutnya($sekarang),
        'jadwal' => $jadwal,
        'lapak' => $aktif === null ? [] : daftar_lapak($pdo, $aktif, $tanggalAktif),
        'batas' => $aktif === null ? 0 : (int) ACARA_LAPAK[$aktif]['batas'],
    ];
}

// ---------------------------------------------------------------------------
// Pusat Pembelajaran (Fitur 5). Kelas dan materi satu sumber data untuk
// halaman Belajar dan interior 3D (ADR-0004). Kunci jawaban kuis hanya
// pernah dibaca di server; yang sampai ke peramban hanya soal dan pilihan.
// ---------------------------------------------------------------------------

// Seluruh kelas terurut, masing-masing dengan ringkasan materinya.
function daftar_kelas(PDO $pdo): array
{
    $kelas = $pdo->query(
        'SELECT k.*,
            (SELECT COUNT(*) FROM materi m WHERE m.kelas_id = k.id) AS jumlah_materi,
            (SELECT COALESCE(SUM(m2.poin), 0) FROM materi m2 WHERE m2.kelas_id = k.id) AS total_poin
         FROM kelas k ORDER BY k.urutan, k.id'
    )->fetchAll();
    return $kelas;
}

// Kelas menurut slug; null bila tidak ada.
function kelas_dari_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM kelas WHERE slug = ?');
    $stmt->execute([$slug]);
    $baris = $stmt->fetch();
    return $baris === false ? null : $baris;
}

// Daftar materi satu kelas menurut urutan.
function daftar_materi(PDO $pdo, int $kelasId): array
{
    $stmt = $pdo->prepare('SELECT * FROM materi WHERE kelas_id = ? ORDER BY urutan, id');
    $stmt->execute([$kelasId]);
    return $stmt->fetchAll();
}

// Materi menurut id; null bila tidak ada.
function materi_dari_id(PDO $pdo, int $materiId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM materi WHERE id = ?');
    $stmt->execute([$materiId]);
    $baris = $stmt->fetch();
    return $baris === false ? null : $baris;
}

// Himpunan materi_id yang sudah dituntaskan warga (kunci = materi_id).
function progres_warga(PDO $pdo, string $nomor): array
{
    $stmt = $pdo->prepare('SELECT materi_id, selesai_pada FROM progres_materi WHERE nomor = ?');
    $stmt->execute([$nomor]);
    $hasil = [];
    foreach ($stmt->fetchAll() as $baris) {
        $hasil[(int) $baris['materi_id']] = $baris['selesai_pada'];
    }
    return $hasil;
}

// Materi terbuka bila semua materi sebelumnya dalam kelas yang sama sudah
// selesai untuk warga ini. Untuk pengunjung tanpa kartu, hanya materi
// pertama yang terbuka.
function materi_terbuka(PDO $pdo, array $materi, ?string $nomor): bool
{
    $daftar = daftar_materi($pdo, (int) $materi['kelas_id']);
    $progres = $nomor !== null ? progres_warga($pdo, $nomor) : [];
    foreach ($daftar as $m) {
        if ((int) $m['id'] === (int) $materi['id']) {
            return true;
        }
        if (!isset($progres[(int) $m['id']])) {
            return false;
        }
    }
    return false;
}

// Materi berikutnya dalam kelas yang belum dituntaskan, atau null bila
// kelas sudah tuntas. Dipakai untuk tombol Lanjut sesudah satu materi usai.
function materi_berikutnya(PDO $pdo, string $nomor, int $kelasId): ?array
{
    $progres = progres_warga($pdo, $nomor);
    foreach (daftar_materi($pdo, $kelasId) as $m) {
        if (!isset($progres[(int) $m['id']])) {
            return $m;
        }
    }
    return null;
}

// Satu kelas tuntas bila semua materinya selesai untuk warga ini.
function kelas_selesai_untuk(PDO $pdo, string $nomor, int $kelasId): bool
{
    $daftar = daftar_materi($pdo, $kelasId);
    if ($daftar === []) {
        return false;
    }
    $progres = progres_warga($pdo, $nomor);
    foreach ($daftar as $m) {
        if (!isset($progres[(int) $m['id']])) {
            return false;
        }
    }
    return true;
}

// Jumlah kelas yang sudah dituntaskan warga (dasar lencana Warga Cerdas).
function kelas_selesai_warga(PDO $pdo, string $nomor): int
{
    $jumlah = 0;
    foreach (daftar_kelas($pdo) as $k) {
        if (kelas_selesai_untuk($pdo, $nomor, (int) $k['id'])) {
            $jumlah++;
        }
    }
    return $jumlah;
}

// Keadaan belajar lengkap satu warga untuk satu kelas: daftar materi dengan
// status masing-masing (terkunci, terbuka, selesai, segera) plus ringkasan
// progres. Inilah satu sumber data untuk halaman Belajar dan panel/interior.
function keadaan_belajar(PDO $pdo, ?string $nomor, int $kelasId): array
{
    $daftar = daftar_materi($pdo, $kelasId);
    $progres = $nomor !== null ? progres_warga($pdo, $nomor) : [];
    $materi = [];
    $selesai = 0;
    $gembokPatah = true;
    foreach ($daftar as $m) {
        $id = (int) $m['id'];
        $sudah = isset($progres[$id]);
        if ($sudah) {
            $selesai++;
        }
        $segera = $m['tipe'] === 'video' && trim((string) ($m['youtube_id'] ?? '')) === '';
        $materi[] = $m + [
            'selesai' => $sudah,
            'selesai_pada' => $progres[$id] ?? null,
            'terbuka' => $gembokPatah,
            'segera' => $segera,
        ];
        if (!$sudah) {
            $gembokPatah = false;
        }
    }
    $total = count($daftar);
    return [
        'materi' => $materi,
        'selesai' => $selesai,
        'total' => $total,
        'persen' => $total > 0 ? (int) round($selesai / $total * 100) : 0,
        'tuntas' => $total > 0 && $selesai === $total,
    ];
}

// Catat materi selesai dan beri poinnya, semuanya dalam satu transaksi.
// tambah_poin() membuka transaksinya sendiri, jadi di sini penulisan poin
// mengikuti pola simpan_panen(): UPDATE warga dan INSERT poin_log manual
// dengan sumber 'materi' di dalam transaksi yang sama dengan progresnya.
// Kunci utama progres_materi membuat kiriman ulang tidak menggandakan poin.
function selesaikan_materi(PDO $pdo, string $nomor, array $materi): array
{
    $materiId = (int) $materi['id'];
    $sudah = $pdo->prepare('SELECT COUNT(*) FROM progres_materi WHERE nomor = ? AND materi_id = ?');
    $sudah->execute([$nomor, $materiId]);
    if ((int) $sudah->fetchColumn() > 0) {
        return ['ok' => true, 'baru' => false, 'poin' => 0, 'saldo' => saldo_poin($pdo, $nomor)];
    }
    $warga = warga_dari_nomor($pdo, $nomor);
    if ($warga === null) {
        return ['ok' => false, 'galat' => 'warga'];
    }
    $poin = (int) $materi['poin'];
    $pdo->beginTransaction();
    try {
        $tulis = $pdo->prepare(
            'INSERT INTO progres_materi (nomor, materi_id, selesai_pada) VALUES (?, ?, ?)
             ON CONFLICT(nomor, materi_id) DO NOTHING'
        );
        $tulis->execute([$nomor, $materiId, date('Y-m-d H:i:s')]);
        $baru = $tulis->rowCount() > 0;
        if ($baru && $poin !== 0) {
            $pdo->prepare('UPDATE warga SET poin = poin + ? WHERE id = ?')
                ->execute([$poin, (int) $warga['id']]);
            $pdo->prepare(
                'INSERT INTO poin_log (nomor, delta, sumber, ref, tanggal) VALUES (?, ?, ?, ?, ?)'
            )->execute([$nomor, $poin, 'materi', (string) $materiId, date('Y-m-d')]);
        }
        $pdo->commit();
        return ['ok' => true, 'baru' => $baru, 'poin' => $baru ? $poin : 0, 'saldo' => saldo_poin($pdo, $nomor)];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'galat' => 'gagal'];
    }
}

// Soal kuis tanpa kunci jawaban: inilah satu-satunya bentuk soal yang boleh
// sampai ke peramban. Kunci 'jawaban' disingkirkan di sini, di server.
function soal_kuis_publik(array $materi): array
{
    $data = json_decode((string) ($materi['isi'] ?? ''), true);
    $soal = is_array($data) && is_array($data['soal'] ?? null) ? $data['soal'] : [];
    $hasil = [];
    foreach ($soal as $s) {
        $hasil[] = [
            'tanya' => (string) ($s['tanya'] ?? ''),
            'pilihan' => array_values(array_map('strval', is_array($s['pilihan'] ?? null) ? $s['pilihan'] : [])),
        ];
    }
    return $hasil;
}

// Nilai jawaban kuis di server. Lulus hanya bila semua soal benar; kiriman
// yang tidak lengkap atau di luar pilihan yang ada otomatis tidak lulus.
function nilai_kuis(array $materi, array $jawabanWarga): array
{
    $data = json_decode((string) ($materi['isi'] ?? ''), true);
    $soal = is_array($data) && is_array($data['soal'] ?? null) ? $data['soal'] : [];
    $benar = 0;
    foreach ($soal as $i => $s) {
        $kunci = $s['jawaban'] ?? null;
        $pilihan = is_array($s['pilihan'] ?? null) ? $s['pilihan'] : [];
        $jawab = $jawabanWarga[$i] ?? null;
        if (is_int($kunci) && is_int($jawab) && $jawab >= 0 && $jawab < count($pilihan) && $jawab === $kunci) {
            $benar++;
        }
    }
    $total = count($soal);
    return ['lulus' => $total > 0 && $benar === $total, 'benar' => $benar, 'total' => $total];
}

// Kurikulum bawaan Pusat Pembelajaran. Teks artikel ditulis ringkas untuk
// warga UMKM; video yang belum punya ID YouTube tampil sebagai segera hadir.
function kurikulum_belajar(): array
{
    return [
        [
            'slug' => 'dokumen-usaha-dasar',
            'judul' => 'Dokumen Usaha Dasar',
            'keterangan' => 'Tiga dokumen yang paling sering dipakai usaha kecil: invoice, surat penawaran, dan slip gaji. Pelajari fungsinya, isinya, dan kesalahan yang paling sering terjadi.',
            'urutan' => 1,
            'materi' => [
                [
                    'judul' => 'Invoice: Bukti Tagihan yang Rapi',
                    'tipe' => 'artikel',
                    'poin' => 10,
                    'isi' => <<<'HTML'
<p>Invoice adalah dokumen tagihan resmi dari penjual kepada pembeli. Fungsinya bukan sekadar meminta bayaran, tapi juga menjadi bukti transaksi yang bisa dipakai untuk pembukuan, pajak, dan penagihan ulang bila pembayaran terlambat. Usaha sekecil apa pun terlihat lebih profesional saat mengirim invoice yang rapi.</p>
<p>Isi invoice yang baik selalu memuat nomor invoice yang unik, tanggal terbit, nama dan kontak penjual, nama pembeli, rincian barang atau jasa beserta jumlah dan harga satuan, total yang harus dibayar, serta cara pembayaran seperti nomor rekening atau kode QRIS. Tambahkan juga tanggal jatuh tempo supaya pembeli tahu batas waktunya.</p>
<p>Kesalahan yang paling sering terjadi adalah nomor invoice yang dobel, total yang tidak cocok dengan rincian, dan tidak mencantumkan jatuh tempo. Akibatnya pembayaran sering tertunda karena pembeli bingung atau harus bertanya lagi. Biasakan memeriksa total dan nomor sebelum invoice dikirim.</p>
<p>Di Desa Target, urusan invoice dibantu Kantor Desa lewat Kejar Target. Kamu bisa membuat invoice gratis dengan pratinjau langsung dan ekspor PDF sekali klik, tanpa perlu memasang aplikasi tambahan.</p>
HTML,
                ],
                [
                    'judul' => 'Surat Penawaran: Meyakinkan Sebelum Menjual',
                    'tipe' => 'artikel',
                    'poin' => 10,
                    'isi' => <<<'HTML'
<p>Surat penawaran adalah dokumen yang dikirim kepada calon pembeli untuk menawarkan produk atau jasa secara resmi. Surat ini biasanya menjadi pintu pertama kerja sama bisnis, jadi isinya harus jelas, sopan, dan mudah dipahami dalam sekali baca.</p>
<p>Struktur surat penawaran yang baik dimulai dari kop atau identitas usaha, tanggal dan nomor surat, pihak yang dituju, lalu pembuka singkat. Bagian inti berisi rincian penawaran: nama barang atau jasa, spesifikasi, harga, jumlah, dan syarat seperti waktu pengerjaan atau garansi. Tutup dengan masa berlaku penawaran dan ajakan untuk menghubungi kamu bila ada pertanyaan.</p>
<p>Dua kesalahan umum adalah harga yang tidak disertai rincian dan penawaran tanpa masa berlaku. Harga tanpa rincian membuat calon pembeli ragu, sedangkan penawaran tanpa batas waktu bisa ditagih bertahun-tahun kemudian dengan harga lama. Cantumkan selalu kalimat seperti: penawaran ini berlaku 14 hari sejak tanggal surat.</p>
<p>Surat penawaran yang rapi menunjukkan usaha kamu serius. Lewat Kejar Target di Kantor Desa, formatnya sudah disiapkan; kamu tinggal mengisi rincian dan mengunduh PDF-nya.</p>
HTML,
                ],
                [
                    'judul' => 'Slip Gaji: Kecil-kecil Penting',
                    'tipe' => 'artikel',
                    'poin' => 10,
                    'isi' => <<<'HTML'
<p>Slip gaji adalah bukti tertulis pembayaran gaji kepada karyawan. Dokumen ini penting untuk dua pihak: karyawan memakainya untuk keperluan administrasi seperti pengajuan kredit, sedangkan pemilik usaha memakainya sebagai arsip pengeluaran yang rapi dan bisa dipertanggungjawabkan.</p>
<p>Komponen slip gaji umumnya terdiri dari gaji pokok, tunjangan seperti transport dan makan, lembur bila ada, lalu potongan seperti pajak penghasilan pasal 21, iuran BPJS, atau cicilan. Di bagian bawah selalu ada gaji bersih, yaitu jumlah akhir yang diterima karyawan setelah semua potongan.</p>
<p>Kesalahan yang sering terjadi adalah potongan yang tidak dijelaskan, total yang tidak cocok antara slip dan transfer, dan slip yang hanya diberikan saat karyawan meminta. Berikan slip setiap periode gaji, simpan salinannya, dan pastikan angka di slip sama persis dengan uang yang ditransfer.</p>
<p>Usaha kecil yang baru punya satu atau dua karyawan tetap dianjurkan memakai slip gaji. Format standarnya tersedia gratis di Kejar Target, termasuk hitungan PPh 21 sederhana, jadi kamu tidak perlu menyusun dari nol di spreadsheet.</p>
HTML,
                ],
            ],
        ],
        [
            'slug' => 'ngonten-buat-jualan',
            'judul' => 'Ngonten Buat Jualan',
            'keterangan' => 'Dasar membuat konten jualan yang jujur dan konsisten untuk media sosial usaha kecil, dari ide sampai jadwal posting.',
            'urutan' => 2,
            'materi' => [
                [
                    'judul' => 'Mulai Ngonten Tanpa Ribet',
                    'tipe' => 'artikel',
                    'poin' => 10,
                    'isi' => <<<'HTML'
<p>Konten jualan tidak harus mewah. Yang paling penting adalah jelas dan konsisten: calon pembeli bisa paham apa yang kamu jual, berapa harganya, dan bagaimana cara memesannya, hanya dari satu postingan.</p>
<p>Mulailah dari tiga jenis konten sederhana. Pertama, konten produk: foto atau video barang dengan harga dan cara order. Kedua, konten proses: suasana membuat, mengemas, atau mengantar pesanan, karena proses membangun kepercayaan. Ketiga, konten bukti: testimoni pembeli atau foto pesanan yang sudah sampai.</p>
<p>Soal jadwal, lebih baik dua kali seminggu secara tetap daripada sepuluh postingan dalam sehari lalu hilang sebulan. Tulis keterangan dengan bahasa sehari-hari, cantumkan harga bila memungkinkan, dan akhiri dengan ajakan yang jelas seperti: pesan lewat WhatsApp di nomor profil.</p>
<p>Video pembuka kelas ini sedang disiapkan. Setelah materi artikel ini selesai, kamu bisa lanjut kapan pun videonya terbit, dan progresmu tetap tersimpan.</p>
HTML,
                ],
                [
                    'judul' => 'Video: Kenalan dengan Konten Jualan',
                    'tipe' => 'video',
                    'youtube_id' => '',
                    'poin' => 10,
                    'isi' => '',
                ],
            ],
        ],
        [
            'slug' => 'ngatur-duit-usaha',
            'judul' => 'Ngatur Duit Usaha',
            'keterangan' => 'Keuangan dasar usaha kecil: memisahkan uang pribadi dan uang usaha, mencatat arus kas, dan menyiapkan dana cadangan.',
            'urutan' => 3,
            'materi' => [
                [
                    'judul' => 'Pisahkan Uang Usaha dan Uang Pribadi',
                    'tipe' => 'artikel',
                    'poin' => 10,
                    'isi' => <<<'HTML'
<p>Masalah keuangan yang paling sering menimpa usaha kecil bukanlah kurang laku, melainkan uang yang tercampur. Uang usaha dipakai belanja harian, uang pribadi menambal modal, dan di akhir bulan tidak ada yang tahu usaha ini sebenarnya untung atau rugi.</p>
<p>Langkah pertama sederhana: pisahkan tempatnya. Buka rekening atau dompet digital khusus usaha, dan biasakan semua pemasukan jualan masuk ke sana. Setiap kali kamu mengambil uang usaha untuk keperluan pribadi, catat sebagai gaji pemilik dengan jumlah dan tanggal yang jelas.</p>
<p>Langkah kedua adalah catatan arus kas sederhana: tanggal, keterangan, uang masuk, uang keluar, dan sisa. Cukup satu buku atau satu berkas spreadsheet. Dari catatan ini kamu bisa melihat pola, misalnya hari ramai, biaya yang diam-diam membengkak, atau pelanggan yang sering membayar terlambat.</p>
<p>Terakhir, sisihkan dana cadangan sedikit demi sedikit, misalnya 5 sampai 10 persen dari keuntungan, sampai terkumpul biaya operasional satu bulan. Dana ini yang menyelamatkan usaha saat penjualan sepi atau ada alat yang rusak mendadak. Setelah materi ini, kerjakan kuisnya untuk menguji pemahamanmu.</p>
HTML,
                ],
                [
                    'judul' => 'Kuis: Ngatur Duit Usaha',
                    'tipe' => 'kuis',
                    'poin' => 10,
                    'isi' => '{"soal":[{"tanya":"Apa langkah pertama supaya keuangan usaha tidak tercampur dengan uang pribadi?","pilihan":["Menyimpan semua uang di satu dompet supaya praktis","Memisahkan rekening atau dompet digital khusus usaha","Meminjam uang pribadi setiap modal kurang","Mencatat hanya pemasukan yang besar saja"],"jawaban":1},{"tanya":"Catatan arus kas yang sederhana setidaknya memuat apa saja?","pilihan":["Tanggal, keterangan, uang masuk, uang keluar, dan sisa","Nama semua pesaing usaha di sekitar","Daftar harga dari pemasok saja","Foto setiap produk yang terjual"],"jawaban":0},{"tanya":"Berapa perkiraan dana cadangan yang disarankan untuk disisihkan sampai terkumpul?","pilihan":["Tidak perlu dana cadangan","Semua keuntungan bulan pertama","5 sampai 10 persen dari keuntungan, sampai cukup untuk biaya operasional satu bulan","50 persen dari setiap pemasukan, tanpa kecuali"],"jawaban":2}]}',
                ],
            ],
        ],
    ];
}
