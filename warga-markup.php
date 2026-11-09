<?php
// Markup halaman daftar warga dan hasil pencarian.
// Variabel yang dipakai: $mencari, $kunci, $warga, $totalWarga, $papanGang,
// $adaLebihBanyak, $batasTampil.
?>
<section class="seksi seksi-warga">
    <div class="seksi-kepala">
        <h2><?= $mencari ? 'Hasil pencarian' : 'Warga Desa Target' ?></h2>
        <a href="peta">Kembali ke peta desa</a>
    </div>

    <p class="seksi-deskripsi">
        <?php if ($mencari): ?>
            <?= count($warga) ?> warga cocok dengan "<?= e($kunci) ?>".
            <a href="warga">Lihat semua warga</a>
            &middot; <a href="peta?cari=<?= urlencode($kunci) ?>">Lihat hasilnya di peta</a>
        <?php else: ?>
            <?= number_format($totalWarga, 0, ',', '.') ?> warga sudah tercatat di balai desa. Cari berdasarkan nama atau nomor kartu.
        <?php endif; ?>
    </p>

    <form class="cari-warga cari-warga-halaman" method="get" action="warga">
        <label class="sr-saja" for="cariWargaKunci">Nama atau nomor warga</label>
        <input class="cari-warga-isian" id="cariWargaKunci" name="cari" type="search"
               placeholder="Nama atau DT-0007" autocomplete="off" maxlength="50"
               value="<?= e($kunci) ?>" enterkeyhint="search">
        <button class="tombol tombol-utama cari-warga-tombol" type="submit">Cari</button>
    </form>

    <?php if ($warga === []): ?>
        <p class="seksi-deskripsi">
            <?php if ($mencari): ?>
                Tidak ada warga yang cocok. Coba nama lain, atau nomor kartu seperti DT-0001.
            <?php else: ?>
                Belum ada warga terdaftar. <a href="ktp">Bikin KTP Warga</a> untuk jadi yang pertama.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <ul class="daftar-warga">
            <?php foreach ($warga as $w): ?>
                <li class="daftar-warga-baris">
                    <a class="daftar-warga-tautan" href="ktp?kartu=<?= (int) $w['id'] ?>">
                        <span class="daftar-warga-nomor"><?= e(nomor_warga((int) $w['id'])) ?></span>
                        <span class="daftar-warga-nama"><?= e($w['nama']) ?></span>
                        <span class="daftar-warga-gang"><?= e($w['gang']) ?></span>
                        <?php if (kelas_selesai_warga($pdo, nomor_warga((int) $w['id'])) > 0): ?>
                            <span class="lencana-warga-cerdas">Warga Cerdas</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($adaLebihBanyak): ?>
            <p class="seksi-deskripsi">Hanya menampilkan <?= (int) $batasTampil ?> warga pertama. Pakai pencarian untuk mempersempit.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="seksi">
    <h2>Papan Gang</h2>
    <p class="seksi-deskripsi">Sebaran warga di lima gang profesi.</p>
    <?php require __DIR__ . '/includes/papan-gang.php'; ?>
</section>

<section class="ajakan">
    <img src="assets/bang-uju.png" alt="" class="ajakan-avatar">
    <div>
        <h2>Belum punya KTP Warga?</h2>
        <p>Bikin sekarang, gratis. Namamu langsung tercatat sebagai warga resmi Desa Target.</p>
    </div>
    <a class="tombol tombol-utama" href="ktp">Bikin KTP Warga</a>
</section>
