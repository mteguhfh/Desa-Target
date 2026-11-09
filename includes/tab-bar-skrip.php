<?php
// Perilaku tab bar dan panel bersayap.
//
// Dipisah dari markup supaya bisa dipakai peta 3D maupun 2D tanpa menyalin
// skrip. Berkas ini hanya mengeluarkan <script>; tidak menyentuh PHP lain.
//
// Cara kerja:
//   - Tab bertanda data-panel membuka panel dan menyalakan isi yang cocok.
//   - Mengetuk tab yang sama saat panel terbuka menutup panel.
//   - Esc, tombol tutup, atau ketukan di luar panel juga menutup.
//   - Fokus kembali ke tab yang tadi diketuk setelah panel ditutup.
//
// Judul dan isi panel dipilih lewat data-isi="..." di includes/panel-bersayap.php.
?>
<script>
(function () {
    var panel = document.getElementById('panelBersayap');
    var tabBar = document.getElementById('tabBar');
    if (!panel || !tabBar) return;

    var judul = document.getElementById('panelBersayapJudul');
    var tombolTutup = document.getElementById('panelBersayapTutup');
    var semuaIsi = panel.querySelectorAll('[data-isi]');
    var semuaTab = tabBar.querySelectorAll('[data-panel]');
    var tabTerakhir = null;

    var JUDUL = {
        'warga': 'Cari Warga',
        'misi': 'Misi Desa',
        'papan-gang': 'Papan Gang',
        'bangunan': 'Bangunan Desa',
        'chat': 'Chat Desa',
        'pasar': 'Pasar Desa',
        'ruko': 'Ruko Wargamu',
        'aku': 'Aku',
        'belajar': 'Pusat Pembelajaran'
    };

    function isiUntuk(nama) {
        for (var i = 0; i < semuaIsi.length; i++) {
            semuaIsi[i].hidden = semuaIsi[i].getAttribute('data-isi') !== nama;
        }
    }

    function tandaiTab(nama) {
        for (var i = 0; i < semuaTab.length; i++) {
            var cocok = nama !== null && semuaTab[i].getAttribute('data-panel') === nama;
            semuaTab[i].classList.toggle('aktif', cocok);
            semuaTab[i].setAttribute('aria-expanded', cocok ? 'true' : 'false');
        }
    }

    var tutupTimer = null;

    function buka(nama, tab) {
        if (!JUDUL[nama]) return;
        window.clearTimeout(tutupTimer);
        var dariTersembunyi = panel.hidden;
        isiUntuk(nama);
        judul.textContent = JUDUL[nama];
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        panel.inert = false;
        if (dariTersembunyi) {
            // Rekam posisi tertutup sebelum transisi dimulai di bingkai berikut.
            void panel.offsetHeight;
        }
        panel.classList.add('terbuka');
        tandaiTab(nama);
        tabTerakhir = tab || null;
    }

    // Isi panel Misi yang bisa dituju langsung, misalnya ladang panen.
    // Dulu mini game hanya bisa dicapai lewat chip Poin, yang tersembunyi
    // untuk tamu tanpa kartu, jadi tamu tidak punya jalan masuk.
    var PANEL_DARI_ISI = { 'panenGame': 'misi' };

    function bukaIsi(idTujuan) {
        var tujuan = document.getElementById(idTujuan);
        if (!tujuan) return;
        buka(PANEL_DARI_ISI[idTujuan], null);
        // Isi panel Misi sangat panjang, jadi ladang digulirkan di dalam
        // wadah bergulirnya sendiri. Menunggu beberapa bingkai saja tidak
        // cukup: di peta 3D, isi Misi baru punya tinggi setelah daftar misi
        // selesai digambar (~0,7 detik), dan guliran yang lebih awal dibuang
        // peramban. Jadi tunggu sampai ada isi yang benar-benar bisa digulir,
        // dengan batas waktu supaya tidak menunggu selamanya.
        var wadah = panel.querySelector('.panel-bersayap-isi');
        var batas = performance.now() + 2000;
        (function coba() {
            if (!wadah) return;
            if (wadah.scrollHeight > wadah.clientHeight + 4 || performance.now() > batas) {
                wadah.scrollTop = Math.max(0, tujuan.offsetTop - 12);
                if (!tujuan.hasAttribute('tabindex')) tujuan.setAttribute('tabindex', '-1');
                tujuan.focus({ preventScroll: true });
                return;
            }
            window.requestAnimationFrame(coba);
        })();
    }

    function tutup(kembalikanFokus) {
        if (!panel.classList.contains('terbuka') && panel.hidden) return;
        panel.classList.remove('terbuka');
        panel.inert = true;
        panel.setAttribute('aria-hidden', 'true');
        tandaiTab(null);
        window.clearTimeout(tutupTimer);
        tutupTimer = window.setTimeout(function () {
            if (!panel.classList.contains('terbuka')) panel.hidden = true;
        }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 260);
        if (kembalikanFokus && tabTerakhir) tabTerakhir.focus();
        tabTerakhir = null;
    }

    for (var i = 0; i < semuaTab.length; i++) {
        semuaTab[i].addEventListener('click', function () {
            var nama = this.getAttribute('data-panel');
            var sedangAktif = panel.classList.contains('terbuka') && this.classList.contains('aktif');
            if (sedangAktif) tutup(true);
            else buka(nama, this);
        });
    }

    if (tombolTutup) tombolTutup.addEventListener('click', function () { tutup(true); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('terbuka')) {
            tutup(true);
            // Jangan biarkan Esc ikut menghentikan mode jalan di peta 3D.
            e.stopPropagation();
        }
    }, true);

    // Ketukan di luar panel dan di luar tab bar menutup panel.
    document.addEventListener('pointerdown', function (e) {
        if (!panel.classList.contains('terbuka')) return;
        if (panel.contains(e.target) || tabBar.contains(e.target)) return;
        tutup(false);
    });

    // Tombol di luar tab bar yang membuka isi panel, misalnya Chat di bilah
    // atas. Berperilaku sama dengan tab: ketuk sekali membuka, ketuk lagi
    // saat isi yang sama sedang terbuka menutup panel.
    function pasangPintasan(id, nama) {
        var tombol = document.getElementById(id);
        if (!tombol || !JUDUL[nama]) return;
        tombol.addEventListener('click', function (e) {
            e.preventDefault();
            var sedangAktif = panel.classList.contains('terbuka') &&
                !!panel.querySelector('[data-isi="' + nama + '"]:not([hidden])');
            if (sedangAktif) tutup(false);
            else buka(nama, tombol);
        });
    }
    pasangPintasan('tombolChat', 'chat');
    pasangPintasan('tombolChat2d', 'chat');
    // Chip suasana di peta 3D juga membuka panel Pasar (Fitur 4).
    pasangPintasan('chipSuasana', 'pasar');
    // Chip Belajar di peta 3D membuka panel Pusat Pembelajaran (Fitur 5).
    pasangPintasan('chipBelajar', 'belajar');
    // Tombol ruko bundar di kontrol 3D membuka panel Ruko yang sama
    // (ADR-0006), jadi pengunjung tidak meninggalkan dunia 3D.
    pasangPintasan('tombolRuko3d', 'ruko');
    // Tombol "Main panen padi" di isi panel (Warga dan seterusnya) membuka
    // panel Misi dan langsung menggulir ke ladang.
    var pintuPanen = document.getElementById('pintuPanen');
    if (pintuPanen) {
        pintuPanen.addEventListener('click', function () {
            bukaIsi(pintuPanen.getAttribute('data-buka-isi'));
        });
    }
    // Panen dicatat atas nama warga berkartu (main.php menolak tamu), jadi
    // janji kartu pintu ikut menyesuaikan: tamu diarahkan bikin KTP dulu.
    if (pintuPanen) {
        var pintuKeterangan = pintuPanen.querySelector('.pintu-panen-teks span');
        var perbaruiPintu = function () {
            var warga;
            try { warga = localStorage.getItem('dt_nomor'); } catch (err) { warga = null; }
            if (pintuKeterangan) {
                pintuKeterangan.textContent = warga
                    ? 'Ketuk saat padi di tengah, kumpulkan poin warga.'
                    : 'Butuh KTP Warga. Bikin dulu, lalu panen dan dapat 5 poin.';
            }
        };
        perbaruiPintu();
        // Kartu bisa dibuat di panel Aku tanpa memuat ulang halaman.
        window.addEventListener('storage', perbaruiPintu);
        document.addEventListener('dt-kartu-siap', perbaruiPintu);
    }

    // Pintasan untuk peta 3D: tombol "Papan Gang" lama kini membuka panel
    // yang sama, bukan panel terpisah. Dijalankan setelah halaman siap.
    var tombolPapanLama = document.getElementById('tombolPapan');
    if (tombolPapanLama) {
        tombolPapanLama.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            buka('papan-gang', null);
        }, true);
    }

    // Untuk uji otomatis.
    window.__tabBar = { buka: buka, tutup: tutup, panel: panel };
})();
</script>
