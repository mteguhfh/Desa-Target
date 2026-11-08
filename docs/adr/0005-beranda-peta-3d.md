# ADR-0005: Beranda adalah peta 3D, beranda lama pindah ke tentang.php

- Status: Diterima
- Tanggal: 2026-11-08
- Diterapkan: 2026-11-08 (`index.php` menjalankan peta 3D, isi beranda lama di `tentang.php`)
- Menggantikan sebagian: [ADR-0001](0001-peta-3d-sebagai-layar-utama.md) butir 4 ("Beranda tetap 2D"). Butir 1 sampai 3 ADR-0001 tetap berlaku.
- Konteks: pembanding produk Sekolah Bebaz, lihat `replica/recon.md`

## Konteks

ADR-0001 memindahkan peta 3D ke `peta.php` dan menjadikannya peta default,
tetapi beranda (`index.php`) tetap halaman 2D: cerita desa, statistik,
"Pembangunan Hari Ini", "Cara Main", dan Papan Gang.

Dua minggu kemudian keadaannya berubah:

- Sekitar 85 persen isi beranda sudah punya tempat sendiri di dalam peta:
  daftar bangunan di tab Bangunan, Papan Gang di tab Gang, pencarian warga di
  tab Warga, misi dan poin di tab Misi, KTP di tab Aku.
- Peta 3D adalah hal yang paling membedakan Desa Target, tetapi pengunjung
  yang membuka alamat utama masih mendarat di halaman teks dan harus menekan
  satu tombol lagi untuk melihatnya.
- Pembanding menempatkan dunia 3D-nya sebagai halaman pertama, dan kesan
  masuk yang dihasilkan itulah yang paling kuat.

## Keputusan

1. **`/` langsung menjalankan peta 3D**, lewat jalur yang sama dengan
   `peta.php` (`$dariAkar = true` di `peta3d/index.php`). Tidak ada halaman
   perantara dan tidak ada pengalihan yang memperpendek umur tautan.
2. **Isi beranda lama tidak dibuang.** `index.php` lama dipindah utuh menjadi
   `tentang.php` (statistik, "Pembangunan Hari Ini", "Cara Main", Papan Gang,
   ajakan bikin KTP), ditambah satu paragraf pembuka dan tautan balik ke peta.
3. **Isi beranda yang sudah punya tempat di panel tidak disalin lagi ke
   panel.** Statistik desa masuk ke panel Bangunan sebagai satu baris ringkas.
   "Pembangunan Hari Ini" adalah bangunan paling atas di daftar Bangunan yang
   sudah ada. "Cara Main" dibaca di `tentang.php`; panel tidak menampung teks
   panjang karena panel itu sempit dan bagian atas dunia 3D harus tetap
   terlihat. Papan Gang sudah ada di tab Gang sejak panel bersayap dibuat.
4. **Pop-up sambutan muncul sekali setelah kamera selesai terbang.** Isinya
   satu kalimat cara main dan ajakan bikin KTP, diingat di `localStorage`
   `dt_sambutan`. Warga yang sudah punya kartu (`dt_nomor` dan `dt_kunci`)
   disapa namanya dan diarahkan ke klaim poin harian, bukan ke halaman KTP.
5. **Judul, meta deskripsi, OG, dan `<noscript>` tetap dipasang di halaman
   beranda.** Tautan yang dibagikan ke Instagram atau WhatsApp harus terlihat
   rapi walau peramban yang membukanya tidak menjalankan 3D.
6. **`tentang.php` tetap disediakan**, ditautkan dari menu situs dan dari
   pop-up sambutan.

## Alasan

- Pengunjung melihat hal terbaik situs ini di layar pertama, bukan setelah
  satu ketukan tambahan.
- `tentang.php` menutup dua kebutuhan yang tidak bisa ditutup `<noscript>`:
  perangkat tanpa WebGL atau dengan HP kelas bawah tetap punya halaman ringan
  untuk memahami situs, dan isi beranda lama tetap bisa ditautkan.
- Pop-up berbasis kartu warga memakai identitas yang sudah ada
  (`dt_nomor` + `dt_kunci`). Tidak ada akun, kata sandi, atau sistem
  identitas baru.

## Konsekuensi

- Unduhan halaman pertama naik menjadi sekitar 1,2 MB (Three.js) ditambah
  modul bangunan. Kerangka halaman tampil dulu, dunia 3D menyusul.
- Tautan lama ke `index.php` sekarang mendapat peta 3D, bukan halaman teks.
  Menu "Tentang" menjaga isi lama tetap terjangkau.
- Pop-up sambutan butuh satu endpoint baca baru (`sapa.php`) yang hanya
  menerima nomor dan kunci, hanya mengembalikan nama dan gang, dan tidak
  membedakan nomor tidak dikenal dari kunci salah.
- Papan Gang di panel tidak lagi menampilkan teks pengantar panjang; teks
  pengantar cukup satu kalimat.

## Alternatif yang ditolak

- **Konten Instagram terbaru di pop-up sambutan.** Tidak ada sumber data IG
  di situs ini, jadi isi pop-up harus ditulis tangan dan cepat basi. Momen
  setelah kamera terbang lebih berharga untuk mengajak masuk ke desa.
- **Menghapus `tentang.php`.** Membuang halaman ringan untuk perangkat lemah
  dan menghapus satu-satunya tempat teks panjang tentang desa.
- **Menyalin seluruh beranda ke panel bersayap.** Panel ditutup setiap kali
  pengunjung menyentuh peta, jadi teks panjang di sana tidak akan dibaca,
  dan bagian atas dunia 3D yang selama ini terjaga jadi sempit.
