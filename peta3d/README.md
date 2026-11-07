# Peta Desa Target 3D (v5)

Peta Desa Target sebagai dunia 3D low-poly sungguhan memakai Three.js
(WebGL), menggantikan dunia SVG 2D di `peta.php` (v4.x) tanpa mengubah
tata letak desa, data, maupun aturan mainnya.

Sejak tahap 2, peta ini membaca data produksi sungguhan: saat halaman
dibuka, `index.php` memanggil endpoint `data-desa.php` di folder ini,
yang membaca basis data SQLite situs induk lewat `../db.php` (warga,
bangunan, dan papan gang). Bila endpoint tidak bisa dibaca (halaman
dibuka mandiri di luar situs, atau basis data galat), peta jatuh ke
data contoh bawaan dan kartu intro menampilkan catatan "data contoh".
Bila data langsung berhasil dimuat, catatan itu berganti keterangan
data langsung dari balai desa.

## Cara menjalankan

Di dalam situs (cara produksi): folder ini tinggal disajikan sebagai
bagian dari docroot Desa Target, lalu buka `peta3d/index.php` dari
domain situs. Endpoint PHP-nya butuh server PHP, sama seperti situs
induknya.

Mandiri (buat menilai visual saja): sajikan folder ini dengan server
statis apa pun, lalu buka `index.php`:

```bash
python3 -m http.server 8811
# buka http://localhost:8811/index.php (mode data contoh)
```

Membuka berkas langsung lewat `file://` tidak bisa, karena modul ES dan
tekstur gambar dimuat lewat HTTP. Three.js r160 sudah di-host sendiri
di `vendor/` (importmap menunjuk ke sana, tidak ada lagi CDN). Aset
lokal lain adalah gambar maskot resmi `assets/bang-uju-peta.png`,
disalin dari repo Desa Target.

Halaman pendamping `templates.php` memamerkan kelima template bangunan
prosedural dalam satu barisan, sebagai bukti jalur template untuk
bangunan hasil komentar.

## Isi folder

| Berkas | Isi |
| --- | --- |
| `index.php` | Peta 3D lengkap: dunia, kamera orbit, mode Keliling Desa, warga wara-wiri, patroli Bang Uju, panel Papan Gang, popover. Memuat data dari `data-desa.php`, jatuh ke data contoh bila gagal. |
| `data-desa.php` | Endpoint JSON hanya-baca: warga (nomor, nama, gang), bangunan (kavling = indeks urutan id, nama, gang, komentar, dari, tanggal), dan papan gang, dari basis data situs induk. |
| `bangunan3d.js` | Pabrik bangunan prosedural bersama: `buatBalai`, `buatKopi`, `buatBengkel`, `buatBakery`, `buatSalon`, `buatRuko`, plus pemilih `varianBangunan(nama)` dan `buatBangunan(nama, warnaAtap)`. |
| `templates.php` | Galeri kelima template bangunan. |
| `vendor/` | Three.js r160 yang di-host sendiri: `three.module.js` dan `addons/controls/OrbitControls.js`. |
| `assets/bang-uju-peta.png` | Sprite Bang Uju (billboard) untuk patroli alun-alun. |

## Model data: cerminan peta 2D

Semua koordinat meniru `includes/peta-posisi.php` apa adanya, dalam
satuan dunia yang sama (2000 x 1333):

- `POSISI_KAVLING`: 12 titik jangkar kavling; kavling 0 adalah Balai Desa
  di alun-alun tengah desa (fokus 1017, 692). Bangunan mengisi kavling
  mengikuti urutan id, sama seperti peta 2D; 11 kavling lain tampil
  sebagai penanda Tanah Kosong.
- `ZONA_GANG`: 5 zona gang dengan petak `tambak` (koordinat lokal versi
  2, dikalikan `SKALA_DUNIA = 1.6667` di mana perlu), posisi papan nama,
  dan warna gang yang sama dengan `warna_atap_gang()` di 2D (Dagang
  biru, Kuliner oranye, Jasa toska, Freelancer ungu, Kerajinan merah
  muda).
- Rect wara-wiri warga dihitung persis seperti `$zonaUntukJs` di
  `peta.php` v4.3: margin sisi 14 satuan lokal, atas 18, bawah 12.
- Jalan utama memakai kurva yang sama dengan path jalan di SVG 2D
  (rangkaian titik kontrol di `TITIK_JALAN`), alun-alun adalah elips
  plaza 2D yang menjadi lingkaran paving, dan kolam memakai pusat dan
  radius elips kolam 2D (1065, 718 lokal, rx 112 lokal).
- Pemetaan koordinat: titik 2D `(x, y)` menjadi 3D
  `(x - 1000, 0, y - 666.5)`. Utara peta 2D menjadi arah -Z, selatan
  menjadi +Z, jadi fasad bangunan menghadap kamera awal.

## Aturan gerak (diporting dari v4.3)

