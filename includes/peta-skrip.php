<?php
// Skrip peta interaktif (versi 4.x). Dimuat oleh peta.php dari dalam
// blok <script> halaman, setelah includes/peta-data.php dan peta-markup.php.
// Variabel PHP yang tersedia: $bangunan, $zonaUntukJs, $ujuX, $ujuY,
// $totalKavling, $terbaruId, dan konstanta POSISI_KAVLING.
?>
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
    var K_MAX = 2.5;
    var FOKUS = { x: <?= $fokusX ?>, y: <?= $fokusY ?> }; // Balai Desa
    var k = 1;        // zoom saat ini, relatif terhadap skala cover (baseS)
    var tx = 0;       // geser kamera dalam piksel layar
    var ty = 0;
    var baseS = 1;    // piksel layar per satuan dunia pada zoom 1 (skala cover)
    var containS = 1; // skala contain: seluruh dunia pas di dalam viewport
    // Batas zoom bawah bersifat dinamis: rasio contain terhadap cover.
    // Di layar lebar tapi pendek, kMin < 1 mengizinkan zoom keluar sampai
    // seluruh desa terlihat. Dulu batasnya kaku di 1 (skala cover), jadi
    // zona gang di tepi atas tidak pernah bisa terlihat dan, karena dunia
    // menutup lebar viewport persis, geser horizontal terkunci di 0 piksel
    // sehingga peta terasa mati digeser.
    var kMin = 1;

    function hitungDasar() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        baseS = Math.max(w / DUNIA_W, h / DUNIA_H);
        containS = Math.min(w / DUNIA_W, h / DUNIA_H);
        kMin = containS / baseS;
        if (k < kMin) {
            k = kMin;
        }
        dunia.style.width = (DUNIA_W * baseS) + 'px';
        dunia.style.height = (DUNIA_H * baseS) + 'px';
    }

    function jepit() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        var dw = DUNIA_W * baseS * k;
        var dh = DUNIA_H * baseS * k;
        // Sumbu yang dunianya lebih kecil dari viewport dipusatkan,
        // bukan dijepit ke rentang terbalik (yang hasilnya kacau);
        // sumbu yang lebih besar dijepit seperti biasa.
        if (dw <= w) {
            tx = (w - dw) / 2;
        } else {
            tx = Math.min(0, Math.max(w - dw, tx));
        }
        if (dh <= h) {
            ty = (h - dh) / 2;
        } else {
            ty = Math.min(0, Math.max(h - dh, ty));
        }
    }

    function terapkan() {
        jepit();
        dunia.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + k + ')';
    }

    // Zoom ke titik layar (px, py) yang relatif terhadap viewport,
    // titik dunia di bawahnya dijaga tetap di tempat yang sama.
    function zoomKe(kBaru, px, py) {
        kBaru = Math.min(K_MAX, Math.max(kMin, kBaru));
        var lx = (px - tx) / k;
        var ly = (py - ty) / k;
        k = kBaru;
        tx = px - lx * k;
        ty = py - ly * k;
        terapkan();
    }

    // Bingkai seluruh desa: zoom paling luar dan dunia dipusatkan,
    // jadi pengunjung pertama selalu melihat semua kavling dan zona gang.
    function lihatSeluruhDesa() {
        k = kMin;
        tx = (viewport.clientWidth - DUNIA_W * baseS * k) / 2;
        ty = (viewport.clientHeight - DUNIA_H * baseS * k) / 2;
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
        // Sistem wara-wiri versi 4.3 membaca variabel ini: elemen yang
        // popover-nya sedang terbuka berhenti bergerak sampai ditutup.
        elemenPopover = null;
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
        // Versi 4.3: catat elemen pemilik popover supaya wara-wiri dan
        // patroli menjeda elemen itu selama popover-nya terbuka. Warga
        // yang ditap juga menyapa balik: pantulan kecil dan label
        // namanya membesar sesaat.
        elemenPopover = el;
        if (warga) {
            el.classList.add('meloncat', 'labelnaik');
            setTimeout(function () {
                el.classList.remove('meloncat', 'labelnaik');
            }, 950);
        }
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
        // Selama jari atau mouse menahan peta, wara-wiri versi 4.3
        // menjeda supaya tap dan seret tidak berlomba dengan gerakan warga.
        seretAmbien = true;
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
            k = Math.min(K_MAX, Math.max(kMin, cubit.k * d / cubit.d));
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
            seretAmbien = false;
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
        lihatSeluruhDesa();
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

    // Satu langkah pasti sebesar kurang lebih 26 satuan dunia, dipakai
    // ketukan singkat pada D-pad. Ketukan yang lebih pendek dari satu
    // frame animasi tidak sempat terbaca putaran jalan (yang bergerak
    // hanya selama tombol tertahan), jadi ketukan dulu terasa tidak
    // menggerakkan karakter sama sekali. Batas dan rintangannya sama
    // dengan langkah berkelanjutan di langkahKarakter.
    function selangkah(ar) {
        var vx = ar === 'kanan' ? 1 : (ar === 'kiri' ? -1 : 0);
        var vy = ar === 'bawah' ? 1 : (ar === 'atas' ? -1 : 0);
        var nx = Math.min(1950, Math.max(50, karakter.x + vx * 26));
        var ny = Math.min(1290, Math.max(95, karakter.y + vy * 26));
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
        gambarKarakter(performance.now());
        perbaruiPrompt();
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
        // Karakter adalah elemen SVG <g>: properti .hidden tidak selalu
        // terhubung ke atributnya pada elemen SVG di semua peramban,
        // jadi atributnya dicabut dan dipasang secara eksplisit. Dengan
        // penetapan properti saja, atribut hidden bisa tertinggal dan
        // aturan [hidden] di stylesheet menahan karakter tetap tidak
        // terlihat walau keadaan mode jalan sudah benar.
        if (aktif) {
            karakterEl.removeAttribute('hidden');
        } else {
            karakterEl.setAttribute('hidden', '');
        }
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

    // D-pad di layar: ketuk untuk melangkah, tekan dan tahan untuk berjalan terus.
    var tombolDpad = dpad.querySelectorAll('button[data-arah]');
    for (var td = 0; td < tombolDpad.length; td++) {
        (function (btn) {
            btn.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                // Ketukan singkat langsung melangkah satu langkah;
                // menahan tombol tetap berjalan terus lewat arahTekan.
                selangkah(btn.dataset.arah);
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

    // === Versi 4.3: warga wara-wiri dan patroli Bang Uju ===
    // Desa terasa hidup karena penghuninya bergerak sendiri. Setiap
    // avatar warga adalah satu agen kecil: ia berdiam di titik berdirinya,
    // sesekali memilih titik tujuan lain di dalam zona gang-nya sendiri
    // (tabel ZONA_WARA dihitung PHP dari petak zona dengan margin tepi),
    // berjalan pelan ke sana, lalu berdiam lagi. Waktu tunggu, kecepatan,
    // dan titik tujuan semuanya acak per agen, jadi desa tidak pernah
    // bergerak serempak. Jalur lurus menuju tujuan ditolak kalau menembus
    // tapak kavling (RINTANGAN_WARA), dan paling banyak BATAS_BERGERAK
    // warga berjalan pada saat yang sama supaya peta tetap tenang.
    // Bang Uju ikut bergerak sebagai agen patroli istimewa: bukan titik
    // acak, melainkan putaran titik kunjung mengelilingi alun-alun Balai
    // Desa, seperti Kepala Desa yang sedang ronda. Semua titik kunjung
    // dan segmen antar titik berada di permukaan alun-alun dan apron
    // selatannya, di depan fasad Balai Desa (tapak bangunannya dijaga
    // tetap di utara garis y 700 untuk rentang x bangunan), tidak pernah
    // masuk zona gang warga dan tidak turun ke jalur jalan utama yang
    // berada jauh di selatan alun-alun.
    // Satu putaran requestAnimationFrame khusus kehidupan ambien ini,
    // terpisah dari putaran mode Keliling Desa. Putarannya menjeda
    // (posisi membeku, tanpa lompatan waktu) selama pengunjung menyeret
    // peta (seretAmbien), selama mode Keliling Desa aktif, selama
    // interior terbuka, dan selama tab tidak terlihat. Elemen yang
    // popover-nya sedang terbuka (elemenPopover, dicatat bukaPopover)
    // juga berhenti bergerak sampai popover ditutup. Pengunjung dengan
    // prefers-reduced-motion tidak menjalankan putaran ini sama sekali;
    // bob diam warga pun dimatikan dari sisi CSS.
    var elemenPopover = null;
    var seretAmbien = false;
    var ZONA_WARA = {
        <?php foreach ($zonaUntukJs as $gangWara => $zWara): ?>
        <?= json_encode($gangWara, JSON_UNESCAPED_UNICODE) ?>: { x: <?= $zWara['x'] ?>, y: <?= $zWara['y'] ?>, w: <?= $zWara['w'] ?>, h: <?= $zWara['h'] ?> },
        <?php endforeach; ?>
    };
    var RINTANGAN_WARA = [
        <?php foreach (POSISI_KAVLING as $kavWara): ?>
        [<?= (int) $kavWara[0] - 105 ?>, <?= (int) $kavWara[1] - 130 ?>, <?= (int) $kavWara[0] + 105 ?>, <?= (int) $kavWara[1] + 6 ?>],
        <?php endforeach; ?>
    ];
    var BATAS_BERGERAK = 12;
    // Titik kunjung patroli Bang Uju (koordinat dunia): mulai dari titik
    // berdirinya di timur alun-alun, ke sisi barat, ke apron selatan,
    // ke sisi timur laut, lalu pulang. Batas kawasan patroli untuk
    // pengujian: x 830 sampai 1260, y 695 sampai 820.
    var TITIK_PATROLI = [[<?= $ujuX ?>, <?= $ujuY ?>], [860, 720], [950, 795], [1180, 712]];
    var BATAS_PATROLI = { x1: 830, y1: 695, x2: 1260, y2: 820 };
    var TAPAK_BALAI = [925, 585, 1135, 700];

    function dalamRintanganWara(x, y) {
        for (var i = 0; i < RINTANGAN_WARA.length; i++) {
            var r = RINTANGAN_WARA[i];
            if (x > r[0] && x < r[2] && y > r[1] && y < r[3]) {
                return true;
            }
        }
        return false;
    }

    function titikAcakZona(zona) {
        return {
            x: zona.x + Math.random() * zona.w,
            y: zona.y + Math.random() * zona.h
        };
    }

    // Pilih titik tujuan untuk satu agen warga: cukup jauh untuk terasa
    // sebagai jalan-jalan (bukan geser di tempat), cukup dekat untuk
    // tetap jadi keliling lingkungan sendiri, dan jalur lurusnya tidak
    // menembus tapak kavling mana pun (diuji di beberapa titik segmen).
    function pilihTargetWara(agen) {
        for (var coba = 0; coba < 8; coba++) {
            var p = titikAcakZona(agen.zona);
            var d = Math.hypot(p.x - agen.x, p.y - agen.y);
            if (d < 34 || d > 200) {
                continue;
            }
            var aman = true;
            for (var s = 1; s < 6; s++) {
                if (dalamRintanganWara(agen.x + (p.x - agen.x) * s / 6, agen.y + (p.y - agen.y) * s / 6)) {
                    aman = false;
                    break;
                }
            }
            if (aman) {
                return p;
            }
        }
        return null;
    }

    var agenWarga = [];
    var elemenWarga = document.querySelectorAll('.warga-svg');
    for (var aw = 0; aw < elemenWarga.length; aw++) {
        (function (el) {
            var zona = ZONA_WARA[el.dataset.zona];
            if (!zona) {
                return;
            }
            agenWarga.push({
                el: el,
                zona: zona,
                x: parseFloat(el.dataset.rumahX),
                y: parseFloat(el.dataset.rumahY),
                tx: 0,
                ty: 0,
                jalan: false,
                tunggu: 0.5 + Math.random() * 7,
                kecepatan: 20 + Math.random() * 16
            });
        })(elemenWarga[aw]);
    }

    // Agen patroli Bang Uju: posisi awal dibaca dari transform markup
    // grupnya, sama persis dengan titik berdiri yang ditulis PHP.
    var patroli = null;
    var elUju = document.getElementById('bangUju');
    if (elUju) {
        var mUju = /translate\(([-\d.]+)[ ,]+([-\d.]+)\)/.exec(elUju.getAttribute('transform') || '');
        patroli = {
            el: elUju,
            x: mUju ? parseFloat(mUju[1]) : TITIK_PATROLI[0][0],
            y: mUju ? parseFloat(mUju[2]) : TITIK_PATROLI[0][1],
            tujuan: 1,
            jalan: false,
            tunggu: 3,
            kecepatan: 26
        };
    }

    function jumlahBergerak() {
        var n = 0;
        for (var i = 0; i < agenWarga.length; i++) {
            if (agenWarga[i].jalan) {
                n++;
            }
        }
        return n;
    }

    function perbaruiWarga(agen, dt) {
        if (elemenPopover === agen.el) {
            return;
        }
        if (!agen.jalan) {
            agen.tunggu -= dt;
            if (agen.tunggu > 0) {
                return;
            }
            if (jumlahBergerak() >= BATAS_BERGERAK) {
                agen.tunggu = 0.8;
                return;
            }
            var p = pilihTargetWara(agen);
            if (!p) {
                agen.tunggu = 1.5;
                return;
            }
            agen.tx = p.x;
            agen.ty = p.y;
            agen.jalan = true;
        }
        var dx = agen.tx - agen.x;
        var dy = agen.ty - agen.y;
        var d = Math.hypot(dx, dy);
        var langkah = agen.kecepatan * dt;
        if (d <= langkah) {
            agen.x = agen.tx;
            agen.y = agen.ty;
            agen.jalan = false;
            agen.tunggu = 2.5 + Math.random() * 6.5;
        } else {
            agen.x += dx / d * langkah;
            agen.y += dy / d * langkah;
        }
        agen.el.setAttribute('transform', 'translate(' + agen.x.toFixed(1) + ' ' + agen.y.toFixed(1) + ')');
    }

    function perbaruiPatroli(dt) {
        if (!patroli || elemenPopover === patroli.el) {
            return;
        }
        if (!patroli.jalan) {
            patroli.tunggu -= dt;
            if (patroli.tunggu > 0) {
                return;
            }
            patroli.jalan = true;
        }
        var target = TITIK_PATROLI[patroli.tujuan];
        var dx = target[0] - patroli.x;
        var dy = target[1] - patroli.y;
        var d = Math.hypot(dx, dy);
        var langkah = patroli.kecepatan * dt;
        if (d <= langkah) {
            patroli.x = target[0];
            patroli.y = target[1];
            patroli.jalan = false;
            patroli.tunggu = 3.5 + Math.random() * 3;
            patroli.tujuan = (patroli.tujuan + 1) % TITIK_PATROLI.length;
        } else {
            patroli.x += dx / d * langkah;
            patroli.y += dy / d * langkah;
        }
        patroli.el.setAttribute('transform', 'translate(' + patroli.x.toFixed(1) + ' ' + patroli.y.toFixed(1) + ')');
    }

    var rafAmbien = null;
    var capAmbien = 0;
    function putaranAmbien(waktu) {
        rafAmbien = requestAnimationFrame(putaranAmbien);
        var dt = Math.min(0.05, capAmbien ? (waktu - capAmbien) / 1000 : 0.016);
        capAmbien = waktu;
        if (jalanAktif || interiorTerbuka || seretAmbien || document.hidden) {
            return;
        }
        for (var i = 0; i < agenWarga.length; i++) {
            perbaruiWarga(agenWarga[i], dt);
        }
        perbaruiPatroli(dt);
    }
    if (!gerakDikurangi && (agenWarga.length > 0 || patroli)) {
        rafAmbien = requestAnimationFrame(putaranAmbien);
    }
    // === Akhir blok wara-wiri versi 4.3 ===

    hitungDasar();
    lihatSeluruhDesa();

    // --- Sorot bangunan dari daftar di panel (tab Bangunan).
    // Mengetuk nama bangunan di panel menggulir dan memperbesar peta ke
    // bangunan itu, lalu membuka kartu detailnya. Dipakai bersama dunia 3D.
    var cincinSorot = null;
    function sorotKavling(kavling) {
        var semuaBangunan = document.querySelectorAll('.bangunan-svg');
        var tujuan = null;
        for (var i = 0; i < semuaBangunan.length; i++) {
            if (Number(semuaBangunan[i].dataset.kavling) === kavling) { tujuan = semuaBangunan[i]; break; }
        }
        if (!tujuan) return false;

        // Kamera dunia: pusatkan pada bangunan lalu perbesar.
        var bx = parseFloat(tujuan.dataset.x);
        var by = parseFloat(tujuan.dataset.y);
        k = Math.min(K_MAX, Math.max(kMin, 1.6));
        tx = viewport.clientWidth / 2 - bx * baseS * k;
        ty = viewport.clientHeight / 2 - by * baseS * k;
        terapkan();

        if (cincinSorot) cincinSorot.remove();
        cincinSorot = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        cincinSorot.setAttribute('class', 'cincin-sorot');
        cincinSorot.setAttribute('cx', bx);
        cincinSorot.setAttribute('cy', by + 6);
        cincinSorot.setAttribute('rx', 70);
        cincinSorot.setAttribute('ry', 26);
        var lapis = document.querySelector('#petaDunia .bangunan');
        (lapis || document.querySelector('#petaDunia')).appendChild(cincinSorot);

        bukaPopover(tujuan);
        document.querySelectorAll('.daftar-bangunan-tombol').forEach(function (b) {
            b.classList.toggle('aktif', Number(b.getAttribute('data-kavling')) === kavling);
        });
        return true;
    }
    document.addEventListener('click', function (e) {
        var tombol = e.target.closest ? e.target.closest('.daftar-bangunan-tombol') : null;
        if (!tombol) return;
        var kavling = tombol.getAttribute('data-kavling');
        if (kavling === '') return;
        sorotKavling(Number(kavling));
    });
    window.__peta2d = { sorotKavling: sorotKavling };
})();
</script>
