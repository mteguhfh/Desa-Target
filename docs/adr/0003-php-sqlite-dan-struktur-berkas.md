# ADR-0003: PHP 8.3 + SQLite, markup dipisah dari logika

- Status: Diterima
- Tanggal: 2026-11-08

## Konteks

Situs berjalan di Plesk dengan PHP 8.3 FPM dan SQLite di `data/`. Beberapa
halaman (peta, KTP, beranda) tumbuh sampai mencampur kueri database, logika
PHP, markup HTML, dan skrip JavaScript dalam satu berkas. `peta.php` sempat
mencapai 1.559 baris.

## Keputusan

1. **Tanpa kerangka kerja backend.** Halaman = berkas PHP, data = SQLite lewat
   `db.php`, tanpa lapisan ORM.
2. **Halaman besar dipecah dua atau tiga berkas** dengan pola:
   - `<halaman>.php` — logika, kueri, variabel, dan rangkaian `require`
   - `<halaman>-markup.php` — markup HTML saja
   - `includes/<halaman>-data.php` dan `includes/<halaman>-skrip.php` untuk
     halaman yang benar-benar berat seperti peta
3. **Markup yang lebih dari beberapa baris tidak ditulis di dalam berkas
   PHP logika.** Alasannya bukan gaya: alat analisis kode dan beberapa editor
   gagal membaca berkas PHP yang isinya bercampur markup panjang.

## Alasan

- Berkas logika tetap terbaca utuh oleh alat analisis dan oleh orang yang
  membaca kode untuk pertama kali.
- Perubahan data tidak tercampur dengan perubahan tampilan, sehingga diff
  lebih mudah ditinjau.
- Pemecahan dilakukan tanpa mengubah keluaran. Setiap pemecahan diverifikasi
  dengan membandingkan HTML hasil render sebelum dan sesudah, byte demi byte.

## Konsekuensi

- Jumlah berkas bertambah dan nama menjadi lebih panjang.
- Variabel antar berkas berpindah lewat `require`, tidak lewat parameter
  eksplisit. Ini mengharuskan setiap berkas markup mencatat di komentarnya
  variabel apa yang diharapkan.
- Verifikasi byte-per-byte menjadi langkah wajib setiap kali memecah berkas.

## Alternatif yang ditolak

- **Tetap satu berkas besar.** Lebih cepat saat itu, tetapi sulit dirawat dan
  tidak terbaca alat analisis.
- **Mesin template terpisah (Twig).** Menambah dependensi dan satu bahasa
  lagi tanpa manfaat yang sepadan pada situs sebesar ini.
