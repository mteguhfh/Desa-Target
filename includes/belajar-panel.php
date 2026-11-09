<?php
// Isi panel Pusat Pembelajaran di peta 2D dan 3D. Kontennya diisi oleh
// belajar-skrip.php dari belajar.php?format=json, sumber data yang sama
// dengan halaman penuh Belajar (ADR-0004). Tamu melihat katalog kelas;
// warga yang kunci kartunya dikenal peramban melihat progresnya sendiri.
?>
<div class="panel-isi" data-isi="belajar" hidden>
    <p class="petunjuk" data-belajar-panel-status>Memuat kelas...</p>
    <div data-belajar-panel-kelas></div>
    <div data-belajar-panel-materi></div>
    <p class="petunjuk">
        Materi berurutan: selesaikan satu materi untuk membuka berikutnya.
        Setiap materi tuntas memberi poin ke dompetmu.
        <a href="<?= e(($tabAwalan ?? '') . 'belajar') ?>">Buka halaman Belajar penuh</a> bila panel ini tidak cukup.
    </p>
</div>
