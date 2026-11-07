# Rencana kerja Desa Target

Disusun dari `replica/features.csv` setelah dicocokkan satu per satu dengan kode
yang berjalan (lihat kolom `evidence`). Bukan daftar impian, melainkan sisa
pekerjaan yang benar-benar belum ada.

Status saat rencana ini ditulis: 11 fitur selesai, 4 sebagian, 7 belum,
5 sengaja tidak dikerjakan.

**Perbarui 2026-11-08:** Tahap 1 selesai. Sekarang 16 selesai, 2 sebagian,
4 belum, 5 sengaja tidak dikerjakan. Yang tersisa semuanya Tahap 2 dan 3.

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

| # | pekerjaan | status awal | yang perlu dibuat |
| --- | --- | --- | --- |
| 2.1 | Daftar semua bangunan beserta gangnya | sebagian | Daftar sudah bisa dibaca di panel `data-isi="bangunan"`; sisa: ketuk baris untuk menyorot bangunan di dunia |
| 2.2 | Cari warga berdasarkan nama atau nomor | belum | Helper baru di `db.php` (`cari_warga_berdasarkan()`), halaman hasil, dipakai tab Desa |
| 2.3 | Interior Balai Desa di dalam 3D | sebagian | Sekarang masih lompat ke `peta2d.php#interior-balai`; perlu tampilan interior tanpa keluar dari peta 3D |

**Selesai bila:** tiga tab punya isi nyata, dan ketukan pada daftar bangunan
menyorot bangunan yang benar di dunia 3D.

---

## Tahap 3 — Nuansa desa

Semua di sini prioritas `could`. Dikerjakan hanya bila tahap 1 dan 2 sudah rapi.

| # | pekerjaan | status awal |
| --- | --- | --- |
| 3.1 | Misi harian | belum (perlu tabel misi + UI) |
| 3.2 | Suara latar desa, mati secara bawaan | belum |
| 3.3 | Pembagian warga per huruf awal nama | belum |

Catatan: 3.1 paling dekat dengan konsep asli Desa Target (komentar terbanyak
dibangun besok), jadi kalau hanya satu yang dikerjakan, ini yang dipilih.

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
