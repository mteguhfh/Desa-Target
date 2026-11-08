# Rencana kerja Desa Target

> Catatan 2026-11-08: untuk gelombang paritas melawan Sekolah Bebaz, lihat
> `replica/rencana-paritas.md`. Berkas ini tetap menjadi rencana dasar fitur
> desa.

Disusun dari `replica/features.csv` setelah dicocokkan satu per satu dengan kode
yang berjalan (lihat kolom `evidence`). Bukan daftar impian, melainkan sisa
pekerjaan yang benar-benar belum ada.

Status saat rencana ini ditulis: 11 fitur selesai, 4 sebagian, 7 belum,
5 sengaja tidak dikerjakan.

**Perbarui 2026-11-08:** Tahap 1 dan 2 selesai, ditambah Fitur 1, Fitur 2,
Fitur 3, Fitur 4, dan Fitur 5 dari `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md`
(dompet poin warga, chat antar gang, ruko warga, Pasar Malam dan CFD, lalu
beranda peta 3D dengan panel Aku dan panel Ruko). Sekarang 25 selesai,
0 sebagian, 2 belum, 5 sengaja tidak dikerjakan. Yang tersisa di tabel Tahap 3:
suara latar desa dan pembagian warga per huruf awal. Rencana paritas melawan
Sekolah Bebaz ada di `replica/rencana-paritas.md`.

Fitur dari spesifikasi terpisah `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` tidak
masuk tabel Tahap 3 di bawah; urutannya diatur dokumen itu sendiri. Yang sudah
selesai: Fitur 1 (dompet poin warga dan misi per warga), Fitur 2 (chat antar
gang, publik, frase preset), Fitur 3 (ruko warga), dan Fitur 4 (Pasar Malam
dan CFD dengan lapak gratis). Berikutnya menurut urutan spesifikasi: Fitur 5.

## Prinsip urutan

1. **Jangan pecah yang sudah jalan.** Peta 3D, sakelar, dan satu sumber data
   (ADR-0004) sudah stabil. Apa pun yang ditambah harus memakai jalur data yang
   sama, bukan membuat jalur kedua.
2. **Yang menahan pengunjung lebih dulu.** Tab bar dan panel bersayap adalah
   inti pengalaman pembanding: tanpa keduanya, pengunjung harus meninggalkan
   dunia 3D untuk berpindah. Itu yang paling terasa hilang.
3. **Yang murah dan sudah ada bahannya didahulukan** di dalam setiap tahap.
4. **Skip tetap skip.** Koperasi afiliasi, video, merchandise, merek, dan data
   mereka tidak akan pernah masuk rencana ini.

---

## Tahap 1 — Jangan sampai dunia 3D hilang saat pindah

Ini satu-satunya kelompok yang menyentuh kerangka halaman, jadi dikerjakan lebih
dulu supaya pekerjaan lain punya tempat menaruh isinya.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| 1.1 | Tab bar bawah 4 tab: Desa · Misi · Papan Gang · Aku | **selesai** | `includes/tab-bar.php` |
| 1.2 | Panel bersayap di atas dunia 3D | **selesai** | `includes/panel-bersayap.php` |
| 1.3 | Bilah atas lengkap (statistik + suasana) | **selesai** | `chipSuasana` di `peta3d/index.php` |
| 1.4 | Tautkan pembuatan kartu warga dari dalam peta 3D | **selesai** | tab Aku → `ktp.php`, kembali ke peta |

**Hasil:** keempat tab bisa dibuka tanpa meninggalkan peta. Tab Desa dan Aku
adalah tautan biasa; Misi dan Papan Gang membuka panel di halaman yang sama.
Dunia peta tetap terlihat di atas panel.

**Catatan teknis yang ditemukan saat mengerjakan:**

- Peta 3D menggambar hanya sekitar 3 bingkai per detik di mesin uji tanpa GPU,
  jadi animasi 300ms tertinggal hampir satu detik. Panel karena itu dibuka
  tanpa transisi saat halaman punya kanvas 3D (kelas `.segera`). Latensi turun
  dari ~870ms ke 7ms.
- Layar muat 3D dulu menutupi tab bar dengan z-index lebih tinggi, sehingga
  ketukan pertama tertelan. Layar muat dinaikkan ke z-index 70, di atas tab bar.
