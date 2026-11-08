const { chromium } = require('playwright-core');

// Uji Fitur 3: ruko komersil warga.
//
// Yang dijaga:
//   - tanpa KTP tidak bisa klaim (403 di fungsi inti dan halaman menolak)
//   - satu warga satu ruko; klaim kedua ditolak dengan pesan jelas
//   - nama usaha dan cerita aman (teks biasa, bukan HTML), cerita dibatasi
//   - tautan hanya https; javascript:, data:, http:, dan tautan berisi
//     sandi ditolak
//   - gang ruko mengikuti kartu, bukan pilihan pemain
//   - ruko muncul di peta 3D (popover + tombol Kunjungi), peta 2D (popover
//     + tautan), daftar bangunan, dan data-desa.php
//   - lepas ruko menyembunyikan bangunan dari peta, bukan menghapus barisnya
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

// Panggil fungsi PHP langsung untuk uji jalur server tanpa peramban.
function php(kode, ...arg) {
  const { execFileSync } = require('child_process');
  return execFileSync('php', ['-r', kode, ...arg.map(String)], { cwd: CWD, encoding: 'utf8' });
}

async function buatWarga(browser, nama, gang) {
  const page = await browser.newPage();
  await page.goto(BASE + '/ktp.php', { waitUntil: 'networkidle' });
  await page.goto(BASE + '/ktp.php', { waitUntil: 'networkidle' });
  await page.locator('#nama').fill(nama);
  await page.locator('#gang').selectOption(gang);
  const [jawaban] = await Promise.all([
    page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('ktp.php')),
    page.locator('button[type="submit"]').click(),
  ]);
  const lokasi = jawaban.headers()['location'] || '';
  const cocok = lokasi.match(/kartu=(\d+)/);
  if (!cocok) throw new Error('KTP tidak terbit untuk ' + nama + ': ' + lokasi);
  await page.goto(BASE + '/ktp.php?kartu=' + cocok[1], { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#ktpIdentitas', { state: 'attached' });
  const identitas = await page.evaluate(() => ({
    nomor: document.getElementById('ktpIdentitas').getAttribute('data-nomor'),
    kunci: document.getElementById('ktpIdentitas').getAttribute('data-kunci'),
  }));
  await page.close();
  return identitas;
}

// Kirim formulir ruko lewat halaman sungguhan (POST lalu redirect).
async function klaimLewatHalaman(page, isian) {
  await page.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  if (!(await page.locator('#formRuko').count())) return { ada: false };
  await page.locator('#namaUsaha').fill(isian.nama || '');
  await page.locator('#ceritaUsaha').fill(isian.cerita || '');
  await page.locator('#tautanUsaha').fill(isian.tautan || '');
  // pattern https:// di input type=url ikut memeriksa di peramban; uji
  // tautan tidak sah memakai jalur server langsung di bawah.
  const [jawaban] = await Promise.all([
    page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('ruko.php')),
    page.locator('#formRuko button[type="submit"]').click(),
  ]);
  await page.waitForLoadState('networkidle');
  return { ada: true, status: jawaban.status(), isi: await page.content() };
}

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });
  const api = await browser.newPage();

  const huruf = 'abcdefghijklmnopqrstuvwxyz';
  let sisa = Number(String(Date.now()).slice(-4));
  let cap = '';
  for (let i = 0; i < 4; i++) { cap += huruf[sisa % 26]; sisa = Math.floor(sisa / 26); }
  const namaUsaha = 'Warung Uji Ruko ' + cap;
  const tautanBaik = 'https://wa.me/6281200000000';
  const cerita = 'Kopi dan gorengan hangat tiap sore.';

  // ---------------------------------------------------------------------------
  // 1. Tanpa kartu: halaman menolak, fungsi inti juga menolak
  // ---------------------------------------------------------------------------
  const tamu = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await tamu.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  // Identitas localStorage ditukar lewat POST; beri waktu halaman memuat
  // ulang bila ada, lalu pastikan tetap tamu.
  await tamu.waitForTimeout(600);
  catat('tanpa kartu tidak ada formulir klaim', await tamu.locator('#formRuko').count() === 0);
  catat('tanpa kartu diarahkan bikin KTP', /Bikin KTP Warga dulu/.test(await tamu.content()));
  await tamu.close();

  const tolakTanpaKartu = php(
    'require "db.php"; echo json_encode(klaim_ruko(db(), "DT-999999", "", "Toko Uji", "", "https://contoh.id", "Gang Jasa", 12));'
  );
  catat('fungsi klaim menolak tanpa kunci', JSON.parse(tolakTanpaKartu).galat === 'warga', tolakTanpaKartu);

  // ---------------------------------------------------------------------------
  // 2. Warga dengan kartu: klaim ruko lewat halaman
  // ---------------------------------------------------------------------------
  const warga = await buatWarga(browser, 'Uji Ruko ' + cap, 'Gang Kuliner');
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await page.addInitScript(([nomor, kunci]) => {
    localStorage.setItem('dt_nomor', nomor);
    localStorage.setItem('dt_kunci', kunci);
  }, [warga.nomor, warga.kunci]);
  // Kunjungan pertama hanya membangun cookie sesi (token formulir disimpan
  // di sesi), jadi halaman dimuat sekali lagi seperti pada ktp.php.
  await page.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  await page.waitForSelector('#formRuko', { timeout: 8000 }).catch(() => {});
  catat('warga dengan kartu melihat formulir klaim', await page.locator('#formRuko').count() === 1);
  catat('identitas peramban terpasang di formulir (nomor dari localStorage)',
    (await page.locator('#formRuko input[name="nomor"]').inputValue()) === warga.nomor);
  catat('gang ruko mengikuti kartu', /Gang Kuliner/.test(await page.locator('.kartu-formulir').first().innerText()));

  const rukoAwal = await api.request.get(BASE + '/peta3d/data-desa.php');
  const bangunanAwal = (await rukoAwal.json()).bangunan.filter((b) => b.jenis === 'ruko').length;

  await klaimLewatHalaman(page, { nama: namaUsaha, cerita, tautan: tautanBaik });
  const isiSetelahKlaim = await page.content();
  catat('klaim ruko diterima', /Rukomu sudah berdiri/.test(isiSetelahKlaim));

  const dataDesa = await (await api.request.get(BASE + '/peta3d/data-desa.php')).json();
  const ruko = dataDesa.bangunan.find((b) => b.nama === namaUsaha);
  catat('ruko muncul di data-desa.php sebagai jenis ruko', !!ruko && ruko.jenis === 'ruko',
    ruko ? ruko.jenis : 'tidak ada');
  catat('jumlah ruko bertambah satu', dataDesa.bangunan.filter((b) => b.jenis === 'ruko').length === bangunanAwal + 1);
  catat('nama usaha, cerita, dan tautan tersimpan apa adanya',
    ruko && ruko.komentar === cerita && ruko.tautan === tautanBaik);
  catat('gang ruko dari kartu', ruko && ruko.gang === 'Gang Kuliner', ruko && ruko.gang);
  catat('ruko diberi kavling', ruko && Number.isInteger(ruko.kavling));

  // ---------------------------------------------------------------------------
  // 3. Klaim kedua ditolak, dan tidak menambah bangunan
  // ---------------------------------------------------------------------------
  await page.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  catat('halaman menampilkan ruko milik warga, bukan formulir kedua',
    await page.locator('#formRuko').count() === 0 && /Rukomu sudah berdiri/.test(await page.content()));

  const klaimGanda = php(
    'require "db.php"; $p = db(); $sebelum = $p->query("SELECT COUNT(*) FROM bangunan WHERE jenis = \'ruko\'")->fetchColumn(); $hasil = klaim_ruko($p, $argv[1], $argv[2], "Ruko Kedua", "", "https://contoh.id", "Gang Kuliner", 12); $sesudah = $p->query("SELECT COUNT(*) FROM bangunan WHERE jenis = \'ruko\'")->fetchColumn(); echo json_encode(["hasil" => $hasil["galat"] ?? null, "tambah" => (int) $sesudah - (int) $sebelum]);',
    warga.nomor, warga.kunci
  );
  const ganda = JSON.parse(klaimGanda);
  catat('klaim ganda ditolak dengan galat punya', ganda.hasil === 'punya', klaimGanda);
  catat('klaim ganda tidak menambah bangunan', ganda.tambah === 0, String(ganda.tambah));

  // ---------------------------------------------------------------------------
  // 4. Tautan tidak sah dan hijack gang ditolak fungsi inti
  // ---------------------------------------------------------------------------
  const warga2 = await buatWarga(browser, 'Uji Ruko B ' + cap, 'Gang Jasa');
  const cobaTautan = (tautan, gang) => JSON.parse(php(
    'require "db.php"; echo json_encode(klaim_ruko(db(), $argv[1], $argv[2], "Toko Uji Tautan", "", $argv[3], $argv[4], 12));',
    warga2.nomor, warga2.kunci, tautan, gang
  ));
  for (const [label, tautan] of [
    ['javascript:', 'javascript:alert(1)'],
    ['data:', 'data:text/html,<script>alert(1)</script>'],
    ['http:', 'http://toko.example.com'],
    ['tanpa skema', 'toko.example.com'],
    ['berisi sandi', 'https://user:rahasia@toko.example.com'],
  ]) {
    catat('tautan ' + label + ' ditolak', cobaTautan(tautan, 'Gang Jasa').galat === 'tautan');
  }
  catat('gang palsu ditolak', cobaTautan(tautanBaik, 'Gang Dagang').galat === 'gang');

  // cerita maksimal 280 karakter
  const ceritaPanjang = 'x'.repeat(281);
  const tolakCerita = JSON.parse(php(
    'require "db.php"; echo json_encode(klaim_ruko(db(), $argv[1], $argv[2], "Toko Uji Cerita", $argv[3], $argv[4], "Gang Jasa", 12));',
    warga2.nomor, warga2.kunci, ceritaPanjang, tautanBaik
  ));
  catat('cerita lebih dari 280 karakter ditolak', tolakCerita.galat === 'cerita');

  // Cerita berisi HTML harus disimpan sebagai teks, bukan dieksekusi.
  const ceritaHtml = '<img src=x onerror=alert(1)>Buka setiap hari';
  const klaimHtml = JSON.parse(php(
    'require "db.php"; echo json_encode(klaim_ruko(db(), $argv[1], $argv[2], "Toko Uji Html", $argv[3], $argv[4], "Gang Jasa", 12));',
    warga2.nomor, warga2.kunci, ceritaHtml, tautanBaik
  ));
  catat('cerita berisi HTML diterima sebagai teks', klaimHtml.ok === true, JSON.stringify(klaimHtml.galat || ''));

  // ---------------------------------------------------------------------------
  // 5. Peta 2D: popover ruko menampilkan cerita sebagai teks dan tautan aman
  // ---------------------------------------------------------------------------
  const p2 = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await p2.goto(BASE + '/peta2d.php', { waitUntil: 'networkidle' });
  const elRuko = p2.locator(`.bangunan-svg[data-nama="${namaUsaha}"]`);
  catat('peta 2D memuat ruko', await elRuko.count() === 1);
  // Popover peta 2D dibuka dari keyboard (Enter) pada elemen bangunan,
  // jalur yang sama dengan ketukan jari setelah seret.
  await p2.locator(`.bangunan-svg[data-nama="${namaUsaha}"]`).focus();
  await p2.keyboard.press('Enter');
  await p2.waitForTimeout(300);
  catat('peta 2D: popover ruko terbuka', await p2.locator('#petaPopover').isVisible());
  catat('peta 2D: nama usaha tampil', (await p2.locator('#popNama').innerText()) === namaUsaha);
  catat('peta 2D: cerita tampil sebagai teks', (await p2.locator('#popKomentar').innerText()) === cerita);
  catat('peta 2D: tidak ada HTML tersisip di popover',
    await p2.locator('#petaPopover img').count() === 0);
  catat('peta 2D: tombol Kunjungi usaha menuju tautan https',
    (await p2.locator('#popTautan').getAttribute('href')) === tautanBaik &&
    (await p2.locator('#popTautan').getAttribute('rel') || '').includes('noopener'));

  // Popover ruko yang ceritanya berisi HTML tetap teks biasa.
  const elRukoHtml = p2.locator('.bangunan-svg[data-nama="Toko Uji Html"]');
  if (await elRukoHtml.count()) {
    await elRukoHtml.focus();
    await p2.keyboard.press('Enter');
    await p2.waitForTimeout(300);
    catat('peta 2D: cerita berisi HTML tampil sebagai teks',
      (await p2.locator('#popKomentar').innerText()).includes('<img'),
      await p2.locator('#popKomentar').innerText());
  }
  await p2.close();

  // ---------------------------------------------------------------------------
  // 6. Peta 3D: popover ruko dan tombol Kunjungi usaha
  // ---------------------------------------------------------------------------
  const p3 = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const galatJs = [];
  p3.on('pageerror', (e) => galatJs.push(e.message));
  await p3.goto(BASE + '/peta.php', { waitUntil: 'networkidle' });
  await p3.waitForTimeout(1200);
  // Tombol daftar bangunan menyorot ruko, lalu popover-nya dibuka lewat data
  // yang sama dengan dunia 3D (klik objek 3D butuh raycast, bukan DOM).
  const pop3d = await p3.evaluate((nama) => {
    const baris = Array.from(document.querySelectorAll('.daftar-bangunan-tombol'))
      .find((b) => b.textContent.includes(nama));
    if (!baris) return null;
    baris.click();
    return { kavling: baris.getAttribute('data-kavling') };
  }, namaUsaha);
  catat('peta 3D: ruko ada di daftar bangunan panel', pop3d !== null);
  await p3.waitForTimeout(600);
  catat('peta 3D: tidak ada galat JavaScript', galatJs.length === 0, galatJs.join(' | '));
  catat('peta 3D: tombol Kunjungi usaha ada di markup popover',
    await p3.locator('#popKunjungi').count() === 1);
  await p3.close();

  // ---------------------------------------------------------------------------
  // 6b. Jalan masuk dari peta: pil Ruko di tab bar, tombol di kontrol 3D,
  //     dan tautan di panel Bangunan
  // ---------------------------------------------------------------------------
  {
    const pjalan = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await pjalan.goto(BASE + '/peta.php', { waitUntil: 'networkidle' });
    catat('peta 3D: pil Ruko ada di tab bar',
      await pjalan.locator('#tabBar a.tab-pil[href$="ruko.php"]').count() === 1);
    catat('peta 3D: tombol Ruko ada di tumpukan kontrol',
      await pjalan.locator('.kontrol a[href$="ruko.php"]').count() === 1);
    await pjalan.evaluate(() => window.__tabBar && window.__tabBar.buka('bangunan', null));
    await pjalan.waitForTimeout(400);
    catat('peta 3D: panel Bangunan menautkan halaman Ruko',
      await pjalan.locator('[data-isi="bangunan"] a[href$="ruko.php"]').count() === 1);
    await pjalan.close();

    const p2d = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await p2d.goto(BASE + '/peta2d.php', { waitUntil: 'networkidle' });
    catat('peta 2D: pil Ruko ada di tab bar',
      await p2d.locator('#tabBar a.tab-pil[href$="ruko.php"]').count() === 1);
    await p2d.close();
  }

  // ---------------------------------------------------------------------------
  // 7. Lepas ruko: hilang dari peta, baris dan cerita tetap tersimpan
  // ---------------------------------------------------------------------------
  await page.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  await Promise.all([
    page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('ruko.php')),
    page.locator('.ruko-lepas button[type="submit"]').click(),
  ]);
  await page.waitForLoadState('networkidle');
  catat('lepas ruko ditampilkan', /Rukomu sudah dilepas/.test(await page.content()));
  const setelahLepas = await (await api.request.get(BASE + '/peta3d/data-desa.php')).json();
  catat('ruko yang dilepas hilang dari peta', !setelahLepas.bangunan.some((b) => b.nama === namaUsaha));
  const barisTersimpan = JSON.parse(php(
    'require "db.php"; $p = db(); $s = $p->prepare("SELECT COUNT(*) FROM bangunan WHERE nama = ? AND jenis = \'ruko\'"); $s->execute([$argv[1]]); echo json_encode(["jumlah" => (int) $s->fetchColumn()]);',
    namaUsaha
  ));
  catat('baris ruko tidak dihapus, hanya dinonaktifkan', barisTersimpan.jumlah === 1);

  await page.goto(BASE + '/ruko.php', { waitUntil: 'networkidle' });
  catat('setelah lepas, warga bisa klaim ruko lagi', await page.locator('#formRuko').count() === 1);
  await page.close();

  // Bersihkan ruko uji yang dibuat uji lain supaya peta lokal tidak penuh
  // bangunan percobaan. Hanya menyentuh baris dengan nama uji ini.
  php(
    'require "db.php"; $p = db(); $s = $p->prepare("DELETE FROM bangunan WHERE nama IN (?, ?)"); $s->execute([$argv[1], "Toko Uji Html"]);',
    namaUsaha
  );

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
