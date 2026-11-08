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
        header('Location: ruko.php');
        exit;
    }

    if ($wargaKirim === null) {
        $_SESSION['pesan_ruko'] = 'Buka kartu wargamu dulu supaya akun peramban ini dikenali.';
        header('Location: ruko.php');
        exit;
    }

    $nomorKirim = nomor_warga((int) $wargaKirim['id']);
    $kunciKirim = (string) $wargaKirim['kunci'];
    $_SESSION['ruko_nomor'] = $nomorKirim;
    $_SESSION['ruko_kunci'] = $kunciKirim;
    $_SESSION['token_ruko'] = bin2hex(random_bytes(16));

    $aksi = (string) ($_POST['aksi'] ?? 'klaim');
    if ($aksi === 'lepas') {
        $hasil = lepas_ruko($pdo, $nomorKirim, $kunciKirim);
        $_SESSION['pesan_ruko'] = ($hasil['galat'] ?? null) === null
            ? 'Rukomu sudah dilepas. Bangunanmu tidak tampil lagi di peta.'
            : 'Tidak ada ruko aktif atas namamu.';
        header('Location: ruko.php');
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
        $totalKavling
    );

    if (($hasil['galat'] ?? null) === null) {
        $_SESSION['pesan_ruko'] = 'Selamat! Rukomu sudah berdiri di Desa Target.';
        header('Location: ruko.php');
        exit;
    }

    $galat = match ($hasil['galat']) {
        'nama' => ['Nama usaha diisi 3 sampai 60 karakter.'],
        'cerita' => ['Cerita usaha paling panjang ' . RUKO_CERITA_MAKS . ' karakter.'],
        'tautan' => ['Tautan jualan harus diawali https:// dan berisi alamat situs yang benar.'],
        'punya' => ['Kamu sudah punya satu ruko. Satu warga satu ruko, ya.'],
        'penuh' => ['Semua kavling desa sudah terisi. Tunggu kavling baru dibuka.'],
        'poin' => ['Poinmu belum cukup untuk membuka ruko.'],
        default => ['Kartu wargamu tidak dikenali. Buka lagi kartu wargamu.'],
    };
    $_SESSION['isian_ruko'] = [
        'nama_usaha' => (string) ($_POST['nama_usaha'] ?? ''),
        'cerita' => (string) ($_POST['cerita'] ?? ''),
        'tautan' => (string) ($_POST['tautan'] ?? ''),
    ];
    $_SESSION['galat_ruko'] = $galat;
    header('Location: ruko.php');
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
