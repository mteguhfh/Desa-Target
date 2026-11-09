const { chromium } = require('playwright-core');

const BASE = process.env.BASE_UJI || 'http://localhost:8100';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus, detail });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });

  // ============ DESKTOP ============
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  // Pop-up sambutan menutupi sakelar pada kunjungan pertama. Uji ini menguji
  // peta, bukan sambutan, jadi tandai sudah pernah tampil sebelum halaman dibuka.
  await ctx.addInitScript(() => {
    try { localStorage.setItem('dt_sambutan', '1'); } catch (e) { /* localStorage diblokir */ }
  });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(String(e).slice(0, 160)));
  page.on('console', (m) => { if (m.type() === 'error') errs.push(m.text().slice(0, 160)); });

  // 1. Default 3D
  await page.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await page.waitForTimeout(4000);
  catat('peta menampilkan 3D sebagai default', (await page.locator('#panggung canvas').count()) === 1);
  const catatan = await page.locator('#catatanData').innerText();
  catat('3D memuat data dari basis data', catatan.includes('Data langsung'), catatan.slice(0, 75));
  const aktif = (await page.locator('.sakelar-opsi.aktif').innerText()).trim();
  catat('sakelar menandai 3D aktif', aktif === '3D', 'aktif=' + aktif);
  catat('URL tetap /peta (tanpa redirect)', page.url().endsWith('/peta'), page.url());

  // Kontrol peta 3D hadir. "Papan Gang" kini berupa tab di tab bar bawah
  // (bukan tombol di tumpukan kontrol), jadi diperiksa terpisah.
  for (const id of ['tombolJalan', 'tombolZoomIn', 'tombolZoomOut', 'tombolReset']) {
    if ((await page.locator('#' + id).count()) === 0) catat('kontrol ' + id + ' ada', false);
  }
  catat('kontrol peta 3D lengkap', true, 'Keliling/zoom/reset');
  catat('tab Papan Gang ada', (await page.locator('#tabBar .tab-item[data-panel="papan-gang"]').count()) === 1);
  await page.screenshot({ path: '/tmp/opencode/shot-3d.png' });

  // 2. Switch ke 2D
  await page.locator('.sakelar-opsi[data-mode="2d"]').click();
  await page.waitForLoadState('networkidle');
  catat('sakelar 2D pindah ke peta2d.php', page.url().endsWith('/peta2d'), page.url());
  catat('peta 2D merender SVG desa', (await page.locator('svg.peta-svg').count()) > 0);
  catat('peta 2D menandai 2D aktif', (await page.locator('.sakelar-opsi.aktif').innerText()).trim() === '2D');
  await page.screenshot({ path: '/tmp/opencode/shot-2d.png' });

  // 3. Pengingat pilihan
  await page.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  catat('pilihan 2D diingat saat buka ulang peta', page.url().includes('/peta2d'), page.url());

  // 4. Balik ke 3D & diingat
  await page.locator('.sakelar-opsi[data-mode="3d"]').click();
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2500);
  catat('sakelar 3D kembali ke peta 3D', !page.url().includes('peta2d'), page.url());
  catat('3D benar-benar merender ulang', (await page.locator('#panggung canvas').count()) === 1);

  await page.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  catat('pilihan 3D diingat', !page.url().includes('peta2d'), page.url());

  // 5. peta3d/ langsung still works
  await page.goto(BASE + '/peta3d/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);
  catat('peta3d/ langsung tetap 3D', (await page.locator('#panggung canvas').count()) === 1);

  // 6. halaman lain tidak rusak
  for (const h of ['', 'ktp']) {
    const r = await page.goto(BASE + '/' + h, { waitUntil: 'networkidle' });
    catat(h + ' tetap normal', r.status() === 200);
  }

  const penting = errs.filter((e) => !/WebGL|favicon/i.test(e));
  catat('tidak ada galat JavaScript', penting.length === 0, penting.slice(0, 2).join(' || '));
  await ctx.close();

  // ============ PONSEL ============
  const ctx2 = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await ctx2.addInitScript(() => {
    try { localStorage.setItem('dt_sambutan', '1'); } catch (e) { /* localStorage diblokir */ }
  });
  const hp = await ctx2.newPage();
  await hp.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await hp.waitForTimeout(3500);
  const kotak = await hp.locator('.sakelar-peta').boundingBox();
  catat('sakelar muat di layar 390px', !!kotak && kotak.x >= 0 && kotak.x + kotak.width <= 390, JSON.stringify(kotak));
  const tombol = await hp.locator('.sakelar-opsi[data-mode="2d"]').boundingBox();
  catat('target sentuh >= 44x32', !!tombol && tombol.width >= 44 && tombol.height >= 32, JSON.stringify(tombol));
  catat('3D jalan di ponsel', (await hp.locator('#panggung canvas').count()) === 1);
  await hp.screenshot({ path: '/tmp/opencode/shot-mobile.png' });
  await ctx2.close();

  await browser.close();

  const gagal = hasil.filter((h) => !h.lulus);
  console.log(`\n=== ${hasil.length - gagal.length} lulus, ${gagal.length} gagal dari ${hasil.length} ===`);
  gagal.forEach((g) => console.log('  GAGAL:', g.nama, g.detail));
  process.exit(gagal.length ? 1 : 0);
})();
