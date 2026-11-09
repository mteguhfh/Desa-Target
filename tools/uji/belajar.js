// Uji Pusat Pembelajaran (Fitur 5 dari spesifikasi) lewat HTTP murni, tanpa
// peramban: kunci berurutan per warga, poin tepat sekali pada kiriman ulang,
// kunci jawaban kuis tidak pernah muncul di HTML maupun JSON, kelas tanpa
// video bisa dituntaskan penuh, video tanpa ID YouTube ditolak server, dan
// galat identitas seragam untuk nomor asing dan kunci yang salah.
//
// Situs harus berjalan lebih dulu dari salinan kerja yang sedang diuji:
//   DESA_TARGET_BASE_URL=http://localhost:8123 php -S localhost:8123
// Jalankan: BASE_UJI=http://localhost:8123 node tools/uji/belajar.js
'use strict';

const BASE = process.env.BASE_UJI || 'http://localhost:8100';

let lulus = 0;
let gagal = 0;
function catat(nama, ok, detail) {
    if (ok) { lulus++; console.log('LULUS ', nama); }
    else { gagal++; console.log('GAGAL ', nama, detail ? '- ' + detail : ''); }
}

// Klien HTTP kecil dengan penanganan cookie sesi sendiri.
function buatKlien() {
    const kue = new Map();
    async function minta(metode, jalur, body) {
        const headers = {};
        if (kue.size) headers.cookie = [...kue.entries()].map(([k, v]) => k + '=' + v).join('; ');
        let payload;
        if (body !== undefined) {
            payload = new URLSearchParams(body).toString();
            headers['content-type'] = 'application/x-www-form-urlencoded';
        }
        const res = await fetch(BASE + jalur, { method: metode, headers, body: payload, redirect: 'manual' });
        for (const sc of res.headers.getSetCookie ? res.headers.getSetCookie() : []) {
            const pasangan = sc.split(';')[0];
            const i = pasangan.indexOf('=');
            if (i > 0) kue.set(pasangan.slice(0, i).trim(), pasangan.slice(i + 1).trim());
        }
        const teks = await res.text();
        let json = null;
        try { json = JSON.parse(teks); } catch (_) { /* HTML */ }
        return { status: res.status, location: res.headers.get('location'), teks, json };
    }
    return {
        get: (jalur) => minta('GET', jalur),
        post: (jalur, body) => minta('POST', jalur, body),
    };
}

// Suffix huruf saja: formulir KTP menolak nama yang mengandung angka.
const acak = Array.from({ length: 6 }, () => 'abcdefghjkmnpqrstuvwxyz'[Math.floor(Math.random() * 23)]).join('');

// Daftarkan warga lewat alur KTP sungguhan (token sesi sekali pakai),
// lalu baca nomor dan kunci rahasia dari halaman kartunya.
async function buatWarga(nama) {
    const klien = buatKlien();
    await klien.get('/ktp');
    const halaman = await klien.get('/ktp');
    const cocok = /name="token" value="([0-9a-f]+)"/.exec(halaman.teks);
    if (!cocok) throw new Error('token formulir KTP tidak ditemukan');
    const kirim = await klien.post('/ktp', {
        token: cocok[1], nama, gang: 'Gang Dagang', izin_penerbitan: '1',
    });
    if (!kirim.location || !kirim.location.includes('kartu=')) {
        throw new Error('pendaftaran tidak mengarah ke kartu: ' + kirim.status + ' ' + (kirim.location || ''));
    }
    const kartu = await klien.get('/' + kirim.location);
    const nomor = /data-nomor="(DT-\d+)"/.exec(kartu.teks)?.[1];
    const kunci = /data-kunci="([0-9a-f]{32})"/.exec(kartu.teks)?.[1];
    if (!nomor || !kunci) throw new Error('identitas kartu tidak terbaca untuk ' + nama);
    return { klien, nomor, kunci };
}

