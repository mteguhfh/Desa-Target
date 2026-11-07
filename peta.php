<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/peta-posisi.php';
require_once __DIR__ . '/includes/bangunan-svg.php';
require_once __DIR__ . '/includes/interior-balai.php';

$pdo = db();
$bangunan = semua_bangunan($pdo);
$papanGang = papan_gang($pdo);
$totalKavling = count(POSISI_KAVLING); // Peta interaktif: 12 kavling di dunia 2000 x 1333.
$terbaruId = $bangunan !== [] ? (int) $bangunan[count($bangunan) - 1]['id'] : null;
$bangunanTerbaru = bangunan_terbaru($pdo);

// Warga versi 4: setiap warga terdaftar berdiri sebagai avatar di zona
// gang-nya. Warga paling awal mendaftar didahulukan; yang dirender di
// peta dibatasi 80 avatar dan dibatasi lagi oleh kapasitas grid zona.
// Sisanya dihitung supaya papan zona bisa menulis "+N warga lainnya".
$semuaWarga = $pdo->query('SELECT * FROM warga ORDER BY id ASC')->fetchAll();
$totalPerGang = [];
foreach ($semuaWarga as $w) {
    $totalPerGang[$w['gang']] = ($totalPerGang[$w['gang']] ?? 0) + 1;
}
$wargaPerGang = [];
$berdiriTotal = 0;
foreach ($semuaWarga as $w) {
    $gangWarga = (string) $w['gang'];
    if (!isset(ZONA_GANG[$gangWarga]) || $berdiriTotal >= 80) {
        continue;
    }
    $zonaWarga = ZONA_GANG[$gangWarga];
    $indeks = count($wargaPerGang[$gangWarga] ?? []);
    if ($indeks >= $zonaWarga['kolom'] * $zonaWarga['baris']) {
        continue;
    }
    $wargaPerGang[$gangWarga][] = [
        'warga' => $w,
        'x' => $zonaWarga['awal'][0] + ($indeks % $zonaWarga['kolom']) * $zonaWarga['dx'],
        'y' => $zonaWarga['awal'][1] + intdiv($indeks, $zonaWarga['kolom']) * $zonaWarga['dy'],
    ];
    $berdiriTotal++;
}

// Titik fokus kamera saat halaman dibuka: kavling 0, tempat Balai Desa berdiri.
$fokusX = POSISI_KAVLING[0][0];
$fokusY = POSISI_KAVLING[0][1];
// Bang Uju berdiri di samping Balai Desa, sedikit ke kanan dan ke depan alun-alun.
$ujuX = $fokusX + 211;
$ujuY = $fokusY + 23;

