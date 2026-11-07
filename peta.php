<?php
// Peta Desa Target — pintu masuk peta, mode 3D (ADR-0001).
//
// Dunia 3D beserta seluruh kendalinya tinggal di peta3d/index.php. Berkas ini
// menjalankannya langsung (bukan redirect) supaya alamat tetap /peta.php dan
// pengunjung tidak melihat halaman perantara.
//
// Pilihan mode 2D diingat lewat localStorage. Skrip pengingat ada di dalam
// peta3d/index.php, yang memeriksa penanda <body data-pintu-peta="1">.
$dariAkar = true;
$tetap = isset($_GET['tetap']);

require __DIR__ . '/peta3d/index.php';
