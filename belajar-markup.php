<?php
// Halaman penuh Pusat Pembelajaran. Dipakai pengunjung tanpa JavaScript dan
// sebagai halaman mandiri dari peta 2D. Data berasal dari keadaan_belajar()
// yang sama dengan panel dan interior 3D (ADR-0004). Variabel dari
// belajar.php: $warga (baris warga atau null), $nomor, $kelas (baris kelas
// atau null pada daftar), $keadaan, $materiTerpilih, $daftar (daftar kelas).
$judulHalaman = ($kelas['judul'] ?? 'Pusat Pembelajaran') . ' - Belajar';
$halamanAktif = 'belajar';
require __DIR__ . '/includes/header.php';

function belajar_tautan_kelas(array $kelasBaris, ?array $wargaAktif, ?string $nomorAktif): string
{
    $tautan = 'belajar.php?kelas=' . urlencode($kelasBaris['slug']);
    if ($wargaAktif !== null) {
        $tautan .= '&nomor=' . urlencode((string) $nomorAktif) . '&kunci=' . urlencode((string) $wargaAktif['kunci']);
    }
    return $tautan;
}
?>
<section class="panel lebar">
    <div class="panel-kepala">
        <div>
            <p class="label-kecil">Pusat Pembelajaran</p>
            <h1><?= $kelas !== null ? e($kelas['judul']) : 'Belajar di Desa Target' ?></h1>
        </div>
        <span class="chip">Fitur 5 desa</span>
    </div>

    <?php if ($warga === null): ?>
        <p class="petunjuk">
            Kamu sedang melihat sebagai pengunjung. Materi pertama setiap kelas bisa dibaca,
            tapi progres dan poin hanya tersimpan untuk warga. Masukkan nomor dan kunci rahasia
            dari KTP Wargamu supaya progresmu tercatat.
        </p>
        <form class="form-ktp" method="get" action="belajar.php">
            <?php if ($kelas !== null): ?>
                <input type="hidden" name="kelas" value="<?= e($kelas['slug']) ?>">
            <?php endif; ?>
            <div class="kolom">
                <label for="nomorBelajar">Nomor KTP Warga</label>
                <input id="nomorBelajar" name="nomor" type="text" placeholder="DT-0001" autocomplete="off" required>
            </div>
            <div class="kolom">
                <label for="kunciBelajar">Kunci rahasia kartu</label>
                <input id="kunciBelajar" name="kunci" type="password" autocomplete="off" required>
            </div>
            <button class="tombol" type="submit">Masuk belajar</button>
            <p class="petunjuk">Belum punya kartu? <a href="ktp.php">Bikin KTP Warga</a> dulu, gratis.</p>
        </form>
    <?php else: ?>
        <p class="petunjuk">
            Halo, <?= e($warga['nama']) ?> (<?= e($nomor) ?>). Saldo poinmu sekarang
            <strong><?= e((string) saldo_poin($pdo, $nomor)) ?> poin</strong>.
            Selesaikan materi berurutan; setiap materi yang tuntas menambah poin dompetmu.
        </p>
    <?php endif; ?>

    <?php if ($kelas === null): ?>
        <div class="daftar-warga">
            <?php foreach ($daftar as $k): ?>
                <article class="kartu-warga">
                    <h3><?= e($k['judul']) ?></h3>
                    <p><?= e($k['keterangan']) ?></p>
                    <p>
                        <?= (int) $k['jumlahMateri'] ?> materi, total <?= (int) $k['totalPoin'] ?> poin.
                        <?php if ($warga !== null): ?>
                            Progresmu: <?= (int) $k['selesai'] ?> dari <?= (int) $k['total'] ?> selesai (<?= (int) $k['persen'] ?>%).
                            <?php if ($k['tuntas']): ?> Kelas tuntas, lencana Warga Cerdas terbuka.<?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <p><a class="tombol" href="<?= e(belajar_tautan_kelas($k, $warga, $nomor)) ?>">Buka kelas</a></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><a href="belajar.php<?= $warga !== null ? '?nomor=' . urlencode((string) $nomor) . '&kunci=' . urlencode((string) $warga['kunci']) : '' ?>">Kembali ke daftar kelas</a></p>
        <p><?= e($kelas['keterangan']) ?></p>
        <p>Progres kelas ini: <?= (int) $keadaan['selesai'] ?> dari <?= (int) $keadaan['total'] ?> materi selesai (<?= (int) $keadaan['persen'] ?>%).</p>

        <div class="daftar-warga">
            <?php foreach ($keadaan['materi'] as $m): ?>
                <article class="kartu-warga">
                    <h3><?= e($m['judul']) ?></h3>
                    <p>
                        <?= e(ucfirst($m['tipe'])) ?>, <?= (int) $m['poin'] ?> poin.
                        <?php if ($m['selesai']): ?>
                            Sudah selesai<?= $m['selesai_pada'] !== null ? ' pada ' . e($m['selesai_pada']) : '' ?>.
                        <?php elseif (!$m['terbuka']): ?>
                            Terkunci, selesaikan materi sebelumnya dulu.
                        <?php elseif ($m['segera']): ?>
                            Segera hadir.
                        <?php else: ?>
                            Terbuka untukmu.
                        <?php endif; ?>
                    </p>
                    <?php if ($m['terbuka'] && !$m['selesai']): ?>
                        <p><a class="tombol" href="<?= e(belajar_tautan_kelas($kelas, $warga, $nomor)) ?>&materi=<?= (int) $m['id'] ?>">Mulai materi</a></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($materiTerpilih !== null): ?>
            <hr>
            <h2><?= e($materiTerpilih['judul']) ?></h2>
            <?php if (!$materiTerpilih['terbuka']): ?>
                <p class="petunjuk">Materi ini masih terkunci. Selesaikan materi sebelumnya di kelas ini dulu ya.</p>
            <?php elseif ($materiTerpilih['tipe'] === 'artikel'): ?>
                <div class="isi-materi"><?= $materiTerpilih['isi'] ?></div>
                <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                    <form data-belajar-selesai data-materi="<?= (int) $materiTerpilih['id'] ?>">
                        <button class="tombol" type="submit">Tandai selesai, ambil <?= (int) $materiTerpilih['poin'] ?> poin</button>
                    </form>
                <?php elseif ($warga === null): ?>
                    <p class="petunjuk">Masuk dengan nomor dan kunci KTP untuk menandai materi ini selesai dan mengambil poinnya.</p>
                <?php endif; ?>
            <?php elseif ($materiTerpilih['tipe'] === 'video'): ?>
                <?php if ($materiTerpilih['segera']): ?>
                    <p class="petunjuk">Video untuk materi ini masih disiapkan Bang Uju. Tandai selesai belum bisa dipakai; progresmu di materi lain tetap tersimpan.</p>
                <?php else: ?>
                    <div class="video-bungkus">
                        <iframe src="https://www.youtube-nocookie.com/embed/<?= e((string) $materiTerpilih['youtube_id']) ?>" title="<?= e($materiTerpilih['judul']) ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                    <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                        <form data-belajar-selesai data-materi="<?= (int) $materiTerpilih['id'] ?>">
                            <button class="tombol" type="submit">Sudah kutonton, tandai selesai</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php elseif ($materiTerpilih['tipe'] === 'kuis'): ?>
                <?php $soalPublik = soal_kuis_publik($materiTerpilih); ?>
                <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                    <form data-belajar-kuis data-materi="<?= (int) $materiTerpilih['id'] ?>">
                        <?php foreach ($soalPublik as $i => $s): ?>
                            <fieldset class="kolom">
                                <legend><?= ($i + 1) . '. ' . e($s['tanya']) ?></legend>
                                <?php foreach ($s['pilihan'] as $j => $pil): ?>
                                    <label class="radio-baris">
                                        <input type="radio" name="jawaban[<?= $i ?>]" value="<?= $j ?>" required>
                                        <?= e($pil) ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                        <button class="tombol" type="submit">Kirim jawaban</button>
                        <p class="petunjuk">Lulus hanya bila semua soal benar. Boleh mencoba lagi berkali-kali.</p>
                    </form>
                <?php else: ?>
                    <ol>
                        <?php foreach ($soalPublik as $s): ?>
                            <li><?= e($s['tanya']) ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="petunjuk"><?= $materiTerpilih['selesai'] ? 'Kuis ini sudah kamu lulusi.' : 'Masuk dengan nomor dan kunci KTP untuk mengerjakan kuis ini.' ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <p class="pesan-belajar" data-belajar-pesan role="status"></p>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php if ($warga !== null): ?>