$judulHalaman = 'Peta Desa';
$halamanAktif = 'peta';
// Peta adalah layar pertama itu sendiri (versi 4.1): kepala situs biasa
// tidak digambar, diganti bilah merek dan navigasi yang melayang di atas peta.
$tanpaHeaderSitus = true;
require __DIR__ . '/includes/header.php';
?>

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

                <!-- Zona gang versi 4: petak warna dan papan nama tiap gang,
                     digambar di bawah bangunan supaya tidak menutupi kavling. -->
                <?php foreach (ZONA_GANG as $gang => $zona): ?>
                    <?= svg_zona_gang($gang, $zona, $totalPerGang[$gang] ?? 0, count($wargaPerGang[$gang] ?? [])) ?>
                <?php endforeach; ?>

                <!-- Kavling: bangunan yang sudah berdiri dan tanah kosong -->
                <?php for ($i = 0; $i < $totalKavling; $i++): ?>
                    <?php if (isset($bangunan[$i])): ?>
                        <?= svg_bangunan($bangunan[$i], POSISI_KAVLING[$i], (int) $bangunan[$i]['id'] === $terbaruId) ?>
                    <?php else: ?>
                        <?= svg_tanah_kosong(POSISI_KAVLING[$i]) ?>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Avatar para warga versi 4, berdiri di zona gang masing-masing. -->
                <?php foreach ($wargaPerGang as $daftarWarga): ?>
                    <?php foreach ($daftarWarga as $tempat): ?>
                        <?= svg_warga($tempat['warga'], $tempat['x'], $tempat['y']) ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>

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

                <!-- Karakter pengunjung versi 4 buat mode Keliling Desa:
                     pejalan kecil bertopi merah dan beransel, sengaja beda
                     dari Bang Uju supaya pengunjung tidak mengira dirinya
                     Kepala Desa. Posisinya digerakkan skrip halaman ini. -->
                <g id="karakterJalan" hidden>
                    <ellipse cx="0" cy="3" rx="21" ry="6" fill="#14532d" opacity="0.20"/>
                    <g id="karakterGoyang">
                        <g id="karakterBadan">
                            <rect x="-21" y="-56" width="13" height="24" rx="5" fill="#d97706"/>
                            <rect x="-21" y="-56" width="13" height="8" rx="4" fill="#b45309"/>
                            <rect x="-10" y="-16" width="8" height="16" rx="2" fill="#334155"/>
                            <rect x="3" y="-16" width="8" height="16" rx="2" fill="#334155"/>
                            <rect x="-19" y="-54" width="7" height="24" rx="3.5" fill="#0f766e"/>
                            <rect x="12" y="-54" width="7" height="24" rx="3.5" fill="#0f766e"/>
                            <rect x="-14" y="-58" width="28" height="44" rx="9" fill="#0d9488"/>
                            <rect x="-14" y="-58" width="28" height="10" rx="5" fill="#0f766e"/>
                            <circle cx="0" cy="-70" r="13" fill="#f6c48a"/>
                            <path d="M-13,-72 a13,13 0 0 1 26,0 Z" fill="#dc2626"/>
                            <rect x="-3" y="-74" width="22" height="5" rx="2.5" fill="#b91c1c"/>
                        </g>
                        <g class="karakter-label">
                            <rect x="-26" y="-112" width="52" height="21" rx="10.5" fill="#ffffff" fill-opacity="0.95" stroke="#cbd5e1"/>
                            <text x="0" y="-97.5" text-anchor="middle" font-size="13" font-weight="700" fill="#1d4ed8">Kamu</text>
                        </g>
                    </g>
                </g>
            </svg>
        </div>

        <!-- Bilah merek dan navigasi melayang di pojok kiri atas peta,
             pengganti kepala situs khusus halaman peta sepenuh layar. -->
        <div class="peta-merek-baris">
            <a class="peta-merek" href="index.php">
                <img src="assets/bang-uju.png" alt="" class="peta-merek-logo">
                <span>Desa <strong>Target</strong></span>
            </a>
            <nav class="peta-nav" aria-label="Navigasi peta">
                <a href="index.php">Beranda</a>
                <a href="ktp.php">Bikin KTP Warga</a>
                <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">Instagram</a>
            </nav>
        </div>

        <!-- Kartu pengantar di bawah bilah merek. Bisa ditutup dengan
             tombol silang; di ponsel dan layar pendek kartu mulai dalam
             keadaan tertutup lewat media query di stylesheet. -->
        <div class="peta-intro" id="petaIntro">
            <button type="button" class="popover-tutup" id="introTutup" aria-label="Tutup pengantar">&times;</button>
            <h1>Peta Desa Target</h1>
            <p>
                Setiap bangunan di peta ini lahir dari komentar warga di Instagram.
                Komentar paling banyak disukai hari ini, besok paginya sudah berdiri di sini.
                Masih banyak tanah kosong, desa ini menunggu usulan usahamu.
            </p>
        </div>

        <p class="peta-chip" id="petaChip">Geser buat keliling. Tap bangunan, atau jalan-jalan pakai tombol Keliling Desa.</p>

        <div class="peta-kontrol">
            <button type="button" id="tombolJalan" class="tombol-jalan" aria-pressed="false">Keliling Desa</button>
            <button type="button" id="tombolPanelGang" class="tombol-panel-gang" aria-expanded="false" aria-controls="panelGang">Papan Gang</button>
            <button type="button" id="zoomMasuk" aria-label="Perbesar peta">+</button>
            <button type="button" id="zoomKeluar" aria-label="Perkecil peta">&minus;</button>
            <button type="button" id="zoomReset" aria-label="Kembali ke Balai Desa">&#8982;</button>
            <button type="button" id="layarPenuh" aria-label="Layar penuh">&#10530;</button>
        </div>

        <button type="button" class="chip-prompt" id="chipPrompt" hidden></button>

        <div class="peta-dpad" id="petaDpad" hidden aria-label="Tombol arah buat jalan-jalan">
            <span></span>
            <button type="button" data-arah="atas" aria-label="Jalan ke atas">&#9650;</button>
            <span></span>
            <button type="button" data-arah="kiri" aria-label="Jalan ke kiri">&#9664;</button>
            <span></span>
            <button type="button" data-arah="kanan" aria-label="Jalan ke kanan">&#9654;</button>
            <span></span>
            <button type="button" data-arah="bawah" aria-label="Jalan ke bawah">&#9660;</button>
            <span></span>
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
            <p class="kartu-meta" id="popNomorBaris" hidden>Warga <strong id="popNomor"></strong></p>
            <p class="kartu-meta" id="popTanggalBaris" hidden><span id="popTanggalKata">Berdiri</span> <span id="popTanggal"></span></p>
            <p class="pop-info" id="popInfo" hidden></p>
            <p class="pop-tautan" id="popTautanBaris" hidden><a id="popTautan" href="#" target="_blank" rel="noopener"></a></p>
        </div>

        <div class="peta-interior" id="petaInterior" hidden>
            <?= interior_balai_svg($bangunanTerbaru) ?>
            <button type="button" class="interior-keluar" id="tombolKeluarInterior">&larr; Keluar ke Peta</button>
        </div>

        <!-- Panel Papan Gang: meluncur dari kanan di dalam bingkai peta,
             dibuka dari tombol Papan Gang di tumpukan kontrol. Isinya sama
             dengan papan gang lama, hanya pindah tempat dari bawah peta. -->
        <aside class="panel-gang" id="panelGang" aria-hidden="true" aria-label="Papan peringkat gang">
            <div class="panel-gang-kepala">
                <h2>Papan Gang</h2>
                <button type="button" class="popover-tutup" id="panelGangTutup" aria-label="Tutup papan gang">&times;</button>
            </div>
            <p class="seksi-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
            <?php require __DIR__ . '/includes/papan-gang.php'; ?>
            <p class="panel-gang-gabung"><a href="ktp.php">Gabung gang-mu</a></p>
        </aside>

        <!-- Tombol panah ke bawah: jalan keluar buat pengunjung menggulir
             halaman melewati peta yang memenuhi layar menuju konten di
             bawahnya. Disembunyikan stylesheet saat peta layar penuh. -->
        <button type="button" class="peta-gulir-bawah" id="tombolGulirBawah" aria-label="Lihat ke bawah">&#9660;</button>
    </div>
    <p class="peta-petunjuk">Seret peta buat keliling desa, pakai tombol plus dan minus buat zoom. Pencet tombol Keliling Desa buat jalan-jalan keliling desa dan masuk Balai Desa. Atap bangunan dan baju warga berwarna mengikuti gang-nya.</p>

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
    var popNomorBaris = document.getElementById('popNomorBaris');
    var popNomor = document.getElementById('popNomor');
    var popTanggalBaris = document.getElementById('popTanggalBaris');
    var popTanggalKata = document.getElementById('popTanggalKata');
    var popTanggal = document.getElementById('popTanggal');
    var popInfo = document.getElementById('popInfo');
    var popTautanBaris = document.getElementById('popTautanBaris');
    var popTautan = document.getElementById('popTautan');

    function tutupPopover() {
        popover.hidden = true;
        var lama = document.querySelectorAll('.bangunan-svg.terpilih, .bang-uju.terpilih, .warga-svg.terpilih, .hotspot-interior.terpilih');
        for (var i = 0; i < lama.length; i++) {
            lama[i].classList.remove('terpilih');
        }
    }

    function bukaPopover(el) {
        tutupPopover();
        el.classList.add('terpilih');

        var d = el.dataset;
        var kades = d.jenis === 'kades';
        var warga = d.jenis === 'warga';
        var papan = d.jenis === 'papan';
        popLabel.textContent = kades ? 'Kepala Desa' : (warga ? 'Warga Desa' : (papan ? 'Pengumuman Desa' : 'Detail Bangunan'));
        popNama.textContent = d.nama || '';
        popGang.textContent = d.gang || '';
        popGang.hidden = kades || !d.gang;
        popKades.hidden = !kades;
        popBaru.hidden = d.terbaru !== '1';
        popKomentar.textContent = d.komentar || '';
        popKomentarBaris.hidden = !d.komentar;
        popPeminta.textContent = d.peminta || '';
        popPemintaBaris.hidden = !d.peminta;
        popNomor.textContent = d.nomor || '';
        popNomorBaris.hidden = !warga;
        popTanggalKata.textContent = warga ? 'Bergabung' : 'Berdiri';
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
            if (interiorTerbuka) {
                tutupInterior();
            } else if (panelGangTerbuka) {
                aturPanelGang(false);
            } else {
                tutupPopover();
            }
        }
    });

    // --- Seret untuk menggeser, cubit untuk zoom, tap untuk membuka popover.
    var penunjuk = {};
    var jumlahPenunjuk = 0;
    var seret = null;
    var cubit = null;

    viewport.addEventListener('pointerdown', function (e) {
        if (interiorTerbuka) {
            return;
        }
        if (e.target.closest && e.target.closest('.peta-kontrol, .peta-popover, .peta-dpad, .chip-prompt, .peta-merek-baris, .peta-intro, .panel-gang, .peta-gulir-bawah')) {
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
            // Di mode Keliling Desa, seretan hanya dibaca sebagai tap:
            // kamera mengikuti karakter, bukan geseran jari.
            seret = { x: e.clientX, y: e.clientY, tx: tx, ty: ty, gerak: false, target: e.target, tanpaGeser: jalanAktif };
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
            if (seret.gerak && !seret.tanpaGeser) {
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
                var el = (t && t.closest) ? t.closest('.bangunan-svg, .bang-uju, .warga-svg') : null;
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

    // Roda mouse: zoom hanya di layar penuh atau saat Ctrl atau Cmd
    // ditahan, mengikuti posisi kursor (pinch trackpad juga sampai ke sini
    // sebagai ctrl+wheel). Roda biasa di luar layar penuh tidak dicegat
    // sama sekali, halaman menggulir seperti biasa dan chip petunjuk
    // memberi tahu gestur zoom-nya sebentar. Dulu roda selalu dicegat,
    // jadi sejak peta memenuhi layar pengunjung merasa terjebak: niat
    // menggulir halaman malah memperbesar peta.
    // Di atas panel gang, roda mouse menggulir panelnya, bukan zoom peta.
    var chipPeta = document.getElementById('petaChip');
    var teksChipPeta = chipPeta ? chipPeta.textContent : '';
    var timerChipPeta = null;
    function tampilPetunjukCtrl() {
        if (!chipPeta) {
            return;
        }
        chipPeta.textContent = 'Tahan Ctrl sambil scroll buat zoom peta';
        if (timerChipPeta) {
            clearTimeout(timerChipPeta);
        }
        timerChipPeta = setTimeout(function () {
            chipPeta.textContent = teksChipPeta;
            timerChipPeta = null;
        }, 2000);
    }
    viewport.addEventListener('wheel', function (e) {
        if (e.target.closest && e.target.closest('.panel-gang')) {
            return;
        }
        if (document.fullscreenElement !== viewport && !e.ctrlKey && !e.metaKey) {
            tampilPetunjukCtrl();
            return;
        }
        e.preventDefault();
        tutupPopover();
        var r = viewport.getBoundingClientRect();
        zoomKe(k * Math.exp(-e.deltaY * 0.0016), e.clientX - r.left, e.clientY - r.top);
    }, { passive: false });

    // Tombol panah ke bawah menggulir halaman melewati bingkai peta
    // menuju konten di bawahnya, supaya tidak ada yang terjebak di peta.
    document.getElementById('tombolGulirBawah').addEventListener('click', function () {
        var kurangGerak = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        var batasBawah = viewport.getBoundingClientRect().bottom + window.scrollY;
        window.scrollTo({ top: batasBawah, behavior: kurangGerak ? 'auto' : 'smooth' });
    });

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

    // Papan ketik: Enter atau spasi pada bangunan, Bang Uju, dan warga membuka popover.
    var interaktif = document.querySelectorAll('.bangunan-svg, .bang-uju, .warga-svg');
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

    // === Versi 4: mode Keliling Desa dan interior Balai Desa ===
    var tombolJalan = document.getElementById('tombolJalan');
    var dpad = document.getElementById('petaDpad');
    var chipPrompt = document.getElementById('chipPrompt');
    var karakterEl = document.getElementById('karakterJalan');
    var karakterGoyang = document.getElementById('karakterGoyang');
    var karakterBadan = document.getElementById('karakterBadan');
    var interior = document.getElementById('petaInterior');
    var gerakDikurangi = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var jalanAktif = false;
    var interiorTerbuka = false;
    var rafId = null;
    var capWaktu = 0;
    var hadap = 1;
    var sedangJalan = false;
    // Karakter mulai berdiri di alun-alun, tepat di depan Balai Desa.
    var karakter = { x: FOKUS.x, y: FOKUS.y + 88 };
    var arahTekan = { atas: false, bawah: false, kiri: false, kanan: false };
    var promptTarget = null;
    var promptBalai = false;

    // Rintangan sederhana untuk kaki karakter: kolam (persegi kasar)
    // dan tapak tiap bangunan yang sudah berdiri.
    var rintangan = [[1530, 1075, 2010, 1320]];
    var tapakBangunan = [
        <?php foreach ($bangunan as $i => $b): ?>
        [<?= (int) POSISI_KAVLING[$i][0] ?>, <?= (int) POSISI_KAVLING[$i][1] ?>],
        <?php endforeach; ?>
    ];
    for (var tb = 0; tb < tapakBangunan.length; tb++) {
        rintangan.push([tapakBangunan[tb][0] - 98, tapakBangunan[tb][1] - 120, tapakBangunan[tb][0] + 98, tapakBangunan[tb][1] + 2]);
    }

    function dalamRintangan(x, y) {
        for (var i = 0; i < rintangan.length; i++) {
            var r = rintangan[i];
            if (x > r[0] && x < r[2] && y > r[1] && y < r[3]) {
                return true;
            }
        }
        return false;
    }

    function gambarKarakter(waktu) {
        var bob = (sedangJalan && !gerakDikurangi) ? Math.sin(waktu / 90) * 3.2 : 0;
        karakterEl.setAttribute('transform', 'translate(' + karakter.x + ' ' + karakter.y + ')');
        karakterGoyang.setAttribute('transform', 'translate(0 ' + bob.toFixed(1) + ')');
        karakterBadan.setAttribute('transform', 'scale(' + hadap + ' 1)');
    }

    function langkahKarakter(dt) {
        var vx = (arahTekan.kanan ? 1 : 0) - (arahTekan.kiri ? 1 : 0);
        var vy = (arahTekan.bawah ? 1 : 0) - (arahTekan.atas ? 1 : 0);
        sedangJalan = vx !== 0 || vy !== 0;
        if (!sedangJalan) {
            return;
        }
        var panjang = Math.hypot(vx, vy) || 1;
        var jarak = 265 * dt;
        var nx = karakter.x + vx / panjang * jarak;
        var ny = karakter.y + vy / panjang * jarak;
        nx = Math.min(1950, Math.max(50, nx));
        ny = Math.min(1290, Math.max(95, ny));
        if (!dalamRintangan(nx, karakter.y)) {
            karakter.x = nx;
        }
        if (!dalamRintangan(karakter.x, ny)) {
            karakter.y = ny;
        }
        if (vx < 0) {
            hadap = -1;
        } else if (vx > 0) {
            hadap = 1;
        }
    }

    function ikutiKarakter() {
        var targetTx = viewport.clientWidth / 2 - karakter.x * baseS * k;
        var targetTy = viewport.clientHeight / 2 - karakter.y * baseS * k;
        tx += (targetTx - tx) * 0.16;
        ty += (targetTy - ty) * 0.16;
        terapkan();
    }

    function perbaruiPrompt() {
        var els = document.querySelectorAll('.bangunan-svg');
        var terbaik = null;
        var jarakTerbaik = 160;
        for (var i = 0; i < els.length; i++) {
            var d = Math.hypot(parseFloat(els[i].dataset.x) - karakter.x, parseFloat(els[i].dataset.y) - karakter.y);
            if (d < jarakTerbaik) {
                jarakTerbaik = d;
                terbaik = els[i];
            }
        }
        if (!terbaik) {
            promptTarget = null;
            chipPrompt.hidden = true;
            return;
        }
        promptTarget = terbaik;
        promptBalai = terbaik.dataset.balai === '1';
        chipPrompt.textContent = promptBalai ? 'Masuk Balai Desa' : 'Lihat ' + (terbaik.dataset.nama || 'bangunan');
        chipPrompt.style.left = (tx + karakter.x * baseS * k) + 'px';
        chipPrompt.style.top = (ty + (karakter.y - 108) * baseS * k) + 'px';
        chipPrompt.hidden = false;
    }

    function putaranJalan(waktu) {
        if (!jalanAktif) {
            rafId = null;
            return;
        }
        var dt = Math.min(0.05, capWaktu ? (waktu - capWaktu) / 1000 : 0);
        capWaktu = waktu;
        if (!interiorTerbuka) {
            langkahKarakter(dt);
            gambarKarakter(waktu);
            ikutiKarakter();
            perbaruiPrompt();
        }
        rafId = requestAnimationFrame(putaranJalan);
    }

    function aturJalan(aktif) {
        jalanAktif = aktif;
        tombolJalan.classList.toggle('aktif', aktif);
        tombolJalan.setAttribute('aria-pressed', aktif ? 'true' : 'false');
        tombolJalan.textContent = aktif ? 'Berhenti' : 'Keliling Desa';
        dpad.hidden = !aktif;
        karakterEl.hidden = !aktif;
        viewport.classList.toggle('mode-jalan', aktif);
        if (aktif) {
            tutupPopover();
            gambarKarakter(0);
            capWaktu = 0;
            if (!rafId) {
                rafId = requestAnimationFrame(putaranJalan);
            }
        } else {
            arahTekan = { atas: false, bawah: false, kiri: false, kanan: false };
            promptTarget = null;
            chipPrompt.hidden = true;
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
        }
    }
    tombolJalan.addEventListener('click', function () {
        aturJalan(!jalanAktif);
    });
    chipPrompt.addEventListener('click', function () {
        if (!promptTarget) {
            return;
        }
        if (promptBalai) {
            bukaInterior();
        } else {
            bukaPopover(promptTarget);
        }
    });

    // D-pad di layar: tekan dan tahan untuk berjalan.
    var tombolDpad = dpad.querySelectorAll('button[data-arah]');
    for (var td = 0; td < tombolDpad.length; td++) {
        (function (btn) {
            btn.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                arahTekan[btn.dataset.arah] = true;
            });
            var lepas = function () {
                arahTekan[btn.dataset.arah] = false;
            };
            btn.addEventListener('pointerup', lepas);
            btn.addEventListener('pointerleave', lepas);
            btn.addEventListener('pointercancel', lepas);
            btn.addEventListener('contextmenu', function (e) {
                e.preventDefault();
            });
        })(tombolDpad[td]);
    }

    // Papan ketik buat berjalan: panah dan WASD, E buat memakai prompt.
    var petaArah = { ArrowUp: 'atas', KeyW: 'atas', ArrowDown: 'bawah', KeyS: 'bawah', ArrowLeft: 'kiri', KeyA: 'kiri', ArrowRight: 'kanan', KeyD: 'kanan' };
    document.addEventListener('keydown', function (e) {
        if (!jalanAktif || interiorTerbuka) {
            return;
        }
        if (petaArah[e.code]) {
            arahTekan[petaArah[e.code]] = true;
            e.preventDefault();
        } else if (e.code === 'KeyE' && promptTarget && !chipPrompt.hidden) {
            chipPrompt.click();
        }
    });
    document.addEventListener('keyup', function (e) {
        if (petaArah[e.code]) {
            arahTekan[petaArah[e.code]] = false;
        }
    });

    // Interior Balai Desa: tampil menutupi viewport, keadaan kamera dan
    // posisi karakter tidak berubah, jadi keluar berarti kembali persis
    // ke tempat semula.
    function bukaInterior() {
        interiorTerbuka = true;
        tutupPopover();
        chipPrompt.hidden = true;
        interior.hidden = false;
    }
    function tutupInterior() {
        interiorTerbuka = false;
        tutupPopover();
        interior.hidden = true;
    }
    document.getElementById('tombolKeluarInterior').addEventListener('click', tutupInterior);

    // Titik interaktif di dalam interior yang membuka popover
    // (Papan Pengumuman dan Bang Uju; loket dan arsip adalah tautan).
    var hotspotInterior = interior.querySelectorAll('.hotspot-interior[data-nama]');
    for (var hi = 0; hi < hotspotInterior.length; hi++) {
        (function (el) {
            el.addEventListener('click', function () {
                bukaPopover(el);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    bukaPopover(el);
                }
            });
        })(hotspotInterior[hi]);
    }

    // === Versi 4.1: tata letak sepenuh layar ===
    // Kartu pengantar bisa ditutup; keadaan terbukanya di ponsel dan
    // layar pendek diatur media query di stylesheet.
    var petaIntro = document.getElementById('petaIntro');
    document.getElementById('introTutup').addEventListener('click', function () {
        petaIntro.classList.add('tertutup');
    });

    // Panel Papan Gang meluncur dari kanan di dalam bingkai peta.
    var panelGang = document.getElementById('panelGang');
    var tombolPanelGang = document.getElementById('tombolPanelGang');
    var panelGangTerbuka = false;
    function aturPanelGang(buka) {
        panelGangTerbuka = buka;
        panelGang.classList.toggle('terbuka', buka);
        panelGang.setAttribute('aria-hidden', buka ? 'false' : 'true');
        tombolPanelGang.classList.toggle('aktif', buka);
        tombolPanelGang.setAttribute('aria-expanded', buka ? 'true' : 'false');
    }
    tombolPanelGang.addEventListener('click', function () {
        aturPanelGang(!panelGangTerbuka);
    });
    document.getElementById('panelGangTutup').addEventListener('click', function () {
        aturPanelGang(false);
    });

    // Tombol layar penuh memakai Fullscreen API pada bingkai peta.
    // Tombol disembunyikan kalau peramban tidak mendukungnya.
    var tombolLayarPenuh = document.getElementById('layarPenuh');
    if (!document.fullscreenEnabled || typeof viewport.requestFullscreen !== 'function') {
        tombolLayarPenuh.hidden = true;
    } else {
        tombolLayarPenuh.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                var janji = viewport.requestFullscreen();
                if (janji && typeof janji.catch === 'function') {
                    janji.catch(function () {
                        // Layar penuh ditolak peramban, peta tetap jalan seperti biasa.
                    });
                }
            }
        });
        // Masuk atau keluar layar penuh mengubah ukuran viewport;
        // hitung ulang skala dasar dan jepit kamera seperti pada resize.
        document.addEventListener('fullscreenchange', function () {
            hitungDasar();
            terapkan();
            tutupPopover();
        });
    }

    hitungDasar();
    fokusBalai();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
