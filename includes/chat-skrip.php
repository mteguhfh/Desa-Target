<?php
// Chat antar gang di panel bersayap (Fitur 2).
//
// Aturan yang dijaga berkas ini:
//   - Tidak ada kolom teks. Yang bisa dikirim hanya tombol frase preset yang
//     datang dari server (CHAT_FRASE di config.php).
//   - Publik total. Tidak ada pilihan pesan privat di UI maupun endpoint.
//   - Nama dan gang diambil server dari kartu warga; peramban hanya mengirim
//     nomor dan kunci untuk membuktikan siapa yang menulis.
//   - Pengunjung tanpa kartu tetap bisa membaca semua kanal.
//
// Polling 5 detik memakai parameter sejak (id terakhir yang sudah tampil) dan
// berhenti saat panel ditutup atau tab peramban disembunyikan, mengikuti pola
// jeda animasi dunia 3D.
//
// Berkas ini hanya mengeluarkan markup penanda + <script>.
?>
<span id="chatPenanda" hidden
      data-endpoint="<?= e($awalanChat ?? '') ?>chat.php"
      data-poind="<?= e($awalanChat ?? '') ?>poin.php"
      data-kanal="<?= e(CHAT_KANAL_UMUM) ?>"
      data-gang="<?= e(implode('|', GANG_WARGA)) ?>"></span>
