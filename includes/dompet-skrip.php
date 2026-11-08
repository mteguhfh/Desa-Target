<?php
// Dompet poin warga dan misi per warga di panel Misi.
//
// Identitas warga di peramban: angka di localStorage kunci "dt_nomor" dan
// kunci rahasianya di "dt_kunci". Keduanya ditulis oleh ktp.php saat pemilik
// membuka kartunya. Tanpa keduanya, panel Misi tetap membaca daftar misi desa
// (hanya baca) dan menampilkan ajakan bikin KTP.
//
// Berkas ini hanya mengeluarkan <script>. Kalau server tidak bisa dihubungi,
// panel memakai isi dari server halaman ini dan tidak terjadi apa-apa.
//
// Variabel: $awalanPoin ('' dari akar, '../' dari peta3d/).
?>
<script>
(function () {
    var awalan = <?= json_encode($awalanPoin ?? '', JSON_UNESCAPED_SLASHES) ?>;
    var KUNCI_NOMOR = 'dt_nomor';
    var KUNCI_KUNCI = 'dt_kunci';

    var dompet = document.getElementById('misiDompet');
    var tamu = document.getElementById('misiTamu');
    var saldoEl = document.getElementById('misiSaldo');
    var saldoKet = document.getElementById('misiSaldoKet');
    var tombolKlaim = document.getElementById('tombolKlaimHarian');
    var kabar = document.getElementById('misiKabar');
    var ringkasanEl = document.getElementById('misiRingkasan');
    var kemajuanLabel = document.getElementById('misiKemajuanLabel');
    var kemajuanBar = document.getElementById('misiBar');
    var streakEl = document.getElementById('misiStreak');
    var chipPoin = document.getElementById('chipPoin');
    var chipAngka = document.getElementById('chipPoinAngka');
    if (!dompet || !tamu) return;

    function baca(kunci) {
        try { return localStorage.getItem(kunci) || ''; } catch (e) { return ''; }
    }

    var nomor = baca(KUNCI_NOMOR);
    var kunci = baca(KUNCI_KUNCI);

    if (!/^DT-\d{4,9}$/.test(nomor)) {
        tamu.hidden = false;
        dompet.hidden = true;
        return;
    }
    tamu.hidden = true;
    dompet.hidden = false;
    if (chipPoin) chipPoin.hidden = false;

    function tampilSaldo(nilai) {
        var teks = String(parseInt(nilai, 10) || 0);
        if (saldoEl) saldoEl.textContent = teks;
        if (chipAngka) chipAngka.textContent = teks;
    }

    function tampilKabar(teks) {
        if (!kabar) return;
        kabar.textContent = teks;
        kabar.hidden = teks === '';
    }

    function kirim(url, body) {
        return fetch(awalan + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body
        });
    }

    function isiMisi(daftar) {
        if (!Array.isArray(daftar)) return;
        daftar.forEach(function (m) {
            var baris = document.querySelector('.misi-baris[data-kode="' + m.kode + '"]');
            if (!baris) return;
            baris.classList.toggle('selesai', !!m.selesai);
            var tanda = baris.querySelector('.misi-tanda');
            var status = baris.querySelector('.misi-status');
            if (tanda) tanda.innerHTML = m.selesai ? '\u2713' : '';
            if (status) status.textContent = m.selesai ? 'Selesai' : 'Belum';
        });
    }

    function isiRingkasan(ringkasan) {
        if (ringkasan && ringkasanEl) {
            ringkasanEl.textContent = ringkasan.selesai + ' dari ' + ringkasan.total;
            var persen = parseInt(ringkasan.persen, 10) || 0;
            if (kemajuanLabel) kemajuanLabel.textContent = persen + '%';
            if (kemajuanBar) { kemajuanBar.setAttribute('aria-valuenow', persen); kemajuanBar.querySelector('span').style.width = persen + '%'; }
        }
    }
    function isiStreak(nilai) {
        if (streakEl && typeof nilai === 'number') streakEl.textContent = nilai > 0 ? 'Streak: ' + nilai + ' hari beruntun' : 'Streak: belum mulai';
    }

    var klaimSelesai = false;

    function muatDompet() {
        if (kunci === '') {
            saldoKet.textContent = 'Buka kartu wargamu di halaman KTP supaya poinmu bisa terbaca di peramban ini.';
            if (tombolKlaim) tombolKlaim.disabled = true;
            return;
        }
        var body = 'nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci);
        fetch(awalan + 'poin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (!data) return;
            tampilSaldo(data.saldo);
            klaimSelesai = !!data.klaim;
            // Status misi di bawah ini milik warga itu, bukan keadaan desa.
            if (Array.isArray(data.daftar)) {
                isiMisi(data.daftar);
                isiRingkasan(data.ringkasan);
            }
            isiStreak(data.streak);
            if (tombolKlaim) {
                tombolKlaim.disabled = klaimSelesai;
                tombolKlaim.textContent = klaimSelesai ? 'Poin harian sudah diklaim' : 'Klaim poin harian +' + (data.poinKlaimHarian || 0);
            }
        }).catch(function () { /* dompet bukan hal kritis; gagal diam saja */ });
    }

    if (tombolKlaim) {
        tombolKlaim.textContent = 'Klaim poin harian';
        tombolKlaim.addEventListener('click', function () {
            if (klaimSelesai) return;
            tombolKlaim.disabled = true;
            if (kunci === '') {
                tampilKabar('Buka kartu wargamu di halaman KTP dulu supaya poin bisa diklaim.');
                tombolKlaim.disabled = false;
                return;
            }
            kirim('klaim.php', 'nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci))
                .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
                .then(function (hasil) {
                    if (hasil.status === 200) {
                        tampilSaldo(hasil.data.saldo);
                        klaimSelesai = true;
                        tombolKlaim.textContent = 'Poin harian sudah diklaim';
                        tombolKlaim.disabled = true;
                        tampilKabar('Poin harian masuk. Kembali besok ya.');
                    } else if (hasil.status === 409) {
                        klaimSelesai = true;
                        tombolKlaim.textContent = 'Poin harian sudah diklaim';
                        tombolKlaim.disabled = true;
                        tampilKabar(hasil.data.galat);
                    } else {
                        tombolKlaim.disabled = false;
                        tampilKabar(hasil.data.galat || 'Poin harian belum bisa diklaim.');
                    }
                })
                .catch(function () {
                    tombolKlaim.disabled = false;
                    tampilKabar('Jaringan sedang rewel. Coba lagi sebentar.');
                });
        });
    }

    // Misi yang ditandai lewat Keliling Desa: misi-skrip.php mengirim misi
    // dengan nomor dan kunci warga, lalu memberi tahu keadaan terbarunya.
    // Berkas ini menampung hasilnya supaya saldo dan status ikut berubah.
    window.__dompetPoin = {
        nomor: nomor,
        kunci: kunci,
        tampilSaldo: tampilSaldo,
        tampilKabar: tampilKabar,
        isiMisi: isiMisi,
        isiRingkasan: isiRingkasan
        ,isiStreak: isiStreak
    };

    muatDompet();
})();
</script>
