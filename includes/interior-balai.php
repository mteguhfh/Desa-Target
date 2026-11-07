<?php
// Interior Balai Desa versi 4: ruang Kantor Desa yang tampil menggantikan
// dunia peta saat warga masuk Balai Desa dari mode Keliling Desa.
//
// Satu layar statis bergaya gambar datar: lantai kayu hangat, meja layanan
// tempat Bang Uju berdiri, dan empat titik interaktif:
// - Loket KTP Warga: tautan ke ktp.php.
// - Papan Pengumuman: membuka popover peta berisi bangunan terbaru dan
//   aturan loop harian desa (data bangunan terbaru dari database).
// - Arsip Dokumen Warga: tautan ke https://www.kejartarget.id, kantor
//   desanya Desa Target.
// - Bang Uju: membuka popover berisi kalimat andalannya.
// Titik yang membuka popover memakai kelas hotspot-interior dan atribut
// data-* yang sama seperti bangunan di peta; titik tautan memakai elemen
// <a> SVG biasa. Skrip di peta.php yang menanganinya.

// Teks isi Papan Pengumuman dari bangunan terbaru di database.
function interior_balai_svg(?array $bangunanTerbaru): string
{
    $namaTerbaru = $bangunanTerbaru !== null
        ? (string) $bangunanTerbaru['nama']
        : 'Balai Desa (Kantor Desa Kejar Target)';

    $svg = '<svg class="interior-svg" viewBox="0 0 1200 750" preserveAspectRatio="xMidYMid slice" role="group" aria-label="Dalam Balai Desa, Kantor Desa Target. Ada loket KTP warga, papan pengumuman, arsip dokumen, dan Bang Uju di meja layanan.">';

    // Dinding, lis kayu, dan lantai papan.
    $svg .= '<rect x="0" y="0" width="1200" height="470" fill="#fdeeda"/>'
        . '<rect x="0" y="340" width="1200" height="130" fill="#efd3ac"/>'
        . '<rect x="0" y="336" width="1200" height="6" fill="#d9b77c"/>'
        . '<rect x="0" y="470" width="1200" height="280" fill="#c98a4b"/>'
        . '<g stroke="#b57a3e" stroke-width="2" opacity="0.6">'
        . '<line x1="0" y1="516" x2="1200" y2="516"/><line x1="0" y1="562" x2="1200" y2="562"/>'
        . '<line x1="0" y1="608" x2="1200" y2="608"/><line x1="0" y1="654" x2="1200" y2="654"/>'
        . '<line x1="0" y1="700" x2="1200" y2="700"/></g>'
        . '<rect x="0" y="462" width="1200" height="8" fill="#000000" opacity="0.06"/>';

    // Bendera hias kecil di langit-langit.
    $svg .= '<path d="M0,16 Q300,84 600,42 T1200,24" fill="none" stroke="#d97706" stroke-width="2"/>';
    $warnaHias = ['#2563eb', '#f59e0b', '#22c55e', '#db2777'];
    $titikHias = [[60, 34], [180, 52], [300, 64], [420, 62], [540, 50], [660, 44], [780, 44], [900, 40], [1020, 32], [1130, 26]];
    foreach ($titikHias as $i => [$hx, $hy]) {
        $svg .= '<polygon points="' . $hx . ',' . $hy . ' ' . ($hx + 34) . ',' . ($hy - 3) . ' ' . ($hx + 17) . ',' . ($hy + 27) . '" fill="' . $warnaHias[$i % 4] . '"/>';
    }

    // Papan nama Kantor Desa yang digantung di dinding.
    $svg .= '<line x1="520" y1="0" x2="520" y2="56" stroke="#92600f" stroke-width="3"/>'
        . '<line x1="680" y1="0" x2="680" y2="56" stroke="#92600f" stroke-width="3"/>'
        . '<rect x="465" y="56" width="270" height="46" rx="9" fill="#92600f"/>'
        . '<text x="600" y="86" text-anchor="middle" font-size="24" font-weight="700" letter-spacing="2" fill="#fff7e6">KANTOR DESA</text>';

    // Jam dinding di antara papan pengumuman dan jendela kanan.
    $svg .= '<circle cx="648" cy="196" r="27" fill="#ffffff" stroke="#b45309" stroke-width="4"/>'
        . '<line x1="648" y1="196" x2="648" y2="181" stroke="#334155" stroke-width="3" stroke-linecap="round"/>'
        . '<line x1="648" y1="196" x2="660" y2="203" stroke="#334155" stroke-width="3" stroke-linecap="round"/>'
        . '<circle cx="648" cy="196" r="3" fill="#b45309"/>';

    // Dua jendela dengan cahaya siang.
    foreach ([88, 972] as $jx) {
        $svg .= '<rect x="' . $jx . '" y="110" width="140" height="160" fill="#ffffff" stroke="#d9b77c" stroke-width="5"/>'
            . '<rect x="' . ($jx + 10) . '" y="120" width="120" height="140" fill="#bae6fd"/>'
            . '<line x1="' . ($jx + 70) . '" y1="120" x2="' . ($jx + 70) . '" y2="260" stroke="#ffffff" stroke-width="5"/>'
            . '<line x1="' . ($jx + 10) . '" y1="190" x2="' . ($jx + 130) . '" y2="190" stroke="#ffffff" stroke-width="5"/>'
            . '<circle cx="' . ($jx + 36) . '" cy="146" r="13" fill="#fde68a"/>'
            . '<rect x="' . ($jx - 6) . '" y="270" width="152" height="9" fill="#d9b77c"/>';
    }

    // Papan Pengumuman: papan gabus berisi catatan tertempel.
    $svg .= '<g class="hotspot-interior" tabindex="0" role="button" aria-label="Papan Pengumuman Balai Desa"'
        . ' data-jenis="papan" data-nama="Papan Pengumuman"'
        . ' data-komentar="' . svg_teks('Bangunan terbaru: ' . $namaTerbaru) . '"'
        . ' data-info="Setiap hari, komen dengan like terbanyak di Instagram @desatarget dibangun besoknya jadi bangunan baru di desa ini.">'
        . '<title>Papan Pengumuman</title>'
        . '<g class="hotspot-dalam">'
        . '<rect x="322" y="118" width="256" height="176" rx="8" fill="#b45309"/>'
        . '<rect x="334" y="130" width="232" height="152" fill="#f7e3b0"/>'
        . '<rect x="334" y="130" width="232" height="30" fill="#92400e"/>'
        . '<text x="450" y="150" text-anchor="middle" font-size="14.5" font-weight="700" letter-spacing="1" fill="#ffffff">PAPAN PENGUMUMAN</text>'
        . '<g transform="rotate(-4 384 200)"><rect x="352" y="176" width="64" height="48" fill="#ffffff"/><rect x="360" y="186" width="48" height="4" fill="#cbd5e1"/><rect x="360" y="196" width="48" height="4" fill="#cbd5e1"/><rect x="360" y="206" width="32" height="4" fill="#cbd5e1"/></g>'
        . '<circle cx="384" cy="180" r="3.5" fill="#dc2626"/>'
        . '<g transform="rotate(3 465 203)"><rect x="436" y="182" width="58" height="42" fill="#fef08a"/><rect x="444" y="192" width="42" height="4" fill="#d6c98a"/><rect x="444" y="202" width="42" height="4" fill="#d6c98a"/></g>'
        . '<circle cx="465" cy="186" r="3.5" fill="#2563eb"/>'
        . '<g transform="rotate(-2 534 200)"><rect x="508" y="174" width="52" height="52" fill="#ffffff"/><rect x="516" y="184" width="36" height="4" fill="#cbd5e1"/><rect x="516" y="194" width="36" height="4" fill="#cbd5e1"/><rect x="516" y="204" width="24" height="4" fill="#cbd5e1"/></g>'
        . '<circle cx="534" cy="178" r="3.5" fill="#16a34a"/>'
        . '<rect x="366" y="304" width="168" height="24" rx="12" fill="#ffffff" fill-opacity="0.95" stroke="#cbd5e1"/>'
        . '<text x="450" y="320" text-anchor="middle" font-size="13" font-weight="600" fill="#334155">Papan Pengumuman</text>'
        . '</g></g>';

    // Loket KTP Warga: bilik layanan di sisi kiri, tautan ke ktp.php.
    $svg .= '<a href="ktp.php" class="hotspot-tautan" aria-label="Loket KTP Warga, buka formulir KTP Warga">'
        . '<g class="hotspot-interior"><title>Loket KTP Warga</title><g class="hotspot-dalam">'
        . '<rect x="64" y="436" width="10" height="128" fill="#92600f"/>'
        . '<rect x="316" y="436" width="10" height="128" fill="#92600f"/>'
        . '<rect x="44" y="392" width="312" height="44" rx="8" fill="#2563eb"/>'
        . '<text x="200" y="420" text-anchor="middle" font-size="17" font-weight="700" letter-spacing="1" fill="#ffffff">LOKET KTP WARGA</text>'
        . '<rect x="54" y="560" width="332" height="26" rx="5" fill="#b45309"/>'
        . '<rect x="64" y="586" width="312" height="86" fill="#cd8b4a"/>'
        . '<g stroke="#b57a3e" stroke-width="2"><line x1="142" y1="586" x2="142" y2="672"/><line x1="220" y1="586" x2="220" y2="672"/><line x1="298" y1="586" x2="298" y2="672"/></g>'
        . '<g transform="translate(150 500)"><rect x="0" y="0" width="44" height="54" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5"/><polygon points="32,0 44,12 32,12" fill="#dbeafe"/><rect x="7" y="20" width="30" height="4" fill="#cbd5e1"/><rect x="7" y="29" width="30" height="4" fill="#cbd5e1"/><rect x="7" y="38" width="20" height="4" fill="#cbd5e1"/><text x="22" y="14" text-anchor="middle" font-size="9" font-weight="700" fill="#2563eb">KTP</text></g>'
        . '<rect x="212" y="528" width="40" height="28" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5"/>'
        . '<rect x="218" y="520" width="40" height="28" fill="#eff6ff" stroke="#cbd5e1" stroke-width="1.5"/>'
        . '</g></g></a>';

    // Arsip Dokumen Warga: lemari arsip di sisi kanan, tautan ke kantor desa.
    $svg .= '<a href="https://www.kejartarget.id" target="_blank" rel="noopener" class="hotspot-tautan" aria-label="Arsip Dokumen Warga, buka situs Kejar Target">'
        . '<g class="hotspot-interior"><title>Arsip Dokumen Warga</title><g class="hotspot-dalam">'
        . '<rect x="852" y="272" width="276" height="40" rx="8" fill="#0f766e"/>'
        . '<text x="990" y="297" text-anchor="middle" font-size="15" font-weight="700" letter-spacing="1" fill="#ffffff">ARSIP DOKUMEN WARGA</text>'
        . '<rect x="872" y="318" width="236" height="322" rx="6" fill="#8a5a2b"/>';
    foreach ([336, 438, 540] as $ly) {
        $svg .= '<rect x="888" y="' . $ly . '" width="204" height="86" fill="#a06a35" stroke="#7c4a21" stroke-width="2"/>'
            . '<rect x="900" y="' . ($ly + 12) . '" width="44" height="16" fill="#fef3c7"/>'
            . '<rect x="962" y="' . ($ly + 38) . '" width="56" height="10" rx="5" fill="#fde68a"/>';
    }
    $svg .= '</g></g></a>';

    // Karpet di bawah meja layanan.
    $svg .= '<ellipse cx="600" cy="676" rx="272" ry="36" fill="#a16207" opacity="0.30"/>';

    // Bang Uju berdiri di belakang meja layanan, bisa ditap.
    $svg .= '<g class="hotspot-interior" tabindex="0" role="button" aria-label="Bang Uju, Kepala Desa Target"'
        . ' data-jenis="kades" data-nama="Bang Uju"'
        . ' data-komentar="Ikutin Bang Uju, biar sama-sama sampai tujuan!"'
        . ' data-info="Kepala Desa Target, bertugas di Balai Desa ini. Urusan surat dan dokumen warga dibantu lewat loket KTP dan arsip dokumen di kantor ini."'
        . ' data-tautan="' . svg_teks(APP_INSTAGRAM) . '" data-tautan-teks="Ikut komen di Instagram @desatarget">'
        . '<title>Bang Uju, Kepala Desa</title>'
        . '<g class="hotspot-dalam">'
        . '<rect x="538" y="316" width="124" height="25" rx="12.5" fill="#ffffff" fill-opacity="0.95" stroke="#cbd5e1"/>'
        . '<text x="600" y="333" text-anchor="middle" font-size="13" font-weight="600" fill="#334155">Bang Uju</text>'
        . '<image href="assets/bang-uju-peta.png" x="512" y="352" width="176" height="176" preserveAspectRatio="xMidYMax meet"/>'
        . '</g></g>';

    // Meja layanan utama di tengah ruangan, di depan Bang Uju.
    $svg .= '<rect x="428" y="540" width="344" height="110" fill="#b45309"/>'
        . '<g stroke="#92400e" stroke-width="2"><line x1="514" y1="546" x2="514" y2="644"/><line x1="600" y1="546" x2="600" y2="644"/><line x1="686" y1="546" x2="686" y2="644"/></g>'
        . '<rect x="416" y="524" width="368" height="22" rx="6" fill="#92600f"/>'
        . '<g transform="translate(452 492)"><rect x="0" y="6" width="46" height="26" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5"/><rect x="4" y="0" width="46" height="26" fill="#eff6ff" stroke="#cbd5e1" stroke-width="1.5"/></g>'
        . '<circle cx="742" cy="516" r="8" fill="#f59e0b" stroke="#b45309" stroke-width="2"/>';

    // Tanaman pot di dua sudut ruangan.
    foreach ([36, 1150] as $tx) {
        $svg .= '<polygon points="' . ($tx - 18) . ',648 ' . ($tx + 18) . ',648 ' . ($tx + 12) . ',690 ' . ($tx - 12) . ',690" fill="#b45309"/>'
            . '<circle cx="' . $tx . '" cy="626" r="18" fill="#22c55e"/>'
            . '<circle cx="' . ($tx - 16) . '" cy="638" r="12" fill="#4ade80"/>'
            . '<circle cx="' . ($tx + 16) . '" cy="638" r="12" fill="#16a34a"/>';
    }

    return $svg . '</svg>';
}
