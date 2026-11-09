const assert = require('node:assert/strict');
const { chromium } = require('playwright-core');
const { execFileSync } = require('node:child_process');
const BASE = process.env.BASE_UJI || 'http://localhost:8100';
const CWD = process.env.CWD_UJI || '/home/mteguhfh/Documents/Kejartarget/Desa_kejartarget';
function php(kode, ...args) {
  return JSON.parse(execFileSync('php', ['-r', 'require "db.php"; ' + kode, ...args], { cwd: CWD, encoding: 'utf8' }));
}
async function jalankan(uji) {
  const browser = await chromium.launch({ executablePath: process.env.CHROME_UJI || '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome', args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'] });
  const nomorUji = [];
  let total = 0;
  const cek = (nama, kondisi) => { assert.ok(kondisi, nama); total++; console.log('LULUS ' + nama); };
  async function halaman(id = null, sambutan = true, width = 1024) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
    page.setDefaultTimeout(30000);
    await page.addInitScript(({ id, sambutan }) => {
      if (sambutan) localStorage.setItem('dt_sambutan', '1');
      if (id) { localStorage.setItem('dt_nomor', id.nomor); localStorage.setItem('dt_kunci', id.kunci); }
    }, { id, sambutan });
    return page;
  }
  async function buatWarga() {
    const page = await halaman();
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    const response = await page.request.post(BASE + '/ktp-json', { form: { nama: 'Warga Uji Paritas', gang: 'Gang Kuliner', izin_penerbitan: '1', token: await page.evaluate(() => window.__tokenKtp) } });
    assert.equal(response.status(), 200);
    const warga = await response.json();
    nomorUji.push(warga.nomor);
    await page.close();
    return warga;
  }
  try {
    await uji({ halaman, buatWarga, nomorUji, cek, php, BASE });
    console.log(`=== ${total} lulus, 0 gagal ===`);
  } catch (e) { console.error('GAGAL', e.stack); process.exitCode = 1; }
  finally {
    await browser.close();
    for (const nomor of nomorUji) php('$p=db(); $p->beginTransaction(); foreach (["ruko_klaim","bangunan","poin_log","klaim_harian","misi_selesai"] as $t) { $s=$p->prepare("DELETE FROM $t WHERE nomor=?"); $s->execute([$argv[1]]); } $s=$p->prepare("DELETE FROM warga WHERE id=?"); $s->execute([(int)substr($argv[1],3)]); $p->commit(); echo "true";', nomor);
  }
}
module.exports = { jalankan };
