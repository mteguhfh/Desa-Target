const { chromium } = require('playwright-core');

// Uji Fitur 4: Pasar Malam dan CFD, lapak gratis.
//
// Yang dijaga:
//   - jadwal: Pasar Malam Sabtu 18.00-22.00, CFD Minggu 06.00-10.00, dan di
//     luar jam itu tidah ada acara
//   - lapak hanya bisa diambil saat acara berlangsung (di luar jam: galat
//     tutup), satu lapak per warga per acara per tanggal, batas 12 lapak
//   - nama usaha dan tautan dari ruko; tanpa ruko memakai isian formulir
//     ringkas; tautan wajib https; nama usaha selalu teks biasa
//   - tenda hanya muncul di peta 3D saat acara berlangsung, dan data-desa.php
//     mengirim acara = null di luar jam acara
//   - panel Pasar bisa dibuka dari peta 2D dan dari chip suasana di peta 3D
//   - lapak gratis: poin warga tidak berkurang
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

function acara(kapan) {
  return php('require "db.php"; echo json_encode(acara_aktif($argv[1]));', kapan).trim();
}

async function buatWarga(browser, nama, gang) {
  const page = await browser.newPage();
  await page.goto(BASE + '/ktp', { waitUntil: 'networkidle' });
  await page.goto(BASE + '/ktp', { waitUntil: 'networkidle' });
  await page.locator('#nama').fill(nama);
  await page.locator('#gang').selectOption(gang);
  await page.locator('[name="izin_penerbitan"]').check();
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
  }));
  await page.close();
  return identitas;
}

