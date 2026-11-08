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

Batas verifikasi: belum seluruh suite tata letak lama dijalankan. Sinkronisasi animasi panen dengan skor server masih dipengaruhi latensi jaringan. Interior usaha berupa ruang contoh dengan meja dagang, belum katalog interaktif di dalam ruang.

Arah visual mengikuti rencana paritas: krem untuk kertas, navy untuk teks dan garis, kuning untuk aksi utama; tipografi dan struktur panel lama dipertahankan untuk keterbacaan. ENERGY 2 / RHYTHM 1 / MOTION 1, dengan gerak permainan sebagai mekanik dan gerak dunia sebagai navigasi.

Tracker tetap terbuka sampai seluruh kriteria dan regresi selesai diperiksa.
