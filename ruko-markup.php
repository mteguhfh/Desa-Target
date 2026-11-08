<?php
// Markup halaman Ruko Warga (Fitur 3). Logikanya di ruko.php.
//
// Variabel yang dipakai:
//   $wargaAktif   baris warga pemilik peramban, atau null (pengunjung)
//   $nomorAktif   nomor KTP warga aktif, '' bila tidak ada
//   $kunciAktif   kunci rahasia warga aktif
//   $rukoSaya     baris bangunan ruko aktif milik warga, atau null
//   $daftarRuko   semua ruko aktif di desa
//   $isianRuko    isian formulir terakhir (nama_usaha, cerita, tautan, gambar)
//   $galat        daftar pesan galat validasi
//   $pesan        pesan sekali tampil dari proses sebelumnya
//   $tokenRuko    token formulir
if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', 'UTF-8');
    }
}

$tokenRuko = (string) ($_SESSION['token_ruko'] ?? '');
// Gambar usaha yang boleh dipilih (lihat daftar_gambar_usaha() di db.php).
$pilihanGambar = daftar_gambar_usaha();
$gambarSaya = (string) ($rukoSaya['gambar'] ?? '');
$isianRuko = $isianRuko ?? ['nama_usaha' => '', 'cerita' => '', 'tautan' => ''];
$galat = $galat ?? [];
$pesan = $pesan ?? null;
$daftarRuko = $daftarRuko ?? [];
$rukoSaya = $rukoSaya ?? null;
$nomorAktif = $nomorAktif ?? '';
$kunciAktif = $kunciAktif ?? '';
?>
<main class="halaman ruko-halaman">
    <section class="kartu-formulir">
        <p class="label-kecil">RUKO WARGA</p>
        <h1>Buka ruko usahamu di desa</h1>
        <p class="seksi-deskripsi">
            Satu warga satu ruko, gratis. Namamu berdiri di peta sebagai pemilik usaha,
            dan pengunjung bisa langsung membuka tempat jualanmu. Ruko bukan iklan afiliasi:
            yang dikunjungi adalah tempat jualanmu sendiri.
        </p>

        <?php if ($pesan !== null): ?>
            <p class="formulir-pesan" role="status"><?= e($pesan) ?></p>
        <?php endif; ?>
        <?php foreach ($galat as $g): ?>
            <p class="formulir-galat" role="alert"><?= e($g) ?></p>
        <?php endforeach; ?>

        <?php // Peramban yang kartunya sudah tersimpan dikenali oleh skrip di bawah
        // lewat POST ke halaman ini, lalu halaman dimuat ulang sekali. ?>
        <div id="rukoCekIdentitas"<?= $wargaAktif === null ? '' : ' hidden' ?>>
            <h2 class="ruko-subjudul">Bikin KTP Warga dulu</h2>
            <p class="seksi-deskripsi">
                Ruko hanya untuk warga yang punya kartu. Kartu gratis, nomornya milikmu sendiri.
            </p>
            <p><a class="tombol tombol-utama" href="ktp.php">Bikin KTP Warga</a></p>
            <p class="catatan">
                Sudah punya kartu? Buka kartumu di halaman KTP dulu supaya peramban ini mengenalimu,
                lalu kembali ke sini.
            </p>
        </div>
        <?php if ($wargaAktif === null): ?>
            <p class="seksi-deskripsi">Ruko dibuka oleh warga yang punya kartu. Membuka ruko gratis.</p>
        <?php elseif ($rukoSaya !== null): ?>
            <h2 class="ruko-subjudul">Rukomu sudah berdiri</h2>
            <?php if ($gambarSaya !== ''): ?>
                <img class="gambar-usaha ruko-gambar-saya" src="<?= e($gambarSaya) ?>" alt="Gambar <?= e((string) $rukoSaya['nama']) ?>">
            <?php else: ?>
                <p class="gambar-usaha-kosong">Belum ada gambar usaha. Tambahkan di bawah supaya rukomu lebih hidup.</p>
            <?php endif; ?>
            <dl class="ruko-kartu">
                <div>
                    <dt>Nama usaha</dt>
                    <dd><?= e((string) $rukoSaya['nama']) ?></dd>
                </div>
                <div>
                    <dt>Pemilik</dt>
                    <dd><?= e((string) $rukoSaya['diminta_oleh']) ?> (<?= e($nomorAktif) ?>)</dd>
                </div>
                <div>
                    <dt>Gang</dt>
                    <dd><?= e((string) $rukoSaya['gang']) ?></dd>
                </div>
                <div>
                    <dt>Tautan jualan</dt>
                    <dd><a href="<?= e((string) $rukoSaya['tautan']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $rukoSaya['tautan']) ?></a></dd>
                </div>
            </dl>
            <p>
                <a class="tombol tombol-utama" href="peta.php">Lihat rukoku di peta</a>
            </p>
            <form method="post" action="ruko.php" class="ruko-lepas">
                <input type="hidden" name="token" value="<?= e($tokenRuko) ?>">
                <input type="hidden" name="nomor" value="<?= e($nomorAktif) ?>">
                <input type="hidden" name="kunci" value="<?= e($kunciAktif) ?>">
                <input type="hidden" name="aksi" value="lepas">
                <button type="submit" class="tombol tombol-halus">Lepas ruko</button>
            </form>
            <p class="catatan">Melepas ruko tidak menghapus ceritamu; bangunanmu hanya tidak tampil lagi di peta.</p>

            <h3 class="ruko-subjudul">Ubah rukomu</h3>
            <form class="formulir" method="post" action="ruko.php" id="formUbahRuko">
                <input type="hidden" name="token" value="<?= e($tokenRuko) ?>">
                <input type="hidden" name="nomor" value="<?= e($nomorAktif) ?>">
                <input type="hidden" name="kunci" value="<?= e($kunciAktif) ?>">
                <input type="hidden" name="aksi" value="ubah">

                <label for="ubahNamaUsaha">Nama usaha</label>
                <input type="text" id="ubahNamaUsaha" name="nama_usaha" required minlength="3" maxlength="60"
                       value="<?= e((string) $rukoSaya['nama']) ?>">

                <label for="ubahCeritaUsaha">Cerita singkat usaha</label>
                <textarea id="ubahCeritaUsaha" name="cerita" rows="4" maxlength="<?= (int) RUKO_CERITA_MAKS ?>"><?= e((string) ($rukoSaya['komentar'] ?? '')) ?></textarea>

                <label for="ubahTautanUsaha">Tautan jualan</label>
                <input type="url" id="ubahTautanUsaha" name="tautan" required maxlength="300"
                       pattern="https://.+" value="<?= e((string) $rukoSaya['tautan']) ?>">

                <?php require __DIR__ . '/includes/pilih-gambar.php'; ?>

                <button type="submit" class="tombol tombol-utama">Simpan perubahan</button>
            </form>
        <?php else: ?>
            <h2 class="ruko-subjudul">Klaim satu ruko</h2>
            <p class="seksi-deskripsi">
                Ruko menempati kavling kosong berikutnya di peta. Gang rukomu mengikuti gang di kartumu:
                <strong><?= e((string) ($wargaAktif['gang'] ?? '')) ?></strong>.
            </p>
            <form class="formulir" method="post" action="ruko.php" id="formRuko">
                <input type="hidden" name="token" value="<?= e($tokenRuko) ?>">
                <input type="hidden" name="nomor" value="<?= e($nomorAktif) ?>">
                <input type="hidden" name="kunci" value="<?= e($kunciAktif) ?>">
                <input type="hidden" name="aksi" value="klaim">

                <label for="namaUsaha">Nama usaha</label>
                <input type="text" id="namaUsaha" name="nama_usaha" required minlength="3" maxlength="60"
                       value="<?= e($isianRuko['nama_usaha']) ?>" placeholder="Contoh: Warung Kopi Sari">

                <label for="ceritaUsaha">Cerita singkat usaha</label>
                <textarea id="ceritaUsaha" name="cerita" rows="4" maxlength="<?= (int) RUKO_CERITA_MAKS ?>"
                          placeholder="Apa yang kamu jual, dan kenapa warga harus mampir?"><?= e($isianRuko['cerita']) ?></textarea>
                <p class="formulir-catatan">Paling panjang <?= (int) RUKO_CERITA_MAKS ?> karakter.</p>

                <label for="tautanUsaha">Tautan jualan</label>
                <input type="url" id="tautanUsaha" name="tautan" required maxlength="300"
                       pattern="https://.+"
                       value="<?= e($isianRuko['tautan']) ?>" placeholder="https://wa.me/62812...">
                <p class="formulir-catatan">Alamat WhatsApp, marketplace, atau Instagram tokomu. Wajib diawali https://.</p>

                <?php $gambarSaya = ''; require __DIR__ . '/includes/pilih-gambar.php'; ?>

                <button type="submit" class="tombol tombol-utama">Klaim ruko</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="kartu-formulir">
        <h2 class="ruko-subjudul">Ruko yang sudah berdiri</h2>
        <?php if ($daftarRuko === []): ?>
            <p class="seksi-deskripsi">Belum ada ruko warga. Jadilah yang pertama membuka usaha di desa.</p>
        <?php else: ?>
            <ul class="daftar-ruko">
                <?php foreach ($daftarRuko as $r): ?>
                    <li class="daftar-ruko-baris<?= !empty($r['gambar']) ? ' ada-gambar' : '' ?>">
                        <?php if (!empty($r['gambar'])): ?>
                            <img class="gambar-usaha daftar-ruko-gambar" src="<?= e((string) $r['gambar']) ?>"
                                 alt="Gambar <?= e((string) $r['nama']) ?>" loading="lazy" width="640" height="480">
                        <?php endif; ?>
                        <span class="daftar-ruko-nama"><?= e((string) $r['nama']) ?></span>
                        <span class="daftar-ruko-meta"><?= e((string) $r['diminta_oleh']) ?> &middot; <?= e((string) $r['gang']) ?></span>
                        <?php if (!empty($r['komentar'])): ?>
                            <span class="daftar-ruko-cerita"><?= e((string) $r['komentar']) ?></span>
                        <?php endif; ?>
                        <a class="daftar-ruko-tautan" href="<?= e((string) $r['tautan']) ?>" target="_blank" rel="noopener noreferrer">Kunjungi usaha</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><a href="peta.php">Lihat semuanya di peta</a></p>
        <?php endif; ?>
    </section>
