# Desa Target

Situs Desa Target, rumahnya komunitas Instagram [@desatarget](https://www.instagram.com/desatarget) (Desa Kejar Target).

Desa Target adalah desa fiktif untuk para UMKM. Followers Instagram menjadi **warga**, dan setiap hari komentar dengan like terbanyak di postingan desa **dibangun** menjadi usaha atau bangunan baru di desa, lalu tampil di Peta Desa situs ini. Kepala Desanya adalah Bang Uju (Si Tuju), maskot Kejar Target.

Situs ini adalah versi 1 (MVP): halaman beranda, pendaftaran KTP Warga, Peta Desa, dan papan peringkat gang.

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
├── index.php                  Beranda: hero, penghitung warga, pembangunan terbaru, papan gang
├── ktp.php                    Formulir KTP Warga dan tampilan kartu warga
├── peta.php                   Peta desa (kavling bangunan dan tanah kosong) dan papan gang
├── config.php                 Konfigurasi: base URL publik, nama aplikasi, daftar gang
├── db.php                     Koneksi SQLite, pembuatan tabel, seed Balai Desa, fungsi kueri
├── .htaccess                  Tolak akses web ke config.php dan db.php, header keamanan
├── includes/
│   ├── header.php             Kepala halaman dan navigasi bersama
│   ├── footer.php             Kaki halaman bersama
│   └── papan-gang.php         Partial peringkat gang, dipakai beranda dan peta
├── assets/
│   ├── style.css              Satu stylesheet untuk semua halaman
│   ├── bang-uju.png           Avatar Bang Uju (foto KTP dan hero)
│   └── bang-uju-wave.png      Bang Uju melambai (hero beranda)
├── tools/
│   └── tambah-bangunan.php    Alat admin baris perintah untuk membangun bangunan baru
└── data/                      Tempat database SQLite (isi tidak masuk git, diblokir dari web)
```

## Cara loop hariannya bekerja

1. Di Instagram @desatarget, warga menulis komentar: usaha apa yang mau dibangun di desa.
2. Komentar dengan like terbanyak hari itu menjadi pemenang.
3. Admin menjalankan alat baris perintah dari folder proyek:

   ```bash
   php tools/tambah-bangunan.php "Warung Kopi Senja" "Gang Kuliner" "Sari Wulandari" "Bangun warung kopi dong, Bang!"
   ```

   Urutan argumen: nama bangunan, gang, nama peminta, komentar asal.
4. Bangunan langsung muncul di `peta.php` sebagai kavling terisi, menjadi sorotan "Pembangunan Hari Ini" di beranda, dan kavling tanah kosong berkurang satu.

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
- Peta desa yang lebih hidup: ikon bangunan per jenis usaha, bukan hanya kavling
- Panel admin web untuk tambah bangunan, menggantikan alat CLI bila volume sudah besar