<script>
(() => {
    const nomor = <?= json_encode($nomor) ?>;
    const kunci = <?= json_encode((string) $warga['kunci']) ?>;
    const pesan = document.querySelector('[data-belajar-pesan]');
    const muatUlang = (data) => {
        const url = new URL(window.location.href);
        url.searchParams.set('nomor', nomor);
        url.searchParams.set('kunci', kunci);
        if (data && data.berikutnya) url.searchParams.set('materi', String(data.berikutnya));
        window.location.assign(url.toString());
    };
    document.querySelectorAll('[data-belajar-selesai]').forEach((form) => {
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            pesan.textContent = 'Mencatat...';
            const body = new URLSearchParams({ nomor, kunci, aksi: 'selesai', materi: form.dataset.materi });
            try {
                const res = await fetch('belajar.php', { method: 'POST', body });
                const data = await res.json();
                pesan.textContent = data.pesan || 'Selesai.';
                if (data.ok) setTimeout(() => muatUlang(data), 700);
            } catch (_) { pesan.textContent = 'Koneksi bermasalah. Coba lagi.'; }
        });
    });
    document.querySelectorAll('[data-belajar-kuis]').forEach((form) => {
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            pesan.textContent = 'Menilai jawaban...';
            const body = new URLSearchParams({ nomor, kunci, aksi: 'kuis', materi: form.dataset.materi });
            new FormData(form).forEach((nilai, nama) => {
                const cocok = /^jawaban\[(\d+)\]$/.exec(nama);
                if (cocok) body.set('jawaban[' + cocok[1] + ']', String(nilai));
            });
            try {
                const res = await fetch('belajar.php', { method: 'POST', body });
                const data = await res.json();
                pesan.textContent = data.pesan || 'Selesai.';
                if (data.ok && data.lulus) setTimeout(() => muatUlang(data), 900);
            } catch (_) { pesan.textContent = 'Koneksi bermasalah. Coba lagi.'; }
        });
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
