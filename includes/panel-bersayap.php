<?php
// Panel bersayap: satu panel yang isinya berganti mengikuti tab yang diketuk.
//
// Tujuannya meniru pola pembanding: berpindah isi TANPA meninggalkan dunia 3D.
// Panel menempel di sisi bawah, jadi bagian atas dunia tetap terlihat.
//
// Variabel yang dipakai:
//   $tabAwalan          awalan URL, sama seperti tab-bar.php
//   $panelBangunan      daftar bangunan (dari semua_bangunan()), boleh kosong
//   $panelPapanGang     peringkat gang (dari papan_gang())
//   $panelTotalWarga    jumlah warga terdaftar
//   $panelKavling       peta id bangunan -> indeks kavling, supaya ketukan
//                       pada daftar bisa menyorot bangunan yang tepat
//   $panelMisi          hasil daftar_misi(), tampil di tab Misi
//
// Semua isi berasal dari data yang sudah dibaca halaman. Tidak ada permintaan
// jaringan tambahan, jadi panel tidak bisa menampilkan angka yang berbeda
// dengan dunia 3D di belakangnya.
if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$tabAwalan = $tabAwalan ?? '';
$panelBangunan = $panelBangunan ?? [];
$panelPapanGang = $panelPapanGang ?? [];
$panelTotalWarga = $panelTotalWarga ?? 0;
$panelKavling = $panelKavling ?? []; // id bangunan -> indeks kavling
$panelMisi = $panelMisi ?? [];