</main>

<form id="rukoIdentitas" method="post" action="ruko.php" hidden>
    <input type="hidden" name="token" value="<?= e($tokenRuko) ?>">
    <input type="hidden" name="nomor" value="">
    <input type="hidden" name="kunci" value="">
</form>
<script>
// Identitas warga disimpan di localStorage oleh halaman KTP. Kartu yang
// dibuka di halaman lain (atau di tab baru) tidak memberi tahu halaman ini,
// jadi peramban mengirim nomor + kuncinya sendiri; server yang memutuskan
// cocok atau tidak. Setelah cocok, halaman ini memuat ulang sekali dan
// menampilkan formulir klaim atau ruko milik warga.
(function () {
    var form = document.getElementById('rukoIdentitas');
    if (!form) return;
    var nomor = '';
    var kunci = '';
    try {
        nomor = localStorage.getItem('dt_nomor') || '';
        kunci = localStorage.getItem('dt_kunci') || '';
    } catch (e) { return; }
    if (!/^DT-\d{4,9}$/.test(nomor) || kunci === '') return;
    var batas = 'dt_ruko_cek_' + nomor;
    try {
        if (sessionStorage.getItem(batas)) return; // sudah dicoba, jangan berputar
        sessionStorage.setItem(batas, '1');
    } catch (e) { return; }
    form.querySelector('input[name="nomor"]').value = nomor;
    form.querySelector('input[name="kunci"]').value = kunci;
    form.submit();
})();
</script>