<script>
(function () {
    var penanda = document.getElementById('chatPenanda');
    var panelChat = document.querySelector('[data-isi="chat"]');
    if (!penanda || !panelChat) return;

    var endpoint = penanda.getAttribute('data-endpoint') || 'chat.php';
    var poinEndpoint = penanda.getAttribute('data-poind') || 'poin.php';
    var kanalUmum = penanda.getAttribute('data-kanal') || 'alun-alun';
    var daftarGang = (penanda.getAttribute('data-gang') || '').split('|').filter(Boolean);
    var JEDA_POLLING = 5000;

    var elPilih = document.getElementById('chatPilih');
    var elRiwayat = document.getElementById('chatRiwayat');
    var elKirim = document.getElementById('chatKirim');
    var elFrase = document.getElementById('chatFrase');
    var elKabar = document.getElementById('chatKabar');
    var elTamu = document.getElementById('chatTamu');

    var nomor = '';
    var kunci = '';
    var gangSaya = '';
    try {
        nomor = localStorage.getItem('dt_nomor') || '';
        kunci = localStorage.getItem('dt_kunci') || '';
    } catch (e) { nomor = ''; }
    var punyaKartu = /^DT-\d{4,9}$/.test(nomor) && kunci !== '';

    var kanalAktif = kanalUmum;
    var terakhir = 0;
    var pesanTampil = {};
    var pewaktu = null;
    var muatBerjalan = false;
    var frasaSiap = false;

    function potong(teks, batas) {
        teks = teks == null ? '' : String(teks);
        return teks.length > batas ? teks.slice(0, batas - 1) + '…' : teks;
    }

    function tampilKabar(teks, galat) {
        if (!elKabar) return;
        elKabar.textContent = teks || '';
        elKabar.hidden = !teks;
        elKabar.classList.toggle('galat', !!galat);
    }

    // --- Pemilih kanal -----------------------------------------------------
    function labelKanal(kanal) {
        return kanal === kanalUmum ? 'Alun-Alun' : kanal.replace(/^Gang /, '');
    }

    function buatTombolKanal() {
        if (!elPilih) return;
        elPilih.innerHTML = '';
        [kanalUmum].concat(daftarGang).forEach(function (kanal) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'chat-kanal';
            b.setAttribute('data-kanal', kanal);
            b.textContent = labelKanal(kanal);
            b.addEventListener('click', function () { gantiKanal(kanal); });
            elPilih.appendChild(b);
        });
        tandaiKanal();
    }

    function tandaiKanal() {
        if (!elPilih) return;
        Array.prototype.forEach.call(elPilih.querySelectorAll('.chat-kanal'), function (b) {
            var cocok = b.getAttribute('data-kanal') === kanalAktif;
            b.classList.toggle('aktif', cocok);
            b.setAttribute('aria-pressed', cocok ? 'true' : 'false');
        });
    }

    // Menulis hanya di kanal umum atau gang sendiri. Server memeriksa hal
    // yang sama, jadi aturan ini tidak bisa ditembus dengan permintaan buatan.
    function bolehTulis() {
        return punyaKartu && gangSaya !== '' &&
            (kanalAktif === kanalUmum || kanalAktif === gangSaya);
    }

    function perbaruiKirim() {
        if (elKirim) elKirim.hidden = !bolehTulis();
        if (elTamu) elTamu.hidden = punyaKartu;
    }

    // --- Riwayat pesan -----------------------------------------------------
    function tampilkan(kumpulan) {
        if (!Array.isArray(kumpulan) || !kumpulan.length || !elRiwayat) return;
        var kosong = elRiwayat.querySelector('.chat-kosong');
        if (kosong) kosong.hidden = true;
        kumpulan.forEach(function (m) {
            if (!m || typeof m.id !== 'number' || pesanTampil[m.id]) return;
            pesanTampil[m.id] = true;
            terakhir = Math.max(terakhir, m.id);

            var baris = document.createElement('div');
            baris.className = 'chat-pesan';
            baris.setAttribute('data-id', String(m.id));
            if (punyaKartu && m.nomor === nomor) baris.classList.add('milikku');

            var isi = document.createElement('span');
            isi.className = 'chat-pesan-isi';
            // Teks dari daftar preset server, ditulis sebagai teks biasa jadi
            // tidak ada HTML yang bisa disusupkan lewat nama warga.
            isi.textContent = potong(m.teks, 120);

            var meta = document.createElement('span');
            meta.className = 'chat-pesan-meta';
            meta.textContent = potong(m.nama, 40) + ' · ' + potong(m.gang, 40);

            baris.appendChild(isi);
            baris.appendChild(meta);
            elRiwayat.appendChild(baris);
        });
        elRiwayat.scrollTop = elRiwayat.scrollHeight;
    }

    function tampilKosong(teks) {
        if (!elRiwayat || elRiwayat.querySelector('.chat-pesan')) return;
        var kosong = elRiwayat.querySelector('.chat-kosong');
        if (!kosong) {
            kosong = document.createElement('p');
            kosong.className = 'chat-kosong';
            elRiwayat.appendChild(kosong);
        }
        kosong.textContent = teks;
        kosong.hidden = false;
    }

    // --- Tombol frase ------------------------------------------------------
    function buatTombolFrase(daftar) {
        if (frasaSiap || !elFrase || !Array.isArray(daftar)) return;
        frasaSiap = true;
        daftar.forEach(function (f) {
            if (!f || typeof f.kode !== 'string') return;
            var b = document.createElement('button');
            b.type = 'button';
            b.setAttribute('data-frase', f.kode);
            b.textContent = potong(f.teks, 120);
            b.addEventListener('click', function () { kirim(f.kode); });
            elFrase.appendChild(b);
        });
    }

    // --- Baca pesan --------------------------------------------------------
    function minta() {
        return fetch(endpoint + '?kanal=' + encodeURIComponent(kanalAktif) + '&sejak=' + terakhir, {
            headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.ok ? r.json() : null; });
    }

    function muat() {
        if (muatBerjalan) return Promise.resolve();
        muatBerjalan = true;
        var kanalDiminta = kanalAktif;
        return minta().then(function (data) {
            muatBerjalan = false;
            if (!data || data.kanal !== kanalDiminta) return null;
            if (Array.isArray(data.frase)) buatTombolFrase(data.frase);
            tampilkan(data.pesan);
            if (data.ada_lagi) muat(); // lebih dari 50 pesan baru
            return data;
        }).catch(function () {
            muatBerjalan = false;
            return null;
        });
    }

    function gantiKanal(kanal) {
        if (kanal === kanalAktif) return;
        kanalAktif = kanal;
        terakhir = 0;
        pesanTampil = {};
        if (elRiwayat) elRiwayat.innerHTML = '<p class="chat-kosong">Sedang memuat pesan…</p>';
        tandaiKanal();
        perbaruiKirim();
        tampilKabar('');
        muat().then(function (data) {
            if (data && data.kanal === kanalAktif && !data.pesan.length) {
                tampilKosong('Belum ada pesan di kanal ini. Jadilah yang pertama menyapa.');
            }
        });
    }

    // --- Kirim pesan -------------------------------------------------------
    function tombolFrase() {
        return elFrase ? Array.prototype.slice.call(elFrase.querySelectorAll('button')) : [];
    }

    function kirim(frase) {
        if (!bolehTulis()) {
            tampilKabar('Kanal ini hanya bisa dibaca. Menulis hanya di gangmu sendiri dan Alun-Alun.', true);
            return;
        }
        tombolFrase().forEach(function (b) { b.disabled = true; });
        var body = 'kanal=' + encodeURIComponent(kanalAktif) +
            '&frase=' + encodeURIComponent(frase) +
            '&nomor=' + encodeURIComponent(nomor) +
            '&kunci=' + encodeURIComponent(kunci);
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (hasil) {
            if (hasil.status === 201 && hasil.data.pesan) {
                tampilkan(hasil.data.pesan);
                tampilKabar('Terkirim.');
            } else if (hasil.status === 429) {
                tampilKabar(hasil.data.galat || 'Terlalu cepat. Tunggu beberapa detik lagi.', true);
            } else {
                tampilKabar(hasil.data.galat || 'Pesan belum bisa dikirim.', true);
                // Kunci lama atau kartu dicabut: kembalikan panel ke mode tamu.
                if (hasil.status === 403 && hasil.data.galat === 'Nomor atau kunci warga tidak cocok.') {
                    punyaKartu = false;
                }
            }
        }).catch(function () {
            tampilKabar('Jaringan sedang rewel. Coba lagi sebentar.', true);
        }).then(function () {
            tombolFrase().forEach(function (b) { b.disabled = false; });
            perbaruiKirim();
        });
    }

    // --- Polling -----------------------------------------------------------
    function panelTampil() {
        return !panelChat.hidden && !document.hidden;
    }

    function hentikanPolling() {
        if (pewaktu !== null) {
            window.clearInterval(pewaktu);
            pewaktu = null;
        }
    }

    function mulaiPolling() {
        if (pewaktu !== null || !panelTampil()) return;
        muat();
        pewaktu = window.setInterval(function () {
            if (document.hidden) { hentikanPolling(); return; }
            muat();
        }, JEDA_POLLING);
    }

    // Panel dibuka/ditutup oleh tab-bar-skrip.php. Berkas ini hanya memantau
    // atribut hidden pada isi panel, jadi tidak perlu diubah di sana.
    // Panel menyembunyikan isinya SESAAT setelah ditutup (menunggu animasi
    // 300ms selesai), jadi polling yang berhenti terlalu cepat akan minta
    // sekali lagi tanpa panel terlihat. Diperiksa berkala juga: begitu panel
    // benar-benar tersembunyi, polling berhenti.
    function selaraskanPolling() {
        var panelInduk = document.getElementById('panelBersayap');
        if (panelInduk && panelInduk.hidden) { hentikanPolling(); return; }
        if (panelTampil()) mulaiPolling();
        else if (document.hidden) hentikanPolling();
    }
    if (window.MutationObserver) {
        new MutationObserver(selaraskanPolling)
            .observe(panelChat, { attributes: true, attributeFilter: ['hidden'] });
    }
    window.setInterval(selaraskanPolling, 1000);

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) hentikanPolling();
        else if (!panelChat.hidden) mulaiPolling();
    });

    // --- Mulai -------------------------------------------------------------
    // Identitas: nama gang dari kartu lewat poin.php (hanya membaca). Kalau
    // kuncinya tidak cocok, panel tetap tamu dan tombol kirim tidak muncul.
    buatTombolKanal();
    perbaruiKirim();

    var siap = Promise.resolve();
    if (punyaKartu) {
        siap = fetch(poinEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: 'nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci)
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (data && typeof data.gang === 'string' && data.gang !== '') gangSaya = data.gang;
            else punyaKartu = false;
        }).catch(function () { /* biarkan mode tamu, kirim ditolak server */ });
    }

    siap.then(function () {
        perbaruiKirim();
        muat().then(function (data) {
            if (data && !data.pesan.length) {
                tampilKosong('Belum ada pesan di kanal ini. Jadilah yang pertama menyapa.');
            }
            if (panelTampil()) mulaiPolling();
        });
    });

    // Untuk uji otomatis.
    window.__chatDesa = {
        kanal: function () { return kanalAktif; },
        terakhir: function () { return terakhir; },
        pollingJalan: function () { return pewaktu !== null; }
    };
})();
</script>
