// Uji mode Keliling Desa di peta 3D.
//
// Dua bug yang pernah lolos ke production dan dijaga oleh berkas ini:
//
//   1. Pemain lahir DI DALAM tapak Balai Desa (FOKUS.y + 88 jatuh di kotak
//      566..818), jadi setiap langkah ditolak tabrakanJalan(). Tombol jalan
//      menyala tapi pemain sama sekali tidak bergerak -- terasa seperti
//      "belum bisa jalan-jalan di map 3D".
//
//   2. Kecepatan jalan ikut terpotong batas dt 0,05 s. Di perangkat tanpa
//      GPU (15-20 fps) pemain merayap beberapa piksel per detik.
//
// Lingkungan uji ini hanya ~3 fps, jadi jarak tempuh wajar diukur dengan
// batas bawah yang longgar; yang penting pemain BERGERAK dan arahnya benar.
//
// Jalankan dengan server lokal:
//   DESA_TARGET_BASE_URL=http://localhost:8100 php -S localhost:8100
//   node tools/uji/mode-jalan.js

const { chromium } = require('playwright-core');

const DASAR = process.env.DESA_TARGET_BASE_URL || 'http://localhost:8100';
const CHROME = process.env.CHROME_PATH
  || '/home/mteguhfh/.cache/ms-playwright/chromium-1248/chrome-linux64/chrome';

let lulus = 0, gagal = 0;
function catat(nama, ok, bukti = '') {
  if (ok) { lulus++; console.log('LULUS ' + nama + (bukti ? ' — ' + bukti : '')); }
  else { gagal++; console.log('GAGAL ' + nama + (bukti ? ' — ' + bukti : '')); }
}

async function buka(browser, jalur) {
  // Viewport lebih kecil dari layar biasa: lingkungan uji tanpa GPU hanya ~1-3
  // fps pada 1440px, dan bingkai yang terlalu jarang membuat tombol yang
  // ditahan bisa tidak jatuh pada satu pun bingkai.
  const page = await browser.newPage({ viewport: { width: 1024, height: 720 } });
  const galat = [];
  page.on('pageerror', (e) => galat.push(String(e)));
  await page.goto(DASAR + '/' + jalur, { waitUntil: 'networkidle' });
  // Tunggu dunia 3D selesai dibangun.
  await page.waitForFunction(() => window.__desa && window.__desa.kamu, null, { timeout: 30000 });
  await page.waitForFunction(
    () => getComputedStyle(document.getElementById('layarMuat')).opacity === '0',
    null, { timeout: 30000 },
  );
  page.galatJs = galat;
  return page;
}

// Tahan tombol sampai sejumlah bingkai render benar-benar terjadi, lalu
// lepas. Lingkungan uji tanpa GPU hanya 1-3 bingkai/detik, jadi menunggu
// "2 detik" bisa berakhir 1-2 bingkai saja -- kadang nol bila bingkai jatuh
// tepat di antara penekanan dan pelepasan tombol.
async function tahanBingkai(page, tekan, lepas, jumlahBingkai = 8) {
  await tekan();
  await page.evaluate((n) => new Promise((r) => {
    let k = 0;
    const f = () => { k++; if (k >= n) r(); else requestAnimationFrame(f); };
    requestAnimationFrame(f);
  }), jumlahBingkai);
  await lepas();
  // Beri waktu satu-dua bingkai lagi untuk posisi akhir terbaca.
  await page.evaluate(() => new Promise((r) => {
    let k = 0;
    const f = () => { k++; if (k >= 2) r(); else requestAnimationFrame(f); };
    requestAnimationFrame(f);
  }));
}

const posisi = (page) => page.evaluate(() => ({
  x: Math.round(window.__desa.kamu.x),
  y: Math.round(window.__desa.kamu.y),
  arah: Math.round(((window.__desa.kamu.arah * 180) / Math.PI + 360) % 360),
}));

