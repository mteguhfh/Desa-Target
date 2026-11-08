<?php
# Konfigurasi aplikasi Desa Target.

// Alamat publik aplikasi. Target production: https://desa.kejartarget.id
// (subdomain, karena kejartarget.id sendiri adalah situs produk Kejar Target).
// Untuk development lokal, override lewat environment variable, contoh:
//   DESA_TARGET_BASE_URL=http://localhost:8000 php -S localhost:8000
define('APP_BASE_URL', rtrim(getenv('DESA_TARGET_BASE_URL') ?: 'https://desa.kejartarget.id', '/'));

define('APP_NAME', 'Desa Target');
define('APP_INSTAGRAM', 'https://www.instagram.com/desatarget');

date_default_timezone_set('Asia/Jakarta');

// Lima gang warga berdasarkan profesi. Urutan ini juga urutan tampilan awal
// di papan gang sebelum diurutkan menurut jumlah warga.
const GANG_WARGA = [
    'Gang Dagang',
    'Gang Kuliner',
    'Gang Jasa',
    'Gang Freelancer',
    'Gang Kerajinan',
];

// Palet warna warga. Warga memilih sendiri tampilannya saat bikin KTP, dan
// figur 3D di peta memakai pilihan itu. Kuncinya disimpan di basis data
// (bukan kode warna), jadi paletnya bisa diubah tanpa menyentuh data warga.
//
// Tiga bagian: baju, kulit, dan topi/rambut. Warna yang dulu dipakai (baju
// mengambil warna gang, kulit bawaan, topi merah) tetap jadi bawaan bagi
// warga lama yang belum pernah memilih.
const WARNA_BAJU = [
    'biru' => ['nama' => 'Biru', 'hex' => '#2563eb'],
    'oranye' => ['nama' => 'Oranye', 'hex' => '#ea580c'],
    'teal' => ['nama' => 'Teal', 'hex' => '#0d9488'],
    'ungu' => ['nama' => 'Ungu', 'hex' => '#7c3aed'],
    'merah' => ['nama' => 'Merah', 'hex' => '#dc2626'],
    'hijau' => ['nama' => 'Hijau', 'hex' => '#16a34a'],
    'kuning' => ['nama' => 'Kuning', 'hex' => '#ca8a04'],
    'abu' => ['nama' => 'Abu-abu', 'hex' => '#475569'],
    'pink' => ['nama' => 'Pink', 'hex' => '#db2777'],
    'cokelat' => ['nama' => 'Cokelat', 'hex' => '#92400e'],
];

const WARNA_KULIT = [
    // Lima tingkat dari paling terang sampai paling gelap, supaya semua
    // orang bisa menemukan warna yang mirip dirinya.
    'terang' => ['nama' => 'Sangat terang', 'hex' => '#fde0c8'],
    'cerah' => ['nama' => 'Cerah', 'hex' => '#fcd9b8'],
    'sawo' => ['nama' => 'Sawo matang', 'hex' => '#e0a878'],
    'gelap' => ['nama' => 'Gelap', 'hex' => '#b0794b'],
    'hitam' => ['nama' => 'Hitam', 'hex' => '#7a4f2c'],
];

const WARNA_TOPI = [
    'merah' => ['nama' => 'Merah', 'hex' => '#dc2626'],
    'biru' => ['nama' => 'Biru', 'hex' => '#2563eb'],
    'hitam' => ['nama' => 'Hitam', 'hex' => '#334155'],
    'kuning' => ['nama' => 'Kuning', 'hex' => '#eab308'],
    'hijau' => ['nama' => 'Hijau', 'hex' => '#16a34a'],
    'putih' => ['nama' => 'Putih', 'hex' => '#f8fafc'],
    'ungu' => ['nama' => 'Ungu', 'hex' => '#7c3aed'],
    'oranye' => ['nama' => 'Oranye', 'hex' => '#ea580c'],
];

// Kunci bawaan: dipakai bila warga belum memilih (atau kolomnya kosong).
const WARNA_BAWAAN = ['baju' => 'teal', 'kulit' => 'cerah', 'topi' => 'merah'];
