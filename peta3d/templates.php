<?php
// Galeri template bangunan 3D. Sama seperti index.php: berkas ini PHP supaya
// penanda versi bangunan3d.js dihitung otomatis dari filemtime.
$versiBangunan3d = (int) (@filemtime(__DIR__ . '/bangunan3d.js') ?: 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Template Bangunan 3D Desa Target</title>
<style>
    html, body { margin: 0; height: 100%; overflow: hidden; font-family: system-ui, sans-serif; background: #bae6fd; }
    #panggung { position: fixed; inset: 0; }
    .judul {
        position: fixed; top: 14px; left: 14px; z-index: 5;
        background: #fff; border-radius: 12px; padding: 12px 16px;
        box-shadow: 0 6px 20px rgba(30,58,138,0.2); max-width: 400px;
    }
    .judul h1 { margin: 0 0 4px; font-size: 17px; }
    .judul p { margin: 0; font-size: 13px; color: #4b5563; line-height: 1.5; }
</style>
<script type="importmap">
{
    "imports": {
        "three": "./vendor/three.module.js",
        "three/addons/": "./vendor/addons/"
    }
}
</script>
</head>
<body>
<div id="panggung"></div>
<div class="judul">
    <h1>Template Bangunan 3D</h1>
    <p>Lima varian bangunan prosedural dari bangunan3d.js, dipilih otomatis dari kata kunci nama bangunan, sama seperti aturan varian di peta 2D. Warna atap mengikuti gang pemilik bangunan. Seret buat memutar kamera.</p>
</div>
<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { buatBalai, buatKopi, buatBengkel, buatBakery, buatSalon, buatRuko, varianBangunan } from './bangunan3d.js?v=<?= $versiBangunan3d ?>';

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
document.getElementById('panggung').appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.background = new THREE.Color(0xbae6fd);
scene.fog = new THREE.Fog(0xbae6fd, 1600, 3200);
const camera = new THREE.PerspectiveCamera(42, window.innerWidth / window.innerHeight, 1, 8000);
camera.position.set(240, 620, 1180);
const controls = new OrbitControls(camera, renderer.domElement);
controls.target.set(0, 60, 0);
controls.enableDamping = true;
controls.maxPolarAngle = 1.4;
controls.update();

scene.add(new THREE.HemisphereLight(0xeaf6ff, 0x86c06c, 0.95));
const matahari = new THREE.DirectionalLight(0xfff1d6, 1.7);
matahari.position.set(-500, 800, -350);
matahari.castShadow = true;
matahari.shadow.mapSize.set(2048, 2048);
matahari.shadow.camera.left = -1100; matahari.shadow.camera.right = 1100;
matahari.shadow.camera.top = 800; matahari.shadow.camera.bottom = -800;
matahari.shadow.bias = -0.0006;
scene.add(matahari);

const tanah = new THREE.Mesh(new THREE.CircleGeometry(1150, 48), new THREE.MeshStandardMaterial({ color: 0x8fd07a, roughness: 1 }));
tanah.rotation.x = -Math.PI / 2;
tanah.receiveShadow = true;
scene.add(tanah);

function labelNama(teks) {
    const c = document.createElement('canvas'); c.width = 384; c.height = 80;
    const g = c.getContext('2d');
    g.font = '800 40px system-ui, sans-serif';
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.lineWidth = 9; g.strokeStyle = 'rgba(17,24,39,0.65)';
    g.strokeText(teks, 192, 42);
    g.fillStyle = '#ffffff';
    g.fillText(teks, 192, 42);
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
    s.scale.set(190, 40, 1);
    return s;
}

// Barisan contoh: tiap bangunan dipanggil lewat jalur yang sama dengan
// produksi kelak (nama -> varian -> model, warna atap = warna gang contoh).
const contoh = [
    { nama: 'Balai Desa', gang: 0xf59e0b },
    { nama: 'Warung Kopi Senja', gang: 0x2563eb },
    { nama: 'Bengkel Berkah Motor', gang: 0x0891b2 },
    { nama: 'Toko Roti Manis', gang: 0xea580c },
    { nama: 'Salon Ayu', gang: 0xdb2777 },
    { nama: 'Ruko Serba Ada', gang: 0x7c3aed },
];
const pembangun = { balai: () => buatBalai(), kopi: buatKopi, bengkel: buatBengkel, bakery: buatBakery, salon: buatSalon, ruko: (w, n) => buatRuko(w, n) };
contoh.forEach((c, i) => {
    const x = (i - (contoh.length - 1) / 2) * 340;
    const varian = varianBangunan(c.nama);
    const model = pembangun[varian](c.gang, c.nama);
    model.position.set(x, 0, varian === 'balai' ? -160 : 60);
    scene.add(model);
    const label = labelNama(varian.toUpperCase());
    label.position.set(x, varian === 'balai' ? 400 : 235, model.position.z);
    scene.add(label);
});

window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});
const jam = new THREE.Clock();
(function putaran() {
    requestAnimationFrame(putaran);
    controls.update();
    renderer.render(scene, camera);
})();
</script>
</body>
</html>
