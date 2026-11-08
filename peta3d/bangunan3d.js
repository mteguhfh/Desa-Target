// bangunan3d.js
// Pabrik bangunan low-poly prosedural untuk Peta Desa 3D (prototipe v5).
//
// Berkas ini adalah padanan 3D dari includes/bangunan-svg.php di peta 2D:
// satu fungsi pembangun per varian bangunan, dipilih dari kata kunci nama
// bangunan lewat varianBangunan() (aturan katanya sama persis dengan versi
// 2D: kopi/warung, bengkel, kue/roti/toko, salon/barber/cukur, sisanya ruko).
//
// Semua bangunan digambar dari bentuk dasar (kotak, silinder, prisma atap)
// dengan material flat-shaded. Titik asal (0, 0, 0) adalah titik berdiri
// bangunan di atas tanah, fasad menghadap +Z (selatan), sama seperti
// konvensi titik jangkar POSISI_KAVLING di peta 2D. Satuan yang dipakai
// adalah satuan dunia peta (dunia 2000 x 1333), jadi bangunan di sini
// berukuran sebanding dengan Balai Desa versi 4.2 (fasad 184 satuan lokal
// versi 2, atau sekitar 307 satuan dunia).
//
// Cara produksi memakai modul ini kelak: server mengisi daftar bangunan
// dari basis data, lalu untuk tiap bangunan memanggil
// buatBangunan(varianBangunan(nama), warnaAtapGang(gang)) dan menaruhnya
// di POSISI_KAVLING[indeks].

import * as THREE from 'three';

// Paleta material bersama. MeshStandardMaterial flatShading memberi rasa
// mainan low-poly dan tetap murah di GPU.
const cacheMaterial = new Map();
// Registri material jendela menyala: material yang dibuat dengan opsi
// { jendela: true } tercatat di sini beserta intensitas emisif dasarnya,
// supaya peta bisa menaikkan cahaya jendela saat malam dari satu tempat.
export const matJendela = [];
export function matBangunan(hex, opsi = {}) {
    const kunci = hex + '|' + (opsi.emissive || '') + '|' + (opsi.emissiveIntensity || '');
    if (!cacheMaterial.has(kunci)) {
        const bahan = new THREE.MeshStandardMaterial({
            color: hex,
            roughness: 0.9,
            metalness: 0,
            flatShading: true,
            emissive: opsi.emissive || 0x000000,
            emissiveIntensity: opsi.emissiveIntensity || 0,
        });
        if (opsi.jendela) matJendela.push({ bahan, dasar: opsi.emissiveIntensity || 0 });
        cacheMaterial.set(kunci, bahan);
    }
    return cacheMaterial.get(kunci);
}

function kotak(w, h, d, hex, x = 0, y = 0, z = 0, opsi) {
    const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), matBangunan(hex, opsi));
    m.position.set(x, y, z);
    m.castShadow = true;
    m.receiveShadow = true;
    return m;
}

function silinder(rt, rb, h, hex, x = 0, y = 0, z = 0, segmen = 10) {
    const m = new THREE.Mesh(new THREE.CylinderGeometry(rt, rb, h, segmen), matBangunan(hex));
    m.position.set(x, y, z);
    m.castShadow = true;
    return m;
}

// Prisma atap segitiga: profil segitiga di bidang XY diekstrusi sepanjang Z.
// lebar = bentang fasad, tinggi = puncak atap, dalam = kedalaman bangunan.
function prismaAtap(lebar, tinggi, dalam, hex) {
    const bentuk = new THREE.Shape();
    bentuk.moveTo(-lebar / 2, 0);
    bentuk.lineTo(lebar / 2, 0);
    bentuk.lineTo(0, tinggi);
    bentuk.closePath();
    const geo = new THREE.ExtrudeGeometry(bentuk, { depth: dalam, bevelEnabled: false });
    geo.translate(0, 0, -dalam / 2);
    const m = new THREE.Mesh(geo, matBangunan(hex));
    m.castShadow = true;
    m.receiveShadow = true;
    return m;
}

// Tekstur kanvas untuk papan nama: teks besar di atas papan krem berbingkai.
export function teksturPapan(teks, { lebar = 512, tinggi = 128, latar = '#fef3c7', bingkai = '#d97706', warnaTeks = '#92400e', ukuran = 64 } = {}) {
    const c = document.createElement('canvas');
    c.width = lebar; c.height = tinggi;
    const g = c.getContext('2d');
    g.fillStyle = latar; g.fillRect(0, 0, lebar, tinggi);
    g.strokeStyle = bingkai; g.lineWidth = 12;
    g.strokeRect(8, 8, lebar - 16, tinggi - 16);
    g.fillStyle = warnaTeks;
    g.font = '800 ' + ukuran + 'px system-ui, sans-serif';
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.fillText(teks, lebar / 2, tinggi / 2 + 4);
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.SRGBColorSpace;
    t.anisotropy = 4;
    return t;
}

