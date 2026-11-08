<?php
// Panel Aku: bikin KTP Warga langsung dari peta, lalu lihat kartunya
// (ADR-0007).
//
// Sebelumnya tab Aku hanya menaut ke ktp.php, jadi mengetuk KTP membawa
// pengunjung keluar dari dunia 3D. Sekarang formulirnya ada di panel dan
// dikirim ke ktp-json.php, yang memakai aturan yang sama dengan ktp.php.
//
// Dua keadaan, dipilih dari kartu warga di peramban:
//   - belum punya dt_nomor + dt_kunci: formulir bikin KTP
//   - sudah punya: nama, gang, saldo poin, tombol klaim poin harian
//
// Kunci warga dijawab ktp-json.php sekali, lalu disimpan di localStorage
// supaya fitur poin, ruko, dan lapak bisa memakainya.
//
// Berkas ini hanya mengeluarkan <script>.
//
// Variabel: $akuAwalan ('' dari akar, '../' dari peta3d/).
?>
<script>
(function () {
    var awalan = <?= json_encode($akuAwalan ?? '', JSON_UNESCAPED_SLASHES) ?>;
    var KUNCI_NOMOR = 'dt_nomor';
    var KUNCI_KUNCI = 'dt_kunci';

    var muat = document.getElementById('akuMuat');
    var tamu = document.getElementById('akuTamu');
    var punya = document.getElementById('akuPunya');
    var form = document.getElementById('akuFormKtp');
    if (!muat || !tamu || !punya || !form) return;

    var inputNama = document.getElementById('akuNama');
    var inputGang = document.getElementById('akuGang');
    var kabarTamu = document.getElementById('akuKabar');
    var kabarPunya = document.getElementById('akuKabarPunya');
    var elNama = document.getElementById('akuName');
    var elMeta = document.getElementById('akuMeta');
    var elPoin = document.getElementById('akuPoinAngka');
    var tombolKlaim = document.getElementById('akuKlaim');

    function baca(kunci) {
        try { return localStorage.getItem(kunci) || ''; } catch (e) { return ''; }
    }
    function simpan(kunci, nilai) {
        try { localStorage.setItem(kunci, nilai); } catch (e) { /* localStorage diblokir */ }
    }

    function tampilKabar(el, teks) {
        if (!el) return;
        el.textContent = teks;
        el.hidden = teks === '';
    }

    function tampilKeadaan(nama) {
        muat.hidden = nama !== 'muat';
        tamu.hidden = nama !== 'tamu';
        punya.hidden = nama !== 'punya';
        window.__akuPanel = { keadaan: nama };
    }

    function punyaKartu() {
        return /^DT-\d{4,9}$/.test(baca(KUNCI_NOMOR)) && baca(KUNCI_KUNCI) !== '';
    }

    // Tampilkan kartu dari data yang sudah ada di peramban. Saldo diambil
    // dari poin.php (baca saja) supaya angka di sini sama dengan dompet.
    function tampilKartu(namaDariKtp) {
        tampilKeadaan('punya');
        if (namaDariKtp) elNama.textContent = namaDariKtp;
        var nomor = baca(KUNCI_NOMOR);
        var kunci = baca(KUNCI_KUNCI);
        elMeta.textContent = nomor;
        var data = new FormData();
        data.append('nomor', nomor);
        data.append('kunci', kunci);
        fetch(awalan + 'poin.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (hasil) {
                if (!hasil) return;
                if (typeof hasil.saldo === 'number') elPoin.textContent = hasil.saldo;
                if (hasil.nama) elNama.textContent = hasil.nama;
                if (hasil.gang) elMeta.textContent = hasil.gang + ' · ' + nomor;
            })
            .catch(function () { /* saldo gagal dimuat: angka tetap 0 */ });
    }

    // Formulir bikin KTP. Dikirim ke ktp-json.php dengan token sesi dari
    // halaman yang sedang dibuka (token KTP dibuat di sesi yang sama).
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        tampilKabar(kabarTamu, '');
        var tombol = form.querySelector('button[type="submit"]');
        var data = new FormData(form);
        data.set('token', window.__tokenKtp || '');
        if (tombol) tombol.disabled = true;

        fetch(awalan + 'ktp-json.php', { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, isi: j }; }); })
            .then(function (hasil) {
                if (tombol) tombol.disabled = false;
                if (!hasil.ok) {
                    var galat = hasil.isi.galat;
                    tampilKabar(kabarTamu, Array.isArray(galat) ? galat.join(' ') : (galat || 'Kartu belum bisa dibuat.'));
                    return;
                }
                simpan(KUNCI_NOMOR, hasil.isi.nomor);
                simpan(KUNCI_KUNCI, hasil.isi.kunci);
                form.reset();
                tampilKartu(hasil.isi.nama);
                tampilKabar(kabarPunya, 'Selamat! Kartu wargamu jadi, nomornya ' + hasil.isi.nomor + '.');
                // Warga baru langsung bisa memakai fitur poin di bilah atas.
                var chip = document.getElementById('chipPoin');
                if (chip) chip.hidden = false;
            })
            .catch(function () {
                if (tombol) tombol.disabled = false;
                tampilKabar(kabarTamu, 'Balai desa sedang lambat. Coba lagi sebentar lagi.');
            });
    });

    // Klaim poin harian dari panel Aku.
    if (tombolKlaim) {
        tombolKlaim.addEventListener('click', function () {
            var data = new FormData();
            data.append('nomor', baca(KUNCI_NOMOR));
            data.append('kunci', baca(KUNCI_KUNCI));
            tombolKlaim.disabled = true;
            fetch(awalan + 'klaim.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, isi: j }; }); })
                .then(function (hasil) {
                    tombolKlaim.disabled = false;
                    if (hasil.ok) {
                        elPoin.textContent = parseInt(hasil.isi.saldo, 10) || 0;
                        tampilKabar(kabarPunya, 'Poin harianmu masuk.');
                    } else {
                        tampilKabar(kabarPunya, hasil.isi.galat || 'Poin harian belum bisa diklaim.');
                    }
                })
                .catch(function () {
                    tombolKlaim.disabled = false;
                    tampilKabar(kabarPunya, 'Balai desa sedang lambat. Coba lagi nanti.');
                });
        });
    }

    if (punyaKartu()) tampilKartu('');
    else tampilKeadaan('tamu');
})();
</script>
