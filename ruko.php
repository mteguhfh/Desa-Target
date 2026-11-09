<?php
// Halaman Ruko Warga: tempat warga mengklaim satu ruko di peta untuk
// memajang usahanya (SPESIFIKASI-FITUR-BELUM-DIBANGUN.md, Fitur 3).
//
// Alurnya sengaja sama dengan ktp.php: POST lalu redirect (PRG) supaya
// kirim ulang karena refresh tidak menggandakan ruko, ditambah token sesi.
//
// Identitas tetap nomor KTP + kunci rahasia dari kartu. Tanpa keduanya
// halaman ini hanya menampilkan ajakan bikin KTP dan daftar ruko yang sudah
// berdiri; tidak ada akun atau kata sandi baru.
//
// Ruko menempati kavling kosong berikutnya menurut urutan id bangunan, jalur
// yang sama dengan peta 2D dan peta 3D (tidak ada logika kavling kedua).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/unggah-usaha.php';

// Panel Ruko di peta (ADR-0006) membaca data lewat jalur yang sama:
//   GET ruko.php?json=1&nomor=DT-0001&kunci=...
//   -> 200 { ruko: { namaUsaha, cerita, tautan, gang } | null }
//   -> 403 { galat }   nomor dan kunci tidak cocok (jawaban sama dengan
//                      nomor tidak dikenal, seperti endpoint lain)
// Hanya membaca: klaim dan lepas ruko tetap lewat POST di halaman ini.
if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $wargaJson = warga_dari_nomor_dan_kunci(db(), $_GET['nomor'] ?? '', $_GET['kunci'] ?? '');
    if ($wargaJson === null) {
        http_response_code(403);
        echo json_encode(['galat' => 'Nomor atau kunci warga tidak cocok.']);
        exit;
    }
    $rukoJson = ruko_milik(db(), nomor_warga((int) $wargaJson['id']));
    echo json_encode(
        ['ruko' => $rukoJson === null ? null : [
            'namaUsaha' => (string) $rukoJson['nama'],
            'cerita' => (string) ($rukoJson['komentar'] ?? ''),
            'tautan' => (string) ($rukoJson['tautan'] ?? ''),
            'gang' => (string) ($rukoJson['gang'] ?? ''),
            'gambar' => (string) ($rukoJson['gambar'] ?? ''),
        ]],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}


// Cookie sesi ditulis setelah kepala halaman dicetak, jadi peramban baru
// memilikinya pada kunjungan berikutnya. Sama dengan ktp.php: formulir
// memakai token sesi, dan kunjungan pertama memang hanya membangun sesi.
session_start();

$pdo = db();
$totalKavling = count(require __DIR__ . '/includes/kavling.php');
$pesan = $_SESSION['pesan_ruko'] ?? null;
unset($_SESSION['pesan_ruko']);

// Identitas dikirim di setiap kiriman formulir (nomor + kunci dari
// localStorage) dan juga dititipkan di sesi setelah kiriman pertama. Salah
// satu cocok dengan kartu di basis data sudah cukup; kiriman POST diperiksa
// dulu supaya formulir yang dibuka di tab baru tetap bekerja.
$nomorSesi = (string) ($_SESSION['ruko_nomor'] ?? '');
$kunciSesi = (string) ($_SESSION['ruko_kunci'] ?? '');
$nomorKirim = (string) ($_POST['nomor'] ?? '');
$kunciKirim = (string) ($_POST['kunci'] ?? '');

$wargaAktif = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($nomorKirim !== '' || $kunciKirim !== '')) {
    $wargaAktif = warga_dari_nomor_dan_kunci($pdo, $nomorKirim, $kunciKirim);
}
if ($wargaAktif === null && $nomorSesi !== '' && $kunciSesi !== '') {
    $wargaAktif = warga_dari_nomor_dan_kunci($pdo, $nomorSesi, $kunciSesi);
}

