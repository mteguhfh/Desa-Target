const { jalankan } = require('./bantuan-paritas');
jalankan(async ({ halaman, cek, BASE }) => {
  const page = await halaman();
  const galat = [];
  page.on('pageerror', e => galat.push(e.message));
  const response = await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  cek('beranda membalas 200', response.status() === 200);
  await page.waitForSelector('#panggung canvas');
  cek('beranda menampilkan kanvas 3D', await page.locator('#panggung canvas').isVisible());
  cek('beranda punya lima tab', await page.locator('#tabBar .tab-item').count() === 5);
  const vendor = await page.locator('script[type="importmap"]').textContent();
  cek('Three.js berasal dari vendor lokal', vendor.includes('vendor/') && !/https?:/.test(vendor));
  cek('beranda tanpa galat JavaScript', galat.length === 0);
  const tentang = await page.goto(BASE + '/tentang.php', { waitUntil: 'networkidle' });
  cek('Tentang membalas 200', tentang.status() === 200);
  cek('Tentang punya tautan sambutan', await page.locator('a[href*="sambutan=1"]').count() > 0);
});
