<section id="interiorUsaha" class="interior-3d" hidden aria-label="Interior usaha">
    <div id="usahaKanvas" class="interior-3d-kanvas"></div>
    <h2 id="usahaJudul" class="usaha-judul">Pasar Desa</h2>
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
function kotak(w,h,d,warna,x,y,z) {
    const benda = new THREE.Mesh(new THREE.BoxGeometry(w,h,d), new THREE.MeshStandardMaterial({color:warna}));
    benda.position.set(x,y,z); scene.add(benda); return benda;
}
function ukuran() { if (!renderer || panel.hidden) return; renderer.setSize(panel.clientWidth,panel.clientHeight); camera.aspect=panel.clientWidth/panel.clientHeight; camera.updateProjectionMatrix(); }
function tutup() { panel.hidden=true; gerak.clear(); cancelAnimationFrame(frame); pemicu?.focus(); }
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
window.__usaha = { buka(nama, pasar=false) {
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
    panel.hidden=false;ukuran();sebelumnya=performance.now();frame=requestAnimationFrame(gambar);document.getElementById('usahaKeluar').focus();
}, tutup, posisi: () => figur ? {x: figur.position.x, z: figur.position.z} : null };
document.getElementById('usahaKeluar').onclick=tutup;
const tombol={ArrowUp:'0,-1',w:'0,-1',ArrowDown:'0,1',s:'0,1',ArrowLeft:'-1,0',a:'-1,0',ArrowRight:'1,0',d:'1,0'};
document.addEventListener('keydown',e=>{if(panel.hidden)return;if(e.key==='Escape'){tutup();return;}if(tombol[e.key]){e.preventDefault();gerak.add(tombol[e.key]);}});
document.addEventListener('keyup',e=>gerak.delete(tombol[e.key]));
document.querySelectorAll('#usahaDpad button').forEach(b=>{const arah=b.dataset.dx+','+b.dataset.dz;b.onpointerdown=e=>{b.setPointerCapture(e.pointerId);gerak.add(arah);};b.onpointerup=b.onpointercancel=()=>gerak.delete(arah);});
window.addEventListener('blur',()=>gerak.clear());window.addEventListener('resize',ukuran);
</script>