$galat = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenForm = (string) ($_POST['token'] ?? '');
    $tokenSesi = (string) ($_SESSION['token_ruko'] ?? '');
    $wargaKirim = $wargaAktif;

    if ($tokenSesi === '' || !hash_equals($tokenSesi, $tokenForm)) {
        $_SESSION['pesan_ruko'] = 'Formulir ini sepertinya sudah terkirim sebelumnya. Coba kirim ulang dari halaman ini.';
        header('Location: ruko');
        exit;
    }

    if ($wargaKirim === null) {
        $_SESSION['pesan_ruko'] = 'Buka kartu wargamu dulu supaya akun peramban ini dikenali.';
        header('Location: ruko');
        exit;
    }

    $nomorKirim = nomor_warga((int) $wargaKirim['id']);
    $kunciKirim = (string) $wargaKirim['kunci'];
    $_SESSION['ruko_nomor'] = $nomorKirim;
    $_SESSION['ruko_kunci'] = $kunciKirim;
    $_SESSION['token_ruko'] = bin2hex(random_bytes(16));

    $aksi = (string) ($_POST['aksi'] ?? 'klaim');
    $fotoBaru = null;
    if (is_string($_POST['gambar'] ?? null) && str_starts_with($_POST['gambar'], 'assets/uploads/') && !str_starts_with($_POST['gambar'], 'assets/uploads/usaha/' . $nomorKirim . '-')) {
        $_SESSION['galat_ruko'] = ['Pilih foto milik usahamu sendiri.'];
        header('Location: ruko');
        exit;
    }
    if (in_array($aksi, ['klaim', 'ubah'], true) && empty($_POST['hapus_gambar']) && ($_FILES['foto_usaha']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $fotoBaru = unggah_foto_usaha($_FILES['foto_usaha'], $nomorKirim);
            $_POST['gambar'] = $fotoBaru;
        } catch (RuntimeException $e) {
            $_SESSION['galat_ruko'] = [$e->getMessage()];
            header('Location: ruko');
            exit;
        }
    }
    if ($aksi === 'lepas') {
        $hasil = lepas_ruko($pdo, $nomorKirim, $kunciKirim);
        $_SESSION['pesan_ruko'] = ($hasil['galat'] ?? null) === null
            ? 'Rukomu sudah dilepas. Bangunanmu tidak tampil lagi di peta.'
            : 'Tidak ada ruko aktif atas namamu.';
        header('Location: ruko');
        exit;
    }

    if ($aksi === 'ubah') {
        // Hapus gambar hanya bila dicentang; selain itu pilihan radio yang
        // dipakai, jadi tidak ada cara tidak sengaja menghapus gambar.
        $gambarPilih = !empty($_POST['hapus_gambar']) ? '' : ($_POST['gambar'] ?? '');
        $hasilUbah = ubah_ruko(
            $pdo,
            $nomorKirim,
            $kunciKirim,
            (string) ($_POST['nama_usaha'] ?? ''),
            (string) ($_POST['cerita'] ?? ''),
            $_POST['tautan'] ?? '',
            $gambarPilih
        );
        if (($hasilUbah['galat'] ?? null) === null) {
            $rukoKatalog = ruko_milik($pdo, $nomorKirim);
            if ($rukoKatalog) simpan_katalog($pdo, 'bangunan', (int) $rukoKatalog['id'], $_POST['kategori'] ?? 'lainnya', $_POST['harga'] ?? '');
            $_SESSION['pesan_ruko'] = 'Rukomu sudah diperbarui.';
            header('Location: ruko');
            exit;
        }
        $_SESSION['isian_ruko'] = [
            'nama_usaha' => (string) ($_POST['nama_usaha'] ?? ''),
            'cerita' => (string) ($_POST['cerita'] ?? ''),
            'tautan' => (string) ($_POST['tautan'] ?? ''),
            'gambar' => (string) ($_POST['gambar'] ?? ''),
        ];
        if ($fotoBaru) unlink(__DIR__ . '/' . $fotoBaru);
        $_SESSION['galat_ruko'] = match ($hasilUbah['galat']) {
            'nama' => ['Nama usaha diisi 3 sampai 60 karakter.'],
            'cerita' => ['Cerita usaha paling panjang ' . RUKO_CERITA_MAKS . ' karakter.'],
            'tautan' => ['Tautan jualan harus diawali https:// dan berisi alamat situs yang benar.'],
            'gambar' => ['Gambar itu tidak tersedia. Pilih salah satu gambar yang ada di daftar.'],
            default => ['Rukomu belum bisa diubah. Buka lagi kartu wargamu.'],
        };
        header('Location: ruko');
        exit;
    }

    $hasil = klaim_ruko(
        $pdo,
        $nomorKirim,
        $kunciKirim,
        (string) ($_POST['nama_usaha'] ?? ''),
        (string) ($_POST['cerita'] ?? ''),
        $_POST['tautan'] ?? '',
        (string) $wargaKirim['gang'],
        $totalKavling,
        $_POST['gambar'] ?? ''
    );

    if (($hasil['galat'] ?? null) === null) {
        $rukoKatalog = ruko_milik($pdo, $nomorKirim);
        if ($rukoKatalog) simpan_katalog($pdo, 'bangunan', (int) $rukoKatalog['id'], $_POST['kategori'] ?? 'lainnya', $_POST['harga'] ?? '');
        $_SESSION['pesan_ruko'] = 'Selamat! Rukomu sudah berdiri di Desa Target.';
        header('Location: ruko');
        exit;
    }

    $galat = match ($hasil['galat']) {
        'nama' => ['Nama usaha diisi 3 sampai 60 karakter.'],
        'cerita' => ['Cerita usaha paling panjang ' . RUKO_CERITA_MAKS . ' karakter.'],
        'tautan' => ['Tautan jualan harus diawali https:// dan berisi alamat situs yang benar.'],
        'punya' => ['Kamu sudah punya satu ruko. Satu warga satu ruko, ya.'],
        'penuh' => ['Semua kavling desa sudah terisi. Tunggu kavling baru dibuka.'],
        'poin' => ['Poinmu belum cukup untuk membuka ruko.'],
        'gambar' => ['Gambar itu tidak tersedia. Pilih salah satu gambar yang ada di daftar.'],
        default => ['Kartu wargamu tidak dikenali. Buka lagi kartu wargamu.'],
    };
    if ($fotoBaru) unlink(__DIR__ . '/' . $fotoBaru);
    $_SESSION['isian_ruko'] = [
        'nama_usaha' => (string) ($_POST['nama_usaha'] ?? ''),
        'cerita' => (string) ($_POST['cerita'] ?? ''),
        'tautan' => (string) ($_POST['tautan'] ?? ''),
        'gambar' => (string) ($_POST['gambar'] ?? ''),
        'hapus_gambar' => !empty($_POST['hapus_gambar']),
    ];
    $_SESSION['galat_ruko'] = $galat;
    header('Location: ruko');
    exit;
}

