# Rencana kerja Desa Target

Disusun dari `replica/features.csv` setelah dicocokkan satu per satu dengan kode
yang berjalan (lihat kolom `evidence`). Bukan daftar impian, melainkan sisa
pekerjaan yang benar-benar belum ada.

Status saat rencana ini ditulis: 11 fitur selesai, 4 sebagian, 7 belum,
5 sengaja tidak dikerjakan.

**Perbarui 2026-11-08:** Tahap 1 dan 2 selesai, ditambah Fitur 1 dari
`SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` (dompet poin warga dan misi per warga).
Sekarang 21 selesai, 0 sebagian, 2 belum, 5 sengaja tidak dikerjakan. Yang
tersisa di tabel Tahap 3: suara latar desa dan pembagian warga per huruf awal.

Fitur dari spesifikasi terpisah `SPESIFIKASI-FITUR-BELUM-DIBANGUN.md` tidak
masuk tabel Tahap 3 di bawah; urutannya diatur dokumen itu sendiri. Yang sudah
selesai: Fitur 1 (dompet poin warga dan misi per warga).

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

## Yang sengaja tidak dikerjakan

Koperasi afiliasi TikTok Shop, video YouTube dan absen murid, merchandise
hoodie, merek dan karakter Pak Kepsek, serta jaringan 50 ribu murid. Alasannya
ada di `replica/recon.md` bagian *Out of scope*.

---

## Cara memakai rencana ini

Satu tahap = satu rangkaian commit. Setiap tahap selesai bila uji di
`tools/uji/` masih lulus dan halaman di production tetap 200. Rencana ini
ditinjau ulang setiap kali `features.csv` berubah.