- Panel dan tab bar dijaga jarak 1px lewat rumus `bottom: 72px`, bukan 64px:
  tinggi tab bar sebenarnya 70.6px (52px item + padding + safe-area).

**Uji:** `tools/uji/tab-bar-panel.js`, 112 pemeriksaan di 4 ukuran layar ×
2 halaman. Tata letak diverifikasi dengan `elementFromPoint`, bukan hanya kotak.

---

## Tahap 2 — Isi panel jadi berguna

Tab bar tanpa isi hanya kulit. Di sini isinya diisi.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| 2.1 | Daftar bangunan + sorot di dunia | **selesai** | tab Bangunan; ketuk baris → kamera terbang, cincin menyala, kartu detail terbuka |
| 2.2 | Cari warga berdasarkan nama atau nomor | **selesai** | `cari_warga_berdasarkan()` + `cari-warga.php` (panel) + `warga.php` (tanpa JavaScript) |
| 2.3 | Interior Balai Desa di dalam 3D | **selesai** | `peta3d/interior-balai.php`; tidak lagi melompat ke peta 2D |

**Hasil:** ketiga tab punya isi nyata, dan pencarian tidak pernah memuat ulang
halaman di peta.

**Keputusan yang diambil di tahap ini:**

- Slot tab "Misi" dipakai untuk **Warga**, karena pencarian lebih berguna
  daripada daftar misi yang belum ada. Misi tetap tersedia sebagai isi panel
  tersembunyi (`data-isi="misi"`) sampai fiturnya dibuat.
- Di halaman peta, tab "Desa" diganti **Bangunan** yang membuka daftar. Di luar
  peta, "Desa" tetap beranda. Diatur halaman lewat `$tabDesaPanel`.
- Pencarian di dalam peta memakai endpoint `cari-warga.php` yang **hanya
  membaca**. Kunci tidak pernah masuk SQL selain lewat parameter terikat, dan
  `%` / `_` diperlakukan sebagai huruf biasa, bukan wildcard.

**Uji:** `tools/uji/tahap2-cari-sorot-interior.js`, 30 pemeriksaan — termasuk
percobaan injeksi SQL, wildcard, dan pemotongan kunci panjang.

---

## Tahap 3 — Nuansa desa

Semua di sini prioritas `could`. Dikerjakan hanya bila tahap 1 dan 2 sudah rapi.

| # | pekerjaan | status |
| --- | --- | --- |
| 3.1 | Misi harian | **selesai** (Fitur 1: per warga, berpoin) |
| 3.2 | Suara latar desa, mati secara bawaan | belum |
| 3.3 | Pembagian warga per huruf awal nama | belum |

Catatan: 3.1 paling dekat dengan konsep asli Desa Target (komentar terbanyak
dibangun besok), jadi kalau hanya satu yang dikerjakan, ini yang dipilih.
3.1 sudah dikerjakan bersama dompet poin (Fitur 1), karena poin misi butuh
tempat menampung saldo.

---

## Fitur 1 — Dompet poin warga dan misi per warga

Sumber acuan: `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` bagian 3.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| F1.1 | Skema poin: `warga.poin`, `poin_log`, `klaim_harian`, `misi.poin`, `misi_selesai.nomor` | **selesai** | migrasi idempoten di `db.php` |
| F1.2 | Fungsi dompet: `saldo_poin()`, `tambah_poin()`, `klaim_harian()`, `riwayat_poin()` | **selesai** | `db.php`; saldo dan log satu transaksi |
| F1.3 | Misi per warga: `daftar_misi($nomor)`, `tandai_misi($kode, $nomor)`, poin sekali per hari | **selesai** | `db.php` + `misi.php` |
| F1.4 | Endpoint baca `poin.php` dan tulis `klaim.php` | **selesai** | butuh nomor + kunci warga |
| F1.5 | UI panel Misi (saldo, klaim harian, tombol tandai misi) + chip saldo di bilah atas | **selesai** | `includes/dompet-skrip.php`, `panel-bersayap.php` |

**Keputusan yang diambil di luar teks spesifikasi:**

- **Kunci rahasia per warga.** Nomor KTP mudah ditebak (DT-0001, DT-0002),
  jadi nomor saja tidak cukup sebagai bukti pemilik. Kolom `warga.kunci`
  (32 hex) diterbitkan saat kartu dibuka di `ktp.php` dan disimpan browser di
  `localStorage` kunci `dt_kunci`. Semua endpoint tulis wajib nomor + kunci;
  kunci tidak pernah muncul di daftar warga atau pencarian.
