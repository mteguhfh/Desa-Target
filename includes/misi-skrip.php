<?php
// Penanda misi "keliling-desa" dan ringkasan misi.
//
// Saat pengunjung menekan Keliling Desa, beri tahu server bahwa misi itu
// dikerjakan hari ini. Permintaan hanya dikirim sekali per kunjungan, dan
// hanya saat mode jalan DINYALAKAN (bukan saat berhenti).
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
    var MISI = 'keliling-desa';
    var penanda = document.getElementById('misiPenanda');
    if (!penanda) return;
    var endpoint = penanda.getAttribute('data-endpoint') || 'misi.php';

    var tombolJalan = document.getElementById('tombolJalan');
    if (!tombolJalan) return;

    var sudahDikirim = false;
    tombolJalan.addEventListener('click', function () {
        if (sudahDikirim) return;
        // Hanya saat mode jalan dinyalakan, bukan saat dimatikan.
        if (!tombolJalan.classList.contains('aktif')) return;
        sudahDikirim = true;
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: 'kode=' + encodeURIComponent(MISI),
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            if (!data || !data.ringkasan) return;
            var teks = document.getElementById('misiRingkasan');
            if (teks) teks.textContent = data.ringkasan.selesai + ' dari ' + data.ringkasan.total;
            var baris = document.querySelector('.misi-baris[data-kode="' + MISI + '"]');
            if (baris) {
                baris.classList.add('selesai');
                var tanda = baris.querySelector('.misi-tanda');
                var status = baris.querySelector('.misi-status');
                if (tanda) tanda.textContent = '\u2713';
                if (status) status.textContent = 'Selesai';
            }
        }).catch(function () { /* misi bukan hal kritis; gagal diam saja */ });
    });
})();
</script>
