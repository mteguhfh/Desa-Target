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

| berkas | yang diperiksa |
| --- | --- |
| `peta-3d-sakelar.js` | 3D jadi default, sakelar pindah mode, pilihan diingat, jalan di ponsel |
| `tata-letak-sakelar.js` | sakelar tidak menimpa tombol atau chip di 3 ukuran layar |
| `adr0004-satu-sumber-data.js` | koordinat 3D berasal dari `peta-posisi.php`, bukan salinan; cadangan tetap jalan bila endpoint mati |
| `tab-bar-panel.js` | tab bar dan panel bersayap di 4 ukuran layar × peta 3D/2D: panel tidak menimpa tab atau sakelar, tab aktif benar-benar bisa diketuk, dunia peta tetap terlihat |
| `tahap2-cari-sorot-interior.js` | pencarian warga (termasuk uji injeksi SQL dan wildcard), sorot bangunan dari daftar di 3D dan 2D, interior Balai Desa tanpa pindah halaman |

Semua berkas keluar dengan kode 1 bila ada yang gagal.