function papanNama(teks, w, h, opsi) {
    const m = new THREE.Mesh(
        new THREE.PlaneGeometry(w, h),
        new THREE.MeshStandardMaterial({ map: teksturPapan(teks, opsi), roughness: 0.85 })
    );
    m.castShadow = true;
    return m;
}

// Aturan kata kunci yang sama dengan varian_bangunan() di bangunan-svg.php.
export function varianBangunan(nama) {
    const n = (nama || '').toLowerCase();
    if (n.includes('balai desa')) return 'balai';
    if (n.includes('pembelajaran') || n.includes('belajar')) return 'sekolah';
    if (n.includes('kopi') || n.includes('warung')) return 'kopi';
    if (n.includes('bengkel')) return 'bengkel';
    if (n.includes('kue') || n.includes('roti') || n.includes('toko')) return 'bakery';
    if (n.includes('salon') || n.includes('barber') || n.includes('cukur')) return 'salon';
    return 'ruko';
}

// ---------------------------------------------------------------------------
// Balai Desa: landmark desa. Fasad lebar, atap dua tingkat amber, pilar,
// balkon kayu, papan nama besar, pintu ganda, jendela menyala lembut.
// Bendera dan tiangnya ditambah oleh peta (perlu animasi gelombang).
// ---------------------------------------------------------------------------
export function buatBalai() {
    const g = new THREE.Group();
    const krem = 0xfff7e6, kremGelap = 0xf3e5c3, amber = 0xf59e0b, amberGelap = 0xd97706, coklat = 0x92400e;

    g.add(kotak(330, 18, 240, 0xe7d8b7, 0, 9, -10));            // plint / podium
    g.add(kotak(300, 120, 210, krem, 0, 78, -20));              // dinding utama (di atas plint)
    // Pilar teras depan
    for (const x of [-128, -44, 44, 128]) {
        g.add(silinder(9, 10, 120, kremGelap, x, 78, 92));
    }
    g.add(kotak(336, 14, 96, amberGelap, 0, 142, 62));          // slab atap teras
    // Atap dua tingkat
    const atap1 = prismaAtap(364, 96, 252, amber); atap1.position.set(0, 138, -20); g.add(atap1);
    const atap2 = prismaAtap(224, 72, 164, amber); atap2.position.set(0, 216, -20); g.add(atap2);
    g.add(kotak(34, 18, 176, 0xb45309, 0, 292, -20));           // balok bubungan
    // Balkon kayu di bawah papan nama
    g.add(kotak(150, 8, 26, coklat, 0, 152, 92));
    for (let i = -3; i <= 3; i++) g.add(kotak(6, 26, 6, coklat, i * 22, 168, 102));
    g.add(kotak(156, 6, 6, coklat, 0, 182, 102));
    // Papan nama besar: duduk di bibir depan slab atap teras (z=113, di depan
    // muka prisma atap z=106) supaya tidak tertutup atap dari sudut kamera
    // depan-atas. Lebar 220 pas dengan lebar segitiga atap di ketinggian ini.
    const papan = papanNama('BALAI DESA', 220, 48, { ukuran: 62 });
    papan.position.set(0, 152, 113);
    g.add(papan);
    // Pintu ganda dan tangga
    g.add(kotak(66, 86, 6, coklat, 0, 61, 88));
    g.add(kotak(4, 86, 8, 0x78350f, 0, 61, 89));
    g.add(kotak(120, 8, 30, 0xd6c294, 0, 22, 104));
    g.add(kotak(140, 8, 30, 0xe7d8b7, 0, 14, 118));
    g.add(kotak(160, 8, 30, 0xefe3c2, 0, 6, 132));
    // Jendela depan menyala lembut
    for (const x of [-102, -58, 58, 102]) {
        g.add(kotak(40, 52, 5, 0xbae6fd, x, 92, 87, { emissive: 0x7dd3fc, emissiveIntensity: 0.5, jendela: true }));
    }
    // Pot bunga di sisi pintu
    for (const x of [-84, 84]) {
        g.add(silinder(11, 8, 16, 0xb45309, x, 26, 96));
        const semak = new THREE.Mesh(new THREE.SphereGeometry(15, 8, 6), matBangunan(0x22c55e));
        semak.position.set(x, 44, 96); semak.castShadow = true; g.add(semak);
    }
    return g;
}

