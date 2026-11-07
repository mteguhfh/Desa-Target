<?php
// Gambar SVG untuk Peta Desa interaktif.
//
// Semua bangunan digambar dari bentuk dasar SVG (persegi, segitiga, lingkaran),
// tanpa aset gambar luar. Fungsi svg_bangunan() mengembalikan satu grup SVG
// (<g>) yang sudah berisi data detail bangunan sebagai atribut data-*;
// peta.php menaruh grup itu di titik kavlingnya dan skrip kecil di halaman
// yang sama membaca atributnya saat bangunan diklik.
//
// Aturan gambar:
// - Jenis bangunan dipilih dari kata kunci di nama bangunan:
//   kopi/warung jadi warung kopi, bengkel jadi bengkel, kue/roti/toko jadi
//   toko roti, salon/barber/cukur jadi salon, sisanya ruko biasa.
// - Warna atap mengikuti gang pemilik bangunan.
// - Balai Desa digambar khusus: lebih besar, atap amber, ada bendera.

require_once __DIR__ . '/../config.php';

if (!function_exists('svg_teks')) {
    function svg_teks(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Warna atap per gang. Gang di luar daftar, termasuk Pusat Desa, memakai amber.
function warna_atap_gang(?string $gang): string
{
    $petaWarna = [
        'Gang Dagang' => '#2563eb',
        'Gang Kuliner' => '#ea580c',
        'Gang Jasa' => '#0891b2',
        'Gang Freelancer' => '#7c3aed',
        'Gang Kerajinan' => '#db2777',
    ];
    return $petaWarna[$gang ?? ''] ?? '#f59e0b';
}

// Jenis gambar bangunan dari kata kunci nama. Urutan penting:
// "Warung Kopi" harus terbaca kopi, "Toko Kue" harus terbaca toko roti.
function varian_bangunan(string $nama): string
{
    $n = strtolower($nama);
    if (str_contains($n, 'balai desa')) {
        return 'balai';
    }
    if (str_contains($n, 'kopi') || str_contains($n, 'warung')) {
        return 'kopi';
    }
    if (str_contains($n, 'bengkel')) {
        return 'bengkel';
    }
    if (str_contains($n, 'kue') || str_contains($n, 'roti') || str_contains($n, 'toko')) {
        return 'toko';
    }
    if (str_contains($n, 'salon') || str_contains($n, 'barber') || str_contains($n, 'cukur')) {
        return 'salon';
    }
    return 'ruko';
}

// Ikon kecil di papan nama bangunan, digambar di sekitar titik (0, 0)
// dalam kotak kurang lebih 16 x 16 piksel.
function ikon_bangunan(string $varian, string $warna, string $nama): string
{
    switch ($varian) {
        case 'kopi':
            // Cangkir kopi: badan cangkir, gagang, alas, dan uap.
            return '<g fill="none" stroke="' . $warna . '" stroke-width="1.8" stroke-linecap="round">'
                . '<path d="M-6,-3.5 h11 l-1.3,7.5 h-8.4 z" fill="' . $warna . '" stroke="none"/>'
                . '<path d="M5.2,-2 c2.6,0 2.6,4.6 0,4.6"/>'
                . '<path d="M-7,6.8 h13.5"/>'
                . '<path d="M-2.5,-6.5 c-1,-1.2 1,-2 0,-3.4 M1.8,-6.5 c-1,-1.2 1,-2 0,-3.4" stroke-width="1.4"/>'
                . '</g>';
        case 'bengkel':
            // Roda gigi: lingkaran dengan enam gigi dan lubang tengah.
            $gigi = '';
            for ($i = 0; $i < 6; $i++) {
                $gigi .= '<rect x="-1.4" y="-7" width="2.8" height="3.6" rx="0.8" transform="rotate(' . ($i * 60) . ')"/>';
            }
            return '<g fill="' . $warna . '">' . $gigi . '<circle r="4.4"/>'
                . '<circle r="1.8" fill="#ffffff"/></g>';
        case 'toko':
            // Roti: badan lonjong dengan tiga goresan di atasnya.
            return '<g><path d="M-7.5,1.5 a7.5,5.4 0 1,1 15,0 l0,3 h-15 z" fill="' . $warna . '"/>'
                . '<g stroke="#ffffff" stroke-width="1.5" stroke-linecap="round">'
                . '<path d="M-4,-3.2 l-1,2.4 M0,-4 l-1,2.4 M4,-3.2 l-1,2.4"/></g></g>';
        case 'salon':
            // Gunting: dua bilah menyilang dan dua pegangan bulat.
            return '<g fill="none" stroke="' . $warna . '" stroke-width="1.8" stroke-linecap="round">'
                . '<path d="M-5,-6.5 L5,3.5 M5,-6.5 L-5,3.5"/>'
                . '<circle cx="-5.6" cy="5.4" r="1.9"/><circle cx="5.6" cy="5.4" r="1.9"/></g>';
        default:
            // Ruko biasa: papan nama memakai huruf awal nama bangunan.
            $huruf = '?';
            if (preg_match('/[A-Za-z]/', $nama, $m) === 1) {
                $huruf = strtoupper($m[0]);
            }
            return '<text x="0" y="4.6" text-anchor="middle" font-size="12" font-weight="700" fill="' . $warna . '">' . $huruf . '</text>';
    }
}

// Lencana bintang kecil untuk bangunan yang paling baru dibangun.
function lencana_baru_svg(float $x, float $y): string
{
    return '<g transform="translate(' . $x . ' ' . $y . ')">'
        . '<circle r="11" fill="#f59e0b" stroke="#ffffff" stroke-width="2.5"/>'
        . '<text x="0" y="4.8" text-anchor="middle" font-size="12" fill="#ffffff">★</text></g>';
}

// Rangka dasar bangunan model rumah toko: bayangan, dinding, atap segitiga,
// dan papan nama (fascia) di bawah atap. Dipakai varian ruko, toko, salon,
// dan bengkel; bedanya ada di gambar depannya masing-masing.
function rangka_ruko(string $atap): string
{
    return '<ellipse cx="0" cy="3" rx="57" ry="10" fill="#14532d" opacity="0.18"/>'
        . '<rect x="-45" y="-56" width="90" height="56" fill="#fff8ea"/>'
        . '<rect x="-45" y="-4" width="90" height="4" fill="#f0dfba"/>'
        . '<polygon points="-55,-56 0,-87 55,-56" fill="' . $atap . '"/>'
        . '<rect x="-55" y="-60" width="110" height="6" rx="3" fill="#000000" opacity="0.14"/>'
        . '<rect x="-45" y="-56" width="90" height="15" fill="#ffffff" stroke="#eadfc6" stroke-width="1"/>';
}

// Gambar bangunan sesuai variannya. Koordinat lokal: titik (0, 0) adalah
// titik tengah bawah bangunan, sumbu y negatif ke atas.
function gambar_bangunan(string $varian, string $atap, string $nama, bool $terbaru): string
{
    $svg = '';

    if ($varian === 'balai') {
        // Balai Desa: bangunan utama desa. Lebih besar dari bangunan biasa,
        // atap amber, bendera berkibar di puncaknya, papan nama jelas.
        $svg .= '<ellipse cx="0" cy="4" rx="84" ry="13" fill="#14532d" opacity="0.18"/>'
            . '<rect x="-62" y="-74" width="124" height="74" fill="#fff8ea"/>'
            . '<rect x="-62" y="-5" width="124" height="5" fill="#f0dfba"/>'
            . '<polygon points="-75,-74 0,-113 75,-74" fill="#f59e0b"/>'
            . '<rect x="-75" y="-78" width="150" height="7" rx="3.5" fill="#000000" opacity="0.12"/>'
            . '<line x1="0" y1="-113" x2="0" y2="-141" stroke="#78716c" stroke-width="3" stroke-linecap="round"/>'
            . '<polygon points="0,-141 27,-134 0,-127" fill="#2563eb"/>'
            . '<rect x="-41" y="-73" width="82" height="17" fill="#ffffff" stroke="#eadfc6" stroke-width="1"/>'
            . '<text x="0" y="-60.5" text-anchor="middle" font-size="11.5" font-weight="700" letter-spacing="1" fill="#92400e">BALAI DESA</text>'
            . '<rect x="-56" y="-48" width="14" height="18" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="-36" y="-48" width="14" height="18" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="22" y="-48" width="14" height="18" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="42" y="-48" width="14" height="18" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="-16" y="-36" width="15" height="36" rx="2" fill="#b45309"/>'
            . '<rect x="1" y="-36" width="15" height="36" rx="2" fill="#b45309"/>'
            . '<rect x="-25" y="-4" width="50" height="4" rx="2" fill="#ddd2b4"/>'
            . '<circle cx="-72" cy="-5" r="8" fill="#22c55e"/><circle cx="-66" cy="-9" r="6" fill="#4ade80"/>'
            . '<circle cx="72" cy="-5" r="8" fill="#22c55e"/><circle cx="66" cy="-9" r="6" fill="#4ade80"/>';
        if ($terbaru) {
            $svg .= lencana_baru_svg(70, -108);
        }
        return $svg;
    }

    if ($varian === 'kopi') {
        // Warung kopi: kedai terbuka dengan kanopi, dua tiang, meja konter,
        // dan papan nama bergambar cangkir yang tergantung di tengah.
        $svg .= '<ellipse cx="0" cy="3" rx="58" ry="10" fill="#14532d" opacity="0.18"/>'
            . '<rect x="-49" y="-52" width="5" height="52" fill="#92600f"/>'
            . '<rect x="44" y="-52" width="5" height="52" fill="#92600f"/>'
            . '<polygon points="-59,-50 59,-50 45,-73 -45,-73" fill="' . $atap . '"/>'
            . '<rect x="-59" y="-56" width="118" height="6" fill="#000000" opacity="0.14"/>'
            . '<rect x="-27" y="-49" width="54" height="16" rx="4" fill="#ffffff" stroke="#eadfc6" stroke-width="1"/>'
            . '<g transform="translate(0 -41)">' . ikon_bangunan('kopi', $atap, $nama) . '</g>'
            . '<rect x="-41" y="-25" width="82" height="25" rx="3" fill="#b45309"/>'
            . '<rect x="-41" y="-25" width="82" height="6" fill="#d97706"/>'
            . '<line x1="-41" y1="-12" x2="41" y2="-12" stroke="#92600f" stroke-width="2"/>'
            . '<rect x="-30" y="-32" width="7" height="7" rx="1.5" fill="#ffffff"/>'
            . '<rect x="22" y="-32" width="7" height="7" rx="1.5" fill="#ffffff"/>';
        if ($terbaru) {
            $svg .= lencana_baru_svg(52, -70);
        }
        return $svg;
    }

    // Tiga varian lain berbagi rangka rumah toko yang sama.
    $svg .= rangka_ruko($atap);
    $svg .= '<g transform="translate(0 -48.5)">' . ikon_bangunan($varian, $atap, $nama) . '</g>';

    if ($varian === 'bengkel') {
        // Bengkel: pintu garasi lebar bergaris horizontal.
        $svg .= '<rect x="-30" y="-38" width="60" height="38" fill="#cbd5e1" stroke="#94a3b8" stroke-width="1.5"/>'
            . '<g stroke="#94a3b8" stroke-width="1.5">'
            . '<line x1="-30" y1="-28.5" x2="30" y2="-28.5"/><line x1="-30" y1="-19" x2="30" y2="-19"/><line x1="-30" y1="-9.5" x2="30" y2="-9.5"/>'
            . '</g>'
            . '<rect x="-30" y="-38" width="60" height="6" fill="#e0f2fe"/>';
    } else {
        // Pintu dan dua jendela untuk ruko, toko roti, dan salon.
        $svg .= '<rect x="-38" y="-34" width="18" height="16" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="20" y="-34" width="18" height="16" fill="#bae6fd" stroke="#7dd3fc" stroke-width="1.5"/>'
            . '<rect x="-9" y="-28" width="18" height="28" rx="2" fill="#b45309"/>'
            . '<circle cx="4.5" cy="-14" r="1.8" fill="#fde68a"/>';

        if ($varian === 'toko') {
            // Toko roti: tenda bergaris warna atap dan putih di atas etalase.
            $garis = '';
            for ($i = 0; $i < 5; $i++) {
                $garis .= '<rect x="' . (-45 + $i * 18) . '" y="-41" width="9" height="8" fill="' . $atap . '"/>';
            }
            $svg .= '<rect x="-45" y="-41" width="90" height="8" fill="#ffffff"/>' . $garis
                . '<rect x="-45" y="-33.5" width="90" height="1.6" fill="#000000" opacity="0.10"/>';
        }

        if ($varian === 'salon') {
            // Salon: tiang barber kecil di samping pintu.
            $svg .= '<rect x="33" y="-40" width="7" height="27" rx="3.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="1"/>'
                . '<rect x="33" y="-37" width="7" height="5" fill="#dc2626"/>'
                . '<rect x="33" y="-28" width="7" height="5" fill="#2563eb"/>'
                . '<rect x="33" y="-19" width="7" height="5" fill="#dc2626"/>';
        }
    }

    if ($terbaru) {
        $svg .= lencana_baru_svg(48, -84);
    }
    return $svg;
}

// Grup SVG lengkap satu bangunan di titik kavlingnya, lengkap dengan atribut
// data detail yang dibaca skrip peta saat bangunan diklik atau dipilih.
function svg_bangunan(array $bangunan, array $posisi, bool $terbaru = false): string
{
    $varian = varian_bangunan((string) $bangunan['nama']);
    $atap = $varian === 'balai' ? '#f59e0b' : warna_atap_gang($bangunan['gang'] ?? null);
    $x = (int) $posisi[0];
    $y = (int) $posisi[1];

    return '<g class="bangunan-svg" transform="translate(' . $x . ' ' . $y . ')" tabindex="0" role="button"'
        . ' aria-label="' . svg_teks('Detail bangunan ' . $bangunan['nama']) . '"'
        . ' data-nama="' . svg_teks($bangunan['nama']) . '"'
        . ' data-gang="' . svg_teks($bangunan['gang'] ?? '') . '"'
        . ' data-peminta="' . svg_teks($bangunan['diminta_oleh'] ?? '') . '"'
        . ' data-komentar="' . svg_teks($bangunan['komentar'] ?? '') . '"'
        . ' data-tanggal="' . svg_teks(format_tanggal((string) $bangunan['tanggal'])) . '"'
        . ' data-terbaru="' . ($terbaru ? '1' : '0') . '">'
        . '<title>' . svg_teks($bangunan['nama']) . '</title>'
        . '<g class="bangunan-dalam">' . gambar_bangunan($varian, $atap, (string) $bangunan['nama'], $terbaru) . '</g>'
        . '</g>';
}

// Penanda kavling yang belum ada bangunannya: petak bergaris putus-putus
// dengan papan kayu kecil bertuliskan Tanah Kosong.
function svg_tanah_kosong(array $posisi): string
{
    $x = (int) $posisi[0];
    $y = (int) $posisi[1];

    return '<g class="tanah-kosong" transform="translate(' . $x . ' ' . $y . ')">'
        . '<rect x="-58" y="-72" width="116" height="70" rx="12" fill="#ffffff" fill-opacity="0.16" stroke="#3f6212" stroke-opacity="0.45" stroke-width="2" stroke-dasharray="7 6"/>'
        . '<path d="M-38,-3 c1,-5 -1,-8 -3,-11 M-33,-3 c0,-5 2,-8 4,-10" stroke="#3f6212" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.55"/>'
        . '<path d="M36,-3 c1,-5 -1,-8 -3,-11 M41,-3 c0,-5 2,-8 4,-10" stroke="#3f6212" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.55"/>'
        . '<rect x="-2" y="-33" width="4" height="31" fill="#92600f"/>'
        . '<rect x="-41" y="-53" width="82" height="21" rx="5" fill="#fef3c7" stroke="#d97706" stroke-width="1.5"/>'
        . '<text x="0" y="-38.5" text-anchor="middle" font-size="11" font-weight="700" fill="#92400e">Tanah Kosong</text>'
        . '</g>';
}
