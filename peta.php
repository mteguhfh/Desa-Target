<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/peta-posisi.php';
require_once __DIR__ . '/includes/bangunan-svg.php';

$pdo = db();
$bangunan = semua_bangunan($pdo);
$papanGang = papan_gang($pdo);
$totalKavling = count(POSISI_KAVLING); // Peta interaktif v3: 12 kavling di dunia 2000 x 1333.
$terbaruId = $bangunan !== [] ? (int) $bangunan[count($bangunan) - 1]['id'] : null;

// Titik fokus kamera saat halaman dibuka: kavling 0, tempat Balai Desa berdiri.
$fokusX = POSISI_KAVLING[0][0];
$fokusY = POSISI_KAVLING[0][1];
// Bang Uju berdiri di samping Balai Desa, sedikit ke kanan dan ke depan alun-alun.
$ujuX = $fokusX + 211;
$ujuY = $fokusY + 23;

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

    <div class="peta-viewport" id="petaViewport">
        <div class="peta-dunia" id="petaDunia">
            <svg class="peta-svg" viewBox="0 0 2000 1333" role="group" aria-label="Peta ilustrasi Desa Target. Geser untuk menjelajah, tap bangunan untuk melihat detailnya.">
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

                <!-- Latar desa versi 2 digambar ulang apa adanya di dalam grup
                     skala, sehingga koordinat 1200 x 800 miliknya pas dengan
                     dunia 2000 x 1333 versi 3. -->
                <g class="peta-latar" transform="scale(<?= SKALA_DUNIA ?>)">
                    <!-- Lahan desa dan bukit lembut di latar atas -->
                    <rect x="0" y="0" width="1200" height="800" fill="url(#gradLahan)"/>
                    <ellipse cx="240" cy="-35" rx="430" ry="115" fill="#a3e635" opacity="0.30"/>
                    <ellipse cx="985" cy="-55" rx="470" ry="125" fill="#84cc16" opacity="0.22"/>
                    <g class="awan" fill="#ffffff" opacity="0.85">
                        <ellipse cx="150" cy="52" rx="42" ry="13"/>
                        <ellipse cx="182" cy="44" rx="30" ry="11"/>
                    </g>
                    <g class="awan awan-lambat" fill="#ffffff" opacity="0.85">
                        <ellipse cx="1015" cy="46" rx="46" ry="13"/>
                        <ellipse cx="1050" cy="38" rx="30" ry="10"/>
                    </g>
                    <g class="awan" style="animation-delay:-14s" fill="#ffffff" opacity="0.8">
                        <ellipse cx="588" cy="64" rx="36" ry="11"/>
                        <ellipse cx="614" cy="57" rx="24" ry="9"/>
                    </g>

                    <!-- Kolam di pojok kanan bawah, lengkap dengan riaknya -->
                    <ellipse cx="1065" cy="722" rx="128" ry="58" fill="#f4e7c3"/>
                    <ellipse cx="1065" cy="718" rx="112" ry="50" fill="url(#gradKolam)"/>
                    <ellipse cx="1028" cy="704" rx="46" ry="13" fill="#e0f2fe" opacity="0.7"/>
                    <circle cx="1106" cy="726" r="5" fill="#22c55e"/>
                    <circle cx="1014" cy="731" r="5" fill="#22c55e"/>
                    <circle cx="1106" cy="726" r="1.8" fill="#f472b6"/>
                    <circle cx="1014" cy="731" r="1.8" fill="#f472b6"/>
                    <ellipse class="riak" cx="1065" cy="716" rx="54" ry="22" fill="none" stroke="#f0f9ff" stroke-width="2.5"/>
                    <ellipse class="riak riak-2" cx="1065" cy="716" rx="54" ry="22" fill="none" stroke="#f0f9ff" stroke-width="2.5"/>

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
                </g>

                <!-- Kavling: bangunan yang sudah berdiri dan tanah kosong -->
                <?php for ($i = 0; $i < $totalKavling; $i++): ?>
                    <?php if (isset($bangunan[$i])): ?>
                        <?= svg_bangunan($bangunan[$i], POSISI_KAVLING[$i], (int) $bangunan[$i]['id'] === $terbaruId) ?>
                    <?php else: ?>
                        <?= svg_tanah_kosong(POSISI_KAVLING[$i]) ?>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Bang Uju, Kepala Desa, berdiri di samping Balai Desa.
                     Gambarnya memakai potongan transparan dari aset lambaian
                     Bang Uju supaya menyatu dengan rumput desa. -->
                <g class="bang-uju" transform="translate(<?= $ujuX ?> <?= $ujuY ?>)" tabindex="0" role="button"
                   aria-label="Bang Uju, Kepala Desa Target"
                   data-jenis="kades" data-nama="Bang Uju"
                   data-komentar="Ikutin Bang Uju, biar sama-sama sampai tujuan!"
                   data-info="Komen paling banyak disukai hari ini, besoknya langsung Bang Uju bangun jadi bangunan baru di desa ini."
                   data-tautan="<?= e(APP_INSTAGRAM) ?>" data-tautan-teks="Ikut komen di Instagram @desatarget">
                    <title>Bang Uju, Kepala Desa</title>
                    <ellipse cx="0" cy="3" rx="42" ry="8" fill="#14532d" opacity="0.18"/>
                    <g class="bang-uju-dalam">
                        <image href="assets/bang-uju-peta.png" x="-66" y="-132" width="132" height="132" preserveAspectRatio="xMidYMax meet"/>
                    </g>
                </g>
            </svg>
        </div>

        <p class="peta-chip">Geser buat keliling. Tap bangunan atau Bang Uju.</p>

        <div class="peta-kontrol">
            <button type="button" id="zoomMasuk" aria-label="Perbesar peta">+</button>
            <button type="button" id="zoomKeluar" aria-label="Perkecil peta">&minus;</button>
            <button type="button" id="zoomReset" aria-label="Kembali ke Balai Desa">&#8982;</button>
        </div>

        <div class="peta-popover" id="petaPopover" hidden role="dialog" aria-label="Detail di peta">
            <button type="button" class="popover-tutup" id="popoverTutup" aria-label="Tutup detail">&times;</button>
            <p class="label-kecil" id="popLabel">Detail Bangunan</p>
            <h3 id="popNama"></h3>
            <p class="peta-popover-lencana">
                <span class="lencana-gang" id="popGang" hidden></span>
                <span class="lencana-kades" id="popKades" hidden>Kepala Desa</span>
                <span class="lencana-baru" id="popBaru" hidden>Baru dibangun</span>
            </p>
            <p class="kartu-komentar" id="popKomentarBaris" hidden>&ldquo;<span id="popKomentar"></span>&rdquo;</p>
            <p class="kartu-meta" id="popPemintaBaris" hidden>Diminta oleh <strong id="popPeminta"></strong></p>
            <p class="kartu-meta" id="popTanggalBaris" hidden>Berdiri <span id="popTanggal"></span></p>
            <p class="pop-info" id="popInfo" hidden></p>
            <p class="pop-tautan" id="popTautanBaris" hidden><a id="popTautan" href="#" target="_blank" rel="noopener"></a></p>
        </div>
    </div>
    <p class="peta-petunjuk">Seret peta buat keliling desa, pakai tombol plus dan minus buat zoom. Atap bangunan berwarna mengikuti gang pemiliknya.</p>
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
    var viewport = document.getElementById('petaViewport');
    var dunia = document.getElementById('petaDunia');
    var popover = document.getElementById('petaPopover');
    if (!viewport || !dunia || !popover) {
        return;
    }

    // --- Kamera dunia: dunia 2000 x 1333 digeser dan di-zoom di dalam viewport.
    var DUNIA_W = 2000;
    var DUNIA_H = 1333;
    var K_MIN = 1;
    var K_MAX = 2.5;
    var FOKUS = { x: <?= $fokusX ?>, y: <?= $fokusY ?> }; // Balai Desa
    var k = 1;        // zoom saat ini, 1 sampai 2.5
    var tx = 0;       // geser kamera dalam piksel layar
    var ty = 0;
    var baseS = 1;    // piksel layar per satuan dunia pada zoom 1

    function hitungDasar() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        baseS = Math.max(w / DUNIA_W, h / DUNIA_H);
        dunia.style.width = (DUNIA_W * baseS) + 'px';
        dunia.style.height = (DUNIA_H * baseS) + 'px';
    }

    function jepit() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        var dw = DUNIA_W * baseS * k;
        var dh = DUNIA_H * baseS * k;
        tx = Math.min(0, Math.max(w - dw, tx));
        ty = Math.min(0, Math.max(h - dh, ty));
    }

    function terapkan() {
        jepit();
        dunia.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + k + ')';
    }

    // Zoom ke titik layar (px, py) yang relatif terhadap viewport,
    // titik dunia di bawahnya dijaga tetap di tempat yang sama.
    function zoomKe(kBaru, px, py) {
        kBaru = Math.min(K_MAX, Math.max(K_MIN, kBaru));
        var lx = (px - tx) / k;
        var ly = (py - ty) / k;
        k = kBaru;
        tx = px - lx * k;
        ty = py - ly * k;
        terapkan();
    }

    function fokusBalai() {
        k = 1;
        tx = viewport.clientWidth / 2 - FOKUS.x * baseS;
        ty = viewport.clientHeight / 2 - FOKUS.y * baseS;
        terapkan();
    }

    // --- Popover detail melayang di dekat elemen yang ditap.
    var popLabel = document.getElementById('popLabel');
    var popNama = document.getElementById('popNama');
    var popGang = document.getElementById('popGang');
    var popKades = document.getElementById('popKades');
    var popBaru = document.getElementById('popBaru');
    var popKomentarBaris = document.getElementById('popKomentarBaris');
    var popKomentar = document.getElementById('popKomentar');
    var popPemintaBaris = document.getElementById('popPemintaBaris');
    var popPeminta = document.getElementById('popPeminta');
    var popTanggalBaris = document.getElementById('popTanggalBaris');
    var popTanggal = document.getElementById('popTanggal');
    var popInfo = document.getElementById('popInfo');
    var popTautanBaris = document.getElementById('popTautanBaris');
    var popTautan = document.getElementById('popTautan');

    function tutupPopover() {
        popover.hidden = true;
        var lama = document.querySelectorAll('.bangunan-svg.terpilih, .bang-uju.terpilih');
        for (var i = 0; i < lama.length; i++) {
            lama[i].classList.remove('terpilih');
        }
    }

    function bukaPopover(el) {
        tutupPopover();
        el.classList.add('terpilih');

        var d = el.dataset;
        var kades = d.jenis === 'kades';
        popLabel.textContent = kades ? 'Kepala Desa' : 'Detail Bangunan';
        popNama.textContent = d.nama || '';
        popGang.textContent = d.gang || '';
        popGang.hidden = kades || !d.gang;
        popKades.hidden = !kades;
        popBaru.hidden = d.terbaru !== '1';
        popKomentar.textContent = d.komentar || '';
        popKomentarBaris.hidden = !d.komentar;
        popPeminta.textContent = d.peminta || '';
        popPemintaBaris.hidden = kades || !d.peminta;
        popTanggal.textContent = d.tanggal || '';
        popTanggalBaris.hidden = kades || !d.tanggal;
        popInfo.textContent = d.info || '';
        popInfo.hidden = !d.info;
        if (d.tautan) {
            popTautan.href = d.tautan;
            popTautan.textContent = d.tautanTeks || d.tautan;
            popTautanBaris.hidden = false;
        } else {
            popTautanBaris.hidden = true;
        }

        popover.hidden = false;

        // Posisikan popover di atas elemen yang ditap; kalau tidak muat,
        // pindah ke bawahnya. Kiri dan kanan selalu dijepit di dalam viewport.
        var r = el.getBoundingClientRect();
        var v = viewport.getBoundingClientRect();
        var pw = popover.offsetWidth;
        var ph = popover.offsetHeight;
        var cx = r.left - v.left + r.width / 2;
        var left = Math.min(Math.max(8, cx - pw / 2), Math.max(8, v.width - pw - 8));
        var top = (r.top - v.top) - ph - 12;
        if (top < 8) {
            top = (r.bottom - v.top) + 12;
        }
        if (top + ph > v.height - 8) {
            top = Math.max(8, v.height - ph - 8);
        }
        popover.style.left = left + 'px';
        popover.style.top = top + 'px';
    }

    document.getElementById('popoverTutup').addEventListener('click', tutupPopover);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            tutupPopover();
        }
    });

    // --- Seret untuk menggeser, cubit untuk zoom, tap untuk membuka popover.
    var penunjuk = {};
    var jumlahPenunjuk = 0;
    var seret = null;
    var cubit = null;

    viewport.addEventListener('pointerdown', function (e) {
        if (e.target.closest && e.target.closest('.peta-kontrol, .peta-popover')) {
            return;
        }
        try {
            viewport.setPointerCapture(e.pointerId);
        } catch (err) {
            // Penangkap penunjuk tidak selalu tersedia, abaikan saja.
        }
        penunjuk[e.pointerId] = { x: e.clientX, y: e.clientY };
        jumlahPenunjuk++;
        if (jumlahPenunjuk === 1) {
            seret = { x: e.clientX, y: e.clientY, tx: tx, ty: ty, gerak: false, target: e.target };
            viewport.classList.add('menyeret');
        } else if (jumlahPenunjuk === 2) {
            var pts = Object.values(penunjuk);
            var r = viewport.getBoundingClientRect();
            var mx = (pts[0].x + pts[1].x) / 2 - r.left;
            var my = (pts[0].y + pts[1].y) / 2 - r.top;
            cubit = {
                d: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
                k: k,
                lx: (mx - tx) / k,
                ly: (my - ty) / k
            };
            seret = null;
            tutupPopover();
        }
    });

    viewport.addEventListener('pointermove', function (e) {
        if (!penunjuk[e.pointerId]) {
            return;
        }
        penunjuk[e.pointerId] = { x: e.clientX, y: e.clientY };
        if (cubit && jumlahPenunjuk === 2) {
            var pts = Object.values(penunjuk);
            var r = viewport.getBoundingClientRect();
            var d = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            var mx = (pts[0].x + pts[1].x) / 2 - r.left;
            var my = (pts[0].y + pts[1].y) / 2 - r.top;
            k = Math.min(K_MAX, Math.max(K_MIN, cubit.k * d / cubit.d));
            tx = mx - cubit.lx * k;
            ty = my - cubit.ly * k;
            terapkan();
        } else if (seret) {
            var dx = e.clientX - seret.x;
            var dy = e.clientY - seret.y;
            if (!seret.gerak && Math.abs(dx) + Math.abs(dy) > 7) {
                seret.gerak = true;
                tutupPopover();
            }
            if (seret.gerak) {
                tx = seret.tx + dx;
                ty = seret.ty + dy;
                terapkan();
            }
        }
    });

    function akhiriPenunjuk(e) {
        if (!penunjuk[e.pointerId]) {
            return;
        }
        delete penunjuk[e.pointerId];
        jumlahPenunjuk--;
        if (jumlahPenunjuk < 2) {
            cubit = null;
        }
        if (jumlahPenunjuk === 0) {
            viewport.classList.remove('menyeret');
            if (seret && !seret.gerak) {
                // Gerakan pendek tanpa geser berarti tap.
                var t = seret.target;
                var el = (t && t.closest) ? t.closest('.bangunan-svg, .bang-uju') : null;
                if (el) {
                    bukaPopover(el);
                } else {
                    tutupPopover();
                }
            }
            seret = null;
        }
    }
    viewport.addEventListener('pointerup', akhiriPenunjuk);
    viewport.addEventListener('pointercancel', akhiriPenunjuk);

    // Roda mouse buat zoom mengikuti posisi kursor.
    viewport.addEventListener('wheel', function (e) {
        e.preventDefault();
        tutupPopover();
        var r = viewport.getBoundingClientRect();
        zoomKe(k * Math.exp(-e.deltaY * 0.0016), e.clientX - r.left, e.clientY - r.top);
    }, { passive: false });

    // Tombol zoom di layar.
    document.getElementById('zoomMasuk').addEventListener('click', function () {
        tutupPopover();
        zoomKe(k * 1.4, viewport.clientWidth / 2, viewport.clientHeight / 2);
    });
    document.getElementById('zoomKeluar').addEventListener('click', function () {
        tutupPopover();
        zoomKe(k / 1.4, viewport.clientWidth / 2, viewport.clientHeight / 2);
    });
    document.getElementById('zoomReset').addEventListener('click', function () {
        tutupPopover();
        fokusBalai();
    });

    // Papan ketik: Enter atau spasi pada bangunan dan Bang Uju membuka popover.
    var interaktif = document.querySelectorAll('.bangunan-svg, .bang-uju');
    for (var i = 0; i < interaktif.length; i++) {
        (function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    bukaPopover(el);
                }
            });
        })(interaktif[i]);
    }

    window.addEventListener('resize', function () {
        hitungDasar();
        terapkan();
        tutupPopover();
    });

    hitungDasar();
    fokusBalai();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
