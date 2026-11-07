<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/peta-posisi.php';
require_once __DIR__ . '/includes/bangunan-svg.php';

$pdo = db();
$bangunan = semua_bangunan($pdo);
$papanGang = papan_gang($pdo);
$totalKavling = count(POSISI_KAVLING); // Peta interaktif v2: 12 kavling di adegan desa.
$terbaruId = $bangunan !== [] ? (int) $bangunan[count($bangunan) - 1]['id'] : null;
// Panel detail dibuka dengan bangunan pertama (Balai Desa) sudah terpilih.
$bangunanAwal = $bangunan[0] ?? null;

$judulHalaman = 'Peta Desa';
$halamanAktif = 'peta';
require __DIR__ . '/includes/header.php';
?>

<section class="seksi">
    <h1>Peta Desa Target</h1>
    <p class="seksi-deskripsi">
        Setiap bangunan di peta ini lahir dari komentar warga di Instagram.
        Komentar paling banyak disukai hari ini, besok paginya sudah berdiri di sini.
        Masih banyak tanah kosong, desa ini menunggu usulan usahamu.
    </p>

    <div class="peta-bungkus">
        <div class="peta-panggung">
            <svg class="peta-svg" viewBox="0 0 1200 800" role="group" aria-label="Peta ilustrasi Desa Target. Klik bangunan untuk melihat detailnya.">
                <defs>
                    <linearGradient id="gradLahan" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#dcfce7"/>
                        <stop offset="1" stop-color="#bbf7d0"/>
                    </linearGradient>
                    <linearGradient id="gradKolam" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#7dd3fc"/>
                        <stop offset="1" stop-color="#38bdf8"/>
                    </linearGradient>
                    <g id="pohon">
                        <rect x="-3" y="-14" width="6" height="14" fill="#92600f"/>
                        <circle cx="0" cy="-27" r="15" fill="#22c55e"/>
                        <circle cx="-11" cy="-19" r="10" fill="#4ade80"/>
                        <circle cx="11" cy="-19" r="10" fill="#16a34a"/>
                    </g>
                    <g id="semak">
                        <circle cx="-8" cy="-4" r="7" fill="#4ade80"/>
                        <circle cx="6" cy="-5" r="9" fill="#22c55e"/>
                        <circle cx="15" cy="-3" r="6" fill="#4ade80"/>
                    </g>
                </defs>

                <!-- Lahan desa dan bukit lembut di latar atas -->
                <rect x="0" y="0" width="1200" height="800" fill="url(#gradLahan)"/>
                <ellipse cx="240" cy="-35" rx="430" ry="115" fill="#a3e635" opacity="0.30"/>
                <ellipse cx="985" cy="-55" rx="470" ry="125" fill="#84cc16" opacity="0.22"/>
                <g fill="#ffffff" opacity="0.85">
                    <ellipse cx="150" cy="52" rx="42" ry="13"/>
                    <ellipse cx="182" cy="44" rx="30" ry="11"/>
                    <ellipse cx="1015" cy="46" rx="46" ry="13"/>
                    <ellipse cx="1050" cy="38" rx="30" ry="10"/>
                </g>

                <!-- Kolam di pojok kanan bawah -->
                <ellipse cx="1065" cy="722" rx="128" ry="58" fill="#f4e7c3"/>
                <ellipse cx="1065" cy="718" rx="112" ry="50" fill="url(#gradKolam)"/>
                <ellipse cx="1028" cy="704" rx="46" ry="13" fill="#e0f2fe" opacity="0.7"/>
                <circle cx="1106" cy="726" r="5" fill="#22c55e"/>
                <circle cx="1014" cy="731" r="5" fill="#22c55e"/>
                <circle cx="1106" cy="726" r="1.8" fill="#f472b6"/>
                <circle cx="1014" cy="731" r="1.8" fill="#f472b6"/>

                <!-- Jalan utama desa yang berkelok -->
                <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#e9d8a6" stroke-width="58" stroke-linecap="round"/>
                <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#f4e7c3" stroke-width="45" stroke-linecap="round"/>
                <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#ffffff" stroke-width="3" stroke-dasharray="16 14" opacity="0.7" stroke-linecap="round"/>

                <!-- Alun-alun kecil tempat Balai Desa berdiri -->
                <ellipse cx="610" cy="428" rx="148" ry="49" fill="#efe7d2"/>
                <ellipse cx="610" cy="428" rx="148" ry="49" fill="none" stroke="#ddd2b4" stroke-width="2"/>
                <path d="M610,472 C610,512 608,542 604,576" fill="none" stroke="#efe7d2" stroke-width="24" stroke-linecap="round"/>

                <!-- Pepohonan dan semak penghias desa -->
                <use href="#pohon" transform="translate(70 185)"/>
                <use href="#pohon" transform="translate(520 115) scale(0.9)"/>
                <use href="#pohon" transform="translate(715 285) scale(1.1)"/>
                <use href="#pohon" transform="translate(985 120)"/>
                <use href="#pohon" transform="translate(305 95) scale(0.8)"/>
                <use href="#pohon" transform="translate(55 470)"/>
                <use href="#pohon" transform="translate(565 692)"/>
                <use href="#pohon" transform="translate(845 758)"/>
                <use href="#pohon" transform="translate(1175 190) scale(0.9)"/>
                <use href="#pohon" transform="translate(90 762) scale(0.85)"/>
                <use href="#semak" transform="translate(430 330)"/>
                <use href="#semak" transform="translate(790 150)"/>
                <use href="#semak" transform="translate(185 415)"/>
                <use href="#semak" transform="translate(660 748)"/>
                <use href="#semak" transform="translate(1020 472)"/>
                <g>
                    <circle cx="255" cy="380" r="2.6" fill="#f472b6"/>
                    <circle cx="905" cy="330" r="2.6" fill="#facc15"/>
                    <circle cx="480" cy="120" r="2.6" fill="#f472b6"/>
                    <circle cx="720" cy="480" r="2.6" fill="#facc15"/>
                    <circle cx="120" cy="250" r="2.6" fill="#facc15"/>
                    <circle cx="1080" cy="90" r="2.6" fill="#f472b6"/>
                </g>

                <!-- Kavling: bangunan yang sudah berdiri dan tanah kosong -->
                <?php for ($i = 0; $i < $totalKavling; $i++): ?>
                    <?php if (isset($bangunan[$i])): ?>
                        <?= svg_bangunan($bangunan[$i], POSISI_KAVLING[$i], (int) $bangunan[$i]['id'] === $terbaruId) ?>
                    <?php else: ?>
                        <?= svg_tanah_kosong(POSISI_KAVLING[$i]) ?>
                    <?php endif; ?>
                <?php endfor; ?>
            </svg>
        </div>

        <aside class="peta-detail" aria-live="polite">
            <p class="label-kecil">Detail Bangunan</p>
            <h3 id="detailNama"><?= $bangunanAwal !== null ? e($bangunanAwal['nama']) : 'Belum ada bangunan' ?></h3>
            <p class="peta-detail-lencana">
                <span class="lencana-gang" id="detailGang"<?= $bangunanAwal === null || empty($bangunanAwal['gang']) ? ' hidden' : '' ?>><?= $bangunanAwal !== null ? e($bangunanAwal['gang'] ?? '') : '' ?></span>
                <span class="lencana-baru" id="detailBaru"<?= $bangunanAwal === null || (int) $bangunanAwal['id'] !== $terbaruId ? ' hidden' : '' ?>>Baru dibangun</span>
            </p>
            <?php if ($bangunanAwal !== null): ?>
                <p class="kartu-komentar" id="detailKomentarBaris">
                    &ldquo;<span id="detailKomentar"><?= e($bangunanAwal['komentar'] ?? '') ?></span>&rdquo;
                </p>
                <p class="kartu-meta" id="detailPemintaBaris">Diminta oleh <strong id="detailPeminta"><?= e($bangunanAwal['diminta_oleh'] ?? '') ?></strong></p>
                <p class="kartu-meta">Berdiri <span id="detailTanggal"><?= e(format_tanggal((string) $bangunanAwal['tanggal'])) ?></span></p>
            <?php endif; ?>
            <p class="peta-detail-petunjuk">Klik bangunan lain di peta buat lihat cerita di balik berdirinya.</p>
        </aside>
    </div>
    <p class="peta-petunjuk">Klik atau sentuh bangunan di peta buat lihat siapa pemintanya dan komentar asalnya. Atap bangunan berwarna mengikuti gang pemiliknya.</p>
