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

        <div class="panel-isi" data-isi="misi" hidden>
            <p class="panel-deskripsi">Misi harian desa belum dibuka. Untuk sekarang, satu misi yang selalu ada:</p>
            <div class="kartu-misi">
                <h3>Usulkan usaha impianmu</h3>
                <p>Komentar usaha yang paling banyak disukai hari ini dibangun Bang Uju besok pagi. Buka Instagram, tulis usahamu, minta temanmu menyukai komentarnya.</p>
                <a class="tombol-panel-utama" href="https://www.instagram.com/desatarget" target="_blank" rel="noopener">Buka Instagram</a>
            </div>
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
                <ul class="daftar-bangunan">
                    <?php foreach (array_reverse($panelBangunan) as $b): ?>
                        <li class="daftar-bangunan-baris" data-bangunan="<?= (int) $b['id'] ?>">
                            <span class="daftar-bangunan-nama"><?= e($b['nama']) ?></span>
                            <?php if (!empty($b['gang'])): ?>
                                <span class="daftar-bangunan-gang"><?= e($b['gang']) ?></span>
                            <?php endif; ?>
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

    </div>
</section>
