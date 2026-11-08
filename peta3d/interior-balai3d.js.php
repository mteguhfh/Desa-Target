<?php
// Mesin ruang 3D Balai Desa (Kantor Desa) untuk mode Keliling Desa.
//
// Ruangannya dibangun di kanvas Three.js sendiri, terpisah dari dunia desa di
// luar, supaya berkas ini bisa membaca berulang tanpa mengganggu peta: dunia
// desa tidak perlu tahu-menahu soal interior, dan sebaliknya.
//
// Prinsip yang sama dengan luar: FIGUR jalan di lantai, kamera mengikuti dari
// belakang bahu, dan "maju" berarti ke arah pandang kamera. Bedanya hanya
// ukurannya: ruang 1200 x 750 cm, jadi langkah terasa di dalam gedung.
//
// Berkas ini hanya mengeluarkan <script>; markupnya di interior-balai.php.
?>
<script type="module">
// Three.js dimuat sebagai modul ES oleh peta (importmap di atas). Ruangan
// memakai pustaka yang sama lewat import, bukan variabel global -- peta tidak
// mengekspor THREE ke window.
import * as THREE from 'three';

(function () {
    'use strict';

    var kanvasInduk = document.getElementById('interior3dKanvas');
    var panelRuang = document.getElementById('interior3d');
    var kotakTitik = document.getElementById('interior3dTitik');
    if (!kanvasInduk || !panelRuang || !kotakTitik) return;

    // ------------------------------------------------------------------
    // Ukuran ruang (cm). Lantai 1200 x 750 dengan dinding setinggi 330.
    // ------------------------------------------------------------------
    var LEBAR = 1200, DALAM = 750, TINGGI = 330;
    var X_MIN = -LEBAR / 2 + 34, X_MAKS = LEBAR / 2 - 34;   // tepi jalan
    var Z_MIN = -DALAM / 2 + 34, Z_MAKS = DALAM / 2 - 34;

    // Posisi empat titik penting. Dipakai dua kali: menaruh objek 3D dan
    // menempelkan tombol 2D di atasnya.
    var TITIK = {
        loket: { x: -330, z: -DALAM / 2 + 40, nama: 'Loket KTP Warga' },
        papan: { x: 60, z: -DALAM / 2 + 30, nama: 'Papan Pengumuman' },
        arsip: { x: 330, z: -DALAM / 2 + 40, nama: 'Arsip Dokumen Warga' },
        uju: { x: 0, z: -120, nama: 'Bang Uju' }
    };

    var renderer, scene, camera, dunia;
    var siap = false, terbuka = false, sebelumnya = 0;
    var figur = null;
    var titik3d = {};        // id -> Vector3 posisi label di dunia
    var tombolTitik = {};    // id -> elemen tombol 2D
    var cahayaRuang = [];

    // Pemain di dalam ruangan.
    var kamu = { x: 0, z: DALAM / 2 - 70, arah: Math.PI };
    // Kamera: yaw mengikuti arah pemain, bisa digeser seret.
    var seret = { aktif: false, x: 0, geser: 0 };

    // Kumpulan tombol yang sedang ditekan (keyboard + D-pad).
    var tombolNyala = {};
    var ditekan = {};

    // ------------------------------------------------------------------
    // Bahan dan pembantu bentuk
    // ------------------------------------------------------------------
    function bahan(warna, opsi) {
        var o = opsi || {};
        return new THREE.MeshStandardMaterial({
            color: warna,
            roughness: o.roughness !== undefined ? o.roughness : 0.9,
            metalness: 0,
            flatShading: o.halus ? false : true,
            map: o.map || null,
            emissive: o.emissive || 0x000000,
            emissiveIntensity: o.emissiveIntensity !== undefined ? o.emissiveIntensity : 1,
            transparent: !!o.transparan,
            opacity: o.opacity !== undefined ? o.opacity : 1
        });
    }

    function kotak(w, h, d, mat, x, y, z) {
        var m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
        m.position.set(x, y, z);
        m.castShadow = true;
        m.receiveShadow = true;
        return m;
    }

    function tabung(r, h, mat, x, y, z, segmen) {
        var m = new THREE.Mesh(new THREE.CylinderGeometry(r, r, h, segmen || 14), mat);
        m.position.set(x, y, z);
        m.castShadow = true;
        m.receiveShadow = true;
        return m;
    }

    // Tekstur papan nama: teks digambar di kanvas, supaya tulisan tetap
    // tajam tanpa memuat font tambahan.
    function teksturPapan(judul, warnaTeks, warnaLatar) {
        var c = document.createElement('canvas');
        c.width = 512; c.height = 128;
        var g = c.getContext('2d');
        g.fillStyle = warnaLatar;
        g.fillRect(0, 0, c.width, c.height);
        g.strokeStyle = 'rgba(0,0,0,0.14)';
        g.lineWidth = 8;
        g.strokeRect(6, 6, c.width - 12, c.height - 12);
        g.fillStyle = warnaTeks;
        g.font = '700 58px system-ui, sans-serif';
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillText(judul, c.width / 2, c.height / 2 + 4);
        var t = new THREE.CanvasTexture(c);
        t.colorSpace = THREE.SRGBColorSpace;
        t.anisotropy = 4;
        return t;
    }

    function papan(judul, lebar, tinggi, x, y, z, warnaTeks, warnaLatar) {
        var m = new THREE.Mesh(
            new THREE.BoxGeometry(lebar, tinggi, 6),
            bahan(0xffffff, { map: teksturPapan(judul, warnaTeks, warnaLatar) })
        );
        m.position.set(x, y, z);
        m.castShadow = true;
        return m;
    }

    // ------------------------------------------------------------------
    // Membangun ruangan
    // ------------------------------------------------------------------
    function bangun() {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        kanvasInduk.appendChild(renderer.domElement);

        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf4e3c8);

        camera = new THREE.PerspectiveCamera(62, 1, 1, 4000);

        dunia = new THREE.Group();
        scene.add(dunia);

        // --- Cahaya: matahari siang dari jendela, ditambah lampu ruangan ---
        scene.add(new THREE.HemisphereLight(0xfff7e8, 0x8b5e34, 1.05));

        var matahari = new THREE.DirectionalLight(0xfff1d6, 1.35);
        matahari.position.set(-420, 700, 520);
        matahari.castShadow = true;
        matahari.shadow.mapSize.set(1024, 1024);
        matahari.shadow.camera.left = -800;
        matahari.shadow.camera.right = 800;
        matahari.shadow.camera.top = 800;
        matahari.shadow.camera.bottom = -800;
        matahari.shadow.camera.far = 2600;
        matahari.shadow.bias = -0.0012;
        scene.add(matahari);

        [0xffffff, 0xfff3dc].forEach(function (warna, i) {
            var lampu = new THREE.PointLight(warna, 0.55, 1500, 1.4);
            lampu.position.set(i ? 330 : -330, TINGGI - 40, 60);
            scene.add(lampu);
            cahayaRuang.push(lampu);
        });

        // --- Lantai papan kayu ---
        var lantai = kotak(LEBAR, 12, DALAM, bahan(0xc98a4b, { roughness: 1 }),
            0, -6, 0);
        dunia.add(lantai);
        // Garis papan: papan tipis sejajar, supaya lantai terasa bersendi.
        var matGaris = bahan(0xb57a3e, { roughness: 1 });
        for (var z = -DALAM / 2 + 40; z < DALAM / 2; z += 60) {
            dunia.add(kotak(LEBAR - 8, 1.2, 2.4, matGaris, 0, 0.8, z));
        }

        // --- Dinding ---
        var matDinding = bahan(0xfdeeda, { roughness: 1 });
        var matLisKayu = bahan(0xefd3ac, { roughness: 1 });
        var matBawah = bahan(0xd9b77c, { roughness: 1 });

        // Dinding belakang (yang dilihat pemain saat masuk).
        dunia.add(kotak(LEBAR, TINGGI, 14, matDinding, 0, TINGGI / 2, -DALAM / 2));
        // Lis kayu bawah dinding belakang.
        dunia.add(kotak(LEBAR, 110, 4, matBawah, 0, 55, -DALAM / 2 + 9));
        dunia.add(kotak(LEBAR, 5, 6, matLisKayu, 0, 112, -DALAM / 2 + 11));

        // Dinding kiri & kanan.
        dunia.add(kotak(14, TINGGI, DALAM, matDinding, -LEBAR / 2, TINGGI / 2, 0));
        dunia.add(kotak(14, TINGGI, DALAM, matDinding, LEBAR / 2, TINGGI / 2, 0));
        dunia.add(kotak(4, 110, DALAM, matBawah, -LEBAR / 2 + 9, 55, 0));
        dunia.add(kotak(4, 110, DALAM, matBawah, LEBAR / 2 - 9, 55, 0));

        // Dinding depan, dengan pintu keluar di tengah.
        var lebarPintu = 220;
        var lebarSisi = (LEBAR - lebarPintu) / 2;
        // Kiri dan kanan pintu.
        dunia.add(kotak(lebarSisi, TINGGI, 14, matDinding,
            -(lebarPintu / 2 + lebarSisi / 2), TINGGI / 2, DALAM / 2));
        dunia.add(kotak(lebarSisi, TINGGI, 14, matDinding,
            (lebarPintu / 2 + lebarSisi / 2), TINGGI / 2, DALAM / 2));
        // Ambang atas pintu.
        dunia.add(kotak(lebarPintu, TINGGI - 260, 14, matDinding, 0, 260 + (TINGGI - 260) / 2, DALAM / 2));
        // Cahaya dari luar pintu, supaya jalan keluar terasa mengundang.
        dunia.add(kotak(lebarPintu - 10, 250, 4, bahan(0xe0f2fe, { emissive: 0xbae6fd, emissiveIntensity: 0.75 }), 0, 125, DALAM / 2 - 2));

        // Langit-langit.
        dunia.add(kotak(LEBAR, 10, DALAM, bahan(0xfffaf0, { roughness: 1 }), 0, TINGGI, 0));

        // --- Bendera hias merentang di langit-langit ---
        var warnaHias = [0x2563eb, 0xf59e0b, 0x22c55e, 0xdb2777];
        var kabel = new THREE.Mesh(
            new THREE.TubeGeometry(new THREE.CatmullRomCurve3([
                new THREE.Vector3(-LEBAR / 2 + 20, TINGGI - 30, -180),
                new THREE.Vector3(-200, TINGGI - 82, -150),
                new THREE.Vector3(200, TINGGI - 70, -120),
                new THREE.Vector3(LEBAR / 2 - 20, TINGGI - 26, -160)
            ]), 24, 1.4, 6, false),
            bahan(0xd97706)
        );
        dunia.add(kabel);
        for (var i = 0; i < 12; i++) {
            var t = i / 11;
            var bx = -LEBAR / 2 + 40 + t * (LEBAR - 80);
            var by = TINGGI - 34 - Math.sin(t * Math.PI) * 42;
            var bz = -180 + Math.sin(t * Math.PI) * 50;
            var bendera = new THREE.Mesh(
                new THREE.ConeGeometry(20, 34, 4),
                bahan(warnaHias[i % 4], { roughness: 0.8 })
            );
            bendera.rotation.x = Math.PI;
            bendera.rotation.y = Math.PI / 4;
            bendera.position.set(bx, by - 17, bz);
            dunia.add(bendera);
        }

        // --- Papan nama KANTOR DESA di dinding belakang ---
        var papanNama = papan('KANTOR DESA', 330, 62, 0, 262, -DALAM / 2 + 12, '#fff7e6', '#92600f');
        dunia.add(papanNama);
        dunia.add(kotak(6, 60, 6, bahan(0x92600f), -110, 315, -DALAM / 2 + 8));
        dunia.add(kotak(6, 60, 6, bahan(0x92600f), 110, 315, -DALAM / 2 + 8));

        // --- Jam dinding ---
        var jam = new THREE.Group();
        var mukaJam = tabung(30, 6, bahan(0xffffff), 0, 0, 0, 32);
        mukaJam.rotation.x = Math.PI / 2;
        jam.add(mukaJam);
        var cincin = new THREE.Mesh(new THREE.TorusGeometry(30, 4, 8, 32), bahan(0xb45309));
        jam.add(cincin);
        var jarumPanjang = kotak(3, 18, 2, bahan(0x334155), 0, 9, 4);
        var jarumPendek = kotak(3, 12, 2, bahan(0x334155), 6, 4, 4);
        jarumPendek.rotation.z = -Math.PI / 4;
        jam.add(jarumPanjang, jarumPendek);
        jam.position.set(208, 210, -DALAM / 2 + 10);
        dunia.add(jam);

        // --- Dua jendela dengan cahaya siang ---
        [-330, 330].forEach(function (jx) {
            dunia.add(kotak(190, 190, 6, bahan(0xffffff, { roughness: 0.7 }), jx, 200, -DALAM / 2 + 10));
            var kaca = new THREE.Mesh(
                new THREE.BoxGeometry(170, 170, 4),
                bahan(0xbfe6fb, { emissive: 0x9fd7f7, emissiveIntensity: 0.9, roughness: 0.2 })
            );
            kaca.position.set(jx, 200, -DALAM / 2 + 13);
            dunia.add(kaca);
            // Palang jendela.
            dunia.add(kotak(6, 170, 8, bahan(0xffffff), jx, 200, -DALAM / 2 + 16));
            dunia.add(kotak(170, 6, 8, bahan(0xffffff), jx, 200, -DALAM / 2 + 16));
            // Tirai.
            dunia.add(kotak(22, 200, 10, bahan(0xf1c27d), jx - 92, 196, -DALAM / 2 + 18));
            dunia.add(kotak(22, 200, 10, bahan(0xf1c27d), jx + 92, 196, -DALAM / 2 + 18));
        });

        // --- Meja layanan Bang Uju ---
        var meja = new THREE.Group();
        meja.add(kotak(300, 18, 110, bahan(0xb45309), 0, 96, 0));
        meja.add(kotak(286, 86, 96, bahan(0x92400e), 0, 48, 0));
        meja.add(kotak(270, 4, 4, bahan(0xd97706), 0, 66, 50));
        for (var s = 0; s < 3; s++) {
            meja.add(kotak(84, 66, 3, bahan(0xa16207), -92 + s * 92, 44, 50));
        }
        meja.position.set(TITIK.uju.x, 0, TITIK.uju.z + 55);
        dunia.add(meja);

        // Kertas & komputer kecil di atas meja.
        dunia.add(kotak(46, 2, 32, bahan(0xfefce8), -60, 106, TITIK.uju.z + 40));
        var layar = kotak(54, 36, 4, bahan(0x334155), 64, 126, TITIK.uju.z + 42);
        dunia.add(layar);
        dunia.add(kotak(8, 12, 8, bahan(0x475569), 64, 108, TITIK.uju.z + 42));

        // --- Bang Uju di belakang meja ---
        var uju = buatFigurBundar(0x1d4ed8, 0xffffff, 0xdc2626);
        uju.position.set(TITIK.uju.x, 0, TITIK.uju.z - 40);
        uju.rotation.y = 0;
        dunia.add(uju);
        figurUju = uju;

        // --- Loket KTP di kiri ---
        var loket = new THREE.Group();
        loket.add(kotak(300, 20, 96, bahan(0x2563eb), 0, 104, 0));
        loket.add(kotak(286, 100, 82, bahan(0x1e40af), 0, 50, 0));
        loket.add(kotak(250, 46, 4, bahan(0x1e3a8a), 0, 50, 42));
        loket.position.set(TITIK.loket.x, 0, TITIK.loket.z + 56);
        dunia.add(loket);
        dunia.add(kotak(44, 2, 30, bahan(0xf8fafc), -52, 116, TITIK.loket.z + 40));
        var kartu = kotak(30, 1.6, 20, bahan(0xbfdbfe), -52, 118, TITIK.loket.z + 40);
        dunia.add(kartu);
        dunia.add(kotak(40, 28, 3, bahan(0x334155), 56, 120, TITIK.loket.z + 42));

        // Papan gantung "LOKET KTP WARGA" di atas loket.
        dunia.add(papan('LOKET KTP WARGA', 230, 46, TITIK.loket.x, 216, TITIK.loket.z + 50, '#ffffff', '#2563eb'));
        dunia.add(kotak(4, 60, 4, bahan(0x1e3a8a), TITIK.loket.x - 90, 264, TITIK.loket.z + 50));
        dunia.add(kotak(4, 60, 4, bahan(0x1e3a8a), TITIK.loket.x + 90, 264, TITIK.loket.z + 50));

        // --- Papan pengumuman di tengah ---
        var papanUtama = new THREE.Group();
        papanUtama.add(kotak(260, 180, 10, bahan(0x7c2d12), 0, 0, 0));
        papanUtama.add(kotak(240, 160, 6, bahan(0xfef3c7), 0, 0, 6));
        // Tiga lembar kertas kecil berwarna.
        [[0xffffff, -72], [0xfde68a, 0], [0xffffff, 72]].forEach(function (kertas, idx) {
            var lembar = kotak(60, 80, 2, bahan(kertas[0]), kertas[1], 14, 10);
            lembar.rotation.z = (idx - 1) * 0.05;
            papanUtama.add(lembar);
            for (var l = 0; l < 4; l++) {
                papanUtama.add(kotak(40, 2.4, 1, bahan(idx === 1 ? 0xb45309 : 0x9ca3af),
                    kertas[1], 34 - l * 13, 11.5));
            }
            var paku = new THREE.Mesh(new THREE.SphereGeometry(3.6, 8, 8), bahan(idx === 1 ? 0xdc2626 : 0x2563eb));
            paku.position.set(kertas[1], 58, 12);
            papanUtama.add(paku);
        });
        papanUtama.position.set(TITIK.papan.x, 190, TITIK.papan.z + 14);
        dunia.add(papanUtama);
        dunia.add(papan('PAPAN PENGUMUMAN', 260, 40, TITIK.papan.x, 296, TITIK.papan.z + 12, '#fff7e6', '#7c2d12'));

        // --- Arsip Dokumen Warga di kanan ---
        var arsip = new THREE.Group();
        arsip.add(kotak(240, 250, 90, bahan(0x7c2d12), 0, 125, 0));
        for (var d2 = 0; d2 < 4; d2++) {
            arsip.add(kotak(214, 50, 6, bahan(0x9a3412), 0, 34 + d2 * 58, 46));
            arsip.add(kotak(60, 7, 8, bahan(0xd97706), 0, 34 + d2 * 58, 51));
        }
        arsip.position.set(TITIK.arsip.x, 0, TITIK.arsip.z + 70);
        dunia.add(arsip);
        dunia.add(papan('ARSIP DOKUMEN', 230, 42, TITIK.arsip.x, 296, TITIK.arsip.z + 66, '#ffffff', '#0f766e'));

        // --- Tanaman di pojok ---
        [[-560, 300], [560, 300], [-560, -300], [560, -300]].forEach(function (p) {
            var pot = new THREE.Mesh(new THREE.CylinderGeometry(26, 18, 44, 10), bahan(0xb45309));
            pot.position.set(p[0], 22, p[1]);
            pot.castShadow = true;
            dunia.add(pot);
            var rumpun = new THREE.Mesh(new THREE.SphereGeometry(34, 10, 8), bahan(0x16a34a));
            rumpun.position.set(p[0], 76, p[1]);
            rumpun.castShadow = true;
            dunia.add(rumpun);
        });

        // --- Titik label 3D (posisi untuk ditempeli tombol 2D) ---
        titik3d.loket = new THREE.Vector3(TITIK.loket.x, 286, TITIK.loket.z + 50);
        titik3d.papan = new THREE.Vector3(TITIK.papan.x, 330, TITIK.papan.z + 12);
        titik3d.arsip = new THREE.Vector3(TITIK.arsip.x, 336, TITIK.arsip.z + 66);
        titik3d.uju = new THREE.Vector3(TITIK.uju.x, 210, TITIK.uju.z - 40);

        // --- Pemain ---
        figur = buatFigurBundar(0x0d9488, 0xfef3c7, 0xdc2626);
        dunia.add(figur);

        siap = true;
        ukur();
    }

    var figurUju = null;

    // Figur bundar bergaya desa: sama seperti tokoh di peta, sangat sederhana
    // supaya ringan. Tinggi total sekitar 170 cm.
    function buatFigurBundar(warnaBadan, warnaWajah, warnaTopi) {
        var g = new THREE.Group();
        var badan = new THREE.Mesh(new THREE.CylinderGeometry(34, 44, 92, 12), bahan(warnaBadan));
        badan.position.y = 46;
        badan.castShadow = true;
        g.add(badan);
        var kepala = new THREE.Mesh(new THREE.SphereGeometry(36, 14, 12), bahan(warnaWajah));
        kepala.position.y = 124;
        kepala.castShadow = true;
        g.add(kepala);
        var topi = new THREE.Mesh(new THREE.CylinderGeometry(30, 38, 12, 14), bahan(warnaTopi));
        topi.position.y = 156;
        topi.castShadow = true;
        g.add(topi);
        // Mata sederhana supaya punya arah hadap.
        [-12, 12].forEach(function (mx) {
            var mata = new THREE.Mesh(new THREE.SphereGeometry(4.5, 8, 8), bahan(0x1f2937));
            mata.position.set(mx, 128, 32);
            g.add(mata);
        });
        return g;
    }

    // ------------------------------------------------------------------
    // Ukuran kanvas & proyeksi label
    // ------------------------------------------------------------------
    function ukur() {
        if (!siap) return;
        var w = kanvasInduk.clientWidth || window.innerWidth;
        var h = kanvasInduk.clientHeight || window.innerHeight;
        renderer.setSize(w, h, false);
        camera.aspect = w / Math.max(h, 1);
        camera.updateProjectionMatrix();
    }

    // Tempatkan tombol 2D di atas titik 3D-nya. Titik yang membelakangi kamera
    // atau terlalu jauh disembunyikan, seperti nama tempat di game peta.
    var vektorSementara = new THREE.Vector3();
    var arahPandang = new THREE.Vector3();
    function perbaruiTitik() {
        camera.getWorldDirection(arahPandang);
        var w = kanvasInduk.clientWidth || window.innerWidth;
        var h = kanvasInduk.clientHeight || window.innerHeight;
        Object.keys(titik3d).forEach(function (id) {
            var el = tombolTitik[id];
            if (!el) return;
            // "Di depan kamera" diuji lewat arah pandang, bukan dari z hasil
            // proyeksi. Dengan far-plane 4000 dan objek hanya 4 m di depan,
            // z hasil proyeksi tetap ~1,006 -- nyaris tidak bisa dibedakan
            // dari objek di belakang.
            vektorSementara.copy(titik3d[id]).sub(camera.position);
            var diDepan = vektorSementara.dot(arahPandang) > 0;
            vektorSementara.copy(titik3d[id]).project(camera);
            var x = (vektorSementara.x * 0.5 + 0.5) * w;
            var y = (-vektorSementara.y * 0.5 + 0.5) * h;
            var jarak = Math.hypot(titik3d[id].x - kamu.x, titik3d[id].z - kamu.z);
            // Label tetap terlihat di seluruh ruangan (jarak maksimum ruang
            // ~1400), lalu disorot saat pemain sudah dekat. Tombol yang
            // membuka kartu hanya aktif saat dekat.
            var terlihat = jarak < 1200;
            el.style.transform = 'translate(-50%, -100%) translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px)';
            var dekat = jarak < 340;
            el.classList.toggle('tersembunyi', !diDepan || !terlihat);
            el.classList.toggle('dekat', dekat);
            el.setAttribute('aria-hidden', diDepan && terlihat ? 'false' : 'true');
            // Tab bisa mencapai semua label yang terlihat; yang jauh tetap
            // bisa diketuk karena tombolnya terlihat (ruangan kecil).
            el.tabIndex = diDepan && terlihat ? 0 : -1;
        });
    }

    // ------------------------------------------------------------------
    // Gerak pemain
    // ------------------------------------------------------------------
    function tabrakan(x, z) {
        if (x < X_MIN || x > X_MAKS || z < Z_MIN || z > Z_MAKS) return true;
        // Meja layanan, loket, dan lemari arsip tidak bisa ditembus.
        var rintangan = [
            { x1: TITIK.uju.x - 152, x2: TITIK.uju.x + 152, z1: TITIK.uju.z - 10, z2: TITIK.uju.z + 118 },
            { x1: TITIK.loket.x - 152, x2: TITIK.loket.x + 152, z1: TITIK.loket.z - 24, z2: TITIK.loket.z + 112 },
            { x1: TITIK.arsip.x - 122, x2: TITIK.arsip.x + 122, z1: TITIK.arsip.z + 22, z2: TITIK.arsip.z + 118 }
        ];
        return rintangan.some(function (r) {
            return x > r.x1 && x < r.x2 && z > r.z1 && z < r.z2;
        });
    }

    // Arah jalan dikunci saat tombol berubah, sama seperti di luar ruangan.
    var sudutTerkunci = { kunci: null, nilai: 0 };
    function arahKameraYaw() {
        return kamu.arah + seret.geser;
    }

    function gerak(dt) {
        var maju = (ditekan.w ? 1 : 0) - (ditekan.s ? 1 : 0);
        var samping = (ditekan.d ? 1 : 0) - (ditekan.a ? 1 : 0);
        if (!maju && !samping) { sudutTerkunci.kunci = null; return; }
        var kunci = maju + ':' + samping;
        if (sudutTerkunci.kunci !== kunci) {
            var yaw = arahKameraYaw();
            // Ruangan ini memakai arah 0 = menghadap -z (ke dinding belakang
            // tempat meja layanan). Kamera berada di +z dari pemain.
            var depanX = -Math.sin(yaw), depanZ = -Math.cos(yaw);
            var kananX = -Math.cos(yaw), kananZ = Math.sin(yaw);
            sudutTerkunci.nilai = Math.atan2(
                depanX * maju + kananX * samping,
                depanZ * maju + kananZ * samping
            );
            sudutTerkunci.kunci = kunci;
        }
        var a = sudutTerkunci.nilai;
        // Arah 0 = -z, jadi dx = -sin(a) dan dz = -cos(a).
        var dx = -Math.sin(a), dz = -Math.cos(a);
        var langkah = 185 * dt;
        var nx = kamu.x + dx * langkah;
        var nz = kamu.z + dz * langkah;
        if (!tabrakan(nx, kamu.z)) kamu.x = nx;
        if (!tabrakan(kamu.x, nz)) kamu.z = nz;
        if (window.__interior3d) window.__interior3d._jejakGerak.push({ maju: maju, samping: samping, a: a, dx: +dx.toFixed(2), dz: +dz.toFixed(2), nx: Math.round(nx), nz: Math.round(nz), bx: !tabrakan(nx, kamu.z), bz: !tabrakan(kamu.x, nz) });
        // Pemain menghadap arah langkah.
        kamu.arah = a;
        kamu.arahPutar = 0;
    }

    // ------------------------------------------------------------------
    // Putaran gambar
    // ------------------------------------------------------------------
    function gambar(waktu) {
        if (!siap) return;
        requestAnimationFrame(gambar);
        if (!terbuka) { sebelumnya = waktu; return; }
        var dt = Math.min((waktu - sebelumnya) / 1000 || 0, 0.15);
        sebelumnya = waktu;

        gerak(dt);

        // Figur pemain. Modelnya menghadap -z pada rotasi 0.
        figur.position.set(kamu.x, 0, kamu.z);
        var hadap = arahKameraYaw();
        figur.rotation.y = hadap;

        // Kamera di belakang bahu: berlawanan arah hadap (sisi +z).
        // Jaraknya dikecilkan bila dinding terlalu dekat, supaya kamera tidak
        // berada di luar ruangan (dulu kamera menembus dinding belakang dan
        // yang terlihat hanya langit-langit).
        var jarakBelakang = 210;
        var tx = kamu.x + Math.sin(hadap) * jarakBelakang;
        var tz = kamu.z + Math.cos(hadap) * jarakBelakang;
        var batasX = LEBAR / 2 - 26, batasZ = DALAM / 2 - 26;
        if (tx > batasX) tx = batasX;
        if (tx < -batasX) tx = -batasX;
        if (tz > batasZ) tz = batasZ;
        if (tz < -batasZ) tz = -batasZ;
        camera.position.lerp(new THREE.Vector3(tx, 205, tz), 1 - Math.pow(0.0001, dt));
        // Lihat sedikit lebih tinggi dan lebih jauh: kepala pemain sempat
        // memenuhi separuh layar karena kamera hanya 1,3 m di belakangnya.
        var lihat = new THREE.Vector3(kamu.x - Math.sin(hadap) * 220, 135, kamu.z - Math.cos(hadap) * 220);
        camera.lookAt(lihat);

        // Bang Uju sedikit bergoyang supaya ruangan terasa hidup.
        if (figurUju) figurUju.position.y = Math.sin(waktu / 520) * 3;

        perbaruiTitik();
        renderer.render(scene, camera);
    }

    // ------------------------------------------------------------------
    // Tabrakan tombol: klik/sentuh pada kanvas memutar pandangan.
    // ------------------------------------------------------------------
    var kanvasEl = null;
    function pasangSeret() {
        kanvasEl = renderer.domElement;
        var mulai = function (x) { seret.aktif = true; seret.x = x; kanvasEl.classList.add('menyeret'); };
        var geser = function (x) {
            if (!seret.aktif) return;
            var d = (x - seret.x) * 0.0045;
            seret.geser += d;
            seret.x = x;
        };
        var berhenti = function () { seret.aktif = false; if (kanvasEl) kanvasEl.classList.remove('menyeret'); };

        kanvasEl.addEventListener('pointerdown', function (e) { mulai(e.clientX); kanvasEl.setPointerCapture(e.pointerId); });
        kanvasEl.addEventListener('pointermove', function (e) { geser(e.clientX); });
        kanvasEl.addEventListener('pointerup', berhenti);
        kanvasEl.addEventListener('pointercancel', berhenti);
        kanvasEl.addEventListener('pointerleave', function () { if (!seret.aktif) return; });
    }

    // ------------------------------------------------------------------
    // Buka / tutup ruangan
    // ------------------------------------------------------------------
    function buka() {
        if (!siap) {
            // WebGL tidak bisa dibuat hanya untuk ruangan ini.
            return false;
        }
        terbuka = true;
        panelRuang.hidden = false;
        ukur();
        // Pemain selalu mulai di dekat pintu, menghadap meja layanan.
        // Arah 0 = menghadap -z, arah tepat menuju dinding belakang.
        kamu.x = 0;
        kamu.z = DALAM / 2 - 70;
        kamu.arah = 0;
        seret.geser = 0;
        ditekan = {};
        sudutTerkunci.kunci = null;
        sebelumnya = performance.now();
        var yaw = arahKameraYaw();
        // Pemain menghadap -z (meja layanan); kamera di +z, di belakangnya,
        // tetapi selalu di dalam ruangan.
        camera.position.set(
            Math.max(-LEBAR / 2 + 26, Math.min(LEBAR / 2 - 26, kamu.x + Math.sin(yaw) * 210)),
            205,
            Math.max(-DALAM / 2 + 26, Math.min(DALAM / 2 - 26, kamu.z + Math.cos(yaw) * 210))
        );
        // Hadapkan kamera SEKETIKA. Dulu posisinya saja yang dipasang,
        // arahnya menunggu gambar() berikutnya, jadi pada bingkai pertama
        // kamera masih melihat dari tempat lama dan label titik dinilai
        // "di belakang layar" sehingga semuanya tersembunyi.
        camera.lookAt(
            kamu.x - Math.sin(yaw) * 160, 120, kamu.z - Math.cos(yaw) * 160
        );
        perbaruiTitik();
        renderer.render(scene, camera);
        return true;
    }

    function tutup() {
        terbuka = false;
        panelRuang.hidden = true;
        ditekan = {};
        sudutTerkunci.kunci = null;
    }

    // ------------------------------------------------------------------
    // Input
    // ------------------------------------------------------------------
    var PETA_TOMBOL = { w: 'w', a: 'a', s: 's', d: 'd', arrowup: 'w', arrowleft: 'a', arrowdown: 's', arrowright: 'd' };
    window.addEventListener('keydown', function (e) {
        if (!terbuka) return;
        var k = PETA_TOMBOL[e.key.toLowerCase()];
        if (k) { ditekan[k] = true; e.preventDefault(); }
    });
    window.addEventListener('keyup', function (e) {
        var k = PETA_TOMBOL[e.key.toLowerCase()];
        if (k) ditekan[k] = false;
    });
    window.addEventListener('blur', function () { ditekan = {}; });

    var dpad = document.getElementById('interior3dDpad');
    if (dpad) {
        var pasang = function (el) {
            var arah = el.dataset.arah;
            var nama = { maju: 'w', mundur: 's', kiri: 'a', kanan: 'd' }[arah];
            var nyala = function (e) {
                if (e) e.preventDefault();
                ditekan[nama] = true;
                el.classList.add('tertekan');
            };
            var mati = function () { ditekan[nama] = false; el.classList.remove('tertekan'); };
            el.addEventListener('pointerdown', nyala);
            el.addEventListener('pointerup', mati);
            el.addEventListener('pointerleave', mati);
            el.addEventListener('pointercancel', mati);
        };
        Array.prototype.forEach.call(dpad.querySelectorAll('button'), pasang);
    }

    window.addEventListener('resize', ukur);

    // Tombol titik 2D.
    Array.prototype.forEach.call(kotakTitik.querySelectorAll('[data-pos]'), function (el) {
        tombolTitik[el.dataset.pos] = el;
    });

    // Bangun santai supaya tidak menahan pembukaan peta.
    var mulaiBangun = function () {
        try {
            bangun();
            pasangSeret();
            requestAnimationFrame(gambar);
        } catch (e) {
            siap = false;
            // Jangan telan diam-diam: tanpa pesan ini, layar kosong tanpa
            // petunjuk apa pun dan tidak ada yang bisa diperbaiki.
            console.error('Ruang Balai Desa 3D gagal dibangun:', e);
        }
    };
    if ('requestIdleCallback' in window) {
        requestIdleCallback(mulaiBangun, { timeout: 1500 });
    } else {
        setTimeout(mulaiBangun, 60);
    }

    // Dipakai peta untuk membuka ruangan.
    window.__interior3d = {
        buka: buka,
        tutup: tutup,
        siap: function () { return siap; },
        posisi: function () {
            return { x: Math.round(kamu.x), z: Math.round(kamu.z), arah: Math.round((kamu.arah * 180) / Math.PI) };
        },
        // Untuk uji: apakah tombol titik sedang terlihat.
        // Untuk uji/diagnosa: posisi kamera dan nilai proyeksi tiap titik.
        diagnosa: function () {
            var hasil = { kamera: [Math.round(camera.position.x), Math.round(camera.position.y), Math.round(camera.position.z)], kamu: { x: Math.round(kamu.x), z: Math.round(kamu.z) }, titik: {} };
            var arah = new THREE.Vector3();
            camera.getWorldDirection(arah);
            hasil.arah = { x: +arah.x.toFixed(2), y: +arah.y.toFixed(2), z: +arah.z.toFixed(2) };
            Object.keys(titik3d).forEach(function (id) {
                var v = titik3d[id].clone().project(camera);
                var d = titik3d[id].clone().sub(camera.position);
                hasil.titik[id] = {
                    x: +v.x.toFixed(2), y: +v.y.toFixed(2), z: +v.z.toFixed(3),
                    depan: d.dot(arah) > 0,
                    jarak: Math.round(Math.hypot(titik3d[id].x - kamu.x, titik3d[id].z - kamu.z)),
                };
            });
            return hasil;
        },
        // Untuk uji: posisi kamera & pemain tiap bingkai dipilih sendiri.
        // (Diagnosa dihapus nanti bila sudah tidak perlu.)
        _ditekan: function () { return ditekan; },
        _jejakGerak: [],
        _tabrakan: tabrakan,
        _terbuka: function () { return terbuka; },
        titikTampil: function () {
            return Object.keys(tombolTitik).filter(function (k) {
                return !tombolTitik[k].classList.contains('tersembunyi');
            });
        }
    };
})();
</script>
