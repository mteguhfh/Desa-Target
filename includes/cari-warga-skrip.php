<?php
// Pencarian warga di panel bersayap, tanpa memuat ulang halaman.
//
// Ada dua jalur:
//   - JavaScript aktif: hasil diambil dari cari-warga.php dan ditulis ke
//     panel, jadi dunia 3D/2D di belakangnya tidak pernah hilang.
//   - JavaScript mati: formulir tetap mengirim ke warga.php seperti biasa,
//     karena action= dan method= sudah ada di markup.
//
// Berkas ini hanya mengeluarkan <script>.
?>
<script>
(function () {
    var form = document.getElementById('cariWargaForm');
    var isian = document.getElementById('cariWargaKunci');
    var hasil = document.getElementById('cariWargaHasil');
    if (!form || !isian || !hasil) return;

    var permintaanTerakhir = 0;
    var pewaktu = null;

    function bersihkan(teks) {
        var d = document.createElement('div');
        d.textContent = teks == null ? '' : String(teks);
        return d.innerHTML;
    }

    function tampilkan(daftar, kunci) {
        if (!daftar.length) {
            hasil.innerHTML = '<p class="cari-warga-kosong">Tidak ada warga bernama atau bernomor "' +
                bersihkan(kunci) + '". Coba kata lain.</p>';
            return;
        }
        var html = '';
        for (var i = 0; i < daftar.length; i++) {
            var w = daftar[i];
            html += '<button type="button" class="cari-warga-baris" data-warga-nomor="' + bersihkan(w.nomor) + '" data-warga-nama="' + bersihkan(w.nama) + '">' +
                '<span class="cari-warga-nama">' + bersihkan(w.nama) + '</span>' +
                '<span class="cari-warga-meta">' + bersihkan(w.nomor) + ' &middot; ' + bersihkan(w.gang) + '</span>' +
                '</button>';
        }
        hasil.innerHTML = html;
    }

    function cari(kunci) {
        if (kunci === '' && arguments.length === 0) { kunci = ''; }
        var nomor = ++permintaanTerakhir;
        hasil.innerHTML = '<p class="cari-warga-memuat">Mencari…</p>';
        // Endpoint relatif terhadap HALAMAN, bukan folder: halaman peta 3D
        // bisa dibuka dari akar (/peta.php) maupun dari /peta3d/.
        var endpoint = form.getAttribute('data-endpoint') || 'cari-warga.php';
        fetch(endpoint + '?cari=' + encodeURIComponent(kunci), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (nomor !== permintaanTerakhir) return; // jawaban lama, abaikan
                if (!data || !Array.isArray(data.hasil)) {
                    hasil.innerHTML = '<p class="cari-warga-kosong">Daftar warga belum bisa dibaca.</p>';
                    return;
                }
                tampilkan(data.hasil, data.cari);
            })
            .catch(function () {
                if (nomor !== permintaanTerakhir) return;
                hasil.innerHTML = '<p class="cari-warga-kosong">Tidak bisa menghubungi balai desa. Coba lagi.</p>';
            });
    }

    isian.addEventListener('input', function () {
        window.clearTimeout(pewaktu);
        var kunci = isian.value.trim();
        pewaktu = window.setTimeout(function () { cari(kunci); }, 250);
    });

    function wargaTerbaru() { cari(''); }
    hasil.addEventListener('click', function (e) {
        var baris = e.target.closest ? e.target.closest('[data-warga-nomor]') : null;
        if (!baris || !window.__desa || !window.__desa.cariWarga) return;
        e.preventDefault();
        window.__desa.cariWarga(baris.getAttribute('data-warga-nomor'), baris.getAttribute('data-warga-nama'));
    });

    form.addEventListener('submit', function (e) {
        // Tetap di halaman: hasil muncul di panel, bukan halaman baru.
        e.preventDefault();
        window.clearTimeout(pewaktu);
        cari(isian.value.trim());
    });

    // Pintasan untuk peta 3D: saat tab pencarian dibuka, langsung fokus ke isian.
    var tabWarga = document.querySelector('.tab-bar [data-panel="warga"]');
    if (tabWarga) {
        tabWarga.addEventListener('click', function () {
            window.setTimeout(function () { isian.focus(); }, 60);
        });
    }

    // Bila halaman peta dibuka dengan ?cari=..., langsung tampilkan hasilnya
    // dan buka panel tab Warga. Dipakai tautan dari halaman daftar warga.
    var kunciUrl = new URLSearchParams(window.location.search).get('cari');
    if (kunciUrl && typeof window.__tabBar === 'object' && window.__tabBar) {
        isian.value = kunciUrl;
        window.__tabBar.buka('warga', null);
        cari(kunciUrl.trim());
    }

    window.__cariWarga = { cari: cari, hasil: hasil, isian: isian, wargaTerbaru: wargaTerbaru };
    wargaTerbaru();
})();
</script>
