const { chromium } = require('playwright-core');

// Uji Fitur 2: chat antar gang, publik, frase preset.
//
// Yang dijaga:
//   - hanya frase preset yang diterima; teks bebas ditolak di endpoint dan
//     tidak ada kolom teks di UI
//   - nama dan gang pesan selalu dari kartu warga, tidak bisa dipalsukan
//   - menulis butuh nomor + kunci; kunci warga lain ditolak
//   - menulis di kanal gang lain ditolak, membaca kanal gang lain boleh
//   - batas laju 1 pesan per 5 detik per nomor (429)
//   - kanal tidak sah ditolak, injeksi tidak mengubah apa pun
//   - polling jalan saat panel terbuka dan berhenti saat panel ditutup
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

async function buatWarga(browser, nama, gang) {
  const page = await browser.newPage();
  // Kunjungan pertama membangun sesi, kunjungan kedua memakai sesi yang sama.
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
    // Nama yang benar-benar tersimpan (form memadatkan spasi), dipakai uji
    // untuk membandingkan pesan dengan isi kartu yang tercatat di server.
    nama: (document.getElementById('ktpIdentitas').getAttribute('data-nama') || '').trim(),
    gang: (document.getElementById('ktpIdentitas').getAttribute('data-gang') || '').trim(),
  }));
  await page.close();
  return identitas;
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

  const baca = async (kanal, sejak = 0) => {
    const r = await api.request.get(BASE + '/chat.php?kanal=' + encodeURIComponent(kanal) + '&sejak=' + sejak);
    return { status: r.status(), isi: await r.json().catch(() => null) };
  };
  const kirim = async (data) => {
    const r = await api.request.post(BASE + '/chat.php', { form: data });
    return { status: r.status(), isi: await r.json().catch(() => null) };
  };

  // Batas laju dihitung dari baris terakhir pengirim. Uji memundurkan waktu
  // pesan uji sebelumnya supaya tidak saling menghalangi, tanpa menunggu.
  const { execFileSync } = require('child_process');
  const mundurkanWaktu = (nomor) => {
    try {
      execFileSync('php', ['-r',
        'require "db.php"; db()->prepare("UPDATE chat_pesan SET dibuat_pada = datetime(\'now\', \'-60 seconds\') WHERE nomor = ?")->execute([$argv[1]]);',
        nomor], { cwd: CWD, encoding: 'utf8' });
    } catch (e) { /* dilewati: batas laju tetap diuji dari pesan terakhir */ }
  };

  // ---------------------------------------------------------------------------
  // 1. Membaca tanpa kartu: kanal sah, kanal tidak sah ditolak
  // ---------------------------------------------------------------------------
  const awal = await baca('alun-alun');
  catat('pengunjung tanpa kartu bisa membaca Alun-Alun', awal.status === 200 &&
    Array.isArray(awal.isi.pesan), 'status ' + awal.status);
  catat('daftar frase preset dikirim server (8 frase)', Array.isArray(awal.isi.frase) && awal.isi.frase.length === 8,
    String(awal.isi.frase && awal.isi.frase.length));
  const gangKedu = await baca('Gang Kuliner');
  catat('kanal gang lain bisa dibaca', gangKedu.status === 200);
  for (const kanal of ['alun alun', 'Gang Dagang; DROP TABLE chat_pesan', 'gang dagang', '']) {
    const r = await baca(kanal);
    catat('kanal tidak sah ditolak 400: "' + kanal + '"', r.status === 400, String(r.status));
  }
  const metodeSalah = await api.request.put(BASE + '/chat.php', { form: { kanal: 'alun-alun' } });
  catat('metode selain GET/POST ditolak 405', metodeSalah.status() === 405);

  // ---------------------------------------------------------------------------
  // 2. Kartu warga: nama dan gang dari server, bukan dari klien
  // ---------------------------------------------------------------------------
  const wargaA = await buatWarga(browser, 'Uji Chat ' + cap, 'Gang Jasa');
  const wargaB = await buatWarga(browser, 'Uji Chat B ' + cap, 'Gang Kuliner');
  catat('dua warga uji punya identitas berbeda', wargaA.nomor !== wargaB.nomor);

  const pertamA = await kirim({
    kanal: 'alun-alun', frase: 'halo', nomor: wargaA.nomor, kunci: wargaA.kunci,
    nama: 'Nama Palsu', gang: 'Gang Dagang', teks: '<script>alert(1)</script>',
  });
  const pesanA = (pertamA.isi && pertamA.isi.pesan && pertamA.isi.pesan[0]) || {};
  catat('pesan pertama diterima 201', pertamA.status === 201, String(pertamA.status));
  catat('nama pesan diambil dari kartu, bukan kiriman klien',
    wargaA.nama !== '' && pesanA.nama === wargaA.nama,
    pesanA.nama + ' (kiriman: Nama Palsu)');
  catat('gang pesan diambil dari kartu, bukan kiriman klien',
    wargaA.gang !== '' && pesanA.gang === wargaA.gang,
    pesanA.gang + ' (kiriman: Gang Dagang)');
  catat('isi pesan adalah teks preset, teks bebas diabaikan', pesanA.teks === 'Halo, warga!', pesanA.teks);
  catat('nomor pesan adalah nomor KTP pengirim', pesanA.nomor === wargaA.nomor);

  // ---------------------------------------------------------------------------
  // 3. Batas laju: 1 pesan per 5 detik per nomor
  // ---------------------------------------------------------------------------
  const cepat = await kirim({ kanal: 'alun-alun', frase: 'pagi', nomor: wargaA.nomor, kunci: wargaA.kunci });
  catat('pesan kedua dalam 5 detik ditolak 429', cepat.status === 429, String(cepat.status));
  const daftarSetelah429 = await baca('alun-alun');
  const jumlahDariA = daftarSetelah429.isi.pesan.filter((p) => p.nomor === wargaA.nomor).length;
  catat('pesan yang ditolak batas laju tidak tersimpan', jumlahDariA === 1, String(jumlahDariA));

  mundurkanWaktu(wargaA.nomor);
  const setelahJeda = await kirim({ kanal: 'alun-alun', frase: 'pagi', nomor: wargaA.nomor, kunci: wargaA.kunci });
  catat('pesan diterima lagi setelah jeda lewat', setelahJeda.status === 201, String(setelahJeda.status));

  // ---------------------------------------------------------------------------
  // 4. Frase preset wajib; teks bebas tidak punya jalan masuk
  // ---------------------------------------------------------------------------
  const teksBebas = await kirim({ kanal: 'alun-alun', frase: 'Hai semua, ini teks bebas', nomor: wargaB.nomor, kunci: wargaB.kunci });
  catat('frase teks bebas ditolak 400', teksBebas.status === 400, String(teksBebas.status));
  const fieldTeks = await kirim({ kanal: 'alun-alun', nomor: wargaB.nomor, kunci: wargaB.kunci, teks: 'Halo semua', pesan: 'Halo semua' });
  catat('field teks tanpa frase ditolak 400', fieldTeks.status === 400, String(fieldTeks.status));
  const fraseInjeksi = await kirim({ kanal: 'alun-alun', frase: "halo' OR 1=1 --", nomor: wargaB.nomor, kunci: wargaB.kunci });
  catat('injeksi pada frase ditolak 400', fraseInjeksi.status === 400, String(fraseInjeksi.status));

  // ---------------------------------------------------------------------------
  // 5. Kunci wajib dan kanal gang sendiri
  // ---------------------------------------------------------------------------
  const tanpaKunci = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: wargaB.nomor });
  catat('menulis tanpa kunci ditolak 403', tanpaKunci.status === 403, String(tanpaKunci.status));
  const kunciSalah = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: wargaB.nomor, kunci: 'f'.repeat(32) });
  catat('menulis dengan kunci salah ditolak 403', kunciSalah.status === 403);
  const kunciTetangga = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: wargaB.nomor, kunci: wargaA.kunci });
  catat('menulis dengan kunci warga lain ditolak 403', kunciTetangga.status === 403);
  const nomorPalsu = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: 'DT-999999', kunci: 'a'.repeat(32) });
  catat('nomor tidak dikenal dan kunci salah punya jawaban sama (403)', nomorPalsu.status === 403 &&
    nomorPalsu.isi.galat === kunciSalah.isi.galat);

  // Warga A di Gang Jasa: gang lain ditolak, gangnya sendiri diterima.
  mundurkanWaktu(wargaA.nomor);
  const kanalLain = await kirim({ kanal: 'Gang Dagang', frase: 'halo', nomor: wargaA.nomor, kunci: wargaA.kunci });
  catat('menulis di kanal gang lain ditolak 403', kanalLain.status === 403, String(kanalLain.status));
  const kanalSendiri = await kirim({ kanal: 'Gang Jasa', frase: 'mampir', nomor: wargaA.nomor, kunci: wargaA.kunci });
  catat('menulis di kanal gang sendiri diterima', kanalSendiri.status === 201, String(kanalSendiri.status));
  const pesanGangA = kanalSendiri.isi && kanalSendiri.isi.pesan && kanalSendiri.isi.pesan[0];
  catat('pesan gang sendiri masuk ke kanalnya', pesanGangA && pesanGangA.kanal === 'Gang Jasa');

  // Pesan warga A di Gang Jasa tidak muncul lagi di daftar Alun-Alun terbaru.
  const kanalUmumTerakhir = await baca('alun-alun', 0);
  catat('pesan kanal gang tidak bocor ke Alun-Alun',
    !kanalUmumTerakhir.isi.pesan.some((p) => p.frase === 'mampir' && p.kanal === 'Gang Jasa'));

  // ---------------------------------------------------------------------------
  // 6. Injeksi pada kanal, nomor, dan kunci
  // ---------------------------------------------------------------------------
  const sebelumInjeksi = (await baca('alun-alun')).isi.pesan.length;
  const injKanal = await kirim({ kanal: "alun-alun' OR '1'='1", frase: 'halo', nomor: wargaA.nomor, kunci: wargaA.kunci });
  catat('injeksi pada kanal ditolak 400', injKanal.status === 400, String(injKanal.status));
  const injNomor = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: wargaA.nomor + "' OR '1'='1", kunci: wargaA.kunci });
  catat('injeksi pada nomor ditolak 403', injNomor.status === 403, String(injNomor.status));
  const injKunci = await kirim({ kanal: 'alun-alun', frase: 'halo', nomor: wargaA.nomor, kunci: "' OR 1=1 --" });
  catat('injeksi pada kunci ditolak 403', injKunci.status === 403, String(injKunci.status));
  const sesudahInjeksi = (await baca('alun-alun')).isi.pesan.length;
  catat('injeksi tidak menambah pesan', sesudahInjeksi === sebelumInjeksi, sebelumInjeksi + ' vs ' + sesudahInjeksi);

  // ---------------------------------------------------------------------------
  // 7. sejak: hanya pesan yang lebih baru, urut naik
  // ---------------------------------------------------------------------------
  const semua = (await baca('alun-alun')).isi;
  const idTerakhir = semua.terakhir;
  const kosong = await baca('alun-alun', idTerakhir);
  catat('meminta dengan sejak terakhir tidak mengulang pesan', kosong.isi.pesan.length === 0);
  const urut = semua.pesan.map((p) => p.id);
  catat('pesan urut naik (paling lama dulu)', urut.every((id, i) => i === 0 || id > urut[i - 1]));

  // ---------------------------------------------------------------------------
  // 8. UI peta 3D dan 2D: panel chat, tanpa kolom teks, polling mengikuti panel
  // ---------------------------------------------------------------------------
  for (const halaman of ['peta.php', 'peta2d.php']) {
    // Pengunjung tanpa kartu: bisa membaca, tidak bisa menulis.
    const tamu = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await tamu.goto(BASE + '/' + halaman, { waitUntil: 'networkidle' });
    const tombolChat = halaman === 'peta.php' ? '#tombolChat' : '#tombolChat2d';
    // Fitur 3 menambah pil Ruko, sehingga tab utama tetap empat.
    catat(halaman + ': tab bar tetap empat tab utama',
      await tamu.locator('#tabBar .tab-item:not(.tab-pil)').count() === 4);
    catat(halaman + ': tombol Chat ada di bilah atas', await tamu.locator(tombolChat).isVisible());
    await tamu.locator(tombolChat).click();
    await tamu.waitForTimeout(500);
    catat(halaman + ': panel Chat terbuka dari bilah atas',
      await tamu.locator('[data-isi="chat"]').isVisible());
    await tamu.waitForSelector('.chat-pesan', { timeout: 5000 }).catch(() => {});
    catat(halaman + ': pengunjung bisa membaca pesan', await tamu.locator('.chat-pesan').count() >= 1);
    const kolomTeks = await tamu.evaluate(() => {
      const isi = document.querySelector('[data-isi="chat"]');
      return isi.querySelectorAll('input, textarea, [contenteditable]').length;
    });
    catat(halaman + ': panel chat tidak punya kolom teks', kolomTeks === 0, String(kolomTeks));
    catat(halaman + ': pengunjung tanpa kartu tidak bisa menulis', await tamu.locator('#chatKirim').isHidden());
    catat(halaman + ': pengunjung diarahkan bikin KTP', await tamu.locator('#chatTamu').isVisible());
    await tamu.close();

    // Warga A: bisa menulis di Alun-Alun (gang Jasa dengan pemilih kanal).
    const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await page.addInitScript(([nomor, kunci]) => {
      localStorage.setItem('dt_nomor', nomor);
      localStorage.setItem('dt_kunci', kunci);
    }, [wargaA.nomor, wargaA.kunci]);
    await page.goto(BASE + '/' + halaman, { waitUntil: 'networkidle' });
    // Peta 3D menggambar terus di GPU lunak, jadi tombolnya bisa tertahan
    // "tidak stabil" oleh Playwright; ketuk lewat DOM saja.
    await page.evaluate((id) => document.getElementById(id).click(), halaman === 'peta.php' ? 'tombolChat' : 'tombolChat2d');
    await page.waitForTimeout(600);
    await page.waitForSelector('#chatFrase button', { timeout: 5000 }).catch(() => {});
    catat(halaman + ': tombol frase tampil untuk warga', await page.locator('#chatFrase button').count() === 8,
      String(await page.locator('#chatFrase button').count()));
    catat(halaman + ': polling jalan saat panel terbuka',
      await page.evaluate(() => window.__chatDesa && window.__chatDesa.pollingJalan()));

    // Warga A baru saja mengirim pesan di uji API, jadi mundurkan waktu dulu.
    mundurkanWaktu(wargaA.nomor);
    const sebelumUk = await page.locator('.chat-pesan').count();
    await page.locator('#chatFrase button[data-frase="semangat"]').click();
    await page.waitForFunction((n) => document.querySelectorAll('.chat-pesan').length > n, sebelumUk, { timeout: 5000 }).catch(() => {});
    const sesudahUk = await page.locator('.chat-pesan').count();
    catat(halaman + ': tombol frase mengirim pesan', sesudahUk > sebelumUk, sebelumUk + ' -> ' + sesudahUk);
    const pesanDikirim = await page.locator('.chat-pesan.milikku').last().innerText().catch(() => '');
    catat(halaman + ': pesan terkirim bertanda milik sendiri dan teksnya preset',
      /Semangat jualannya hari ini!/.test(pesanDikirim), pesanDikirim.replace(/\n/g, ' | '));

    // Memilih kanal gang lain: hanya baca.
    await page.locator('.chat-kanal[data-kanal="Gang Kuliner"]').click();
    await page.waitForTimeout(600);
    catat(halaman + ': kanal gang lain hanya bisa dibaca', await page.locator('#chatKirim').isHidden());
    catat(halaman + ': daftar pesan berganti mengikuti kanal',
      await page.evaluate(() => window.__chatDesa.kanal() === 'Gang Kuliner'));

    // Polling berhenti saat panel ditutup dan hidup lagi saat dibuka.
    // Panel baru benar-benar tersembunyi setelah animasi 300 ms selesai.
    await page.evaluate(() => window.__tabBar.tutup(false));
    await page.waitForFunction(() => document.getElementById('panelBersayap').hidden, null, { timeout: 8000 }).catch(() => {});
    await page.waitForFunction(() => window.__chatDesa.pollingJalan() === false, null, { timeout: 8000 }).catch(() => {});
    catat(halaman + ': polling berhenti saat panel ditutup',
      await page.evaluate(() => window.__chatDesa.pollingJalan() === false));
    await page.evaluate(() => window.__tabBar.buka('chat', null));
    await page.waitForFunction(() => window.__chatDesa.pollingJalan() === true, null, { timeout: 8000 }).catch(() => {});
    catat(halaman + ': polling hidup lagi saat panel dibuka',
      await page.evaluate(() => window.__chatDesa.pollingJalan() === true));
    await page.close();
  }

  await browser.close();
  const gagal = hasil.filter((h) => !h.lulus);
  console.log('\n=== ' + (hasil.length - gagal.length) + ' lulus, ' + gagal.length + ' gagal dari ' + hasil.length + ' ===');
  process.exit(gagal.length ? 1 : 0);
})();
