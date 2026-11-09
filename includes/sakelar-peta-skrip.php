<?php
// Skrip pengingat pilihan tampilan peta (ADR-0001). Diperlukan tepat satu
// kali per halaman, sebelum pengunjung menekan sakelar.
//
// Cara kerja: setiap sakelar membawa data-mode ("3d" atau "2d"). Saat salah
// satu diklik, kunci "modePeta" di localStorage diisi nilai itu. Ketika
// pengunjung membuka peta lagi lewat menu Beranda (index.php atau peta.php),
// skrip memeriksa kunci itu dan mengarahkan ke mode yang terakhir dipilih.
//
// Yang diingat adalah NAMA MODE, bukan alamat halaman, supaya perpindahan
// tetap benar walau alamatnya berubah kelak.
?>
<script>
(function () {
    var KUNCI = 'modePeta';
    try {
        // 1. Catat pilihan setiap kali sakelar ditekan.
        document.querySelectorAll('.sakelar-opsi').forEach(function (tautan) {
            tautan.addEventListener('click', function () {
                try { localStorage.setItem(KUNCI, tautan.dataset.mode); } catch (e) {}
            });
        });

        // 2. Kalau halaman ini dibuka sebagai pintu masuk peta (beranda atau
        //    peta.php tanpa penanda), dan pengunjung terakhir memilih 2D,
        //    arahkan ke sana.
        //    Penanda ?tetap=1 berarti pengunjung sengaja memilih 3D, jadi
        //    tidak boleh dibelokkan.
        var params = new URLSearchParams(window.location.search);
        var pintuMasuk = document.body && document.body.dataset.pintuPeta === '1';
        if (pintuMasuk && !params.has('tetap')) {
            var terakhir = null;
            try { terakhir = localStorage.getItem(KUNCI); } catch (e) {}
            if (terakhir === '2d') {
                window.location.replace('peta2d?tetap=1');
            }
        }
    } catch (e) { /* localStorage diblokir: abaikan, default 3D */ }
})();
</script>
