<?php
// Panel Ruko di tab Ruko (ADR-0006).
//
// Sebelumnya tab Ruko langsung membuka halaman ruko.php, jadi pengunjung
// meninggalkan desa 3D hanya untuk melihat rukonya sendiri. Sekarang isinya
// dibuka di panel bersayap, seperti tab Bangunan dan Papan Gang.
//
// Tiga keadaan, semuanya ditentukan dari kartu warga di peramban:
//   - tamu (belum punya dt_nomor): ajakan bikin KTP
//   - sudah punya kartu, ruko belum ada: ajakan buka ruko (ke ruko.php)
//   - punya ruko: nama, gang, cerita, tautan jualan
//
// Data dibaca dari ruko.php (GET, nomor + kunci) yang sama dengan halaman
// ruko, jadi panel dan halaman tidak mungkin menampilkan ruko berbeda.
//
// Berkas ini hanya mengeluarkan <script>.
//
// Variabel: $rukoPanelAwalan ('' dari akar, '../' dari peta3d/).
?>
<script>
(function () {
    var awalan = <?= json_encode($rukoPanelAwalan ?? '', JSON_UNESCAPED_SLASHES) ?>;
    var KUNCI_NOMOR = 'dt_nomor';
    var KUNCI_KUNCI = 'dt_kunci';

    var tamu = document.getElementById('rukoPanelTamu');
    var muat = document.getElementById('rukoPanelMuat');
    var milik = document.getElementById('rukoPanelMilik');
    var kosong = document.getElementById('rukoPanelKosong');
    if (!tamu || !muat || !milik || !kosong) return;

    var elNama = document.getElementById('rukoPanelNama');
    var elGang = document.getElementById('rukoPanelGang');
    var elCerita = document.getElementById('rukoPanelCerita');
    var elKabar = document.getElementById('rukoPanelKabar');
    var elKunjungi = document.getElementById('rukoPanelKunjungi');
    var elBuka = document.getElementById('rukoPanelBuka');
    var elDaftar = document.getElementById('rukoPanelDaftar');
    var elGrid = document.getElementById('rukoPanelGrid');

    function baca(kunci) {
        try { return localStorage.getItem(kunci) || ''; } catch (e) { return ''; }
    }

    function tampilKeadaan(nama) {
        tamu.hidden = nama !== 'tamu';
        muat.hidden = nama !== 'muat';
        milik.hidden = nama !== 'milik';
        kosong.hidden = nama !== 'kosong';
    }

    function setKabar(teks) {
        if (!elKabar) return;
        elKabar.textContent = teks;
        elKabar.hidden = teks === '';
    }

    // Ruko semua warga, dipakai untuk daftar 2 kolom di bagian bawah panel.
    // Sumbernya peta3d/data-desa.php (jalur data tunggal, ADR-0004) yang
    // sudah mengirim semua bangunan aktif beserta gambar dan tautannya.
    function gambarDaftarRuko(daftar, nomorSaya) {
        if (!elGrid || !elDaftar) return;
        elGrid.innerHTML = '';
        if (!Array.isArray(daftar) || daftar.length === 0) {
            elDaftar.hidden = true;
            return;
        }
        daftar.forEach(function (r) {
            var li = document.createElement('li');
            li.className = 'ruko-kartu' + (nomorSaya && r.nomor === nomorSaya ? ' punyaku' : '');
            li.dataset.kategori = r.kategori || 'lainnya';

            if (r.gambar) {
                var img = document.createElement('img');
                img.className = 'gambar-usaha ruko-kartu-gambar';
                img.src = r.gambar;
                img.alt = 'Ruko ' + (r.nama || 'warga');
                img.loading = 'lazy';
                img.width = 640;
                img.height = 480;
                li.appendChild(img);
            } else {
                var kosong = document.createElement('span');
                kosong.className = 'gambar-usaha-kosong';
                kosong.textContent = r.gang || 'Ruko warga';
                li.appendChild(kosong);
            }

            var nama = document.createElement('span');
            nama.className = 'ruko-kartu-nama';
            nama.textContent = r.nama || 'Ruko warga';
            li.appendChild(nama);

            var meta = document.createElement('span');
            meta.className = 'ruko-kartu-meta';
            meta.textContent = [r.diminta_oleh, r.gang].filter(Boolean).join(' · ');
            meta.textContent += ' · ' + (r.kategori || 'lainnya') + ' · ' + (r.harga == null ? 'Tanyakan harga' : 'Rp' + Number(r.harga).toLocaleString('id-ID'));
            li.appendChild(meta);

            if (typeof r.tautan === 'string' && /^https:\/\//i.test(r.tautan)) {
                var a = document.createElement('a');
                a.className = 'ruko-kartu-tautan';
                a.href = r.tautan;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                a.textContent = 'Kunjungi usaha';
                li.appendChild(a);
            }
            elGrid.appendChild(li);
        });
        elDaftar.hidden = false;
    }

    var nomor = baca(KUNCI_NOMOR);
    var kunci = baca(KUNCI_KUNCI);

    // Daftar ruko dimuat apa pun keadaannya (tamu, belum punya, punya).
    // Dijalankan sekali; kegagalan jaringan cukup menyembunyikan daftar.
    function muatDaftarRuko() {
        fetch(awalan + 'peta3d/data-desa', { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !Array.isArray(data.bangunan)) return;
                var ruko = data.bangunan.filter(function (b) { return b.jenis === 'ruko'; });
                // Yang paling baru ditampilkan lebih dulu, seperti katalog.
                ruko.reverse();
                gambarDaftarRuko(ruko, nomor);
            })
            .catch(function () { /* daftar tidak wajib: panel tetap berguna */ });
    }

    if (!/^DT-\d{4,9}$/.test(nomor) || kunci === '') {
        tampilKeadaan('tamu');
        window.__rukoPanel = { keadaan: 'tamu' };
        muatDaftarRuko();
        return;
    }

    // ruko.php?nomor=...&kunci=...&json=1 mengembalikan { ruko: {...} | null }.
    // Tanpa penanda json=1 halaman biasa yang dikirim, jadi aman dipanggil.
    var alamat = awalan + 'ruko?json=1&nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci);
    tampilKeadaan('muat');

    fetch(alamat, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (hasil) {
            if (!hasil) {
                // Kartu tidak bisa dipakai (kunci tidak cocok atau server
                // sibuk). Jangan tampilkan data orang lain: arahkan ke tamu.
                tampilKeadaan('tamu');
                window.__rukoPanel = { keadaan: 'tamu' };
                return;
            }
            var ruko = hasil.ruko;
            if (!ruko) {
                tampilKeadaan('kosong');
                window.__rukoPanel = { keadaan: 'kosong' };
                muatDaftarRuko();
                return;
            }
            if (elNama) elNama.textContent = ruko.namaUsaha || 'Ruko warga';
            // Gambar usaha dari ruko (khususnya untuk lapak di Pasar).
            var elGambar = document.getElementById('rukoPanelGambar');
            if (elGambar) {
                if (ruko.gambar) {
                    elGambar.src = ruko.gambar;
                    elGambar.alt = 'Gambar ' + (ruko.namaUsaha || 'ruko');
                    elGambar.hidden = false;
                } else {
                    elGambar.hidden = true;
                    elGambar.removeAttribute('src');
                }
            }
            if (elGang) elGang.textContent = ruko.gang ? 'di ' + ruko.gang : '';
            if (elCerita) {
                elCerita.textContent = ruko.cerita || '';
                elCerita.hidden = !ruko.cerita;
            }
            if (elKunjungi) {
                if (ruko.tautan) {
                    elKunjungi.href = ruko.tautan;
                    elKunjungi.hidden = false;
                } else {
                    elKunjungi.hidden = true;
                }
            }
            setKabar('');
            tampilKeadaan('milik');
            window.__rukoPanel = { keadaan: 'milik', nama: ruko.namaUsaha };
            muatDaftarRuko();
        })
        .catch(function () {
            // Jaringan mati: tetap tampilkan jalan masuk ke halaman ruko
            // daripada panel kosong tanpa arti.
            tampilKeadaan('kosong');
            if (elBuka) elBuka.textContent = 'Buka halaman ruko';
            window.__rukoPanel = { keadaan: 'kosong', galat: 'jaringan' };
        });
})();
</script>
