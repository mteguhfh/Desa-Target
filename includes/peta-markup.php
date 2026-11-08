<?php
// Partial markup peta interaktif. Dimuat oleh peta.php setelah
// includes/header.php, dengan semua variabel dan fungsi dari
// includes/peta-data.php sudah tersedia.
?>

    <div class="peta-viewport" id="petaViewport">
        <div class="peta-dunia" id="petaDunia">
            <svg class="peta-svg" viewBox="0 0 2000 1333" role="group" aria-label="Peta ilustrasi Desa Target. Geser untuk menjelajah, tap bangunan untuk melihat detailnya.">
                <defs>
                    <linearGradient id="gradLahan" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#dcfce7"/>
                        <stop offset="1" stop-color="#bbf7d0"/>
                    </linearGradient>
                    <linearGradient id="gradTanah" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#bbf7d0"/>
                        <stop offset="1" stop-color="#86efac"/>
                    </linearGradient>
                    <linearGradient id="gradKolam" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#7dd3fc"/>
                        <stop offset="1" stop-color="#38bdf8"/>
                    </linearGradient>
                    <!-- Tekstur rumput: jumbai kecil berulang di atas gradasi
                         lahan. Opasitas rendah dan selnya kecil, jadi lahan
                         tidak lagi terasa satu warna rata tanpa memberatkan
                         gambar. -->
                    <pattern id="polaRumput" width="46" height="38" patternUnits="userSpaceOnUse">
                        <path d="M8,34 q1.5,-7 -2,-11 M13,35 q0,-6 3,-10" stroke="#4ade80" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.35"/>
                        <path d="M31,32 q1.5,-6 -1.5,-10 M36,33 q0,-5 2.5,-9" stroke="#22c55e" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.28"/>
                    </pattern>
                    <!-- Ubin paving alun-alun: kotak bergeser dengan garis tipis. -->
                    <pattern id="polaPaving" width="30" height="30" patternUnits="userSpaceOnUse">
                        <path d="M0,15 H30 M15,0 V15 M7,15 V30 M22,15 V30" stroke="#d8cbab" stroke-width="1.6" fill="none"/>
                    </pattern>
                    <!-- Filter bayangan lembut bersama, dipakai hemat pada
                         tengara dan papan nama saja supaya gambar tetap ringan. -->
                    <filter id="bayLembut" x="-30%" y="-30%" width="160%" height="160%">
                        <feDropShadow dx="0" dy="2.5" stdDeviation="2.2" flood-color="#14532d" flood-opacity="0.28"/>
                    </filter>
                    <!-- Kabut tipis buat gumpalan asap dapur, supaya tepinya
                         lembut seperti asap beneran. -->
                    <filter id="gangKabut" x="-40%" y="-40%" width="180%" height="180%">
                        <feGaussianBlur stdDeviation="1.6"/>
                    </filter>
                    <g id="pohon">
                        <rect x="-3" y="-14" width="6" height="14" fill="#92600f"/>
                        <circle cx="0" cy="-27" r="15" fill="#22c55e"/>
                        <circle cx="-11" cy="-19" r="10" fill="#4ade80"/>
                        <circle cx="11" cy="-19" r="10" fill="#16a34a"/>
                    </g>
                    <g id="pohonPinus">
                        <rect x="-2.5" y="-10" width="5" height="10" fill="#92600f"/>
                        <polygon points="0,-52 -13,-28 13,-28" fill="#16a34a"/>
                        <polygon points="0,-42 -15,-16 15,-16" fill="#22c55e"/>
                        <polygon points="0,-30 -16,-4 16,-4" fill="#15803d"/>
                    </g>
                    <g id="pohonBuah">
                        <rect x="-3" y="-13" width="6" height="13" fill="#92600f"/>
                        <circle cx="0" cy="-26" r="16" fill="#16a34a"/>
                        <circle cx="-11" cy="-18" r="10" fill="#22c55e"/>
                        <circle cx="11" cy="-18" r="10" fill="#22c55e"/>
                        <circle cx="-6" cy="-26" r="2.6" fill="#f97316"/>
                        <circle cx="7" cy="-31" r="2.6" fill="#f97316"/>
                        <circle cx="3" cy="-17" r="2.6" fill="#fb923c"/>
                    </g>
                    <g id="semak">
                        <circle cx="-8" cy="-4" r="7" fill="#4ade80"/>
                        <circle cx="6" cy="-5" r="9" fill="#22c55e"/>
                        <circle cx="15" cy="-3" r="6" fill="#4ade80"/>
                    </g>
                    <g id="semakBunga">
                        <circle cx="-8" cy="-4" r="7" fill="#4ade80"/>
                        <circle cx="6" cy="-5" r="9" fill="#22c55e"/>
                        <circle cx="15" cy="-3" r="6" fill="#4ade80"/>
                        <circle cx="-5" cy="-8" r="2" fill="#f472b6"/>
                        <circle cx="7" cy="-11" r="2" fill="#facc15"/>
                        <circle cx="14" cy="-6" r="2" fill="#f472b6"/>
                    </g>
                    <g id="batu">
                        <ellipse cx="0" cy="0" rx="9" ry="5" fill="#a8a29e"/>
                        <ellipse cx="-3" cy="-2" rx="5" ry="3" fill="#d6d3d1"/>
                    </g>
                    <g id="lampu">
                        <rect x="-1.6" y="-34" width="3.2" height="34" fill="#57534e"/>
                        <circle cx="0" cy="-38" r="5" fill="#fef08a" stroke="#d97706" stroke-width="1.6"/>
                    </g>
                    <g id="bangku">
                        <rect x="-11" y="-9" width="22" height="3.4" rx="1.6" fill="#b45309"/>
                        <rect x="-11" y="-4.5" width="22" height="3" rx="1.5" fill="#d97706"/>
                        <rect x="-9" y="-1.5" width="3" height="5" fill="#78716c"/>
                        <rect x="6" y="-1.5" width="3" height="5" fill="#78716c"/>
                    </g>
                </defs>

                <!-- Latar desa versi 2 digambar ulang apa adanya di dalam grup
                     skala, sehingga koordinat 1200 x 800 miliknya pas dengan
                     dunia 2000 x 1333 versi 3. -->
                <g class="peta-latar" transform="scale(<?= SKALA_DUNIA ?>)">
                    <!-- Lahan desa: gradasi dua nada bertemu di tengah, lalu
                         tekstur rumput diratakan di atas seluruh lahan. -->
                    <rect x="0" y="0" width="1200" height="800" fill="url(#gradLahan)"/>
                    <rect x="0" y="430" width="1200" height="370" fill="url(#gradTanah)" opacity="0.55"/>
                    <rect x="0" y="0" width="1200" height="800" fill="url(#polaRumput)"/>
                    <ellipse cx="240" cy="-35" rx="430" ry="115" fill="#a3e635" opacity="0.30"/>
                    <ellipse cx="985" cy="-55" rx="470" ry="125" fill="#84cc16" opacity="0.22"/>
                    <!-- Petak rumput terpotong rapi: tambalan lebih dalam
                         yang memberi ritme pada lahan terbuka. -->
                    <g fill="#4ade80" opacity="0.13">
                        <rect x="290" y="520" width="150" height="95" rx="18"/>
                        <rect x="660" y="600" width="170" height="105" rx="18"/>
                        <rect x="850" y="470" width="130" height="85" rx="16"/>
                        <rect x="90" y="560" width="130" height="80" rx="16"/>
                    </g>
                    <g fill="#22c55e" opacity="0.10">
                        <rect x="330" y="80" width="120" height="70" rx="16"/>
                        <rect x="880" y="600" width="120" height="80" rx="16"/>
                        <rect x="500" y="700" width="140" height="70" rx="16"/>
                    </g>
                    <g class="awan" fill="#ffffff" opacity="0.85">
                        <ellipse cx="150" cy="52" rx="42" ry="13"/>
                        <ellipse cx="182" cy="44" rx="30" ry="11"/>
                    </g>
                    <g class="awan awan-lambat" fill="#ffffff" opacity="0.85">
                        <ellipse cx="1015" cy="46" rx="46" ry="13"/>
                        <ellipse cx="1050" cy="38" rx="30" ry="10"/>
                    </g>
                    <g class="awan" style="animation-delay:-14s" fill="#ffffff" opacity="0.8">
                        <ellipse cx="588" cy="64" rx="36" ry="11"/>
                        <ellipse cx="614" cy="57" rx="24" ry="9"/>
                    </g>
                    <!-- Dua burung kecil melintasi langit desa sesekali. -->
                    <g class="burung" stroke="#64748b" stroke-width="2.2" fill="none" stroke-linecap="round">
                        <path d="M-6,0 q6,-6 12,0 q6,-6 12,0"/>
                    </g>
                    <g class="burung burung-2" stroke="#64748b" stroke-width="1.8" fill="none" stroke-linecap="round">
                        <path d="M-5,0 q5,-5 10,0 q5,-5 10,0"/>
                    </g>

                    <!-- Kolam di pojok kanan bawah: tepian berbayang, batu
                         di tepi, teratai, dan riak yang berdenyut. -->
                    <ellipse cx="1065" cy="726" rx="130" ry="59" fill="#d9c48d"/>
                    <ellipse cx="1065" cy="722" rx="128" ry="58" fill="#f4e7c3"/>
                    <ellipse cx="1065" cy="718" rx="112" ry="50" fill="url(#gradKolam)"/>
                    <ellipse cx="1065" cy="718" rx="112" ry="50" fill="none" stroke="#0284c7" stroke-opacity="0.35" stroke-width="3"/>
                    <ellipse cx="1028" cy="704" rx="46" ry="13" fill="#e0f2fe" opacity="0.7"/>
                    <use href="#batu" transform="translate(955 700) scale(0.8)"/>
                    <use href="#batu" transform="translate(1170 742) scale(1.1)"/>
                    <circle cx="1106" cy="726" r="5" fill="#22c55e"/>
                    <circle cx="1014" cy="731" r="5" fill="#22c55e"/>
                    <circle cx="1106" cy="726" r="1.8" fill="#f472b6"/>
                    <circle cx="1014" cy="731" r="1.8" fill="#f472b6"/>
                    <path d="M1040,738 a9,5 0 0 1 18,0 a9,5 0 0 1 -18,0" fill="#16a34a"/>
                    <circle cx="1049" cy="736" r="2" fill="#f9a8d4"/>
                    <ellipse cx="1090" cy="742" rx="7" ry="3.6" fill="#16a34a"/>
                    <ellipse class="riak" cx="1065" cy="716" rx="54" ry="22" fill="none" stroke="#f0f9ff" stroke-width="2.5"/>
                    <ellipse class="riak riak-2" cx="1065" cy="716" rx="54" ry="22" fill="none" stroke="#f0f9ff" stroke-width="2.5"/>

                    <!-- Jalan utama desa yang berkelok, kini dengan bahu
                         jalan dan garis tepi supaya terbaca sebagai jalan. -->
                    <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#d9c48d" stroke-width="66" stroke-linecap="round"/>
                    <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#e9d8a6" stroke-width="58" stroke-linecap="round"/>
                    <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#f4e7c3" stroke-width="45" stroke-linecap="round"/>
                    <path d="M-20,706 C180,646 320,562 520,588 C720,614 800,502 960,456 C1080,422 1150,362 1220,318" fill="none" stroke="#ffffff" stroke-width="3" stroke-dasharray="16 14" opacity="0.7" stroke-linecap="round"/>

                    <!-- Jalan tanah dari jalan utama ke pintu tiap zona
                         gang dan ke kavling yang jauh dari jalan. Semua
                         digambar sebelum zona dan kavling, murni hiasan
                         di bawah kaki warga dan bangunan. -->
                    <g fill="none" stroke="#e7d6ac" stroke-linecap="round">
                        <path d="M600,560 C600,470 602,300 600,196" stroke-width="15"/>
                        <path d="M902,512 C960,430 1010,300 1048,184" stroke-width="14"/>
                        <path d="M610,478 C610,512 608,542 604,576" stroke-width="24"/>
                        <path d="M255,560 C230,520 210,480 196,455" stroke-width="13"/>
                        <path d="M520,596 C480,540 460,480 448,428" stroke-width="12"/>
                        <path d="M985,470 C950,440 910,415 878,405" stroke-width="12"/>
                        <path d="M1080,432 C1120,450 1150,465 1178,478" stroke-width="12"/>
                        <path d="M650,610 C620,660 585,700 568,725" stroke-width="11"/>
                        <path d="M300,620 C290,690 283,740 282,795" stroke-width="11"/>
                        <path d="M935,470 C920,420 900,360 885,310" stroke-width="11"/>
                        <path d="M125,640 C160,690 190,740 210,790" stroke-width="11"/>
                        <path d="M590,640 C580,730 520,745 520,795" stroke-width="11"/>
                        <path d="M430,700 C425,735 422,760 420,782" stroke-width="10"/>
                        <path d="M520,640 C560,670 620,690 675,700" stroke-width="11"/>
                        <path d="M800,520 C830,580 845,630 852,690" stroke-width="11"/>
                        <path d="M1030,480 C1020,550 1005,620 985,668" stroke-width="11"/>
                        <path d="M860,520 C900,555 930,575 958,592" stroke-width="11"/>
                    </g>
                    <g fill="none" stroke="#d9c48d" stroke-width="2.2" stroke-linecap="round" opacity="0.8">
                        <path d="M600,560 C600,450 602,300 600,196"/>
                        <path d="M902,512 C960,420 1010,300 1048,184"/>
                        <path d="M520,596 C480,540 460,480 448,428"/>
                        <path d="M650,610 C620,660 585,700 568,725"/>
                    </g>

                    <!-- Alun-alun besar tempat Balai Desa berdiri: permukaan
                         paving berbingkai, sudutnya membulat mengikuti elips. -->
                    <ellipse cx="610" cy="428" rx="182" ry="62" fill="#e3d5ae"/>
                    <ellipse cx="610" cy="428" rx="176" ry="59" fill="#efe7d2"/>
                    <ellipse cx="610" cy="428" rx="176" ry="59" fill="url(#polaPaving)"/>
                    <ellipse cx="610" cy="428" rx="176" ry="59" fill="none" stroke="#d8cbab" stroke-width="2"/>

                    <!-- Pagar kayu rendah di sepanjang beberapa tepi zona
                         gang dan kavling, memberi rasa lingkungan yang
                         tertata tanpa menutupi papan nama. -->
                    <g stroke="#b45309" stroke-width="3" stroke-linecap="round">
                        <path d="M24,196 h212 M24,196 v-9 M76,196 v-9 M128,196 v-9 M180,196 v-9 M236,196 v-9" fill="none"/>
                        <path d="M24,462 h212 M24,462 v-9 M76,462 v-9 M128,462 v-9 M180,462 v-9 M236,462 v-9" fill="none"/>
                        <path d="M950,186 h152 M950,186 v-9 M988,186 v-9 M1026,186 v-9 M1064,186 v-9 M1102,186 v-9" fill="none"/>
                        <path d="M768,438 h158 M768,438 v-9 M807,438 v-9 M846,438 v-9 M885,438 v-9 M926,438 v-9" fill="none"/>
                        <path d="M122,610 h120 M122,610 v-9 M162,610 v-9 M202,610 v-9 M242,610 v-9" fill="none"/>
                    </g>
                    <!-- Lampu jalan di sepanjang jalan utama, mengapit
                         jalur kendaraan dari atas dan bawah kurva jalan. -->
                    <use href="#lampu" transform="translate(120 648)"/>
                    <use href="#lampu" transform="translate(330 556)"/>
                    <use href="#lampu" transform="translate(560 622)"/>
                    <use href="#lampu" transform="translate(770 512)"/>
                    <use href="#lampu" transform="translate(1005 420)"/>
                    <!-- Perabot alun-alun: bangku menghadap Balai Desa dan
                         papan pengumuman kayu di tepi barat plaza. -->
                    <use href="#bangku" transform="translate(505 462)"/>
                    <use href="#bangku" transform="translate(715 462)"/>
                    <g transform="translate(468 424)">
                        <ellipse cx="0" cy="2" rx="16" ry="4" fill="#14532d" opacity="0.16"/>
                        <rect x="-2" y="-16" width="4" height="18" fill="#92600f"/>
                        <rect x="-19" y="-38" width="38" height="24" rx="3" fill="#fef3c7" stroke="#d97706" stroke-width="1.8"/>
                        <rect x="-14" y="-32" width="12" height="8" fill="#ffffff"/>
                        <rect x="2" y="-32" width="12" height="8" fill="#fee2e2"/>
                        <rect x="-14" y="-21" width="12" height="5" fill="#dbeafe"/>
                        <rect x="2" y="-21" width="12" height="5" fill="#ffffff"/>
                    </g>
                    <!-- Petunjuk suasana di tiap zona gang: bentuk vektor
                         ringan di dalam zona, jauh dari grid tempat warga
                         berdiri dan dari papan nama. -->
                    <g transform="translate(540 236)">
                        <ellipse cx="18" cy="2" rx="30" ry="5" fill="#14532d" opacity="0.14"/>
                        <path d="M-8,0 V-26 M44,0 V-26" stroke="#92600f" stroke-width="4"/>
                        <path d="M-16,-26 L18,-40 L52,-26 Z" fill="#2563eb"/>
                        <path d="M-16,-26 L18,-40 L52,-26" fill="none" stroke="#1e40af" stroke-width="2"/>
                        <rect x="-10" y="-8" width="8" height="8" fill="#fbbf24"/>
                        <rect x="14" y="-8" width="8" height="8" fill="#f472b6"/>
                        <rect x="30" y="-8" width="8" height="8" fill="#38bdf8"/>
                    </g>
                    <g class="asap" transform="translate(1035 212)">
                        <rect x="-24" y="-4" width="48" height="6" rx="2" fill="#92400e"/>
                        <rect x="-16" y="-22" width="32" height="18" rx="3" fill="#ea580c"/>
                        <rect x="-9" y="-27" width="18" height="6" rx="2" fill="#9a3412"/>
                        <g class="asap-gumpal" fill="#ffffff">
                            <circle cx="-3" cy="-36" r="5" opacity="0.75"/>
                            <circle cx="3" cy="-46" r="6.5" opacity="0.6"/>
                            <circle cx="-2" cy="-58" r="8" opacity="0.45"/>
                        </g>
                    </g>
                    <g transform="translate(110 478)">
                        <ellipse cx="14" cy="2" rx="30" ry="5" fill="#14532d" opacity="0.14"/>
                        <rect x="-10" y="-16" width="48" height="12" rx="2" fill="#78716c"/>
                        <rect x="-6" y="-4" width="5" height="6" fill="#57534e"/>
                        <rect x="29" y="-4" width="5" height="6" fill="#57534e"/>
                        <circle cx="24" cy="-32" r="10" fill="none" stroke="#0891b2" stroke-width="3.4"/>
                        <g stroke="#0891b2" stroke-width="3" stroke-linecap="round">
                            <line x1="24" y1="-42" x2="24" y2="-37"/><line x1="24" y1="-27" x2="24" y2="-22"/>
                            <line x1="14" y1="-32" x2="19" y2="-32"/><line x1="29" y1="-32" x2="34" y2="-32"/>
                        </g>
                    </g>
                    <g transform="translate(214 108)">
                        <ellipse cx="6" cy="4" rx="26" ry="5" fill="#14532d" opacity="0.14"/>
                        <path d="M-8,2 a15,11 0 0 1 30,0 z" fill="#7c3aed"/>
                        <path d="M16,0 a12,9 0 0 1 24,0 z" fill="#8b5cf6"/>
                        <rect x="-24" y="-4" width="22" height="12" rx="2" fill="#d97706"/>
                        <rect x="-24" y="-4" width="22" height="4" fill="#fbbf24"/>
                        <path d="M-13,-4 c0,-6 3,-8 6,-9" stroke="#ffffff" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                    </g>
                    <g transform="translate(930 448)">
                        <ellipse cx="0" cy="2" rx="26" ry="5" fill="#14532d" opacity="0.14"/>
                        <rect x="-3" y="-30" width="6" height="32" fill="#92600f"/>
                        <ellipse cx="0" cy="-36" rx="17" ry="9" fill="none" stroke="#db2777" stroke-width="3"/>
                        <path d="M-17,-36 a17,9 0 0 0 34,0" fill="none" stroke="#f472b6" stroke-width="2"/>
                        <path d="M0,-45 v18 M-12,-41 l24,10 M12,-41 l-24,10" stroke="#f9a8d4" stroke-width="1.4"/>
                        <path d="M3,2 q10,4 16,-2" stroke="#db2777" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <circle cx="22" cy="0" r="2.6" fill="#db2777"/>
                    </g>

                    <!-- Pepohonan dan semak penghias desa, tiga rupa pohon
                         dengan ukuran bervariasi mengisi lahan terbuka. -->
                    <use href="#pohon" transform="translate(70 185)"/>
                    <use href="#pohon" transform="translate(520 115) scale(0.9)"/>
                    <g class="ayun"><use href="#pohon" transform="translate(715 285) scale(1.1)"/></g>
                    <use href="#pohon" transform="translate(985 120)"/>
                    <g class="ayun ayun-2"><use href="#pohon" transform="translate(305 95) scale(0.8)"/></g>
                    <use href="#pohon" transform="translate(55 470)"/>
                    <use href="#pohon" transform="translate(565 692)"/>
                    <use href="#pohon" transform="translate(845 758)"/>
                    <use href="#pohon" transform="translate(1175 190) scale(0.9)"/>
                    <use href="#pohon" transform="translate(90 762) scale(0.85)"/>
                    <use href="#pohonPinus" transform="translate(1140 300)"/>
                    <use href="#pohonPinus" transform="translate(410 660) scale(0.9)"/>
                    <g class="ayun"><use href="#pohonPinus" transform="translate(660 250) scale(1.15)"/></g>
                    <use href="#pohonPinus" transform="translate(1180 620) scale(0.85)"/>
                    <use href="#pohonPinus" transform="translate(330 470) scale(0.8)"/>
                    <use href="#pohonBuah" transform="translate(760 620)"/>
                    <use href="#pohonBuah" transform="translate(240 300) scale(0.9)"/>
                    <g class="ayun ayun-2"><use href="#pohonBuah" transform="translate(1085 300) scale(1.05)"/></g>
                    <use href="#pohonBuah" transform="translate(480 470) scale(0.85)"/>
                    <use href="#pohonBuah" transform="translate(1010 640) scale(0.9)"/>
                    <g class="ayun"><use href="#pohon" transform="translate(950 560) scale(0.95)"/></g>
                    <use href="#pohon" transform="translate(150 330) scale(0.75)"/>
                    <use href="#pohon" transform="translate(700 90) scale(0.85)"/>
                    <use href="#pohonPinus" transform="translate(180 700) scale(1.05)"/>
                    <use href="#pohonBuah" transform="translate(620 770) scale(0.95)"/>
                    <use href="#semak" transform="translate(430 330)"/>
                    <use href="#semak" transform="translate(790 150)"/>
                    <use href="#semak" transform="translate(185 415)"/>
                    <use href="#semak" transform="translate(660 748)"/>
                    <use href="#semak" transform="translate(1020 472)"/>
                    <use href="#semak" transform="translate(300 560) scale(0.9)"/>
                    <use href="#semak" transform="translate(880 700) scale(1.1)"/>
                    <g class="ayun ayun-kecil"><use href="#semak" transform="translate(540 300) scale(0.85)"/></g>
                    <use href="#semakBunga" transform="translate(455 425)"/>
                    <use href="#semakBunga" transform="translate(765 415) scale(0.9)"/>
                    <use href="#semakBunga" transform="translate(1120 520)"/>
                    <use href="#semakBunga" transform="translate(200 640) scale(0.9)"/>
                    <use href="#semakBunga" transform="translate(730 540)"/>
                    <use href="#batu" transform="translate(360 610)"/>
                    <use href="#batu" transform="translate(1145 560) scale(0.85)"/>
                    <use href="#batu" transform="translate(655 330) scale(1.15)"/>
                    <!-- Hamparan bunga kecil berwarna gang, menggantikan
                         titik bunga lama dengan kelompok yang lebih terasa. -->
                    <g>
                        <circle cx="255" cy="380" r="2.6" fill="#f472b6"/>
                        <circle cx="905" cy="330" r="2.6" fill="#facc15"/>
                        <circle cx="480" cy="120" r="2.6" fill="#f472b6"/>
                        <circle cx="720" cy="480" r="2.6" fill="#facc15"/>
                        <circle cx="120" cy="250" r="2.6" fill="#facc15"/>
                        <circle cx="1080" cy="90" r="2.6" fill="#f472b6"/>
                        <g fill="#2563eb"><circle cx="520" cy="212" r="2.4"/><circle cx="534" cy="218" r="2.4"/><circle cx="527" cy="206" r="2.4"/></g>
                        <g fill="#ea580c"><circle cx="905" cy="196" r="2.4"/><circle cx="919" cy="202" r="2.4"/><circle cx="912" cy="190" r="2.4"/></g>
                        <g fill="#0891b2"><circle cx="246" cy="330" r="2.4"/><circle cx="260" cy="336" r="2.4"/><circle cx="253" cy="324" r="2.4"/></g>
                        <g fill="#7c3aed"><circle cx="66" cy="206" r="2.4"/><circle cx="80" cy="212" r="2.4"/><circle cx="73" cy="200" r="2.4"/></g>
                        <g fill="#db2777"><circle cx="770" cy="452" r="2.4"/><circle cx="784" cy="458" r="2.4"/><circle cx="777" cy="446" r="2.4"/></g>
                        <g fill="#f472b6"><circle cx="340" cy="730" r="2.4"/><circle cx="354" cy="736" r="2.4"/><circle cx="347" cy="724" r="2.4"/></g>
                        <g fill="#facc15"><circle cx="880" cy="770" r="2.4"/><circle cx="894" cy="776" r="2.4"/><circle cx="887" cy="764" r="2.4"/></g>
                        <g fill="#f472b6"><circle cx="1060" cy="240" r="2.4"/><circle cx="1074" cy="246" r="2.4"/><circle cx="1067" cy="234" r="2.4"/></g>
                    </g>

                    <!-- Perlakuan tepi dunia versi 4.3: barisan pohon,
                         pinus, dan semak pagar di sela-sela kosong
                         sepanjang tepi lahan, supaya peta terbaca sebagai
                         sebidang tanah desa yang berakhir di barisan
                         pepohonan, bukan persegi yang terpotong kaku.
                         Posisi dipilih menghindari zona gang, papan nama,
                         mulut jalan di tepi kiri dan kanan, kavling, dan
                         kolam. -->
                    <g>
                        <use href="#semak" transform="translate(300 16) scale(1.1)"/>
                        <use href="#semak" transform="translate(348 11)"/>
                        <use href="#semak" transform="translate(396 16) scale(1.15)"/>
                        <use href="#semak" transform="translate(444 12)"/>
                        <use href="#pohon" transform="translate(478 48) scale(0.85)"/>
                        <use href="#semak" transform="translate(736 14) scale(1.1)"/>
                        <use href="#semak" transform="translate(788 10)"/>
                        <use href="#semak" transform="translate(842 15) scale(1.15)"/>
                        <use href="#semak" transform="translate(898 11)"/>
                        <use href="#pohon" transform="translate(14 226) scale(0.9)"/>
                        <use href="#pohonPinus" transform="translate(16 522) scale(0.95)"/>
                        <use href="#semak" transform="translate(11 612) scale(1.1)"/>
                        <use href="#pohon" transform="translate(1181 92) scale(0.9)"/>
                        <use href="#pohon" transform="translate(1185 238) scale(0.95)"/>
                        <use href="#semak" transform="translate(1183 432) scale(1.1)"/>
                        <use href="#pohonPinus" transform="translate(1187 548) scale(0.9)"/>
                        <use href="#pohon" transform="translate(60 790) scale(0.95)"/>
                        <use href="#pohonPinus" transform="translate(152 792) scale(0.9)"/>
                        <use href="#pohon" transform="translate(560 788)"/>
                        <use href="#semak" transform="translate(656 794) scale(1.15)"/>
                        <use href="#pohonBuah" transform="translate(862 786) scale(0.9)"/>
                    </g>
                </g>

                <!-- Zona gang versi 4: petak warna dan papan nama tiap gang,
                     digambar di bawah bangunan supaya tidak menutupi kavling. -->
                <?php foreach (ZONA_GANG as $gang => $zona): ?>
                    <?= svg_zona_gang($gang, $zona, $totalPerGang[$gang] ?? 0, count($wargaPerGang[$gang] ?? [])) ?>
                <?php endforeach; ?>

                <!-- Kavling: bangunan yang sudah berdiri dan tanah kosong -->
                <?php for ($i = 0; $i < $totalKavling; $i++): ?>
                    <?php if (isset($bangunan[$i])): ?>
                        <?= svg_bangunan($bangunan[$i], POSISI_KAVLING[$i], (int) $bangunan[$i]['id'] === $terbaruId, $i) ?>
                    <?php else: ?>
                        <?= svg_tanah_kosong(POSISI_KAVLING[$i]) ?>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Avatar para warga versi 4, berdiri di zona gang masing-masing. -->
                <?php foreach ($wargaPerGang as $daftarWarga): ?>
                    <?php foreach ($daftarWarga as $tempat): ?>
                        <?= svg_warga($tempat['warga'], $tempat['x'], $tempat['y']) ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Bang Uju, Kepala Desa, berdiri di samping Balai Desa.
                     Gambarnya memakai potongan transparan dari aset lambaian
                     Bang Uju supaya menyatu dengan rumput desa. Sejak versi
                     4.2 ia digambar lebih besar sebagai maskot jangkar
                     alun-alun, lengkap dengan panggung bundar kecil dan
                     bendera desa di belakangnya; titik berdiri dan target
                     tap-nya tidak berubah. Sejak versi 4.3 ia ikut
                     berkeliling: grup luarnya digerakkan sistem patroli
                     skrip halaman mengelilingi alun-alun, seperti Kepala
                     Desa yang sedang ronda, sementara lambaiannya tetap
                     hidup dari animasi grup dalamnya. -->
                <g class="bang-uju" id="bangUju" transform="translate(<?= $ujuX ?> <?= $ujuY ?>)" tabindex="0" role="button"
                   aria-label="Bang Uju, Kepala Desa Target"
                   data-jenis="kades" data-nama="Bang Uju"
                   data-komentar="Ikutin Bang Uju, biar sama-sama sampai tujuan!"
                   data-info="Komen paling banyak disukai hari ini, besoknya langsung Bang Uju bangun jadi bangunan baru di desa ini."
                   data-tautan="<?= e(APP_INSTAGRAM) ?>" data-tautan-teks="Ikut komen di Instagram @desatarget">
                    <title>Bang Uju, Kepala Desa</title>
                    <g class="bang-uju-bendera">
                        <rect x="34" y="-158" width="5" height="118" rx="2" fill="#92600f"/>
                        <polygon class="bendera-uju" points="39,-158 83,-146 39,-134" fill="#2563eb"/>
                        <circle cx="36.5" cy="-160" r="4" fill="#f59e0b"/>
                    </g>
                    <ellipse cx="0" cy="4" rx="62" ry="11" fill="#e3d5ae"/>
                    <ellipse cx="0" cy="4" rx="62" ry="11" fill="none" stroke="#cbbd93" stroke-width="2"/>
                    <ellipse cx="0" cy="3" rx="54" ry="9" fill="#14532d" opacity="0.16"/>
                    <g class="bang-uju-dalam">
                        <image href="assets/bang-uju-peta.png" x="-84" y="-168" width="168" height="168" preserveAspectRatio="xMidYMax meet"/>
                    </g>
                </g>

                <!-- Karakter pengunjung versi 4 buat mode Keliling Desa:
                     pejalan kecil bertopi merah dan beransel, sengaja beda
                     dari Bang Uju supaya pengunjung tidak mengira dirinya
                     Kepala Desa. Posisinya digerakkan skrip halaman ini.
                     Transform awal di markup sama persis dengan titik
                     munculnya di skrip (depan Balai Desa), jadi karakter
                     sudah berdiri di tempat yang benar sejak halaman
                     dimuat, tidak menunggu tick pertama skrip. -->
                <g id="karakterJalan" transform="translate(<?= $fokusX ?> <?= $fokusY + 88 ?>)" hidden>
                    <ellipse cx="0" cy="3" rx="21" ry="6" fill="#14532d" opacity="0.20"/>
                    <g id="karakterGoyang">
                        <g id="karakterBadan">
                            <rect x="-21" y="-56" width="13" height="24" rx="5" fill="#d97706"/>
                            <rect x="-21" y="-56" width="13" height="8" rx="4" fill="#b45309"/>
                            <rect x="-10" y="-16" width="8" height="16" rx="2" fill="#334155"/>
                            <rect x="3" y="-16" width="8" height="16" rx="2" fill="#334155"/>
                            <rect x="-19" y="-54" width="7" height="24" rx="3.5" fill="#0f766e"/>
                            <rect x="12" y="-54" width="7" height="24" rx="3.5" fill="#0f766e"/>
                            <rect x="-14" y="-58" width="28" height="44" rx="9" fill="#0d9488"/>
                            <rect x="-14" y="-58" width="28" height="10" rx="5" fill="#0f766e"/>
                            <circle cx="0" cy="-70" r="13" fill="#f6c48a"/>
                            <path d="M-13,-72 a13,13 0 0 1 26,0 Z" fill="#dc2626"/>
                            <rect x="-3" y="-74" width="22" height="5" rx="2.5" fill="#b91c1c"/>
                        </g>
                        <g class="karakter-label">
                            <rect x="-26" y="-112" width="52" height="21" rx="10.5" fill="#ffffff" fill-opacity="0.95" stroke="#cbd5e1"/>
                            <text x="0" y="-97.5" text-anchor="middle" font-size="13" font-weight="700" fill="#1d4ed8">Kamu</text>
                        </g>
                    </g>
                </g>
            </svg>
        </div>

        <!-- Bilah merek dan navigasi melayang di pojok kiri atas peta,
             pengganti kepala situs khusus halaman peta sepenuh layar. -->
        <div class="peta-merek-baris">
            <a class="peta-merek" href="index.php">
                <img src="assets/bang-uju.png" alt="" class="peta-merek-logo">
                <span>Desa <strong>Target</strong></span>
            </a>
            <nav class="peta-nav" aria-label="Navigasi peta">
                <a href="index.php">Beranda</a>
                <a href="ktp.php">Bikin KTP Warga</a>
                <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">Instagram</a>
                <?php // Chat antar gang (Fitur 2): isi panel bersayap yang sama. ?>
                <button type="button" class="peta-nav-chat" id="tombolChat2d" data-panel="chat">Chat</button>
            </nav>
        </div>

        <!-- Kartu pengantar di bawah bilah merek. Bisa ditutup dengan
             tombol silang; di ponsel dan layar pendek kartu mulai dalam
             keadaan tertutup lewat media query di stylesheet. -->
        <div class="peta-intro" id="petaIntro">
            <button type="button" class="popover-tutup" id="introTutup" aria-label="Tutup pengantar">&times;</button>
            <h1>Peta Desa Target</h1>
            <p>
                Setiap bangunan di peta ini lahir dari komentar warga di Instagram.
                Komentar paling banyak disukai hari ini, besok paginya sudah berdiri di sini.
                Masih banyak tanah kosong, desa ini menunggu usulan usahamu.
            </p>
        </div>
        <aside class="kabar-peta" id="kabarPeta" aria-label="Kabar Desa" hidden>
            <p><?= e(KABAR_PETA['teks']) ?> <a href="<?= e($tabAwalan . KABAR_PETA['tautan']) ?>"><?= e(KABAR_PETA['label']) ?></a></p>
            <button type="button" id="kabarPetaTutup" aria-label="Tutup kabar">&times;</button>
        </aside>

        <p class="peta-chip" id="petaChip">Geser buat keliling. Tap bangunan, atau jalan-jalan pakai tombol Keliling Desa.</p>

        <div class="peta-kontrol">
            <button type="button" id="tombolJalan" class="tombol-jalan" aria-pressed="false">Keliling Desa</button>
            <button type="button" id="tombolPanelGang" class="tombol-panel-gang" aria-expanded="false" aria-controls="panelGang">Papan Gang</button>
            <button type="button" id="zoomMasuk" aria-label="Perbesar peta">+</button>
            <button type="button" id="zoomKeluar" aria-label="Perkecil peta">&minus;</button>
            <button type="button" id="zoomReset" aria-label="Lihat seluruh desa" title="Lihat seluruh desa">&#8982;</button>
            <button type="button" id="layarPenuh" aria-label="Layar penuh">&#10530;</button>
        </div>

        <button type="button" class="chip-prompt" id="chipPrompt" hidden></button>

        <div class="peta-dpad" id="petaDpad" hidden aria-label="Tombol arah buat jalan-jalan">
            <span></span>
            <button type="button" data-arah="atas" aria-label="Jalan ke atas">&#9650;</button>
            <span></span>
            <button type="button" data-arah="kiri" aria-label="Jalan ke kiri">&#9664;</button>
            <span></span>
            <button type="button" data-arah="kanan" aria-label="Jalan ke kanan">&#9654;</button>
            <span></span>
            <button type="button" data-arah="bawah" aria-label="Jalan ke bawah">&#9660;</button>
            <span></span>
        </div>

        <div class="peta-popover" id="petaPopover" hidden role="dialog" aria-label="Detail di peta">
            <button type="button" class="popover-tutup" id="popoverTutup" aria-label="Tutup detail">&times;</button>
            <p class="label-kecil" id="popLabel">Detail Bangunan</p>
            <h3 id="popNama"></h3>
            <p class="peta-popover-lencana">
                <span class="lencana-gang" id="popGang" hidden></span>
                <span class="lencana-kades" id="popKades" hidden>Kepala Desa</span>
                <span class="lencana-baru" id="popBaru" hidden>Baru dibangun</span>
            </p>
            <p class="kartu-komentar" id="popKomentarBaris" hidden>&ldquo;<span id="popKomentar"></span>&rdquo;</p>
            <p class="kartu-meta" id="popPemintaBaris" hidden>Diminta oleh <strong id="popPeminta"></strong></p>
            <p class="kartu-meta" id="popNomorBaris" hidden>Warga <strong id="popNomor"></strong></p>
            <p class="kartu-meta" id="popTanggalBaris" hidden><span id="popTanggalKata">Berdiri</span> <span id="popTanggal"></span></p>
            <p class="pop-info" id="popInfo" hidden></p>
            <p class="pop-tautan" id="popTautanBaris" hidden><a id="popTautan" href="#" target="_blank" rel="noopener"></a></p>
        </div>

        <div class="peta-interior" id="petaInterior" hidden>
            <?= interior_balai_svg($bangunanTerbaru) ?>
            <button type="button" class="interior-keluar" id="tombolKeluarInterior">&larr; Keluar ke Peta</button>
        </div>

        <!-- Panel Papan Gang: meluncur dari kanan di dalam bingkai peta,
             dibuka dari tombol Papan Gang di tumpukan kontrol. Isinya sama
             dengan papan gang lama, hanya pindah tempat dari bawah peta. -->
        <aside class="panel-gang" id="panelGang" aria-hidden="true" aria-label="Papan peringkat gang">
            <div class="panel-gang-kepala">
                <h2>Papan Gang</h2>
                <button type="button" class="popover-tutup" id="panelGangTutup" aria-label="Tutup papan gang">&times;</button>
            </div>
            <p class="seksi-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
            <?php require __DIR__ . '/papan-gang.php'; ?>
            <p class="panel-gang-gabung"><a href="ktp.php">Gabung gang-mu</a></p>
        </aside>

        <!-- Tombol panah ke bawah: jalan keluar buat pengunjung menggulir
             halaman melewati peta yang memenuhi layar menuju konten di
             bawahnya. Disembunyikan stylesheet saat peta layar penuh. -->
        <button type="button" class="peta-gulir-bawah" id="tombolGulirBawah" aria-label="Lihat ke bawah">&#9660;</button>
    </div>
    <p class="peta-petunjuk">Seret peta buat keliling desa, pakai tombol plus dan minus buat zoom. Pencet tombol Keliling Desa buat jalan-jalan keliling desa dan masuk Balai Desa. Atap bangunan mengikuti gang-nya, sedangkan warna baju, kulit, dan topi warga dipilih sendiri saat bikin KTP.</p>

