<?php
require_once __DIR__ . '/db.php';

$pdo = db();
$totalWarga = hitung_warga($pdo);
$totalBangunan = hitung_bangunan($pdo);
$terbaru = bangunan_terbaru($pdo);
$papanGang = papan_gang($pdo);

$judulHalaman = 'Beranda';
$halamanAktif = 'beranda';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-teks">
        <p class="hero-sapaan">Selamat datang di</p>
        <h1>Desa Target</h1>
        <p class="hero-deskripsi">
            Desa fiktif tempat para UMKM hebat tinggal. Desa ini dibangun dari komentar warga:
            komen yang paling banyak disukai hari ini, besoknya langsung didirikan jadi usaha baru
            oleh Bang Uju, Kepala Desa kami.
        </p>
        <div class="hero-aksi">
            <a class="tombol tombol-utama" href="ktp.php">Bikin KTP Warga, Gratis</a>
            <a class="tombol tombol-biasa" href="peta.php">Lihat Peta Desa</a>
        </div>
        <div class="hero-statistik">
            <div class="statistik">
                <span class="statistik-angka"><?= number_format($totalWarga, 0, ',', '.') ?></span>
                <span class="statistik-label">Warga terdaftar</span>
            </div>
            <div class="statistik">
                <span class="statistik-angka"><?= number_format($totalBangunan, 0, ',', '.') ?></span>
                <span class="statistik-label">Bangunan berdiri</span>
            </div>
            <div class="statistik">
                <span class="statistik-angka"><?= count(GANG_WARGA) ?></span>
                <span class="statistik-label">Gang profesi</span>
            </div>
        </div>
    </div>
    <div class="hero-gambar">
        <img src="assets/bang-uju-wave.png" alt="Bang Uju melambaikan tangan menyambut warga baru">
        <p class="hero-kutip">"Ikutin Bang Uju, biar sama-sama sampai tujuan!"</p>
    </div>
</section>

<?php if ($terbaru !== null): ?>
<section class="seksi">
    <div class="seksi-kepala">
        <h2>Pembangunan Hari Ini</h2>
        <a href="peta.php">Lihat semua di peta desa</a>
    </div>
    <article class="kartu-bangunan sorotan">
        <div class="kartu-bangunan-isi">
            <p class="label-kecil">Bangunan terbaru di desa</p>
            <h3><?= e($terbaru['nama']) ?></h3>
            <?php if (!empty($terbaru['gang'])): ?>
                <p class="kartu-meta"><span class="lencana-gang"><?= e($terbaru['gang']) ?></span></p>
            <?php endif; ?>
            <?php if (!empty($terbaru['komentar'])): ?>
                <p class="kartu-komentar">"<?= e($terbaru['komentar']) ?>"</p>
            <?php endif; ?>
            <p class="kartu-meta">
                <?php if (!empty($terbaru['diminta_oleh'])): ?>
                    Diminta oleh <?= e($terbaru['diminta_oleh']) ?>.
                <?php endif; ?>
                Berdiri <?= e(format_tanggal($terbaru['tanggal'])) ?>.
            </p>
        </div>
    </article>
</section>
<?php endif; ?>

<section class="seksi">
    <h2>Cara Main di Desa Target</h2>
    <div class="langkah-grid">
        <div class="langkah">
            <span class="langkah-nomor">1</span>
            <h3>Follow, langsung jadi warga</h3>
            <p>Follow <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">@desatarget</a> di Instagram, lalu bikin KTP Warga di situs ini biar namamu tercatat di balai desa.</p>
        </div>
        <div class="langkah">
            <span class="langkah-nomor">2</span>
            <h3>Komen usaha impianmu</h3>
            <p>Setiap hari Bang Uju membuka permintaan pembangunan. Tulis di komentar usaha apa yang mau didirikan di desa, warung kopi, bengkel, toko kue, apa saja.</p>
        </div>
        <div class="langkah">
            <span class="langkah-nomor">3</span>
            <h3>Like tertinggi dibangun besok</h3>
            <p>Komentar dengan like terbanyak langsung dibangun keesokan harinya dan muncul di Peta Desa, lengkap dengan nama pemintanya.</p>
        </div>
    </div>
</section>

<section class="seksi">
    <div class="seksi-kepala">
        <h2>Papan Gang</h2>
        <a href="ktp.php">Gabung gang-mu</a>
    </div>
    <p class="seksi-deskripsi">Warga terbagi ke lima gang sesuai profesinya. Gang mana yang paling ramai?</p>
    <?php require __DIR__ . '/includes/papan-gang.php'; ?>
</section>

<section class="ajakan">
    <img src="assets/bang-uju.png" alt="" class="ajakan-avatar">
    <div>
        <h2>Belum punya KTP Warga?</h2>
        <p>Bikin sekarang, gratis. Namamu langsung tercatat sebagai warga resmi Desa Target dengan nomor warga sendiri.</p>
    </div>
    <a class="tombol tombol-utama" href="ktp.php">Bikin KTP Warga</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
