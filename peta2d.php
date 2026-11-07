<?php
// Peta desa versi 2D (SVG). Ini tampilan alternatif dari peta 3D yang
// menjadi default di peta.php (ADR-0001). Isinya sama dengan peta.php
// sebelum 3D dijadikan default, ditambah sakelar kembali ke mode 3D.
//
// Halaman ini BUKAN pintu masuk peta: pengunjung yang membuka menu
// "Peta Desa" mendarat di peta.php (3D). Pilihan mode diingat di
// localStorage lewat includes/sakelar-peta-skrip.php.
$modePeta = '2d';
require __DIR__ . '/includes/peta-data.php';

require __DIR__ . '/includes/sakelar-peta.php';

require __DIR__ . '/includes/peta-markup.php';

require __DIR__ . '/includes/peta-skrip.php';

require __DIR__ . '/includes/sakelar-peta-skrip.php';

require __DIR__ . '/includes/footer.php';
