<?php
// Markup halaman KTP Warga. Dimuat oleh ktp.php setelah includes/header.php.
// Variabel yang dipakai: $totalWarga, $pesan, $galat, $kartuWarga, $isian.
$adaKartu = $kartuWarga !== null;
$adaPesan = $pesan !== null;
$adaGalat = $galat !== [];
$tokenForm = (string) ($_SESSION['token_ktp'] ?? '');
require_once __DIR__ . '/includes/avatar-markup.php';
?>
<?php if (!empty($kodePulihBaru)): ?>
<section class="seksi sempit pemulihan-kartu">
    <h2>Simpan kode pemulihan kartumu</h2>
    <p>Kode ini hanya ditampilkan sekali. Simpan bersama nomor warga di tempat pribadi.</p>
    <output id="kodePulihTerbit"><?= e($nomorKartu) ?> | <?= e($kodePulihBaru) ?></output>
</section>
<?php endif; ?>

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
            <?php $wKartu = warna_warga($kartuWarga); ?>
            <?= avatar_warga_html($wKartu['baju'], $wKartu['kulit'], $wKartu['topi'], 'ktp-avatar') ?>
            <dl class="ktp-data">
                <div>
                    <dt>Nama Warga</dt>
                    <dd class="ktp-nama"><?= e($kartuWarga['nama']) ?></dd>
                </div>
                <div>
                    <dt>Nomor Warga</dt>
                    <dd><?= e($nomorKartu ?? nomor_warga((int) $kartuWarga['id'])) ?></dd>
                </div>
                <div>
                    <dt>Gang</dt>
                    <dd><?= e($kartuWarga['gang']) ?></dd>
                </div>
                <div>
                    <dt>Saldo Poin</dt>
                    <dd class="ktp-poin"><?= (int) ($kartuWarga['poin'] ?? 0) ?> poin</dd>
                </div>
                <div>
                    <dt>Tanggal Bergabung</dt>
                    <dd><?= e(format_tanggal($kartuWarga['created_at'])) ?></dd>
                </div>
                <div>
                    <dt>Warna di Peta</dt>
                    <dd class="ktp-warna">
                        <?php foreach ([
                            'baju' => ['label' => 'Baju', 'palet' => WARNA_BAJU],
                            'kulit' => ['label' => 'Kulit', 'palet' => WARNA_KULIT],
                            'topi' => ['label' => 'Topi', 'palet' => WARNA_TOPI],
                        ] as $bagian => $info): ?>
                            <span class="ktp-warna-titik"
                                  style="background: <?= e($info['palet'][$wKartu[$bagian]]['hex']) ?>"
                                  title="<?= e($info['label'] . ': ' . $info['palet'][$wKartu[$bagian]]['nama']) ?>"></span>
                        <?php endforeach; ?>
                        <span class="ktp-warna-teks">
                            Baju <?= e(WARNA_BAJU[$wKartu['baju']]['nama']) ?>,
                            kulit <?= e(strtolower(WARNA_KULIT[$wKartu['kulit']]['nama'])) ?>,
                            topi <?= e(strtolower(WARNA_TOPI[$wKartu['topi']]['nama'])) ?>
                        </span>
                    </dd>
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
        Kembali ke <a href="peta.php">Peta Desa</a> buat lihat namamu berdiri di depan balai desa.
    </p>
    <?php if (($kunciKartu ?? '') !== ''): ?>
    <?php // Identitas untuk fitur poin di browser ini. Kuncinya ikut nomor,
          // supaya sabuk pengaman: nomor saja tidak cukup untuk warga lain. ?>
    <span id="ktpIdentitas" hidden
          data-nomor="<?= e($nomorKartu) ?>"
          data-kunci="<?= e($kunciKartu) ?>"
          data-nama="<?= e($kartuWarga['nama']) ?>"
          data-gang="<?= e($kartuWarga['gang']) ?>"></span>
    <script>
    (function () {
        var identitas = document.getElementById('ktpIdentitas');
        if (!identitas) return;
        try {
            localStorage.setItem('dt_nomor', identitas.getAttribute('data-nomor'));
            localStorage.setItem('dt_kunci', identitas.getAttribute('data-kunci'));
        } catch (e) { /* localStorage diblokir: fitur poin hanya perlu nomor saat dibuka */ }
    })();
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <form class="formulir" method="post" action="ktp.php">
        <input type="hidden" name="token" value="<?= e($tokenForm) ?>">
        <label for="nama">Nama panggilan</label>
        <input type="text" id="nama" name="nama" required minlength="3" maxlength="50"
               autocomplete="nickname" placeholder="Contoh: Sari, Ucup, atau Sari W."
               value="<?= e($isian['nama']) ?>">
        <p class="formulir-catatan">Pakai nama panggilan yang nyaman dilihat warga. Jangan gunakan nama lengkap.</p>
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

        <fieldset class="pilih-warna">
            <legend>Tampilan figurnya di peta</legend>
            <p class="formulir-catatan">
                Pilih warna yang paling mirip kamu. Figurmu di peta 3D memakai warna ini,
                dan bisa diubah kapan saja dengan bikin KTP lagi.
            </p>
            <?php foreach ([
                'baju' => ['label' => 'Warna baju', 'palet' => WARNA_BAJU],
                'kulit' => ['label' => 'Warna kulit', 'palet' => WARNA_KULIT],
                'topi' => ['label' => 'Warna topi', 'palet' => WARNA_TOPI],
            ] as $bagian => $info): ?>
                <div class="pilih-warna-baris" role="radiogroup" aria-label="<?= e($info['label']) ?>">
                    <span class="pilih-warna-label"><?= e($info['label']) ?></span>
                    <span class="pilih-warna-swal">
                        <?php foreach ($info['palet'] as $kunci => $warna): ?>
                            <?php $dipilih = ($isian['warna_' . $bagian] ?? WARNA_BAWAAN[$bagian]) === $kunci; ?>
                            <label class="pilih-warna-satu" title="<?= e($warna['nama']) ?>">
                                <input type="radio" name="warna_<?= e($bagian) ?>" value="<?= e($kunci) ?>"
                                    <?= $dipilih ? 'checked' : '' ?>>
                                <span class="pilih-warna-bulat" style="background: <?= e($warna['hex']) ?>"></span>
                                <span class="sr-saja"><?= e($warna['nama']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <label class="izin-penerbitan"><input type="checkbox" name="izin_penerbitan" value="1" required> Saya mengizinkan nama panggilan dan tampilan kartu ini tampil di web Desa Target. Jika belum 18 tahun, saya sudah mendapat izin orang tua atau wali.</label>

        <button type="submit" class="tombol tombol-utama besar">Terbitkan KTP Warga</button>
    </form>
</section>
<section class="seksi sempit">
<?php require __DIR__ . '/includes/pemulihan-markup.php'; ?>
</section>
<script>window.__tokenKtp = <?= json_encode($tokenForm) ?>;</script>
<?php require __DIR__ . '/includes/pemulihan-skrip.php'; ?>
