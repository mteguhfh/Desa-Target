const { jalankan } = require('./bantuan-paritas');
jalankan(async ({ halaman, buatWarga, php, cek, BASE }) => {
  const warga = await buatWarga();
  const page = await halaman(warga);
  await page.goto(BASE + '/ruko', { waitUntil: 'networkidle' });
  await page.locator('#formRuko').waitFor({ state: 'visible' });
  const pilihan = await page.locator('#formRuko input[name="gambar"]').evaluateAll(es => es.map(e => e.value).filter(Boolean));
  cek('tersedia gambar lokal', pilihan.length >= 2 && pilihan.every(p => p.startsWith('assets/img/')));
  cek('tidak ada unggahan berkas', await page.locator('input[type="file"]').count() === 0);
  await page.locator('#namaUsaha').fill('Ruko Uji Gambar');
  await page.locator('#ceritaUsaha').fill('Usaha uji gambar');
  await page.locator('#tautanUsaha').fill('https://example.com');
  await page.locator(`#formRuko label[data-gambar="${pilihan[0]}"]`).click();
  async function kirim(form) {
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator(form + ' button[type="submit"]').click()]);
    await page.locator('#formUbahRuko').waitFor({ state: 'visible' });
  }
  await kirim('#formRuko');
  cek('gambar pilihan tersimpan', await page.locator('.ruko-gambar-saya').getAttribute('src') === pilihan[0]);
  await page.locator(`#formUbahRuko label[data-gambar="${pilihan[1]}"]`).click();
  await kirim('#formUbahRuko');
  cek('gambar bisa diganti', await page.locator('.ruko-gambar-saya').getAttribute('src') === pilihan[1]);
  const data = await (await page.request.get(BASE + '/peta3d/data-desa')).json();
  cek('gambar baru ikut data peta', data.bangunan.some(b => b.nama === 'Ruko Uji Gambar' && b.gambar === pilihan[1]));
  const peta = await halaman(warga);
  await peta.goto(BASE + '/', { waitUntil: 'networkidle' });
  await peta.locator('#tabBar [data-panel="ruko"]').click();
  await peta.locator('#rukoPanelGambar').waitFor({ state: 'visible' });
  cek('gambar tampil di panel', await peta.locator('#rukoPanelGambar').getAttribute('src') === pilihan[1]);
  await peta.close();
  const tolak = php('echo json_encode(ubah_ruko(db(),$argv[1],$argv[2],"Ruko Uji Gambar","Usaha uji gambar","https://example.com",$argv[3]));', warga.nomor, warga.kunci, 'https://example.com/gambar.png');
  cek('URL gambar bebas ditolak', tolak.galat === 'gambar');
  await page.locator('#formUbahRuko input[name="hapus_gambar"]').check();
  await kirim('#formUbahRuko');
  cek('gambar bisa dihapus', await page.locator('.ruko-gambar-saya').count() === 0);
  const akhir = await (await page.request.get(BASE + '/peta3d/data-desa')).json();
  cek('penghapusan ikut data peta', akhir.bangunan.some(b => b.nama === 'Ruko Uji Gambar' && !b.gambar));
});
