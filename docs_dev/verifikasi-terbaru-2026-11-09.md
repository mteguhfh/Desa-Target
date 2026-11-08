# Verifikasi fitur terbaru

- Katalog: 40 pemeriksaan lokal lulus, 360/1440px; smoke produksi sudah tercatat di katalog-interior-2026-11-09.md.
- Inbox penerima pada peta 3D: 15/15 lulus. Dua kanvas 3D software dalam satu browser membuat uji timeout; halaman pengirim memakai 2D, penerima tetap 3D. Pemeriksaan jeda kirim dipindah tepat setelah pesan pertama agar tidak kedaluwarsa selama pengujian browser.
- Produksi 360px: model Uju berupa Group 3D berskala 1.05; audio running, tombol mati menyebabkan suspended. Respons HTML diinstrumentasi hanya dalam browser uji untuk membaca model dan AudioContext.
- Percobaan klik Uju produksi pada 360px belum sah: koordinat proyeksi di luar viewport dan popup yang terbaca Balai Desa. Klik karakter belum dianggap lulus.
- Autoplay produksi Chromium langsung running. Pengujian sebelumnya yang menunggu resume pada kebijakan terbatas timeout; HP fisik dan browser yang benar-benar memblokir autoplay belum diverifikasi.
- Tidak mengklaim pemeriksaan suara secara pendengaran atau performa HP fisik.

## Pemeriksaan lanjutan produksi

`tools/uji/uju-audio-produksi.cjs` lulus pada 360/1440px dengan input sentuh dan kebijakan Chromium `document-user-activation-required`:
- Audio awal benar-benar suspended, sentuhan pertama membuat running.
- Tombol mati membuat suspended; sentuhan lain tidak menyalakannya kembali; tombol nyala membuat running.
- Patroli mengubah posisi lebih dari tiga unit.
- Kamera diarahkan ke model asli melalui kait uji yang sudah tersedia. Ketukan koordinat kanvas sesungguhnya membuka popup Bang Uju, lalu tombol tutup bekerja.
- Tidak ada pageerror; screenshot 360px diperiksa, karakter 3D dan popup terbaca tanpa keluar viewport.

Percobaan awal desktop gagal karena kait scene belum siap; penantian diperbaiki dengan pemeriksaan scene sebelum mengakses model. Tidak ada perubahan aplikasi diperlukan.

Sisa: HP fisik dan Safari belum tersedia untuk pengujian; emulasi sentuh Chromium lulus. Blokir DM, pembersihan foto, dan penguatan waktu panen masih backlog. Posisi warga tetap simulasi yang disetujui.
