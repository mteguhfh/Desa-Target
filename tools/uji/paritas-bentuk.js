const { jalankan } = require('./bantuan-paritas');
jalankan(async ({ halaman, nomorUji, cek, php, BASE }) => {
  const page = await halaman();
  await page.goto(BASE + '/ktp.php', { waitUntil: 'networkidle' });
  const pilih = page.locator('form select[name="bentuk_topi"]').first();
  for (const bentuk of ['pet', 'peci', 'caping', 'rambut']) {
    await pilih.selectOption(bentuk);
    const gaya = await page.locator('form .avatar-topi').first().evaluate(el => ({ width: getComputedStyle(el).width, radius: getComputedStyle(el).borderTopLeftRadius, background: getComputedStyle(el).backgroundColor }));
    cek('pratinjau ' + bentuk, bentuk === 'caping' ? gaya.width === '72px' : bentuk === 'peci' ? gaya.radius === '3px' : bentuk === 'rambut' ? gaya.background === 'rgb(41, 37, 36)' : gaya.width === '34px');
  }
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  for (const bentuk of ['pet', 'peci', 'caping', 'rambut', 'asing']) {
    await page.reload({ waitUntil: 'networkidle' });
    const response = await page.request.post(BASE + '/ktp-json.php', { form: { nama: 'Uji Bentuk Kepala', gang: 'Gang Kuliner', izin_penerbitan: '1', bentuk_topi: bentuk, token: await page.evaluate(() => window.__tokenKtp) } });
    const warga = await response.json();
    cek('kartu terbit ' + bentuk, response.ok() && !!warga.nomor);
    nomorUji.push(warga.nomor);
    const tersimpan = php('$s=db()->prepare("SELECT bentuk_topi FROM warga WHERE id=?"); $s->execute([(int)substr($argv[1],3)]); echo json_encode($s->fetchColumn());', warga.nomor);
    cek('bentuk tersimpan ' + bentuk, tersimpan === (bentuk === 'asing' ? 'pet' : bentuk));
    const html = await (await page.request.get(BASE + '/ktp.php?kartu=' + Number(warga.nomor.slice(3)))).text();
    cek('kartu menampilkan bentuk ' + bentuk, html.includes('bentuk-' + tersimpan));
  }
  await page.close();
});