- **Status desa tetap berlaku untuk semua warga.** `usulkan-usaha`,
  `dukung-teman`, dan `bikin-ktp` dihitung dari data desa, jadi berlaku untuk
  setiap warga. Hanya misi seperti `keliling-desa` yang dicatat di
  `misi_selesai` per warga. Poin tetap diberikan sekali per warga per hari.
- **`misi_selesai` dibangun ulang** menjadi kunci utama `(kode, tanggal,
  nomor)`. Baris lama tetap ada dengan nomor kosong sebagai riwayat global.
- **Misi tanpa nomor masih bisa dibaca.** Pengunjung tanpa KTP tetap melihat
  daftar misi desa (hanya baca), tidak bisa menandai atau mendapat poin.

**Uji:** `tools/uji/dompet-poin.js`, 47 pemeriksaan (klaim ganda 409, klaim
hari berikutnya, isolasi dua warga, kunci salah atau milik warga lain,
injeksi nomor/kunci/kode, dan panel di peta 3D dan 2D).

---

## Fitur 2 — Chat antar gang (publik, frase preset, tanpa DM)

Sumber acuan: `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` bagian 4.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| F2.1 | Skema `chat_pesan` + indeks `idx_chat_kanal_id` | **selesai** | migrasi idempoten di `db.php` |
| F2.2 | Daftar frase preset `CHAT_FRASE` dan jeda `CHAT_JEDA_DETIK` | **selesai** | `config.php`; 8 frase, ditambah lewat commit |
| F2.3 | Fungsi `daftar_kanal_chat()`, `kanal_chat_sah()`, `pesan_chat()`, `kirim_chat()` | **selesai** | `db.php`; nama dan gang diambil dari tabel `warga` |
| F2.4 | Endpoint `chat.php` GET (`kanal`, `sejak`, maks 50, urut naik) dan POST (`kanal`, `frase`, `nomor`, `kunci`) | **selesai** | GET boleh tanpa kartu; POST 403 tanpa kunci, 429 terlalu cepat |
| F2.5 | Panel `data-isi="chat"` + tombol Chat di bilah atas 3D dan 2D | **selesai** | `includes/chat-skrip.php`, `includes/panel-bersayap.php`; tab bar tetap 4 |

**Keputusan yang diambil di luar teks spesifikasi:**

- **Tanpa tab kelima.** Chat dibuka lewat tombol Chat di bilah atas (3D) dan
  di baris navigasi (2D). `tab-bar-skrip.php` mendapat pintasan umum
  `pasangPintasan()` sehingga tombol di luar tab bar berperilaku sama dengan
  tab, tanpa menambah tab.
- **Menulis butuh nomor + kunci kartu**, sama seperti Fitur 1. Tanpa kunci,
  endpoint menolak dengan jawaban yang sama seperti nomor tidak dikenal,
  supaya tidak bisa dipakai menebak nomor warga.
- **Kanal gang sendiri saja.** Menulis hanya di Alun-Alun atau gang milik warga
  itu sendiri; kanal gang lain tetap bisa dibaca. Diperiksa di server, bukan
  hanya disembunyikan di UI.
- **Endpoint tidak punya kolom teks bebas.** Field `teks`/`pesan` diabaikan
  sepenuhnya; hanya kode dari `CHAT_FRASE` yang diterima.
- **Polling selaras dengan panel.** Polling 5 detik memakai `sejak` dan hanya
  berjalan saat panel terbuka dan tab peramban terlihat. Pengawas
  `MutationObserver` plus pemeriksaan berkala 1 detik menutup celah animasi
  panel 300 ms setelah ditutup.

**Uji:** `tools/uji/chat.js`, 64 pemeriksaan (teks bebas ditolak, nama/gang
tidak bisa dipalsukan, kunci wajib, kanal gang lain 403, batas laju 429,
kanal tidak sah, injeksi kanal/nomor/kunci/frase, `sejak` tanpa pesan ganda,
panel chat tanpa kolom teks di 3D dan 2D, polling berhenti saat panel
ditutup dan hidup lagi saat dibuka).

---

## Fitur 3 — Ruko komersil untuk usaha warga

