# ADR-0006: Ruko dibuka di panel peta, bukan halaman baru

- Status: Diterima
- Tanggal: 2026-11-08
- Diterapkan: 2026-11-08 (tab Ruko dan tombol ruko di kontrol 3D membuka panel `data-isi="ruko"`)
- Berkaitan: [ADR-0005](0005-beranda-peta-3d.md), Fitur 3 (`ruko.php`)

## Konteks

Fitur 3 memasang pintu masuk Ruko di tab bar sebagai pil kecil di ujung
kanan, dan pil itu menautkan ke `ruko.php`, halaman terpisah. Saat diuji
dengan tab bar yang kini berisi lima item, dua masalah muncul:

- Pil Ruko digambar berbeda dari empat tab lain (lebih kecil, berwarna,
  menempel di tepi), sehingga baris bawah terlihat tidak rata.
- Mengetuk Ruko meninggalkan dunia 3D sepenuhnya, padahal tab Bangunan,
  Warga, Gang, dan Pasar semuanya membuka panel bersayap tanpa
  meninggalkan peta. Ruko justru satu-satunya tab yang berpindah halaman.

## Keputusan

1. **Tab Ruko menjadi tab penuh kelima**, digambar sama dengan Bangunan,
   Warga, Gang, dan Aku (lima kolom sama lebar, ikon dan teks sama besar).
2. **Tab Ruko membuka panel Ruko di dalam peta** (`data-isi="ruko"`), bukan
   halaman baru. Tombol ruko bundar di kontrol 3D juga membuka panel yang
   sama dan tidak lagi berupa tautan.
3. **Isi panel mengikuti keadaan warga**, sama seperti panel lain:
   - tamu: ajakan bikin KTP dan tautan ke `ktp.php`
   - sudah punya kartu tapi belum punya ruko: ajakan buka ruko
   - punya ruko: nama usaha, gang, cerita, dan tautan jualan
4. **Lembar formulir tetap di `ruko.php`.** Panel hanya membaca ruko
   sendiri lewat `ruko.php?json=1&nomor=...&kunci=...`; klaim, ubah, dan
   lepas ruko tetap lewat halaman dengan token sesi, sama seperti `ktp.php`.
   Formulir panjang di panel sempit akan mendorong bagian atas dunia 3D
   keluar layar.

## Alasan

- Pindah halaman untuk melihat ruko sendiri memutus pengalaman 3D yang
  justru jadi alasan orang membuka situs ini. Panel menjaga dunia tetap
  terlihat, sama seperti tab lain.
- Satu perilaku untuk semua tab lebih mudah dipelajari daripada satu tab
  istimewa.
- Membaca lewat JSON dari `ruko.php` menjaga satu sumber aturan: panel dan
  halaman memakai `warga_dari_nomor_dan_kunci()` dan `ruko_milik()` yang
  sama, jadi tidak ada logika kavling atau pemilik kedua.

## Konsekuensi

- `ruko.php` mendapat jalur `?json=1` yang hanya membaca. Jawaban galat
  untuk nomor tidak dikenal dan kunci salah sengaja sama (403), sama seperti
  endpoint lain.
- Leher botolnya kartu warga: panel hanya bisa menampilkan ruko bila
  `dt_nomor` dan `dt_kunci` ada di peramban. Kartu dibuka sekali di
  `ktp.php` dan disimpan di sana.
- Tombol ruko bundar di kontrol 3D tidak lagi punya `href`, jadi ruko tidak
  bisa dibuka di tab baru. Itu disengaja: ruko sekarang isi panel.

## Alternatif yang ditolak

- **Pil Ruko tetap dipertahankan, hanya dirapikan di tengah baris.** Masih
  menyisakan perilaku berbeda (halaman baru) dan satu warna tambahan yang
  tidak ada di tab lain.
- **Formulir klaim ruko langsung di dalam panel.** Panel bersayap ditutup
  setiap kali pengunjung menyentuh peta, dan formulir tiga kolom plus
  validasi akan memakan seluruh tingginya.
