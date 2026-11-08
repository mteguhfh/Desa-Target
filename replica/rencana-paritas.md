# Rencana paritas Sekolah Bebaz

Tracker kerja untuk menutup selisih antara Desa Target dan
`https://sekolahbebaz.pages.dev/`. Dokumen ini yang dipakai untuk tahu apa yang
sudah selesai, apa yang sedang dikerjakan, dan apa yang sengaja dilewati.
Rencana lama di `replica/rencana-kerja.md` tetap berlaku untuk fitur dasar;
berkas ini khusus untuk gelombang paritas.

Disusun 2026-11-08 setelah menjelajah langsung situs pembanding: membuka
keempat tab, panel Kampus, Misi, Koperasi, Aku, alur Bikin KTA, pilih tampilan,
Pergi ke, Daftar gedung, Kelas, Cari temanmu, Cari murid, dan mode jalan.

## Cara pakai

- `[ ]` belum dikerjakan
- `[~]` sedang dikerjakan
- `[x]` selesai dan sudah diuji
- `[-]` sengaja tidak dikerjakan, alasannya ditulis

Aturan status:

1. Satu item baru boleh dicentang setelah `php -l` bersih, uji baru lulus, uji
   lama lulus, dan halaman terkait membalas 200 di server lokal.
2. Setiap item wajib menyebut berkas yang disentuh dan ukuran selesainya.
   Tanpa itu, item dianggap belum selesai walau tampilannya sudah bagus.
3. Ubah tanggal di bagian Riwayat setiap kali ada status yang berubah.
4. Jangan menyalin aset, teks, atau merek Sekolah Bebaz. Yang diambil hanya
   pola interaksinya.

## Ringkasan

| Gelombang | Isi | Jumlah | Selesai |
| --- | --- | --- | --- |
| 0 | Simpan dan amankan yang sudah jalan | 6 | 6 |
| 1 | Identitas dan alasan balik lagi | 8 | 4 |
| 2 | Navigasi dan dunia yang terasa hidup | 8 | 0 |
| 3 | Permainan, katalog, dan rasa visual | 5 | 0 |
| **Total** | | **27** | **10** |