</section>

<section class="seksi">
    <div class="seksi-kepala">
        <h2>Papan Gang</h2>
        <a href="ktp.php">Gabung gang-mu</a>
    </div>
    <p class="seksi-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
    <?php require __DIR__ . '/includes/papan-gang.php'; ?>
</section>

<script>
(function () {
    var nama = document.getElementById('detailNama');
    if (!nama) {
        return;
    }
    var gang = document.getElementById('detailGang');
    var baru = document.getElementById('detailBaru');
    var komentarBaris = document.getElementById('detailKomentarBaris');
    var komentar = document.getElementById('detailKomentar');
    var pemintaBaris = document.getElementById('detailPemintaBaris');
    var peminta = document.getElementById('detailPeminta');
    var tanggal = document.getElementById('detailTanggal');

    function pilih(el) {
        var lama = document.querySelectorAll('.bangunan-svg.terpilih');
        for (var i = 0; i < lama.length; i++) {
            lama[i].classList.remove('terpilih');
        }
        el.classList.add('terpilih');

        var d = el.dataset;
        nama.textContent = d.nama || '';
        if (gang) {
            gang.textContent = d.gang || '';
            gang.hidden = !d.gang;
        }
        if (baru) {
            baru.hidden = d.terbaru !== '1';
        }
        if (komentarBaris && komentar) {
            komentar.textContent = d.komentar || '';
            komentarBaris.hidden = !d.komentar;
        }
        if (pemintaBaris && peminta) {
            peminta.textContent = d.peminta || '';
            pemintaBaris.hidden = !d.peminta;
        }
        if (tanggal) {
            tanggal.textContent = d.tanggal || '';
        }
    }

    var semua = document.querySelectorAll('.bangunan-svg');
    for (var j = 0; j < semua.length; j++) {
        (function (el) {
            el.addEventListener('click', function () { pilih(el); });
            el.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    pilih(el);
                }
            });
        })(semua[j]);
    }

    // Bangunan pertama di peta adalah Balai Desa, langsung ditandai terpilih
    // supaya panel detail di sampingnya terasa nyambung sejak halaman dibuka.
    if (semua.length > 0) {
        pilih(semua[0]);
    }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
