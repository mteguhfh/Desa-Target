<?php
// Tombol sakelar antara peta desa 3D dan peta desa 2D (ADR-0001).
//
// Dipasang di kedua halaman peta. Yang aktif ditandai aria-current="true"
// supaya pembaca layar tahu mode mana yang sedang dilihat.
// Pilihan pengunjung diingat di localStorage (kunci "modePeta") oleh skrip
// di includes/sakelar-peta-skrip.php.
//
// Variabel yang diharapkan:
//   $modePeta    '3d' | '2d' — mode halaman yang sedang dilihat
//   $awalanPeta  '' ketika halaman ada di akar situs, '../' ketika di
//                dalam subfolder seperti peta3d/
//   $peta3dAkar  true bila 3D ada di akar situs (beranda, ADR-0005). Tanpa
//                itu, tombol 3D mengarah ke peta.php.
$modePeta = $modePeta ?? '3d';
$awalanPeta = $awalanPeta ?? '';
$peta3dAkar = $peta3dAkar ?? false;
?>
<nav class="sakelar-peta" aria-label="Pilih tampilan peta">
    <a class="sakelar-opsi<?= $modePeta === '3d' ? ' aktif' : '' ?>"
       href="<?= $peta3dAkar ? $awalanPeta : $awalanPeta . 'peta' ?>?tetap=1" data-mode="3d"
       aria-current="<?= $modePeta === '3d' ? 'true' : 'false' ?>">3D</a>
    <a class="sakelar-opsi<?= $modePeta === '2d' ? ' aktif' : '' ?>"
       href="<?= $awalanPeta ?>peta2d" data-mode="2d"
       aria-current="<?= $modePeta === '2d' ? 'true' : 'false' ?>">2D</a>
</nav>
