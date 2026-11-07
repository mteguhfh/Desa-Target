<?php
// Posisi 12 kavling pada Peta Desa interaktif v3: dunia SVG berukuran
// 2000 x 1333 yang bisa digeser dan di-zoom dari viewport peta.
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
