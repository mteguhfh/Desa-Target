const { chromium } = require('playwright-core');

// Uji bilah atas peta 3D: semua chip sejajar secara vertikal, tidak ada yang
// keluar layar, dan di layar sempit bilah tetap rapi (boleh turun baris).
//
// Yang dijaga: lencana merek, Chat, Poin, Siang, dan chip suasana punya
// tinggi yang sama dan berbagi satu garis tengah. Keluhan yang jadi asal uji
// ini: tombol Chat dan Siang lebih tinggi daripada chip suasana, jadi barisnya
// terlihat bergerigi.
//
// Situs harus sudah berjalan di localhost:8100.

const BASE = process.env.BASE_UJI || 'http://localhost:8100';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const UKURAN = [
  { width: 1440, height: 900, nama: 'desktop besar' },
  { width: 1024, height: 768, nama: 'desktop kecil' },
  { width: 600, height: 800, nama: 'tablet sempit' },
  { width: 390, height: 844, nama: 'ponsel' },
  { width: 360, height: 740, nama: 'ponsel kecil' },
];
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

async function ukurBilah(page) {
  return page.evaluate(() => {
    const bar = document.querySelector('.bar-atas');
    const kotakBar = bar.getBoundingClientRect();
    const anak = [...bar.children]
      .filter((e) => !e.hidden && getComputedStyle(e).display !== 'none')
      .map((e) => {
        const r = e.getBoundingClientRect();
        return {
          kelas: e.className.split(' ')[0],
          x: Math.round(r.x),
          y: Math.round(r.y),
          w: Math.round(r.width),
          h: Math.round(r.height),
          tengah: Math.round(r.y + r.height / 2),
        };
      });
    return { kotakBar: { bottom: Math.round(kotakBar.bottom + window.scrollY) }, anak, lebar: document.documentElement.scrollWidth };
  });
}

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });

  for (const ukuran of UKURAN) {
    const label = ukuran.nama + ' (' + ukuran.width + 'px)';
    const page = await browser.newPage({ viewport: { width: ukuran.width, height: ukuran.height } });
    // Tampilkan chip Poin supaya bilah diuji dalam keadaan terlengkap.
    await page.addInitScript(() => {
      try {
        localStorage.setItem('dt_sambutan', '1');
        localStorage.setItem('dt_nomor', 'DT-0207');
        localStorage.setItem('dt_kunci', '56424da0a0f621438226b894b084a0e9');
      } catch (e) { /* localStorage diblokir */ }
    });
    await page.goto(BASE + '/peta.php', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    const bilah = await ukurBilah(page);
    const nama = bilah.anak.map((a) => a.kelas).join(', ');
    catat(label + ': chip Poin tampil untuk warga', bilah.anak.some((a) => a.kelas === 'chip-poin'), nama);

    const tinggi = [...new Set(bilah.anak.filter((a) => a.kelas !== 'chip-petunjuk').map((a) => a.h))];
    catat(label + ': semua chip sama tinggi', tinggi.length === 1, tinggi.join('/') + ' px');

    // Satu baris: semua sejajar. Bila turun baris, tiap baris tetap rata.
    const baris = {};
    for (const a of bilah.anak) {
      (baris[a.y] = baris[a.y] || []).push(a);
    }
    const deret = Object.keys(baris).map(Number).sort((a, b) => a - b);
    const rataBaris = deret.every((y) => new Set(baris[y].map((a) => a.tengah)).size === 1);
    catat(label + ': chip dalam satu baris punya garis tengah sama', rataBaris,
      bilah.anak.map((a) => a.kelas + '@' + a.tengah).join(' '));

    catat(label + ': tidak ada yang keluar layar',
      bilah.lebar <= ukuran.width && bilah.anak.every((a) => a.x >= 0 && a.x + a.w <= ukuran.width),
      'lebar dokumen ' + bilah.lebar + 'px');

    const tinggiBaris = deret.length * tinggi[0] + (deret.length - 1) * 8;
    catat(label + ': bilah atas tidak lebih dari dua baris', deret.length <= 2,
      deret.length + ' baris, tinggi ' + tinggiBaris + 'px');

    await page.close();
  }

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