<script>
(function () {
    var viewport = document.getElementById('petaViewport');
    var dunia = document.getElementById('petaDunia');
    var popover = document.getElementById('petaPopover');
    if (!viewport || !dunia || !popover) {
        return;
    }

    // --- Kamera dunia: dunia 2000 x 1333 digeser dan di-zoom di dalam viewport.
    var DUNIA_W = 2000;
    var DUNIA_H = 1333;
    var K_MAX = 2.5;
    var FOKUS = { x: <?= $fokusX ?>, y: <?= $fokusY ?> }; // Balai Desa
    var k = 1;        // zoom saat ini, relatif terhadap skala cover (baseS)
    var tx = 0;       // geser kamera dalam piksel layar
    var ty = 0;
    var baseS = 1;    // piksel layar per satuan dunia pada zoom 1 (skala cover)
    var containS = 1; // skala contain: seluruh dunia pas di dalam viewport
    // Batas zoom bawah bersifat dinamis: rasio contain terhadap cover.
    // Di layar lebar tapi pendek, kMin < 1 mengizinkan zoom keluar sampai
    // seluruh desa terlihat. Dulu batasnya kaku di 1 (skala cover), jadi
    // zona gang di tepi atas tidak pernah bisa terlihat dan, karena dunia
    // menutup lebar viewport persis, geser horizontal terkunci di 0 piksel
    // sehingga peta terasa mati digeser.
    var kMin = 1;

    function hitungDasar() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        baseS = Math.max(w / DUNIA_W, h / DUNIA_H);
        containS = Math.min(w / DUNIA_W, h / DUNIA_H);
        kMin = containS / baseS;
        if (k < kMin) {
            k = kMin;
        }
        dunia.style.width = (DUNIA_W * baseS) + 'px';
        dunia.style.height = (DUNIA_H * baseS) + 'px';
    }

    function jepit() {
        var w = viewport.clientWidth;
        var h = viewport.clientHeight;
        var dw = DUNIA_W * baseS * k;
        var dh = DUNIA_H * baseS * k;
        // Sumbu yang dunianya lebih kecil dari viewport dipusatkan,
        // bukan dijepit ke rentang terbalik (yang hasilnya kacau);
        // sumbu yang lebih besar dijepit seperti biasa.
        if (dw <= w) {
            tx = (w - dw) / 2;
        } else {
            tx = Math.min(0, Math.max(w - dw, tx));
        }
        if (dh <= h) {
            ty = (h - dh) / 2;
        } else {
            ty = Math.min(0, Math.max(h - dh, ty));
        }
    }

    function terapkan() {
        jepit();
        dunia.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + k + ')';
    }

    // Zoom ke titik layar (px, py) yang relatif terhadap viewport,
    // titik dunia di bawahnya dijaga tetap di tempat yang sama.
    function zoomKe(kBaru, px, py) {
        kBaru = Math.min(K_MAX, Math.max(kMin, kBaru));
        var lx = (px - tx) / k;
        var ly = (py - ty) / k;
        k = kBaru;
        tx = px - lx * k;
        ty = py - ly * k;
        terapkan();
    }

    // Bingkai seluruh desa: zoom paling luar dan dunia dipusatkan,
    // jadi pengunjung pertama selalu melihat semua kavling dan zona gang.
    function lihatSeluruhDesa() {
        k = kMin;
        tx = (viewport.clientWidth - DUNIA_W * baseS * k) / 2;
        ty = (viewport.clientHeight - DUNIA_H * baseS * k) / 2;
        terapkan();
    }

    // --- Popover detail melayang di dekat elemen yang ditap.
    var popLabel = document.getElementById('popLabel');
    var popNama = document.getElementById('popNama');
    var popGang = document.getElementById('popGang');
    var popKades = document.getElementById('popKades');
    var popBaru = document.getElementById('popBaru');
    var popKomentarBaris = document.getElementById('popKomentarBaris');
    var popKomentar = document.getElementById('popKomentar');
    var popPemintaBaris = document.getElementById('popPemintaBaris');
    var popPeminta = document.getElementById('popPeminta');
    var popNomorBaris = document.getElementById('popNomorBaris');
    var popNomor = document.getElementById('popNomor');
    var popTanggalBaris = document.getElementById('popTanggalBaris');
    var popTanggalKata = document.getElementById('popTanggalKata');
    var popTanggal = document.getElementById('popTanggal');
    var popInfo = document.getElementById('popInfo');
    var popTautanBaris = document.getElementById('popTautanBaris');
    var popTautan = document.getElementById('popTautan');

    function tutupPopover() {
        popover.hidden = true;
        // Sistem wara-wiri versi 4.3 membaca variabel ini: elemen yang
        // popover-nya sedang terbuka berhenti bergerak sampai ditutup.
        elemenPopover = null;
        var lama = document.querySelectorAll('.bangunan-svg.terpilih, .bang-uju.terpilih, .warga-svg.terpilih, .hotspot-interior.terpilih');
        for (var i = 0; i < lama.length; i++) {
            lama[i].classList.remove('terpilih');
        }
    }

    function bukaPopover(el) {
        tutupPopover();
        el.classList.add('terpilih');

        var d = el.dataset;
        var kades = d.jenis === 'kades';
        var warga = d.jenis === 'warga';
        var papan = d.jenis === 'papan';
        // Versi 4.3: catat elemen pemilik popover supaya wara-wiri dan
        // patroli menjeda elemen itu selama popover-nya terbuka. Warga
        // yang ditap juga menyapa balik: pantulan kecil dan label
        // namanya membesar sesaat.
        elemenPopover = el;
        if (warga) {
            el.classList.add('meloncat', 'labelnaik');
            setTimeout(function () {
                el.classList.remove('meloncat', 'labelnaik');
            }, 950);
        }
        popLabel.textContent = kades ? 'Kepala Desa' : (warga ? 'Warga Desa' : (papan ? 'Pengumuman Desa' : 'Detail Bangunan'));
        popNama.textContent = d.nama || '';
        popGang.textContent = d.gang || '';
        popGang.hidden = kades || !d.gang;
        popKades.hidden = !kades;
        popBaru.hidden = d.terbaru !== '1';
        popKomentar.textContent = d.komentar || '';
        popKomentarBaris.hidden = !d.komentar;
        popPeminta.textContent = d.peminta || '';
        popPemintaBaris.hidden = !d.peminta;
        popNomor.textContent = d.nomor || '';
        popNomorBaris.hidden = !warga;
        popTanggalKata.textContent = warga ? 'Bergabung' : 'Berdiri';
        popTanggal.textContent = d.tanggal || '';
        popTanggalBaris.hidden = kades || !d.tanggal;
        popInfo.textContent = d.info || '';
        popInfo.hidden = !d.info;
        if (d.tautan) {
            popTautan.href = d.tautan;
            popTautan.textContent = d.tautanTeks || d.tautan;
            popTautanBaris.hidden = false;
        } else {
            popTautanBaris.hidden = true;
        }

        popover.hidden = false;

        // Posisikan popover di atas elemen yang ditap; kalau tidak muat,
        // pindah ke bawahnya. Kiri dan kanan selalu dijepit di dalam viewport.
        var r = el.getBoundingClientRect();
        var v = viewport.getBoundingClientRect();
        var pw = popover.offsetWidth;
        var ph = popover.offsetHeight;
        var cx = r.left - v.left + r.width / 2;
        var left = Math.min(Math.max(8, cx - pw / 2), Math.max(8, v.width - pw - 8));
        var top = (r.top - v.top) - ph - 12;
        if (top < 8) {
            top = (r.bottom - v.top) + 12;
        }
        if (top + ph > v.height - 8) {
            top = Math.max(8, v.height - ph - 8);
        }
        popover.style.left = left + 'px';
        popover.style.top = top + 'px';
    }

    document.getElementById('popoverTutup').addEventListener('click', tutupPopover);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (interiorTerbuka) {
                tutupInterior();
            } else if (panelGangTerbuka) {
                aturPanelGang(false);
            } else {
                tutupPopover();
            }
        }
    });

    // --- Seret untuk menggeser, cubit untuk zoom, tap untuk membuka popover.
    var penunjuk = {};
    var jumlahPenunjuk = 0;
    var seret = null;
    var cubit = null;

    viewport.addEventListener('pointerdown', function (e) {
        if (interiorTerbuka) {
            return;
        }
        if (e.target.closest && e.target.closest('.peta-kontrol, .peta-popover, .peta-dpad, .chip-prompt, .peta-merek-baris, .peta-intro, .panel-gang, .peta-gulir-bawah')) {
            return;
        }
        try {
            viewport.setPointerCapture(e.pointerId);
        } catch (err) {
            // Penangkap penunjuk tidak selalu tersedia, abaikan saja.
        }
        penunjuk[e.pointerId] = { x: e.clientX, y: e.clientY };
        jumlahPenunjuk++;
        // Selama jari atau mouse menahan peta, wara-wiri versi 4.3
        // menjeda supaya tap dan seret tidak berlomba dengan gerakan warga.
        seretAmbien = true;
        if (jumlahPenunjuk === 1) {
            // Di mode Keliling Desa, seretan hanya dibaca sebagai tap:
            // kamera mengikuti karakter, bukan geseran jari.
            seret = { x: e.clientX, y: e.clientY, tx: tx, ty: ty, gerak: false, target: e.target, tanpaGeser: jalanAktif };
            viewport.classList.add('menyeret');
        } else if (jumlahPenunjuk === 2) {
            var pts = Object.values(penunjuk);
            var r = viewport.getBoundingClientRect();
            var mx = (pts[0].x + pts[1].x) / 2 - r.left;
            var my = (pts[0].y + pts[1].y) / 2 - r.top;
            cubit = {
                d: Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y),
                k: k,
                lx: (mx - tx) / k,
                ly: (my - ty) / k
            };
            seret = null;
            tutupPopover();
        }
    });

    viewport.addEventListener('pointermove', function (e) {
        if (!penunjuk[e.pointerId]) {
            return;
        }
        penunjuk[e.pointerId] = { x: e.clientX, y: e.clientY };
        if (cubit && jumlahPenunjuk === 2) {
            var pts = Object.values(penunjuk);
            var r = viewport.getBoundingClientRect();
            var d = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
            var mx = (pts[0].x + pts[1].x) / 2 - r.left;
            var my = (pts[0].y + pts[1].y) / 2 - r.top;
            k = Math.min(K_MAX, Math.max(kMin, cubit.k * d / cubit.d));
            tx = mx - cubit.lx * k;
            ty = my - cubit.ly * k;
            terapkan();
        } else if (seret) {
            var dx = e.clientX - seret.x;
            var dy = e.clientY - seret.y;
            if (!seret.gerak && Math.abs(dx) + Math.abs(dy) > 7) {
                seret.gerak = true;
                tutupPopover();
            }
            if (seret.gerak && !seret.tanpaGeser) {
                tx = seret.tx + dx;
                ty = seret.ty + dy;
                terapkan();
            }
        }
    });

    function akhiriPenunjuk(e) {
        if (!penunjuk[e.pointerId]) {
            return;
        }
        delete penunjuk[e.pointerId];
        jumlahPenunjuk--;
        if (jumlahPenunjuk < 2) {
            cubit = null;
        }
        if (jumlahPenunjuk === 0) {
            seretAmbien = false;
            viewport.classList.remove('menyeret');
            if (seret && !seret.gerak) {
                // Gerakan pendek tanpa geser berarti tap.
                var t = seret.target;
                var el = (t && t.closest) ? t.closest('.bangunan-svg, .bang-uju, .warga-svg') : null;
                if (el) {
                    bukaPopover(el);
                } else {
                    tutupPopover();
                }
            }
            seret = null;
        }
    }
    viewport.addEventListener('pointerup', akhiriPenunjuk);
    viewport.addEventListener('pointercancel', akhiriPenunjuk);

    // Roda mouse: zoom hanya di layar penuh atau saat Ctrl atau Cmd
    // ditahan, mengikuti posisi kursor (pinch trackpad juga sampai ke sini
    // sebagai ctrl+wheel). Roda biasa di luar layar penuh tidak dicegat
    // sama sekali, halaman menggulir seperti biasa dan chip petunjuk
    // memberi tahu gestur zoom-nya sebentar. Dulu roda selalu dicegat,
    // jadi sejak peta memenuhi layar pengunjung merasa terjebak: niat
    // menggulir halaman malah memperbesar peta.
    // Di atas panel gang, roda mouse menggulir panelnya, bukan zoom peta.
    var chipPeta = document.getElementById('petaChip');
    var teksChipPeta = chipPeta ? chipPeta.textContent : '';
    var timerChipPeta = null;
    function tampilPetunjukCtrl() {
        if (!chipPeta) {
            return;
        }
        chipPeta.textContent = 'Tahan Ctrl sambil scroll buat zoom peta';
        if (timerChipPeta) {
            clearTimeout(timerChipPeta);
        }
        timerChipPeta = setTimeout(function () {
            chipPeta.textContent = teksChipPeta;
            timerChipPeta = null;
        }, 2000);
    }
    viewport.addEventListener('wheel', function (e) {
        if (e.target.closest && e.target.closest('.panel-gang')) {
            return;
        }
        if (document.fullscreenElement !== viewport && !e.ctrlKey && !e.metaKey) {
            tampilPetunjukCtrl();
            return;
        }
        e.preventDefault();
        tutupPopover();
        var r = viewport.getBoundingClientRect();
        zoomKe(k * Math.exp(-e.deltaY * 0.0016), e.clientX - r.left, e.clientY - r.top);
    }, { passive: false });

    // Tombol panah ke bawah menggulir halaman melewati bingkai peta
    // menuju konten di bawahnya, supaya tidak ada yang terjebak di peta.
    document.getElementById('tombolGulirBawah').addEventListener('click', function () {
        var kurangGerak = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        var batasBawah = viewport.getBoundingClientRect().bottom + window.scrollY;
        window.scrollTo({ top: batasBawah, behavior: kurangGerak ? 'auto' : 'smooth' });
    });

    // Tombol zoom di layar.
    document.getElementById('zoomMasuk').addEventListener('click', function () {
        tutupPopover();
        zoomKe(k * 1.4, viewport.clientWidth / 2, viewport.clientHeight / 2);
    });
    document.getElementById('zoomKeluar').addEventListener('click', function () {
        tutupPopover();
        zoomKe(k / 1.4, viewport.clientWidth / 2, viewport.clientHeight / 2);
    });
    document.getElementById('zoomReset').addEventListener('click', function () {
        tutupPopover();
        lihatSeluruhDesa();
    });

    // Papan ketik: Enter atau spasi pada bangunan, Bang Uju, dan warga membuka popover.
    var interaktif = document.querySelectorAll('.bangunan-svg, .bang-uju, .warga-svg');
    for (var i = 0; i < interaktif.length; i++) {
        (function (el) {
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    bukaPopover(el);
                }
            });
        })(interaktif[i]);
    }

    window.addEventListener('resize', function () {
        hitungDasar();
        terapkan();
        tutupPopover();
    });

    // === Versi 4: mode Keliling Desa dan interior Balai Desa ===
    var tombolJalan = document.getElementById('tombolJalan');
    var dpad = document.getElementById('petaDpad');
    var chipPrompt = document.getElementById('chipPrompt');
    var karakterEl = document.getElementById('karakterJalan');
    var karakterGoyang = document.getElementById('karakterGoyang');
    var karakterBadan = document.getElementById('karakterBadan');
    var interior = document.getElementById('petaInterior');
    var gerakDikurangi = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var jalanAktif = false;
    var interiorTerbuka = false;
    var rafId = null;
    var capWaktu = 0;
    var hadap = 1;
    var sedangJalan = false;
    // Karakter mulai berdiri di alun-alun, tepat di depan Balai Desa.
    var karakter = { x: FOKUS.x, y: FOKUS.y + 88 };
    var arahTekan = { atas: false, bawah: false, kiri: false, kanan: false };
    var promptTarget = null;
    var promptBalai = false;

    // Rintangan sederhana untuk kaki karakter: kolam (persegi kasar)
    // dan tapak tiap bangunan yang sudah berdiri.
    var rintangan = [[1530, 1075, 2010, 1320]];
    var tapakBangunan = [
        <?php foreach ($bangunan as $i => $b): ?>
        [<?= (int) POSISI_KAVLING[$i][0] ?>, <?= (int) POSISI_KAVLING[$i][1] ?>],
        <?php endforeach; ?>
    ];
    for (var tb = 0; tb < tapakBangunan.length; tb++) {
        rintangan.push([tapakBangunan[tb][0] - 98, tapakBangunan[tb][1] - 120, tapakBangunan[tb][0] + 98, tapakBangunan[tb][1] + 2]);
    }

    function dalamRintangan(x, y) {
        for (var i = 0; i < rintangan.length; i++) {
            var r = rintangan[i];
            if (x > r[0] && x < r[2] && y > r[1] && y < r[3]) {
                return true;
            }
        }
        return false;
    }

    function gambarKarakter(waktu) {
        var bob = (sedangJalan && !gerakDikurangi) ? Math.sin(waktu / 90) * 3.2 : 0;
        karakterEl.setAttribute('transform', 'translate(' + karakter.x + ' ' + karakter.y + ')');
        karakterGoyang.setAttribute('transform', 'translate(0 ' + bob.toFixed(1) + ')');
        karakterBadan.setAttribute('transform', 'scale(' + hadap + ' 1)');
    }

    function langkahKarakter(dt) {
        var vx = (arahTekan.kanan ? 1 : 0) - (arahTekan.kiri ? 1 : 0);
        var vy = (arahTekan.bawah ? 1 : 0) - (arahTekan.atas ? 1 : 0);
        sedangJalan = vx !== 0 || vy !== 0;
        if (!sedangJalan) {
            return;
        }
        var panjang = Math.hypot(vx, vy) || 1;
        var jarak = 265 * dt;
        var nx = karakter.x + vx / panjang * jarak;
        var ny = karakter.y + vy / panjang * jarak;
        nx = Math.min(1950, Math.max(50, nx));
        ny = Math.min(1290, Math.max(95, ny));
        if (!dalamRintangan(nx, karakter.y)) {
            karakter.x = nx;
        }
        if (!dalamRintangan(karakter.x, ny)) {
            karakter.y = ny;
        }
        if (vx < 0) {
            hadap = -1;
        } else if (vx > 0) {
            hadap = 1;
        }
    }

    // Satu langkah pasti sebesar kurang lebih 26 satuan dunia, dipakai
    // ketukan singkat pada D-pad. Ketukan yang lebih pendek dari satu
    // frame animasi tidak sempat terbaca putaran jalan (yang bergerak
    // hanya selama tombol tertahan), jadi ketukan dulu terasa tidak
    // menggerakkan karakter sama sekali. Batas dan rintangannya sama
    // dengan langkah berkelanjutan di langkahKarakter.
    function selangkah(ar) {
        var vx = ar === 'kanan' ? 1 : (ar === 'kiri' ? -1 : 0);
        var vy = ar === 'bawah' ? 1 : (ar === 'atas' ? -1 : 0);
        var nx = Math.min(1950, Math.max(50, karakter.x + vx * 26));
        var ny = Math.min(1290, Math.max(95, karakter.y + vy * 26));
        if (!dalamRintangan(nx, karakter.y)) {
            karakter.x = nx;
        }
        if (!dalamRintangan(karakter.x, ny)) {
            karakter.y = ny;
        }
        if (vx < 0) {
            hadap = -1;
        } else if (vx > 0) {
            hadap = 1;
        }
        gambarKarakter(performance.now());
        perbaruiPrompt();
    }

    function ikutiKarakter() {
        var targetTx = viewport.clientWidth / 2 - karakter.x * baseS * k;
        var targetTy = viewport.clientHeight / 2 - karakter.y * baseS * k;
        tx += (targetTx - tx) * 0.16;
        ty += (targetTy - ty) * 0.16;
        terapkan();
    }

    function perbaruiPrompt() {
        var els = document.querySelectorAll('.bangunan-svg');
        var terbaik = null;
        var jarakTerbaik = 160;
        for (var i = 0; i < els.length; i++) {
            var d = Math.hypot(parseFloat(els[i].dataset.x) - karakter.x, parseFloat(els[i].dataset.y) - karakter.y);
            if (d < jarakTerbaik) {
                jarakTerbaik = d;
                terbaik = els[i];
            }
        }
        if (!terbaik) {
            promptTarget = null;
            chipPrompt.hidden = true;
            return;
        }
        promptTarget = terbaik;
        promptBalai = terbaik.dataset.balai === '1';
        chipPrompt.textContent = promptBalai ? 'Masuk Balai Desa' : 'Lihat ' + (terbaik.dataset.nama || 'bangunan');
        chipPrompt.style.left = (tx + karakter.x * baseS * k) + 'px';
        chipPrompt.style.top = (ty + (karakter.y - 108) * baseS * k) + 'px';
        chipPrompt.hidden = false;
    }

    function putaranJalan(waktu) {
        if (!jalanAktif) {
            rafId = null;
            return;
        }
        var dt = Math.min(0.05, capWaktu ? (waktu - capWaktu) / 1000 : 0);
        capWaktu = waktu;
        if (!interiorTerbuka) {
            langkahKarakter(dt);
            gambarKarakter(waktu);
            ikutiKarakter();
            perbaruiPrompt();
        }
        rafId = requestAnimationFrame(putaranJalan);
    }

    function aturJalan(aktif) {
        jalanAktif = aktif;
        tombolJalan.classList.toggle('aktif', aktif);
        tombolJalan.setAttribute('aria-pressed', aktif ? 'true' : 'false');
        tombolJalan.textContent = aktif ? 'Berhenti' : 'Keliling Desa';
        dpad.hidden = !aktif;
        // Karakter adalah elemen SVG <g>: properti .hidden tidak selalu
        // terhubung ke atributnya pada elemen SVG di semua peramban,
        // jadi atributnya dicabut dan dipasang secara eksplisit. Dengan
        // penetapan properti saja, atribut hidden bisa tertinggal dan
        // aturan [hidden] di stylesheet menahan karakter tetap tidak
        // terlihat walau keadaan mode jalan sudah benar.
        if (aktif) {
            karakterEl.removeAttribute('hidden');
        } else {
            karakterEl.setAttribute('hidden', '');
        }
        viewport.classList.toggle('mode-jalan', aktif);
        if (aktif) {
            tutupPopover();
            gambarKarakter(0);
            capWaktu = 0;
            if (!rafId) {
                rafId = requestAnimationFrame(putaranJalan);
            }
        } else {
            arahTekan = { atas: false, bawah: false, kiri: false, kanan: false };
            promptTarget = null;
            chipPrompt.hidden = true;
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
        }
    }
    tombolJalan.addEventListener('click', function () {
        aturJalan(!jalanAktif);
    });
    chipPrompt.addEventListener('click', function () {
        if (!promptTarget) {
            return;
        }
        if (promptBalai) {
            bukaInterior();
        } else {
            bukaPopover(promptTarget);
        }
    });

    // D-pad di layar: ketuk untuk melangkah, tekan dan tahan untuk berjalan terus.
    var tombolDpad = dpad.querySelectorAll('button[data-arah]');
    for (var td = 0; td < tombolDpad.length; td++) {
        (function (btn) {
            btn.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                // Ketukan singkat langsung melangkah satu langkah;
                // menahan tombol tetap berjalan terus lewat arahTekan.
                selangkah(btn.dataset.arah);
                arahTekan[btn.dataset.arah] = true;
            });
            var lepas = function () {
                arahTekan[btn.dataset.arah] = false;
            };
            btn.addEventListener('pointerup', lepas);
            btn.addEventListener('pointerleave', lepas);
            btn.addEventListener('pointercancel', lepas);
            btn.addEventListener('contextmenu', function (e) {
                e.preventDefault();
            });
        })(tombolDpad[td]);
    }

    // Papan ketik buat berjalan: panah dan WASD, E buat memakai prompt.
    var petaArah = { ArrowUp: 'atas', KeyW: 'atas', ArrowDown: 'bawah', KeyS: 'bawah', ArrowLeft: 'kiri', KeyA: 'kiri', ArrowRight: 'kanan', KeyD: 'kanan' };
    document.addEventListener('keydown', function (e) {
        if (!jalanAktif || interiorTerbuka) {
            return;
        }
        if (petaArah[e.code]) {
            arahTekan[petaArah[e.code]] = true;
            e.preventDefault();
        } else if (e.code === 'KeyE' && promptTarget && !chipPrompt.hidden) {
            chipPrompt.click();
        }
    });
    document.addEventListener('keyup', function (e) {
        if (petaArah[e.code]) {
            arahTekan[petaArah[e.code]] = false;
        }
    });

    // Interior Balai Desa: tampil menutupi viewport, keadaan kamera dan
    // posisi karakter tidak berubah, jadi keluar berarti kembali persis
    // ke tempat semula.
    function bukaInterior() {
        interiorTerbuka = true;
        tutupPopover();
        chipPrompt.hidden = true;
        interior.hidden = false;
    }
    function tutupInterior() {
        interiorTerbuka = false;
        tutupPopover();
        interior.hidden = true;
    }
    document.getElementById('tombolKeluarInterior').addEventListener('click', tutupInterior);

    // Titik interaktif di dalam interior yang membuka popover
    // (Papan Pengumuman dan Bang Uju; loket dan arsip adalah tautan).
    var hotspotInterior = interior.querySelectorAll('.hotspot-interior[data-nama]');
    for (var hi = 0; hi < hotspotInterior.length; hi++) {
        (function (el) {
            el.addEventListener('click', function () {
                bukaPopover(el);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    bukaPopover(el);
                }
            });
        })(hotspotInterior[hi]);
    }

    // === Versi 4.1: tata letak sepenuh layar ===
    // Kartu pengantar bisa ditutup; keadaan terbukanya di ponsel dan
    // layar pendek diatur media query di stylesheet.
    var petaIntro = document.getElementById('petaIntro');
    document.getElementById('introTutup').addEventListener('click', function () {
        petaIntro.classList.add('tertutup');
    });

    // Panel Papan Gang meluncur dari kanan di dalam bingkai peta.
    var panelGang = document.getElementById('panelGang');
    var tombolPanelGang = document.getElementById('tombolPanelGang');
    var panelGangTerbuka = false;
    function aturPanelGang(buka) {
        panelGangTerbuka = buka;
        panelGang.classList.toggle('terbuka', buka);
        panelGang.setAttribute('aria-hidden', buka ? 'false' : 'true');
        tombolPanelGang.classList.toggle('aktif', buka);
        tombolPanelGang.setAttribute('aria-expanded', buka ? 'true' : 'false');
    }
    tombolPanelGang.addEventListener('click', function () {
        aturPanelGang(!panelGangTerbuka);
    });
    document.getElementById('panelGangTutup').addEventListener('click', function () {
        aturPanelGang(false);
    });

    // Tombol layar penuh memakai Fullscreen API pada bingkai peta.
    // Tombol disembunyikan kalau peramban tidak mendukungnya.
    var tombolLayarPenuh = document.getElementById('layarPenuh');
    if (!document.fullscreenEnabled || typeof viewport.requestFullscreen !== 'function') {
        tombolLayarPenuh.hidden = true;
    } else {
        tombolLayarPenuh.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                var janji = viewport.requestFullscreen();
                if (janji && typeof janji.catch === 'function') {
                    janji.catch(function () {
                        // Layar penuh ditolak peramban, peta tetap jalan seperti biasa.
                    });
                }
            }
        });
        // Masuk atau keluar layar penuh mengubah ukuran viewport;
        // hitung ulang skala dasar dan jepit kamera seperti pada resize.
        document.addEventListener('fullscreenchange', function () {
            hitungDasar();
            terapkan();
            tutupPopover();
        });
    }

    // === Versi 4.3: warga wara-wiri dan patroli Bang Uju ===
    // Desa terasa hidup karena penghuninya bergerak sendiri. Setiap
    // avatar warga adalah satu agen kecil: ia berdiam di titik berdirinya,
    // sesekali memilih titik tujuan lain di dalam zona gang-nya sendiri
    // (tabel ZONA_WARA dihitung PHP dari petak zona dengan margin tepi),
    // berjalan pelan ke sana, lalu berdiam lagi. Waktu tunggu, kecepatan,
    // dan titik tujuan semuanya acak per agen, jadi desa tidak pernah
    // bergerak serempak. Jalur lurus menuju tujuan ditolak kalau menembus
    // tapak kavling (RINTANGAN_WARA), dan paling banyak BATAS_BERGERAK
    // warga berjalan pada saat yang sama supaya peta tetap tenang.
    // Bang Uju ikut bergerak sebagai agen patroli istimewa: bukan titik
    // acak, melainkan putaran titik kunjung mengelilingi alun-alun Balai
    // Desa, seperti Kepala Desa yang sedang ronda. Semua titik kunjung
    // dan segmen antar titik berada di permukaan alun-alun dan apron
    // selatannya, di depan fasad Balai Desa (tapak bangunannya dijaga
    // tetap di utara garis y 700 untuk rentang x bangunan), tidak pernah
    // masuk zona gang warga dan tidak turun ke jalur jalan utama yang
    // berada jauh di selatan alun-alun.
    // Satu putaran requestAnimationFrame khusus kehidupan ambien ini,
    // terpisah dari putaran mode Keliling Desa. Putarannya menjeda
    // (posisi membeku, tanpa lompatan waktu) selama pengunjung menyeret
    // peta (seretAmbien), selama mode Keliling Desa aktif, selama
    // interior terbuka, dan selama tab tidak terlihat. Elemen yang
    // popover-nya sedang terbuka (elemenPopover, dicatat bukaPopover)
    // juga berhenti bergerak sampai popover ditutup. Pengunjung dengan
    // prefers-reduced-motion tidak menjalankan putaran ini sama sekali;
    // bob diam warga pun dimatikan dari sisi CSS.
    var elemenPopover = null;
    var seretAmbien = false;
    var ZONA_WARA = {
        <?php foreach ($zonaUntukJs as $gangWara => $zWara): ?>
        <?= json_encode($gangWara, JSON_UNESCAPED_UNICODE) ?>: { x: <?= $zWara['x'] ?>, y: <?= $zWara['y'] ?>, w: <?= $zWara['w'] ?>, h: <?= $zWara['h'] ?> },
        <?php endforeach; ?>
    };
    var RINTANGAN_WARA = [
        <?php foreach (POSISI_KAVLING as $kavWara): ?>
        [<?= (int) $kavWara[0] - 105 ?>, <?= (int) $kavWara[1] - 130 ?>, <?= (int) $kavWara[0] + 105 ?>, <?= (int) $kavWara[1] + 6 ?>],
        <?php endforeach; ?>
    ];
    var BATAS_BERGERAK = 12;
    // Titik kunjung patroli Bang Uju (koordinat dunia): mulai dari titik
    // berdirinya di timur alun-alun, ke sisi barat, ke apron selatan,
    // ke sisi timur laut, lalu pulang. Batas kawasan patroli untuk
    // pengujian: x 830 sampai 1260, y 695 sampai 820.
    var TITIK_PATROLI = [[<?= $ujuX ?>, <?= $ujuY ?>], [860, 720], [950, 795], [1180, 712]];
    var BATAS_PATROLI = { x1: 830, y1: 695, x2: 1260, y2: 820 };
    var TAPAK_BALAI = [925, 585, 1135, 700];

    function dalamRintanganWara(x, y) {
        for (var i = 0; i < RINTANGAN_WARA.length; i++) {
            var r = RINTANGAN_WARA[i];
            if (x > r[0] && x < r[2] && y > r[1] && y < r[3]) {
                return true;
            }
        }
        return false;
    }

    function titikAcakZona(zona) {
        return {
            x: zona.x + Math.random() * zona.w,
            y: zona.y + Math.random() * zona.h
        };
    }

    // Pilih titik tujuan untuk satu agen warga: cukup jauh untuk terasa
    // sebagai jalan-jalan (bukan geser di tempat), cukup dekat untuk
    // tetap jadi keliling lingkungan sendiri, dan jalur lurusnya tidak
    // menembus tapak kavling mana pun (diuji di beberapa titik segmen).
    function pilihTargetWara(agen) {
        for (var coba = 0; coba < 8; coba++) {
            var p = titikAcakZona(agen.zona);
            var d = Math.hypot(p.x - agen.x, p.y - agen.y);
            if (d < 34 || d > 200) {
                continue;
            }
            var aman = true;
            for (var s = 1; s < 6; s++) {
                if (dalamRintanganWara(agen.x + (p.x - agen.x) * s / 6, agen.y + (p.y - agen.y) * s / 6)) {
                    aman = false;
                    break;
                }
            }
            if (aman) {
                return p;
            }
        }
        return null;
    }

    var agenWarga = [];
    var elemenWarga = document.querySelectorAll('.warga-svg');
    for (var aw = 0; aw < elemenWarga.length; aw++) {
        (function (el) {
            var zona = ZONA_WARA[el.dataset.zona];
            if (!zona) {
                return;
            }
            agenWarga.push({
                el: el,
                zona: zona,
                x: parseFloat(el.dataset.rumahX),
                y: parseFloat(el.dataset.rumahY),
                tx: 0,
                ty: 0,
                jalan: false,
                tunggu: 0.5 + Math.random() * 7,
                kecepatan: 20 + Math.random() * 16
            });
        })(elemenWarga[aw]);
    }

    // Agen patroli Bang Uju: posisi awal dibaca dari transform markup
    // grupnya, sama persis dengan titik berdiri yang ditulis PHP.
    var patroli = null;
    var elUju = document.getElementById('bangUju');
    if (elUju) {
        var mUju = /translate\(([-\d.]+)[ ,]+([-\d.]+)\)/.exec(elUju.getAttribute('transform') || '');
        patroli = {
            el: elUju,
            x: mUju ? parseFloat(mUju[1]) : TITIK_PATROLI[0][0],
            y: mUju ? parseFloat(mUju[2]) : TITIK_PATROLI[0][1],
            tujuan: 1,
            jalan: false,
            tunggu: 3,
            kecepatan: 26
        };
    }

    function jumlahBergerak() {
        var n = 0;
        for (var i = 0; i < agenWarga.length; i++) {
            if (agenWarga[i].jalan) {
                n++;
            }
        }
        return n;
    }

    function perbaruiWarga(agen, dt) {
        if (elemenPopover === agen.el) {
            return;
        }
        if (!agen.jalan) {
            agen.tunggu -= dt;
            if (agen.tunggu > 0) {
                return;
            }
            if (jumlahBergerak() >= BATAS_BERGERAK) {
                agen.tunggu = 0.8;
                return;
            }
            var p = pilihTargetWara(agen);
            if (!p) {
                agen.tunggu = 1.5;
                return;
            }
            agen.tx = p.x;
            agen.ty = p.y;
            agen.jalan = true;
        }
        var dx = agen.tx - agen.x;
        var dy = agen.ty - agen.y;
        var d = Math.hypot(dx, dy);
        var langkah = agen.kecepatan * dt;
        if (d <= langkah) {
            agen.x = agen.tx;
            agen.y = agen.ty;
            agen.jalan = false;
            agen.tunggu = 2.5 + Math.random() * 6.5;
        } else {
            agen.x += dx / d * langkah;
            agen.y += dy / d * langkah;
        }
        agen.el.setAttribute('transform', 'translate(' + agen.x.toFixed(1) + ' ' + agen.y.toFixed(1) + ')');
    }

    function perbaruiPatroli(dt) {
        if (!patroli || elemenPopover === patroli.el) {
            return;
        }
        if (!patroli.jalan) {
            patroli.tunggu -= dt;
            if (patroli.tunggu > 0) {
                return;
            }
            patroli.jalan = true;
        }
        var target = TITIK_PATROLI[patroli.tujuan];
        var dx = target[0] - patroli.x;
        var dy = target[1] - patroli.y;
        var d = Math.hypot(dx, dy);
        var langkah = patroli.kecepatan * dt;
        if (d <= langkah) {
            patroli.x = target[0];
            patroli.y = target[1];
            patroli.jalan = false;
            patroli.tunggu = 3.5 + Math.random() * 3;
            patroli.tujuan = (patroli.tujuan + 1) % TITIK_PATROLI.length;
        } else {
            patroli.x += dx / d * langkah;
            patroli.y += dy / d * langkah;
        }
        patroli.el.setAttribute('transform', 'translate(' + patroli.x.toFixed(1) + ' ' + patroli.y.toFixed(1) + ')');
    }

    var rafAmbien = null;
    var capAmbien = 0;
    function putaranAmbien(waktu) {
        rafAmbien = requestAnimationFrame(putaranAmbien);
        var dt = Math.min(0.05, capAmbien ? (waktu - capAmbien) / 1000 : 0.016);
        capAmbien = waktu;
        if (jalanAktif || interiorTerbuka || seretAmbien || document.hidden) {
            return;
        }
        for (var i = 0; i < agenWarga.length; i++) {
            perbaruiWarga(agenWarga[i], dt);
        }
        perbaruiPatroli(dt);
    }
    if (!gerakDikurangi && (agenWarga.length > 0 || patroli)) {
        rafAmbien = requestAnimationFrame(putaranAmbien);
    }
    // === Akhir blok wara-wiri versi 4.3 ===

    hitungDasar();
    lihatSeluruhDesa();
})();
</script>

