const { chromium } = require('playwright-core');
const { execFileSync } = require('child_process');

// Uji keamanan kartu: kunci rahasia warga tidak boleh tercetak di halaman
// publik `ktp?kartu=N`. Nomor kartu berurutan, jadi kalau kunci ikut
// tercetak, siapa pun bisa membuka kartu warga lain dan memakai kuncinya.
//
// Yang dijaga:
//   - pengunjung tanpa sesi tidak mendapat #ktpIdentitas maupun data-kunci
//   - kunci warga tidak muncul sama sekali di HTML halaman kartu
//   - kartu tetap bisa dibaca (nama dan gang tampil)
//
// Situs harus sudah berjalan di localhost:8100.

const BASE = process.env.BASE_UJI || 'http://localhost:8100';
const CWD = process.env.CWD_UJI || '/home/mteguhfh/Documents/Kejartarget/Desa_kejartarget';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

function php(kode, ...arg) {
  return execFileSync('php', ['-r', kode, ...arg.map(String)], { cwd: CWD, encoding: 'utf8' });
}

(async () => {
  // Ambil satu warga yang sudah punya kunci langsung dari basis data.
  const calon = JSON.parse(php(`
    require "db.php";
    $w = db()->query("SELECT id, kunci, nama FROM warga WHERE kunci IS NOT NULL AND kunci <> '' ORDER BY id ASC LIMIT 1")->fetch();
    echo json_encode($w ?: null);
  `));
  if (!calon) {
    catat('ada warga dengan kunci untuk diuji', false, 'basis data kosong');
    process.exit(1);
  }

  const browser = await chromium.launch({ executablePath: CHROME });
  // Konteks baru tanpa cookie sesi: ini posisi pengunjung asing.
  const ctx = await browser.newContext({ viewport: { width: 1024, height: 900 } });
  const page = await ctx.newPage();
  await page.goto(BASE + '/ktp?kartu=' + calon.id, { waitUntil: 'networkidle' });

  const jumlahIdentitas = await page.locator('#ktpIdentitas').count();
  catat('kartu publik tidak punya #ktpIdentitas', jumlahIdentitas === 0, String(jumlahIdentitas));

  const punyaKunci = await page.evaluate(() => document.body.innerHTML.includes('data-kunci'));
  catat('kartu publik tidak memuat data-kunci', punyaKunci === false);

  const html = await page.content();
  catat('kunci warga tidak bocor di HTML', html.includes(calon.kunci) === false,
    html.includes(calon.kunci) ? 'kunci ditemukan' : 'kunci tidak ditemukan');

  const teks = await page.locator('.ktp-nama').innerText();
  catat('kartu tetap menampilkan nama warga', teks.trim() === calon.nama.trim(), teks.trim());

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
