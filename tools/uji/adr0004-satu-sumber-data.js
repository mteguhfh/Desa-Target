const { chromium } = require('playwright-core');

// Uji ADR-0004: satu sumber data untuk tampilan 3D dan 2D.
// Membuktikan posisi kavling di peta 3D benar-benar berasal dari
// includes/peta-posisi.php (lewat data-desa.php), bukan salinan di JavaScript.

const BASE = process.env.BASE_UJI || 'http://localhost:8100';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

  // 1. Endpoint mengirim data posisi lengkap.
  const res = await page.request.get(BASE + '/peta3d/data-desa');
  const data = await res.json();
  catat('endpoint mengirim kavling', Array.isArray(data.kavling) && data.kavling.length === 12, String(data.kavling?.length));
  catat('endpoint mengirim zonaGang', data.zonaGang && Object.keys(data.zonaGang).length === 5);
  catat('endpoint mengirim fokus dan kolam', !!data.fokus && !!data.kolam, JSON.stringify(data.fokus));
  catat('endpoint mengirim versi data', !!data.versi, String(data.versi));
  catat('zona membawa kotak wara dari server', !!data.zonaGang['Gang Dagang'].wara);

  // 2. 3D memakai data server, bukan cadangan.
  await page.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await page.waitForTimeout(4000);
  const info = await page.evaluate(() => ({
    sumber: window.__desa?.sumberData,
    kavling: window.__desa?.posisiKavling,
    zona: Object.keys(window.__desa?.zonaGang || {}),
  }));
  catat('3D memakai data langsung', info.sumber === 'langsung', String(info.sumber));
  catat('kavling 3D sama persis dengan server',
    JSON.stringify(info.kavling) === JSON.stringify(data.kavling));
  catat('zona 3D sama persis dengan server',
    JSON.stringify(info.zona.sort()) === JSON.stringify(Object.keys(data.zonaGang).sort()));

  // 3. Tidak ada salinan koordinat yang berbeda dengan server.
  const samaDenganSumber = data.kavling.every(([x, y], i) => {
    const k = info.kavling[i];
    return k && k[0] === x && k[1] === y;
  });
  catat('setiap titik kavling identik angka demi angka', samaDenganSumber);

  // 4. Cadangan dipakai bila endpoint mati, dan peta tetap tampil.
  await page.route('**/data-desa', (r) => r.fulfill({ status: 500, body: '{}' }));
  await page.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await page.waitForTimeout(4000);
  const cad = await page.evaluate(() => ({ sumber: window.__desa?.sumberData, n: window.__desa?.posisiKavling.length }));
  catat('endpoint mati: peta tetap tampil dengan cadangan', cad.sumber === 'cadangan' && cad.n === 12, JSON.stringify(cad));
  catat('endpoint mati: 3D tetap merender', (await page.locator('#panggung canvas').count()) === 1);

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log(`\n=== ${hasil.length - gagal.length} lulus, ${gagal.length} gagal ===`);
  process.exit(gagal.length ? 1 : 0);
})();
