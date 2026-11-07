<?php
// Posisi 12 kavling pada Peta Desa interaktif: dunia SVG berukuran
// 2000 x 1333 yang bisa digeser dan di-zoom dari viewport peta.
// Sejak versi 4 file ini juga memuat ZONA_GANG: wilayah tiap gang tempat
// avatar para warga berdiri di peta.
//
// SKALA_DUNIA adalah faktor pengali dari koordinat adegan versi 2
// (1200 x 800) ke dunia versi 3 (2000 x 1333). Latar desa di peta.php
// digambar memakai grup <g> dengan transform scale(SKALA_DUNIA), sedangkan
// bangunan dan penanda tanah kosong digambar di bangunan-svg.php dalam
// koordinat lokal versi 2 lalu dibungkus grup skala yang sama, sehingga
// semua elemen bertemu di koordinat dunia yang konsisten.
const SKALA_DUNIA = 1.6667;

// Setiap entri adalah titik jangkar [x, y] dalam koordinat dunia:
// titik tengah bawah bangunan, yaitu titik tempat bangunan "berdiri"
// di atas tanah.
//
// Indeks 0 adalah titik fokus peta: di situlah Balai Desa berdiri, di atas
// alun-alun kecil di tengah desa. Kamera peta membuka halaman dengan titik
// ini di tengah viewport. Kavling lain tersebar organik di kiri dan kanan
// jalan utama desa yang berkelok dari kiri bawah ke kanan atas.
//
// Bangunan mengisi kavling mengikuti urutan id (urutan masuk database),
// sama seperti logika peta versi 1 dan 2: bangunan pertama di kavling 0,
// bangunan kedua di kavling 1, dan seterusnya. Kavling selebihnya yang
// belum ada bangunannya tampil sebagai penanda Tanah Kosong.

const POSISI_KAVLING = [
    [1017, 692],   // 0: Balai Desa, titik fokus di alun-alun tengah desa
    [283, 500],    // 1
    [650, 325],    // 2
    [1425, 342],   // 3
    [1767, 492],   // 4
    [233, 867],    // 5
    [583, 758],    // 6
    [758, 1200],   // 7
    [1250, 1117],  // 8
    [1592, 1017],  // 9
    [425, 1250],   // 10
    [1833, 967],   // 11
];

// Zona gang versi 4: wilayah lingkungan tiap gang di atas peta, tempat
// avatar warga gang itu berdiri. Semua koordinat di sini memakai koordinat
// lokal versi 2 (1200 x 800), sama seperti gambar bangunan; fungsi gambar
// di bangunan-svg.php yang mengalikannya dengan SKALA_DUNIA.
//
// Tiap zona berisi:
// - tambak: [x, y, lebar, tinggi] petak tanah berpenanda warna gang.
// - papan:  [x, y] titik tengah papan kayu nama gang, berdiri di tepi
//           atas petak. Di bawah nama gang, papan menampilkan jumlah
//           warga yang belum kebagian tempat berdiri ("+N warga lainnya").
// - awal:   [x, y] titik kaki avatar pertama (baris pertama, kolom pertama).
// - dx, dy: jarak antar avatar dalam satu baris dan antar baris.
// - kolom, baris: ukuran grid tempat berdiri. Kapasitas semua zona
//           dijaga di bawah batas 80 avatar yang dirender di peta.
//
// Letak zona dipilih di lahan terbuka: Gang Freelancer di barat laut,
// Gang Dagang di utara tengah, Gang Kuliner di timur laut, Gang Jasa
// di barat tengah, dan Gang Kerajinan di timur tengah. Tidak ada zona
// yang menimpa kavling bangunan, jalan utama, alun-alun, atau kolam.
const ZONA_GANG = [
    'Gang Dagang' => [
        'tambak' => [495, 20, 210, 168],
        'papan' => [600, 32],
        'awal' => [522, 98],
        'dx' => 36, 'dy' => 34, 'kolom' => 5, 'baris' => 3,
    ],
    'Gang Kuliner' => [
        'tambak' => [952, 16, 205, 162],
        'papan' => [1054, 28],
        'awal' => [980, 94],
        'dx' => 36, 'dy' => 34, 'kolom' => 5, 'baris' => 3,
    ],
    'Gang Jasa' => [
        'tambak' => [26, 302, 205, 148],
        'papan' => [128, 314],
        'awal' => [52, 380],
        'dx' => 36, 'dy' => 32, 'kolom' => 5, 'baris' => 3,
    ],
    'Gang Freelancer' => [
        'tambak' => [30, 24, 240, 150],
        'papan' => [150, 36],
        'awal' => [56, 102],
        'dx' => 36, 'dy' => 34, 'kolom' => 5, 'baris' => 3,
    ],
    'Gang Kerajinan' => [
        'tambak' => [772, 290, 185, 140],
        'papan' => [864, 302],
        'awal' => [796, 368],
        'dx' => 36, 'dy' => 30, 'kolom' => 5, 'baris' => 3,
    ],
];