$jumlahTerbanyak = 0;
foreach ($panelPapanGang as $barisPapan) {
    $jumlahTerbanyak = max($jumlahTerbanyak, (int) $barisPapan['jumlah']);
}
?>
<section class="panel-bersayap" id="panelBersayap" aria-label="Panel desa" aria-hidden="true" hidden>
    <div class="panel-bersayap-kepala">
        <h2 class="panel-bersayap-judul" id="panelBersayapJudul">Desa</h2>
        <button type="button" class="panel-bersayap-tutup" id="panelBersayapTutup" aria-label="Tutup panel">&times;</button>
    </div>
    <div class="panel-bersayap-isi">

        <div class="panel-isi" data-isi="warga" hidden>
            <p class="panel-deskripsi">Cari warga berdasarkan nama atau nomor kartu. Contoh: "sari" atau "DT-0007".</p>
            <form class="cari-warga" id="cariWargaForm" method="get" action="<?= e($tabAwalan) ?>warga.php"
                  data-endpoint="<?= e($tabAwalan) ?>cari-warga.php">
                <label class="sr-saja" for="cariWargaKunci">Nama atau nomor warga</label>
                <input class="cari-warga-isian" id="cariWargaKunci" name="cari" type="search"
                       placeholder="Nama atau DT-0007" autocomplete="off"
                       maxlength="50" enterkeyhint="search">
                <button class="tombol-panel-utama cari-warga-tombol" type="submit">Cari</button>
            </form>
            <div class="cari-warga-hasil" id="cariWargaHasil" aria-live="polite"></div>
            <p class="panel-catatan">Semua warga juga bisa dilihat di <a href="<?= e($tabAwalan) ?>warga.php">daftar warga desa</a>.</p>
        </div>

        <div class="panel-isi" data-isi="misi" hidden>
            <?php $ringkasMisi = ringkasan_misi($panelMisi); ?>
            <p class="panel-deskripsi">
                <strong id="misiRingkasan"><?= (int) $ringkasMisi['selesai'] ?> dari <?= (int) $ringkasMisi['total'] ?></strong> misi hari ini sudah selesai.
                Status dan poin di bawah ini milikmu sendiri setelah kartu warga tersimpan di peramban ini.
            </p>

            <div class="misi-dompet" id="misiDompet" hidden>
                <p class="misi-saldo"><span class="misi-saldo-angka" id="misiSaldo">0</span> poin</p>
                <p class="misi-saldo-ket" id="misiSaldoKet">Saldo poin milikmu. Poin nanti bisa dipakai untuk usaha di desa.</p>
                <button type="button" class="tombol-panel-utama misi-klaim" id="tombolKlaimHarian">Klaim poin harian</button>
                <p class="misi-kabar" id="misiKabar" role="status" hidden></p>
            </div>

            <div class="misi-tamu" id="misiTamu">
                <p class="panel-deskripsi">Bikin KTP Warga dulu supaya misi dan poinmu tercatat atas namamu sendiri.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga</a>
            </div>

            <ul class="daftar-misi" id="daftarMisi">
                <?php foreach ($panelMisi as $m): ?>
                    <li class="misi-baris<?= $m['selesai'] ? ' selesai' : '' ?>" data-kode="<?= e($m['kode']) ?>">
                        <span class="misi-tanda" aria-hidden="true"><?= $m['selesai'] ? '&#10003;' : '' ?></span>
                        <span class="misi-teks">
                            <span class="misi-judul"><?= e($m['judul']) ?></span>
                            <span class="misi-keterangan"><?= e($m['keterangan']) ?></span>
                            <button type="button" class="misi-tandai" data-kode="<?= e($m['kode']) ?>"
                                    <?= $m['selesai'] ? 'hidden' : '' ?>>Tandai selesai, +<?= (int) $m['poin'] ?> poin</button>
                        </span>
                        <span class="misi-status"><?= $m['selesai'] ? 'Selesai' : 'Belum' ?></span>
                        <span class="misi-poin">+<?= (int) $m['poin'] ?> poin</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="tombol-panel-utama" href="https://www.instagram.com/desatarget" target="_blank" rel="noopener">Buka Instagram</a>
        </div>

        <div class="panel-isi" data-isi="papan-gang" hidden>
            <p class="panel-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
            <div class="papan-gang">
                <?php foreach ($panelPapanGang as $i => $baris): ?>
                    <?php $persen = $jumlahTerbanyak > 0 ? (int) round($baris['jumlah'] / $jumlahTerbanyak * 100) : 0; ?>
                    <div class="papan-baris">
                        <span class="papan-peringkat"><?= $i + 1 ?></span>
                        <span class="papan-nama"><?= e($baris['gang']) ?></span>
                        <span class="papan-palangan"><span class="papan-isi" style="width: <?= $persen ?>%"></span></span>
                        <span class="papan-jumlah"><?= (int) $baris['jumlah'] ?> warga</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="panel-gabung"><a href="<?= e($tabAwalan) ?>ktp.php">Gabung gang-mu</a></p>
        </div>

        <div class="panel-isi" data-isi="bangunan" hidden>
            <?php if ($panelBangunan === []): ?>
                <p class="panel-deskripsi">Belum ada bangunan berdiri. Usulkan usaha impianmu lewat komentar Instagram.</p>
            <?php else: ?>
                <p class="panel-deskripsi"><?= count($panelBangunan) ?> bangunan berdiri di desa, urut dari yang paling baru.</p>
                <p class="panel-catatan panel-catatan-atas">Di peta, ketuk nama bangunan untuk menyorotnya di dunia.</p>
                <ul class="daftar-bangunan">
                    <?php foreach (array_reverse($panelBangunan) as $b): ?>
                        <?php $kavling = $panelKavling[$b['id']] ?? null; ?>
                        <li class="daftar-bangunan-baris">
                            <button type="button" class="daftar-bangunan-tombol"
                                    data-bangunan="<?= (int) $b['id'] ?>"
                                    data-kavling="<?= $kavling === null ? '' : (int) $kavling ?>">
                                <span class="daftar-bangunan-nama"><?= e($b['nama']) ?></span>
                                <?php if (!empty($b['gang'])): ?>
                                    <span class="daftar-bangunan-gang"><?= e($b['gang']) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel-isi" data-isi="aku" hidden>
            <p class="panel-deskripsi">Kartu warga adalah identitasmu di desa. Dibuat sekali, gratis, nomornya milikmu sendiri.</p>
            <p class="panel-statistik"><strong><?= (int) $panelTotalWarga ?></strong> warga sudah tercatat di balai desa.</p>
            <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin atau lihat KTP Warga</a>
            <p class="panel-catatan">Setelah kartumu jadi, tekan tombol kembali di halaman KTP. Kamu akan mendarat lagi di peta, dan namamu sudah berdiri sebagai warga baru di depan balai desa.</p>
        </div>

        <?php // Chat antar gang (Fitur 2). Tidak ada tab kelima: isi ini dibuka
              // dari tombol Chat di bilah atas. Hanya frase preset, tanpa kolom
              // teks bebas, dan semua pesan publik. ?>
        <div class="panel-isi" data-isi="chat" hidden>
            <p class="panel-deskripsi">Sapa warga desa lewat pilihan kalimat di bawah. Chat ini publik, semua warga bisa membaca, tidak ada pesan pribadi.</p>

            <div class="chat-pilih" id="chatPilih" role="group" aria-label="Pilih kanal chat"></div>

            <div class="chat-riwayat" id="chatRiwayat" aria-live="polite" aria-label="Pesan terbaru">
                <p class="chat-kosong" id="chatKosong">Sedang memuat pesan…</p>
            </div>

            <div class="chat-kirim" id="chatKirim" hidden>
                <p class="chat-kirim-judul">Pilih kalimat untuk dikirim</p>
                <div class="chat-frase" id="chatFrase"></div>
                <p class="chat-kabar" id="chatKabar" role="status" hidden></p>
            </div>

            <div class="chat-tamu" id="chatTamu" hidden>
                <p class="panel-deskripsi">Bikin KTP Warga supaya bisa ikut menyapa di chat. Membaca pesan tetap gratis tanpa kartu.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga</a>
            </div>
        </div>

    </div>
</section>