Sumber acuan: `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` bagian 5.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| F3.1 | Migrasi `bangunan.jenis`, `nomor`, `tautan`, `aktif` + tabel `ruko_klaim` | **selesai** | `db.php`; baris lama otomatis `jenis = 'komentar'`, `aktif = 1` |
| F3.2 | Fungsi `klaim_ruko()`, `lepas_ruko()`, `ruko_milik()`, `tautan_usaha_sah()`, `semua_bangunan_aktif()` | **selesai** | `db.php`; satu warga satu ruko, klaim dan daftar ruko satu transaksi |
| F3.3 | Halaman `ruko.php` (formulir klaim dan lepas, pola PRG seperti `ktp.php`) | **selesai** | `ruko.php` + `ruko-markup.php` |
| F3.4 | Ruko tampil di peta 2D dan 3D lewat jalur ADR-0004 (`data-desa.php`) | **selesai** | `includes/bangunan-svg.php`, `peta3d/index.php` (tombol Kunjungi usaha) |
| F3.5 | Jalan masuk: pil Ruko di tab bar, tombol di kontrol 3D, tautan di panel Bangunan dan menu situs | **selesai** | `includes/tab-bar.php`, `peta3d/index.php`, `includes/panel-bersayap.php` |

**Keputusan yang diambil di luar teks spesifikasi:**

- **Kolom `bangunan.aktif`.** Spesifikasi hanya menyebut "menandai nonaktif"
  tanpa menyebut kolomnya. Ditambah `aktif INTEGER NOT NULL DEFAULT 1` supaya
  ruko yang dilepas hilang dari peta tetapi baris dan ceritanya tetap utuh.
- **Kavling dari urutan bangunan aktif.** Ruko masuk ke kavling kosong
  berikutnya dengan cara yang sama seperti `tools/tambah-bangunan.php` dan
  peta 2D: bangunan aktif diurutkan menurut id. Tidak ada logika kavling
  kedua; setelah ruko dilepas, kavlingnya dipakai bangunan sesudahnya.
- **Gang ruko mengikuti kartu.** Gang diambil dari baris `warga`, bukan dari
  formulir, dan permintaan dengan gang lain ditolak (`galat = 'gang'`).
- **Tautan hanya https.** Host wajib ada, tanpa nama pengguna atau sandi di
  dalam alamat. `javascript:`, `data:`, `http:`, dan tautan tanpa skema
  ditolak. Di peramban tautan diperiksa ulang sebelum dipasang ke `href` dan
  dibuka dengan `rel="noopener noreferrer"`.
- **Cerita usaha sebagai teks biasa.** Maksimal `RUKO_CERITA_MAKS` (280)
  karakter dan selalu dicetak lewat `textContent` atau `esc_html`, jadi HTML
  dari pemilik tidak pernah dieksekusi.
- **Identitas dari localStorage.** Kartu yang dibuka di tab lain tidak
  memberi tahu halaman Ruko, jadi peramban mengirim nomor + kunci miliknya
  sekali (lewat POST tersembunyi, dengan penanda `sessionStorage` supaya
  tidak berputar), lalu halaman memuat ulang. Server tetap yang memutuskan
  cocok atau tidak.
- **`RUKO_BIAYA_POIN = 0`.** Klaim gratis sekarang, tetapi alur potong poin
  sudah ada di `klaim_ruko()` sehingga harga bisa dipasang tanpa mengubah
  skema.

**Uji:** `tools/uji/ruko.js`, 41 pemeriksaan (tanpa kartu ditolak, klaim ganda
ditolak, tautan tidak sah dan gang palsu ditolak, cerita berisi HTML tampil
sebagai teks, ruko muncul di `data-desa.php`, peta 2D, daftar bangunan 3D,
dan jalan masuk dari tab bar dan kontrol 3D, lepas ruko menyembunyikan
tanpa menghapus baris).

---

## Fitur 4 — Pasar Malam dan CFD (lapak gratis)

Sumber acuan: `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` bagian 6.

