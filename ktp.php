<?php
// Halaman KTP Warga: formulir pendaftaran dan tampilan kartu warga.
// Alur kirim memakai pola POST lalu redirect (PRG) plus token sekali pakai,
// supaya kirim ulang karena refresh tidak menggandakan data warga.
// Logikanya di sini; markup-nya di ktp-markup.php.

session_start();
require_once __DIR__ . '/db.php';

// Panjang teks dalam karakter. Pakai mbstring bila tersedia,
// kalau tidak, hitung karakter UTF-8 secara manual supaya tidak fatal.
function panjang_teks(string $teks): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($teks);
    }
    return (int) preg_match_all('/./us', $teks);
}

$pdo = db();
$galat = [];
$kartuWarga = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenForm = $_POST['token'] ?? '';
    $tokenSesi = $_SESSION['token_ktp'] ?? '';

    if ($tokenSesi === '' || !hash_equals($tokenSesi, $tokenForm)) {
        // Token tidak cocok: kemungkinan besar kiriman ganda dari refresh.
        $_SESSION['pesan_ktp'] = 'Formulir ini sepertinya sudah terkirim sebelumnya. Kalau namamu belum muncul, coba kirim ulang dari formulir di bawah.';
        header('Location: ktp.php');
        exit;
    }

    $nama = trim((string) ($_POST['nama'] ?? ''));
    $gang = trim((string) ($_POST['gang'] ?? ''));

    // Warna tampilan. Bila tidak dikirim atau tidak dikenal, pakai bawaan:
    // warna bukan hal wajib, jadi tidak boleh menggagalkan pendaftaran.
    $warna = [];
    foreach (['baju', 'kulit', 'topi'] as $bagian) {
        $warna[$bagian] = warna_sah($bagian, $_POST['warna_' . $bagian] ?? null) ?? WARNA_BAWAAN[$bagian];
    }

    if (panjang_teks($nama) < 3 || panjang_teks($nama) > 50) {
        $galat[] = 'Nama lengkap diisi 3 sampai 50 karakter ya.';
    } elseif (!preg_match("/^[\p{L} .'-]+$/u", $nama)) {
        $galat[] = 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.';
    }
    if (!in_array($gang, GANG_WARGA, true)) {
        $galat[] = 'Pilih dulu gang profesimu.';
    }

    if ($galat === []) {
        $stmt = $pdo->prepare(
            'INSERT INTO warga (nama, gang, created_at, warna_baju, warna_kulit, warna_topi) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $nama, $gang, date('Y-m-d H:i:s'),
            $warna['baju'], $warna['kulit'], $warna['topi'],
        ]);
        $idBaru = (int) $pdo->lastInsertId();

        // Token lama hangus, ganti baru untuk pengiriman berikutnya.
        $_SESSION['token_ktp'] = bin2hex(random_bytes(16));
        $_SESSION['pesan_ktp'] = 'Selamat datang di Desa Target! Ini KTP Wargamu, tersimpan rapi di balai desa.';

        header('Location: ktp.php?kartu=' . $idBaru);
        exit;
    }

    // Ada galat validasi: simpan isian lama supaya tidak perlu mengetik ulang.
    $_SESSION['isian_ktp'] = ['nama' => $nama, 'gang' => $gang] + array_combine(
        ['warna_baju', 'warna_kulit', 'warna_topi'],
        [$warna['baju'], $warna['kulit'], $warna['topi']]
    );
    $_SESSION['galat_ktp'] = $galat;
    header('Location: ktp.php');
    exit;
}

// Ambil pesan dan isian dari sesi (sekali tampil, lalu dibersihkan).
$pesan = $_SESSION['pesan_ktp'] ?? null;
$galat = $_SESSION['galat_ktp'] ?? [];
$isian = $_SESSION['isian_ktp'] ?? [
    'nama' => '', 'gang' => '',
    'warna_baju' => WARNA_BAWAAN['baju'],
    'warna_kulit' => WARNA_BAWAAN['kulit'],
    'warna_topi' => WARNA_BAWAAN['topi'],
];
unset($_SESSION['pesan_ktp'], $_SESSION['galat_ktp'], $_SESSION['isian_ktp']);

if (isset($_GET['kartu']) && ctype_digit((string) $_GET['kartu'])) {
    $kartuWarga = cari_warga($pdo, (int) $_GET['kartu']);
}

if ($kartuWarga !== null) {
    // Kunci rahasia pemilik kartu ikut dicetak di kartunya sendiri, supaya
    // browser pemilik bisa menyimpannya (dt_nomor + dt_kunci di localStorage)
    // dan fitur yang butuh identitas bisa membuktikan bahwa dialah pemilik
    // kartu itu. Kunci tidak pernah tampil di daftar warga atau pencarian.
    $nomorKartu = nomor_warga((int) $kartuWarga['id']);
    $kunciKartu = (string) ($kartuWarga['kunci'] ?? '');
}

// Token baru untuk formulir, dibuat setelah token lama dipakai atau saat pertama datang.
if (empty($_SESSION['token_ktp'])) {
    $_SESSION['token_ktp'] = bin2hex(random_bytes(16));
}

$totalWarga = hitung_warga($pdo);

$judulHalaman = 'KTP Warga';
$halamanAktif = 'ktp';
require __DIR__ . '/includes/header.php';

require __DIR__ . '/ktp-markup.php';

require __DIR__ . '/includes/footer.php';
