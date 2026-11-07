# ADR-0002: Tanpa framework frontend dan tanpa CDN pihak ketiga

- Status: Diterima
- Tanggal: 2026-11-08

## Konteks

Situs dikunjungi warga dari ponsel, sering dengan koneksi seluler yang tidak
stabil. Halaman utama dulu sempat memuat Three.js dari `unpkg.com`, lalu
diganti host sendiri di `peta3d/vendor/`.

## Keputusan

1. Tidak memakai bundler, tidak memakai framework (React/Vue/Svelte). Semua
   halaman adalah PHP dengan JavaScript biasa di dalam blok `<script>`.
2. Tidak ada permintaan ke CDN pada runtime. Semua pustaka disimpan di dalam
   repo: `peta3d/vendor/three.module.js` dan
   `peta3d/vendor/addons/controls/OrbitControls.js`.
3. Penanda versi aset dihitung otomatis dengan `filemtime()` di sisi PHP,
   bukan ditulis tangan.

## Alasan

- Satu titik gagal lebih sedikit: kalau `unpkg.com` lambat atau mati, situs
  tetap hidup.
- Tanpa proses build, deploy tetap `tar` + `scp`, dan berkas yang ada di repo
  adalah berkas yang berjalan di server. Tidak ada langkah yang bisa terlewat.
- Penanda versi otomatis mengakhiri kebiasaan mengganti `?v=` dengan tangan,
  yang dulu sempat menghasilkan sepuluh commit berjudul sama.

## Konsekuensi

- Ukuran repo bertambah sekitar 1,2 MB karena Three.js ikut disimpan.
- Setiap pembaruan pustaka harus diunduh dan di-commit manual.
- Tidak ada komponen siap pakai, jadi setiap komponen UI ditulis sendiri.

## Alternatif yang ditolak

- **CDN.** Lebih cepat pada kunjungan pertama bagi pengunjung yang cache-nya
  sudah hangat, tetapi menambah ketergantungan dan privasi pihak ketiga.
- **Bundler (Vite).** Menambah langkah build dan salinan hasil. Tidak sepadan
  untuk kode sebesar ini.
