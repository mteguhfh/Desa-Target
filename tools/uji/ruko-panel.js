const { jalankan } = require('./bantuan-paritas');
jalankan(async ({ halaman, buatWarga, php, cek, BASE }) => {
  for (const width of [1440, 390]) {
    const page = await halaman(null, true, width);
    await page.goto(BASE + '/', { waitUntil: 'networkidle' });
    const kotak = await page.locator('#tabBar .tab-item').evaluateAll(es => es.map(e => { const r=e.getBoundingClientRect(); return {w:r.width,h:r.height,y:r.y}; }));
    cek(`tujuh tab setara dan sejajar ${width}px`, kotak.length === 7 && kotak.every(r => Math.abs(r.w-kotak[0].w)<1 && r.h===kotak[0].h && r.y===kotak[0].y));
    await page.locator('#tabBar [data-panel="ruko"]').click();
    await page.locator('#rukoPanelTamu').waitFor({ state: 'visible' });
    cek(`Ruko membuka panel tanpa navigasi ${width}px`, new URL(page.url()).pathname === '/');
    cek(`judul Ruko benar ${width}px`, await page.locator('#panelBersayapJudul').innerText() === 'Ruko Wargamu');
    await page.close();
  }
  const warga = await buatWarga();
  const page = await halaman(warga);
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  await page.locator('#tabBar [data-panel="ruko"]').click();
  await page.locator('#rukoPanelKosong').waitFor({ state: 'visible' });
  cek('warga tanpa ruko mendapat tautan klaim', await page.locator('#rukoPanelBuka').getAttribute('href') === 'ruko');
  const hasil = php('echo json_encode(klaim_ruko(db(),$argv[1],$argv[2],"Ruko Uji Panel","Cerita usaha uji","https://example.com","Gang Kuliner",999));', warga.nomor, warga.kunci);
  cek('ruko uji dibuat', hasil.ok === true);
  await page.reload({ waitUntil: 'networkidle' });
  await page.locator('#tabBar [data-panel="ruko"]').click();
  await page.locator('#rukoPanelMilik').waitFor({ state: 'visible' });
  cek('panel menampilkan usaha sendiri', await page.locator('#rukoPanelNama').innerText() === 'Ruko Uji Panel');
  cek('cerita tampil sesuai data', await page.locator('#rukoPanelCerita').innerText() === 'Cerita usaha uji');
  cek('tautan usaha aman', await page.locator('#rukoPanelKunjungi').getAttribute('href') === 'https://example.com' && (await page.locator('#rukoPanelKunjungi').getAttribute('rel')).includes('noopener'));
  await page.waitForFunction(() => document.getElementById('rukoPanelGrid').textContent.includes('Ruko Uji Panel'));
  cek('usaha muncul di katalog', (await page.locator('#rukoPanelGrid').innerText()).includes('Ruko Uji Panel'));
});
