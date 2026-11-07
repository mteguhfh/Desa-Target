# Desa Target

Situs Desa Target, rumahnya komunitas Instagram [@desatarget](https://www.instagram.com/desatarget) (Desa Kejar Target).

Desa Target adalah desa fiktif untuk para UMKM. Followers Instagram menjadi **warga**, dan setiap hari komentar dengan like terbanyak di postingan desa **dibangun** menjadi usaha atau bangunan baru di desa, lalu tampil di Peta Desa situs ini. Kepala Desanya adalah Bang Uju (Si Tuju), maskot Kejar Target.

Situs ini adalah versi 1 (MVP): halaman beranda, pendaftaran KTP Warga, Peta Desa, dan papan peringkat gang. Peta Desa sendiri sudah versi 4: dunia ilustrasi 2000 x 1333 yang bisa digeser buat keliling dan di-zoom, setiap bangunan memakai label nama dan bisa ditap untuk memunculkan popover cerita di balik berdirinya, Bang Uju berdiri di samping Balai Desa siap ditap juga, avatar tiap warga berdiri di zona gang-nya masing-masing, ada mode Keliling Desa buat jalan-jalan memakai karakter pengunjung, dan Balai Desa bisa dimasuki untuk melihat Kantor Desa dari dalam.

**Production:** `https://desa.kejartarget.id`, subdomain dari situs produk Kejar Target. Alamat publik diatur di `config.php` (`APP_BASE_URL`, bisa dioverride dengan environment variable `DESA_TARGET_BASE_URL`).

## Stack

- PHP 8.3, server-rendered, partial HTML di folder `includes/` (siap diswap per bagian bila nanti memakai HTMX)
- SQLite lewat PDO, tanpa Composer, tanpa build step
- Satu stylesheet tulisan tangan (`assets/style.css`)

## Cara jalan lokal

Butuh PHP 8.3 dengan ekstensi `pdo_sqlite`. Dari folder proyek:

```bash
php -S localhost:8000
```

Lalu buka:

- Beranda: http://localhost:8000/index.php
- KTP Warga: http://localhost:8000/ktp.php
- Peta Desa: http://localhost:8000/peta.php

Catatan: halaman memakai `APP_BASE_URL` untuk URL absolut (canonical dan Open Graph). Buat pemakaian lokal penuh, jalankan dengan override:

```bash
DESA_TARGET_BASE_URL=http://localhost:8000 php -S localhost:8000
```

Database dibuat otomatis di `data/desa-target.sqlite` saat pertama kali halaman dibuka atau alat admin dijalankan. File database tidak masuk git (lihat `.gitignore`).

## Struktur proyek

```
desa-target/
├── index.php                  Beranda: logika dan variabel halaman
├── index-markup.php           Markup beranda
├── ktp.php                    Formulir KTP Warga: logika, validasi, dan token
├── ktp-markup.php             Markup formulir dan kartu warga
├── peta.php                   Peta Desa 3D (default): menjalankan peta3d/index.php
├── peta2d.php                 Peta Desa 2D: tampilan alternatif lewat sakelar 3D/2D
├── config.php                 Konfigurasi: base URL publik, nama aplikasi, daftar gang
├── db.php                     Koneksi SQLite, pembuatan tabel, seed Balai Desa, fungsi kueri
├── .htaccess                  Tolak akses web ke config.php dan db.php, header keamanan
├── includes/
│   ├── header.php             Kepala halaman: variabel, escaping, versi stylesheet
│   ├── header-markup.php      Markup kepala halaman dan navigasi
│   ├── footer.php             Kaki halaman bersama
│   ├── footer-markup.php      Markup kaki halaman
│   ├── papan-gang.php         Partial peringkat gang, dipakai beranda dan peta
│   ├── peta-data.php          Data peta: kueri bangunan dan warga, zona gang, titik fokus
│   ├── peta-markup.php        Markup peta: SVG dunia, popover, panel Papan Gang
│   ├── peta-skrip.php         Skrip interaktif peta: geser, zoom, Keliling Desa, patroli
│   ├── sakelar-peta.php       Tombol sakelar 3D/2D, dipakai kedua halaman peta
│   ├── sakelar-peta-skrip.php Pengingat pilihan mode peta di localStorage
│   ├── peta-posisi.php        Koordinat 12 kavling dan zona lima gang pada dunia peta interaktif (2000 x 1333), plus faktor skalanya
│   ├── bangunan-svg.php       Gambar SVG bangunan per jenis usaha, label nama bangunan, penanda tanah kosong, zona gang, dan avatar warga
│   └── interior-balai.php     Adegan SVG dalam Balai Desa (Kantor Desa) yang tampil saat warga masuk gedung
├── peta3d/
│   ├── index.php              Peta desa 3D (Three.js): dunia, kamera orbit, siang-malam.
│   │                          Dijalankan dari /peta3d/ maupun lewat /peta.php
│   ├── templates.php          Galeri template bangunan 3D
│   ├── bangunan3d.js          Konstruksi model bangunan 3D
│   ├── data-desa.php          Endpoint JSON: warga, bangunan, papan gang dari database
│   └── vendor/                Three.js dan OrbitControls, di-host sendiri
├── assets/
│   ├── style.css              Satu stylesheet untuk semua halaman
│   ├── bang-uju.png           Avatar Bang Uju (foto KTP dan hero)
│   ├── bang-uju-wave.png      Bang Uju melambai (hero beranda)
│   └── bang-uju-peta.png      Potongan transparan Bang Uju melambai, khusus berdiri di Peta Desa
├── tools/
│   └── tambah-bangunan.php    Alat admin baris perintah untuk membangun bangunan baru
└── data/                      Tempat database SQLite (isi tidak masuk git, diblokir dari web)
```

Halaman dengan banyak markup dipisah dua berkas: satu berisi logika PHP murni, satu berisi markupnya. Pola ini dipakai supaya berkas logika tetap bisa dibaca utuh oleh alat analisis kode, dan supaya perubahan data tidak tercampur dengan perubahan tampilan.

Satu sumber data untuk dua tampilan peta (lihat ADR-0004): titik kavling, zona gang, titik fokus kamera, dan kolam semuanya didefinisikan di `includes/peta-posisi.php`. Peta 2D memakainya langsung; peta 3D menerimanya lewat `peta3d/data-desa.php`. Peta 3D tidak menyimpan salinan koordinat, jadi memindahkan kavling di `peta-posisi.php` langsung menggeser bangunan di kedua tampilan.

Penanda versi aset dihitung otomatis dari waktu ubah berkasnya (`filemtime`): `assets/style.css` untuk semua halaman, dan `peta3d/bangunan3d.js` untuk peta 3D. Tidak perlu lagi mengganti `?v=` dengan tangan setiap kali berkas itu berubah.

Uji perilaku peta di peramban ada di `tools/uji/` (Playwright). Lihat `tools/uji/README.md`.

## Cara loop hariannya bekerja

1. Di Instagram @desatarget, warga menulis komentar: usaha apa yang mau dibangun di desa.
2. Komentar dengan like terbanyak hari itu menjadi pemenang.
3. Admin menjalankan alat baris perintah dari folder proyek:

   ```bash
   php tools/tambah-bangunan.php "Warung Kopi Senja" "Gang Kuliner" "Sari Wulandari" "Bangun warung kopi dong, Bang!"
   ```

   Urutan argumen: nama bangunan, gang, nama peminta, komentar asal.
4. Bangunan langsung muncul di `peta.php` sebagai bangunan yang berdiri di kavling berikutnya pada peta interaktif, menjadi sorotan "Pembangunan Hari Ini" di beranda, dan penanda tanah kosong berkurang satu. Jenis gambar bangunannya dipilih otomatis dari namanya (warung kopi, bengkel, toko roti, salon, atau ruko biasa) dan warna atapnya mengikuti gang pemintanya.

Alat ini hanya berjalan dari terminal (CLI). Kalau dibuka dari web, akses ditolak.

## Alur KTP Warga

1. Warga membuka `ktp.php`, mengisi nama lengkap dan memilih satu dari lima gang profesi:
   Gang Dagang, Gang Kuliner, Gang Jasa, Gang Freelancer, Gang Kerajinan.
2. Data tersimpan di tabel `warga`. Nomor warga dibuat dari nomor urut pendaftaran dengan format `DT-0001`, `DT-0002`, dan seterusnya.
3. Setelah terkirim, warga diarahkan ke kartu KTP-nya sendiri (pola POST lalu redirect, ditambah token sekali pakai) supaya refresh halaman tidak menggandakan pendaftaran. Kartu menampilkan nama, nomor warga, gang, dan tanggal bergabung, dengan Kepala Desa Bang Uju.
4. Kartu bisa dibuka lagi kapan pun lewat alamat `ktp.php?kartu=<id>`.

## Deployment

Situs production berjalan di VPS Plesk (`<panel-hosting>`, `<ip-server>`) sebagai subdomain `desa.kejartarget.id` di dalam subscription `kejartarget.id`.

- Docroot: `<docroot>` (user sistem `kejartarget`)
- PHP: 8.3 FPM (`<php-fpm>`), ekstensi `pdo_sqlite` tersedia
- SSL: sertifikat Let's Encrypt `kejartarget.id` yang sudah mencakup `*.kejartarget.id`
- DNS: tercakup wildcard proxied `*.kejartarget.id` di Cloudflare, tidak perlu record baru
- Redirect HTTP ke HTTPS dan HSTS diatur di `.htaccess`

Catatan keamanan: `.htaccess` di root menolak akses web langsung ke `config.php` dan `db.php`, dan `data/.htaccess` menolak seluruh folder `data/` supaya file SQLite tidak bisa diunduh.

Deploy ulang (tar + scp, tanpa `.git` dan tanpa database lokal):

```bash
tar czf /tmp/desa-deploy.tar.gz --exclude=.git --exclude='data/*.sqlite' .
scp /tmp/desa-deploy.tar.gz <ssh-anda>:/tmp/
ssh <ssh-anda> 'cd <docroot> && tar xzf /tmp/desa-deploy.tar.gz && chown -R <user-sistem>:psacln .'
```

Database production bisa dibuat ulang/di-seed dengan menjalankan alat baris perintah sebagai user `kejartarget`:

```bash
ssh <ssh-anda> 'cd <docroot> && sudo -u <user-sistem> <php-cli> tools/tambah-bangunan.php "Nama" "Gang" "Peminta" "Komentar"'
```

## Langkah berikutnya

- Halaman daftar warga dan pencarian warga berdasarkan nama atau nomor
- Peta desa yang makin hidup: animasi dan detail suasana tambahan seiring desa makin ramai
- Panel admin web untuk tambah bangunan, menggantikan alat CLI bila volume sudah besar
