# Rencana kerja Desa Target

Disusun dari `replica/features.csv` setelah dicocokkan satu per satu dengan kode
yang berjalan (lihat kolom `evidence`). Bukan daftar impian, melainkan sisa
pekerjaan yang benar-benar belum ada.

Status saat rencana ini ditulis: 11 fitur selesai, 4 sebagian, 7 belum,
5 sengaja tidak dikerjakan.

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

| # | pekerjaan | status awal | yang perlu dibuat |
| --- | --- | --- | --- |
| 1.1 | Tab bar bawah 4 tab: Desa · Misi · Papan Gang · Aku | belum | Komponen tab di `includes/`, muncul di peta 3D dan 2D |
| 1.2 | Panel bersayap di atas dunia 3D | belum | Satu panel yang isinya berganti per tab; dunia tetap terlihat di belakang |
| 1.3 | Bilah atas lengkap (statistik + suasana) | sebagian | Tambah status ramai/sepi, hitung dari data desa sendiri |
| 1.4 | Tautkan pembuatan kartu warga dari dalam peta 3D | sebagian | Tombol di tab Aku menuju `ktp.php`; kembali ke peta setelah jadi |

**Selesai bila:** dari dalam peta 3D, pengunjung bisa membuka keempat tab tanpa
meninggalkan halaman, dan bisa sampai ke pembuatan kartu lalu kembali.

**Risiko:** panel bertumpuk di layar 390px. Wajib diuji di 390 / 768 / 1440.

---

## Tahap 2 — Isi panel jadi berguna

Tab bar tanpa isi hanya kulit. Di sini isinya diisi.

| # | pekerjaan | status awal | yang perlu dibuat |
| --- | --- | --- | --- |
| 2.1 | Daftar semua bangunan beserta gangnya | sebagian | Data sudah dikirim `data-desa.php`; tinggal tampilan daftar yang bisa digulir dan diketuk untuk menyorot bangunan di dunia |
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