(async () => {
  const browser = await chromium.launch({
    executablePath: CHROME,
    args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'],
  });

  const page = await buka(browser, 'peta.php');
  await page.evaluate(() => document.getElementById('tombolJalan').click());
  await page.waitForTimeout(1000);

  // --- 1. Titik lahir di luar rintangan --------------------------------
  const lahir = await posisi(page);
  catat('mode jalan menyala', await page.evaluate(
    () => document.getElementById('tombolJalan').classList.contains('aktif'),
  ));
  catat('pemain lahir di luar tapak Balai Desa', lahir.y > 832,
    `y = ${lahir.y} (tapak berakhir di 818)`);
  catat('figur pemain terlihat', await page.evaluate(() => window.__desa.kamu.figur.visible));

  // Label "Kamu" tidak boleh menutupi pandangan saat kamera di belakang bahu.
  catat('label Kamu disembunyikan di mode jalan',
    await page.evaluate(() => window.__desa.kamu.label.visible === false));

  // Kamera langsung berada di belakang pemain, bukan masih melayang di orbit.
  const dekat = await page.evaluate(() => Math.round(
    window.__desa.camera.position.distanceTo(window.__desa.kamu.figur.position),
  ));
  catat('kamera langsung dipasang di dekat pemain', dekat < 260, `jarak ${dekat} px`);

  // --- 2. Pemain benar-benar bisa berjalan ------------------------------
  const sebelumW = await posisi(page);
  await tahanBingkai(page,
    () => page.keyboard.down('w'),
    () => page.keyboard.up('w'));
  const sesudahW = await posisi(page);
  const dW = Math.hypot(sesudahW.x - sebelumW.x, sesudahW.y - sebelumW.y);
  catat('tombol W menggerakkan pemain', dW > 15, `geser ${dW} px`);

  // --- 2b. Chip masuk Balai Desa muncul saat dekat pintu ---------------
  // Diperiksa di sini, sebelum tombol lain menggeser pemain menjauh.
  catat('chip Masuk Balai Desa tampil saat dekat pintu',
    await page.evaluate(() => document.getElementById('chipMasuk').classList.contains('tampil')));

  // --- 3. D-pad juga menggerakkan pemain (layar sentuh) -----------------
  // Pindahkan ke ruang lapang dulu: setelah W tadi pemain mungkin sudah
  // menempel gedung, dan gerak yang tertahan gedung bukan tanda kerusakan.
  const keLapang = () => page.evaluate(() => {
    const d = window.__desa;
    d.kamu.x = d.fokus.x;
    d.kamu.y = d.fokus.y + 360;
    d.kamu.arah = Math.PI;
  });
  // Tunggu kamera benar-benar kembali ke belakang bahu. Memindahkan pemain
  // secara paksa membuat kamera masih meluncur beberapa bingkai; sudut jalan
  // dihitung dari arah pandang kamera, jadi menguji sebelum kamera mantap
  // mengukur kamera yang sedang bergerak, bukan fiturnya.
  const tungguKameraMantap = () => page.evaluate(() => new Promise((r) => {
    const d = window.__desa;
    let k = 0;
    const f = () => {
      k++;
      const t = d.kamu.figur.position;
      const c = d.camera.position;
      const dekat = Math.hypot(c.x - t.x, c.z - t.z) < 240;
      if (dekat || k > 30) r(); else requestAnimationFrame(f);
    };
    requestAnimationFrame(f);
  }));
  await keLapang();
  await tungguKameraMantap();
  const sebelumDpad = await posisi(page);
  await tahanBingkai(page,
    () => page.evaluate(() => {
      const b = document.querySelector('.dpad .kanan');
      b.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
    }),
    () => page.evaluate(() => {
      const b = document.querySelector('.dpad .kanan');
      b.dispatchEvent(new PointerEvent('pointerup', { bubbles: true }));
    }));
  const sesudahDpad = await posisi(page);
  const dDpad = Math.hypot(sesudahDpad.x - sebelumDpad.x, sesudahDpad.y - sebelumDpad.y);
  catat('tombol arah D-pad menggerakkan pemain', dDpad > 12, `geser ${dDpad} px`);

  // --- 4. D berjalan lurus ke samping, bukan berputar dalam lingkaran ----
  // Pemain dikembalikan ke halaman dan menghadap gedung lebih dulu, supaya
  // arah "kanan" bisa dibandingkan dengan nilai yang pasti.
  // Di halaman depan gedung ruang kosongnya sempit; geser sedikit ke selatan
  // supaya pemain punya ruang untuk berjalan lurus tanpa menabrak tepi.
  await keLapang();
  await tungguKameraMantap();
  // Dulu "kanan" dihitung dari arah hadap yang ikut berputar tiap bingkai,
  // jadi tombol yang ditahan membuat pemain melingkar dan kembali ke titik
  // awal. Yang benar: satu arah terkunci, lintasan lurus.
  const a0 = await posisi(page);
  // "Kanan" diukur relatif kamera, jadi yang diperiksa adalah pemain berbelok
  // TEPAT 90 derajat dari arah hadapnya sekarang, bukan ke sudut tetap.
  // Acuan: sudut pemain sebelum menekan D. Kamera tidak dipakai sebagai
  // acuan karena ia menghadap pemain yang sedang bergeser, sehingga sudutnya
  // ikut miring beberapa derajat selama berjalan.
  const arahKamera = a0.arah;
  await tahanBingkai(page,
    () => page.evaluate(() => window.dispatchEvent(new KeyboardEvent('keydown', { key: 'd' }))),
    () => page.evaluate(() => window.dispatchEvent(new KeyboardEvent('keyup', { key: 'd' }))));
  const a1 = await posisi(page);
  // Arah hadap setelah D harus 90 derajat ke KANAN dari arah kamera.
  // Selisih ternormalisasi ke -180..180: minus berarti berbelok ke kanan.
  const putar = ((a1.arah - arahKamera + 540) % 360) - 180;
  catat('D memutar pemain 90 derajat ke kanan', Math.abs(putar + 90) <= 15,
    `pemain ${arahKamera}° -> ${a1.arah}° (selisih ${Math.round(putar)}°)`);
  // Lintasan harus hampir lurus: perpindahan horizontal jauh lebih besar
  // daripada perpindahan tegak lurus (dulu keduanya nyaris nol karena
  // pemain kembali ke titik awal).
  // Lintasan harus hampir lurus. Pemain yang melingkar kembali mendekati
  // titik awal, jadi jarak tempuh bersihnya nyaris nol.
  const geser = Math.hypot(a1.x - a0.x, a1.y - a0.y);
  catat('D berjalan lurus ke samping, tidak melingkar', geser > 25,
    `jarak tempuh bersih ${Math.round(geser)} px`);

  // --- 4b. W+D bergerak serong, bukan berpusing -------------------------
  // Kembalikan pemain ke halaman depan: setelah D dan W+D sebelumnya ia
  // mungkin sudah menempel dinding, dan gerak yang tertahan dinding bukan
  // tanda ada yang rusak.
  const kembalikanKeHalaman = () => page.evaluate(() => {
    const d = window.__desa;
    d.kamu.x = d.fokus.x;
    d.kamu.y = d.fokus.y + 200;
    d.kamu.arah = Math.PI;
    // Kamera ikut dipindah ke belakang bahu seketika: kalau dibiarkan
    // meluncur, arah "maju" pada uji berikutnya dihitung dari posisi lama.
    const pos = { x: d.kamu.x - 1000, y: 0, z: d.kamu.y - 666.5 };
    d.camera.position.set(pos.x, 104, pos.z + 178);
    d.camera.lookAt(pos.x, 46, pos.z);
  });
  await keLapang();
  await tungguKameraMantap();
  const s0 = await posisi(page);
  await tahanBingkai(page,
    () => page.evaluate(() => {
      window.dispatchEvent(new KeyboardEvent('keydown', { key: 'w' }));
      window.dispatchEvent(new KeyboardEvent('keydown', { key: 'd' }));
    }),
    () => page.evaluate(() => {
      window.dispatchEvent(new KeyboardEvent('keyup', { key: 'w' }));
      window.dispatchEvent(new KeyboardEvent('keyup', { key: 'd' }));
    }));
  const s1 = await posisi(page);
  const dSerong = Math.hypot(s1.x - s0.x, s1.y - s0.y);
  catat('W+D bergerak serong', dSerong > 25, `geser ${Math.round(dSerong)} px`);

  // --- 5. Masuk Balai Desa tanpa meninggalkan peta 3D -------------------
  // Pemain sudah sempat berjalan menjauh; dekati pintu lagi dengan
  // menaruhnya tepat di halaman depan sebelum menguji masuk.
  // Chip muncul dalam radius 135 dari pintu (FOKUS.y + 88). Taruh pemain di
  // dekat pintu, bukan di titik lahir mode jalan yang jauhnya 200.
  await page.evaluate(() => {
    const d = window.__desa;
    d.kamu.x = d.fokus.x;
    d.kamu.y = d.fokus.y + 110;
  });
  await page.waitForTimeout(700);
  const chipTampil = await page.evaluate(
    () => document.getElementById('chipMasuk').classList.contains('tampil'),
  );
  catat('chip Masuk Balai Desa tetap tampil setelah kembali ke pintu', chipTampil);
  if (chipTampil) {
    await page.evaluate(() => document.getElementById('chipMasuk').click());
    await page.waitForTimeout(800);
    catat('interior terbuka di atas peta 3D',
      await page.evaluate(() => !document.getElementById('interior3d').hidden));
    // Ruangan Balai Desa kini 3D: kanvas Three.js terpisah dari peta luar.
    await page.waitForFunction(
      () => window.__interior3d && window.__interior3d.siap(),
      null, { timeout: 15000 },
    ).catch(() => {});
    catat('interior punya ruangan 3D', (await page.locator('#interior3dKanvas canvas').count()) === 1);
    catat('interior bisa dijelajahi (mesin siap)',
      await page.evaluate(() => !!(window.__interior3d && window.__interior3d.siap())));
    catat('URL tetap di halaman peta 3D', page.url().includes('peta.php'), page.url());
    await page.evaluate(() => document.getElementById('tombolKeluarInterior3d').click());
    await page.waitForTimeout(600);
    catat('keluar interior kembali ke peta 3D',
      await page.evaluate(() => document.getElementById('interior3d').hidden));
  }

  // --- 6. Tidak ada galat JavaScript ------------------------------------
  catat('tidak ada galat JavaScript', page.galatJs.length === 0, page.galatJs[0] || '');
  await page.close();

  // --- 7. Di ponsel tetap bisa jalan ------------------------------------
  const hp = await browser.newPage({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
  await hp.goto(DASAR + '/peta.php', { waitUntil: 'networkidle' });
  await hp.waitForFunction(() => window.__desa && window.__desa.kamu, null, { timeout: 30000 });
  await hp.waitForFunction(
    () => getComputedStyle(document.getElementById('layarMuat')).opacity === '0',
    null, { timeout: 30000 },
  );
  await hp.evaluate(() => document.getElementById('tombolJalan').click());
  await hp.waitForTimeout(1000);
  const hpAwal = await posisi(hp);
  await hp.evaluate(() => {
    const b = document.querySelector('.dpad .atas');
    b.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
  });
  await hp.waitForTimeout(1800);
  await hp.evaluate(() => {
    const b = document.querySelector('.dpad .atas');
    b.dispatchEvent(new PointerEvent('pointerup', { bubbles: true }));
  });
  await hp.waitForTimeout(600);
  const hpAkhir = await posisi(hp);
  const dHp = Math.hypot(hpAkhir.x - hpAwal.x, hpAkhir.y - hpAwal.y);
  catat('di ponsel 390px, D-pad menggerakkan pemain', dHp > 12, `geser ${dHp} px`);
  await hp.close();

  await browser.close();
  console.log(`\n=== ${lulus} lulus, ${gagal} gagal dari ${lulus + gagal} ===`);
  process.exit(gagal ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
