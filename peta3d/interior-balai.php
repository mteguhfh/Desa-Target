<?php
// Interior Balai Desa untuk peta 3D.
//
// Isinya SAMA dengan interior di peta 2D: fungsi interior_balai_svg() dipakai
// bersama, jadi tidak ada dua versi ruangan yang bisa berbeda. Yang baru di
// sini hanya caranya tampil: lapisan di atas kanvas 3D, dibuka saat pengunjung
// berdiri di depan pintu Balai Desa dan mengetuk "Masuk Balai Desa".
//
// Sebelumnya tombol itu membuang pengunjung ke peta2d.php. Sekarang mereka
// masuk tanpa kehilangan mode 3D, dan keluar kembali ke posisi semula.
//
// Variabel yang dipakai: $bangunanTerbaru (dari bangunan_terbaru()).
// Ambil sendiri dari basis data, jangan bergantung pada variabel halaman:
// peta3d/index.php tidak menyiapkan $bangunanTerbaru, dan interior harus tetap
// tampil walau halaman pemanggil lupa menyiapkannya.
require_once __DIR__ . '/../db.php';
// interior_balai_svg() memakai svg_teks() dari bangunan-svg.php.
require_once __DIR__ . '/../includes/bangunan-svg.php';
require_once __DIR__ . '/../includes/interior-balai.php';
try {
    $bangunanTerbaru = bangunan_terbaru(db());
} catch (Throwable $e) {
    $bangunanTerbaru = null; // interior_balai_svg() menerima null
}
?>
<div class="interior-3d" id="interior3d" hidden>
    <div class="interior-3d-bingkai">
        <?= interior_balai_svg($bangunanTerbaru) ?>
    </div>
    <button type="button" class="interior-3d-keluar" id="tombolKeluarInterior3d">&larr; Keluar ke Peta 3D</button>
</div>
