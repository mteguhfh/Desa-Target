const { chromium } = require('playwright-core');

// Uji tab bar bawah dan panel bersayap di peta 3D dan peta 2D.
//
// Yang dijaga: dunia peta tidak boleh hilang saat berpindah isi, panel tidak
// boleh menimpa tab bar maupun sakelar 3D/2D, dan ketukan harus benar-benar
// sampai ke tombolnya (bukan tertutup lapisan lain).

const BASE = 'http://localhost:8100';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const UKURAN = [
  { width: 1440, height: 900, nama: 'desktop besar' },
  { width: 1024, height: 768, nama: 'desktop kecil' },
  { width: 390, height: 844, nama: 'ponsel' },
  { width: 360, height: 740, nama: 'ponsel kecil' },
];
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

// Siapkan halaman peta dan tunggu sampai layar muat 3D benar-benar hilang,
// supaya ketukan tidak tertelan lapisan muat.
async function bukaPeta(browser, halaman, ukuran) {
  const page = await browser.newPage({ viewport: { width: ukuran.width, height: ukuran.height } });
  await page.goto(BASE + '/' + halaman, { waitUntil: 'networkidle' });
  await page.waitForFunction(() => {
    const m = document.getElementById('layarMuat');
    return !m || m.classList.contains('hilang');
  }, null, { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(500);
  return page;
}

// Jarak antar dua kotak; negatif berarti bertumpuk.
function tumpang(a, b) {
  return !(a.x + a.width <= b.x || b.x + b.width <= a.x || a.y + a.height <= b.y || b.y + b.height <= a.y);
}

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });

  for (const halaman of ['peta.php', 'peta2d.php']) {
    for (const ukuran of UKURAN) {
      const label = halaman + ' ' + ukuran.nama;
      const page = await bukaPeta(browser, halaman, ukuran);
      const galat = [];
      page.on('pageerror', (e) => galat.push(String(e).slice(0, 120)));

      const jumlahTab = await page.locator('#tabBar .tab-item').count();
      catat(label + ': 4 tab tampil', jumlahTab === 4, String(jumlahTab));

      // Panel tertutup saat halaman baru dibuka.
      catat(label + ': panel tertutup di awal', await page.locator('#panelBersayap').isHidden());

      // Buka Papan Gang.
      await page.locator('.tab-item[data-panel="papan-gang"]').click();
      await page.waitForTimeout(700);
      catat(label + ': panel Papan Gang terbuka', await page.locator('#panelBersayap').isVisible());
      const barisPapan = await page.locator('#panelBersayap [data-isi="papan-gang"] .papan-baris').count();
      catat(label + ': isi papan gang terisi', barisPapan >= 5, barisPapan + ' baris');

      // Tata letak: panel tidak menimpa tab bar maupun sakelar.
      const kotak = await page.evaluate(() => {
        const r = (id) => { const el = document.getElementById(id) || document.querySelector(id); const b = el.getBoundingClientRect(); return { x: b.x, y: b.y, width: b.width, height: b.height }; };
        return { panel: r('panelBersayap'), tab: r('tabBar'), sakelar: r('.sakelar-peta') };
      });
      catat(label + ': panel tidak menimpa tab bar', !tumpang(kotak.panel, kotak.tab),
        'celah ' + Math.round(kotak.tab.y - (kotak.panel.y + kotak.panel.height)) + 'px');
      catat(label + ': panel tidak menimpa sakelar 3D/2D', !tumpang(kotak.panel, kotak.sakelar));
      catat(label + ': dunia peta masih terlihat di atas panel', kotak.panel.y >= 150,
        Math.round(kotak.panel.y) + 'px');

      // Tab yang aktif harus benar-benar bisa diketuk (bukan tertutup lapisan).
      const bisaDiketuk = await page.evaluate(() => {
        const t = document.querySelector('.tab-item.aktif').getBoundingClientRect();
        const el = document.elementFromPoint(t.x + t.width / 2, t.y + t.height / 2);
        return !!(el && el.closest('#tabBar'));
      });
      catat(label + ': tab aktif bisa diketuk', bisaDiketuk);

      // Ketuk tab yang sama menutup panel.
      await page.locator('.tab-item[data-panel="papan-gang"]').click();
      await page.waitForTimeout(600);
      catat(label + ': ketuk tab yang sama menutup panel', await page.locator('#panelBersayap').isHidden());

      // Tab Warga berganti isi tanpa memuat ulang halaman.
      const urlSebelum = page.url();
      await page.locator('.tab-item[data-panel="warga"]').click();
      await page.waitForTimeout(600);
      const judul = await page.locator('#panelBersayapJudul').innerText();
      catat(label + ': tab Warga menampilkan pencarian', judul.toLowerCase().includes('cari'), judul);
      catat(label + ': tidak ada muat ulang halaman', page.url() === urlSebelum);

      // Esc menutup panel.
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
      catat(label + ': Esc menutup panel', await page.locator('#panelBersayap').isHidden());

      // Dunia 3D tetap hidup (khusus peta.php).
      if (halaman === 'peta.php') {
        catat(label + ': kanvas 3D tetap ada', (await page.locator('#panggung canvas').count()) === 1);
      }

      catat(label + ': tanpa galat JavaScript', galat.length === 0, galat.slice(0, 2).join(' | '));
      await page.close();
    }
  }

  // Tab Desa dan Aku adalah tautan biasa menuju halaman yang benar.
  for (const ukuran of [UKURAN[0], UKURAN[2]]) {
    const page = await bukaPeta(browser, 'peta.php', ukuran);
    // Di halaman peta, slot "Desa" dipakai tombol Bangunan, jadi tautan yang
    // tersisa di tab bar adalah Aku.
    catat('tab Bangunan tersedia di peta (' + ukuran.width + 'px)',
      (await page.locator('#tabBar .tab-item[data-panel="bangunan"]').count()) === 1);
    const hrefAku = await page.locator('#tabBar a.tab-item').last().getAttribute('href');
    catat('tautan tab Aku benar (' + ukuran.width + 'px)', hrefAku === 'ktp.php', String(hrefAku));
    await page.close();
  }

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
