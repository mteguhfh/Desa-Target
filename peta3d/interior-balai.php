<?php
// Ruang Kantor Desa dalam 3D, untuk mode Keliling Desa di Balai Desa.
//
// Sebelumnya "Masuk Balai Desa" hanya menampilkan satu gambar SVG datar: bisa
// dilihat tapi tidak bisa dijelajahi. Sekarang ruangannya benar-benar tiga
// dimensi dan bisa dikelilingi seperti dunia desa di luar:
//
//   W/A/S/D atau tombol panah  -> jalan
//   seret mouse / geser jari   -> lihat sekeliling
//   Esc                        -> keluar ruangan
//
// Empat titik penting (Loket KTP, Papan Pengumuman, Arsip Dokumen, Bang Uju)
// tetap titik interaktif dengan data-* yang sama seperti versi SVG, jadi
// skrip peta tidak perlu tahu bedanya. Bedanya, sekarang mereka ditempelkan
// di objek 3D lewat proyeksi kamera dan baru bisa diketuk saat berdiri cukup
// dekat.
//
// Berkas ini hanya mengeluarkan markup; mesin ruangnya ada di
// interior-balai3d.js supaya tetap bisa dibaca.
//
// Variabel yang dipakai: $bangunanTerbaru (dari bangunan_terbaru()).
// Ambil sendiri dari basis data, jangan bergantung pada variabel halaman.
require_once __DIR__ . '/../db.php';
// interior_balai_svg() memakai svg_teks() dari bangunan-svg.php.
require_once __DIR__ . '/../includes/bangunan-svg.php';
try {
    $bangunanTerbaru = bangunan_terbaru(db());
} catch (Throwable $e) {
    $bangunanTerbaru = null; // tanpa data, papan memakai nama Balai Desa
}
$namaTerbaru = $bangunanTerbaru !== null
    ? (string) $bangunanTerbaru['nama']
    : 'Balai Desa (Kantor Desa Kejar Target)';
?>
<div class="interior-3d" id="interior3d" hidden>
    <div class="interior-3d-kanvas" id="interior3dKanvas" aria-hidden="true"></div>
    <div class="interior-3d-titik" id="interior3dTitik">
        <button type="button" class="hotspot-interior interior-titik" data-pos="loket"
                data-jenis="ktp" data-nama="Loket KTP Warga"
                data-keterangan="Buka loket"
                data-info="Bikin KTP Warga di sini supaya namamu berdiri di peta sebagai warga resmi Desa Target.">
            <span class="interior-titik-lencana">Loket</span>
            <span class="interior-titik-nama">KTP Warga</span>
        </button>
        <button type="button" class="hotspot-interior interior-titik" data-pos="papan"
                data-jenis="papan" data-nama="Papan Pengumuman"
                data-komentar="<?= e('Bangunan terbaru: ' . $namaTerbaru) ?>"
                data-keterangan="Lihat pengumuman"
                data-info="Setiap hari, komen dengan like terbanyak di Instagram @desatarget dibangun besoknya jadi bangunan baru di desa ini.">
            <span class="interior-titik-lencana">Papan</span>
            <span class="interior-titik-nama">Pengumuman</span>
        </button>
        <a href="<?= e($awalan ?? '') ?>ktp" class="hotspot-tautan hotspot-interior interior-titik" data-pos="arsip"
           data-keterangan="Buka arsip"
           aria-label="Arsip Dokumen Warga, buka formulir KTP Warga">
            <span class="interior-titik-lencana">Arsip</span>
            <span class="interior-titik-nama">Dokumen Warga</span>
        </a>
        <button type="button" class="hotspot-interior interior-titik" data-pos="uju"
                data-jenis="kades" data-nama="Bang Uju"
                data-komentar="Ikutin Bang Uju, biar sama-sama sampai tujuan!"
                data-keterangan="Sapa Bang Uju"
                data-info="Kepala Desa Target, bertugas di Balai Desa ini. Urusan surat dan dokumen warga dibantu lewat loket KTP dan arsip dokumen di kantor ini.">
            <span class="interior-titik-lencana">Kades</span>
            <span class="interior-titik-nama">Bang Uju</span>
        </button>
    </div>
    <p class="interior-3d-petunjuk" id="interior3dPetunjuk">
        W/A/S/D atau tombol panah buat jalan. Seret layar buat lihat sekeliling.
    </p>
    <div class="interior-3d-dpad" id="interior3dDpad" aria-label="Tombol arah jalan di dalam Balai Desa">
        <button type="button" class="atas" data-arah="maju" aria-label="Jalan ke depan">&#9650;</button>
        <button type="button" class="kiri" data-arah="kiri" aria-label="Jalan ke kiri">&#9664;</button>
        <button type="button" class="kanan" data-arah="kanan" aria-label="Jalan ke kanan">&#9654;</button>
        <button type="button" class="bawah" data-arah="mundur" aria-label="Jalan ke belakang">&#9660;</button>
    </div>
    <button type="button" class="interior-3d-keluar" id="tombolKeluarInterior3d">&larr; Keluar ke Peta 3D</button>
</div>