async function utama() {
    const wargaA = await buatWarga('Uji Belajar A ' + acak);
    const wargaB = await buatWarga('Uji Belajar B ' + acak);
    catat('dua warga uji terdaftar lewat KTP', true, wargaA.nomor + ' dan ' + wargaB.nomor);
    const idA = { nomor: wargaA.nomor, kunci: wargaA.kunci };
    const idB = { nomor: wargaB.nomor, kunci: wargaB.kunci };
    const q = (obj) => '?' + new URLSearchParams(obj).toString();

    const katalog = await wargaA.klien.get('/belajar' + q({ format: 'json', ...idA }));
    catat('katalog memuat tiga kelas kurikulum', katalog.json && katalog.json.kelas.length === 3,
        katalog.json ? 'jumlah ' + katalog.json.kelas.length : 'bukan JSON');
    const kelasDokumen = katalog.json.kelas.find((k) => k.slug === 'dokumen-usaha-dasar');
    const kelasDuit = katalog.json.kelas.find((k) => k.slug === 'ngatur-duit-usaha');
    const kelasKonten = katalog.json.kelas.find((k) => k.slug === 'ngonten-buat-jualan');
    catat('kelas dokumen punya tiga materi artikel', kelasDokumen && kelasDokumen.jumlahMateri === 3);

    const ambilKelas = async (klien, id, slug) =>
        (await klien.get('/belajar' + q({ format: 'json', kelas: slug, ...id }))).json;
    const awal = await ambilKelas(wargaA.klien, idA, 'dokumen-usaha-dasar');
    const [m1, m2, m3] = awal.kelas.materi;
    catat('materi pertama terbuka, kedua dan ketiga terkunci',
        m1.terbuka === true && m2.terbuka === false && m3.terbuka === false);

    const lompat = await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(m2.id) });
    catat('menyelesaikan materi terkunci ditolak server', lompat.status === 403 && lompat.json?.galat === 'terkunci',
        'status ' + lompat.status);

    const selesai1 = await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(m1.id) });
    catat('materi pertama selesai dan memberi 10 poin',
        selesai1.json?.ok === true && selesai1.json?.baru === true && selesai1.json?.poin === 10 && selesai1.json?.saldo === 10,
        JSON.stringify(selesai1.json));
    const ulang = await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(m1.id) });
    catat('kiriman ulang tidak menambah poin', ulang.json?.ok === true && ulang.json?.baru === false && ulang.json?.saldo === 10,
        JSON.stringify(ulang.json));

    const sesudah = await ambilKelas(wargaA.klien, idA, 'dokumen-usaha-dasar');
    catat('materi kedua terbuka setelah pertama selesai',
        sesudah.kelas.materi[1].terbuka === true && sesudah.kelas.materi[2].terbuka === false);
    const punyaB = await ambilKelas(wargaB.klien, idB, 'dokumen-usaha-dasar');
    catat('progres warga A tidak membuka materi warga B',
        punyaB.kelas.materi[0].selesai === false && punyaB.kelas.materi[1].terbuka === false);

    // Kunci jawaban tidak boleh muncul di permukaan mana pun. Soal kuis
    // hanya dikirim saat kuis menjadi materi aktif warga itu, jadi halaman
    // kelas diperiksa dulu, lalu artikelnya diselesaikan, baru JSON kuis
    // yang aktif diperiksa.
    const halamanKuis = await wargaA.klien.get('/belajar' + q({ kelas: 'ngatur-duit-usaha', ...idA }));
    const jsonAwal = await ambilKelas(wargaA.klien, idA, 'ngatur-duit-usaha');
    catat('HTML halaman kuis tidak memuat kunci jawaban',
        !halamanKuis.teks.includes('"jawaban"'));
    catat('tidak ada kunci jawaban bocor di JSON kelas', !JSON.stringify(jsonAwal).includes('"jawaban"'));
    const materiArtikelDuit = jsonAwal.kelas.materi.find((m) => m.tipe === 'artikel');
    await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(materiArtikelDuit.id) });
    const jsonKuis = await ambilKelas(wargaA.klien, idA, 'ngatur-duit-usaha');
    const materiKuis = jsonKuis.kelas.materi.find((m) => m.tipe === 'kuis');
    catat('JSON kuis aktif memuat soal tanpa field jawaban',
        Array.isArray(materiKuis.soal) && materiKuis.soal.length === 3 &&
        materiKuis.soal.every((s) => !('jawaban' in s)) && !JSON.stringify(jsonKuis).includes('"jawaban"'));

    // Kuis: salah dulu, baru benar. Kunci yang benar diuji dari perilaku
    // server: kombinasi [1,0,2] harus lulus, kombinasi lain tidak.
    const kuisId = materiKuis.id;
    const kirimKuis = (jawaban) => wargaA.klien.post('/belajar', {
        ...idA, aksi: 'kuis', materi: String(kuisId),
        'jawaban[0]': String(jawaban[0]), 'jawaban[1]': String(jawaban[1]), 'jawaban[2]': String(jawaban[2]),
    });
    const salah = await kirimKuis([0, 0, 0]);
    catat('jawaban kuis yang salah tidak lulus dan tidak memberi poin',
        salah.json?.ok === true && salah.json?.lulus === false, JSON.stringify(salah.json));
    const benar = await kirimKuis([1, 0, 2]);
    catat('jawaban kuis yang benar lulus dan memberi poin sekali',
        benar.json?.ok === true && benar.json?.lulus === true && benar.json?.kelasTuntas === true,
        JSON.stringify(benar.json));
    const saldoSesudahKuis = benar.json?.saldo;
    const kuisUlang = await kirimKuis([1, 0, 2]);
    catat('mengulang kuis lulus tidak menambah poin',
        kuisUlang.json?.ok === true && kuisUlang.json?.baru === false && kuisUlang.json?.saldo === saldoSesudahKuis,
        JSON.stringify(kuisUlang.json));

    // Kelas tanpa video dituntaskan penuh oleh warga B, lalu lencananya dicek.
    let keadaanB = await ambilKelas(wargaB.klien, idB, 'dokumen-usaha-dasar');
    for (const m of keadaanB.kelas.materi) {
        await wargaB.klien.post('/belajar', { ...idB, aksi: 'selesai', materi: String(m.id) });
    }
    keadaanB = await ambilKelas(wargaB.klien, idB, 'dokumen-usaha-dasar');
    catat('kelas tanpa video tuntas 100 persen', keadaanB.kelas.tuntas === true && keadaanB.kelas.persen === 100,
        JSON.stringify({ tuntas: keadaanB.kelas.tuntas, persen: keadaanB.kelas.persen }));
    const poinB = await wargaB.klien.get('/poin' + q(idB));
    const lencana = (poinB.json?.koleksi || []).find((k) => k.kode === 'warga-cerdas');
    catat('lencana Warga Cerdas terbuka di koleksi warga B', !!lencana && lencana.terbuka === true,
        JSON.stringify(poinB.json?.koleksi?.map((k) => k.kode + ':' + k.terbuka)));
    const daftarWarga = await wargaB.klien.get('/warga');
    catat('lencana Warga Cerdas tampil di halaman daftar warga', daftarWarga.teks.includes('Warga Cerdas'));

    // Video tanpa ID YouTube tidak bisa ditandai selesai.
    const kontenA = await ambilKelas(wargaA.klien, idA, 'ngonten-buat-jualan');
    const artikelKonten = kontenA.kelas.materi.find((m) => m.tipe === 'artikel');
    await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(artikelKonten.id) });
    const kontenB = await ambilKelas(wargaA.klien, idA, 'ngonten-buat-jualan');
    const video = kontenB.kelas.materi.find((m) => m.tipe === 'video');
    catat('video tanpa ID tampil sebagai segera hadir', video.segera === true && video.youtubeId === null);
    const paksaVideo = await wargaA.klien.post('/belajar', { ...idA, aksi: 'selesai', materi: String(video.id) });
    catat('menandai video segera hadir ditolak server', paksaVideo.status === 422 && paksaVideo.json?.galat === 'segera',
        'status ' + paksaVideo.status + ' ' + JSON.stringify(paksaVideo.json));

    // Identitas: nomor asing dan kunci salah harus menjawab sama persis.
    const asing = await wargaA.klien.get('/belajar' + q({ format: 'json', nomor: 'DT-9999', kunci: '0'.repeat(32) }));
    const kunciSalah = await wargaA.klien.get('/belajar' + q({ format: 'json', nomor: wargaA.nomor, kunci: '0'.repeat(32) }));
    const postAsing = await wargaA.klien.post('/belajar', { nomor: 'DT-9999', kunci: '0'.repeat(32), aksi: 'selesai', materi: String(m1.id) });
    const postSalah = await wargaA.klien.post('/belajar', { nomor: wargaA.nomor, kunci: '0'.repeat(32), aksi: 'selesai', materi: String(m1.id) });
    catat('POST nomor asing dan kunci salah sama-sama 403 dengan pesan sama',
        postAsing.status === 403 && postSalah.status === 403 && postAsing.json?.pesan === postSalah.json?.pesan,
        postAsing.status + '/' + postSalah.status);
    catat('GET dengan identitas buruk diperlakukan sebagai pengunjung',
        asing.json?.pengunjung === true && kunciSalah.json?.pengunjung === true);

    console.log('\nHasil: ' + lulus + ' lulus, ' + gagal + ' gagal.');
    process.exit(gagal > 0 ? 1 : 0);
}

utama().catch((err) => {
    console.error('Uji berhenti karena galat:', err.message);
    process.exit(2);
});
