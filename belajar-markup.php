<?php
// Halaman penuh Pusat Pembelajaran. Dipakai pengunjung tanpa JavaScript dan
// sebagai halaman mandiri dari peta 2D. Data berasal dari keadaan_belajar()
// yang sama dengan panel dan interior 3D (ADR-0004). Variabel dari
// belajar.php: $warga (baris warga atau null), $nomor, $kelas (baris kelas
// atau null pada daftar), $keadaan, $materiTerpilih, $daftar (daftar kelas),
// $kelasHilang (true bila slug kelas di alamat tidak dikenal).
$kelas = $kelas ?? null;
$daftar = $daftar ?? [];
$kelasHilang = $kelasHilang ?? false;
$judulHalaman = ($kelas['judul'] ?? 'Pusat Pembelajaran') . ' - Belajar';
$halamanAktif = 'belajar';
require __DIR__ . '/includes/header.php';

function belajar_tautan_kelas(array $kelasBaris, ?array $wargaAktif, ?string $nomorAktif): string
{
    $tautan = 'belajar?kelas=' . urlencode($kelasBaris['slug']);
    if ($wargaAktif !== null) {
        $tautan .= '&nomor=' . urlencode((string) $nomorAktif) . '&kunci=' . urlencode((string) $wargaAktif['kunci']);
    }
    return $tautan;
}

const BELAJAR_TIPE = ['artikel' => 'Artikel', 'video' => 'Video', 'kuis' => 'Kuis'];

$tautanDaftar = 'belajar' . ($warga !== null
    ? '?nomor=' . urlencode((string) $nomor) . '&kunci=' . urlencode((string) $warga['kunci'])
    : '');
