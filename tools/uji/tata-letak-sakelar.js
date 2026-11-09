const { chromium } = require('playwright-core');
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const BASE = process.env.BASE_UJI || 'http://localhost:8100';

async function cekTumpang(page, namaHalaman, selektorLain) {
  const s = await page.locator('.sakelar-peta').boundingBox();
  if (!s) return [{ el: 'sakelar-hilang', namaHalaman }];
  const bentrok = [];
  for (const sel of selektorLain) {
    const n = await page.locator(sel).count();
    if (!n) continue;
    const r = await page.locator(sel).first().boundingBox();
    if (!r) continue;
    const tumpuk =
      !(s.x + s.width <= r.x || r.x + r.width <= s.x || s.y + s.height <= r.y || r.y + r.height <= s.y);
    if (tumpuk) bentrok.push({ sel, s, r });
  }
  return bentrok;
}

(async () => {
  const b = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });
  let total = 0;
  let gagal = 0;

  for (const [lebar, tinggi, label] of [
    [1440, 900, 'desktop'],
    [1024, 768, 'tablet'],
    [390, 844, 'ponsel'],
  ]) {
    const ctx = await b.newContext({ viewport: { width: lebar, height: tinggi } });
    const p = await ctx.newPage();

    const lain3d = ['#tombolJalan', '#tombolPapan', '#tombolZoomIn', '#tombolZoomOut', '#tombolReset', '#chipPetunjuk', '#dpad', '#kartuIntro', '.bar-atas'];
    const lain2d = ['#tombolJalan', '#tombolPanelGang', '#zoomMasuk', '#zoomKeluar', '#zoomReset', '#layarPenuh', '#petaDpad', '.peta-chip', '.peta-intro', '.peta-gulir-bawah'];

    // 3D
    await p.goto(BASE + '/peta?tetap=1', { waitUntil: 'networkidle' });
    await p.waitForTimeout(3500);
    let b3 = await cekTumpang(p, '3D-' + label, lain3d);
    total++; if (b3.length) { gagal++; console.log(`BENTROK 3D ${label}:`, b3.map((x) => x.sel).join(', ')); } else console.log(`LULUS   3D ${label}: sakelar tidak menimpa elemen lain`);
    await p.screenshot({ path: `/tmp/opencode/fix-3d-${label}.png` });

    // 2D
    await p.goto(BASE + '/peta2d', { waitUntil: 'networkidle' });
    await p.waitForTimeout(1500);
    let b2 = await cekTumpang(p, '2D-' + label, lain2d);
    total++; if (b2.length) { gagal++; console.log(`BENTROK 2D ${label}:`, b2.map((x) => x.sel).join(', ')); } else console.log(`LULUS   2D ${label}: sakelar tidak menimpa elemen lain`);
    await p.screenshot({ path: `/tmp/opencode/fix-2d-${label}.png` });

    await ctx.close();
  }

  await b.close();
  console.log(`\n=== ${total - gagal} lulus, ${gagal} gagal ===`);
  process.exit(gagal ? 1 : 0);
})();
