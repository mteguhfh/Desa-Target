<?php
// Perilaku pop-up sambutan beranda (ADR-0005).
//
// Muncul sekali setelah kamera selesai terbang, lalu diingat di localStorage
// kunci "dt_sambutan". Warga yang sudah punya kartu (dt_nomor + dt_kunci)
// disapa namanya lewat sapa.php dan diarahkan ke klaim poin harian; tamu
// diarahkan ke halaman KTP.
//
// Kalau server sapa.php tidak bisa dihubungi atau kuncinya tidak cocok,
// pop-up memakai versi tamu. Tidak ada galat yang ditampilkan: menyapa
// dengan nama sifatnya tambahan, bukan syarat.
//
// Fungsi pembukanya dipasang di window.__desa supaya tautan "Lihat lagi di
// sambutan" di halaman Tentang (index.php?sambutan=1#sambutan) bisa membuka
// ulang pop-up tanpa menulis ulang logikanya.
//
// Berkas ini hanya mengeluarkan <script>.
//
// Variabel: $sambutanAwalan ('' dari akar, '../' dari peta3d/).
?>
<script>
(function () {
    var awalan = <?= json_encode($sambutanAwalan ?? '', JSON_UNESCAPED_SLASHES) ?>;
    var KUNCI_SUDAH = 'dt_sambutan';
    var KUNCI_NOMOR = 'dt_nomor';
    var KUNCI_KUNCI = 'dt_kunci';

    var kotak = document.getElementById('sambutan');
    if (!kotak) return;

    var judul = document.getElementById('sambutanJudul');
    var isi = document.getElementById('sambutanIsi');
    var warga = document.getElementById('sambutanWarga');
    var namaEl = document.getElementById('sambutanNama');
    var aksiTamu = document.getElementById('sambutanAksiTamu');
    var aksiWarga = document.getElementById('sambutanAksiWarga');
    var langkah = document.getElementById('sambutanLangkah');
    var kabar = document.getElementById('sambutanKabar');
    var tombolKlaim = document.getElementById('sambutanKlaim');
    var sudahTampil = false;
    var fokusSebelum = null;

    function baca(kunci) {
        try { return localStorage.getItem(kunci) || ''; } catch (e) { return ''; }
    }
    function catat(kunci, nilai) {
        try { localStorage.setItem(kunci, nilai); } catch (e) { /* localStorage diblokir: pop-up muncul lagi nanti */ }
    }

    function tampilKabar(teks) {
        if (!kabar) return;
        kabar.textContent = teks;
        kabar.hidden = teks === '';
    }

    function tutup() {
        if (!sudahTampil) return;
        sudahTampil = false;
        kotak.hidden = true;
        document.body.classList.remove('sambutan-terbuka');
        catat(KUNCI_SUDAH, '1');
        if (fokusSebelum && typeof fokusSebelum.focus === 'function') fokusSebelum.focus();
    }

    // data-panel pada tombol sah membuka isi panel bersayap dari luar tab
    // bar. Skrip tab bar belum tentu sudah ada; untuk uji dan tautan
    // cadangan, panel dibuka lewat window.__tabBar bila tersedia.
    function bukaPanel(nama) {
        if (window.__tabBar && typeof window.__tabBar.buka === 'function') {
            window.__tabBar.buka(nama, null);
        }
    }

    function pasangPembukaPanel(id, nama) {
        var tombol = document.getElementById(id);
        if (!tombol) return;
        tombol.addEventListener('click', function () {
            tutup();
            bukaPanel(nama);
        });
    }
    pasangPembukaPanel('sambutanJelajah', 'bangunan');
    pasangPembukaPanel('sambutanMisi', 'misi');

    document.getElementById('sambutanTutup').addEventListener('click', tutup);

    // Ketukan di luar kartu dan Esc juga menutup. Esc dihentikan supaya tidak
    // ikut menghentikan mode jalan di peta 3D di belakangnya.
    kotak.addEventListener('pointerdown', function (e) {
        if (e.target === kotak) tutup();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !kotak.hidden) {
            tutup();
            e.stopPropagation();
        }
    }, true);

    // Klaim poin harian. Masih memakai klaim.php yang sama dengan panel Misi,
    // jadi aturan sekali per hari tetap dipegang server.
    if (tombolKlaim) {
        tombolKlaim.addEventListener('click', function () {
            tombolKlaim.disabled = true;
            var data = new FormData();
            data.append('nomor', baca(KUNCI_NOMOR));
            data.append('kunci', baca(KUNCI_KUNCI));
            fetch(awalan + 'klaim.php', { method: 'POST', body: data })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, isi: j }; }); })
                .then(function (hasil) {
                    if (hasil.ok) {
                        tampilKabar('Poin harianmu masuk, saldo sekarang ' + (parseInt(hasil.isi.saldo, 10) || 0) + ' poin.');
                    } else {
                        // 409 = hari ini sudah klaim. Itu bukan kegagalan.
                        tampilKabar(hasil.isi.galat || 'Poin harian belum bisa diklaim.');
                    }
                    tombolKlaim.textContent = 'Lihat misi hari ini';
                    tombolKlaim.disabled = false;
                })
                .catch(function () {
                    tampilKabar('Balai desa sedang lambat. Coba lagi nanti lewat tab Misi.');
                    tombolKlaim.disabled = false;
                });
        });
    }

    function tampilkan() {
        if (sudahTampil) return;
        sudahTampil = true;
        fokusSebelum = document.activeElement;
        kotak.hidden = false;
        document.body.classList.add('sambutan-terbuka');
        var pertama = kotak.querySelector('.sambutan-aksi:not([hidden]) .sambutan-tombol.utama');
        if (pertama) pertama.focus();
    }

    // Warga yang sudah punya kartu disapa namanya sebelum pop-up tampil.
    function sapaWarga(nomor, kunci) {
        var alamat = awalan + 'sapa.php?nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci);
        return fetch(alamat, { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (hasil) {
                if (!hasil || !hasil.nama) return;
                judul.textContent = 'Halo, ' + hasil.nama + '!';
                isi.textContent = 'Selamat datang kembali di Desa Target.' +
                    (hasil.gang ? ' Gangmu, ' + hasil.gang + ', menunggu kabar darimu.' : '');
                if (namaEl) namaEl.textContent = hasil.nama;
                if (warga) warga.hidden = false;
                if (langkah) langkah.hidden = true;
                if (aksiTamu) aksiTamu.hidden = true;
                if (aksiWarga) aksiWarga.hidden = false;
            })
            .catch(function () { /* kartu tidak bisa dibaca: tetap versi tamu */ });
    }

    // Kamera 3D menyediakan window.__tungguIntro(fn). Tanpa itu (misalnya
    // kalau modul 3D gagal dimuat), pop-up menunggu DOM siap sendiri.
    function tungguIntro(fn) {
        if (typeof window.__tungguIntro === 'function') {
            window.__tungguIntro(fn);
        } else if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            window.setTimeout(fn, 0);
        }
    }

    // Disiapkan sekali: kalau kartu ada, namanya diambil dulu. Tautan
    // "Lihat lagi" di Tentang membuka paksa walau sudah pernah ditutup.
    function siapkan(laluTampil) {
        var nomor = baca(KUNCI_NOMOR);
        var kunci = baca(KUNCI_KUNCI);
        var adaKartu = /^DT-\d{4,9}$/.test(nomor) && kunci !== '';
        var janji = adaKartu ? sapaWarga(nomor, kunci) : Promise.resolve();
        janji.then(function () {
            if (laluTampil) tampilkan();
        });
    }

    // Demi uji otomatis dan tautan dari halaman lain.
    window.__desa = window.__desa || {};
    window.__desa.bukaSambutan = function () { tampilkan(); };
    window.__desa.sambutanDitutup = function () { return !sudahTampil && baca(KUNCI_SUDAH) === '1'; };

    var paksa = /(?:^|[?&])sambutan=1(?:&|$)/.test(window.location.search);
    if (!paksa && baca(KUNCI_SUDAH) !== '') {
        // Sudah pernah ditutup: siapkan saja sapaannya tanpa menampilkan,
        // supaya tombol "Lihat lagi" tidak perlu menunggu jaringan.
        siapkan(false);
        return;
    }
    tungguIntro(function () { siapkan(true); });
})();
</script>