<?php
// Tab bar dan panel bersayap (sama seperti di peta 3D).
// Token formulir KTP untuk panel Aku (ADR-0007), dibuat di sesi yang sama
// dengan ktp.php dan ktp-json.php.
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
if (empty($_SESSION['token_ktp'])) {
    $_SESSION['token_ktp'] = bin2hex(random_bytes(16));
}
$tokenKtpPanel = (string) $_SESSION['token_ktp'];
$tabAktif = '';
$tabAwalan = '';
$tabDesaPanel = true;
$panelBangunan = $bangunan;
$panelKavling = [];
foreach ($bangunan as $i => $b) { $panelKavling[(int) $b['id']] = $i; }
$panelPapanGang = $papanGang;
$panelTotalWarga = $totalWarga ?? hitung_warga($pdo);
$panelMisi = daftar_misi($pdo);
$panelTotalBangunan = count($bangunan);
$panelTerbaru = bangunan_terbaru($pdo);
require __DIR__ . '/panel-bersayap.php';
require __DIR__ . '/tab-bar.php';
require __DIR__ . '/tab-bar-skrip.php';
require __DIR__ . '/cari-warga-skrip.php';
$awalanPoin = '';
require __DIR__ . '/dompet-skrip.php';
$awalanMisi = '';
require __DIR__ . '/misi-skrip.php';
$awalanChat = '';
require __DIR__ . '/chat-skrip.php';
$awalanPasar = '';
require __DIR__ . '/pasar-skrip.php';
?>
<script>window.__tokenKtp = <?= json_encode($tokenKtpPanel) ?>;</script>
<?php
$akuAwalan = '';
require __DIR__ . '/pemulihan-skrip.php';
require __DIR__ . '/aku-skrip.php';
$rukoPanelAwalan = '';
require __DIR__ . '/ruko-skrip.php';
?>
