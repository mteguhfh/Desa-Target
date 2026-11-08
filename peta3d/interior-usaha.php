<section id="interiorUsaha" class="interior-3d" hidden aria-label="Interior usaha">
    <div id="usahaKanvas" class="interior-3d-kanvas"></div>
    <h2 id="usahaJudul" class="usaha-judul">Pasar Desa</h2>
    <aside class="usaha-katalog" aria-label="Katalog usaha">
        <label for="usahaKategori">Kategori usaha</label>
        <select id="usahaKategori"><option value="">Semua kategori</option><option value="makanan">Makanan</option><option value="jasa">Jasa</option><option value="kerajinan">Kerajinan</option><option value="lainnya">Lainnya</option></select>
        <p id="usahaStatus" role="status"></p>
        <button type="button" id="usahaUlang" hidden>Coba lagi</button>
        <ul id="usahaDaftar"></ul>
    </aside>
    <button type="button" id="usahaKeluar" class="interior-3d-keluar">Keluar ke desa</button>
    <div class="interior-3d-dpad" id="usahaDpad" aria-label="Gerak di dalam usaha">
        <button type="button" class="atas" data-dx="0" data-dz="-1" aria-label="Maju">↑</button>
        <button type="button" class="kiri" data-dx="-1" data-dz="0" aria-label="Kiri">←</button>
        <button type="button" class="kanan" data-dx="1" data-dz="0" aria-label="Kanan">→</button>
        <button type="button" class="bawah" data-dx="0" data-dz="1" aria-label="Mundur">↓</button>
    </div>
