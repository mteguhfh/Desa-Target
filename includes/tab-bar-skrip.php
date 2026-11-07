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
        'aku': 'Aku'
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

    // Di halaman dengan kanvas 3D, peramban hanya menggambar sekitar 3 bingkai
    // per detik (perenderan WebGL memakan seluruh waktu bingkai), sehingga
    // animasi 300ms bisa tertinggal hampir satu detik. Animasi hanya dipakai
    // bila halaman memang sanggup menggambarnya; kalau tidak, panel muncul
    // seketika. Dengan begitu ketukan selalu terasa langsung dijawab.
    var ada3D = !!document.getElementById('panggung');

    function buka(nama, tab) {
        if (!JUDUL[nama]) return;
        isiUntuk(nama);
        judul.textContent = JUDUL[nama];
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        if (ada3D) panel.classList.add('segera');
        // Paksa peramban menghitung tata letak sekali, supaya transisi
        // transform berjalan dari posisi tertutup.
        void panel.offsetHeight;
        panel.classList.add('terbuka');
        tandaiTab(nama);
        tabTerakhir = tab || null;
    }

    function tutup(kembalikanFokus) {
        if (!panel.classList.contains('terbuka') && panel.hidden) return;
        panel.classList.remove('terbuka');
        panel.setAttribute('aria-hidden', 'true');
        tandaiTab(null);
        window.setTimeout(function () {
            if (!panel.classList.contains('terbuka')) panel.hidden = true;
        }, 300);
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
