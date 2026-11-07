# ADR-0004: Satu sumber data untuk tampilan 3D dan 2D

- Status: Diusulkan
- Tanggal: 2026-11-08
- Terkait: ADR-0001 (peta 3D sebagai layar utama)

## Konteks

Setelah ADR-0001, dua tampilan peta berjalan berdampingan dan harus
menampilkan desa yang sama: bangunan yang sama, di kavling yang sama, dengan
warga di gang yang sama. Peta 2D membaca dari PHP langsung; peta 3D membaca
dari `peta3d/data-desa.php` yang mengembalikan JSON.

Ada dua risiko yang nyata:

1. **Data berbeda.** Kalau aturan penempatan bangunan ditulis dua kali, suatu
   hari keduanya akan berbeda dan pengunjung melihat desa yang tidak cocok.
2. **Tampilan 3D memiliki salinan koordinat.** `peta3d/index.php` saat ini
   memuat salinan `POSISI_KAVLING` sendiri (baris 300-303). Salinan itu akan
   basi begitu `includes/peta-posisi.php` berubah.

## Keputusan

1. **Database tetap satu-satunya sumber kebenaran.** Tidak ada berkas data
   statis yang menggantikan isi tabel.
2. **Aturan penempatan ditulis sekali** di `includes/peta-posisi.php` (untuk
   2D) dan hasilnya dikirim ke 3D lewat `data-desa.php`. Peta 3D tidak
   menerima daftar koordinat mentah dari berkas JS.
3. **`data-desa.php` mengirim koordinat akhir** yang sudah dihitung server
   untuk setiap bangunan: kavling, posisi dunia, gang, dan nama. Klien tidak
   menghitung ulang penempatan.
4. **Versi data ikut dikirim.** Respons JSON membawa penanda versi dari
   `filemtime` skema dan berkas posisi, supaya klien bisa membedakan data
   basi dari data baru.

## Alasan

- Satu aturan, satu tempat memperbaiki.
- Kebutuhan 2D dan 3D memang berbeda (2D butuh kavling, 3D butuh koordinat
  dunia), tetapi keduanya dapat diturunkan dari satu tabel posisi.
- Menghapus salinan di klien berarti setiap perubahan posisi langsung terlihat
  di kedua tampilan tanpa menyentuh JavaScript.

## Konsekuensi

- `data-desa.php` sedikit lebih berat karena menghitung posisi, tetapi
  hitungannya kecil (belasan bangunan) dan bisa di-cache di sisi PHP.
- Peta 3D menunggu respons server sebelum menempatkan bangunan. Saat memuat,
  tampilkan keadaan kosong yang jelas, bukan data contoh.
- Perlu tes yang membandingkan jumlah dan nama bangunan pada respons 3D dan
  markup 2D, supaya penyimpangan ketahuan lebih awal.

## Alternatif yang ditolak

- **Menyalin koordinat ke JS 3D.** Itulah keadaan sekarang dan itulah sumber
  masalah basi yang sedang dicegah.
- **Peta 2D membaca dari JSON.** Menambah satu perjalanan jaringan pada
  halaman yang sekarang langsung tampil, dengan manfaat yang kecil.