$galat = $_SESSION['galat_ruko'] ?? [];
$isianRuko = $_SESSION['isian_ruko'] ?? ['nama_usaha' => '', 'cerita' => '', 'tautan' => ''];
unset($_SESSION['galat_ruko'], $_SESSION['isian_ruko']);

if (empty($_SESSION['token_ruko'])) {
    $_SESSION['token_ruko'] = bin2hex(random_bytes(16));
}

// Simpan identitas yang terbukti cocok, supaya formulir yang memuat ulang
// (mis. karena galat validasi) tetap dikenali.
if ($wargaAktif !== null && ($nomorKirim !== '' || $nomorSesi !== '')) {
    $_SESSION['ruko_nomor'] = nomor_warga((int) $wargaAktif['id']);
    $_SESSION['ruko_kunci'] = (string) $wargaAktif['kunci'];
}

$nomorAktif = $wargaAktif === null ? '' : nomor_warga((int) $wargaAktif['id']);
$kunciAktif = $wargaAktif === null ? '' : (string) $wargaAktif['kunci'];
$rukoSaya = $nomorAktif === '' ? null : ruko_milik($pdo, $nomorAktif);
$daftarRuko = array_values(array_filter(
    semua_bangunan_aktif($pdo),
    fn($b) => ($b['jenis'] ?? '') === 'ruko'
));

$judulHalaman = 'Ruko Warga';
$halamanAktif = 'ruko';
require __DIR__ . '/includes/header.php';

require __DIR__ . '/ruko-markup.php';

require __DIR__ . '/includes/footer.php';
