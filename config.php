<?php
// Konfigurasi aplikasi Desa Target.

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