- Warga adalah agen kecil: berdiam, memilih titik tujuan acak di dalam
  rect zona gang-nya (jarak 34 sampai 200 satuan, jalur lurus ditolak
  bila menembus tapak kavling `RINTANGAN_WARA`), berjalan pelan, lalu
  berdiam 2,5 sampai 9 detik. Paling banyak 12 warga bergerak bersamaan.
  Avatar yang popover-nya terbuka berhenti bergerak.
- Bang Uju berpatroli mengelilingi alun-alun lewat `TITIK_PATROLI` yang
  sama dengan v4.3, sebagai sprite billboard bergambar maskot resmi
  (selalu menghadap kamera, bentuknya persis versi 2D).
- Warga dan Uju berhenti bergerak selama mode Keliling Desa aktif, tab
  tidak terlihat, atau pengunjung memakai `prefers-reduced-motion`
  (mode itu juga mematikan gelombang bendera, kerlip air, dan awan).

## Kamera dan mode Keliling Desa

- Kamera orbit (OrbitControls): putar, geser (target dijepit di dalam
  batas desa), zoom 240 sampai 2500, sudut polar dibatasi supaya tidak
  masuk ke bawah tanah. Saat halaman dibuka, kamera membingkai seluruh
  desa dari sudut miring seperti isometrik. Tombol reset ("Lihat seluruh
  desa") mengembalikan bingkai ini.
- Mode Keliling Desa: kamera turun ke belakang pengunjung "Kamu"
  (figur toska bertopi merah, mulai di depan Balai Desa seperti v2),
  bergerak dengan WASD / tombol panah / D-pad di layar. Tabrakan
  lingkaran lawan persegi memakai tapak Balai Desa dan penanda kavling.
  Dekat pintu Balai Desa, chip "Masuk Balai Desa" muncul dan mengarah
  ke halaman Balai Desa situs induk (`../balai.php`).

## Performa

- `devicePixelRatio` dibatasi 2. Pohon (3 varian), semak, batu, rumpun
  rumput, garis tengah jalan, lampu, dan teratai memakai `InstancedMesh`.
- Satu directional light dengan shadow map 2048 dan kamera bayangan
  seukuran desa, plus hemisphere light. Material flat-shaded
  `MeshStandardMaterial`.
- Jumlah draw call dan segitiga terukur dari hitungan scene graph
  (menjalankan kode adegan asli dengan renderer tiruan di Node):
  222 draw call dan 39.253 segitiga untuk seluruh desa termasuk
  bayangan dimatikan; angka render WebGL sungguhan bisa sedikit berbeda
  karena pass bayangan menggambar ulang sebagian objek. Buka
  `index.php?stats` untuk melihat angka langsung di peramban.

## Daftar stub dan batas jujur

- Data contoh (10 warga, 1 bangunan) hanya dipakai sebagai cadangan
  bila `data-desa.php` tidak terbaca; di dalam situs, yang tampil
  adalah data basis data sungguhan.
- Interior Balai Desa 3D belum dibangun; chip "Masuk Balai Desa"
  mengarah ke halaman Balai Desa 2D situs induk.
- Bangunan dari basis data menempati kavling mengikuti urutan id,
  persis aturan peta 2D; bila jumlah bangunan melebihi 12 kavling,
  sisanya tidak digambar (sama seperti batas peta 2D).
- Warga yang digambar dibatasi 80 figur (batas yang sama dengan peta
  2D); warga selebihnya tetap terhitung di Papan Gang.
- Suara, misi harian, poin, dan fitur v5 lain di dokumen konsep belum
  termasuk; ini murni lapisan peta.

## Jalur integrasi produksi

1. ~~Host sendiri Three.js~~ Selesai di tahap 2: r160 ada di `vendor/`,
   importmap menunjuk ke berkas lokal. Tidak ada dependensi lain.
2. ~~Data sungguhan dari basis data~~ Selesai di tahap 2 lewat
   `data-desa.php`, tanpa mengubah `peta.php`: halaman 3D tetap berkas
   mandiri di subfolder `peta3d/`, peta 2D tetap jadi fallback penuh
   (termasuk pesan WebGL tidak tersedia yang mengarah ke `../peta.php`).
3. Untuk tiap bangunan dari basis data, peta memanggil
   `buatBangunan(nama, warnaGang(gang))` dari `bangunan3d.js` dan
   menaruhnya di `POSISI_KAVLING[kavling]`; kavling sisanya penanda
   Tanah Kosong. Aturan kata kunci varian sudah sama dengan
   `varian_bangunan()` di `bangunan-svg.php`, jadi bangunan yang sama
   mendapat bentuk yang setara di 2D dan 3D.
4. Interior Balai Desa 3D: bangun ruangan 3D sederhana (loket KTP,
   papan pengumuman dari data bangunan terbaru, arsip dokumen)
   mengikuti isi `interior-balai.php`, menggantikan tautan keluar chip
   "Masuk Balai Desa".
5. Uji di HP kelas menengah sebelum mempromosikan peta 3D jadi
   default, dan jaga batas draw call di kisaran saat ini.
