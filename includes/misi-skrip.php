<?php
// Penanda misi dari panel, termasuk "keliling-desa" yang dipicu tombol
// Keliling Desa.
//
// Semua misi ditandai untuk warga aktif (nomor + kunci dari localStorage),
// jadi poinnya masuk ke dompet warga itu sendiri. Saat pengunjung menekan
// Keliling Desa, misi itu dikirim sekali per kunjungan, dan hanya saat mode
// jalan DINYALAKAN (bukan saat berhenti).
//
// Tombol "Tandai selesai" di baris misi juga memakai jalur yang sama, supaya
// misi yang tidak bisa dibuktikan otomatis (keliling-desa) tetap bisa
// dicatat dari panel.
//
// Penanda ini menyamar sebagai elemen tersembunyi yang membawa alamat
// endpoint, supaya skrip tidak perlu tahu di mana halamannya berada
// (peta.php di akar atau peta3d/ di subfolder).
//
// Berkas ini hanya mengeluarkan markup + <script>. Perilakunya sama sekali
// tidak memengaruhi peta: kalau permintaan gagal, tidak terjadi apa-apa.
?>
<span id="misiPenanda" hidden data-endpoint="<?= e($awalanMisi ?? '') ?>misi.php"></span>
<script>
(function () {
    var MISI_JALAN = 'keliling-desa';
    var penanda = document.getElementById('misiPenanda');
    if (!penanda) {
        return;
    }
    var endpoint = penanda.getAttribute('data-endpoint') || 'misi.php';
    var tujuan = {
        'usulkan-usaha': function () { window.open('https://www.instagram.com/desatarget', '_blank', 'noopener'); },
        'dukung-teman': function () { window.open('https://www.instagram.com/desatarget', '_blank', 'noopener'); },
        'bikin-ktp': function () { var tombol = document.querySelector('[data-panel="aku"]'); if (tombol) tombol.click(); else window.location.href = 'ktp.php'; },
        'keliling-desa': function () { var tombol = document.getElementById('tombolJalan'); if (tombol) tombol.click(); }
    };

    function identitas() {
        try {
            return {
                nomor: localStorage.getItem('dt_nomor') || '',
                kunci: localStorage.getItem('dt_kunci') || ''
            };
        } catch (e) {
            return { nomor: '', kunci: '' };
        }
    }

    // Kirim penanda misi. Tanpa KTP, misi tetap dicatat sebagai keadaan desa
    // seperti dulu (tanpa poin untuk siapa pun).
    function kirimMisi(kode, saatDapat) {
        var siapa = identitas();
        var body = 'kode=' + encodeURIComponent(kode);
        if (/^DT-\d{4,9}$/.test(siapa.nomor)) {
            body += '&nomor=' + encodeURIComponent(siapa.nomor) + '&kunci=' + encodeURIComponent(siapa.kunci);
        }
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (!data) return;
            if (data.ringkasan) {
                var teks = document.getElementById('misiRingkasan');
                if (teks) teks.textContent = data.ringkasan.selesai + ' dari ' + data.ringkasan.total;
            }
            if (window.__dompetPoin && typeof data.streak === 'number') window.__dompetPoin.isiStreak(data.streak);
            var baris = document.querySelector('.misi-baris[data-kode="' + kode + '"]');
            if (baris) {
                baris.classList.add('selesai');
                var tanda = baris.querySelector('.misi-tanda');
                var status = baris.querySelector('.misi-status');
                var tombol = baris.querySelector('.misi-tandai');
                if (tanda) tanda.textContent = '\u2713';
                if (status) status.textContent = 'Selesai';
                if (tombol) tombol.hidden = true;
            }
            if (typeof saatDapat === 'function') saatDapat(data);
        }).catch(function () { /* misi bukan hal kritis; gagal diam saja */ });
    }

    document.querySelectorAll('.misi-aksi').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var aksi = tujuan[this.getAttribute('data-aksi')];
            if (aksi) aksi();
        });
    });

    var tombolJalan = document.getElementById('tombolJalan');
    if (tombolJalan) {
        var sudahDikirim = false;
        tombolJalan.addEventListener('click', function () {
            if (sudahDikirim) return;
            // Hanya saat mode jalan dinyalakan, bukan saat dimatikan.
            if (!tombolJalan.classList.contains('aktif')) return;
            sudahDikirim = true;
            kirimMisi(MISI_JALAN, function (data) {
                if (window.__dompetPoin && typeof data.saldo === 'number') {
                    window.__dompetPoin.tampilSaldo(data.saldo);
                    window.__dompetPoin.isiMisi(data.daftar);
                    window.__dompetPoin.isiRingkasan(data.ringkasan);
                }
            });
        });
    }

    // Tombol "Tandai selesai" di setiap baris misi panel.
    var daftarTombol = document.querySelectorAll('.misi-tandai');
    for (var i = 0; i < daftarTombol.length; i++) {
        daftarTombol[i].addEventListener('click', function () {
            var kode = this.getAttribute('data-kode');
            if (!kode) return;
            this.disabled = true;
            var siapa = identitas();
            if (!/^DT-\d{4,9}$/.test(siapa.nomor)) {
                this.disabled = false;
                return;
            }
            if (window.__dompetPoin) window.__dompetPoin.tampilKabar('');
            kirimMisi(kode, function (data) {
                if (window.__dompetPoin && typeof data.saldo === 'number') {
                    window.__dompetPoin.tampilSaldo(data.saldo);
                    window.__dompetPoin.isiMisi(data.daftar);
                    window.__dompetPoin.isiRingkasan(data.ringkasan);
                    if (data.poin > 0) {
                        window.__dompetPoin.tampilKabar('Misi selesai, +' + data.poin + ' poin masuk ke dompetmu.');
                    }
                }
            });
        });
    }
})();
</script>