</section>
<script type="module">
import * as THREE from 'three';
const panel = document.getElementById('interiorUsaha');
document.querySelectorAll('.masuk-usaha').forEach(b => b.hidden = false);
let renderer, scene, camera, figur, frame, sebelumnya, pemicu;
const gerak = new Set();
const daftarEl = document.getElementById('usahaDaftar');
const kategoriEl = document.getElementById('usahaKategori');
const statusEl = document.getElementById('usahaStatus');
const ulangEl = document.getElementById('usahaUlang');
const akar = new URL(<?= json_encode($tabAwalan ?? '') ?> || './', location.href);
let daftarUsaha = [], permintaan = 0, modePasar = false, pilihanKavling = null;
function katalog() {
    daftarEl.replaceChildren();
    const terlihat = daftarUsaha.filter(r => !kategoriEl.value || r.kategori === kategoriEl.value);
    statusEl.textContent = terlihat.length ? 'Pilih usaha untuk mengunjungi tempat jualannya.' : 'Belum ada usaha di pilihan ini.';
    for (const r of terlihat) {
        const li = document.createElement('li');
        if (r.gambar && /^assets\/img\//.test(r.gambar)) {
            const img = document.createElement('img'); img.src = new URL(r.gambar, akar); img.alt = 'Foto ' + (r.nama || r.namaUsaha); img.width = 640; img.height = 480; img.loading = 'lazy'; li.append(img);
        }
        const nama = document.createElement('h3'); nama.textContent = r.nama || r.namaUsaha; li.append(nama);
        const harga = document.createElement('p'); harga.textContent = r.harga == null ? 'Tanyakan harga' : 'Mulai Rp' + Number(r.harga).toLocaleString('id-ID'); li.append(harga);
        if (r.komentar) { const cerita = document.createElement('p'); cerita.textContent = r.komentar; li.append(cerita); }
        if (r.gang) { const gang = document.createElement('p'); gang.textContent = r.gang; li.append(gang); }
        if (typeof r.tautan === 'string' && /^https:\/\//i.test(r.tautan)) {
            const tautan = document.createElement('a'); tautan.href = r.tautan; tautan.target = '_blank'; tautan.rel = 'noopener noreferrer'; tautan.textContent = 'Kunjungi tempat jualan'; li.append(tautan);
        }
        daftarEl.append(li);
    }
}
async function muatKatalog() {
    const id = ++permintaan; daftarUsaha = []; daftarEl.replaceChildren(); ulangEl.hidden = true; statusEl.textContent = 'Memuat katalog usaha…';
    try {
        const respons = await fetch(new URL('peta3d/data-desa.php', akar));
        if (!respons.ok) throw new Error('data');
        const data = await respons.json();
        let usaha;
        if (modePasar) {
            const acara = data.acara || data.acaraBerikutnya?.acara;
            if (acara) {
                const url = new URL('lapak.php', akar); url.searchParams.set('acara', acara);
                if (!data.acara && data.acaraBerikutnya?.tanggal) url.searchParams.set('tanggal', data.acaraBerikutnya.tanggal);
                const lapak = await fetch(url); if (!lapak.ok) throw new Error('lapak');
                usaha = (await lapak.json()).lapak;
            } else usaha = [];
        } else usaha = data.bangunan?.filter(r => r.jenis === 'ruko');
        if (!Array.isArray(usaha)) throw new Error('format');
        if (id !== permintaan || panel.hidden) return;
        daftarUsaha = !modePasar && pilihanKavling != null ? usaha.filter(r => r.kavling === pilihanKavling) : usaha; katalog();
    } catch (e) {
        if (id !== permintaan || panel.hidden) return;
        statusEl.textContent = 'Katalog belum bisa dimuat. Coba lagi.'; ulangEl.hidden = false;
    }
}
kategoriEl.onchange = katalog;
ulangEl.onclick = muatKatalog;
function kotak(w,h,d,warna,x,y,z) {
    const benda = new THREE.Mesh(new THREE.BoxGeometry(w,h,d), new THREE.MeshStandardMaterial({color:warna}));
    benda.position.set(x,y,z); scene.add(benda); return benda;
}
function ukuran() { if (!renderer || panel.hidden) return; renderer.setSize(panel.clientWidth,panel.clientHeight); camera.aspect=panel.clientWidth/panel.clientHeight; camera.updateProjectionMatrix(); }
function tutup() { panel.hidden=true; permintaan++; gerak.clear(); cancelAnimationFrame(frame); pemicu?.focus(); }
function gambar(t) {
    const dt=Math.min((t-sebelumnya)/1000 || 0,.05); sebelumnya=t;
    let dx=0,dz=0;
    for(const arah of gerak) { const [x,z]=arah.split(',').map(Number); dx+=x;dz+=z; }
    const panjang=Math.hypot(dx,dz)||1;
    figur.position.x=THREE.MathUtils.clamp(figur.position.x+dx/panjang*150*dt,-235,235);
    figur.position.z=THREE.MathUtils.clamp(figur.position.z+dz/panjang*150*dt,-70,235);
    camera.position.set(figur.position.x,270,figur.position.z+320); camera.lookAt(figur.position.x,35,figur.position.z-80);
    renderer.render(scene,camera); frame=requestAnimationFrame(gambar);
}
window.__usaha = { buka(nama, pasar=false, kavling=null) {
    cancelAnimationFrame(frame); gerak.clear(); modePasar=pasar; pilihanKavling=kavling; kategoriEl.value='';
    pemicu=document.activeElement;
    if (!renderer) { renderer=new THREE.WebGLRenderer({antialias:true});renderer.setPixelRatio(Math.min(devicePixelRatio,2));document.getElementById('usahaKanvas').appendChild(renderer.domElement);camera=new THREE.PerspectiveCamera(58,1,1,2000); }
    if(scene) scene.traverse(o=>{o.geometry?.dispose();if(o.material)o.material.dispose();});
    scene=new THREE.Scene();scene.background=new THREE.Color('#fff1d1');
    scene.add(new THREE.HemisphereLight(0xffffff,0x796744,3));
    kotak(600,10,600,0xdbc59a,0,-5,0);kotak(600,240,12,0xfff9e8,0,120,-300);
    kotak(12,240,600,0xf4dbab,-300,120,0);kotak(12,240,600,0xf4dbab,300,120,0);
    for(const x of (pasar?[-190,0,190]:[0])) {kotak(pasar?150:340,80,90,0x99724a,x,40,-180);kotak(pasar?160:350,10,110,0xf8ce46,x,85,-180);for(let i=-1;i<=1;i++)kotak(24,24,24,pasar?0x76a14e:0xe19165,x+i*37,102,-180);}
    figur=kotak(24,40,20,0x315c8c,0,20,180);const kepala=kotak(20,20,20,0xf6c48a,0,50,180);scene.remove(kepala);figur.add(kepala);kepala.position.set(0,30,0);
    document.getElementById('usahaJudul').textContent=nama;
    panel.hidden=false;ukuran();sebelumnya=performance.now();frame=requestAnimationFrame(gambar);document.getElementById('usahaKeluar').focus();muatKatalog();
}, tutup, posisi: () => figur ? {x: figur.position.x, z: figur.position.z} : null };
document.getElementById('usahaKeluar').onclick=tutup;
const tombol={ArrowUp:'0,-1',w:'0,-1',ArrowDown:'0,1',s:'0,1',ArrowLeft:'-1,0',a:'-1,0',ArrowRight:'1,0',d:'1,0'};
document.addEventListener('keydown',e=>{if(panel.hidden)return;if(e.key==='Escape'){tutup();return;}if(e.target.closest('select, input, textarea, a'))return;if(tombol[e.key]){e.preventDefault();gerak.add(tombol[e.key]);}});
document.addEventListener('keyup',e=>gerak.delete(tombol[e.key]));
document.querySelectorAll('#usahaDpad button').forEach(b=>{const arah=b.dataset.dx+','+b.dataset.dz;b.onpointerdown=e=>{b.setPointerCapture(e.pointerId);gerak.add(arah);};b.onpointerup=b.onpointercancel=()=>gerak.delete(arah);});
window.addEventListener('blur',()=>gerak.clear());window.addEventListener('resize',ukuran);
</script>
