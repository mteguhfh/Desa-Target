<?php
// Posisi kavling untuk halaman Ruko Warga. Berkas ini hanya memuat daftar
// titik jangkar; sengaja terpisah supaya ruko.php tidak ikut memuat seluruh
// data peta (zona gang, gambar SVG bangunan) yang tidak dibutuhkan.
require_once __DIR__ . '/peta-posisi.php';

return POSISI_KAVLING;
