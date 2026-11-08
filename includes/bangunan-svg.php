<?php
// Gambar SVG untuk Peta Desa interaktif.
//
// Semua bangunan digambar dari bentuk dasar SVG (persegi, segitiga, lingkaran),
// tanpa aset gambar luar. Fungsi svg_bangunan() mengembalikan satu grup SVG
// (<g>) yang sudah berisi data detail bangunan sebagai atribut data-*;
// peta.php menaruh grup itu di titik kavlingnya dan skrip kecil di halaman
// yang sama membaca atributnya saat bangunan ditap.
//
// Sejak peta versi 3, gambar bangunan tetap memakai koordinat lokal versi 2
// (kanvas 1200 x 800), lalu svg_bangunan() dan svg_tanah_kosong() membungkusnya
// dengan grup skala SKALA_DUNIA supaya pas dengan dunia 2000 x 1333.
// Setiap bangunan juga memakai label nama melayang di atas atapnya, dan
// bangunan yang paling baru dibangun mendapat cincin denyut di kakinya.
//
// Aturan gambar:
// - Jenis bangunan dipilih dari kata kunci di nama bangunan:
//   kopi/warung jadi warung kopi, bengkel jadi bengkel, kue/roti/toko jadi
//   toko roti, salon/barber/cukur jadi salon, sisanya ruko biasa.
// - Warna atap mengikuti gang pemilik bangunan.
// - Balai Desa digambar khusus: lebih besar, atap amber, ada bendera.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/peta-posisi.php';

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
function varian_bangunan(string $nama, ?string $jenis = null): string
{
    // Ruko warga selalu digambar ruko (bentuknya disebut di template agar
    // tidak kebetulan terbaca warung hanya karena nama usahanya), sedangkan
    // fasilitas desa mengikuti kata kunci seperti biasa.
    if ($jenis === 'ruko') {
        return 'ruko';
    }
    $n = strtolower($nama);
    if (str_contains($n, 'balai desa')) {
        return 'balai';
    }
    if (str_contains($n, 'pembelajaran') || str_contains($n, 'belajar')) {
        // Varian sekolah digambar oleh gambar_bangunan() di berkas ini;
        // di peta 3D kata kuncinya mengarah ke varian sekolah juga.
        return 'sekolah';
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
function gambar_bangunan(string $varian, string $atap, string $nama, bool $terbaru, ?string $tautan = null): string
{
    $svg = '';

    if ($varian === 'balai') {
        // Balai Desa: tengara utama desa, digambar ulang versi 4.2 jauh
        // lebih besar dan ramai dari bangunan biasa supaya langsung jadi
        // jangkar visual alun-alun: atap amber bertumpuk dengan bubungan,
        // papan nama BALAI DESA besar di fasad, tiang bendera lebih tinggi,
        // balkon kayu, sepasang pilar, tangga batu, lampion menggantung,
        // pot bunga, dan semak berbunga di kedua sisi. Titik berdirinya
        // sama (tengah bawah di titik kavling), tidak berubah.
        $svg .= '<ellipse cx="0" cy="5" rx="128" ry="19" fill="#14532d" opacity="0.20"/>'
            . '<rect x="-84" y="-104" width="168" height="9" rx="3" fill="#d6c9a8"/>'
            . '<rect x="-92" y="-100" width="184" height="100" fill="#fff6df"/>'
            . '<rect x="-92" y="-100" width="184" height="100" fill="none" stroke="#e7d6ac" stroke-width="2"/>'
            . '<rect x="-92" y="-8" width="184" height="8" fill="#e9d9b0"/>'
            // Atap utama dan atap bawah, keduanya amber jenuh tengara.
            . '<polygon points="-106,-100 0,-156 106,-100" fill="#f59e0b"/>'
            . '<rect x="-106" y="-105" width="212" height="8" rx="4" fill="#000000" opacity="0.13"/>'
            . '<polygon points="-122,-72 -68,-104 68,-104 122,-72" fill="#d97706"/>'
            . '<rect x="-122" y="-77" width="244" height="7" rx="3.5" fill="#000000" opacity="0.10"/>'
            // Bubungan dan finial di puncak.
            . '<rect x="-4" y="-164" width="8" height="10" rx="3" fill="#b45309"/>'
            . '<circle cx="0" cy="-167" r="5.5" fill="#f59e0b" stroke="#b45309" stroke-width="1.5"/>'
            // Tiang bendera dan bendera desa.
            . '<line x1="0" y1="-156" x2="0" y2="-197" stroke="#78716c" stroke-width="4" stroke-linecap="round"/>'
            . '<polygon class="bendera-balai" points="0,-197 36,-187 0,-177" fill="#2563eb"/>'
            // Papan nama besar di fasad.
            . '<rect x="-72" y="-100" width="144" height="30" rx="5" fill="#fffdf5" stroke="#d97706" stroke-width="2.5"/>'
            . '<text x="0" y="-78.5" text-anchor="middle" font-size="21" font-weight="800" letter-spacing="1.5" fill="#92400e">BALAI DESA</text>'
            // Jendela berpanel di lantai atas dan bawah.
            . '<g fill="#bae6fd" stroke="#38bdf8" stroke-width="2">'
            . '<rect x="-80" y="-58" width="21" height="26" rx="2"/><rect x="-49" y="-58" width="21" height="26" rx="2"/>'
            . '<rect x="28" y="-58" width="21" height="26" rx="2"/><rect x="59" y="-58" width="21" height="26" rx="2"/>'
            . '<rect x="-80" y="-22" width="21" height="20" rx="2"/><rect x="59" y="-22" width="21" height="20" rx="2"/>'
            . '</g>'
            . '<g stroke="#e0f2fe" stroke-width="1.4">'
            . '<line x1="-69.5" y1="-58" x2="-69.5" y2="-32"/><line x1="-38.5" y1="-58" x2="-38.5" y2="-32"/>'
            . '<line x1="38.5" y1="-58" x2="38.5" y2="-32"/><line x1="69.5" y1="-58" x2="69.5" y2="-32"/>'
            . '</g>'
            // Balkon kayu di tengah fasad.
            . '<rect x="-30" y="-64" width="60" height="5" rx="2" fill="#b45309"/>'
            . '<g stroke="#b45309" stroke-width="2.4"><line x1="-26" y1="-64" x2="-26" y2="-76"/><line x1="-16" y1="-64" x2="-16" y2="-76"/><line x1="-6" y1="-64" x2="-6" y2="-76"/><line x1="6" y1="-64" x2="6" y2="-76"/><line x1="16" y1="-64" x2="16" y2="-76"/><line x1="26" y1="-64" x2="26" y2="-76"/></g>'
            . '<rect x="-31" y="-78" width="62" height="4" rx="2" fill="#d97706"/>'
            // Pilar dan pintu ganda.
            . '<rect x="-46" y="-28" width="8" height="28" rx="2" fill="#e7d6ac"/>'
            . '<rect x="38" y="-28" width="8" height="28" rx="2" fill="#e7d6ac"/>'
            . '<rect x="-21" y="-48" width="20" height="48" rx="3" fill="#b45309"/>'
            . '<rect x="1" y="-48" width="20" height="48" rx="3" fill="#b45309"/>'
            . '<rect x="-19" y="-44" width="16" height="16" rx="2" fill="#92400e"/>'
            . '<rect x="3" y="-44" width="16" height="16" rx="2" fill="#92400e"/>'
            . '<circle cx="-5" cy="-18" r="2.4" fill="#fde68a"/><circle cx="5" cy="-18" r="2.4" fill="#fde68a"/>'
            // Lampion menggantung di ujung atap bawah.
            . '<circle cx="-102" cy="-58" r="6.5" fill="#fbbf24" stroke="#d97706" stroke-width="1.5"/>'
            . '<circle cx="102" cy="-58" r="6.5" fill="#fbbf24" stroke="#d97706" stroke-width="1.5"/>'
            // Tangga batu lebar di depan pintu.
            . '<rect x="-40" y="-4" width="80" height="5" rx="2" fill="#cfc2a0"/>'
            . '<rect x="-48" y="1" width="96" height="5" rx="2" fill="#ddd2b4"/>'
            // Pot bunga dan semak berbunga mengapit tangga.
            . '<rect x="-66" y="-10" width="16" height="12" rx="2" fill="#b45309"/>'
            . '<circle cx="-58" cy="-14" r="9" fill="#22c55e"/><circle cx="-64" cy="-11" r="2.2" fill="#f472b6"/><circle cx="-52" cy="-16" r="2.2" fill="#facc15"/>'
            . '<rect x="50" y="-10" width="16" height="12" rx="2" fill="#b45309"/>'
            . '<circle cx="58" cy="-14" r="9" fill="#22c55e"/><circle cx="52" cy="-16" r="2.2" fill="#facc15"/><circle cx="64" cy="-11" r="2.2" fill="#f472b6"/>'
            . '<circle cx="-100" cy="-6" r="11" fill="#22c55e"/><circle cx="-93" cy="-11" r="8" fill="#4ade80"/>'
            . '<circle cx="100" cy="-6" r="11" fill="#22c55e"/><circle cx="93" cy="-11" r="8" fill="#4ade80"/>';
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
    if (($tautan ?? '') !== '') {
        // Ruko warga yang punya tautan jualan diberi tanda kecil di
        // dindingnya, supaya terlihat beda dari bangunan komentar.
        $svg .= '<g transform="translate(0 -62)">'
            . '<circle cx="0" cy="0" r="7" fill="#ffffff" stroke="#2563eb" stroke-width="1.5"/>'
            . '<path d="M-3 -1.5l3-3 3 3M0 -4.5v7" fill="none" stroke="#2563eb" stroke-width="1.6" stroke-linecap="round"/>'
            . '</g>';
    }
    return $svg;
}

// Label nama melayang di atas bangunan: pil putih kecil dengan nama usaha.
// Balai Desa cukup ditulis "Balai Desa" supaya labelnya ringkas; nama usaha
// yang panjang dipotong dengan elipsis supaya pil tidak melebar berlebihan.
// Koordinat lokal versi 2, digambar di dalam grup skala bangunan.
function label_bangunan_svg(string $varian, string $nama): string
{
    $teks = $varian === 'balai' ? 'Balai Desa' : $nama;
    if (strlen($teks) > 30) {
        $teks = substr($teks, 0, 29) . '…';
    }
    $lebar = (int) round(strlen($teks) * 7.0) + 20;
    $y = match ($varian) {
        'balai' => -208,
        'kopi' => -97,
        default => -109,
    };

    return '<g class="label-bangunan" transform="translate(0 ' . $y . ')">'
        . '<rect x="' . (-$lebar / 2) . '" y="-11" width="' . $lebar . '" height="22" rx="11" fill="#ffffff" fill-opacity="0.95" stroke="#cbd5e1" stroke-width="1"/>'
        . '<text x="0" y="4.5" text-anchor="middle" font-size="12.5" font-weight="600" fill="#334155">' . svg_teks($teks) . '</text>'
        . '</g>';
}

// Cincin denyut di kaki bangunan yang paling baru dibangun. Animasinya
// diatur di style.css lewat kelas cincin-baru.
function cincin_baru_svg(string $varian): string
{
    $rx = $varian === 'balai' ? 142 : 68;
    $ry = $varian === 'balai' ? 22 : 12;
    return '<ellipse class="cincin-baru" cx="0" cy="2" rx="' . $rx . '" ry="' . $ry . '" fill="none" stroke="#f59e0b" stroke-width="3"/>';
}

// Grup SVG lengkap satu bangunan di titik kavlingnya, lengkap dengan atribut
// data detail yang dibaca skrip peta saat bangunan ditap atau dipilih.
// Atribut data-x dan data-y menyimpan titik jangkar dunia bangunan,
// berguna sebagai acuan posisi popover detail.
function svg_bangunan(array $bangunan, array $posisi, bool $terbaru = false, ?int $kavling = null): string
{
    $varian = varian_bangunan((string) $bangunan['nama'], $bangunan['jenis'] ?? null);
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
        . ' data-jenis="' . svg_teks((string) ($bangunan['jenis'] ?? 'komentar')) . '"'
        . ' data-tautan="' . svg_teks((string) ($bangunan['tautan'] ?? '')) . '"'
        . ' data-tautan-teks="Kunjungi usaha"'
        . ' data-terbaru="' . ($terbaru ? '1' : '0') . '"'
        . ' data-balai="' . ($varian === 'balai' ? '1' : '0') . '"'
        . ' data-x="' . $x . '" data-y="' . $y . '"'
        . ($kavling === null ? '' : ' data-kavling="' . $kavling . '"')
        . '>'
        . '<title>' . svg_teks($bangunan['nama']) . '</title>'
        . '<g class="bangunan-skala" transform="scale(' . SKALA_DUNIA . ')">'
        . ($terbaru ? cincin_baru_svg($varian) : '')
        . '<g class="bangunan-dalam">' . gambar_bangunan($varian, $atap, (string) $bangunan['nama'], $terbaru, (string) ($bangunan['tautan'] ?? '')) . '</g>'
        . label_bangunan_svg($varian, (string) $bangunan['nama'])
        . '</g>'
        . '</g>';
}

// Penanda kavling yang belum ada bangunannya: sejak versi 4.3 bukan lagi
// petak bergaris putus-putus, melainkan lahan kosong yang terbaca sebagai
// clearing: tambalan tanah lembut berbentuk elips bertumpuk tanpa garis
// tepi sama sekali, dengan papan kayu kecil bertuliskan Tanah Kosong.
// Gambarnya memakai koordinat lokal versi 2 lalu diskala ke dunia
// versi 3, sama seperti bangunan.
function svg_tanah_kosong(array $posisi): string
{
    $x = (int) $posisi[0];
    $y = (int) $posisi[1];

    return '<g class="tanah-kosong" transform="translate(' . $x . ' ' . $y . ')">'
        . '<g class="tanah-skala" transform="scale(' . SKALA_DUNIA . ')">'
        . '<ellipse cx="0" cy="-30" rx="74" ry="45" fill="#fef9c3" fill-opacity="0.20"/>'
        . '<ellipse cx="-4" cy="-28" rx="65" ry="38" fill="#e7d6ac" fill-opacity="0.38"/>'
        . '<ellipse cx="7" cy="-36" rx="41" ry="24" fill="#efe3c0" fill-opacity="0.35"/>'
        . '<ellipse cx="-34" cy="-14" rx="6" ry="3.4" fill="#d6d3d1" opacity="0.8"/>'
        . '<ellipse cx="40" cy="-20" rx="4.6" ry="2.8" fill="#d6d3d1" opacity="0.7"/>'
        . '<path d="M-38,-3 c1,-5 -1,-8 -3,-11 M-33,-3 c0,-5 2,-8 4,-10" stroke="#65a30d" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5"/>'
        . '<path d="M36,-3 c1,-5 -1,-8 -3,-11 M41,-3 c0,-5 2,-8 4,-10" stroke="#65a30d" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5"/>'
        . '<rect x="-2" y="-33" width="4" height="31" fill="#92600f"/>'
        . '<rect x="-41" y="-53" width="82" height="21" rx="5" fill="#fef3c7" stroke="#d97706" stroke-width="1.5"/>'
        . '<text x="0" y="-38.5" text-anchor="middle" font-size="11" font-weight="700" fill="#92400e">Tanah Kosong</text>'
        . '</g>'
        . '</g>';
}

// Jalur gumpalan organik untuk petak zona gang: titik-titik di sekeliling
// bentuk superelips (persegi membulat) digeser radial secara acak semu
// namun deterministik dari benih nama gang, lalu dihubungkan dengan kurva
// kuadratik lewat titik tengah antar titik, sehingga tepinya halus dan
// tidak menyisakan garis batas tegas. Tanpa stroke sama sekali; zona
// terbaca murni dari isian warna gang yang lembut.
function jalur_gumpal_zona(float $x, float $y, float $w, float $h, int $benih): string
{
    $cx = $x + $w / 2;
    $cy = $y + $h / 2;
    $n = 16;
    $titik = [];
    for ($i = 0; $i < $n; $i++) {
        $sudut = ($i / $n) * 2 * M_PI;
        $c = cos($sudut);
        $s = sin($sudut);
        $px = $cx + ($w / 2) * ($c < 0 ? -1.0 : 1.0) * pow(abs($c), 0.55);
        $py = $cy + ($h / 2) * ($s < 0 ? -1.0 : 1.0) * pow(abs($s), 0.55);
        $acak = sin($benih * 127.1 + $i * 311.7) * 43758.5453;
        $acak -= floor($acak);
        $faktor = 0.96 + $acak * 0.08;
        $titik[] = [$cx + ($px - $cx) * $faktor, $cy + ($py - $cy) * $faktor];
    }
    $fmt = static fn(float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    $awalX = ($titik[$n - 1][0] + $titik[0][0]) / 2;
    $awalY = ($titik[$n - 1][1] + $titik[0][1]) / 2;
    $d = 'M' . $fmt($awalX) . ',' . $fmt($awalY);
    for ($i = 0; $i < $n; $i++) {
        $berikut = $titik[($i + 1) % $n];
        $tengahX = ($titik[$i][0] + $berikut[0]) / 2;
        $tengahY = ($titik[$i][1] + $berikut[1]) / 2;
        $d .= ' Q' . $fmt($titik[$i][0]) . ',' . $fmt($titik[$i][1]) . ' ' . $fmt($tengahX) . ',' . $fmt($tengahY);
    }
    return $d . ' Z';
}

// Zona gang versi 4: petak tanah berpenanda warna gang plus papan kayu
// nama gang di tepi atasnya. Kalau ada warga yang belum kebagian tempat
// berdiri (grid zona penuh atau batas render tercapai), papan menambah
// satu baris "+N warga lainnya". Koordinat lokal versi 2, dibungkus grup
// skala SKALA_DUNIA seperti elemen peta lainnya.
function svg_zona_gang(string $gang, array $zona, int $totalWarga, int $berdiri): string
{
    $warna = warna_atap_gang($gang);
    [$zx, $zy, $zw, $zh] = $zona['tambak'];
    [$px, $py] = $zona['papan'];
    $lebih = $totalWarga - $berdiri;
    $tinggiPapan = $lebih > 0 ? 36 : 24;
    $atasPapan = $py - (int) ($tinggiPapan / 2);

    // Sejak versi 4.3 petak zona bukan persegi bergaris tepi, melainkan
    // gumpalan organik tanpa garis batas: warna gang tetap terbaca dari
    // isiannya, tapi tepinya mengalir seperti semak lahan lingkungan.
    $jalurZona = jalur_gumpal_zona($zx, $zy, $zw, $zh, abs(crc32($gang)));

    $svg = '<g class="zona-gang"><g transform="scale(' . SKALA_DUNIA . ')">'
        . '<path d="' . $jalurZona . '" fill="' . $warna . '" fill-opacity="0.12"/>'
        . '<rect x="' . ($px - 44) . '" y="' . ($atasPapan + $tinggiPapan) . '" width="4" height="12" fill="#92600f"/>'
        . '<rect x="' . ($px + 40) . '" y="' . ($atasPapan + $tinggiPapan) . '" width="4" height="12" fill="#92600f"/>'
        . '<rect class="zona-papan" x="' . ($px - 66) . '" y="' . $atasPapan . '" width="132" height="' . $tinggiPapan . '" rx="6" fill="#fef3c7" stroke="#d97706" stroke-width="1.5"/>';

    if ($lebih > 0) {
        $svg .= '<text x="' . $px . '" y="' . ($py - 1) . '" text-anchor="middle" font-size="11.5" font-weight="700" fill="#92400e">' . svg_teks($gang) . '</text>'
            . '<text x="' . $px . '" y="' . ($py + 12) . '" text-anchor="middle" font-size="9.5" font-weight="600" fill="#b45309">+' . $lebih . ' warga lainnya</text>';
    } else {
        $svg .= '<text x="' . $px . '" y="' . ($py + 4) . '" text-anchor="middle" font-size="11.5" font-weight="700" fill="#92400e">' . svg_teks($gang) . '</text>';
    }

    return $svg . '</g></g>';
}

// Avatar satu warga yang berdiri di zona gang-nya, versi 4. Gambar orang
// sederhana dari bentuk dasar: kepala bulat, badan berwarna gang, dan
// label nama depan di bawah kakinya. $x dan $y adalah titik kaki avatar
// dalam koordinat lokal versi 2; grup luar menaruhnya di koordinat dunia.
// Atribut data-* dibaca popover peta, sama seperti bangunan.
// Sejak versi 4.3 avatar ikut sistem wara-wiri di peta.php: grup luar
// adalah pegangan posisi yang digerakkan skrip (data-rumah-x dan
// data-rumah-y adalah titik berdiri awal dalam koordinat dunia,
// data-zona nama gang pemiliknya), sedangkan sosok dan labelnya dibungkus
// grup .warga-bob yang beranimasi CSS naik turun halus (bob diam) dengan
// tunda dan durasi berbeda per warga supaya desa tidak bergerak serempak.
function svg_warga(array $warga, float $x, float $y): string
{
    // Baju memakai warna pilihan warga sendiri (dari KTP). Warga lama yang
    // belum memilih -- warna kosong di basis data -- tetap memakai warna gang
    // dan warna kulit/topi bawaan seperti versi sebelumnya.
    $warna = warna_atap_gang($warga['gang'] ?? null);
    $warnaKulit = '#f6c48a';
    $warnaTopi = '#40342a';
    if (($warga['warna_baju'] ?? '') !== '') {
        $warna = hex_warna($warga, 'baju');
    }
    if (($warga['warna_kulit'] ?? '') !== '') {
        $warnaKulit = hex_warna($warga, 'kulit');
    }
    if (($warga['warna_topi'] ?? '') !== '') {
        $warnaTopi = hex_warna($warga, 'topi');
    }
    $nama = (string) $warga['nama'];
    $topiSvg = match ($warga['bentuk_topi'] ?? 'pet') {
        'peci' => '<rect x="-10" y="-59" width="20" height="12" rx="2" fill="' . $warnaTopi . '"/>',
        'caping' => '<path d="M-20,-47 L0,-65 L20,-47 Z" fill="' . $warnaTopi . '"/>',
        'rambut' => '<path d="M-10,-44 Q-13,-62 0,-58 Q13,-62 10,-44 L5,-51 L0,-48 L-5,-51 Z" fill="#292524"/>',
        default => '<path d="M-9.5,-45 a9.5,9.5 0 0 1 19,0 Z" fill="' . $warnaTopi . '"/>',
    };
    $kata = preg_split('/\s+/', trim($nama)) ?: [];
    $pertama = ($kata[0] ?? '') !== '' ? $kata[0] : $nama;
    if (function_exists('mb_strlen') && mb_strlen($pertama, 'UTF-8') > 10) {
        $pertama = mb_substr($pertama, 0, 10, 'UTF-8');
    } elseif (strlen($pertama) > 10) {
        $pertama = substr($pertama, 0, 10);
    }
    $lebarLabel = (int) round(strlen($pertama) * 6.4) + 12;
    $duniaX = (int) round($x * SKALA_DUNIA);
    $duniaY = (int) round($y * SKALA_DUNIA);
    // Irama bob per warga dari id-nya: deterministik, tidak serempak.
    $idWarga = (int) $warga['id'];
    $tundaBob = number_format(-((($idWarga * 137) % 40) / 10), 1, '.', '');
    $durasiBob = number_format(3.6 + (($idWarga * 89) % 13) / 10, 1, '.', '');

    return '<g class="warga-svg" transform="translate(' . $duniaX . ' ' . $duniaY . ')" tabindex="0" role="button"'
        . ' aria-label="' . svg_teks('Warga ' . $nama) . '"'
        . ' data-jenis="warga"'
        . ' data-nama="' . svg_teks($nama) . '"'
        . ' data-gang="' . svg_teks($warga['gang'] ?? '') . '"'
        . ' data-zona="' . svg_teks($warga['gang'] ?? '') . '"'
        . ' data-rumah-x="' . $duniaX . '"'
        . ' data-rumah-y="' . $duniaY . '"'
        . ' data-nomor="' . svg_teks(nomor_warga((int) $warga['id'])) . '"'
        . ' data-tanggal="' . svg_teks(format_tanggal((string) $warga['created_at'])) . '">'
        . '<title>' . svg_teks($nama) . '</title>'
        . '<g transform="scale(' . SKALA_DUNIA . ')">'
        . '<ellipse cx="0" cy="2" rx="13" ry="3.5" fill="#14532d" opacity="0.18"/>'
        . '<g class="warga-bob" style="animation-delay:' . $tundaBob . 's;animation-duration:' . $durasiBob . 's">'
        . '<g class="warga-dalam">'
        . '<rect x="-8" y="-13" width="6" height="13" fill="#334155"/>'
        . '<rect x="2" y="-13" width="6" height="13" fill="#334155"/>'
        . '<path d="M-11,-29 L-16,-17 M11,-29 L16,-17" stroke="' . $warna . '" stroke-width="4.5" stroke-linecap="round" fill="none"/>'
        . '<rect x="-11" y="-36" width="22" height="25" rx="7" fill="' . $warna . '"/>'
        . '<rect x="-11" y="-36" width="22" height="6" rx="3" fill="#ffffff" opacity="0.28"/>'
        . '<circle cx="0" cy="-45" r="9.5" fill="' . $warnaKulit . '"/>'
        . $topiSvg
        . '<circle cx="-3.5" cy="-43.5" r="1" fill="#1e293b"/>'
        . '<circle cx="3.5" cy="-43.5" r="1" fill="#1e293b"/>'
        . '</g>'
        . '<g class="label-warga">'
        . '<rect x="' . (-$lebarLabel / 2) . '" y="7" width="' . $lebarLabel . '" height="14" rx="7" fill="#ffffff" fill-opacity="0.92" stroke="#cbd5e1" stroke-width="0.75"/>'
        . '<text x="0" y="17.5" text-anchor="middle" font-size="10.5" font-weight="600" fill="#334155">' . svg_teks($pertama) . '</text>'
        . '</g>'
        . '</g>'
        . '</g>'
        . '</g>';
}
