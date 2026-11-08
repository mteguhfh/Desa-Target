# Gelombang 3

Implementasi: panen harian, kategori dan harga usaha, interior Pasar dan ruko contoh, permukaan kertas, empat bentuk kepala.

Verifikasi lokal:
- Lint seluruh PHP dan git diff --check lulus.
- Panel Ruko: 12 pemeriksaan lulus pada desktop dan mobile.
- Tab bar dan panel 2D/3D: 130 pemeriksaan lulus pada empat ukuran layar.
- Pasar: 36 pemeriksaan lulus setelah fixture penerbitan menyertakan izin.
- Browser 360 dan 1440px: tombol masuk Pasar/ruko membuka interior; Escape kembali tanpa navigasi; tidak ada pageerror atau overflow horizontal.
- Penerbitan kartu caping melalui formulir berhasil dan kartu memakai bentuk caping.
- Endpoint panen menerima sesi mulai dan menghitung hasil sendiri meskipun klien mengirim skor 100.
- Database memori: hadiah 5 poin, saldo konsisten, klaim kedua ditolak.
- Lapak dalam transaksi rollback: kategori makanan dan harga 15000 tersimpan dan terbaca.

Batas verifikasi: belum seluruh suite lama dijalankan. Interior usaha berupa ruang contoh dengan meja dagang, belum katalog interaktif di dalam ruang.

Verifikasi lanjutan: `paritas-mini-game.js` lulus 15 pemeriksaan, termasuk kunci salah, aksi asing, batas waktu, token palsu/replay, penundaan kirim 1,6 detik, hadiah kedua, dan saldo 5 poin. Skor dihitung server dari waktu ketuk klien yang dibatasi umur sesi; waktu klien tetap bisa dimanipulasi, sehingga ini bukan bukti keterampilan pemain. Batas satu hasil per hari tetap ditegakkan database. Uji `tata-letak-sakelar.js` lulus 6/6 setelah posisi sakelar ponsel diturunkan agar tidak bertabrakan dengan bilah atas; suite kini menghormati BASE_UJI.

Arah visual mengikuti rencana paritas: krem untuk kertas, navy untuk teks dan garis, kuning untuk aksi utama; tipografi dan struktur panel lama dipertahankan untuk keterbacaan. ENERGY 2 / RHYTHM 1 / MOTION 1, dengan gerak permainan sebagai mekanik dan gerak dunia sebagai navigasi.

Verifikasi 2026-11-09:
- `paritas-katalog.js`: 26/26, kategori/harga/filter/fallback, Pasar dan ruko, keyboard dan D-pad, keluar tanpa navigasi, tanpa overflow atau pageerror pada 360/1440px.
- `paritas-bentuk.js`: 19/19, empat pratinjau, penyimpanan, kartu, nilai asing menjadi pet. Bentuk SVG 2D dan geometri 3D diperiksa pada pembentuk figur; CSS peci/rambut dipindahkan setelah gaya dasar agar benar-benar berlaku.
- Kontras terukur dengan luminansi sRGB: navy/kertas 13,58:1; navy/kuning 9,46:1; hijau/kertas 6,78:1; abu/kertas 4,52:1.
- Regresi ruko 47/47 pada salinan basis data terpisah dengan kavling kosong. Fixture izin pada chat/dompet/ruko/Aku disesuaikan; suite pencarian mengikuti perilaku warga terbaru dan tombol tujuan warga yang sudah berlaku sejak Gelombang 2.
- Lint seluruh PHP dan pemeriksaan whitespace bersih.
- Regresi akhir: adr0004 11, Aku 11, beranda 7, bilah atas 25, chat 64, dompet 47, gambar 9, kunci publik 4, mode jalan 19, Pasar 36, pemulihan 13, sakelar 20, ruko 47, panel ruko 12, sambutan 8, panel/tab 130, pencarian/interior Balai 31, tata letak 6; semua lulus.
- Deploy `c499096`: stylesheet terbaru diunggah tanpa menyentuh SQLite. Smoke browser produksi 360px: beranda/KTP/Pasar/Ruko 200; peci memakai radius 3px dan tidak ada overflow pada halaman KTP.
