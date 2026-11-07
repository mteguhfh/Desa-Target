# Recon map: Sekolah Bebaz (web)

Scope: pola kampus 3D sebagai layar default, panel bertingkat di tab bar bawah, dan cara data warga ditampilkan di dunia 3D. Bukan menyalin isi, merek, atau aset mereka.
For: Desa Target — desa fiktif UMKM milik Kejar Target, dibangun dari komentar Instagram.
Date: 2026-11-08

## Cara recon ini disusun

Sumbernya hanya halaman publik, dibaca sebagai pengunjung biasa. Tidak ada login,
tidak ada pengiriman formulir, tidak ada pembacaan bundel JavaScript mereka, dan
tidak ada pemanggilan API privat. Yang diambil hanya pola UX dan alur, lalu
diterjemahkan ke konsep Desa Target yang sudah ada.

## Sources

| # | source | URL | notes |
| --- | --- | --- | --- |
| 1 | halaman utama (render publik) | https://sekolahbebaz.pages.dev/ | layar 3D fullscreen, tab bar bawah, panel Kampus/Misi/Koperasi/Aku |
| 2 | halaman YouTube | https://sekolahbebaz.pages.dev/youtube.html | absen murid, daftar video, CTA subscribe |
| 3 | observasi browser desktop 1440x900 | (screenshot, `replica/screens/`) | urutan elemen saat muat pertama |
| 4 | observasi browser mobile 390x844 | (screenshot, `replica/screens/`) | tab bar dan tombol tetap terjangkau |

Catatan: mereka tidak punya halaman dokumentasi, halaman harga, changelog,
maupun listing app store. Situsnya satu halaman dengan beberapa tab.

## Core loop

Warga mengikuti Instagram, mendaftar mendapat kartu identitas, lalu namanya
berdiri sebagai avatar di kampus 3D. Semakin banyak yang ikut, semakin ramai
dunianya, dan semakin banyak nama yang muncul di dalamnya.

## Screens

| ID | screen | route / how to reach | purpose | key components | states seen |
| --- | --- | --- | --- | --- | --- |
| S01 | Kampus 3D (default) | `/` | dunia utama, langsung terlihat saat muat | canvas 3D, bilah atas, tombol Jalan-Jalan, zoom, tab bar | loading ("Membangun kampus…"), siang, malam |
| S02 | Panel Kampus | tab `🏫 Kampus` | mencari teman, memilih gaya ngobrol, misi, peta kampus, pembagian kelas | heading bergaya emoji, daftar gedung, tabel kelas | daftar gedung terisi, kelas kosong bila belum mendaftar |
| S03 | Panel Misi | tab `🎯 Misi` | daftar misi harian | kartu misi, penanda notifikasi | ada misi baru (titik merah), selesai |
| S04 | Panel Koperasi | tab `🛒 Koperasi` | etalase produk afiliasi | daftar produk, catatan afiliasi + batas usia | daftar produk |
| S05 | Panel Aku | tab `🪪 Aku` | identitas, koleksi, tautan akun resmi | kartu identitas, koleksi, tautan YouTube/TikTok/IG | belum punya kartu, sudah punya kartu |
| S06 | Halaman YouTube | `/youtube.html` | absen murid di video | daftar absen, langkah 1-2-3, CTA | memuat, daftar absen tampil |

## Flows

```
F01 Pengunjung masuk pertama kali
    S01 (muat 3D, loading lalu terlihat)
    happy path clicks: 0 — dunia langsung tampil
    edge: WebGL tidak didukung, koneksi lambat, layar kecil

F02 Pengunjung melihat identitas dirinya
    S01 -> tap tab Aku -> S05
    happy path clicks: 1
    edge: belum mendaftar (kartu kosong), sudah mendaftar

F03 Pengunjung mencari teman
    S01 -> tab Kampus -> S02 -> ketik username
    happy path clicks: 2
    edge: teman belum terdaftar, nama tidak ada

F04 Pengunjung masuk ke sebuah gedung
    S01 -> tap gedung di dunia 3D (atau dari daftar Peta kampus di S02)
    happy path clicks: 1
    edge: gedung kosong, gedung penuh

F05 Pengunjung berjalan keliling
    S01 -> tombol "Jalan-Jalan" -> mode jalan
    happy path clicks: 1
    edge: buntu, keluar area, kembali ke kamera bebas
```

## Components

| component | variants | states | used on |
| --- | --- | --- | --- |
| Dunia 3D | kamera orbit, mode jalan | loading, siang, malam, interaktif | S01 |
| Bilah identitas atas | nama, statistik, status suasana | terisi | S01 |
| Tombol aksi utama | tombol bulat besar | default, aktif | S01 |
| Kontrol zoom | + / − | default, batas atas/bawah | S01 |
| Tab bar bawah | 4 tab | aktif, tidak aktif, ada notifikasi | S01-S05 |
| Panel bersayap | kepala + isi menggulir | terbuka, tertutup | S02-S05 |
| Label nama melayang | nama warga di dunia 3D | terlihat, tersembunyi saat jauh | S01 |
| Kartu identitas | milik sendiri, dilihat orang lain | kosong, terisi | S05 |
| Daftar gedung | baris nama gedung | terisi | S02 |
| Kartu misi | harian | belum, selesai | S03 |
| Kartu produk | afiliasi | terisi | S04 |

## Inferred data model

```
Warga      id, username, nama_tampil, gang/profesi, tanggal_daftar, posisi_di_dunia
           evidence: kartu identitas di S05, label @username di S01, Pencarian teman di S02
           confidence: high (kita sudah punya tabel warga)

Bangunan   id, nama, jenis, gang, posisi (x,y,z), status (kosong/berdiri), peminta, komentar
           evidence: label gedung di S01, daftar Peta kampus di S02
           confidence: high (kita sudah punya tabel bangunan)

Misi       id, judul, deskripsi, tanggal, selesai
           evidence: S03 "Misi hari ini"
           confidence: medium

Kelas      nama kelas, huruf awal username, daftar warga
           evidence: S02 "Pembagian kelas", ditentukan huruf pertama username
           confidence: high (aturan dinyatakan terbuka di teks halaman)
```

Relationships: Gang 1-n Warga, Gang 1-n Bangunan, Kelas 1-n Warga.

## Feature matrix

See `features.csv`. Must: 11, should: 6, could: 5, skip: 5.

## Out of scope (cannot or should not be cloned)

- **Isi, merek, dan aset Sekolah Bebaz.** Nama, logo, karakter Pak Kepsek, dan ilustrasi mereka milik mereka. Desa Target sudah punya identitas sendiri (Bang Uju, gang UMKM).
- **Jaringan dan datanya.** 50 ribu murid dan 124 ribu kartu adalah milik mereka. Angka Desa Target tumbuh dari komentarku sendiri, bukan ditarik dari mereka.
- **Koperasi afiliasi TikTok Shop.** Itu perjanjian komersial pribadi mereka, bukan fitur produk. Desa Target tidak perlu dan tidak sebaiknya mengikuti.
- **Akun YouTube dan video.** Video milik kanal mereka.
- **Kartu identitas fisik/merchandise** (hoodie YouTube). Itu hadiah fisik, bukan perangkat lunak.

## Size

Screens 6, flows 5, entities 4. Hard parts: performa 3D di ponsel kelas bawah, mengubah peta 2D yang sudah jalan menjadi sumber utama data untuk dunia 3D, mode jalan yang tidak menembus bangunan, dan menjaga agar dua tampilan (3D dan 2D) tidak saling bertentangan saat data berubah. Size: M.
