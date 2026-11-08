const { jalankan } = require('./bantuan-paritas');

jalankan(async ({ halaman, buatWarga, cek, php, BASE }) => {
  const warga = await buatWarga();
  const page = await halaman(warga);
  const post = (form) => page.request.post(BASE + '/main.php', { form: { ...warga, ...form } });
  try {
    cek('GET ditolak', (await page.request.get(BASE + '/main.php')).status() === 405);
    cek('kunci salah ditolak', (await post({ aksi: 'mulai', kunci: 'salah' })).status() === 403);
    cek('aksi asing ditolak', (await post({ aksi: 'asing' })).status() === 400);
    const mulai = await (await post({ aksi: 'mulai' })).json();
    cek('sesi menerbitkan token', /^[a-f0-9]{32}$/.test(mulai.token));
    for (const waktu of ['', '-1', '399', '59001', '450.5', 'abc']) {
      cek('waktu tidak sah ditolak: ' + waktu, (await post({ aksi: 'selesai', token: mulai.token, waktu_ketuk: waktu })).status() === 400);
    }
    cek('token palsu ditolak', (await post({ aksi: 'selesai', token: 'palsu', waktu_ketuk: '450' })).status() === 400);
    await new Promise(resolve => setTimeout(resolve, 1600));
    const hasil = await (await post({ aksi: 'selesai', token: mulai.token, waktu_ketuk: '450', skor: '0' })).json();
    cek('latensi tidak menggeser skor ketuk', hasil.ok && hasil.skor === 100 && hasil.hadiah === 5);
    cek('token tidak bisa dipakai ulang', (await post({ aksi: 'selesai', token: mulai.token, waktu_ketuk: '450' })).status() === 400);
    const ulang = await (await post({ aksi: 'mulai' })).json();
    await new Promise(resolve => setTimeout(resolve, 500));
    const kedua = await (await post({ aksi: 'selesai', token: ulang.token, waktu_ketuk: '450' })).json();
    cek('hasil kedua tidak memberi hadiah', kedua.sudah === true);
    const saldo = await (await page.request.get(BASE + '/poin.php?nomor=' + warga.nomor + '&kunci=' + warga.kunci)).json();
    cek('saldo tepat lima poin', saldo.saldo === 5);
  } finally {
    php('$p=db(); $p->prepare("DELETE FROM panen_harian WHERE nomor=?")->execute([$argv[1]]); echo "true";', warga.nomor);
    await page.close();
  }
});
