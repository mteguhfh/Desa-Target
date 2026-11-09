const {jalankan}=require('./bantuan-paritas');
jalankan(async({halaman,buatWarga,cek,php,BASE})=>{
 const w=await buatWarga(),p=await halaman(w);const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
 await p.goto(BASE+'/',{waitUntil:'networkidle'});await p.waitForFunction(()=>window.__desa?.agenWarga.length>0);
 await p.evaluate(n=>{window.__desa.agenWarga[0].figur.userData.popover.nomor=n;},w.nomor);
 await p.waitForResponse(r=>r.url().includes('chat?kanal=alun-alun&sejak='));
 const r=await p.request.post(BASE+'/chat',{form:{...w,kanal:'alun-alun',teks:'Halo dari dunia'}});cek('pesan terkirim',r.status()===201);
 await p.waitForFunction(n=>window.__desa.agenWarga.find(a=>a.figur.userData.popover?.nomor===n)?.figur.getObjectByName('bubbleChat'),w.nomor);
 cek('bubble muncul di figur pengirim',true);
 await p.evaluate(n=>{const d=window.__desa;d.aturModeJalan(true);const a=d.agenWarga.find(a=>a.figur.userData.popover?.nomor!==n);a.x=d.kamu.x+40;a.y=d.kamu.y;a.figur.position.set(a.x-1000,0,a.y-666.5);},w.nomor);
 await p.locator('#bicaraWarga').waitFor({state:'visible'});cek('ajakan muncul saat dekat',true);
 await p.locator('#bicaraWarga').click();cek('ajakan membuka inbox',await p.locator('#inboxForm').isVisible());cek('tanpa pageerror',errors.length===0);
 }finally{php('db()->prepare("DELETE FROM chat_pesan WHERE nomor=?")->execute([$argv[1]]);echo "true";',w.nomor);}
});
