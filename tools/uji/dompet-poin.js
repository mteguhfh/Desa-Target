const { chromium } = require('playwright-core');

// Uji Fitur 1: dompet poin warga dan misi per warga.
//
// Yang dijaga:
//   - klaim harian hanya sekali per hari (kedua ditolak 409), besoknya diterima
//   - misi menambah saldo tepat sebesar poin misi itu, sekali per warga per hari
//   - dua warga tidak tercampur saldo maupun status misinya
//   - nomor tanpa kunci, kunci salah, dan kunci warga lain ditolak
//   - injeksi pada nomor, kunci, dan kode tidak mengubah data
//   - panel Misi dan chip saldo berjalan di peta 3D dan 2D
//
// Nomor + kunci warga diambil dari kartu yang baru dibuat (ktp.php lewat
// formulir biasa), jadi uji tidak bergantung pada isi basis data tertentu.
//
// Situs harus sudah berjalan di localhost:8100.

const BASE = process.env.BASE_UJI || 'http://localhost:8100';
// Akar proyek untuk memanggil php -r (lihat uji klaim besok). Bisa diatur
// lewat CWD_UJI bila berkas uji dijalankan dari salinan di luar repo.
const CWD = process.env.CWD_UJI || '/home/mteguhfh/Documents/Kejartarget/Desa_kejartarget';
const CHROME = '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';
const hasil = [];
const catat = (nama, lulus, detail = '') => {
  hasil.push({ nama, lulus });
  console.log((lulus ? 'LULUS ' : 'GAGAL ') + nama + (detail ? ' — ' + detail : ''));
};

// Tanggal besok menurut jam server (bukan jam mesin uji).
async function besok(api) {
  const r = await api.request.get(BASE + '/poin');
  const waktu = new Date(r.headers()['date']);
  waktu.setDate(waktu.getDate() + 1);
  return waktu.toISOString().slice(0, 10);
}