?>
<div class="belajar-halaman">
    <section class="kartu-formulir">
        <?php if ($kelas !== null): ?>
            <p class="belajar-kembali"><a href="<?= e($tautanDaftar) ?>">&larr; Semua kelas</a></p>
        <?php endif; ?>
        <p class="label-kecil">PUSAT PEMBELAJARAN</p>
        <h1><?= $kelas !== null ? e($kelas['judul']) : 'Belajar di Desa Target' ?></h1>
        <p class="seksi-deskripsi">
            <?php if ($kelas !== null): ?>
                <?= e($kelas['keterangan']) ?>
            <?php else: ?>
                Kelas singkat untuk warga yang punya usaha: dokumen, konten jualan, dan keuangan.
                Materinya berurutan, dan setiap materi yang selesai menambah poin ke dompetmu.
            <?php endif; ?>
        </p>

        <?php if ($kelas !== null): ?>
            <div class="belajar-progres">
                <div class="belajar-progres-teks">
                    <span><?= (int) $keadaan['selesai'] ?> dari <?= (int) $keadaan['total'] ?> materi selesai</span>
                    <strong><?= (int) $keadaan['persen'] ?>%</strong>
                </div>
                <div class="belajar-progres-bar" role="progressbar" aria-label="Progres kelas"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $keadaan['persen'] ?>">
                    <span style="width: <?= (int) $keadaan['persen'] ?>%"></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($warga === null): ?>
            <div class="belajar-masuk">
                <h2 class="ruko-subjudul">Masuk sebagai warga</h2>
                <p class="formulir-catatan">
                    Tanpa masuk, kamu tetap bisa membaca materi pertama setiap kelas. Progres dan poin
                    hanya tersimpan kalau kamu masuk dengan nomor dan kunci dari KTP Wargamu.
                </p>
                <form class="belajar-masuk-form" method="get" action="belajar">
                    <?php if ($kelas !== null): ?>
                        <input type="hidden" name="kelas" value="<?= e($kelas['slug']) ?>">
                    <?php endif; ?>
                    <div class="belajar-isian">
                        <label for="nomorBelajar">Nomor KTP Warga</label>
                        <input id="nomorBelajar" name="nomor" type="text" placeholder="DT-0001" autocomplete="off" required>
                    </div>
                    <div class="belajar-isian">
                        <label for="kunciBelajar">Kunci rahasia kartu</label>
                        <input id="kunciBelajar" name="kunci" type="password" autocomplete="off" required>
                    </div>
                    <button class="tombol tombol-utama" type="submit">Masuk belajar</button>
                </form>
                <p class="formulir-catatan">Belum punya kartu? <a href="ktp">Bikin KTP Warga</a> dulu, gratis.</p>
            </div>
        <?php else: ?>
            <p class="belajar-sapa">
                Halo, <strong><?= e($warga['nama']) ?></strong> (<?= e($nomor) ?>).
                Saldo poinmu <strong><?= e((string) saldo_poin($pdo, $nomor)) ?> poin</strong>.
            </p>
        <?php endif; ?>
    </section>

    <?php if ($kelasHilang): ?>
        <section class="kartu-formulir">
            <h2 class="ruko-subjudul">Kelasnya tidak ditemukan</h2>
            <p class="seksi-deskripsi">Mungkin tautannya sudah lama. Pilih kelas lain dari daftar.</p>
            <p><a class="tombol tombol-biasa" href="<?= e($tautanDaftar) ?>">Lihat semua kelas</a></p>
        </section>
    <?php elseif ($kelas === null): ?>
        <section class="kartu-formulir">
            <h2 class="ruko-subjudul">Pilih kelas</h2>
            <ul class="belajar-kelas-grid">
                <?php foreach ($daftar as $k): ?>
                    <li class="belajar-kelas<?= $k['tuntas'] ? ' tuntas' : '' ?>">
                        <h3><?= e($k['judul']) ?></h3>
                        <p class="belajar-kelas-ket"><?= e($k['keterangan']) ?></p>
                        <p class="belajar-kelas-meta">
                            <span><?= (int) $k['jumlahMateri'] ?> materi</span>
                            <span><?= (int) $k['totalPoin'] ?> poin</span>
                            <?php if ($k['tuntas']): ?>
                                <span class="lencana-warga-cerdas">Warga Cerdas</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($warga !== null): ?>
                            <div class="belajar-progres-bar kecil" role="progressbar"
                                 aria-label="Progres <?= e($k['judul']) ?>"
                                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $k['persen'] ?>">
                                <span style="width: <?= (int) $k['persen'] ?>%"></span>
                            </div>
                            <p class="belajar-kelas-progres"><?= (int) $k['selesai'] ?> dari <?= (int) $k['total'] ?> selesai</p>
                        <?php endif; ?>
                        <a class="tombol tombol-utama" href="<?= e(belajar_tautan_kelas($k, $warga, $nomor)) ?>">
                            <?= $warga !== null && (int) $k['selesai'] > 0 && !$k['tuntas'] ? 'Lanjutkan kelas' : 'Buka kelas' ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <section class="kartu-formulir">
            <h2 class="ruko-subjudul">Daftar materi</h2>
            <ol class="belajar-materi-daftar">
                <?php foreach ($keadaan['materi'] as $m): ?>
                    <?php
                    if ($m['selesai']) {
                        [$statusKelas, $statusTeks] = ['selesai', 'Selesai'];
                    } elseif (!$m['terbuka']) {
                        [$statusKelas, $statusTeks] = ['terkunci', 'Terkunci'];
                    } elseif ($m['segera']) {
                        [$statusKelas, $statusTeks] = ['segera', 'Segera hadir'];
                    } else {
                        [$statusKelas, $statusTeks] = ['terbuka', 'Terbuka'];
                    }
                    $dipilih = $materiTerpilih !== null && (int) $materiTerpilih['id'] === (int) $m['id'];
                    ?>
                    <li class="belajar-materi<?= $dipilih ? ' dipilih' : '' ?>">
                        <div class="belajar-materi-info">
                            <h3><?= e($m['judul']) ?></h3>
                            <p>
                                <?= e(BELAJAR_TIPE[$m['tipe']] ?? ucfirst($m['tipe'])) ?> &middot; <?= (int) $m['poin'] ?> poin
                                <?php if ($m['selesai'] && $m['selesai_pada'] !== null): ?>
                                    &middot; selesai <?= e($m['selesai_pada']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="belajar-status <?= $statusKelas ?>"><?= $statusTeks ?></span>
                        <?php if ($m['terbuka'] && !$dipilih): ?>
                            <a class="tombol tombol-biasa belajar-materi-tombol"
                               href="<?= e(belajar_tautan_kelas($kelas, $warga, $nomor)) ?>&amp;materi=<?= (int) $m['id'] ?>#materi">
                                <?= $m['selesai'] ? 'Buka lagi' : 'Mulai' ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php if ($materiTerpilih !== null): ?>
            <section class="kartu-formulir belajar-materi-isi" id="materi">
                <p class="label-kecil"><?= e(BELAJAR_TIPE[$materiTerpilih['tipe']] ?? ucfirst($materiTerpilih['tipe'])) ?> &middot; <?= (int) $materiTerpilih['poin'] ?> poin</p>
                <h2><?= e($materiTerpilih['judul']) ?></h2>

                <?php if (!$materiTerpilih['terbuka']): ?>
                    <p class="belajar-catatan">Materi ini masih terkunci. Selesaikan materi sebelumnya di kelas ini dulu ya.</p>
                <?php elseif ($materiTerpilih['tipe'] === 'artikel'): ?>
                    <div class="isi-materi"><?= $materiTerpilih['isi'] ?></div>
                    <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                        <form data-belajar-selesai data-materi="<?= (int) $materiTerpilih['id'] ?>">
                            <button class="tombol tombol-utama" type="submit">Tandai selesai, ambil <?= (int) $materiTerpilih['poin'] ?> poin</button>
                        </form>
                    <?php elseif ($warga === null): ?>
                        <p class="belajar-catatan">Masuk dengan nomor dan kunci KTP untuk menandai materi ini selesai dan mengambil poinnya.</p>
                    <?php else: ?>
                        <p class="belajar-catatan selesai">Materi ini sudah kamu selesaikan.</p>
                    <?php endif; ?>
                <?php elseif ($materiTerpilih['tipe'] === 'video'): ?>
                    <?php if ($materiTerpilih['segera']): ?>
                        <p class="belajar-catatan">Video untuk materi ini masih disiapkan Bang Uju. Tandai selesai belum bisa dipakai; progresmu di materi lain tetap tersimpan.</p>
                    <?php else: ?>
                        <div class="video-bungkus">
                            <iframe src="https://www.youtube-nocookie.com/embed/<?= e((string) $materiTerpilih['youtube_id']) ?>" title="<?= e($materiTerpilih['judul']) ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                        <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                            <form data-belajar-selesai data-materi="<?= (int) $materiTerpilih['id'] ?>">
                                <button class="tombol tombol-utama" type="submit">Sudah kutonton, tandai selesai</button>
                            </form>
                        <?php elseif ($warga === null): ?>
                            <p class="belajar-catatan">Masuk dengan nomor dan kunci KTP untuk menandai video ini selesai dan mengambil poinnya.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php elseif ($materiTerpilih['tipe'] === 'kuis'): ?>
                    <?php $soalPublik = soal_kuis_publik($materiTerpilih); ?>
                    <?php if ($warga !== null && !$materiTerpilih['selesai']): ?>
                        <form class="belajar-kuis" data-belajar-kuis data-materi="<?= (int) $materiTerpilih['id'] ?>">
                            <?php foreach ($soalPublik as $i => $s): ?>
                                <fieldset class="belajar-soal">
                                    <legend><?= ($i + 1) . '. ' . e($s['tanya']) ?></legend>
                                    <?php foreach ($s['pilihan'] as $j => $pil): ?>
                                        <label class="radio-baris">
                                            <input type="radio" name="jawaban[<?= $i ?>]" value="<?= $j ?>" required>
                                            <span><?= e($pil) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php endforeach; ?>
                            <button class="tombol tombol-utama" type="submit">Kirim jawaban</button>
                            <p class="formulir-catatan">Lulus hanya bila semua soal benar. Boleh mencoba lagi berkali-kali.</p>
                        </form>
                    <?php else: ?>
                        <ol class="belajar-soal-pratinjau">
                            <?php foreach ($soalPublik as $s): ?>
                                <li><?= e($s['tanya']) ?></li>
                            <?php endforeach; ?>
                        </ol>
                        <p class="belajar-catatan<?= $materiTerpilih['selesai'] ? ' selesai' : '' ?>"><?= $materiTerpilih['selesai'] ? 'Kuis ini sudah kamu lulusi.' : 'Masuk dengan nomor dan kunci KTP untuk mengerjakan kuis ini.' ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <p class="pesan-belajar" data-belajar-pesan role="status"></p>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
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
        url.hash = 'materi';
        window.location.assign(url.toString());
    };
    document.querySelectorAll('[data-belajar-selesai]').forEach((form) => {
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            pesan.textContent = 'Mencatat...';
            const body = new URLSearchParams({ nomor, kunci, aksi: 'selesai', materi: form.dataset.materi });
            try {
                const res = await fetch('belajar', { method: 'POST', body });
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
                const res = await fetch('belajar', { method: 'POST', body });
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