// Rangka rumah toko bersama untuk varian ruko, bakery, salon, bengkel.
function rangkaRuko(warnaAtap, { lebar = 168, tinggi = 108, dalam = 128 } = {}) {
    const g = new THREE.Group();
    g.add(kotak(lebar + 18, 12, dalam + 18, 0xe7d8b7, 0, 6, 0));
    g.add(kotak(lebar, tinggi, dalam, 0xfff7e6, 0, 12 + tinggi / 2, 0));
    const atap = prismaAtap(lebar + 30, 44, dalam + 26, warnaAtap);
    atap.position.set(0, 12 + tinggi, 0);
    g.add(atap);
    // Pintu dan jendela etalase
    g.add(kotak(44, 68, 5, 0x92400e, -lebar / 4, 46, dalam / 2 + 2));
    g.add(kotak(64, 56, 5, 0xbae6fd, lebar / 4, 52, dalam / 2 + 2, { emissive: 0x7dd3fc, emissiveIntensity: 0.35, jendela: true }));
    return g;
}

// ---------------------------------------------------------------------------
// Warung kopi: kedai terbuka, konter kayu, tiang, kanopi warna gang,
// bangku kecil, dan papan bundar bergambar cangkir.
// ---------------------------------------------------------------------------
export function buatKopi(warnaAtap = 0x2563eb) {
    const g = new THREE.Group();
    g.add(kotak(150, 10, 120, 0xe7d8b7, 0, 5, 0));
    g.add(kotak(124, 46, 52, 0xb45309, 0, 33, -18));            // konter
    g.add(kotak(132, 8, 60, 0x92400e, 0, 60, -18));             // meja konter
    g.add(kotak(112, 62, 18, 0x78350f, 0, 41, -52));            // rak belakang
    for (const [x, z] of [[-66, -48], [66, -48], [-66, 48], [66, 48]]) {
        g.add(silinder(4.5, 4.5, 98, 0x92600f, x, 59, z));
    }
    const kanopi = kotak(158, 8, 124, warnaAtap, 0, 112, 0);
    kanopi.rotation.x = -0.1;
    g.add(kanopi);
    for (const x of [-44, 0, 44]) {                              // bangku pengunjung
        g.add(silinder(12, 12, 20, 0xd97706, x, 20, 34, 12));
    }
    // Papan bundar cangkir kopi
    const c = document.createElement('canvas'); c.width = c.height = 128;
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#fef3c7'; ctx.beginPath(); ctx.arc(64, 64, 60, 0, Math.PI * 2); ctx.fill();
    ctx.strokeStyle = '#92400e'; ctx.lineWidth = 7; ctx.stroke();
    ctx.font = '62px system-ui'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText('☕', 64, 70);
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    const papan = new THREE.Mesh(new THREE.CircleGeometry(24, 24), new THREE.MeshStandardMaterial({ map: tex, roughness: 0.85, side: THREE.DoubleSide }));
    papan.position.set(0, 96, 66);
    g.add(papan);
    return g;
}

// ---------------------------------------------------------------------------
// Bengkel: rangka ruko dengan mulut garasi terbuka gelap dan papan nama.
// ---------------------------------------------------------------------------
export function buatBengkel(warnaAtap = 0x0891b2) {
    const g = rangkaRuko(warnaAtap);
    g.add(kotak(96, 74, 6, 0x1f2937, 0, 49, 67));               // mulut garasi gelap
    g.add(kotak(104, 10, 8, 0x475569, 0, 90, 68));              // kusen atas garasi
    g.add(kotak(30, 22, 24, 0x64748b, -58, 23, 84));            // tumpukan ban / krat
    g.add(silinder(13, 13, 10, 0x111827, 58, 17, 84, 14));      // ban berdiri
    const papan = papanNama('BENGKEL', 150, 38, { ukuran: 58 });
    papan.position.set(0, 138, 66);
    g.add(papan);
    return g;
}