async function buatWarga(browser, nama, gang) {
  const page = await browser.newPage();
  // Kunjungan pertama membangun sesi (session_start() menulis cookie-nya
  // belakangan), jadi formulir dimuat sekali lalu sekali lagi supaya token
  // yang dipakai milik sesi yang sama.
  await page.goto(BASE + '/ktp', { waitUntil: 'networkidle' });
  await page.goto(BASE + '/ktp', { waitUntil: 'networkidle' });
  const token = await page.locator('input[name="token"]').inputValue();
  await page.locator('#nama').fill(nama);
  await page.locator('[name="izin_penerbitan"]').check();
  await page.locator('#gang').selectOption(gang);
  const [jawaban] = await Promise.all([
    page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('ktp')),
    page.locator('form[action="ktp"] button[type="submit"]').click(),
  ]);
  const lokasi = jawaban.headers()['location'] || '';
  const cocok = lokasi.match(/kartu=(\d+)/);
  if (!cocok) throw new Error('KTP tidak terbit untuk ' + nama + ': ' + lokasi);
  await page.goto(BASE + '/ktp?kartu=' + cocok[1], { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#ktpIdentitas', { state: 'attached' });
  const identitas = await page.evaluate(() => ({
    nomor: document.getElementById('ktpIdentitas').getAttribute('data-nomor'),
    kunci: document.getElementById('ktpIdentitas').getAttribute('data-kunci'),
    saldo: (document.querySelector('.ktp-poin') || {}).textContent || '',
  }));
  // localStorage ikut terisi saat halaman kartu dibuka.
  identitas.diBrowser = await page.evaluate(() => [
    localStorage.getItem('dt_nomor'), localStorage.getItem('dt_kunci'),
  ]);
  await page.close();
  return identitas;
}

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });
  const api = await browser.newPage();
  // Penanda unik yang lolos validasi nama KTP (huruf saja, tanpa angka).
  const huruf = 'abcdefghijklmnopqrstuvwxyz';
  let sisa = Number(String(Date.now()).slice(-4));
  let cap = '';
  for (let i = 0; i < 4; i++) { cap += huruf[sisa % 26]; sisa = Math.floor(sisa / 26); }
  cap = ' Dompet ' + cap + ' ';
  const klaimA = (nomor, kunci) => api.request.post(BASE + '/klaim', { form: { nomor, kunci } });
  const poin = async (nomor, kunci) => {
    const r = await api.request.get(BASE + '/poin?nomor=' + encodeURIComponent(nomor) + '&kunci=' + encodeURIComponent(kunci));
    return { status: r.status(), isi: await r.json().catch(() => null) };
  };
  const misi = (kode, nomor, kunci) => api.request.post(BASE + '/misi', { form: { kode, nomor, kunci } });

  // ---------------------------------------------------------------------------
  // 1. Kartu warga menerbitkan nomor dan kunci rahasia
  // ---------------------------------------------------------------------------
  const wargaA = await buatWarga(browser, 'Uji Dompet ' + cap, 'Gang Dagang');
  const wargaB = await buatWarga(browser, 'Uji Dompet B ' + cap, 'Gang Kuliner');
  catat('kartu menerbitkan nomor KTP sah', /^DT-\d{4,9}$/.test(wargaA.nomor), wargaA.nomor);
  catat('kartu menerbitkan kunci rahasia 32 hex', /^[0-9a-f]{32}$/.test(wargaA.kunci));
  catat('dua warga berbeda punya nomor dan kunci berbeda',
    wargaA.nomor !== wargaB.nomor && wargaA.kunci !== wargaB.kunci);
  catat('kartu menyimpan nomor + kunci ke localStorage',
    wargaA.diBrowser[0] === wargaA.nomor && wargaA.diBrowser[1] === wargaA.kunci);
  catat('kartu baru bersaldo 0 poin', /0 poin/.test(wargaA.saldo), wargaA.saldo.trim());

  // ---------------------------------------------------------------------------
  // 2. Klaim harian: sekali per hari, besoknya diterima
  // ---------------------------------------------------------------------------
  const awalan = await poin(wargaA.nomor, wargaA.kunci);
  catat('poin dengan kunci membuka dompet', awalan.status === 200 && awalan.isi.saldo === 0,
    'saldo ' + (awalan.isi && awalan.isi.saldo));

  const saldoAwalA = awalan.isi.saldo;
  const klaim1 = await klaimA(wargaA.nomor, wargaA.kunci);
  const isiKlaim1 = await klaim1.json();
  catat('klaim harian pertama diterima', klaim1.status() === 200 && isiKlaim1.saldo === saldoAwalA + 10,
    JSON.stringify(isiKlaim1));

  const klaim2 = await klaimA(wargaA.nomor, wargaA.kunci);
  catat('klaim harian kedua di hari yang sama ditolak 409', klaim2.status() === 409);

  const saldoSetelahGanda = await poin(wargaA.nomor, wargaA.kunci);
  catat('klaim ganda tidak menambah saldo', saldoSetelahGanda.isi.saldo === saldoAwalA + 10,
    'saldo ' + saldoSetelahGanda.isi.saldo);
  catat('poin melaporkan klaim hari ini', saldoSetelahGanda.isi.klaim === true);

  const saldoB = await poin(wargaB.nomor, wargaB.kunci);
  catat('klaim warga A tidak menambah saldo warga B',
    saldoB.isi.klaim === false && !saldoB.isi.riwayat.some((r) => r.tanggal === saldoSetelahGanda.isi.riwayat[0].tanggal && r.sumber === 'klaim-harian'),
    'saldo B ' + saldoB.isi.saldo);

  catat('riwayat poin tercatat sumber klaim-harian',
    Array.isArray(saldoSetelahGanda.isi.riwayat) && saldoSetelahGanda.isi.riwayat.length >= 1 &&
    saldoSetelahGanda.isi.riwayat[0].sumber === 'klaim-harian' &&
    saldoSetelahGanda.isi.riwayat[0].delta === 10);

  // Besoknya diterima: tanggal klaim dimajukan lewat helper PHP (server yang
  // sama rumusnya dengan klaim_harian()). Kalau php tidak ada, dilewati.
  const tanggalBesok = await besok(api);
  const { execFileSync } = require('child_process');
  let hasilBesok = null;
  try {
    const keluaran = execFileSync('php', ['-r',
      'require "db.php"; echo json_encode(klaim_harian(db(), $argv[1], $argv[2]));',
      wargaA.nomor, tanggalBesok], { cwd: CWD, encoding: 'utf8' });
    hasilBesok = JSON.parse(keluaran);
  } catch (e) {
    hasilBesok = null;
  }
  if (hasilBesok) {
    catat('klaim di hari berikutnya diterima', hasilBesok.saldo === saldoSetelahGanda.isi.saldo + 10,
      JSON.stringify(hasilBesok));
  } else {
    catat('klaim di hari berikutnya diterima', false, 'php lokal tidak bisa dijalankan dari mesin uji');
  }

  // ---------------------------------------------------------------------------
  // 3. Misi per warga: poin tepat sekali per warga per hari
  // ---------------------------------------------------------------------------
  const saldoSebelumMisiA = (await poin(wargaA.nomor, wargaA.kunci)).isi.saldo;
  const misiA1 = await misi('keliling-desa', wargaA.nomor, wargaA.kunci);
  const isiMisiA1 = await misiA1.json();
  catat('misi keliling-desa untuk warga A diterima', misiA1.status() === 200, String(misiA1.status()));
  catat('misi menambah poin tepat sebesar poin misi', isiMisiA1.poin === 5 &&
    isiMisiA1.saldo === saldoSebelumMisiA + 5, 'poin ' + isiMisiA1.poin + ', saldo ' + isiMisiA1.saldo);

  const misiA2 = await misi('keliling-desa', wargaA.nomor, wargaA.kunci);
  const isiMisiA2 = await misiA2.json();
  catat('misi yang sama diulang tidak menambah poin', isiMisiA2.poin === 0 && isiMisiA2.saldo === saldoSebelumMisiA + 5,
    'poin ' + isiMisiA2.poin + ', saldo ' + isiMisiA2.saldo);

  const statusMisiA = isiMisiA2.daftar.find((m) => m.kode === 'keliling-desa');
  catat('misi tercatat selesai untuk warga A', statusMisiA && statusMisiA.selesai === true);

  // Sebelum warga B menandai apa pun, misi yang sama harus belum selesai di
  // dompetnya. Status desa (jumlah bangunan dan warga) memang berlaku untuk
  // semua, tapi keliling-desa hanya milik yang menandainya.
  const dompetBSebelum = await poin(wargaB.nomor, wargaB.kunci);
  catat('warga B punya status misi sendiri, belum selesai',
    dompetBSebelum.isi.daftar.find((m) => m.kode === 'keliling-desa').selesai === false);

  const saldoSebelumMisiB = dompetBSebelum.isi.saldo;
  const misiB = await misi('keliling-desa', wargaB.nomor, wargaB.kunci);
  const isiMisiB = await misiB.json();
  catat('warga B mendapat poin misinya sendiri', isiMisiB.poin === 5 && isiMisiB.saldo === saldoSebelumMisiB + 5,
    'saldo B ' + isiMisiB.saldo);

  const saldoAAkhir = await poin(wargaA.nomor, wargaA.kunci);
  catat('poin_log berisi misi dengan ref kode',
    saldoAAkhir.isi.riwayat.some((r) => r.sumber === 'misi' && r.ref === 'keliling-desa'));
  catat('tidak ada selisih saldo dan riwayat',
    saldoAAkhir.isi.saldo === saldoAAkhir.isi.riwayat.reduce((n, r) => n + r.delta, 0),
    'saldo ' + saldoAAkhir.isi.saldo);

  // ---------------------------------------------------------------------------
  // 4. Kunci wajib: nomor saja, kunci salah, atau kunci warga lain ditolak
  // ---------------------------------------------------------------------------
  const tanpaKunci = await poin(wargaA.nomor, '');
  catat('poin tanpa kunci ditolak 403', tanpaKunci.status === 403);
  const kunciSalah = await poin(wargaA.nomor, 'f'.repeat(32));
  catat('poin dengan kunci salah ditolak 403', kunciSalah.status === 403);
  const kunciTetangga = await poin(wargaA.nomor, wargaB.kunci);
  catat('poin dengan kunci warga lain ditolak 403', kunciTetangga.status === 403);
  const klaimTetangga = await klaimA(wargaA.nomor, wargaB.kunci);
  catat('klaim harian dengan kunci warga lain ditolak 403', klaimTetangga.status() === 403);
  const misiTetangga = await misi('keliling-desa', wargaB.nomor, wargaA.kunci);
  catat('misi dengan kunci warga lain ditolak 403', misiTetangga.status() === 403);

  // ---------------------------------------------------------------------------
  // 5. Injeksi pada nomor, kunci, dan kode
  // ---------------------------------------------------------------------------
  const injeksiNomor = wargaA.nomor + "' OR '1'='1";
  const injeksiKode = "keliling-desa' OR 1=1 --";
  const injNomorKlaim = await klaimA(injeksiNomor, wargaA.kunci);
  catat('injeksi pada nomor klaim ditolak', injNomorKlaim.status() === 403);
  const injNomorPoin = await poin(injeksiNomor, wargaA.kunci);
  catat('injeksi pada nomor poin tidak membocorkan data', injNomorPoin.status === 403);
  const injKunci = await poin(wargaA.nomor, "' OR 1=1 --");
  catat('injeksi pada kunci ditolak', injKunci.status === 403);
  const injKode = await misi(injeksiKode, wargaA.nomor, wargaA.kunci);
  catat('injeksi pada kode misi ditolak 400', injKode.status() === 400);
  const kodeNgawur = await misi('misi-yang-tidak-ada', wargaA.nomor, wargaA.kunci);
  catat('kode misi tidak dikenal ditolak 404', kodeNgawur.status() === 404);

  const saldoSetelahInjeksi = await poin(wargaA.nomor, wargaA.kunci);
  catat('injeksi tidak mengubah saldo', saldoSetelahInjeksi.isi.saldo === saldoAAkhir.isi.saldo,
    saldoSetelahInjeksi.isi.saldo + ' vs ' + saldoAAkhir.isi.saldo);
  catat('injeksi tidak menambah baris riwayat',
    saldoSetelahInjeksi.isi.riwayat.length === saldoAAkhir.isi.riwayat.length);

  const tamuTanpaKtp = await misi('keliling-desa', '', '');
  catat('misi tanpa identitas (pengunjung) tetap hanya membaca', tamuTanpaKtp.status() === 200 &&
    typeof (await tamuTanpaKtp.json()).ringkasan === 'object');

  // ---------------------------------------------------------------------------
  // 6. UI: chip saldo dan panel Misi per warga di peta 3D dan 2D
  // ---------------------------------------------------------------------------
  for (const halaman of ['peta', 'peta2d']) {
    // Pengunjung tanpa kartu: tidak ada chip saldo, panel menawarkan KTP.
    const tamu = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await tamu.goto(BASE + '/' + halaman, { waitUntil: 'networkidle' });
    await tamu.waitForTimeout(400);
    catat(halaman + ': chip saldo tidak tampil tanpa kartu', await tamu.locator('#chipPoin').isHidden());
    await tamu.close();

    // Warga A: chip saldo tampil, panel Misi memuat saldo dan klub misinya.
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await page.addInitScript(([nomor, kunci]) => {
      localStorage.setItem('dt_nomor', nomor);
      localStorage.setItem('dt_kunci', kunci);
    }, [wargaA.nomor, wargaA.kunci]);
    await page.goto(BASE + '/' + halaman, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);

    if (halaman === 'peta') {
      catat(halaman + ': chip saldo tampil untuk warga', await page.locator('#chipPoin').isVisible());
    }
    await page.evaluate(() => window.__tabBar && window.__tabBar.buka('misi', null));
    await page.waitForTimeout(500);
    const dompetTampil = await page.locator('#misiDompet').isVisible();
    catat(halaman + ': dompet poin tampil di panel Misi', dompetTampil);
    const saldoServer = (await poin(wargaA.nomor, wargaA.kunci)).isi.saldo;
    await page.waitForFunction((n) => document.getElementById('misiSaldo').textContent === String(n),
      saldoServer, { timeout: 5000 }).catch(() => {});
    const saldoPanel = (await page.locator('#misiSaldo').innerText()).trim();
    catat(halaman + ': saldo panel sama dengan saldo server', saldoPanel === String(saldoServer),
      'panel ' + saldoPanel + ', server ' + saldoServer);
    catat(halaman + ': tombol klaim harian nonaktif setelah klaim',
      await page.locator('#tombolKlaimHarian').isDisabled());
    const barisSelesai = await page.locator('.misi-baris[data-kode="keliling-desa"].selesai').count();
    catat(halaman + ': misi warga tampil selesai di panel', barisSelesai === 1);
    await page.close();
  }

  // Klaim lewat tombol panel untuk warga B (belum klaim).
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await page.addInitScript(([nomor, kunci]) => {
      localStorage.setItem('dt_nomor', nomor);
      localStorage.setItem('dt_kunci', kunci);
    }, [wargaB.nomor, wargaB.kunci]);
    await page.goto(BASE + '/peta2d', { waitUntil: 'networkidle' });
    await page.evaluate(() => window.__tabBar && window.__tabBar.buka('misi', null));
    await page.waitForTimeout(500);
    const klaimTombol = page.locator('#tombolKlaimHarian');
    await page.waitForFunction(() => !document.getElementById('tombolKlaimHarian').disabled, null, { timeout: 5000 }).catch(() => {});
    const saldoBSebelum = (await poin(wargaB.nomor, wargaB.kunci)).isi.saldo;
    await klaimTombol.click();
    await page.waitForTimeout(900);
    const saldoPanelB = (await page.locator('#misiSaldo').innerText()).trim();
    catat('tombol klaim harian menambah saldo warga B', saldoPanelB === String(saldoBSebelum + 10),
      'panel ' + saldoPanelB + ', sebelum ' + saldoBSebelum);
    catat('tombol klaim harian nonaktif setelah diklik', await klaimTombol.isDisabled());
    await page.close();
  }

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
