<?php
// Posisi 12 kavling pada Peta Desa interaktif (adegan SVG viewBox 1200 x 800).
// Setiap entri adalah titik jangkar [x, y]: titik tengah bawah bangunan,
// yaitu titik tempat bangunan "berdiri" di atas tanah.
//
// Indeks 0 adalah titik fokus peta: di situlah Balai Desa berdiri, di atas
// alun-alun kecil di tengah desa. Kavling lain tersebar organik di kiri dan
// kanan jalan utama desa yang berkelok dari kiri bawah ke kanan atas.
//
// Bangunan mengisi kavling mengikuti urutan id (urutan masuk database),
// sama seperti logika peta versi 1: bangunan pertama di kavling 0,
// bangunan kedua di kavling 1, dan seterusnya. Kavling selebihnya yang
// belum ada bangunannya tampil sebagai penanda Tanah Kosong.

const POSISI_KAVLING = [
    [610, 415],   // 0: Balai Desa, titik fokus di alun-alun tengah desa
    [170, 300],   // 1
    [390, 195],   // 2
    [855, 205],   // 3
    [1060, 295],  // 4
    [140, 520],   // 5
    [350, 455],   // 6
    [455, 720],   // 7
    [750, 670],   // 8
    [955, 610],   // 9
    [255, 750],   // 10
    [1100, 580],  // 11
];
