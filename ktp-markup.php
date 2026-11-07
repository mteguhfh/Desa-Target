<?php
// Markup halaman KTP Warga. Dimuat oleh ktp.php setelah includes/header.php.
// Variabel yang dipakai: $totalWarga, $pesan, $galat, $kartuWarga, $isian.
$adaKartu = $kartuWarga !== null;
$adaPesan = $pesan !== null;
$adaGalat = $galat !== [];
$tokenForm = (string) ($_SESSION['token_ktp'] ?? '');
?>

<section class="seksi sempit">
    <h1>KTP Warga Desa Target</h1>
    <p class="seksi-deskripsi">
        KTP ini tanda kamu resmi jadi warga Desa Target. Gratis, tanpa ribet.
        Isi nama dan pilih gang sesuai profesimu, kartumu langsung jadi.
        Sudah ada <?= number_format($totalWarga, 0, ',', '.') ?> warga yang tercatat.
    </p>

    <?php if ($adaPesan): ?>

    <p class="pesan pesan-info"><?= e($pesan) ?></p>
    <?php endif; ?>

    <?php if ($adaGalat): ?>

    <div class="pesan pesan-gagal">
        <p>Masih ada yang kurang pas:</p>
        <ul>
            <?php foreach ($galat as $g): ?>
                <li><?= e($g) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($adaKartu): ?>

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
        <input type="hidden" name="token" value="<?= e($tokenForm) ?>">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" required minlength="3" maxlength="50"
               autocomplete="name" placeholder="Contoh: Sari Wulandari"
               value="<?= e($isian['nama']) ?>">
        <label for="gang">Gang profesimu</label>
        <select id="gang" name="gang" required>
            <option value="">Pilih gang dulu</option>
            <?php foreach (GANG_WARGA as $gangPilihan): ?>
                <option value="<?= e($gangPilihan) ?>"<?= $isian['gang'] === $gangPilihan ? ' selected' : '' ?>><?= e($gangPilihan) ?></option>
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

