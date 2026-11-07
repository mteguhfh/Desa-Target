const { chromium } = require('playwright-core');

// Uji Tahap 2: pencarian warga, sorot bangunan, dan interior Balai Desa di 3D.
//
// Situs harus sudah berjalan di localhost:8100.

const BASE = 'http://localhost:8100';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

async function buka(browser, url, vp = { width: 1440, height: 900 }) {
  const page = await browser.newPage({ viewport: vp });
  await page.goto(BASE + '/' + url, { waitUntil: 'networkidle' });
  await page.waitForFunction(() => {
    const m = document.getElementById('layarMuat');
    return !m || m.classList.contains('hilang');
  }, null, { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(600);
  return page;
}

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });

  // --- 1. Endpoint pencarian -------------------------------------------------
  {
    const page = await buka(browser, 'index.php');
    const cari = async (k) => (await page.request.get(BASE + '/cari-warga.php?cari=' + encodeURIComponent(k))).json();

    const nomor = await cari('DT-0001');
    catat('cari lewat nomor kartu', nomor.jumlah >= 1 && nomor.hasil[0].nomor === 'DT-0001', JSON.stringify(nomor.hasil[0] || null));

    const angka = await cari('1');
    catat('cari lewat angka saja', angka.jumlah >= 1, String(angka.jumlah));

    const kosong = await cari('');
    catat('kunci kosong tidak mengembalikan warga', kosong.jumlah === 0);

    // Wildcard SQL tidak boleh bocor: '%' dan '_' bukan pola pencarian.
    const persen = await cari('%');
    catat('wildcard % tidak membocorkan semua warga', persen.jumlah === 0, String(persen.jumlah));

    const injeksi = await cari("' OR 1=1 --");
    catat('percobaan injeksi SQL tidak tembus', injeksi.jumlah === 0, String(injeksi.jumlah));

    const panjang = await cari('A'.repeat(200));
    catat('kunci panjang dipotong 50 karakter', panjang.cari.length === 50, String(panjang.cari.length));
    await page.close();
  }

  // --- 2. Halaman daftar warga bekerja tanpa JavaScript ---------------------
  {
    const page = await buka(browser, 'warga.php?cari=DT-0001');
    catat('warga.php menampilkan hasil pencarian',
      (await page.locator('.daftar-warga-baris').count()) >= 1);
    const tanpaJsNama = await page.locator('.daftar-warga-nama').first().innerText();
    catat('warga.php menampilkan nama warga', tanpaJsNama.length > 0, tanpaJsNama);
    await page.close();
  }

  // --- 3. Pencarian di dalam peta tanpa memuat ulang -------------------------
  for (const url of ['peta.php', 'peta2d.php']) {
    const page = await buka(browser, url);
    await page.locator('.tab-item[data-panel="warga"]').click();
    await page.waitForTimeout(500);
    const urlSebelum = page.url();

    await page.locator('#cariWargaKunci').fill('DT-0001');
    await page.waitForTimeout(900);
    const isi = await page.locator('#cariWargaHasil').innerText();
    catat(url + ': pencarian langsung menampilkan hasil', isi.includes('DT-0001'), isi.slice(0, 40));
    catat(url + ': pencarian tidak memuat ulang halaman', page.url() === urlSebelum);

    await page.locator('#cariWargaKunci').fill('zzzz tidak ada');
    await page.waitForTimeout(900);
    const kosong = await page.locator('#cariWargaHasil').innerText();
    catat(url + ': pesan jelas bila warga tidak ada', kosong.toLowerCase().includes('tidak ada'), kosong.slice(0, 40));

    // Hasil pencarian berupa tautan ke KTP warga itu.
    await page.locator('#cariWargaKunci').fill('DT-0001');
    await page.waitForTimeout(900);
    const href = await page.locator('.cari-warga-baris').first().getAttribute('href');
    catat(url + ': hasil bisa diketuk menuju KTP', /ktp\.php\?kartu=1$/.test(href || ''), String(href));
    await page.close();
  }

  // --- 4. Sorot bangunan dari daftar ----------------------------------------
  {
    const page = await buka(browser, 'peta.php');
    await page.locator('.tab-item[data-panel="bangunan"]').click();
    await page.waitForTimeout(600);
    const jumlah = await page.locator('.daftar-bangunan-tombol').count();
    catat('daftar bangunan terisi', jumlah >= 1, jumlah + ' bangunan');

    const kameraAwal = await page.evaluate(() => ({
      x: Math.round(window.__desa.camera.position.x),
      z: Math.round(window.__desa.camera.position.z),
    }));
    // Ketuk baris terakhir (bangunan tertua, bukan yang terdekat kamera).
    await page.locator('.daftar-bangunan-tombol').last().click();
    await page.waitForTimeout(1800);
    const sesudah = await page.evaluate(() => ({
      x: Math.round(window.__desa.camera.position.x),
      z: Math.round(window.__desa.camera.position.z),
      kavling: window.__desa.kavlingDisorot,
      pop: document.getElementById('popover').classList.contains('tampil'),
    }));
    const bergerak = Math.abs(sesudah.x - kameraAwal.x) > 50 || Math.abs(sesudah.z - kameraAwal.z) > 50;
    catat('3D: kamera terbang ke bangunan', bergerak, JSON.stringify(kameraAwal) + ' -> ' + JSON.stringify(sesudah));
    catat('3D: kavling ditandai tersorot', Number.isInteger(sesudah.kavling));
    catat('3D: kartu detail terbuka', sesudah.pop);
    catat('3D: tombol daftar ditandai aktif', (await page.locator('.daftar-bangunan-tombol.aktif').count()) === 1);
    await page.close();
  }
  {
    const page = await buka(browser, 'peta2d.php');
    await page.locator('.tab-item[data-panel="bangunan"]').click();
    await page.waitForTimeout(600);
    await page.locator('.daftar-bangunan-tombol').last().click();
    await page.waitForTimeout(900);
    catat('2D: cincin sorot muncul', (await page.locator('.cincin-sorot').count()) === 1);
    catat('2D: kartu detail terbuka', await page.locator('#petaPopover').isVisible().catch(() => false));
    await page.close();
  }

  // --- 5. Interior Balai Desa di dalam 3D -----------------------------------
  {
    const page = await buka(browser, 'peta.php');
    catat('interior tertutup di awal', await page.locator('#interior3d').isHidden());
    await page.evaluate(() => document.getElementById('chipMasuk').click());
    await page.waitForTimeout(900);
    catat('interior terbuka tanpa pindah halaman',
      (await page.locator('#interior3d').isVisible()) && page.url().endsWith('/peta.php'));
    catat('interior punya 4 hotspot', (await page.locator('#interior3d .hotspot-interior').count()) === 4);
    catat('interior punya tautan loket KTP', (await page.locator('#interior3d a[href="ktp.php"]').count()) === 1);

    await page.locator('#interior3d .hotspot-interior').first().click();
    await page.waitForTimeout(600);
    catat('hotspot interior membuka kartu', await page.locator('#popover').isVisible());
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);

    await page.evaluate(() => document.getElementById('tombolKeluarInterior3d').click());
    await page.waitForTimeout(500);
    catat('keluar interior kembali ke dunia 3D',
      (await page.locator('#interior3d').isHidden()) &&
      (await page.locator('#panggung canvas').count()) === 1);
    await page.close();
  }

  // --- 6. Tautan ?cari= langsung membuka panel ------------------------------
  {
    const page = await buka(browser, 'peta.php?cari=DT-0001');
    const panel = await page.locator('#panelBersayap').isVisible();
    const isi = await page.locator('#cariWargaHasil').innerText();
    catat('?cari= membuka panel warga dengan hasil', panel && isi.includes('DT-0001'), isi.slice(0, 40));
    await page.close();
  }

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