| # | pekerjaan | status | bukti |
| --- | --- | --- | --- |
| F4.1 | Konstanta `ACARA_LAPAK` (hari, jam, batas lapak) | **selesai** | `config.php`; Pasar Malam Sabtu 18.00-22.00, CFD Minggu 06.00-10.00, masing-masing 12 lapak |
| F4.2 | Tabel `lapak` + migrasi idempoten | **selesai** | `db.php`; kunci unik `(acara, tanggal, nomor)` |
| F4.3 | Fungsi `acara_sah()`, `acara_aktif()`, `acara_berikutnya()`, `daftar_lapak()`, `klaim_lapak()`, `keadaan_pasar()` | **selesai** | `db.php`; semua fungsi menerima waktu sekarang supaya jadwal bisa diuji |
| F4.4 | Endpoint `lapak.php` (GET jadwal dan lapak, POST klaim) | **selesai** | `lapak.php`; 400/403/409/422 dibedakan, nomor asing dan kunci salah dijawab sama |
| F4.5 | Panel Pasar di `data-isi="pasar"` + `includes/pasar-skrip.php` | **selesai** | `includes/panel-bersayap.php`; pemilih acara, hitung mundur, daftar lapak, klaim |
| F4.6 | Tenda di peta 3D dan chip suasana jadi pintu masuk | **selesai** | `peta3d/index.php` (`bangunLapak()`, `#chipSuasana` `data-panel="pasar"`), `peta3d/bangunan3d.js` (`buatTenda()`) |
| F4.7 | Data acara lewat satu jalur ADR-0004 | **selesai** | `peta3d/data-desa.php` mengirim `acara`, `namaAcara`, `acaraBerikutnya`, `lapak`, `batasLapak` |
| F4.8 | Halaman `pasar.php` (jadwal, hitung mundur, daftar lapak) + menu situs | **selesai** | `pasar.php`, `pasar-markup.php`, `includes/header-markup.php` |

**Keputusan yang diambil di luar teks spesifikasi:**

- **Klaim hanya saat acara berlangsung.** Di luar jam acara, `klaim_lapak()`
  mengembalikan galat `tutup` (HTTP 409). Daftar lapak dan jadwal acara
  berikutnya tetap bisa dibaca kapan saja, jadi warga bisa menyiapkan usaha
  lebih dulu.
- **Jam acara dari jam server.** `acara_aktif()` tidak menerima jam dari
  klien, jadi tidak ada yang bisa memajukan jam peramban untuk mengambil
  lapak saat pasar tutup. Hitung mundur di panel memakai jam peramban, tetapi
  hanya untuk tampilan.
- **Nama usaha dan tautan dari ruko.** Warga yang sudah punya ruko tidak
  menulis ulang data usaha: server menyalin nama dan tautannya dari
  `ruko_milik()`. Warga tanpa ruko memakai formulir ringkas di panel, dengan
  `tautan_usaha_sah()` yang sama dengan Fitur 3 (hanya https).
- **Jalan masuk lewat chip suasana.** Spesifikasi tidak menyebut tab baru.
  Chip suasana di bilah atas yang sudah ada dipakai (`data-panel="pasar"`),
  dan saat acara berlangsung chip berganti menjadi "Pasar Malam/CFD sedang
  berlangsung · N lapak". Panel juga bisa dibuka dari peta 2D lewat
  `__tabBar.buka('pasar')`.
- **Tenda hanya saat acara.** `data-desa.php` mengirim `lapak` kosong dan
  `acara = null` di luar jam acara, jadi peta 3D tidak menggambar tenda sama
  sekali. Posisi tenda dua baris di selatan alun-alun, urut klaim.
- **Tanpa poin.** Lapak gratis: `klaim_lapak()` tidak memotong poin, dan uji
  memastikan saldo warga tidak berubah.

**Uji:** `tools/uji/pasar.js`, 32 pemeriksaan (empat kasus jadwal, batas 12
lapak dan lapak ke-13 ditolak, klaim ganda ditolak, tautan tidak sah dan nama
terlalu pendek ditolak, nomor asing dan kunci salah dijawab sama, nama usaha
berisi HTML disimpan sebagai teks, poin tidak berkurang, `data-desa.php`
mengirim `acara` yang benar, peta 3D dan 2D membuka panel Pasar, chip suasana
menjadi pintu masuk).

---

## Yang sengaja tidak dikerjakan

Koperasi afiliasi TikTok Shop, video YouTube dan absen murid, merchandise
hoodie, merek dan karakter Pak Kepsek, serta jaringan 50 ribu murid. Alasannya
ada di `replica/recon.md` bagian *Out of scope*.

---

## Cara memakai rencana ini

Satu tahap = satu rangkaian commit. Setiap tahap selesai bila uji di
`tools/uji/` masih lulus dan halaman di production tetap 200. Rencana ini
ditinjau ulang setiap kali `features.csv` berubah.