// Bersihkan semua lapak uji supaya batas 12 dan klaim ganda bisa diuji dari
// keadaan kosong. Hanya tabel lapak yang disentuh.
function bersihkanLapak() {
  php('require "db.php"; db()->exec("DELETE FROM lapak"); echo "ok";');
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

  // ---------------------------------------------------------------------------
  // 1. Jadwal acara
  // ---------------------------------------------------------------------------
  catat('Sabtu 19.00 Pasar Malam aktif', acara('2026-11-07 19:00') === '"pasar-malam"', acara('2026-11-07 19:00'));
  catat('Sabtu 12.00 tidak ada acara', acara('2026-11-07 12:00') === 'null');
  catat('Sabtu 22.00 sudah tutup', acara('2026-11-07 22:00') === 'null');
  catat('Minggu 07.00 CFD aktif', acara('2026-11-08 07:00') === '"cfd"');
  catat('Minggu 10.00 CFD sudah tutup', acara('2026-11-08 10:00') === 'null');
  catat('Senin 07.00 tidak ada acara', acara('2026-11-09 07:00') === 'null');
  const berikut = JSON.parse(php('require "db.php"; echo json_encode(acara_berikutnya("2026-11-09 07:00"));'));
  catat('acara berikutnya dari Senin adalah Pasar Malam Sabtu', berikut.acara === 'pasar-malam' && berikut.tanggal === '2026-11-14', JSON.stringify(berikut));

  // ---------------------------------------------------------------------------
  // 2. Halaman pasar.php: jadwal, hitung mundur, lapak
  // ---------------------------------------------------------------------------
  const halaman = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await halaman.goto(BASE + '/pasar', { waitUntil: 'networkidle' });
  const isiHalaman = await halaman.locator('.pasar-halaman').innerText();
  catat('halaman Pasar menampilkan Pasar Malam Sabtu 18.00', /Pasar Malam[\s\S]*Sabtu[\s\S]*18:00/.test(isiHalaman));
  catat('halaman Pasar menampilkan CFD Minggu 06.00', /CFD[\s\S]*Minggu[\s\S]*06:00/.test(isiHalaman));
  catat('halaman Pasar menyebut lapak gratis dan tanpa pembayaran', /[Gg]ratis/.test(isiHalaman) && /pembayaran/.test(isiHalaman));
  catat('halaman Pasar menautkan Buka Ruko', await halaman.locator('a[href$="ruko"]').count() > 0);
  await halaman.close();

  // ---------------------------------------------------------------------------
  // 3. Warga tanpa ruko mengklaim lewat API: butuh nama usaha dan tautan
  // ---------------------------------------------------------------------------
  bersihkanLapak();
  const warga = await buatWarga(browser, 'Uji Pasar ' + cap, 'Gang Dagang');
  const poinAwal = Number(php('require "db.php"; echo saldo_poin(db(), $argv[1]);', warga.nomor));

  const kirim = async (data) => {
    const res = await api.request.post(BASE + '/lapak', { form: data });
    return { status: res.status(), data: await res.json().catch(() => ({})) };
  };
  const dasar = { acara: 'cfd', nomor: warga.nomor, kunci: warga.kunci, nama_usaha: 'Lapak Uji ' + cap, tautan: 'https://wa.me/6281200000000' };

  // Tanggal sekarang dari server: acara yang aktif hari ini ditentukan jam
  // server, jadi uji API memakai acara_aktif() hari ini.
  const sekarang = php('require "db.php"; echo date("Y-m-d H:i");').trim();
  const aktifSekarang = JSON.parse(acara(sekarang));

  if (aktifSekarang === null) {
    const tutup = await kirim(dasar);
    catat('di luar jam acara klaim ditolak 409', tutup.status === 409 && /berlangsung/.test(tutup.data.galat || ''), tutup.status + ' ' + (tutup.data.galat || ''));
    catat('lapak tidak bertambah di luar jam acara', JSON.parse(php('require "db.php"; echo json_encode((int) db()->query("SELECT COUNT(*) FROM lapak")->fetchColumn());')) === 0);
  }

  const acaraUji = aktifSekarang || 'cfd';
  const dasarAktif = { ...dasar, acara: acaraUji };

  const kunciSalah = await kirim({ ...dasarAktif, kunci: 'salah' });
  const nomorAsing = await kirim({ ...dasarAktif, nomor: 'DT-999999' });
  catat('nomor asing dan kunci salah dijawab sama (403)', kunciSalah.status === 403 && nomorAsing.status === 403 &&
    kunciSalah.data.galat === nomorAsing.data.galat, kunciSalah.status + ' / ' + nomorAsing.status);

  catat('acara tidak dikenal ditolak 400', (await kirim({ ...dasarAktif, acara: 'nonton-bola' })).status === 400);

  // Validasi nama/tautan dan klaim diuji langsung di fungsi inti dengan jam
  // acara tetap (Sabtu 19.00). Dengan begitu hasil uji tidak berubah hanya
  // karena uji dijalankan di luar jam Pasar Malam atau CFD.
  const klaimLangsung = (namaUsaha, tautan) => JSON.parse(php(
    'require "db.php"; echo json_encode(klaim_lapak(db(), $argv[1], $argv[2], "pasar-malam", "2026-11-07 19:00", $argv[3], $argv[4]));',
    warga.nomor, warga.kunci, namaUsaha, tautan
  ));

  bersihkanLapak();
  const tautanBuruk = klaimLangsung('Lapak Uji ' + cap, 'javascript:alert(1)');
  catat('tautan javascript: ditolak', tautanBuruk.galat === 'tautan', String(tautanBuruk.galat));
  const namaPendek = klaimLangsung('ab', 'https://wa.me/6281200000000');
  catat('nama usaha sependek 2 huruf ditolak', namaPendek.galat === 'nama', String(namaPendek.galat));

  const klaim = klaimLangsung('<img src=x onerror=alert(1)>', 'https://wa.me/6281200000000');
  catat('klaim saat acara berlangsung diterima', klaim.ok === true, String(klaim.galat));
  const hasilKlaim = klaim.lapak || [];
  catat('lapak tersimpan dengan nama usaha sebagai teks',
    hasilKlaim.some((l) => l.namaUsaha === '<img src=x onerror=alert(1)>'), JSON.stringify(hasilKlaim));
  catat('lapak pertama dapat tenda 1', hasilKlaim[0] && hasilKlaim[0].kavling === 0, JSON.stringify(hasilKlaim[0] || null));
  const ganda = klaimLangsung('<img src=x onerror=alert(1)>', 'https://wa.me/6281200000000');
  catat('klaim ganda di acara sama ditolak', ganda.galat === 'sudah', String(ganda.galat));
  const poinAkhir = Number(php('require "db.php"; echo saldo_poin(db(), $argv[1]);', warga.nomor));
  catat('lapak gratis: poin warga tidak berkurang', poinAkhir === poinAwal, poinAwal + ' -> ' + poinAkhir);

  // Batas 12 lapak diuji langsung di fungsi inti untuk jam acara.
  bersihkanLapak();
  const ujiBatas = JSON.parse(php(`
    require "db.php";
    $p = db();
    $ids = $p->query("SELECT id, kunci FROM warga ORDER BY id DESC LIMIT 13")->fetchAll();
    $hasil = [];
    foreach ($ids as $w) {
      $r = klaim_lapak($p, nomor_warga((int) $w["id"]), $w["kunci"], "pasar-malam", "2026-11-07 19:00", "Lapak Uji", "https://wa.me/6281");
      $hasil[] = $r["galat"] ?? "ok";
    }
    $p->exec("DELETE FROM lapak");
    echo json_encode($hasil);
  `));
  catat('dua belas lapak pertama diterima', ujiBatas.slice(0, 12).every((h) => h === 'ok'), ujiBatas.slice(0, 12).join(','));
  catat('lapak ke-13 ditolak karena penuh', ujiBatas[12] === 'penuh', String(ujiBatas[12]));

  // ---------------------------------------------------------------------------
  // 4. Tenda di peta 3D hanya saat acara berlangsung
  // ---------------------------------------------------------------------------
  bersihkanLapak();
  const jamSebelum = JSON.parse(acara(php('require "db.php"; echo date("Y-m-d H:i");').trim()));
  const dataDesa = await (await api.request.get(BASE + '/peta3d/data-desa')).json();
  const jamSesudah = JSON.parse(acara(php('require "db.php"; echo date("Y-m-d H:i");').trim()));
  if (jamSebelum !== jamSesudah) {
    // Uji sempat melewati batas jam acara; status acara tidak bisa dinilai.
    catat('data-desa: batas jam acara terlewati, status dilewati', true);
  } else if (jamSebelum === null) {
    catat('data-desa: acara null di luar jam acara', dataDesa.acara === null, String(dataDesa.acara));
    catat('data-desa: lapak kosong di luar jam acara', Array.isArray(dataDesa.lapak) && dataDesa.lapak.length === 0);
  } else {
    catat('data-desa: acara terisi saat acara berlangsung', dataDesa.acara === jamSebelum, String(dataDesa.acara));
  }
  catat('data-desa: acara berikutnya dikirim', dataDesa.acaraBerikutnya !== null && !!dataDesa.acaraBerikutnya.tanggal);

  const peta = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  const galatJs = [];
  peta.on('pageerror', (e) => galatJs.push(e.message));
  await peta.goto(BASE + '/peta', { waitUntil: 'networkidle' });
  await peta.waitForTimeout(1000);
  catat('peta 3D tidak ada galat JavaScript', galatJs.length === 0, galatJs.join(' | '));
  const tenda = await peta.evaluate(() => ({
    jumlah: window.__desa && window.__desa.jumlahLapak3d ? window.__desa.jumlahLapak3d() : -1,
    acara: window.__desa ? window.__desa.acara : 'tidak ada',
  }));
  // Di luar acara server tidak mengirim lapak, jadi peta harus bersih. Saat
  // acara berlangsung, tenda ikut lapak yang sudah diklaim; uji ini tidak
  // mengklaim lapak dari peramban, cukup memastikan peta bisa menampilkannya
  // (bangunLapak menerima daftar kosong dan tidak melempar galat).
  if (aktifSekarang === null) {
    catat('peta 3D: tidak ada tenda di luar jam acara', tenda.jumlah === 0, String(tenda.jumlah));
  }
  const bisaGambarTenda = await peta.evaluate(() => {
    try {
      window.__desa.bangunLapak([{ nomor: 'DT-0001', namaUsaha: 'Lapak Uji', tautan: 'https://wa.me/6281', kavling: 0 }]);
      const n = window.__desa.jumlahLapak3d();
      window.__desa.bangunLapak([]);
      return { n: n, kosong: window.__desa.jumlahLapak3d() };
    } catch (e) { return { galat: String(e) }; }
  });
  catat('peta 3D: bangunLapak menggambar satu tenda lalu membersihkannya',
    bisaGambarTenda.n === 1 && bisaGambarTenda.kosong === 0, JSON.stringify(bisaGambarTenda));
  const chip = await peta.locator('#chipSuasana');
  catat('chip suasana bisa diklik ke panel Pasar', (await chip.getAttribute('data-panel')) === 'pasar');
  await peta.evaluate(() => document.getElementById('chipSuasana').click());
  await peta.waitForTimeout(600);
  catat('chip suasana membuka panel Pasar di peta 3D',
    await peta.locator('#panelBersayap [data-isi="pasar"]').isVisible());
  await peta.waitForSelector('#pasarPilih .pasar-pil', { timeout: 5000 }).catch(() => {});
  catat('panel Pasar memuat pemilih acara (2 acara)',
    await peta.locator('#pasarPilih .pasar-pil').count() === 2);
  const statusPanel = await peta.locator('#pasarStatus').innerText();
  catat('panel Pasar menampilkan status jadwal', statusPanel.length > 0, statusPanel.slice(0, 60));
  await peta.close();

  // ---------------------------------------------------------------------------
  // 5. Panel Pasar di peta 2D
  // ---------------------------------------------------------------------------
  const p2d = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await p2d.goto(BASE + '/peta2d', { waitUntil: 'networkidle' });
  await p2d.evaluate(() => {
    const btn = document.querySelector('#tabBar .tab-item[data-panel="papan-gang"]');
    if (btn) btn.click();
  });
  await p2d.waitForTimeout(400);
  await p2d.evaluate(() => window.__tabBar && window.__tabBar.buka('pasar', null));
  await p2d.waitForTimeout(600);
  catat('peta 2D: panel Pasar terbuka',
    await p2d.locator('#panelBersayap [data-isi="pasar"]').isVisible());
  await p2d.waitForSelector('#pasarPilih .pasar-pil', { timeout: 5000 }).catch(() => {});
  catat('peta 2D: pemilih acara terisi', await p2d.locator('#pasarPilih .pasar-pil').count() === 2);
  await p2d.close();

  // ---------------------------------------------------------------------------
  // Bersihkan lapak uji
  // ---------------------------------------------------------------------------
  bersihkanLapak();
  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
