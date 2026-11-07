<?php
// Halaman KTP Warga: formulir pendaftaran dan tampilan kartu warga.
// Alur kirim memakai pola POST lalu redirect (PRG) plus token sekali pakai,
// supaya kirim ulang karena refresh tidak menggandakan data warga.

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

    if (panjang_teks($nama) < 3 || panjang_teks($nama) > 50) {
        $galat[] = 'Nama lengkap diisi 3 sampai 50 karakter ya.';
    } elseif (!preg_match("/^[\p{L} .'-]+$/u", $nama)) {
        $galat[] = 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.';
    }
    if (!in_array($gang, GANG_WARGA, true)) {
        $galat[] = 'Pilih dulu gang profesimu.';
    }

    if ($galat === []) {
        $stmt = $pdo->prepare('INSERT INTO warga (nama, gang, created_at) VALUES (?, ?, ?)');
        $stmt->execute([$nama, $gang, date('Y-m-d H:i:s')]);
        $idBaru = (int) $pdo->lastInsertId();

        // Token lama hangus, ganti baru untuk pengiriman berikutnya.
        $_SESSION['token_ktp'] = bin2hex(random_bytes(16));
        $_SESSION['pesan_ktp'] = 'Selamat datang di Desa Target! Ini KTP Wargamu, tersimpan rapi di balai desa.';

        header('Location: ktp.php?kartu=' . $idBaru);
        exit;
    }

    // Ada galat validasi: simpan isian lama supaya tidak perlu mengetik ulang.
    $_SESSION['isian_ktp'] = ['nama' => $nama, 'gang' => $gang];
    $_SESSION['galat_ktp'] = $galat;
    header('Location: ktp.php');
    exit;
}

// Ambil pesan dan isian dari sesi (sekali tampil, lalu dibersihkan).
$pesan = $_SESSION['pesan_ktp'] ?? null;
$galat = $_SESSION['galat_ktp'] ?? [];
$isian = $_SESSION['isian_ktp'] ?? ['nama' => '', 'gang' => ''];
unset($_SESSION['pesan_ktp'], $_SESSION['galat_ktp'], $_SESSION['isian_ktp']);

if (isset($_GET['kartu']) && ctype_digit((string) $_GET['kartu'])) {
    $kartuWarga = cari_warga($pdo, (int) $_GET['kartu']);
}

// Token baru untuk formulir, dibuat setelah token lama dipakai atau saat pertama datang.
if (empty($_SESSION['token_ktp'])) {
    $_SESSION['token_ktp'] = bin2hex(random_bytes(16));
}

$totalWarga = hitung_warga($pdo);

$judulHalaman = 'KTP Warga';
$halamanAktif = 'ktp';
require __DIR__ . '/includes/header.php';
?>

<section class="seksi sempit">
    <h1>KTP Warga Desa Target</h1>
    <p class="seksi-deskripsi">
        KTP ini tanda kamu resmi jadi warga Desa Target. Gratis, tanpa ribet.
        Isi nama dan pilih gang sesuai profesimu, kartumu langsung jadi.
        Sudah ada <?= number_format($totalWarga, 0, ',', '.') ?> warga yang tercatat.
    </p>

    <?php if ($pesan !== null): ?>
        <p class="pesan pesan-info"><?= e($pesan) ?></p>
    <?php endif; ?>

    <?php if ($galat !== []): ?>
        <div class="pesan pesan-gagal">
            <p>Masih ada yang kurang pas:</p>
            <ul>
                <?php foreach ($galat as $g): ?>
                    <li><?= e($g) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($kartuWarga !== null): ?>
        <article class="ktp-kartu">
            <div class="ktp-kepala">
                <span>KARTU TANDA WARGA</span>
                <strong>DESA TARGET</strong>
            </div>
            <div class="ktp-badan">
                <img src="assets/bang-uju.png" alt="Foto warga kehormatan: Bang Uju" class="ktp-foto">
                <dl class="ktp-data">
                    <div>
                        <dt>Nama Warga</dt>
                        <dd class="ktp-nama"><?= e($kartuWarga['nama']) ?></dd>
                    </div>
                    <div>
                        <dt>Nomor Warga</dt>
                        <dd><?= e(nomor_warga((int) $kartuWarga['id'])) ?></dd>
                    </div>
                    <div>
                        <dt>Gang</dt>
                        <dd><?= e($kartuWarga['gang']) ?></dd>
                    </div>
                    <div>
                        <dt>Tanggal Bergabung</dt>
                        <dd><?= e(format_tanggal($kartuWarga['created_at'])) ?></dd>
                    </div>
                </dl>
            </div>
            <div class="ktp-kaki">
                <span>Kepala Desa: <strong>Bang Uju</strong></span>
                <span>Desa Target, desanya UMKM hebat</span>
            </div>
        </article>
        <p class="ktp-setelah">
            Simpan alamat halaman ini untuk melihat kartumu lagi kapan pun.
            Lanjut ke <a href="peta.php">Peta Desa</a> buat lihat bangunan yang sudah berdiri.
        </p>
    <?php endif; ?>

    <form class="formulir" method="post" action="ktp.php">
        <input type="hidden" name="token" value="<?= e($_SESSION['token_ktp']) ?>">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" required minlength="3" maxlength="50"
               autocomplete="name" placeholder="Contoh: Sari Wulandari"
               value="<?= e($isian['nama']) ?>">
        <label for="gang">Gang profesimu</label>
        <select id="gang" name="gang" required>
            <option value="">Pilih gang dulu</option>
            <?php foreach (GANG_WARGA as $gang): ?>
                <option value="<?= e($gang) ?>"<?= $isian['gang'] === $gang ? ' selected' : '' ?>><?= e($gang) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="formulir-catatan">
            Gang Dagang buat yang jualan barang, Gang Kuliner buat makanan dan minuman,
            Gang Jasa buat layanan, Gang Freelancer buat pekerja lepas, dan Gang Kerajinan
            buat pembuat produk tangan.
        </p>
        <button type="submit" class="tombol tombol-utama besar">Terbitkan KTP Warga</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