// ---------------------------------------------------------------------------
// Toko roti / bakery: rangka ruko, tenda etalase bergaris, papan nama.
// ---------------------------------------------------------------------------
export function buatBakery(warnaAtap = 0xea580c) {
    const g = rangkaRuko(warnaAtap);
    // Tenda bergaris dari tekstur kanvas
    const c = document.createElement('canvas'); c.width = 256; c.height = 64;
    const ctx = c.getContext('2d');
    for (let i = 0; i < 8; i++) { ctx.fillStyle = i % 2 ? '#fff7e6' : '#f472b6'; ctx.fillRect(i * 32, 0, 32, 64); }
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    const tenda = new THREE.Mesh(new THREE.BoxGeometry(120, 6, 44), new THREE.MeshStandardMaterial({ map: tex, roughness: 0.9 }));
    tenda.position.set(30, 92, 80); tenda.rotation.x = 0.35;
    tenda.castShadow = true;
    g.add(tenda);
    const papan = papanNama('TOKO ROTI', 170, 38, { ukuran: 54 });
    papan.position.set(0, 138, 66);
    g.add(papan);
    return g;
}

// ---------------------------------------------------------------------------
// Salon: rangka ruko dengan tiang barber bergaris yang ikonik.
// ---------------------------------------------------------------------------
export function buatSalon(warnaAtap = 0xdb2777) {
    const g = rangkaRuko(warnaAtap);
    const c = document.createElement('canvas'); c.width = 32; c.height = 128;
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, 32, 128);
    ctx.fillStyle = '#dc2626';
    for (let i = -1; i < 5; i++) { ctx.save(); ctx.translate(16, i * 32); ctx.rotate(0.5); ctx.fillRect(-24, -6, 48, 12); ctx.restore(); }
    ctx.fillStyle = '#2563eb';
    for (let i = -1; i < 5; i++) { ctx.save(); ctx.translate(16, i * 32 + 16); ctx.rotate(0.5); ctx.fillRect(-24, -6, 48, 12); ctx.restore(); }
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
    const tiang = new THREE.Mesh(new THREE.CylinderGeometry(9, 9, 52, 14), new THREE.MeshStandardMaterial({ map: tex, roughness: 0.6 }));
    tiang.position.set(-64, 44, 76); tiang.castShadow = true;
    g.add(tiang);
    g.add(silinder(11, 11, 6, 0xfacc15, -64, 73, 76, 14));
    g.add(silinder(11, 11, 6, 0xfacc15, -64, 15, 76, 14));
    const papan = papanNama('SALON', 130, 38, { ukuran: 58 });
    papan.position.set(0, 138, 66);
    g.add(papan);
    return g;
}

// ---------------------------------------------------------------------------
// Ruko umum: rangka ruko dengan huruf awal nama sebagai papan nama.
// ---------------------------------------------------------------------------
export function buatRuko(warnaAtap = 0xf59e0b, nama = 'Ruko') {
    const g = rangkaRuko(warnaAtap);
    const huruf = (nama || 'R').trim().charAt(0).toUpperCase() || 'R';
    const papan = papanNama(nama ? nama.slice(0, 14) : 'RUKO', 170, 38, { ukuran: 50 });
    papan.position.set(0, 138, 66);
    g.add(papan);
    return g;
}

// Pemilih tunggal untuk jalur produksi: nama bangunan -> model 3D.
export function buatBangunan(nama, warnaAtap) {
    const v = varianBangunan(nama);
    switch (v) {
        case 'balai': return buatBalai();
        case 'kopi': return buatKopi(warnaAtap);
        case 'bengkel': return buatBengkel(warnaAtap);
        case 'bakery': return buatBakery(warnaAtap);
        case 'salon': return buatSalon(warnaAtap);
        default: return buatRuko(warnaAtap, nama);
    }
}

// ---------------------------------------------------------------------------
// Tenda lapak Pasar Malam dan CFD. Geometri murah: dua tiang, kanopi
// segitiga, dan meja kayu; semua material sama supaya tenda-tenda bisa
// digambar dari satu kelompok tanpa renderer kedua.
// ---------------------------------------------------------------------------
export function buatTenda(warnaKanopi = 0xef4444) {
    const g = new THREE.Group();
    const bahanTiang = matBangunan(0x78350f);
    g.add(kotak(8, 96, 8, 0x78350f, -54, 48, -34));
    g.add(kotak(8, 96, 8, 0x78350f, 54, 48, -34));
    g.add(kotak(8, 96, 8, 0x78350f, -54, 48, 34));
    g.add(kotak(8, 96, 8, 0x78350f, 54, 48, 34));
    const kanopi = prismaAtap(150, 46, 100, warnaKanopi);
    kanopi.position.set(0, 96, 0);
    g.add(kanopi);
    g.add(kotak(126, 34, 52, 0xe7d8b7, 0, 17, 12));
    g.add(kotak(118, 6, 46, 0xfef3c7, 0, 36, 12));
    return g;
}
