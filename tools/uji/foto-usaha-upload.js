const {jalankan}=require('./bantuan-paritas');
jalankan(async({halaman,buatWarga,cek,php,BASE})=>{
 const w=await buatWarga(), p=await halaman(w);
 let path;
 try{
 const hasil=php('echo json_encode(klaim_ruko(db(),$argv[1],$argv[2],"Foto Usaha Uji","","https://example.com","Gang Kuliner",999));',w.nomor,w.kunci);
 cek('fixture ruko tersedia',hasil.ok);
 await p.goto(BASE+'/ruko.php',{waitUntil:'networkidle'});
 await p.locator('#formUbahRuko').waitFor();
 const png=Buffer.from(await p.evaluate(()=>{const c=document.createElement('canvas');c.width=800;c.height=600;const g=c.getContext('2d');g.fillStyle='green';g.fillRect(0,0,800,600);return c.toDataURL('image/png').split(',')[1];}),'base64');
 await p.locator('#ubahFotoUsaha').setInputFiles({name:'foto.png',mimeType:'image/png',buffer:png});
 cek('pratinjau terlihat',await p.locator('.foto-usaha-pratinjau').isVisible());
 await Promise.all([p.waitForNavigation(),p.locator('#formUbahRuko button[type="submit"]').click()]);
 path=php('echo json_encode(ruko_milik(db(),$argv[1])["gambar"]);',w.nomor);
 cek('foto disimpan sebagai JPEG',path.startsWith('assets/uploads/usaha/'+w.nomor));
 cek('foto bisa dibuka',(await p.request.get(BASE+'/'+path)).ok());
 cek('foto warga lain ditolak',php('echo json_encode(gambar_usaha_sah($argv[1],"DT-999999")===null);',path));
 }finally{if(path)php('unlink(__DIR__."/".$argv[1]);echo "true";',path);}
});
