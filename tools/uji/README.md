# Uji browser untuk peta 3D

Uji perilaku peta langsung di peramban sungguhan memakai Playwright. Dipakai
untuk hal yang tidak bisa dibuktikan dengan `curl`: WebGL benar-benar
merender, sakelar bisa diklik, pilihan mode diingat, koordinat 3D sama
dengan server.

## Persiapan sekali

Butuh Node dan Chromium dari Playwright. Situs harus berjalan lebih dulu
(`php -S localhost:8100` dari akar proyek).

```bash
mkdir -p /tmp/uji && cd /tmp/uji
npm init -y && npm install playwright-core
```

Sesuaikan `CHROME` di setiap berkas uji ke lokasi Chromium di mesinmu
(`~/.cache/ms-playwright/chromium-*/chrome-linux64/chrome`).

## Menjalankan

```bash
cp tools/uji/*.js /tmp/uji/ && cd /tmp/uji && node peta-3d-sakelar.js
```

`dompet-poin.js` memanggil `php -r` dari akar repo untuk menguji klaim hari
berikutnya. Bila berkas uji dijalankan dari salinan, isi `CWD_UJI` dengan akar
repo. `BASE_UJI` bisa dipakai untuk menunjuk alamat situs selain
`http://localhost:8100`.

| berkas | yang diperiksa |
| --- | --- | --- |
| `belajar.js` | Pusat Pembelajaran (HTTP murni, tanpa peramban): kunci berurutan dua warga, poin tepat sekali, kunci jawaban kuis tidak bocor, kelas tanpa video tuntas, video tanpa ID ditolak |
| `paritas-mini-game.js` | latensi, waktu tidak sah, autentikasi, token replay, hadiah harian dan saldo |
| `paritas-katalog.js` | kategori dan harga, filter, fallback, interior Pasar/ruko, keyboard dan D-pad pada 360/1440px |
| `paritas-bentuk.js` | empat pratinjau kepala, penyimpanan, kartu dan fallback nilai asing |
| `pemulihan-kartu.js` | hash kode, galat seragam, token wajib, pemulihan di peramban baru, kunci lama dicabut, kode sekali pakai dan pembuatan ulang |
| `beranda-peta.js` | beranda 200, kanvas 3D, lima tab, vendor lokal, Tentang dan tautan sambutan |
| `sambutan.js` | muncul pertama kali, diingat, buka ulang, Escape, sapaan warga, aksi Misi |
| `aku-panel.js` | validasi nama, terbit KTP dari panel, nomor dan kunci tersimpan serta dikenali setelah muat ulang |
| `ruko-panel.js` | lima tab sejajar di desktop dan ponsel, keadaan tamu, tanpa ruko, pemilik, dan katalog |
| `gambar-usaha.js` | pilih, ganti, hapus gambar lokal, sinkronisasi peta dan panel, URL bebas ditolak |
| `peta-3d-sakelar.js` | 3D jadi default, sakelar pindah mode, pilihan diingat, jalan di ponsel |
| `tata-letak-sakelar.js` | sakelar tidak menimpa tombol atau chip di 3 ukuran layar |
| `bilah-atas.js` | chip bilah atas peta 3D (merek, Chat, Poin, Siang, suasana) sama tinggi dan sejajar di 5 ukuran layar, tidak keluar layar, maksimal dua baris |
| `adr0004-satu-sumber-data.js` | koordinat 3D berasal dari `peta-posisi.php`, bukan salinan; cadangan tetap jalan bila endpoint mati |
| `tab-bar-panel.js` | tab bar lima tab dan panel bersayap di 4 ukuran layar × peta 3D/2D: panel tidak menimpa tab atau sakelar, tab aktif benar-benar bisa diketuk, dunia peta tetap terlihat |
| `tahap2-cari-sorot-interior.js` | pencarian warga (termasuk uji injeksi SQL dan wildcard), sorot bangunan dari daftar di 3D dan 2D, interior Balai Desa tanpa pindah halaman |
| `dompet-poin.js` | Fitur 1: kartu menerbitkan nomor + kunci, klaim harian sekali sehari (409 pada klaim kedua), poin misi sekali per warga per hari, isolasi saldo dua warga, kunci wajib, injeksi nomor/kunci/kode, panel Misi dan chip saldo di 3D dan 2D |
| `chat.js` | Chat publik, identitas dari kartu, autentikasi, kanal gang, batas laju, injeksi, dan polling panel |
| `chat-bebas-inbox.js` | Teks bebas, privasi inbox, notifikasi, status baca; `PETA_INBOX_UJI=/` menguji penerima di peta 3D |
| `ruko.js` | Fitur 3: warga tanpa KTP tidak bisa klaim, satu warga satu ruko, tautan hanya https (javascript/data/http ditolak), cerita dibatasi dan dicetak sebagai teks, gang mengikuti kartu, ruko tampil di peta 2D dan 3D serta daftar bangunan, lepas ruko tidak menghapus baris |
| `pasar.js` | Fitur 4: jadwal Pasar Malam (Sabtu) dan CFD (Minggu), lapak hanya saat acara berlangsung, batas 12 lapak, klaim ganda dan tautan tidak sah ditolak, lapak gratis (poin tidak berkurang), panel Pasar di peta 3D dan 2D, chip suasana jadi pintu masuk |
| `mode-jalan.js` | Keliling Desa: pemain lahir di luar tapak gedung, WASD dan D-pad benar-benar menggeser pemain, D memutar 90°, kamera langsung di belakang bahu, masuk/keluar Balai Desa tanpa pindah halaman, jalan di ponsel 390px |
| `ktp-kunci.js` | kunci rahasia warga tidak tercetak di `ktp.php?kartu=N` saat dibuka pengunjung lain, tetapi nama dan gang kartu tetap tampil |

Semua berkas keluar dengan kode 1 bila ada yang gagal.

Suite ruko membutuhkan kavling kosong. Untuk basis data pengembangan yang penuh,
jalankan pada salinan proyek dan basis data terpisah; arahkan `BASE_UJI` serta
`CWD_UJI` ke salinan yang sama. Jangan mengosongkan data produksi demi fixture.

Lima uji paritas memakai `bantuan-paritas.js` bersama. Salin berkas bantuan
itu juga saat menjalankan dari folder lain. Warga dan ruko yang dibuatnya
dibersihkan berdasarkan nomor khusus uji dalam blok `finally`.
