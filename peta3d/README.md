# Peta Desa Target 3D (Prototipe v5)

Prototipe peta Desa Target sebagai dunia 3D low-poly sungguhan memakai
Three.js (WebGL), menggantikan dunia SVG 2D di `peta.php` (v4.x) tanpa
mengubah tata letak desa, data, maupun aturan mainnya. Ini prototipe
visual dan interaksi: datanya masih contoh statis (seed), belum terhubung
ke basis data produksi.

## Cara menjalankan

Cukup sajikan folder ini dengan server statis apa pun, lalu buka
`index.html`:

```bash
cd desa-target3d
python3 -m http.server 8811
# buka http://localhost:8811/index.html
```

Membuka berkas langsung lewat `file://` tidak bisa, karena modul ES dan
tekstur gambar dimuat lewat HTTP. Three.js r160 dimuat dari CDN
(unpkg, dipin lewat importmap di `index.html` dan `templates.html`);
untuk produksi, pustaka ini wajib di-host sendiri (lihat bagian
integrasi). Satu-satunya aset lokal adalah gambar maskot resmi
`assets/bang-uju-peta.png`, disalin dari repo Desa Target.

Halaman pendamping `templates.html` memamerkan kelima template bangunan
prosedural dalam satu barisan, sebagai bukti jalur template untuk
bangunan hasil komentar.

## Isi folder

| Berkas | Isi |
| --- | --- |
| `index.html` | Peta 3D lengkap: dunia, kamera orbit, mode Keliling Desa, warga wara-wiri, patroli Bang Uju, panel Papan Gang, popover. |
| `bangunan3d.js` | Pabrik bangunan prosedural bersama: `buatBalai`, `buatKopi`, `buatBengkel`, `buatBakery`, `buatSalon`, `buatRuko`, plus pemilih `varianBangunan(nama)` dan `buatBangunan(nama, warnaAtap)`. |
| `templates.html` | Galeri kelima template bangunan. |
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
  Dekat pintu Balai Desa, chip "Masuk Balai Desa" muncul; untuk saat ini
  chip itu stub (lihat daftar stub).

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
  `index.html?stats` untuk melihat angka langsung di peramban.

## Daftar stub dan batas jujur prototipe

- Data warga, jumlah Papan Gang, dan isi popover adalah seed statis di
  dalam `index.html` (10 warga contoh), bukan basis data.
- Chip "Masuk Balai Desa" adalah stub: halaman interior Balai Desa 3D
  belum dibangun (interior 2D di `includes/interior-balai.php` adalah
  acuan isinya kelak).
- Tautan Beranda, Bikin KTP Warga, Instagram, Gabung gang-mu, dan
  tautan cadangan peta 2D semuanya placeholder `#`.
- Three.js dimuat dari CDN; belum ada mekanisme fallback bila CDN mati
  selain pesan WebGL tidak tersedia.
- Hanya Balai Desa yang berdiri; kelima template bangunan lain hanya
  tampil di `templates.html`, belum ada contoh bangunan warga di peta
  utama (disengaja, supaya kebenaran "baru Balai yang dibangun" tetap
  terjaga seperti produksi).
- Suara, misi harian, poin, dan fitur v5 lain di dokumen konsep belum
  termasuk; ini murni lapisan peta.

## Jalur integrasi produksi

1. Host sendiri Three.js: unduh `three.module.js` dan
   `examples/jsm/controls/OrbitControls.js` r160 ke repo Desa Target
   (misalnya `assets/vendor/three/`), ubah importmap di halaman peta
   menunjuk ke sana. Tidak ada dependensi lain.
2. Ganti badan `peta.php`: pertahankan header, panel, dan chrome UI
   yang ada; SVG dunia diganti `<div id="panggung">` plus skrip modul
   dari prototipe ini. PHP mencetak data sungguhan sebagai JSON
   (daftar bangunan dari `bangunan_terbaru()` / tabel bangunan, warga
   per gang dari basis data, posisi dari `peta-posisi.php`), menggantikan
   konstanta seed di prototipe.
3. Untuk tiap bangunan dari basis data, panggil
   `buatBangunan(nama, warnaAtapGang(gang))` dari `bangunan3d.js` dan
   taruh di `POSISI_KAVLING[indeks]`; kavling sisanya penanda Tanah
   Kosong. Aturan kata kunci varian sudah sama dengan
   `varian_bangunan()` di `bangunan-svg.php`, jadi bangunan yang sama
   mendapat bentuk yang setara di 2D dan 3D.
4. Interior Balai Desa: bangun ruangan 3D sederhana (loket KTP, papan
   pengumuman dari data bangunan terbaru, arsip dokumen) mengikuti isi
   `interior-balai.php`, dipicu chip "Masuk Balai Desa".
5. Pertahankan peta 2D sebagai fallback: bila WebGL gagal, tampilkan
   pesan seperti di prototipe dan arahkan ke `peta.php` versi SVG.
   Uji di HP kelas menengah sebelum mengganti default, dan jaga batas
   draw call di kisaran prototipe ini.
