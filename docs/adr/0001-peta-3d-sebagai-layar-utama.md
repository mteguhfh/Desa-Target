# ADR-0001: Peta 3D sebagai layar utama, peta 2D sebagai opsi

- Status: Diterima, sebagian digantikan oleh [ADR-0005](0005-beranda-peta-3d.md) (butir 4: beranda kini peta 3D, bukan halaman 2D)
- Tanggal: 2026-11-08
- Diterapkan: 2026-11-08 (`peta.php` 3D default, `peta2d.php` mode 2D, sakelar di kedua halaman)
- Konteks: pembanding produk Sekolah Bebaz, lihat `replica/recon.md`

## Konteks

Desa Target sudah punya dua tampilan peta yang hidup berdampingan:

- `peta.php` — peta 2D SVG yang sudah matang: 12 kavling, zona lima gang,
  avatar warga, mode Keliling Desa, interior Balai Desa, popover detail,
  panel Papan Gang, dan sudah terhubung ke seluruh data `db.php`.
- `peta3d/index.php` — dunia 3D Three.js: bangunan low-poly, siang-malam,
  burung, fog, mode jalan, dan sudah membaca data yang sama lewat
  `peta3d/data-desa.php`.

Saat ini keduanya terpisah: pengunjung yang membuka situs mendarat di beranda
2D, sementara peta 3D hanya bisa dicapai kalau alamatnya diketahui. Belum ada
tautan dari situs utama.

Produk pembanding, Sekolah Bebaz, menempatkan dunia 3D-nya sebagai layar
pertama, dan itulah yang membuat kesan masuknya kuat: pengunjung langsung
masuk ke tempatnya, bukan ke halaman penjelasan.

## Keputusan

1. **Peta 3D menjadi tampilan peta default** di `peta.php`. Pengunjung yang
   menekan menu "Peta Desa" langsung melihat dunia 3D.
2. **Peta 2D tetap dipertahankan** dan bisa diakses lewat satu tombol switch
   yang selalu terlihat di atas dunia 3D. Peta 2D dipindah ke
   `peta2d.php` (isi lama `peta.php`), tanpa perubahan perilaku.
   Catatan implementasi: sakelar tidak memakai redirect. `peta.php` menjalankan
   `peta3d/index.php` langsung (`$dariAkar = true`) supaya alamat tetap
   `/peta.php` dan pengunjung tidak melihat halaman perantara.
3. **Pilihan pengguna diingat** di `localStorage` selama satu kunjungan: kalau
   seseorang memilih mode 2D, membuka peta berikutnya tetap mode 2D. Default
   untuk pengunjung baru tetap 3D.
4. **Beranda tetap 2D.** Beranda (`index.php`) memuat cerita, statistik, dan
   Papan Gang. Yang menjadi 3D adalah halaman peta, bukan seluruh situs.

## Alasan

- Tiga tujuan sekaligus: pertama kali masuk langsung terasa "hidup"; 3D jadi
  pembeda utama; dan 2D tetap jadi jalan keluar yang murah.
- Peta 2D sudah teruji dan jadi jaring pengaman. Kalau WebGL tidak tersedia
  atau perangkat terlalu lemah, pengunjung masih bisa melihat desa.
- Mempertahankan 2D juga menghormati pengunjung yang hanya ingin melihat
  lokasi bangunan tanpa menunggu unduhan Three.js (1,2 MB).

## Konsekuensi

- Bertambah satu berkas (`peta2d.php`) dan satu komponen switch.
- Unduhan awal halaman peta naik sekitar 1,2 MB (Three.js) ditambah modul
  bangunan. Perlu strategi pemuatan bertahap: kerangka halaman tampil dulu,
  dunia 3D menyusul, dengan indikator "Membangun desa…".
- Peta 2D tidak lagi jadi alamat utama, jadi tautan lama yang menunjuk
  `peta.php` akan mendapat versi 3D.
- Perlu pengukuran performa di ponsel kelas bawah sebelum status naik menjadi
  "Diterima".

## Alternatif yang ditolak

- **Menghapus peta 2D sepenuhnya.** Menolak akses ke seluruh desa bagi
  perangkat tanpa WebGL, dan membuang pekerjaan yang sudah matang.
- **Menjadikan seluruh situs 3D seperti pembanding.** Beranda 2D dibutuhkan
  untuk keperluan teks (cerita desa, cara main, papan gang) yang buruk bila
  dipaksa masuk ke dalam dunia 3D.
- **Dua alamat terpisah tanpa switch.** Pengunjung harus tahu alamatnya, yang
  persis masalah yang sedang diperbaiki.
