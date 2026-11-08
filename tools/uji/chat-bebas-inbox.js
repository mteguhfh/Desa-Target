const { jalankan } = require('./bantuan-paritas');
jalankan(async ({halaman,buatWarga,cek,BASE,php})=>{
 const a=await buatWarga(), b=await buatWarga(), c=await buatWarga();
 const p=await halaman(a); await p.goto(BASE+'/peta2d.php',{waitUntil:'networkidle'});
 const post=(path,form)=>p.request.post(BASE+path,{form});
 try {
 let r=await post('/chat.php',{...a,kanal:'alun-alun',teks:'Halo <script>alert(1)</script> 🌾'});
 cek('teks bebas tersimpan sebagai teks',r.status()===201 && (await r.json()).pesan[0].teks.includes('<script>'));
 r=await post('/inbox.php',{...a,tujuan:b.nomor,teks:'Pesan pribadi uji'}); cek('pesan pribadi terkirim',r.ok());
 r=await post('/inbox.php',{...a,tujuan:b.nomor,teks:'ulang'}); cek('jeda kirim berlaku',r.status()===429);
 r=await post('/inbox.php',b); cek('penerima membaca pesan', (await r.json()).pesan.some(x=>x.teks==='Pesan pribadi uji'));
 r=await post('/inbox.php',b); const masuk=await r.json();
 cek('polling tidak menandai pesan dibaca',masuk.belum_dibaca===1);
 r=await post('/inbox.php',{...c,baca:String(masuk.pesan[0].id)}); cek('warga lain tidak menghapus status belum dibaca',(await r.json()).belum_dibaca===0);
 const penerima=await halaman(b); await penerima.goto(BASE+(process.env.PETA_INBOX_UJI || '/peta2d.php'),{waitUntil:'domcontentloaded'});
 await penerima.bringToFront();
 await penerima.locator('#notifikasiInbox').waitFor({state:'visible'});
 cek('notifikasi tampil di luar inbox',await penerima.locator('#notifikasiInbox').isVisible());
 await penerima.locator('#notifikasiInbox').click(); cek('notifikasi membuka inbox',await penerima.locator('#inboxForm').isVisible());
 await penerima.locator('#notifikasiInbox').waitFor({state:'hidden',timeout:15000});
 r=await post('/inbox.php',b); cek('inbox terlihat menandai pesan dibaca',(await r.json()).belum_dibaca===0);
 await penerima.close();
 r=await post('/inbox.php',c); cek('warga lain tidak membaca pesan',(await r.json()).pesan.length===0);
 r=await post('/inbox.php',{nomor:b.nomor,kunci:'salah'}); cek('kunci salah ditolak',r.status()===403);
 r=await post('/inbox.php',{...b,tujuan:a.nomor,teks:'x'.repeat(281)}); cek('panjang pesan dibatasi',r.status()===400);
 const errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.locator('#chipChat').count().then(async n=>{if(n)await p.locator('#chipChat').click();else await p.evaluate(()=>window.__tabBar.buka('chat'));});
 await p.evaluate(({nomor})=>window.__bicaraWarga(nomor,'Warga Uji'),b);
 cek('ajakan bicara membuka inbox',await p.locator('#inboxForm').isVisible());
 cek('tujuan terisi benar',await p.locator('#inboxTujuan').inputValue()===b.nomor);
 cek('tanpa galat browser',errors.length===0);
 } finally { php('$p=db();foreach($argv as $n){$p->prepare("DELETE FROM inbox_pesan WHERE pengirim=? OR penerima=?")->execute([$n,$n]);$p->prepare("DELETE FROM chat_pesan WHERE nomor=?")->execute([$n]);}echo "true";',a.nomor,b.nomor,c.nomor); }
});
