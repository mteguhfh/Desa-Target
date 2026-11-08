<?php
// Markup halaman Pasar Desa (Fitur 4). Logikanya di pasar.php.
//
// Variabel yang dipakai:
//   $pasarData         hasil keadaan_pasar(): acara, namaAcara, tanggal,
//                      berikutnya, jadwal, lapak, batas
//   $pasarLapakBerikut daftar lapak acara berikutnya
if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', 'UTF-8');
    }
}

$HARI = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$acaraAktif = $pasarData['acara'];
$berikut = $pasarData['berikutnya'];
?>
<main class="halaman pasar-halaman">
    <section class="kartu-formulir">
        <p class="label-kecil">PASAR DESA</p>
        <h1>Pasar Malam dan CFD</h1>
        <p class="seksi-deskripsi">
            Dua acara berkala yang bikin desa ramai. Lapak gratis, dan pembeli langsung
            diarahkan ke tempat jualanmu sendiri. Desa tidak memproses pembayaran apa pun.
        </p>

        <?php
        $acaraAktifInfo = null;
        foreach ($pasarData['jadwal'] as $jadwalAcara) {
            if ($jadwalAcara['acara'] === $acaraAktif) {
                $acaraAktifInfo = $jadwalAcara;
            }
        }
        ?>
        <p class="pasar-status<?= $acaraAktif !== null ? ' aktif' : '' ?>" id="pasarStatusHalaman">
            <?php if ($acaraAktifInfo !== null): ?>
                <?= e((string) $acaraAktifInfo['nama']) ?> sedang berlangsung sampai <?= e((string) $acaraAktifInfo['selesai']) ?>.
            <?php elseif ($berikut !== null): ?>
                <span id="pasarBerikutNama"><?= e((string) $berikut['nama']) ?> berikutnya
                <?= e(format_tanggal((string) $berikut['tanggal'])) ?> pukul <?= e((string) $berikut['mulai']) ?></span>,
                <strong class="pasar-mundur" id="pasarMundur"
                        data-mulai="<?= e((string) $berikut['tanggal']) ?>T<?= e((string) $berikut['mulai']) ?>:00">&hellip;</strong>
            <?php else: ?>
                Jadwal acara belum tersedia.
            <?php endif; ?>
        </p>

        <dl class="pasar-jadwal">
            <?php foreach ($pasarData['jadwal'] as $j): ?>
                <div class="pasar-jadwal-baris">
                    <dt><?= e((string) $j['nama']) ?></dt>
                    <dd>Tiap <?= e($HARI[(int) $j['hari']] ?? '') ?>, <?= e((string) $j['mulai']) ?>&ndash;<?= e((string) $j['selesai']) ?>
                        &middot; <?= (int) $j['batas'] ?> lapak</dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <p>
            <a class="tombol tombol-utama" href="peta.php"><?= $acaraAktif !== null ? 'Buka lapak di peta' : 'Lihat peta desa' ?></a>
            <a class="tombol tombol-biasa" href="ktp.php">Bikin KTP Warga</a>
        </p>
        <p class="catatan">
            Lapak hanya bisa diambil saat acaranya sedang berlangsung. Buka panel Pasar lewat chip suasana
            di bilah atas peta, atau lewat tombol di bawah saat acara dimulai.
        </p>
    </section>

    <section class="kartu-formulir">
        <h2 class="ruko-subjudul">Lapak <?= $acaraAktif !== null ? 'acara ini' : 'acara berikutnya' ?></h2>
        <?php
        $daftarTampil = $acaraAktif !== null ? $pasarData['lapak'] : $pasarLapakBerikut;
        $judulKosong = $acaraAktif !== null
            ? 'Belum ada lapak di acara ini. Jadilah yang pertama.'
            : 'Belum ada yang mendaftar untuk acara berikutnya.';
        ?>
        <?php if ($daftarTampil === []): ?>
            <p class="seksi-deskripsi"><?= e($judulKosong) ?></p>
        <?php else: ?>
            <ul class="lapak-grid" id="pasarDaftarHalaman">
                <?php foreach ($daftarTampil as $i => $l): ?>
                    <li class="lapak-kartu" data-kategori="<?= e($l['kategori'] ?? 'lainnya') ?>">
                        <p><?= e(KATEGORI_USAHA[$l['kategori'] ?? 'lainnya'] ?? 'Lainnya') ?> · <?= e(harga_usaha($l['harga'] ?? null)) ?></p>
                        <?php if (!empty($l['gambar'])): ?>
                            <img class="gambar-usaha lapak-kartu-gambar" src="<?= e((string) $l['gambar']) ?>"
                                 alt="Lapak <?= e((string) $l['namaUsaha']) ?>" loading="lazy" width="640" height="480">
                        <?php else: ?>
                            <span class="gambar-usaha-kosong">Belum ada foto lapak</span>
                        <?php endif; ?>
                        <div class="lapak-kartu-isi">
                            <h3 class="lapak-kartu-nama"><?= e((string) $l['namaUsaha']) ?></h3>
                            <p class="lapak-kartu-meta">Tenda <?= $i + 1 ?> &middot; <?= e((string) $l['nomor']) ?></p>
                            <?php if (str_starts_with((string) $l['tautan'], 'https://')): ?>
                                <a class="tombol tombol-biasa lapak-kartu-tombol" href="<?= e((string) $l['tautan']) ?>" target="_blank" rel="noopener noreferrer">Kunjungi lapak</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/katalog-skrip.php'; ?>

<script>
// Hitung mundur ke acara berikutnya, dihitung dari jam peramban pengunjung.
(function () {
    var mundur = document.getElementById('pasarMundur');
    if (!mundur) return;
    var target = new Date(mundur.getAttribute('data-mulai'));
    function tick() {
        var sisa = Math.floor((target.getTime() - Date.now()) / 1000);
        if (isNaN(sisa)) return;
        if (sisa <= 0) { mundur.textContent = 'sebentar lagi'; return; }
        var hari = Math.floor(sisa / 86400);
        var jam = Math.floor((sisa % 86400) / 3600);
        var menit = Math.floor((sisa % 3600) / 60);
        mundur.textContent = hari > 0
            ? hari + ' hari ' + jam + ' jam lagi'
            : (jam > 0 ? jam + ' jam ' + menit + ' menit lagi' : menit + ' menit lagi');
    }
    tick();
    window.setInterval(tick, 60000);
})();
</script>