Lihat bagian [Pekerjaan berikutnya](#pekerjaan-berikutnya) untuk urutan yang
disarankan.

## Kondisi awal

Sudah setara atau lebih: peta 3D dan 2D dengan sakelar, mode jalan dan D-pad,
KTP bernomor dengan kunci rahasia, poin, misi, chat frasa preset, ruko, Pasar
Malam dan CFD, papan gang, siang dan malam, suasana ramai atau sepi, serta
panel bersayap yang tidak menutup dunia.

Selisih yang paling terasa, urut dari yang paling membuat kalah:

| Pembanding | Desa Target | Gelombang |
| --- | --- | --- |
| Dandani karakter dengan pratinjau saat bikin KTA | Pemilih warna ada di halaman KTP, tetapi tidak ada pratinjau, tidak ada di panel Aku, dan avatar tidak tampil di kartu | 1 |
| Misi punya bar kemajuan, streak, dan tombol aksi | Daftar misi dengan tombol tandai manual | 1 |
| Koleksi lencana dan hadiah | Belum ada | 1 |
| Hitungan hari dan banner kabar | Belum ada | 1 |
| Kode pemulihan kartu | Kunci kartu justru ikut tercetak di halaman publik | 1 |
| Pergi ke tujuan dengan Antar atau Ikuti | Hanya ketuk bangunan | 2 |
| Lihat dari atas | Hanya zoom manual | 2 |
| Titik nongkrong tiap warga dan cari teman | Pencarian hanya menampilkan daftar | 2 |
| Daftar gedung dengan kapasitas | Daftar bangunan dengan gang | 2 |
| Tombol aksi kontekstual saat dekat bangunan | Belum ada | 2 |
| Dunia padat oleh properti dan avatar | 80 figur, properti masih sedikit | 2 |
| Mini game | Belum ada | 3 |
| Katalog produk berkategori | Daftar ruko dan lapak | 3 |
| Interior semua gedung | Baru Balai Desa | 3 |
| Gaya visual kertas board game | Putih biru ala dasbor | 3 |

## Temuan penting di luar paritas

**Sudah ditutup 2026-11-08 (lihat 0.5).** Dulu `ktp.php?kartu=207` mencetak
`$kunciKartu` untuk setiap kartu yang dibuka, dan nomor kartu berurutan.
Artinya siapa pun bisa membuka kartu warga lain lalu memakai kuncinya untuk
klaim poin, chat, atau ruko. Komentar di `ktp.php` menyebut kunci hanya untuk
pemilik, tetapi kodenya tidak membedakan pemilik dari pengunjung. Ini bukan
fitur paritas, tetapi harus ditutup. Kini kunci hanya dicetak untuk kartu
yang baru dibuat di sesi itu; sisa pekerjaannya (kode pemulihan) ada di 1.7.

---

## Gelombang 0: simpan dan amankan yang sudah jalan

Pekerjaan sesi sebelumnya (beranda peta 3D, pop-up sambutan, panel Aku, panel
Ruko, gambar usaha) masih belum masuk riwayat. Gelombang baru tidak boleh
dimulai sebelum ini bersih, supaya kalau ada yang rusak gampang dilacak.

- [x] **0.1 Periksa semua berkas berubah**
  Berkas: seluruh `git status`. Ukuran selesai: `php -l` bersih untuk semua
  berkas PHP, tidak ada berkas percobaan tertinggal, `data/*.sqlite` tidak ikut.
  Hasil 2026-11-08: lint bersih, `data/*.sqlite` sudah masuk `.gitignore`.
- [x] **0.2 Jalankan seluruh uji lama**
  Berkas: `tools/uji/*.js`. Ukuran selesai: adr0004, dompet-poin,
  peta-3d-sakelar, tab-bar-panel, tahap2, tata-letak, chat, ruko, mode-jalan,
  dan pasar lulus semua.
  Hasil 2026-11-08: `ruko.js` 46/46 setelah batas kavling uji dilonggarkan;
  seluruh uji dijalankan ulang setelah pemilih 5 tab dan pop-up sambutan:
  adr0004 11/11, dompet-poin 47/47, chat 64/64, ruko 46/46, mode-jalan 19/19,
  tahap2 31/31, tata-letak 6/6, pasar 36/36, peta-3d-sakelar 20/20,
  tab-bar-panel 130/130. Uji usang yang diperbaiki: hitungan tab 4 menjadi 5,
  pil Ruko dihapus, pop-up sambutan dilewati lewat `dt_sambutan`, dan klaim
  pasar diuji dengan jam acara tetap agar tidak bergantung jam mesin.
- [x] **0.3 Tulis uji baru untuk pekerjaan sesi lalu**
  Berkas baru: `tools/uji/beranda-peta.js`, `sambutan.js`, `aku-panel.js`,
  `ruko-panel.js`, `gambar-usaha.js`. Ukuran selesai: beranda `/` benar-benar
  peta 3D, `tentang.php` 200, pop-up muncul sekali lalu diingat, KTP dari panel
  menyimpan kunci, tab Ruko kelima dan sejajar, gambar usaha bisa dipilih,
  diubah, dan dihapus.
  Hasil: lima berkas uji ditambahkan dan lulus 44 pemeriksaan: beranda 7,
  sambutan 8, panel Aku 8, panel Ruko 12, gambar usaha 9. Data baru yang
  dibuat kelima uji dibersihkan berdasarkan nomor warga uji.
- [x] **0.4 Perbarui catatan fitur**
  Berkas: `replica/features.csv`, `replica/rencana-kerja.md`,
  `tools/uji/README.md`. Ukuran selesai: status dan bukti cocok dengan kode.
  Hasil 2026-11-08: `features.csv` memuat tab bar lima tab, beranda peta 3D,
  pop-up sambutan, KTP dari panel Aku, panel Ruko, dan gambar usaha (25
  selesai, 2 belum, 5 sengaja tidak dikerjakan). `README.md` uji memuat
  `ktp-kunci.js` dan hitungan lima tab.
- [x] **0.5 Tutup kebocoran kunci kartu**
  Berkas: `ktp.php`, `ktp-markup.php`, `warga-markup.php`,
  `includes/cari-warga-skrip.php`, `tools/uji/*`.
  Ukuran selesai: `ktp.php?kartu=N` milik pengunjung tidak lagi mencetak
  `dt_kunci`; pemilik yang baru membuat kartu tetap menerima kuncinya sekali
  lewat sesi; pemilik yang localStorage-nya hilang memakai kode pemulihan
  (lihat 1.7); uji membuktikan halaman publik tidak memuat kunci.
  Hasil 2026-11-08: kebocoran ditutup. `ktp.php` hanya mencetak kunci bila
  `$_SESSION['kartu_baru']` sama dengan nomor kartu yang dibuka. Uji baru
  `tools/uji/ktp-kunci.js` lulus 4/4: kartu publik tanpa `#ktpIdentitas`,
  tanpa `data-kunci`, dan kunci tidak muncul di HTML. Sisa pekerjaan: kode
  pemulihan kini tersedia melalui 1.7 dengan kode sekali pakai.
- [x] **0.6 Commit, push, deploy**
  Ukuran selesai: `git status` bersih, situs live memuat beranda peta 3D.
  Catatan: data contoh ruko dan pasar di DB lokal bisa dihapus dengan
  `php tools/isi-data-ruko-pasar.php --hapus` sebelum deploy.
  Hasil 2026-11-08: commit `24b103b` di-push ke `origin/main`, lalu di-deploy
  ke `/var/www/vhosts/kejartarget.id/site2`. Semua halaman wajib membalas 200,
  `config.php`/`db.php`/`data/` 403, dan http 301 ke https. `DEPLOY.local.md`
  sempat ikut terunggah lalu dihapus dari server; perintah tar di berkas itu
  sekarang mengecualikannya.

---

## Gelombang 1: identitas dan alasan balik lagi

### 1.1 Pemilih tampilan yang utuh dan tersimpan

- [x] Panel Aku punya pemilih warna kulit, baju, dan topi seperti halaman KTP,
  dan pilihan itu ikut dikirim saat KTP dibuat dari panel.
- [ ] `ktp-json.php` menerima `warna_baju`, `warna_kulit`, `warna_topi`
  memakai `warna_sah()` seperti `ktp.php`, bukan selalu memakai warna bawaan.
- Berkas: `includes/panel-bersayap.php`, `includes/aku-skrip.php`,
  `ktp-json.php`, `ktp-markup.php`, `assets/style.css`.
- Bahan yang sudah ada: `WARNA_KULIT`, `WARNA_BAJU`, `WARNA_TOPI`,
  `WARNA_BAWAAN`, `warna_sah()`, kolom `warna_*` di tabel `warga`, dan pemilih
  di `ktp-markup.php`.
- Ukuran selesai: KTP yang dibuat dari panel dan dari halaman menghasilkan
  warna yang sama dengan pilihan pengguna; nilai asing jatuh ke warna bawaan.
  Hasil 2026-11-08: pemilih ditambahkan ke panel Aku, `ktp-json.php` memakai
  `warna_sah()`, dan `aku-panel.js` lulus 9/9 termasuk tiga pilihan warna.

### 1.2 Pratinjau karakter hidup

- [x] Saat warna dipilih, pratinjau figur berubah langsung tanpa memuat ulang.
  Pratinjau dipakai di halaman KTP dan di panel Aku.
- [x] Kartu KTP menampilkan avatar warga, bukan foto Bang Uju.
- Berkas: `ktp-markup.php`, `includes/aku-skrip.php`, `assets/style.css`,
  `peta3d/index.php` (bila pembentuk avatar dipakai ulang).
- Keputusan: Bang Uju tidak dihapus; ia tetap warga kehormatan di
  `tentang-markup.php`.
- Ukuran selesai: avatar di kartu sama dengan avatar di peta, termasuk warna
  kulit, baju, dan topi; pratinjau juga bekerja dengan papan tombol.
  Hasil 2026-11-08: avatar HTML memakai token palet yang sama dengan SVG peta;
  pratinjau panel merespons pilihan radio tanpa muat ulang dan uji panel 11/11.

### 1.3 Misi dengan bar kemajuan, streak, dan tombol aksi

- [x] Panel Misi menampilkan bar kemajuan `selesai/total`, hitungan streak
  hari beruntun, dan satu tombol aksi per misi yang langsung menuju tempat
  atau panel yang dimaksud.
- Berkas: `includes/panel-bersayap.php`, `includes/misi-skrip.php`,
  `db.php`, `misi.php`, `assets/style.css`.
- Bahan: `misi_selesai` sudah per warga dan per tanggal, `klaim_harian`
  menyimpan klaim per `(nomor, tanggal)`, `poin_log` menyimpan riwayat,
  `daftar_misi()` dan `ringkasan_misi()` sudah ada.
- Ukuran selesai: streak dihitung dari hari berturut-turut yang punya minimal
  satu misi selesai atau klaim harian; tombol aksi membuka panel atau
  menerbangkan kamera; tidak ada tombol yang menandai misi tanpa bukti.

### 1.4 Lencana Koleksiku

- [ ] Panel Aku punya bagian Koleksiku berisi lencana yang terbuka otomatis
  dari data yang sudah ada.
- Berkas: `includes/panel-bersayap.php`, `includes/aku-skrip.php`, `db.php`,
  `assets/style.css`.
- Tujuh lencana, semuanya dari tabel yang sudah ada: Warga Baru (punya KTP),
  Rajin Klaim (7 hari klaim beruntun), Tukang Usaha (punya ruko aktif),
  Pedagang Pasar (punya lapak), Suara Gang (pernah kirim chat), Penjelajah
  (misi `keliling-desa` selesai), Misi Tuntas (5 misi dalam sehari).
- Keputusan: lencana dihitung saat dibaca, bukan tabel baru, supaya tidak ada
  data yang bisa basi. Lencana yang butuh data baru (misalnya jumlah kartu
  warga lain yang dibuka) tidak dipakai dulu.
- Ukuran selesai: lencana yang belum terbuka tampil redup dengan syaratnya,
  yang sudah terbuka tampil berwarna dan bertanggal.

### 1.5 Hitungan Hari ke-N dan suasana hari

- [ ] Bilah atas menampilkan "Hari ke-N Desa Target" sejak tanggal berdirinya
  desa, di samping jumlah warga dan bangunan.
- [ ] Bila hari ini ada acara Pasar Malam atau CFD, bilah atas menyebut acara
  itu, bukan hanya "Pagi" atau "Malam".
- Berkas: `peta3d/index.php`, `includes/peta-markup.php`, `config.php`,
  `assets/style.css`.
- Keputusan: tanggal tetap di konfigurasi (mis. `TANGGAL_DESA`), dengan
  cadangan tanggal warga paling awal bila konstanta kosong.
- Ukuran selesai: angka bertambah tiap hari, tampil sama di peta 3D dan 2D,
  dan acara hari ini memakai `acara_aktif()` yang sama dengan pasar.

### 1.6 Banner kabar di atas peta

- [ ] Satu baris kabar di atas kanvas, bisa ditutup, berisi satu tautan.
  Contoh: "Pasar Malam malam ini, buka pukul 18.00" atau "Ruko baru dibuka di
  Gang Kuliner".
- Berkas: `peta3d/index.php`, `includes/peta-markup.php`, `config.php`,
  `assets/style.css`.
- Keputusan: isi banner dari konfigurasi, bukan basis data, supaya bisa
  diganti lewat commit tanpa migrasi. Disembunyikan di layar sangat kecil.
- Ukuran selesai: banner tidak menghalangi tab bar, tidak muncul lagi setelah
  ditutup pada hari yang sama, dan bisa dibuka paksa lewat `?kabar=1` untuk uji.

### 1.7 Kode pemulihan kartu

- [x] Warga bisa memulihkan kartu di peramban atau ponsel lain memakai kode
  pemulihan, tanpa akun dan tanpa email.
- Berkas: `db.php` (kolom `kode_pulih` dan hash-nya), `ktp.php`,
  `ktp-markup.php`, `includes/aku-skrip.php`, endpoint baru
  `pulihkan.php`, `assets/style.css`.
- Keputusan: kode disimpan sebagai hash, ditampilkan sekali saat kartu dibuat,
  dan bisa dibuat ulang dari perangkat yang masih punya kunci. Kode tidak
  pernah dicetak di halaman publik.
- Ukuran selesai: kode salah dan nomor asing memberi galat yang sama; kode
  benar menulis `dt_nomor` dan `dt_kunci` di peramban baru; kode lama hangus
  setelah dipakai atau setelah dibuat ulang.
  Implementasi: hash SHA-256 kode acak 128 bit di warga.kode_pulih,
  endpoint POST bertoken sesi, dan UPDATE bersyarat yang mengonsumsi kode
  sekaligus mengganti kunci. Uji pemulihan-kartu.js lulus 13 pemeriksaan.

### 1.8 Nama panggilan dan izin

- [ ] Formulir meminta nama panggilan, bukan nama lengkap, dan memberi contoh
  yang jelas. Teks bantuan mengingatkan jangan memakai nama lengkap.
- [ ] Ada satu kotak izin sebelum kartu diterbitkan: nama dan tampilan boleh
  tampil di web, dan orang tua atau wali mengizinkan bila belum 18 tahun.
- Berkas: `ktp.php`, `ktp-markup.php`, `ktp-json.php`,
  `includes/panel-bersayap.php`, `includes/aku-skrip.php`, `assets/style.css`.
- Catatan: data lama yang sudah berisi nama lengkap tidak diubah otomatis.
  Yang berubah hanya label, contoh, dan validasi untuk kartu baru.
- Ukuran selesai: kartu tidak terbit tanpa izin; nama panggilan tetap boleh
  mengandung spasi, titik, apostrof, dan tanda hubung seperti aturan sekarang.

---

## Gelombang 2: navigasi dan dunia yang terasa hidup

### 2.1a Pergi ke tujuan: Antar

- [ ] Panel Kampus berisi daftar tujuan: Balai Desa, setiap ruko, Pasar,
  dan gerbang. Aksi Antar menerbangkan kamera langsung ke tujuan.
- Berkas: `includes/panel-bersayap.php`, berkas baru
  `includes/pergi-ke-skrip.php`, `peta3d/index.php`, `db.php`,
  `assets/style.css`.
- Bahan: posisi kavling dari `POSISI_KAVLING` dan `peta3d/data-desa.php`,
  penerbangan kamera sudah dipakai saat baris bangunan diketuk.
- Ukuran selesai: daftar tujuan memakai data yang sama dengan peta; ruko baru
  langsung muncul; di peta 2D minimal Antar bekerja.

### 2.1b Pergi ke tujuan: Ikuti

- [ ] Aksi Ikuti membuat avatar berjalan sendiri menuju tujuan, mengikuti
  panah, dan berhenti saat sampai.
- Berkas: `includes/pergi-ke-skrip.php`, `peta3d/index.php`,
  `assets/style.css`.
- Ketergantungan: 2.1a dan mode jalan yang sudah ada.
- Ukuran selesai: rute tidak menembus bangunan; berhenti atau menekan D-pad
  membatalkan perjalanan; tidak ada galat JavaScript saat tujuan tidak ada.

### 2.2 Lihat dari atas

- [ ] Tombol "Lihat dari atas" mengubah kamera ke tampilan seluruh desa, lalu
  tombol yang sama kembali ke tampilan awal.
- Berkas: `peta3d/index.php`, `assets/style.css`.
- Ukuran selesai: satu ketukan, kamera bergerak halus, tidak ada bangunan yang
  terpotong, dan kembali ke posisi semula persis.

### 2.3 Titik nongkrong tiap warga

- [ ] Setiap warga punya satu titik tetap di desa. Titiknya dipakai untuk
  menaruh avatar saat warga itu dicari, bukan hanya saat sedang dilihat.
- Berkas: `db.php`, `peta3d/data-desa.php`, `includes/cari-warga-skrip.php`,
  `peta3d/index.php`.
- Keputusan: titik dihitung dari nomor KTP dengan daftar titik yang sudah
  disiapkan di sekitar alun-alun dan jalan desa. Tidak boleh menumpuk di dalam
  bangunan.
- Ukuran selesai: dua warga berbeda tidak mendapat titik yang sama; titik tetap
  sama setiap kali halaman dibuka.

### 2.4 Cari teman langsung terbang

- [ ] Mengetik nama warga lalu memilih hasilnya menerbangkan kamera ke titik
  nongkrong warga itu, dengan cincin penanda dan kartu nama.
- [ ] Panel Warga juga menampilkan "warga terbaru" sebagai daftar awal, supaya
  panel tidak kosong sebelum ada kata kunci.
- Berkas: `includes/cari-warga-skrip.php`, `includes/panel-bersayap.php`,
  `peta3d/index.php`, `assets/style.css`.
- Ketergantungan: 2.3.
- Ukuran selesai: bekerja untuk warga yang punya dan tidak punya bangunan;
  hasil pencarian kosong tidak mengubah kamera.

### 2.5 Daftar gedung berkapasitas

- [ ] Daftar bangunan menampilkan keterangan jumlah, misalnya "muat 40 warga"
  untuk balai, "1 kios" untuk ruko, "12 tenda" untuk pasar.
- Berkas: `db.php`, `includes/panel-bersayap.php`, `assets/style.css`.
- Ukuran selesai: angka berasal dari data, bukan teks tetap; ruko baru langsung
  menambah keterangan yang masuk akal.

### 2.6 Tombol aksi kontekstual

- [ ] Saat avatar dekat bangunan, muncul satu tombol aksi yang jelas, misalnya
  "Buka ruko" atau "Masuk balai", dan bisa dipicu dengan tombol E.
- Berkas: `peta3d/index.php`, `assets/style.css`.
- Ukuran selesai: tombol hanya muncul saat dekat; tidak bentrok dengan D-pad
  dan tab bar di layar 360px; bekerja dengan sentuhan dan papan tombol.

### 2.7 Dunia lebih padat

- [ ] Tambah properti yang membuat desa terasa dihuni: bangku, lampu taman,
  gapura, bendera, petak bunga, dan papan nama lorong.
- [ ] Naikkan batas figur yang digambar bila perangkat kuat, tanpa membuat
  perangkat lemah tersendat.
- Berkas: `peta3d/index.php`, `peta3d/bangunan3d.js`, `assets/style.css`.
- Keputusan: jumlah figur tetap dibatasi, tetapi properti statis tidak
  menambah beban gambar per bingkai.
- Ukuran selesai: tidak ada penurunan bingkai yang berarti di mesin uji;
  properti tidak menutupi label dan pintu bangunan.

---

## Gelombang 3: permainan, katalog, dan rasa visual

### 3.1 Mini game panen padi

- [ ] Satu mini game di dalam panel: padi muncul di petak, pemain mengetuk
  pada waktu yang tepat, skor akhirnya memberi poin sekali per hari.
- Berkas: `includes/panel-bersayap.php`, berkas baru
  `includes/mini-game-skrip.php`, endpoint baru `main.php`, `db.php`,
  `assets/style.css`.
- Keputusan: hadiah dibatasi satu kali per warga per hari dan divalidasi di
  server, supaya skor yang diakali tidak bisa mencetak poin tanpa batas.
- Ukuran selesai: bisa dimainkan dengan papan tombol dan layar sentuh; hadiah
  harian masuk ke dompet; percobaan kedua di hari yang sama tidak menambah poin.

### 3.2 Katalog produk ruko dan lapak

- [ ] Halaman Pasar dan panel Ruko memakai kategori (makanan, jasa, kerajinan),
  kartu produk berharga, dan tombol kunjungi.
- Berkas: `pasar-markup.php`, `ruko-markup.php`, `includes/panel-bersayap.php`,
  `db.php`, `assets/style.css`.
- Ketergantungan: kolom `kategori` di `bangunan` dan `lapak`, plus nilai
  bawaan untuk data lama. Tanpa migrasi ini, kategori hanya bisa ditebak dari
  gang dan hasilnya tidak konsisten.
- Ukuran selesai: kategori bisa dipilih; ruko tanpa kategori masuk "Lainnya";
  kartu tetap rapi di layar 360px.

### 3.3 Interior tambahan

- [ ] Minimal dua bangunan lain bisa dimasuki. Balai Desa sudah ada, jadi yang
  ditambah adalah Pasar dan satu ruko contoh. Tidak ada gedung "Kantin" di desa
  ini, jadi jangan memakai nama itu.
- Berkas: `includes/interior-balai.php`, `peta3d/index.php`,
  `peta3d/interior-balai3d.js.php`, `assets/style.css`.
- Ukuran selesai: masuk dan keluar tanpa memuat ulang, D-pad bekerja, dan
  interior tidak bentrok dengan panel.

### 3.4 Sapuan gaya visual kertas

- [ ] Panel, tombol, dan kartu memakai satu bahasa visual yang lebih hangat:
  latar kertas, garis navy, tombol kuning, ikon besar, sudut membulat.
- Berkas: `assets/style.css`, `includes/panel-bersayap.php`,
  `includes/tab-bar.php`, `peta3d/index.php`.
- Keputusan: warna desa dan logo tetap; yang berubah hanya permukaan panel dan
  tombol. Kontras teks minimal 4.5:1.
- Ukuran selesai: tidak ada teks yang hilang kontras, tab bar tetap terbaca di
  luar ruangan, dan semua uji tata letak lama lulus.

### 3.5 Variasi bentuk topi dan rambut

- [ ] Tambah 3 sampai 5 bentuk topi atau rambut, bukan hanya warna, supaya
  figur warga lebih mudah dibedakan.
- Berkas: `config.php`, `db.php` (kolom bentuk bila perlu), `ktp-markup.php`,
  `peta3d/index.php`, `assets/style.css`.
- Keputusan: bentuk disimpan sebagai kode pendek yang sudah masuk daftar putih;
  data lama otomatis memakai bentuk bawaan.
- Ukuran selesai: bentuk tampil di pratinjau, kartu, peta 2D, dan peta 3D;
  nilai asing tidak merusak tampilan.

---

## Pekerjaan berikutnya

Urutan yang disarankan, dari yang paling murah ke yang paling berdampak.

### Bisa dikerjakan sekarang

1. **0.3 sudah selesai.** Lima uji langsung lulus 44 pemeriksaan untuk
   beranda peta 3D, sambutan, panel Aku, panel Ruko, dan gambar usaha.
2. **1.7 sudah selesai.** Pemulihan kartu tersedia lewat kode sekali pakai.
3. **1.1 Pemilih tampilan di panel Aku.** Bahan sudah lengkap (`WARNA_*`,
   `warna_sah()`, kolom `warna_*`); `ktp-json.php` masih mengabaikan warna.
4. **1.2 Pratinjau karakter.** Butuh 1.1. Sekalian memasang avatar di kartu.

### Sesudah itu, Gelombang 1 sisanya

5. **1.5 Hari ke-N** dan **1.6 banner kabar.** Keduanya kecil dan hanya
   menyentuh bilah atas serta `config.php`; ia memberi alasan balik lagi setiap
   hari tanpa data baru.
6. **1.3 Misi dengan bar kemajuan, streak, tombol aksi.** Bahan dari tabel
   `misi_selesai`, `klaim_harian`, `poin_log` sudah ada.
7. **1.4 Lencana Koleksiku.** Butuh 1.3 agar lencana misi punya angka.
8. **1.8 Nama panggilan dan izin.** Mengubah formulir dan teks; lakukan
   bersamaan dengan 1.7 supaya formulir hanya ditulis sekali.

### Gelombang 2

9. **2.1a Antar**, lalu **2.2 Lihat dari atas**, lalu **2.3 titik nongkrong**
   dan **2.4 cari teman terbang**, lalu **2.1b Ikuti**.
10. **2.5 kapasitas gedung**, **2.6 tombol aksi kontekstual**, dan
    **2.7 dunia lebih padat**. Kerjakan 2.6 sebelum 3.3 agar pintu masuk
    interior tidak perlu ditulis dua kali.

### Gelombang 3

11. **3.2 katalog berkategori** (butuh migrasi kolom `kategori`), lalu
    **3.5 variasi topi/rambut**, lalu **3.1 mini game**, lalu **3.3 interior
    Pasar dan ruko**, lalu **3.4 sapuan gaya visual kertas** paling akhir
    supaya tidak merombak tampilan yang masih akan berubah.

### Detail yang masih menggantung

- **0.3:** selesai, lima berkas uji tersedia dan lulus.
- **0.5:** selesai, kebocoran ditutup dan pemulihan tersedia di 1.7.
- **1.4:** lencana "Tetangga Baik" sengaja dibuang karena butuh data baru.
- **2.7:** angka batas figur (kini 80) baru boleh dinaikkan setelah ada
  pengukuran bingkai di perangkat lemah.
- **3.4:** belum ada nilai kontras yang diukur; wajib dicek sebelum dicentang.
- **3.3:** nama gedung memakai Pasar dan ruko, bukan "Kantin".

## Ketergantungan antar item

- 1.2 butuh 1.1.
- 1.7 butuh 0.5 dan menentukan bentuk penyimpanan kode.
- 2.1b butuh 2.1a dan mode jalan.
- 2.4 butuh 2.3.
- 3.2 butuh migrasi `kategori` lebih dulu.
- 3.3 memakai pola interior Balai yang sudah ada; kerjakan setelah 2.6 agar
  tombol aksi kontekstual tidak perlu ditulis dua kali.

## Uji yang harus ada di akhir

- `tools/uji/paritas-identitas.js`: pemilih warna, pratinjau, kartu, pemulihan.
- `tools/uji/paritas-misi.js`: bar, streak, tombol aksi, lencana.
- `tools/uji/paritas-navigasi.js`: Antar, Ikuti, Lihat dari atas, cari teman.
- `tools/uji/paritas-katalog.js`: kategori, kartu produk, interior.
- `tools/uji/paritas-mini-game.js`: hadiah harian, batas server.
- Semua uji lama tetap lulus.

## Sengaja tidak dikerjakan

- Koperasi afiliasi TikTok Shop: perjanjian komersial mereka, bukan fitur.
- Video YouTube dan absen murid: kanal mereka sendiri.
- Merchandise dan hadiah fisik: bukan perangkat lunak.
- Merek, aset, dan karakter Pak Kepsek: Desa Target memakai Bang Uju.
- Menyalin jumlah murid atau KTA mereka: data milik mereka.
- Pembagian kelas berdasarkan huruf awal: di Desa Target sudah ada gang
  profesi dan papan peringkat gang, yang lebih masuk akal untuk desa.
- Foto bareng teman dan unggah gambar: proyek ini sengaja tanpa unggah berkas;
  gambar usaha dipilih dari `assets/img/`.
- Tiga mini game sekaligus: satu dulu (3.1), sisanya dilihat setelah dinilai.
- Akun, email, dan nomor HP: identitas tetap nomor KTP dan kunci rahasia.

## Riwayat

- 2026-11-08: dokumen dibuat setelah jelajah langsung situs pembanding.
- 2026-11-08: ditinjau ulang. Ditambah temuan kebocoran kunci kartu,
  pemulihan kartu, nama panggilan dan izin, aksi kontekstual, kepadatan dunia,
  variasi bentuk topi, serta pemisahan Antar dan Ikuti. Item 0.1 selesai,
  0.2 selesai setelah seluruh uji lama dijalankan ulang, dan uji `ruko.js`
  diperbaiki agar tidak gagal karena data contoh memenuhi kavling.
- 2026-11-08: kebocoran kunci kartu ditutup dan uji `ktp-kunci.js` ditambah.
  Semua uji lama dan baru lulus. Catatan fitur (`replica/features.csv`,
  `replica/rencana-kerja.md`, `tools/uji/README.md`) diperbarui untuk beranda
  peta 3D, pop-up sambutan, panel Aku, panel Ruko, dan gambar usaha.
- 2026-11-08: bilah atas peta 3D dirapikan (semua chip sama tinggi dan
  sejajar) dan uji `bilah-atas.js` ditambah, commit `e91f563`. Gelombang 0
  saat itu tinggal 0.3 (kini selesai) dan sisa 0.5 (kode pemulihan di 1.7).
- 2026-11-08: commit `24b103b` di-push dan di-deploy ke
  https://desa.kejartarget.id. Verifikasi live: halaman wajib 200, berkas
  rahasia 403, kartu publik tidak memuat kunci.
