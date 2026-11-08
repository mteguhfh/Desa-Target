<?php
// Peta Desa 3D.
//
// Berkas ini bisa dijalankan dari dua tempat:
//   - /peta3d/          langsung, untuk meninjau peta 3D sendiri
//   - /peta.php         sebagai pintu masuk peta (mode default, ADR-0001),
//                       lewat require di peta.php dengan $dariAkar = true
//
// Dua variabel di bawah menyesuaikan alamat aset dan tautan menurut tempat
// berkas ini dijalankan: $awalan untuk tautan ke halaman situs, $folder3d
// untuk tautan ke berkas di dalam peta3d/.
$dariAkar = $dariAkar ?? false;
$awalan = $dariAkar ? '' : '../';
$folder3d = $dariAkar ? 'peta3d/' : './';

// Berkas ini sengaja PHP, bukan HTML statis, supaya penanda versi modul
// bangunan3d.js dihitung dari waktu ubah berkasnya (filemtime). Setiap
// deploy yang mengubah bangunan3d.js otomatis mengganti URL-nya, jadi
// peramban tidak lagi menyajikan modul lama dari cache.
$versiBangunan3d = (int) (@filemtime(__DIR__ . '/bangunan3d.js') ?: 1);
$versiStyle = (int) (@filemtime(__DIR__ . '/../assets/style.css') ?: 1);
// Import map wajib berisi alamat URL yang eksplisit (diawali ./ atau /),
// bukan jalur relatif polos: "peta3d/vendor/..." ditolak browser sebagai
// bare specifier. Dipakai untuk importmap dan modul bangunan3d.js.
$akarUrl = $dariAkar ? './' : '../';
$folder3dUrl = $dariAkar ? './peta3d/' : './';

// Isi panel bersayap (tab Papan Gang dan bangunan). Dibaca dari basis data
// supaya panel dan dunia 3D di belakangnya tidak mungkin berbeda angka.
// Kegagalan basis data tidak boleh mematikan peta 3D: panel cukup kosong.
$PAPAN_GANG_SERVER = [];
$BANGUNAN_SERVER = [];
$TOTAL_WARGA_SERVER = 0;
$MISI_SERVER = [];
$KAVLING_BANGUNAN = [];
try {
    require_once __DIR__ . '/../db.php';
    $pdoPanel = db();
    $PAPAN_GANG_SERVER = papan_gang($pdoPanel);
    $BANGUNAN_SERVER = semua_bangunan($pdoPanel);
    $TOTAL_WARGA_SERVER = hitung_warga($pdoPanel);
    $MISI_SERVER = daftar_misi($pdoPanel);
    // Kavling mengikuti urutan id, sama seperti yang dipakai dunia 3D.
    foreach ($BANGUNAN_SERVER as $i => $b) {
        $KAVLING_BANGUNAN[(int) $b['id']] = $i;
    }
} catch (Throwable $e) {
    // biarkan kosong
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Peta Desa Target 3D (Prototipe v5)</title>
<style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
    body {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        background: #d8f6e0;
        color: #1f2937;
    }
    #panggung { position: fixed; inset: 0; }
    #panggung canvas { display: block; }

    /* --- Lencana merek dan navigasi melayang, sama seperti peta 2D --- */
    .bar-atas {
        position: fixed; top: 12px; left: 12px; right: 12px;
        display: flex; align-items: flex-start; gap: 8px;
        pointer-events: none; z-index: 20;
    }
    .bar-atas > * { pointer-events: auto; }
    .lencana-merek {
        display: inline-flex; align-items: center; gap: 8px;
        background: #ffffff; border-radius: 999px; padding: 9px 16px 9px 12px;
        font-weight: 800; font-size: 17px; color: #1e3a8a;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.18);
        white-space: nowrap;
    }
    .lencana-merek .target { color: #2563eb; }
    .lencana-merek .versi {
        background: #2563eb; color: #fff; font-size: 11px; font-weight: 800;
        border-radius: 999px; padding: 2px 8px; letter-spacing: 0.4px;
    }
    .pil-nav {
        display: inline-flex; align-items: center;
        background: #ffffff; border: none; cursor: pointer; text-decoration: none;
        border-radius: 999px; padding: 10px 16px;
        font: 600 14px system-ui, sans-serif; color: #1f2937;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
        white-space: nowrap;
    }
    .pil-nav:hover { background: #eff6ff; }
    .chip-petunjuk {
        margin-left: auto;
        background: rgba(255,255,255,0.94); border-radius: 999px;
        padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: #374151;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
        max-width: 430px;
    }
    @media (max-width: 900px) { .chip-petunjuk { display: none; } }
    .chip-waktu {
        display: inline-flex; align-items: center; white-space: nowrap;
        background: rgba(255,255,255,0.94); border-radius: 999px;
        padding: 10px 14px; font-size: 13.5px; font-weight: 700; color: #374151;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
    }
    .chip-waktu.malam { background: rgba(30,41,59,0.92); color: #e2e8f0; }
    /* Suasana desa: ramai atau sepi, dihitung dari warga yang berdiri. */
    .chip-suasana {
        display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
        background: rgba(255,255,255,0.94); border-radius: 999px;
        padding: 10px 14px; font-size: 13.5px; font-weight: 700; color: #374151;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
    }
    .chip-suasana::before {
        content: ''; width: 8px; height: 8px; border-radius: 999px; background: #94a3b8;
    }
    .chip-suasana.ramai::before { background: #22c55e; }
    .chip-suasana.sepi::before { background: #f59e0b; }
    @media (max-width: 700px) { .chip-suasana { display: none; } }
    /* Saldo poin warga aktif. Hanya tampil bila peramban sudah punya kartu. */
    .chip-poin {
        display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
        background: #fef3c7; border: none; cursor: pointer;
        border-radius: 999px; padding: 10px 14px;
        font: 700 13.5px system-ui, sans-serif; color: #92400e;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.15);
    }
    .chip-poin:hover { background: #fde68a; }
    .chip-poin[hidden] { display: none; }
    @media (max-width: 560px) { .pil-nav.sembunyi-kecil { display: none; } }

    /* --- Kartu pengantar --- */
    .kartu-intro {
        position: fixed; top: 66px; left: 12px; z-index: 19;
        width: min(336px, calc(100vw - 24px));
        background: #ffffff; border-radius: 16px;
        box-shadow: 0 10px 32px rgba(30, 58, 138, 0.22);
        padding: 16px 18px 14px;
    }
    .kartu-intro h1 { margin: 0 24px 8px 0; font-size: 20px; color: #111827; }
    .kartu-intro p { margin: 0 0 8px; font-size: 14px; line-height: 1.55; color: #4b5563; }
    .kartu-intro p.catatan { font-size: 12.5px; color: #92400e; background: #fffbeb; border-radius: 8px; padding: 7px 9px; }
    .kartu-intro p.catatan.langsung { color: #065f46; background: #ecfdf5; }
    .tombol-tutup {
        position: absolute; top: 8px; right: 8px;
        width: 30px; height: 30px; border: none; border-radius: 999px;
        background: #f3f4f6; color: #4b5563; font-size: 17px; cursor: pointer; line-height: 1;
    }
    .tombol-tutup:hover { background: #e5e7eb; }
    @media (max-width: 700px) { .kartu-intro { display: none; } .kartu-intro.paksa-tampil { display: block; } }

    /* --- Tumpukan kontrol kanan --- */
    .kontrol {
        position: fixed; right: 12px; bottom: 146px; z-index: 20;
        display: flex; flex-direction: column; gap: 8px; align-items: flex-end;
    }
    .kontrol button {
        border: none; cursor: pointer; background: #ffffff; color: #1f2937;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.18);
        font: 700 14px system-ui, sans-serif;
    }
    .kontrol .pil { border-radius: 999px; padding: 11px 18px; }
    .kontrol .pil.utama { background: #2563eb; color: #ffffff; }
    .kontrol .pil.utama.aktif { background: #dc2626; }
    .kontrol .bulat {
        width: 44px; height: 44px; border-radius: 999px;
        font-size: 20px; display: flex; align-items: center; justify-content: center;
    }
    .kontrol button:hover { filter: brightness(0.96); }

    /* --- Panel Papan Gang --- */
    .panel-gang {
        position: fixed; top: 0; right: 0; z-index: 30;
        width: min(340px, 92vw); height: 100dvh;
        background: #ffffff; box-shadow: -12px 0 32px rgba(30, 58, 138, 0.25);
        transform: translateX(105%); transition: transform 0.32s ease;
        padding: 18px 18px 24px; overflow-y: auto;
    }
    .panel-gang.buka { transform: translateX(0); }
    @media (max-width: 700px) { .panel-gang { width: 100vw; } }
    .panel-gang .kepala { display: flex; align-items: center; justify-content: space-between; }
    .panel-gang h2 { margin: 0; font-size: 19px; }
    .panel-gang .kepala .tombol-tutup { position: static; }
    .panel-gang .deskripsi { font-size: 13.5px; color: #6b7280; line-height: 1.5; }
    .papan-baris {
        display: grid; grid-template-columns: 2rem minmax(0, 1fr) auto;
        grid-template-areas: "peringkat nama jumlah" "peringkat palangan palangan";
        align-items: center; column-gap: 0.6rem; row-gap: 0.3rem;
        padding: 0.55rem 0; border-bottom: 1px solid #f3f4f6;
    }
    .papan-peringkat {
        grid-area: peringkat; width: 1.7rem; height: 1.7rem; border-radius: 999px;
        background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 13px;
        display: flex; align-items: center; justify-content: center;
    }
    .papan-nama { grid-area: nama; font-weight: 700; font-size: 14.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .papan-jumlah { grid-area: jumlah; font-size: 13px; color: #6b7280; white-space: nowrap; }
    .papan-palangan { grid-area: palangan; height: 8px; border-radius: 999px; background: #f3f4f6; overflow: hidden; }
    .papan-isi { display: block; height: 100%; border-radius: 999px; }
    .panel-gang .gabung { margin-top: 14px; font-size: 14px; }
    .panel-gang .gabung a { color: #2563eb; font-weight: 700; }

    /* --- Kartu popover hasil tap --- */
    .popover {
        position: fixed; z-index: 25; width: min(300px, calc(100vw - 24px));
        background: #ffffff; border-radius: 14px;
        box-shadow: 0 12px 34px rgba(30, 58, 138, 0.28);
        padding: 14px 16px 12px; display: none;
    }
    .popover.tampil { display: block; }
    .popover .kicker { font-size: 11px; font-weight: 800; letter-spacing: 1.2px; color: #2563eb; margin: 0 0 3px; }
    .popover h3 { margin: 0 26px 6px 0; font-size: 17px; }
    .popover p { margin: 0 0 5px; font-size: 13.5px; color: #4b5563; line-height: 1.5; }
    .popover .kutipan { font-style: italic; color: #374151; }
    .popover .lencana {
        display: inline-block; font-size: 11.5px; font-weight: 700; color: #fff;
        border-radius: 999px; padding: 2px 9px; margin-bottom: 6px;
    }

    /* --- Mode Keliling Desa --- */
    .dpad {
        position: fixed; left: 14px; bottom: 78px; z-index: 22;
        display: none; grid-template-columns: repeat(3, 52px); grid-template-rows: repeat(3, 52px);
        gap: 5px; touch-action: none;
    }
    .dpad.tampil { display: grid; }
    .dpad button {
        border: none; border-radius: 14px; background: rgba(255,255,255,0.92);
        box-shadow: 0 4px 14px rgba(30,58,138,0.22); font-size: 19px; color: #1e3a8a;
        cursor: pointer; user-select: none; -webkit-user-select: none;
    }
    .dpad button.tertekan { background: #bfdbfe; }
    .dpad .atas { grid-area: 1 / 2; } .dpad .kiri { grid-area: 2 / 1; }
    .dpad .kanan { grid-area: 2 / 3; } .dpad .bawah { grid-area: 3 / 2; }
    .chip-masuk {
        position: fixed; left: 50%; bottom: 150px; transform: translateX(-50%);
        z-index: 22; display: none;
        background: #2563eb; color: #fff; border: none; cursor: pointer;
        font: 700 15px system-ui, sans-serif; border-radius: 999px; padding: 13px 24px;
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.45);
    }
    .chip-masuk.tampil { display: block; }
    .chip-petunjuk-jalan {
        position: fixed; left: 50%; bottom: 78px; transform: translateX(-50%);
        z-index: 21; display: none;
        background: rgba(17,24,39,0.82); color: #f9fafb; border-radius: 999px;
        padding: 9px 16px; font-size: 13px; font-weight: 600; white-space: nowrap;
    }
    .chip-petunjuk-jalan.tampil { display: block; }
    @media (max-width: 700px) { .chip-petunjuk-jalan { display: none !important; } }

    /* --- Cadangan bila WebGL tidak tersedia --- */
    .webgl-gagal {
        position: fixed; inset: 0; z-index: 50; display: none;
        align-items: center; justify-content: center; padding: 20px;
        background: linear-gradient(#bae6fd, #dcfce7);
    }
    .webgl-gagal.tampil { display: flex; }
    .webgl-gagal .kotak {
        background: #fff; border-radius: 16px; padding: 24px; max-width: 420px;
        box-shadow: 0 12px 34px rgba(30,58,138,0.25); text-align: center;
    }
    .webgl-gagal a { color: #2563eb; font-weight: 700; }

    /* --- Interior Balai Desa (Tahap 2) ---
       Lapisan penuh di atas kanvas 3D. Isi ruangannya sama dengan interior
       peta 2D (interior_balai_svg dipakai bersama), jadi yang diatur di sini
       hanya bingkai dan tombol keluarnya. */
    .interior-3d {
        position: fixed; inset: 0; z-index: 68;
        background: #fdf3e3;
        display: flex; align-items: center; justify-content: center;
    }
    .interior-3d[hidden] { display: none; }
    /* Kanvas ruangan 3D mengisi seluruh lapisan. */
    .interior-3d-kanvas { position: absolute; inset: 0; }
    .interior-3d-kanvas > canvas { display: block; width: 100%; height: 100%; touch-action: none; cursor: grab; }
    .interior-3d-kanvas > canvas.menyeret { cursor: grabbing; }

    /* Tombol titik interaktif (Loket, Papan, Arsip, Bang Uju). Posisinya
       dihitung dari proyeksi kamera 3D tiap bingkai, jadi lapisannya hanya
       wadah: isi tombol yang mengatur dirinya sendiri. */
    .interior-3d-titik { position: absolute; inset: 0; pointer-events: none; z-index: 3; }
    .interior-titik {
        position: absolute; top: 0; left: 0;
        pointer-events: auto;
        display: flex; flex-direction: column; align-items: center; gap: 3px;
        padding: 6px 12px 7px;
        border: none; cursor: pointer; text-decoration: none;
        border-radius: 12px;
        background: rgba(255,255,255,0.96);
        color: #1e3a8a;
        font: 700 13px system-ui, sans-serif;
        box-shadow: 0 5px 16px rgba(30,58,138,0.28);
        white-space: nowrap;
        transition: opacity 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }
    .interior-titik::after {
        /* Penunjuk ke objek 3D di bawahnya. */
        content: ''; position: absolute; left: 50%; bottom: -7px;
        width: 12px; height: 12px; margin-left: -6px;
        background: inherit; transform: rotate(45deg);
        border-radius: 2px;
    }
    .interior-titik-lencana {
        font: 800 10.5px system-ui, sans-serif; letter-spacing: 1px;
        text-transform: uppercase; color: #2563eb;
    }
    .interior-titik-nama { font-size: 13px; }
    .interior-titik.dekat {
        background: #2563eb; color: #fff;
        box-shadow: 0 8px 22px rgba(37,99,235,0.45);
    }
    .interior-titik.dekat .interior-titik-lencana { color: #dbeafe; }
    .interior-titik.tersembunyi {
        opacity: 0; pointer-events: none; visibility: hidden;
    }
    .interior-titik:focus-visible { outline: 3px solid #f59e0b; outline-offset: 3px; }

    /* Petunjuk & tombol arah di dalam ruangan. */
    .interior-3d-petunjuk {
        position: absolute; left: 50%; bottom: 18px; transform: translateX(-50%);
        z-index: 4; margin: 0;
        background: rgba(17,24,39,0.82); color: #f9fafb;
        border-radius: 999px; padding: 9px 16px;
        font: 600 13px system-ui, sans-serif; white-space: nowrap;
    }
    .interior-3d-dpad {
        position: absolute; left: 14px; bottom: 18px; z-index: 4;
        display: grid; grid-template-columns: repeat(3, 48px); grid-template-rows: repeat(3, 48px);
        gap: 5px; touch-action: none;
    }
    .interior-3d-dpad button {
        border: none; border-radius: 13px; background: rgba(255,255,255,0.94);
        box-shadow: 0 4px 14px rgba(30,58,138,0.22); font-size: 18px; color: #1e3a8a;
        cursor: pointer; user-select: none; -webkit-user-select: none;
    }
    .interior-3d-dpad button.tertekan { background: #bfdbfe; }
    .interior-3d-dpad .atas { grid-area: 1 / 2; } .interior-3d-dpad .kiri { grid-area: 2 / 1; }
    .interior-3d-dpad .kanan { grid-area: 2 / 3; } .interior-3d-dpad .bawah { grid-area: 3 / 2; }
    @media (max-width: 700px) {
        .interior-3d-petunjuk { display: none; }
        .interior-3d-dpad { bottom: 14px; }
    }
    .interior-3d-keluar {
        position: absolute; top: 14px; left: 14px;
        z-index: 5;
        border: none; cursor: pointer;
        border-radius: 999px; padding: 11px 18px;
        background: #ffffff; color: #1e3a8a;
        font: 700 14px system-ui, sans-serif;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.25);
    }
    .interior-3d-keluar:hover { background: #eff6ff; }
    /* Hotspot interior dipakai bersama dengan peta 2D; gayanya ada di
       assets/style.css yang dimuat halaman ini. */
    #stats {
        position: fixed; left: 12px; bottom: 78px; z-index: 40; display: none;
        background: rgba(17,24,39,0.85); color: #a7f3d0; border-radius: 10px;
        font: 12px ui-monospace, monospace; padding: 8px 11px; white-space: pre;
    }
    .muat {
        position: fixed; inset: 0; z-index: 70; display: flex;
        align-items: center; justify-content: center;
        background: linear-gradient(#bae6fd, #dcfce7);
        font-weight: 700; color: #1e3a8a; font-size: 17px;
        transition: opacity 0.4s ease;
    }
    .muat.hilang { opacity: 0; pointer-events: none; }

    /* --- Sakelar tampilan peta 3D / 2D (ADR-0001) ---
       Halaman 3D memakai stylesheet sendiri di dalam berkas ini (sengaja,
       supaya tetap hidup kalau style.css situs induk gagal dimuat), jadi
       gaya sakelar ditulis di sini juga. Warna mengikuti merek situs. */
    .sakelar-peta {
        position: fixed;
        left: 12px;
        bottom: 72px;
        z-index: 40;
        display: inline-flex;
        gap: 2px;
        padding: 3px;
        background: rgba(255, 255, 255, 0.96);
        border-radius: 999px;
        box-shadow: 0 4px 16px rgba(30, 58, 138, 0.18);
    }
    .sakelar-opsi {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font: 800 13px/1 system-ui, sans-serif;
        letter-spacing: 0.3px;
        color: #64748b;
        text-decoration: none;
        transition: background 160ms ease, color 160ms ease;
    }
    .sakelar-opsi:hover { background: #eff6ff; color: #1d4ed8; }
    .sakelar-opsi.aktif { background: #2563eb; color: #fff; }
    .sakelar-opsi:focus-visible {
        outline: 3px solid #1d4ed8;
        outline-offset: 2px;
    }
    /* Di layar sempit sudut kiri bawah dipakai D-pad, jadi sakelar naik. */
    @media (max-width: 700px) {
        .sakelar-peta { bottom: auto; top: 62px; left: 12px; }
        /* Tab bar bawah memakan ~66px; D-pad naik supaya tetap di atasnya. */
        .dpad { bottom: 78px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sakelar-opsi { transition: none; }
    }
</style>
<script type="importmap">
{
    "imports": {
        "three": "<?= $folder3dUrl ?>vendor/three.module.js",
        "three/addons/": "<?= $folder3dUrl ?>vendor/addons/"
    }
}
</script>
<?php
// Stylesheet situs untuk tab bar dan panel bersayap. Dunia 3D tetap memakai
// gaya di dalam berkas ini; yang dipinjam dari style.css hanya dua komponen
// navigasi tadi, jadi kalau style.css gagal dimuat peta 3D masih hidup.
?>
<link rel="stylesheet" href="<?= $awalan ?>assets/style.css?v=<?= $versiStyle ?>">
</head>
<body<?= $dariAkar ? ' data-pintu-peta="1"' : '' ?>>
<div id="panggung" aria-label="Peta Desa Target tiga dimensi"></div>

<?php
// Sakelar 3D/2D (ADR-0001). Ditempatkan di dalam bilah atas supaya tetap
// terlihat dan terjangkau saat dunia 3D memenuhi layar.
$modePeta = '3d';
require __DIR__ . '/../includes/sakelar-peta.php';
?>

<div class="bar-atas">
    <span class="lencana-merek">🎯 Desa <span class="target">Target</span> <span class="versi">3D</span></span>
    <span class="chip-petunjuk" id="chipPetunjuk">Seret buat keliling. Putar dan zoom petanya, tap bangunan, atau jalan-jalan pakai tombol Keliling Desa.</span>
    <?php // Chat antar gang (Fitur 2). Dibuka sebagai isi panel bersayap, jadi
          // tab bar tetap empat dan dunia 3D di belakangnya tidak hilang. ?>
    <button type="button" class="pil-nav" id="tombolChat" data-panel="chat"
            title="Chat antar gang, publik">Chat</button>
    <?php // Saldo poin warga aktif. Disembunyikan sampai peramban tahu nomor
          // warga (localStorage dt_nomor); tanpa itu bilah atas tidak berubah. ?>
    <button type="button" class="chip-poin" id="chipPoin" hidden
            data-panel="misi"
            title="Buka misi dan dompet poinmu">Poin <strong id="chipPoinAngka">0</strong></button>
    <span class="chip-waktu" id="chipWaktu">&#9728;&#65039; Siang</span>
    <span class="chip-suasana" id="chipSuasana" title="Suasana desa dihitung dari jumlah warga yang berdiri di peta">Memuat…</span>
</div>

<?php
// Tab bar bawah (Desa · Misi · Papan Gang · Aku) dan panel bersayap.
// Navigasi pindah dari bilah atas ke sini supaya dunia 3D tetap terlihat
// saat pengunjung berpindah isi: panel membuka di bawah, bukan halaman baru.
$tabAktif = '';
$tabAwalan = $awalan;
$tabDesaPanel = true;
$panelBangunan = $BANGUNAN_SERVER;
$panelKavling = $KAVLING_BANGUNAN;
$panelPapanGang = $PAPAN_GANG_SERVER;
$panelTotalWarga = $TOTAL_WARGA_SERVER;
$panelMisi = $MISI_SERVER;
require __DIR__ . '/../includes/panel-bersayap.php';
require __DIR__ . '/../includes/tab-bar.php';
?>

<div class="kartu-intro" id="kartuIntro">
    <button type="button" class="tombol-tutup" id="introTutup" aria-label="Tutup pengantar">&times;</button>
    <h1>Peta Desa Target</h1>
    <p>Setiap bangunan di peta ini lahir dari komentar warga di Instagram. Komentar paling banyak disukai hari ini, besok paginya sudah berdiri di sini. Masih banyak tanah kosong, desa ini menunggu usulan usahamu.</p>
    <p class="catatan" id="catatanData">Sedang memuat data desa dari balai desa…</p>
</div>

<div class="kontrol">
    <button type="button" class="pil utama" id="tombolJalan">Keliling Desa</button>
    <button type="button" class="bulat" id="tombolZoomIn" aria-label="Zoom masuk">+</button>
    <button type="button" class="bulat" id="tombolZoomOut" aria-label="Zoom keluar">&minus;</button>
    <button type="button" class="bulat" id="tombolReset" aria-label="Lihat seluruh desa" title="Lihat seluruh desa">&#8982;</button>
    <button type="button" class="bulat" id="tombolLayarPenuh" aria-label="Layar penuh">&#9974;</button>
</div>

<div class="popover" id="popover">
    <button type="button" class="tombol-tutup" id="popoverTutup" aria-label="Tutup detail">&times;</button>
    <p class="kicker" id="popKicker">DETAIL</p>
    <h3 id="popJudul">Balai Desa</h3>
    <span class="lencana" id="popLencana" hidden></span>
    <p class="kutipan" id="popKutipan" hidden></p>
    <p id="popIsi"></p>
</div>

<div class="dpad" id="dpad" aria-label="Tombol arah jalan">
    <button type="button" class="atas" data-arah="maju" aria-label="Jalan ke depan">&#9650;</button>
    <button type="button" class="kiri" data-arah="kiri" aria-label="Jalan ke kiri">&#9664;</button>
    <button type="button" class="kanan" data-arah="kanan" aria-label="Jalan ke kanan">&#9654;</button>
    <button type="button" class="bawah" data-arah="mundur" aria-label="Jalan ke belakang">&#9660;</button>
</div>
<button type="button" class="chip-masuk" id="chipMasuk">Masuk Balai Desa</button>
<div class="chip-petunjuk-jalan" id="chipJalan">WASD atau tombol panah buat jalan. Esc buat berhenti.</div>

<div class="webgl-gagal" id="webglGagal">
    <div class="kotak">
        <h2>Peta 3D tidak bisa dibuka</h2>
        <p>Browser kamu tidak mendukung WebGL, jadi desa tiga dimensinya tidak bisa digambar. Tenang, peta desa versi 2D tetap bisa dipakai seperti biasa.</p>
        <p><a href="<?= $awalan ?>peta2d.php">Buka Peta Desa 2D</a></p>
    </div>
</div>

<?php require __DIR__ . '/interior-balai.php'; ?>
<?php require __DIR__ . '/interior-balai3d.js.php'; ?>

<div id="stats"></div>
<div class="muat" id="layarMuat">Membangun desa 3D...</div>

<?php require __DIR__ . '/../includes/sakelar-peta-skrip.php'; ?>
<?php require __DIR__ . '/../includes/tab-bar-skrip.php'; ?>
<?php require __DIR__ . '/../includes/cari-warga-skrip.php'; ?>
<?php $awalanPoin = $awalan; require __DIR__ . '/../includes/dompet-skrip.php'; ?>
<?php $awalanMisi = $awalan; require __DIR__ . '/../includes/misi-skrip.php'; ?>
<?php $awalanChat = $awalan; require __DIR__ . '/../includes/chat-skrip.php'; ?>

<script type="module">
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { buatBalai, buatBangunan, varianBangunan, teksturPapan, matJendela } from '<?= $folder3dUrl ?>bangunan3d.js?v=<?= $versiBangunan3d ?>';

// ===========================================================================
// DATA DESA
//
// Sumber kebenaran posisi adalah includes/peta-posisi.php, berkas yang sama
// yang dipakai peta 2D (ADR-0004). Server mengirim titik jangkar lewat
// data-desa.php; halaman ini TIDAK menyimpan salinan koordinat.
//
// Dunia 2000 x 1333 satuan. Koordinat 2D (x, y) dipetakan ke 3D sebagai
// (x - 1000, 0, y - 666.5): utara peta 2D menjadi -Z, selatan menjadi +Z,
// jadi fasad bangunan yang menghadap selatan (+Z) terlihat dari kamera awal.
// ===========================================================================
const SKALA = 1.6667;
const KE3 = (x, y) => new THREE.Vector3(x - 1000, 0, y - 666.5);

// Semua data yang menunggu endpoint: titik kavling, zona gang, fokus, kolam.
// Nilai di bawah hanya dipakai bila endpoint tidak bisa dibaca sama sekali
// (halaman dibuka mandiri tanpa PHP, atau jaringan gagal). Begitu data asli
// tiba, semuanya diganti sebelum desa pertama kali dibangun; lihat muatDesa().
let POSISI_KAVLING = [];
let ZONA_GANG = {};
let FOKUS = null;
let KOLAM = null;
let rintanganKavling = [];
let titikPatroli = [];

// Cadangan darurat. SALINAN dari peta-posisi.php versi saat ini; sengaja
// tidak dipakai selama endpoint bisa dihubungi.
const CADANGAN = {
    kavling: [
        [1017, 692], [283, 500], [650, 325], [1425, 342], [1767, 492], [233, 867],
        [583, 758], [758, 1200], [1250, 1117], [1592, 1017], [425, 1250], [1833, 967],
    ],
    zona: {
        'Gang Dagang':     { tambak: [495, 20, 210, 168],  papan: [600, 32],   warna: 0x2563eb, css: '#2563eb' },
        'Gang Kuliner':    { tambak: [952, 16, 205, 162],  papan: [1054, 28],  warna: 0xea580c, css: '#ea580c' },
        'Gang Jasa':       { tambak: [26, 302, 205, 148],  papan: [128, 314],  warna: 0x0891b2, css: '#0891b2' },
        'Gang Freelancer': { tambak: [30, 24, 240, 150],   papan: [150, 36],   warna: 0x7c3aed, css: '#7c3aed' },
        'Gang Kerajinan':  { tambak: [772, 290, 185, 140], papan: [864, 302],  warna: 0xdb2777, css: '#db2777' },
    },
    fokus: { x: 1017, y: 692 },
    kolam: { x: 1775, y: 1197, r: 187 },
};

// Warna dan identitas visual tiap gang tidak datang dari server: itu urusan
// tampilan 3D. Peta 2D punya warnanya sendiri di bangunan-svg.php.
const WARNA_GANG = {
    'Gang Dagang':     { warna: 0x2563eb, css: '#2563eb' },
    'Gang Kuliner':    { warna: 0xea580c, css: '#ea580c' },
    'Gang Jasa':       { warna: 0x0891b2, css: '#0891b2' },
    'Gang Freelancer': { warna: 0x7c3aed, css: '#7c3aed' },
    'Gang Kerajinan':  { warna: 0xdb2777, css: '#db2777' },
};

function pakaiCadangan() {
    POSISI_KAVLING = CADANGAN.kavling;
    ZONA_GANG = CADANGAN.zona;
    FOKUS = CADANGAN.fokus;
    KOLAM = CADANGAN.kolam;
}

// Rect wara-wiri: dipakai bila server belum mengirim 'wara' untuk sebuah zona.
// Rumusnya sama dengan perhitungan di data-desa.php dan di peta 2D.
function rectWara(z) {
    if (z.wara) return z.wara;
    const t = z.tambak, mx = 14 * SKALA, mt = 18 * SKALA, mb = 12 * SKALA;
    return { x: t[0] * SKALA + mx, y: t[1] * SKALA + mt, w: t[2] * SKALA - 2 * mx, h: t[3] * SKALA - mt - mb };
}

// Turunan dari POSISI_KAVLING, dihitung ulang setiap kali titik kavling
// berganti (mis. setelah data server tiba).
function hitungTurunan() {
    // Tapak kavling yang dihindari warga saat memilih jalur lurus.
    rintanganKavling = POSISI_KAVLING.map(([x, y]) => ({ x1: x - 105, y1: y - 130, x2: x + 105, y2: y + 6 }));
    // Patroli Bang Uju mengelilingi alun-alun, sama seperti TITIK_PATROLI 2D.
    titikPatroli = [[FOKUS.x + 211, FOKUS.y + 23], [860, 720], [950, 795], [1180, 712]];
}

// Zona gang gabungan: koordinat dari server, warna dari WARNA_GANG.
function zonaGangLengkap() {
    const hasil = {};
    for (const [nama, z] of Object.entries(ZONA_GANG)) {
        const w = WARNA_GANG[nama] || { warna: 0x64748b, css: '#64748b' };
        hasil[nama] = { ...z, warna: w.warna, css: w.css };
    }
    return hasil;
}

function rectWaraUntuk(namaGang) {
    const z = ZONA_GANG[namaGang];
    return z ? rectWara(z) : null;
}

// ---------------------------------------------------------------------------
// Ambil data desa SEBELUM dunia dibangun (ADR-0004). Titik kavling, zona
// gang, fokus kamera, dan kolam semuanya berasal dari includes/peta-posisi.php
// lewat data-desa.php, berkas yang sama yang dipakai peta 2D. Klien tidak
// menyimpan salinan koordinat, jadi tidak ada yang bisa basi.
//
// Bila endpoint tidak bisa dihubungi (halaman dibuka langsung dari disk,
// jaringan putus), CADANGAN dipakai supaya peta tetap tampil.
// ---------------------------------------------------------------------------
async function ambilDataDesa() {
    try {
        const respons = await fetch('<?= $folder3d ?>data-desa.php', { headers: { 'Accept': 'application/json' } });
        if (!respons.ok) return null;
        const data = await respons.json();
        if (!data || data.galat) return null;
        if (!Array.isArray(data.kavling) || data.kavling.length === 0) return null;
        return data;
    } catch (e) {
        return null;
    }
}

function terapkanDataDesa(data) {
    if (data) {
        POSISI_KAVLING = data.kavling.map(([x, y]) => [Number(x), Number(y)]);
        ZONA_GANG = data.zonaGang || {};
        FOKUS = { x: Number(data.fokus.x), y: Number(data.fokus.y) };
        KOLAM = { x: Number(data.kolam.x), y: Number(data.kolam.y), r: Number(data.kolam.r) };
    } else {
        pakaiCadangan();
    }
    hitungTurunan();
}

const DATA_DESA = await ambilDataDesa();
terapkanDataDesa(DATA_DESA);

// Isi desa dari server, atau cadangan bila endpoint gagal.
const WARGA_SEED = DATA_DESA && Array.isArray(DATA_DESA.warga) ? DATA_DESA.warga : [];
const BANGUNAN_SEED = DATA_DESA && Array.isArray(DATA_DESA.bangunan) ? DATA_DESA.bangunan : [];
// RNG deterministik supaya sebaran pohon dan warga konsisten tiap muat.
function mulberry32(a) {
    return function () {
        a |= 0; a = (a + 0x6D2B79F5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}
const acak = mulberry32(20261107);
const kurangGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ===========================================================================
// PANGGUNG
// ===========================================================================
const panggung = document.getElementById('panggung');
let renderer = null;
try {
    renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
} catch (e) { /* ditangani di bawah */ }
if (!renderer) {
    document.getElementById('webglGagal').classList.add('tampil');
    document.getElementById('layarMuat').classList.add('hilang');
    throw new Error('WebGL tidak tersedia');
}
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
panggung.appendChild(renderer.domElement);

const scene = new THREE.Scene();
// Langit berupa kubah gradasi raksasa (BackSide) mengelilingi dunia: warna
// cakrawalanya menyatu dengan kabut, jadi tepi tanah memudar ke kejauhan
// dan tidak ada lagi kesan lempengan melayang. Tekstur kanvas digambar
// ulang oleh siklus siang-malam hanya saat warnanya berubah nyata.
const kubah = (() => {
    const c = document.createElement('canvas'); c.width = 4; c.height = 256;
    const ctx = c.getContext('2d');
    const gr = ctx.createLinearGradient(0, 0, 0, 256);
    gr.addColorStop(0, '#54b6f5'); gr.addColorStop(0.58, '#bae6fd'); gr.addColorStop(1, '#d6f0f7');
    ctx.fillStyle = gr; ctx.fillRect(0, 0, 4, 256);
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    const mesh = new THREE.Mesh(
        new THREE.SphereGeometry(3900, 24, 14),
        new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false, depthWrite: false })
    );
    mesh.renderOrder = -10;
    scene.add(mesh);
    return { kanvas: c, ctx, tex, kunci: '' };
})();
scene.background = new THREE.Color(0xd6f0f7);
scene.fog = new THREE.Fog(0xd6f0f7, 1500, 3400);

const camera = new THREE.PerspectiveCamera(42, window.innerWidth / window.innerHeight, 1, 8000);
const KAMERA_AWAL = new THREE.Vector3(120, 1150, 1460);
const TARGET_AWAL = KE3(1017, 760);
camera.position.copy(KAMERA_AWAL);

const controls = new OrbitControls(camera, renderer.domElement);
controls.target.copy(TARGET_AWAL);
controls.enableDamping = true;
controls.dampingFactor = 0.06;
controls.minDistance = 240;
controls.maxDistance = 2500;
controls.maxPolarAngle = 1.35;
controls.update();

const cahayaHemi = new THREE.HemisphereLight(0xeaf6ff, 0x86c06c, 0.95);
scene.add(cahayaHemi);
const matahari = new THREE.DirectionalLight(0xfff1d6, 1.7);
matahari.position.set(-750, 950, -520);
matahari.castShadow = true;
matahari.shadow.mapSize.set(2048, 2048);
matahari.shadow.camera.left = -1250; matahari.shadow.camera.right = 1250;
matahari.shadow.camera.top = 1250; matahari.shadow.camera.bottom = -1250;
matahari.shadow.camera.far = 3200;
matahari.shadow.bias = -0.0006;
scene.add(matahari);

// Material tanah bersama.
const matStandar = (hex, opsi = {}) => new THREE.MeshStandardMaterial({ color: hex, roughness: 0.95, metalness: 0, flatShading: true, ...opsi });
function kotak(w, h, d, bahan, x = 0, y = 0, z = 0) {
    const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), bahan);
    m.position.set(x, y, z);
    m.castShadow = true; m.receiveShadow = true;
    return m;
}
const dunia = new THREE.Group();
scene.add(dunia);
const bisaDiklik = [];   // objek yang merespons tap (beserta data popover)
// Material yang diwarnai ulang siklus siang-malam (diisi blok tanah/dekor).
let matTanahLuar = null, bahanKepalaLampu = null;

// ---------------------------------------------------------------------------
// Tanah, zona gang, alun-alun
// ---------------------------------------------------------------------------
{
    const tanah = new THREE.Mesh(new THREE.PlaneGeometry(2000, 1333), matStandar(0x8fd07a));
    tanah.rotation.x = -Math.PI / 2;
    tanah.receiveShadow = true;
    dunia.add(tanah);
    // Hamparan luar: jauh lebih besar dari pulau dan LEBIH GELAP dari tanah
    // desa, membentang sampai kabut menelannya. Tidak ada garis tepi keras.
    matTanahLuar = matStandar(0x74b45e);
    const tanahLuar = new THREE.Mesh(new THREE.PlaneGeometry(7600, 7600), matTanahLuar);
    tanahLuar.rotation.x = -Math.PI / 2;
    tanahLuar.position.y = -0.4;
    tanahLuar.receiveShadow = true;
    dunia.add(tanahLuar);
}
function bentukBulat(w, h, r) {
    const s = new THREE.Shape();
    const x = -w / 2, y = -h / 2;
    s.moveTo(x + r, y);
    s.lineTo(x + w - r, y); s.quadraticCurveTo(x + w, y, x + w, y + r);
    s.lineTo(x + w, y + h - r); s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    s.lineTo(x + r, y + h); s.quadraticCurveTo(x, y + h, x, y + h - r);
    s.lineTo(x, y + r); s.quadraticCurveTo(x, y, x + r, y);
    return s;
}
for (const [namaGang, z] of Object.entries(zonaGangLengkap())) {
    const [tx, ty, tw, th] = z.tambak.map(v => v * SKALA);
    const tambak = new THREE.Mesh(
        new THREE.ShapeGeometry(bentukBulat(tw, th, 60)),
        new THREE.MeshStandardMaterial({ color: z.warna, transparent: true, opacity: 0.22, roughness: 1, depthWrite: false })
    );
    tambak.rotation.x = -Math.PI / 2;
    const tengah = KE3(tx + tw / 2, ty + th / 2);
    tambak.position.set(tengah.x, 0.35, tengah.z);
    tambak.receiveShadow = true;
    dunia.add(tambak);

    // Papan kayu nama gang di tepi atas petak.
    const papanGrup = new THREE.Group();
    const posPapan = KE3(z.papan[0] * SKALA, z.papan[1] * SKALA);
    papanGrup.position.copy(posPapan);
    const bahanKayu = matStandar(0x92600f);
    papanGrup.add(kotak(7, 58, 7, bahanKayu, -52, 29, 0));
    papanGrup.add(kotak(7, 58, 7, bahanKayu, 52, 29, 0));
    const papan = new THREE.Mesh(
        new THREE.BoxGeometry(168, 46, 6),
        [bahanKayu, bahanKayu, bahanKayu, bahanKayu,
         new THREE.MeshStandardMaterial({ map: teksturPapan(namaGang, { ukuran: 52, latar: '#fef3c7' }), roughness: 0.85 }),
         bahanKayu]
    );
    papan.position.y = 62; papan.castShadow = true;
    papanGrup.add(papan);
    dunia.add(papanGrup);
}
{   // Alun-alun paving di depan Balai Desa.
    const plaza = KE3(FOKUS.x, 714);
    const paving = new THREE.Mesh(new THREE.CircleGeometry(178, 40), matStandar(0xefe7d2));
    paving.rotation.x = -Math.PI / 2; paving.position.set(plaza.x, 0.3, plaza.z);
    paving.receiveShadow = true;
    dunia.add(paving);
    const bingkai = new THREE.Mesh(new THREE.RingGeometry(178, 190, 40), matStandar(0xe0cfa1));
    bingkai.rotation.x = -Math.PI / 2; bingkai.position.set(plaza.x, 0.28, plaza.z);
    bingkai.receiveShadow = true;
    dunia.add(bingkai);
    // Bangku dan papan pengumuman plaza (posisi dari peta 2D).
    const bahanKayu = matStandar(0x92600f);
    for (const [bx, by] of [[842, 770], [1192, 770]]) {
        const b = KE3(bx, by);
        const bangku = new THREE.Group();
        bangku.add(kotak(64, 7, 20, bahanKayu, 0, 16, 0));
        bangku.add(kotak(8, 16, 18, bahanKayu, -24, 8, 0));
        bangku.add(kotak(8, 16, 18, bahanKayu, 24, 8, 0));
        bangku.position.copy(b);
        dunia.add(bangku);
    }
    const pp = KE3(780, 707);
    const papanPengumuman = new THREE.Group();
    papanPengumuman.add(kotak(6, 62, 6, bahanKayu, 0, 31, 0));
    const isi = new THREE.Mesh(new THREE.BoxGeometry(76, 48, 5),
        [bahanKayu, bahanKayu, bahanKayu, bahanKayu,
         new THREE.MeshStandardMaterial({ map: teksturPapan('PENGUMUMAN', { ukuran: 40 }), roughness: 0.9 }), bahanKayu]);
    isi.position.y = 72; isi.castShadow = true;
    papanPengumuman.add(isi);
    papanPengumuman.position.copy(pp);
    dunia.add(papanPengumuman);
}

// ---------------------------------------------------------------------------
// Jalan utama melengkung dan jalan setapak tanah
// ---------------------------------------------------------------------------
const TITIK_JALAN = [[-33, 1177], [300, 1077], [533, 955], [867, 980], [1200, 1002], [1333, 835], [1600, 760], [1817, 708], [2033, 530]];
const kurvaJalan = new THREE.CatmullRomCurve3(TITIK_JALAN.map(([x, y]) => KE3(x, y)));
const contohJalan = kurvaJalan.getPoints(72).map(p => ({ x: p.x + 1000, y: p.z + 666.5 }));
function pitaJalan(kurva, lebar, bahan, y) {
    const N = 72, pos = [], idx = [];
    const pts = kurva.getPoints(N);
    for (let i = 0; i <= N; i++) {
        const p = pts[i];
        const t = kurva.getTangent(i / N);
        const nx = -t.z, nz = t.x;
        pos.push(p.x + nx * lebar / 2, y, p.z + nz * lebar / 2, p.x - nx * lebar / 2, y, p.z - nz * lebar / 2);
        if (i < N) { const a = i * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
    geo.setIndex(idx);
    geo.computeVertexNormals();
    const m = new THREE.Mesh(geo, bahan);
    m.receiveShadow = true;
    return m;
}
dunia.add(pitaJalan(kurvaJalan, 116, matStandar(0xd9c48d), 0.22));
dunia.add(pitaJalan(kurvaJalan, 100, matStandar(0xf4e7c3), 0.3));
{   // Garis tengah putus-putus.
    const geoGaris = new THREE.BoxGeometry(26, 1.2, 5);
    const garis = new THREE.InstancedMesh(geoGaris, matStandar(0xffffff), 30);
    const dummy = new THREE.Object3D();
    for (let i = 0; i < 30; i++) {
        const u = (i + 0.5) / 30;
        const p = kurvaJalan.getPoint(u), t = kurvaJalan.getTangent(u);
        dummy.position.set(p.x, 0.5, p.z);
        dummy.rotation.set(0, Math.atan2(t.x, t.z) + Math.PI / 2, 0);
        dummy.updateMatrix();
        garis.setMatrixAt(i, dummy.matrix);
    }
    dunia.add(garis);
}
function jalanSetapak(x1, y1, x2, y2, lebar = 24) {
    const a = KE3(x1, y1), b = KE3(x2, y2);
    const panjang = a.distanceTo(b);
    const m = new THREE.Mesh(new THREE.PlaneGeometry(panjang, lebar), matStandar(0xe9d8a6));
    m.rotation.x = -Math.PI / 2;
    m.rotation.z = -Math.atan2(b.z - a.z, b.x - a.x);
    m.position.set((a.x + b.x) / 2, 0.26, (a.z + b.z) / 2);
    m.receiveShadow = true;
    dunia.add(m);
}
jalanSetapak(1000, 925, 1000, 330);    // plaza ke Gang Dagang
jalanSetapak(1508, 850, 1747, 310);    // jalan utama ke Gang Kuliner
jalanSetapak(425, 925, 330, 760);      // jalan utama ke Gang Jasa
jalanSetapak(1083, 995, 947, 1200);    // jalan utama ke kavling 7
jalanSetapak(1642, 783, 1470, 680);    // jalan utama ke Gang Kerajinan

// ---------------------------------------------------------------------------
// Kolam di tenggara desa
// ---------------------------------------------------------------------------
let bahanAir = null;
{
    const c = KE3(KOLAM.x, KOLAM.y);
    const pantai = new THREE.Mesh(new THREE.CircleGeometry(KOLAM.r + 32, 36), matStandar(0xd9c48d));
    pantai.rotation.x = -Math.PI / 2; pantai.position.set(c.x, 0.24, c.z); pantai.receiveShadow = true;
    dunia.add(pantai);
    bahanAir = new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.9, roughness: 0.35, metalness: 0 });
    const air = new THREE.Mesh(new THREE.CircleGeometry(KOLAM.r, 36), bahanAir);
    air.rotation.x = -Math.PI / 2; air.position.set(c.x, 0.45, c.z);
    dunia.add(air);
    // Daun teratai dan bunganya.
    const teratai = new THREE.InstancedMesh(new THREE.CircleGeometry(11, 10), matStandar(0x16a34a), 6);
    const dummy = new THREE.Object3D();
    [[-70, -30], [40, 45], [95, -55], [-15, 70], [60, -95], [-105, 25]].forEach(([dx, dz], i) => {
        dummy.position.set(c.x + dx, 0.55, c.z + dz);
        dummy.rotation.set(-Math.PI / 2, 0, i * 1.1);
        dummy.scale.setScalar(0.8 + (i % 3) * 0.3);
        dummy.updateMatrix();
        teratai.setMatrixAt(i, dummy.matrix);
    });
    dunia.add(teratai);
    const bunga = new THREE.Mesh(new THREE.SphereGeometry(5, 8, 6), matStandar(0xf472b6));
    bunga.position.set(c.x - 68, 2, c.z - 28);
    dunia.add(bunga);
}

// ---------------------------------------------------------------------------
// Balai Desa dan tiang bendera
// ---------------------------------------------------------------------------
let bendera = null, geoBenderaAsli = null;
{
    // Tiang bendera di sisi timur alun-alun, dekat titik berdiri Bang Uju.
    const tiangPos = KE3(1205, 700);
    const tiang = new THREE.Mesh(new THREE.CylinderGeometry(4, 5, 250, 8), matStandar(0x92600f));
    tiang.position.set(tiangPos.x, 125, tiangPos.z);
    tiang.castShadow = true;
    dunia.add(tiang);
    // Bendera memakai lambang resmi Kejar Target (logo di kejartarget.id),
    // disimpan di assets/img supaya tetap tampil tanpa internet. Teksturnya
    // baru terpasang setelah gambar selesai dimuat; sebelum itu benderanya
    // polos biru supaya tidak berkedip putih.
    const c = document.createElement('canvas'); c.width = 256; c.height = 144;
    const g = c.getContext('2d');
    g.fillStyle = '#ffffff'; g.fillRect(0, 0, c.width, c.height);
    const texBendera = new THREE.CanvasTexture(c);
    texBendera.colorSpace = THREE.SRGBColorSpace;
    texBendera.anisotropy = 4;
    const gambar = new Image();
    gambar.onload = () => {
        // Latar bendera PUTIH, bukan biru. Lambang Kejar Target berwarna biru
        // cerah; di atas bendera biru logo jadi menyatu dan terlihat gelap.
        // Dengan latar putih, logonya terbaca jelas dari jauh.
        const tinggiLambang = c.height * 0.78;
        const lebarLambang = tinggiLambang * (gambar.width / gambar.height);
        g.clearRect(0, 0, c.width, c.height);
        g.fillStyle = '#ffffff';
        g.fillRect(0, 0, c.width, c.height);
        // Pita biru tipis di tepi atas dan bawah sebagai penegas warna desa.
        g.fillStyle = '#2563eb';
        g.fillRect(0, 0, c.width, Math.round(c.height * 0.06));
        g.fillRect(0, c.height - Math.round(c.height * 0.06), c.width, Math.round(c.height * 0.06));
        g.drawImage(gambar, (c.width - lebarLambang) / 2, (c.height - tinggiLambang) / 2, lebarLambang, tinggiLambang);
        texBendera.needsUpdate = true;
    };
    gambar.src = '<?= $awalan ?>assets/img/logo-kejar-target.png';
    const geoB = new THREE.PlaneGeometry(82, 46, 10, 2);
    geoB.translate(41, 0, 0);
    // Bendera memakai material yang tidak menerima bayangan dan sedikit
    // memancarkan warnanya sendiri. Material standar biasa membuat logo biru
    // terlihat hampir hitam saat sisi yang membelakangi matahari.
    bendera = new THREE.Mesh(geoB, new THREE.MeshStandardMaterial({
        map: texBendera,
        side: THREE.DoubleSide,
        roughness: 0.75,
        metalness: 0,
        emissive: 0xffffff,
        emissiveMap: texBendera,
        emissiveIntensity: 0.55,
    }));
    bendera.castShadow = false;
    bendera.receiveShadow = false;
    bendera.position.set(tiangPos.x + 3, 222, tiangPos.z);
    dunia.add(bendera);
    geoBenderaAsli = geoB.attributes.position.array.slice();
}

// ---------------------------------------------------------------------------
// Bangunan dari data: grup khusus yang dibangun (dan bisa dibangun ulang)
// dari daftar bangunan. Sumbernya data-desa.php; selama data langsung
// belum tiba, data contoh yang dipakai. Balai Desa tidak lagi hardcode:
// ia baris pertama basis data, dipilih oleh varianBangunan() seperti
// bangunan lain, menempati kavling sesuai urutan id-nya.
// ---------------------------------------------------------------------------
const grupBangunan = new THREE.Group();
dunia.add(grupBangunan);
let klikBangunan = [];
// Warna gang berasal dari WARNA_GANG (klarifikasi tampilan), bukan dari server.
function warnaGang(gang, cadangan = 0xf59e0b) {
    const w = WARNA_GANG[gang];
    return w ? w.warna : cadangan;
}
function cssGang(gang) {
    const w = WARNA_GANG[gang];
    return w ? w.css : '#64748b';
}
function bangunBangunan(daftar) {
    for (const o of klikBangunan) {
        const i = bisaDiklik.indexOf(o);
        if (i >= 0) bisaDiklik.splice(i, 1);
    }
    klikBangunan = [];
    while (grupBangunan.children.length) grupBangunan.remove(grupBangunan.children[0]);
    const terisi = new Set();
    const urut = [...daftar].sort((a, b) => (a.kavling ?? 0) - (b.kavling ?? 0));
    for (const b of urut) {
        const k = Number.isInteger(b.kavling) ? b.kavling : urut.indexOf(b);
        if (k < 0 || k >= POSISI_KAVLING.length || terisi.has(k)) continue;
        terisi.add(k);
        const model = buatBangunan(b.nama, warnaGang(b.gang));
        const pos = KE3(POSISI_KAVLING[k][0], POSISI_KAVLING[k][1]);
        model.position.copy(pos);
        const popover = {
            kicker: 'DETAIL BANGUNAN',
            judul: b.nama,
            lencana: b.gang ? { teks: b.gang, warna: cssGang(b.gang) } : null,
            kutipan: b.komentar ? '“' + b.komentar + '”' : null,
            isi: (b.dari ? 'Diminta oleh ' + b.dari + '.' : '') + (b.tanggal ? ' Berdiri ' + b.tanggal + '.' : ''),
        };
        model.userData.kavling = k;
        model.traverse(o => { o.userData.popover = popover; });
        grupBangunan.add(model);
        klikBangunan.push(model);
        bisaDiklik.push(model);
        if (varianBangunan(b.nama) === 'balai') {
            // Label melayang di atas atap landmark desa.
            const label = labelNama('Balai Desa', 1.35);
            label.position.set(pos.x, 352, pos.z);
            grupBangunan.add(label);
        }
    }
    POSISI_KAVLING.forEach(([x, y], i) => {
        if (!terisi.has(i)) grupBangunan.add(penandaKosong(x, y));
    });
}

// Label nama melayang (sprite tekstur kanvas).
function labelNama(teks, skala = 1) {
    const c = document.createElement('canvas'); c.width = 256; c.height = 72;
    const g = c.getContext('2d');
    g.font = '800 38px system-ui, sans-serif';
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.lineWidth = 9; g.strokeStyle = 'rgba(17,24,39,0.65)';
    g.strokeText(teks, 128, 38);
    g.fillStyle = '#ffffff';
    g.fillText(teks, 128, 38);
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
    s.scale.set(104 * skala, 29 * skala, 1);
    return s;
}

// ---------------------------------------------------------------------------
// Penanda Tanah Kosong di 11 kavling lain
// ---------------------------------------------------------------------------
function penandaKosong(x, y) {
    const grup = new THREE.Group();
    const pos = KE3(x, y);
    grup.position.copy(pos);
    const lapang = new THREE.Mesh(new THREE.CircleGeometry(96, 26), matStandar(0xe9d8a6));
    lapang.rotation.x = -Math.PI / 2; lapang.position.y = 0.28; lapang.receiveShadow = true;
    grup.add(lapang);
    const bahanKayu = matStandar(0x92600f);
    grup.add(kotak(6, 58, 6, bahanKayu, -40, 29, 0));
    grup.add(kotak(6, 58, 6, bahanKayu, 40, 29, 0));
    const papan = new THREE.Mesh(
        new THREE.BoxGeometry(124, 42, 6),
        [bahanKayu, bahanKayu, bahanKayu, bahanKayu,
         new THREE.MeshStandardMaterial({ map: teksturPapan('Tanah Kosong', { ukuran: 52 }), roughness: 0.85 }),
         new THREE.MeshStandardMaterial({ map: teksturPapan('Tanah Kosong', { ukuran: 52 }), roughness: 0.85 })]
    );
    papan.position.y = 64; papan.castShadow = true;
    grup.add(papan);
    grup.userData.popover = { kicker: 'KAVLING KOSONG', judul: 'Tanah Kosong', lencana: null, kutipan: null, isi: 'Kavling ini menunggu komentar pemenang. Usulkan usahamu di Instagram @desatarget, komentar paling banyak disukai dibangun di sini.' };
    klikBangunan.push(grup);
    bisaDiklik.push(grup);
    return grup;
}
// Bangun awal memakai data contoh; data langsung menggantikannya begitu tiba.
bangunBangunan(BANGUNAN_SEED);

// ---------------------------------------------------------------------------
// Pepohonan dan alam: instancing supaya draw call tetap sedikit.
// Sebaran ditolak bila menabrak zona, kavling, jalan, alun-alun, atau kolam.
// ---------------------------------------------------------------------------
function titikTabrakan(x, y) {
    if (x < 45 || x > 1955 || y < 45 || y > 1288) return true;
    for (const z of Object.values(ZONA_GANG)) {
        const [tx, ty, tw, th] = z.tambak.map(v => v * SKALA);
        if (x > tx - 14 && x < tx + tw + 14 && y > ty - 14 && y < ty + th + 14) return true;
    }
    for (const r of rintanganKavling) if (x > r.x1 - 24 && x < r.x2 + 24 && y > r.y1 - 24 && y < r.y2 + 40) return true;
    if (Math.hypot(x - FOKUS.x, y - 714) < 225) return true;
    if (Math.hypot(x - KOLAM.x, y - KOLAM.y) < KOLAM.r + 70) return true;
    for (const p of contohJalan) if (Math.hypot(x - p.x, y - p.y) < 92) return true;
    return false;
}
function kumpulkanTitik(jumlah, terima) {
    const hasil = [];
    let gagal = 0;
    while (hasil.length < jumlah && gagal < jumlah * 60) {
        const x = 40 + acak() * 1920, y = 40 + acak() * 1253;
        if (titikTabrakan(x, y) || !terima(x, y, hasil)) { gagal++; continue; }
        hasil.push({ x, y, s: 0.8 + acak() * 0.55, r: acak() * Math.PI * 2 });
    }
    return hasil;
}
function jauhDariSemua(hasil, jarak) {
    return (x, y) => hasil.every(p => Math.hypot(x - p.x, y - p.y) > jarak);
}
{
    const dummy = new THREE.Object3D();
    const buatInstansi = (geo, bahan, daftar, fn) => {
        const im = new THREE.InstancedMesh(geo, bahan, daftar.length);
        daftar.forEach((p, i) => { fn(p, dummy); dummy.updateMatrix(); im.setMatrixAt(i, dummy.matrix); });
        im.castShadow = true; im.receiveShadow = true;
        dunia.add(im);
        return im;
    };
    // Pohon bulat: batang + tajuk utama + 2 tajuk samping.
    const titikBulat = kumpulkanTitik(48, () => true);
    const terimaPisah = jauhDariSemua(titikBulat, 120);
    buatInstansi(new THREE.CylinderGeometry(7, 10, 36, 7), matStandar(0x92600f), titikBulat,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 18 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    buatInstansi(new THREE.SphereGeometry(31, 9, 7), matStandar(0x22c55e), titikBulat,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 70 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    const titikSamping = [];
    titikBulat.forEach(p => { titikSamping.push({ ...p, dx: -20, dy: 54 }); titikSamping.push({ ...p, dx: 20, dy: 54 }); });
    buatInstansi(new THREE.SphereGeometry(21, 8, 6), matStandar(0x4ade80), titikSamping,
        (p, d) => { const c = KE3(p.x + p.dx, p.y); d.position.set(c.x, p.dy * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    // Pinus: batang + 3 kerucut bertumpuk.
    const titikPinus = kumpulkanTitik(24, terimaPisah);
    buatInstansi(new THREE.CylinderGeometry(6, 9, 28, 7), matStandar(0x78350f), titikPinus,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 14 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    const titikKerucut = [];
    titikPinus.forEach(p => { titikKerucut.push({ ...p, dy: 58, r: 31 }); titikKerucut.push({ ...p, dy: 88, r: 23 }); titikKerucut.push({ ...p, dy: 112, r: 15 }); });
    buatInstansi(new THREE.ConeGeometry(1, 52, 8), matStandar(0x15803d), titikKerucut,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, p.dy * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.set(p.r * p.s, p.s, p.r * p.s); });
    // Pohon buah: tajuk bulat plus titik buah oranye.
    const titikBuah = kumpulkanTitik(14, jauhDariSemua(titikPinus, 120));
    buatInstansi(new THREE.CylinderGeometry(7, 10, 34, 7), matStandar(0x92600f), titikBuah,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 17 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    buatInstansi(new THREE.SphereGeometry(29, 9, 7), matStandar(0x16a34a), titikBuah,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 64 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.setScalar(p.s); });
    const titikBuahKecil = [];
    titikBuah.forEach(p => { for (let i = 0; i < 5; i++) { const a = acak() * Math.PI * 2; titikBuahKecil.push({ x: p.x + Math.cos(a) * 24, y: p.y + Math.sin(a) * 10, dy: 52 + acak() * 22, s: p.s }); } });
    buatInstansi(new THREE.SphereGeometry(4.4, 6, 5), matStandar(0xf97316), titikBuahKecil,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, p.dy * p.s, c.z); d.rotation.set(0, 0, 0); d.scale.setScalar(1); });
    // Semak, batu, dan rumpun rumput.
    const titikSemak = kumpulkanTitik(52, () => true);
    buatInstansi(new THREE.SphereGeometry(15, 8, 6), matStandar(0x4ade80), titikSemak,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 8 * p.s, c.z); d.rotation.set(0, p.r, 0); d.scale.set(p.s, p.s * 0.62, p.s); });
    const titikBatu = kumpulkanTitik(22, () => true);
    buatInstansi(new THREE.DodecahedronGeometry(12, 0), matStandar(0xa8a29e), titikBatu,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 6 * p.s, c.z); d.rotation.set(p.r, p.r * 2, 0); d.scale.setScalar(p.s); });
    const titikRumput = [];
    for (let i = 0; i < 380; i++) {
        const x = 30 + acak() * 1940, y = 30 + acak() * 1273;
        if (Math.hypot(x - KOLAM.x, y - KOLAM.y) < KOLAM.r + 40) continue;
        titikRumput.push({ x, y, s: 0.7 + acak() * 0.8, r: acak() * Math.PI });
    }
    const rumput = new THREE.InstancedMesh(new THREE.ConeGeometry(6, 17, 5), matStandar(0x4ade80), titikRumput.length);
    titikRumput.forEach((p, i) => { const c = KE3(p.x, p.y); dummy.position.set(c.x, 8, c.z); dummy.rotation.set(0, p.r, 0); dummy.scale.setScalar(p.s); dummy.updateMatrix(); rumput.setMatrixAt(i, dummy.matrix); });
    dunia.add(rumput);
    // Deretan pohon di tepi desa (penanda batas tanah, seperti versi 4.3).
    const titikTepi = [];
    for (let x = 90; x < 1980; x += 128) { titikTepi.push({ x: x + acak() * 30, y: 26 + acak() * 16 }); titikTepi.push({ x: x + acak() * 30, y: 1300 - acak() * 16 }); }
    for (let y = 150; y < 1250; y += 128) { titikTepi.push({ x: 26 + acak() * 16, y }); titikTepi.push({ x: 1974 - acak() * 16, y }); }
    const tepiSah = titikTepi.filter(p => !titikTabrakan(p.x, p.y));
    buatInstansi(new THREE.CylinderGeometry(7, 10, 36, 7), matStandar(0x92600f), tepiSah,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 18, c.z); d.rotation.set(0, 0, 0); d.scale.setScalar(1.05); });
    buatInstansi(new THREE.SphereGeometry(31, 9, 7), matStandar(0x22c55e), tepiSah,
        (p, d) => { const c = KE3(p.x, p.y); d.position.set(c.x, 72, c.z); d.rotation.set(0, 0, 0); d.scale.setScalar(1.05); });
    // Lampu jalan di sepanjang jalan utama (posisi dari peta 2D).
    const titikLampu = [[120, 648], [330, 556], [560, 622], [770, 512], [1005, 420]].map(([x, y]) => KE3(x * SKALA, y * SKALA));
    buatInstansi(new THREE.CylinderGeometry(3.5, 4.5, 66, 7), matStandar(0x475569), titikLampu,
        (p, d) => { d.position.set(p.x, 33, p.z); d.rotation.set(0, 0, 0); d.scale.setScalar(1); });
    bahanKepalaLampu = matStandar(0xfef08a, { emissive: 0xfacc15, emissiveIntensity: 0.7 });
    buatInstansi(new THREE.SphereGeometry(8.5, 8, 6), bahanKepalaLampu, titikLampu,
        (p, d) => { d.position.set(p.x, 70, p.z); d.rotation.set(0, 0, 0); d.scale.setScalar(1); });
    // Pagar kayu di tepi beberapa zona (garis dari peta 2D, koordinat lokal).
    const bahanPagar = matStandar(0xb45309);
    const garisPagar = [
        [[24, 196], [236, 196]], [[24, 462], [236, 462]], [[950, 186], [1102, 186]],
        [[768, 438], [926, 438]], [[122, 610], [242, 610]],
    ];
    for (const [[ax, ay], [bx, by]] of garisPagar) {
        const a = KE3(ax * SKALA, ay * SKALA), b = KE3(bx * SKALA, by * SKALA);
        const panjang = a.distanceTo(b);
        for (const tinggiRel of [13, 25]) {
            const rel = new THREE.Mesh(new THREE.BoxGeometry(panjang, 4.5, 4.5), bahanPagar);
            rel.position.set((a.x + b.x) / 2, tinggiRel, (a.z + b.z) / 2);
            rel.rotation.y = -Math.atan2(b.z - a.z, b.x - a.x);
            rel.castShadow = true;
            dunia.add(rel);
        }
        const jumlahTiang = Math.max(2, Math.round(panjang / 62));
        for (let i = 0; i <= jumlahTiang; i++) {
            const t = i / jumlahTiang;
            const tiangPagar = new THREE.Mesh(new THREE.BoxGeometry(6, 30, 6), bahanPagar);
            tiangPagar.position.set(a.x + (b.x - a.x) * t, 15, a.z + (b.z - a.z) * t);
            tiangPagar.castShadow = true;
            dunia.add(tiangPagar);
        }
    }
    // Awan low-poly melayang.
    const bahanAwan = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 1, flatShading: true, transparent: true, opacity: 0.92 });
    window.__awan = [];
    for (let i = 0; i < 4; i++) {
        const awan = new THREE.Group();
        const pangkal = new THREE.Mesh(new THREE.SphereGeometry(46, 8, 6), bahanAwan);
        pangkal.scale.set(1.5, 0.5, 1);
        awan.add(pangkal);
        const samping = new THREE.Mesh(new THREE.SphereGeometry(32, 8, 6), bahanAwan);
        samping.position.set(52, 4, 6); samping.scale.set(1.3, 0.5, 1);
        awan.add(samping);
        awan.position.set(-1100 + i * 620, 300 + (i % 3) * 55, -500 + (i % 4) * 300);
        dunia.add(awan);
        window.__awan.push(awan);
    }
}

// ---------------------------------------------------------------------------
// Manusia: warga berwarna gang, Bang Uju (sprite), dan pengunjung "Kamu"
// ---------------------------------------------------------------------------
function buatFigur(warnaBadan, { topi = null, kulit = 0xfcd9b8 } = {}) {
    const grup = new THREE.Group();
    const dalam = new THREE.Group();
    grup.add(dalam);
    const bahan = matStandar(warnaBadan);
    const badan = new THREE.Mesh(new THREE.CylinderGeometry(10.5, 13.5, 37, 10), bahan);
    badan.position.y = 32; badan.castShadow = true;
    dalam.add(badan);
    const kepala = new THREE.Mesh(new THREE.SphereGeometry(11.5, 10, 8), matStandar(kulit));
    kepala.position.y = 60; kepala.castShadow = true;
    dalam.add(kepala);
    if (topi) {
        const t = new THREE.Mesh(new THREE.CylinderGeometry(12, 12.5, 6, 10), matStandar(topi));
        t.position.y = 68;
        dalam.add(t);
        const brim = new THREE.Mesh(new THREE.BoxGeometry(15, 3, 10), matStandar(topi));
        brim.position.set(0, 66, 11);
        dalam.add(brim);
    }
    grup.userData.dalam = dalam;
    return grup;
}
const agenWarga = [];
// Warga yang dirender dibatasi 80 figur, meniru batas peta 2D.
const BATAS_WARGA = 80;
function bangunWarga(daftar) {
    for (const a of agenWarga) {
        dunia.remove(a.figur);
        const iKlik = bisaDiklik.indexOf(a.figur);
        if (iKlik >= 0) bisaDiklik.splice(iKlik, 1);
    }
    agenWarga.length = 0;
    const perGang = {};
    daftar.slice(0, BATAS_WARGA).forEach((w, i) => {
        const z = ZONA_GANG[w.gang];
        if (!z) return;
        const rect = rectWara(z);
        perGang[w.gang] = perGang[w.gang] || 0;
        const idx = perGang[w.gang]++;
        // Titik berdiri awal mengikuti grid zona versi 2D (awal + dx/dy),
        // jatuh kembali ke titik acak di rect wara bila di luar.
        const t2 = z.tambak;
        const gridX = (t2[0] + 27 + (idx % 5) * 36) * SKALA;
        const gridY = (t2[1] + 82 + Math.floor(idx / 5) * 34) * SKALA;
        const diDalam = gridX >= rect.x && gridX <= rect.x + rect.w && gridY >= rect.y && gridY <= rect.y + rect.h;
        const rumah = diDalam ? { x: gridX, y: gridY } : { x: rect.x + rect.w * (0.25 + 0.5 * acak()), y: rect.y + rect.h * (0.25 + 0.5 * acak()) };
        // Warna pilihan warga sendiri (dari KTP). Bila data belum membawa
        // warna -- mis. server lama -- pakai warna gang seperti dulu.
        const warnaFigur = w.baju ? new THREE.Color(w.baju).getHex() : z.warna;
        const warnaKulit = w.kulit ? new THREE.Color(w.kulit).getHex() : 0xfcd9b8;
        const warnaTopi = w.topi ? new THREE.Color(w.topi).getHex() : null;
        const figur = buatFigur(warnaFigur, { kulit: warnaKulit, topi: warnaTopi });
        figur.position.copy(KE3(rumah.x, rumah.y));
        const label = labelNama(w.nama, 0.82);
        label.position.y = 92;
        // Label ikut jarak kamera: saat seluruh desa dilihat dari jauh, label
        // semua warga akan saling menimpa jadi gumpalan tak terbaca. Label
        // disembunyikan bila kamera terlalu jauh, lalu muncul lagi saat
        // pengunjung mendekat. Aturannya di perbaruiLabelWarga().
        label.userData.labelWarga = true;
        figur.add(label);
        figur.userData.popover = { kicker: 'WARGA DESA', judul: w.nama, lencana: { teks: w.gang, warna: z.css }, kutipan: null, isi: 'Nomor warga ' + w.nomor + '. Warga ' + w.gang + ' yang sedang keliling lingkungan gang-nya.' };
        dunia.add(figur);
        bisaDiklik.push(figur);
        agenWarga.push({
            figur, label, dalam: figur.userData.dalam, rect,
            x: rumah.x, y: rumah.y, rumah: { ...rumah },
            target: null, kecepatan: 26 + acak() * 18,
            tunggu: 1 + i * 1.7 + acak() * 4, fase: acak() * Math.PI * 2,
            jedaPopover: false,
        });
    });
}
bangunWarga(WARGA_SEED);
// Bang Uju: sprite billboard dari gambar resmi maskot, patroli alun-alun.
const agenUju = { x: titikPatroli[0][0], y: titikPatroli[0][1], target: null, indeks: 0, tunggu: 2, kecepatan: 26, jedaPopover: false };
let spriteUju = null;
{
    const tex = new THREE.TextureLoader().load('<?= $awalan ?>assets/bang-uju-peta.png', (t) => {
        const aspek = t.image.width / t.image.height;
        spriteUju.scale.set(168 * aspek, 168, 1);
    });
    spriteUju = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
    spriteUju.scale.set(130, 168, 1);
    const pos = KE3(agenUju.x, agenUju.y);
    spriteUju.position.set(pos.x, 84, pos.z);
    spriteUju.userData.popover = { kicker: 'KEPALA DESA', judul: 'Bang Uju', lencana: null, kutipan: '“Ikutin Bang Uju, biar sama-sama sampai tujuan!”', isi: 'Kepala Desa Target yang sedang ronda keliling alun-alun Balai Desa.' };
    dunia.add(spriteUju);
    bisaDiklik.push(spriteUju);
    const label = labelNama('Bang Uju', 0.95);
    label.position.set(pos.x, 196, pos.z);
    label.name = 'labelUju';
    dunia.add(label);
}
// Pengunjung "Kamu" untuk mode Keliling Desa.
//
// Titik lahir sengaja di depan halaman Balai Desa, BUKAN di dalam tapaknya.
// Rumus lama (FOKUS.y + 88) jatuh di dalam kotak tapak gedung (566..818),
// jadi setiap langkah ditolak tabrakanJalan() dan pemain terkunci di tempat
// walau tombol jalan sudah menyala. y = FOKUS.y + 200 menaruhnya di halaman,
// beberapa langkah dari pintu supaya chip "Masuk Balai Desa" tetap dekat.
const kamu = { x: FOKUS.x, y: FOKUS.y + 200, arah: Math.PI, figur: null };
{
    kamu.figur = buatFigur(0x0d9488, { topi: 0xdc2626 });
    kamu.figur.position.copy(KE3(kamu.x, kamu.y));
    const label = labelNama('Kamu', 0.82);
    label.position.y = 92;
    // Label ini tidak pernah ditampilkan di mode jalan: kamera sudah berada
    // di belakang Kamu, jadi nama besar di atas kepala hanya menghalangi
    // pandangan. Disimpan supaya bisa diatur lagi bila perlu.
    label.visible = false;
    kamu.label = label;
    kamu.figur.add(label);
    kamu.figur.visible = false;
    dunia.add(kamu.figur);
}

// ---------------------------------------------------------------------------
// Pantulan saat sesuatu diketuk.
//
// Setiap objek yang diketuk "melompat" sekali lalu mendarat lagi, seperti
// balon yang dipencet: cepat naik, melambat di puncak, jatuh lebih cepat,
// lalu memantul kecil. Tujuannya memberi balasan seketika bahwa ketukan
// benar-benar mengenai sesuatu -- penting di peta 3D karena popover baru
// muncul di pojok dan mudah terlewat.
//
// Diatur lewat satu peta (objek -> waktu mulai) yang diperiksa sekali per
// bingkai, bukan setTimeout per objek. Dengan begitu animasinya tidak pernah
// saling menimpa posisi: begitu peta 3D hanya 3 fps, animasi berbasis CSS
// atau tween terpisah akan tertinggal.
// ---------------------------------------------------------------------------
const bounce = {
    aktif: [],          // [{ simpul, mulai, durasi, tinggi, dasar }]
    // 760 ms, bukan 400-500: di ponsel kelas menengah peta ini hanya 20-30
    // fps, dan di perangkat tanpa GPU 3 fps. Lompatan 0,5 detik bisa habis
    // di antara dua bingkai sehingga tidak terlihat sama sekali.
    lebar: 760,
    tinggi: 34,         // cm lompatan; disesuaikan ukuran objek
};

function tentukanTinggi(simpul) {
    // Warga & Bang Uju kecil (tinggi ~75), bangunan besar (ratusan).
    const kotak = new THREE.Box3().setFromObject(simpul);
    // Warga setinggi ~75: lompatan 26 terlihat jelas dari jarak orbit.
    // Bangunan ratusan: 12% cukup untuk "mengangguk" tanpa pindah tempat.
    const tinggi = Math.max(1, kotak.max.y - kotak.min.y);
    return Math.min(210, Math.max(26, tinggi * 0.12));
}

// Ketuk simpul apa pun: lompat sekali. Bila sedang melompat, mulai lagi
// dari titik dasar (bukan dari posisi tengah udara) supaya tidak melayang.
function lompat(simpul) {
    if (!simpul) return;
    // Bangunan adalah grup yang posisinya juga dipakai sorot; aman karena
    // kita hanya menambah-di-atas dan mengembalikannya ke dasar semula.
    let catatan = bounce.aktif.find(b => b.simpul === simpul);
    if (!catatan) {
        catatan = { simpul, dasar: simpul.position.y, mulai: 0, durasi: 0, tinggi: 0 };
        bounce.aktif.push(catatan);
    }
    // Konversi posisi.y sekarang ke dasar memakai fase terakhir.
    catatan.dasar = simpul.position.y - (catatan.offsetSebelumnya || 0);
    catatan.mulai = performance.now();
    catatan.durasi = bounce.lebar;
    catatan.tinggi = catatan.tinggi || tentukanTinggi(simpul);
    catatan.offsetSebelumnya = 0;
}

// Kurva lompat: naik cepat, tahan sebentar di puncak, mendarat, memantul
// kecil dua kali. Nilainya 0..1 (0 = di tanah).
function kurvaLompat(t) {
    if (t >= 1) return 0;
    if (t < 0.36) {
        // Naik: diperlambat (ease-out) supaya puncaknya terasa ringan.
        const k = t / 0.36;
        return k * k * (3 - 2 * k);
    }
    if (t < 0.62) {
        // Jatuh: dipercepat (ease-in), berat seperti benda sungguhan.
        const k = (t - 0.36) / 0.26;
        return 1 - k * k;
    }
    // Pantulan kecil setelah mendarat.
    const k = (t - 0.62) / 0.38;
    return Math.abs(Math.sin(k * Math.PI * 2)) * 0.13 * (1 - k);
}

// Offset lompatan yang sedang berlaku untuk sebuah simpul (0 bila diam).
function offsetLompat(simpul) {
    for (const b of bounce.aktif) {
        if (b.simpul === simpul) return b.offsetSebelumnya || 0;
    }
    return 0;
}

function perbaruiBounce(sekarang) {
    if (!bounce.aktif.length) return;
    for (let i = bounce.aktif.length - 1; i >= 0; i--) {
        const b = bounce.aktif[i];
        const t = (sekarang - b.mulai) / b.durasi;
        // Lepas objek yang sudah selesai atau disembunyikan pemiliknya.
        if (t >= 1 || !b.simpul.parent) {
            if (b.simpul.parent) b.simpul.position.y = b.dasar;
            bounce.aktif.splice(i, 1);
            continue;
        }
        const naik = kurvaLompat(t) * b.tinggi;
        b.offsetSebelumnya = naik;
        b.simpul.position.y = b.dasar + naik;
    }
}

// ---------------------------------------------------------------------------
// Mesin wara-wiri (aturan sama seperti versi 4.3)
// ---------------------------------------------------------------------------
function dalamRintangan(x, y) {
    return rintanganKavling.some(r => x > r.x1 && x < r.x2 && y > r.y1 && y < r.y2);
}
function pilihTarget(agen) {
    for (let coba = 0; coba < 8; coba++) {
        const tx = agen.rect.x + Math.random() * agen.rect.w;
        const ty = agen.rect.y + Math.random() * agen.rect.h;
        const jarak = Math.hypot(tx - agen.x, ty - agen.y);
        if (jarak < 34 || jarak > 200) continue;
        let aman = true;
        for (let s = 1; s <= 5; s++) {
            const px = agen.x + (tx - agen.x) * s / 5, py = agen.y + (ty - agen.y) * s / 5;
            if (dalamRintangan(px, py)) { aman = false; break; }
        }
        if (aman) return { x: tx, y: ty };
    }
    return null;
}
function gerakkanAgen(agen, dt, patroli = false) {
    if (agen.jedaPopover) return;
    if (agen.tunggu > 0) { agen.tunggu -= dt; return; }
    if (!agen.target) {
        if (patroli) {
            agen.indeks = (agen.indeks + 1) % titikPatroli.length;
            agen.target = { x: titikPatroli[agen.indeks][0], y: titikPatroli[agen.indeks][1] };
        } else {
            agen.target = pilihTarget(agen);
            if (!agen.target) { agen.tunggu = 1.2; return; }
        }
    }
    const dx = agen.target.x - agen.x, dy = agen.target.y - agen.y;
    const jarak = Math.hypot(dx, dy);
    const langkah = agen.kecepatan * dt;
    if (jarak <= Math.max(langkah, 2)) {
        agen.x = agen.target.x; agen.y = agen.target.y;
        agen.target = null;
        agen.tunggu = patroli ? 3.5 + Math.random() * 3 : 2.5 + Math.random() * 6.5;
    } else {
        agen.x += dx / jarak * langkah;
        agen.y += dy / jarak * langkah;
        agen.arahTarget = Math.atan2(dx, dy);
    }
}
function terapkanAgen(agen, waktu) {
    const pos = KE3(agen.x, agen.y);
    // Offset lompatan (bila figur baru saja diketuk) ditambahkan di atas
    // posisi dasar, karena posisi y ditulis ulang di sini tiap bingkai.
    agen.figur.position.set(pos.x, offsetLompat(agen.figur), pos.z);
    if (agen.arahTarget !== undefined) {
        let beda = agen.arahTarget - (agen.figur.rotation.y || 0);
        while (beda > Math.PI) beda -= Math.PI * 2;
        while (beda < -Math.PI) beda += Math.PI * 2;
        agen.figur.rotation.y += beda * 0.12;
    }
    const jalan = !!agen.target && agen.tunggu <= 0;
    agen.dalam.position.y = jalan ? Math.abs(Math.sin(waktu * 9 + agen.fase)) * 2.4 : Math.sin(waktu * 2 + agen.fase) * 1.5;
    agen.dalam.rotation.x = jalan ? 0.07 : 0;
}

// ---------------------------------------------------------------------------
// Mode Keliling Desa: jalan sebagai "Kamu"
// ---------------------------------------------------------------------------
let modeJalan = false;
const tombolJalan = document.getElementById('tombolJalan');
const dpad = document.getElementById('dpad');
const chipMasuk = document.getElementById('chipMasuk');
const chipJalan = document.getElementById('chipJalan');
const tombolDitekan = {};
const kunciDitekan = {};
const RINTANGAN_JALAN = [
    { x1: 852, y1: 566, x2: 1182, y2: 818 },   // tapak Balai Desa
    ...POSISI_KAVLING.slice(1).map(([x, y]) => ({ x1: x - 64, y1: y - 16, x2: x + 64, y2: y + 16 })), // penanda tanah kosong
];
function tabrakanJalan(x, y) {
    if (x < 46 || x > 1954 || y < 46 || y > 1287) return true;
    if (Math.hypot(x - KOLAM.x, y - KOLAM.y) < KOLAM.r + 14) return true;
    return RINTANGAN_JALAN.some(r => x > r.x1 - 14 && x < r.x2 + 14 && y > r.y1 - 14 && y < r.y2 + 14);
}

// Titik aman terdekat dari (x, y): dipakai sebagai titik lahir mode jalan
// supaya pemain tidak pernah mulai dari dalam rintangan. Bila sudah aman,
// titiknya tidak digeser sama sekali.
function titikJalanAman(x, y) {
    if (!tabrakanJalan(x, y)) return { x, y };
    for (let jari = 24; jari <= 400; jari += 24) {
        // Cincin di sekeliling titik: mulai dari selatan (depan gedung).
        for (let i = 0; i < 16; i++) {
            const sudut = Math.PI / 2 + (i * Math.PI * 2) / 16;
            const nx = x + Math.cos(sudut) * jari;
            const ny = y + Math.sin(sudut) * jari;
            if (!tabrakanJalan(nx, ny)) return { x: nx, y: ny };
        }
    }
    return { x, y };
}
function aturModeJalan(nyala) {
    modeJalan = nyala;
    tombolJalan.textContent = nyala ? 'Berhenti' : 'Keliling Desa';
    tombolJalan.classList.toggle('aktif', nyala);
    dpad.classList.toggle('tampil', nyala);
    chipJalan.classList.toggle('tampil', nyala);
    if (!nyala) chipMasuk.classList.remove('tampil');
    kamu.figur.visible = nyala;
    controls.enabled = !nyala;
    if (nyala) {
        // Jaga-jaga bila titik lahir (atau posisi terakhir) jatuh di dalam
        // rintangan karena data desa berubah: pindahkan ke titik aman
        // terdekat sebelum pemain mencoba berjalan.
        const aman = titikJalanAman(kamu.x, kamu.y);
        kamu.x = aman.x;
        kamu.y = aman.y;
        // Hadapkan pemain ke Balai Desa supaya langkah pertama terasa
        // menuju sesuatu, bukan membelakangi desa.
        const kePusat = Math.atan2(FOKUS.x - kamu.x, FOKUS.y - kamu.y);
        kamu.arah = kePusat;
        const pos = KE3(kamu.x, kamu.y);
        kamu.figur.position.set(pos.x, 0, pos.z);
        kamu.figur.rotation.y = kamu.arah;
        // Kamera langsung dipasang di belakang bahu. Dulu kamera dibiarkan
        // meluncur dari posisi orbitnya, dan selama peluncuran itu arah
        // pandang kamera masih berubah-ubah. Akibatnya W/D mendorong pemain
        // ke arah yang salah dan terasa seperti "tidak bisa jalan".
        letakkanKamera(kamu.arah, pos, true);
    } else {
        camera.position.copy(KAMERA_AWAL);
        controls.target.copy(TARGET_AWAL);
        controls.update();
    }
    tutupPopover();
}
tombolJalan.addEventListener('click', () => aturModeJalan(!modeJalan));
window.addEventListener('keydown', (e) => {
    if (!modeJalan) return;
    kunciDitekan[e.key.toLowerCase()] = true;
    if (e.key === 'Escape') aturModeJalan(false);
    if (['arrowup', 'arrowdown', 'arrowleft', 'arrowright', ' '].includes(e.key.toLowerCase())) e.preventDefault();
});
window.addEventListener('keyup', (e) => {
    kunciDitekan[e.key.toLowerCase()] = false;
    lepasSudutJalan();
});
dpad.querySelectorAll('button').forEach(btn => {
    const arah = btn.dataset.arah;
    const on = (e) => { e.preventDefault(); tombolDitekan[arah] = true; btn.classList.add('tertekan'); };
    const off = () => { tombolDitekan[arah] = false; btn.classList.remove('tertekan'); lepasSudutJalan(); };
    btn.addEventListener('pointerdown', on);
    btn.addEventListener('pointerup', off);
    btn.addEventListener('pointerleave', off);
    btn.addEventListener('pointercancel', off);
});
// Kamera orang ketiga di belakang bahu pemain.
//   seketika = true  -> dipasang langsung (saat mode jalan baru dinyalakan)
//   seketika = false -> meluncur lembut mengikuti pemain
// Peluncuran memakai faktor per detik, bukan per bingkai, supaya di ponsel
// yang bingkainya jarang tetap terasa responsif.
function letakkanKamera(arah, pos, seketika) {
    const belakang = new THREE.Vector3(Math.sin(arah), 0, Math.cos(arah));
    const tujuan = new THREE.Vector3(pos.x, 0, pos.z).addScaledVector(belakang, -178);
    tujuan.y = 104;
    if (seketika) {
        camera.position.copy(tujuan);
    } else {
        const sekarang = performance.now();
        const dt = Math.min((sekarang - (letakkanKamera.terakhir || sekarang)) / 1000, 0.1);
        letakkanKamera.terakhir = sekarang;
        // Sekitar 92% jarak ditempuh dalam seperempat detik.
        camera.position.lerp(tujuan, 1 - Math.pow(0.0001, dt));
    }
    camera.lookAt(pos.x, 46, pos.z);
}

// Lepas kunci sudut jalan hanya saat SEMUA tombol arah sudah dilepas.
// Kalau kunci dilepas begitu pemain berhenti sekejap, menekan tombol lagi
// menghitung sudut dari kamera yang masih berputar mengikuti pemain.
function adaTombolArah() {
    const arah = ['w', 'a', 's', 'd', 'arrowup', 'arrowleft', 'arrowdown', 'arrowright'];
    if (arah.some(k => kunciDitekan[k])) return true;
    return Object.values(tombolDitekan).some(Boolean);
}
function lepasSudutJalan() {
    if (!adaTombolArah()) sudutJalan.kunci = null;
}

// Arah "maju" & "kanan" untuk satu kombinasi tombol.
//
// Sudutnya DIKUNCI dari arah kamera pada saat tombol pertama kali ditekan,
// bukan dihitung ulang tiap bingkai dari arah hadap pemain. Kalau dihitung
// ulang, "kanan" ikut memutar pemain dan tombol yang ditahan membuat pemain
// berputar terus dalam lingkaran (W+D berujung balik ke titik awal).
// Dengan sudut terkunci, W+D selalu berarti "serong kanan-depan", sama
// seperti di game 3D lain, dan hasilnya tidak bergantung pada fps.
function sudutJalan(maju, samping) {
    const kunci = maju + ':' + samping;
    // Kunci sudut DIHITUNG SEKALI saat rangkaian tombol dimulai, lalu
    // dipertahankan sampai semua tombol arah dilepas. Dulu sudut dihitung
    // dari kamera yang sedang berputar mengikuti pemain, jadi "kanan" bisa
    // berbelok 120-180 derajat, bukan 90.
    if (sudutJalan.kunci !== kunci || sudutJalan.lepas) {
        sudutJalan.lepas = false;
        // Kamera melihat ke sasaran; "maju" = arah pandang kamera.
        const depan = new THREE.Vector3();
        camera.getWorldDirection(depan);
        depan.y = 0;
        if (depan.lengthSq() < 1e-6) depan.set(0, 0, -1);
        depan.normalize();
        // Sumbu x dunia bertambah ke kanan layar saat kamera menghadap -z.
        const kanan = new THREE.Vector3(-depan.z, 0, depan.x);
        const arah = depan.multiplyScalar(maju).add(kanan.multiplyScalar(samping));
        sudutJalan.kunci = kunci;
        sudutJalan.sudut = Math.atan2(arah.x, arah.z);
    }
    return sudutJalan.sudut;
}

function perbaruiKamu(dt) {
    const maju = (kunciDitekan['w'] || kunciDitekan['arrowup'] || tombolDitekan['maju'] ? 1 : 0) - (kunciDitekan['s'] || kunciDitekan['arrowdown'] || tombolDitekan['mundur'] ? 1 : 0);
    const samping = (kunciDitekan['d'] || kunciDitekan['arrowright'] || tombolDitekan['kanan'] ? 1 : 0) - (kunciDitekan['a'] || kunciDitekan['arrowleft'] || tombolDitekan['kiri'] ? 1 : 0);
    if (maju || samping) {
        // Koordinat dunia: x = kamu.x, z = kamu.y.
        const sudut = sudutJalan(maju, samping);
        const dx = Math.sin(sudut);
        const dz = Math.cos(sudut);
        const kecepatan = 135 * dt;
        const nx = kamu.x + dx * kecepatan;
        const nz = kamu.y + dz * kecepatan;
        // Gerak per sumbu supaya pemain bisa "menggeser" di sepanjang dinding
        // alih-alih berhenti total saat menyerempet rintangan.
        if (!tabrakanJalan(nx, kamu.y)) kamu.x = nx;
        if (!tabrakanJalan(kamu.x, nz)) kamu.y = nz;
        // Hadapkan pemain persis ke arah ia melangkah (termasuk serong).
        kamu.arah = sudut;
    } else {
        // Kunci baru dilepas saat SEMUA tombol arah sudah dilepas. Kalau
        // dilepas begitu saja ketika tidak ada tombol, menekan D lagi setelah
        // sesaat menghitung ulang dari kamera yang belum kembali ke posisi
        // bahu, dan pemain berbelok ke arah yang salah.
        sudutJalan.lepas = true;
    }
    kamu.figur.rotation.y = kamu.arah;
    const pos = KE3(kamu.x, kamu.y);
    kamu.figur.position.set(pos.x, 0, pos.z);
    letakkanKamera(kamu.arah, pos, false);
    perbaruiChipMasuk();
}

// Kedekatan ke pintu Balai Desa memunculkan chip masuk. Dipanggil juga saat
// pemain DIAM: dulu chip hanya diperbarui selama tombol jalan ditekan, jadi
// pemain yang berhenti tepat di depan pintu tidak pernah melihat tombolnya.
function perbaruiChipMasuk() {
    const dekatPintu = Math.hypot(kamu.x - FOKUS.x, kamu.y - (FOKUS.y + 88)) < 135;
    chipMasuk.classList.toggle('tampil', dekatPintu);
}
// ---------------------------------------------------------------------------
// Interior Balai Desa langsung di dalam peta 3D.
//
// Sebelumnya tombol "Masuk Balai Desa" membuang pengunjung ke peta 2D. Sekarang
// ruangannya tampil sebagai lapisan di atas kanvas: kamera, posisi Kamu, dan
// pilihan mode tidak berubah, jadi keluar berarti kembali persis ke tempat
// semula. Isi ruangan sama dengan peta 2D karena memakai interior_balai_svg().
// ---------------------------------------------------------------------------
const interior3d = document.getElementById('interior3d');
let interiorTerbuka3d = false;

function bukaInterior3d() {
    if (!interior3d) return;
    interiorTerbuka3d = true;
    // Mode jalan luar dimatikan supaya dua set tombol arah tidak berebut
    // tombol yang sama. Ruangan punya mesin gerak dan kanvasnya sendiri.
    aturModeJalan(false);
    tutupPopover();
    interior3d.hidden = false;
    // Ruangan dibangun di latar belakang setelah halaman dimuat; kalau
    // pengunjung masuk sangat cepat, beri waktu sebentar lalu coba lagi.
    let trial = 0;
    (function cobaBuka() {
        const mesin = window.__interior3d;
        if (mesin && mesin.buka()) return;
        if (trial++ < 40) { requestAnimationFrame(cobaBuka); return; }
        // Mesin ruangan tidak bisa dimuat (WebGL habis / Three.js gagal).
        // Jangan biarkan pengunjung menatap lapisan kosong: tampilkan
        // petunjuk dan sediakan jalan keluar.
        const petunjuk = document.getElementById('interior3dPetunjuk');
        if (petunjuk) petunjuk.textContent = 'Ruangan 3D belum bisa dibuka di perangkat ini. Tekan Keluar untuk kembali ke peta.';
    })();
    const keluar = document.getElementById('tombolKeluarInterior3d');
    if (keluar) setTimeout(() => keluar.focus(), 30);
}
function tutupInterior3d() {
    if (!interior3d) return;
    interiorTerbuka3d = false;
    if (window.__interior3d) window.__interior3d.tutup();
    interior3d.hidden = true;
    // Fokus kembali ke tombol yang membuka ruangan.
    if (chipMasuk) chipMasuk.focus();
}
chipMasuk.addEventListener('click', bukaInterior3d);

document.getElementById('tombolKeluarInterior3d')?.addEventListener('click', tutupInterior3d);
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && interiorTerbuka3d) {
        e.stopPropagation();
        tutupInterior3d();
    }
}, true);

// Hotspot di dalam interior: yang membuka kartu (Papan Pengumuman, Bang Uju)
// memakai popover yang sama dengan dunia; yang berupa tautan dibiarkan.
if (interior3d) {
    interior3d.querySelectorAll('.hotspot-interior[data-nama]').forEach((el) => {
        const buka = () => {
            bukaPopover({
                kicker: el.dataset.jenis === 'kades' ? 'KEPALA DESA' : 'PENGUMUMAN DESA',
                judul: el.dataset.nama || '',
                lencana: null,
                kutipan: el.dataset.komentar ? '“' + el.dataset.komentar + '”' : null,
                isi: el.dataset.info || '',
            }, null, window.innerWidth / 2 - 150, window.innerHeight / 2 - 60);
        };
        el.addEventListener('click', buka);
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buka(); }
        });
    });
}

// ---------------------------------------------------------------------------
// Keterbacaan label warga.
//
// Label nama melayang di atas kepala tiap warga. Saat kamera jauh (melihat
// seluruh desa), label semua warga bergabung jadi gumpalan yang tidak terbaca
// dan justru menutupi bangunan. Jadi label hanya ditampilkan saat kamera cukup
// dekat: makin dekat, makin banyak yang terbaca. Batu uji dibuat longgar
// supaya di layar ponsel pun tidak terasa "hilang".
// ---------------------------------------------------------------------------
const JARAK_LABEL_PENUH = 620;   // lebih dekat dari ini: semua label tampil
const JARAK_LABEL_MATI = 1500;   // lebih jauh dari ini: semua label disembunyikan

function perbaruiLabelWarga() {
    // Di mode jalan kamera tidak mengorbit controls.target, jadi jarak acuan
    // diukur dari pemain. Kalau tetap memakai controls.target, semua label
    // warga dinilai "jauh" dan hilang padahal pemain berjalan di sebelahnya.
    const jarak = modeJalan
        ? camera.position.distanceTo(kamu.figur.position)
        : camera.position.distanceTo(controls.target);
    // 0 = semua disembunyikan, 1 = semua tampil.
    const tampil = 1 - Math.min(1, Math.max(0, (jarak - JARAK_LABEL_PENUH) / (JARAK_LABEL_MATI - JARAK_LABEL_PENUH)));
    for (const a of agenWarga) {
        if (!a.label) continue;
        // Semakin jauh, label memudar; hilang total setelah ambang mati.
        a.label.visible = tampil > 0.02;
        if (a.label.visible) {
            a.label.material.opacity = tampil;
            // Ukuran bertambah sedikit saat dekat supaya tetap terbaca.
            const s = 0.95 + 0.25 * tampil;
            a.label.scale.set(104 * 0.82 * s, 29 * 0.82 * s, 1);
        }
    }
    if (window.__desa) window.__desa.labelTampil = Math.round(tampil * 100) / 100;
}
if (window.__desa) window.__desa.perbaruiLabelWarga = perbaruiLabelWarga;

// ---------------------------------------------------------------------------
// Popover hasil tap, panel Papan Gang, dan tombol UI
// ---------------------------------------------------------------------------
const popoverEl = document.getElementById('popover');
let agenJeda = null;
function agenDariObjek(obj) {
    if (obj === spriteUju) return agenUju;
    return agenWarga.find(a => a.figur === obj) || null;
}
function bukaPopover(data, objek, clientX, clientY) {
    document.getElementById('popKicker').textContent = data.kicker;
    document.getElementById('popJudul').textContent = data.judul;
    const lencana = document.getElementById('popLencana');
    if (data.lencana) {
        lencana.hidden = false;
        lencana.textContent = data.lencana.teks;
        lencana.style.background = data.lencana.warna;
    } else lencana.hidden = true;
    const kutipan = document.getElementById('popKutipan');
    if (data.kutipan) { kutipan.hidden = false; kutipan.textContent = data.kutipan; } else kutipan.hidden = true;
    document.getElementById('popIsi').textContent = data.isi;
    popoverEl.style.left = Math.min(clientX + 14, window.innerWidth - 316) + 'px';
    popoverEl.style.top = Math.min(clientY + 14, window.innerHeight - 190) + 'px';
    popoverEl.classList.add('tampil');
    if (agenJeda) agenJeda.jedaPopover = false;
    agenJeda = agenDariObjek(objek);
    if (agenJeda) agenJeda.jedaPopover = true;
}
function tutupPopover() {
    popoverEl.classList.remove('tampil');
    if (agenJeda) { agenJeda.jedaPopover = false; agenJeda = null; }
}
document.getElementById('popoverTutup').addEventListener('click', tutupPopover);
const raycaster = new THREE.Raycaster();
let titikTekan = null;
renderer.domElement.addEventListener('pointerdown', (e) => { titikTekan = { x: e.clientX, y: e.clientY }; });
renderer.domElement.addEventListener('pointerup', (e) => {
    if (!titikTekan) return;
    const geser = Math.hypot(e.clientX - titikTekan.x, e.clientY - titikTekan.y);
    titikTekan = null;
    if (geser > 6 || modeJalan) return;
    const ndc = new THREE.Vector2((e.clientX / window.innerWidth) * 2 - 1, -(e.clientY / window.innerHeight) * 2 + 1);
    raycaster.setFromCamera(ndc, camera);
    const potong = raycaster.intersectObjects(bisaDiklik, true);
    if (!potong.length) { tutupPopover(); return; }
    let obj = potong[0].object;
    while (obj && !obj.userData.popover) obj = obj.parent;
    if (obj) {
        // Balasan seketika: yang diketuk melompat sekali. Bangunan adalah
        // grup besar, warga adalah figur kecil; tinggi lompatannya mengikuti
        // ukuran objeknya.
        // Bang Uju berupa sprite yang posisinya ditulis ulang tiap bingkai;
        // lompat() tetap aman karena putaran utama menambahkan
        // offsetLompat() sesudahnya.
        lompat(obj);
        bukaPopover(obj.userData.popover, obj === spriteUju ? spriteUju : obj, e.clientX, e.clientY);
    }
});

// Papan peringkat gang dibangun server (includes/panel-bersayap.php), jadi
// tidak ada lagi yang perlu digambar dari sini. Fungsi ini disisakan supaya
// kait window.__desa tetap sah, dan hanya menyegarkan bila wadahnya ada.
function gambarPapanGang(papan) {
    const wadah = document.getElementById('isiPapanGang');
    if (!wadah) return;
    const urutan = [...papan].sort((a, b) => b.jumlah - a.jumlah);
    const maks = Math.max(1, ...urutan.map(p => p.jumlah));
    wadah.innerHTML = urutan.map((p, i) => `
        <div class="papan-baris">
            <span class="papan-peringkat">${i + 1}</span>
            <span class="papan-nama">${p.gang}</span>
            <span class="papan-jumlah">${p.jumlah} warga</span>
            <span class="papan-palangan"><span class="papan-isi" style="width: ${Math.round(p.jumlah / maks * 100)}%; background: ${cssGang(p.gang)}"></span></span>
        </div>`).join('');
}
document.getElementById('introTutup').addEventListener('click', () => {
    document.getElementById('kartuIntro').style.display = 'none';
});
function zoomFaktor(f) {
    if (modeJalan) return;
    const arah = camera.position.clone().sub(controls.target).multiplyScalar(f);
    const panjang = THREE.MathUtils.clamp(arah.length(), controls.minDistance, controls.maxDistance);
    arah.setLength(panjang);
    camera.position.copy(controls.target).add(arah);
}
document.getElementById('tombolZoomIn').addEventListener('click', () => zoomFaktor(0.78));
document.getElementById('tombolZoomOut').addEventListener('click', () => zoomFaktor(1.28));
document.getElementById('tombolReset').addEventListener('click', () => {
    if (modeJalan) aturModeJalan(false);
    camera.position.copy(KAMERA_AWAL);
    controls.target.copy(TARGET_AWAL);
    controls.update();
    tutupPopover();
});
document.getElementById('tombolLayarPenuh').addEventListener('click', () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen().catch(() => {});
});

// ---------------------------------------------------------------------------
// Sorot bangunan dari daftar di panel.
//
// Mengetuk nama bangunan di tab Bangunan harus terasa seperti "bawa saya ke
// sana": kamera terbang ke bangunan itu, sebuah cincin menyala di bawahnya,
// dan kartu detailnya terbuka. Tujuannya menjawab pertanyaan "yang mana?"
// tanpa pengunjung harus mencari sendiri di antara 12 kavling.
// ---------------------------------------------------------------------------
let cincinSorot = null;
let animasiSorot = null;

function buatCincinSorot() {
    if (cincinSorot) return cincinSorot;
    // Cincin tipis di alas, bukan piringan besar: tujuannya menunjuk bangunan,
    // bukan menutupinya.
    const geo = new THREE.RingGeometry(100, 112, 48);
    const mat = new THREE.MeshBasicMaterial({
        color: 0xf59e0b, transparent: true, opacity: 0.9, side: THREE.DoubleSide, depthWrite: false,
    });
    cincinSorot = new THREE.Mesh(geo, mat);
    cincinSorot.rotation.x = -Math.PI / 2;
    cincinSorot.visible = false;
    dunia.add(cincinSorot);
    return cincinSorot;
}

function bangunDariKavling(kavling) {
    return klikBangunan.find(m => m.userData.kavling === kavling) || null;
}

// Kamera terbang ke bangunan yang dipilih dari daftar.
let sorotan = null;
function perbaruiSorotan(sekarang) {
    if (!sorotan) return;
    const t = Math.min(1, (sekarang - sorotan.mulai) / sorotan.durasi);
    // Kurva halus: cepat di awal, lembut saat berhenti.
    const e = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    camera.position.lerpVectors(sorotan.posAwal, sorotan.posTujuan, e);
    controls.target.lerpVectors(sorotan.targetAwal, sorotan.sasaran, e);
    controls.update();
    if (t >= 1) sorotan = null;
}

function sorotKavling(kavling) {
    if (!Number.isInteger(kavling) || kavling < 0 || kavling >= POSISI_KAVLING.length) return false;
    const model = bangunDariKavling(kavling);
    if (!model) return false;

    if (modeJalan) aturModeJalan(false);

    const [x, y] = POSISI_KAVLING[kavling];
    const pusat = KE3(x, y);

    // Cincin menyala di alas bangunan.
    const cincin = buatCincinSorot();
    cincin.position.set(pusat.x, 0.6, pusat.z);
    cincin.material.opacity = 0.8;
    cincin.visible = true;

    // Terbang ke sana: sasaran digeser sedikit ke atas supaya atap ikut terlihat,
    // dan kamera mendekat dari arah yang sama seperti pandangan awal.
    const sasaran = new THREE.Vector3(pusat.x, 60, pusat.z);
    const arah = camera.position.clone().sub(controls.target).normalize();
    // Cukup jauh supaya seluruh bangunan muat di layar, termasuk atapnya.
    const jarak = 640;
    const posTujuan = sasaran.clone().add(arah.multiplyScalar(jarak));
    posTujuan.y = Math.max(posTujuan.y, sasaran.y + 330);

    // Bangunan yang diminta dari daftar juga melompat supaya terlihat mana
    // yang sedang disorot, bukan cuma cincinnya.
    lompat(model);

    // Animasi kamera dijalankan dari putaran utama (lihat perbaruiSorotan),
    // bukan requestAnimationFrame sendiri. Di perangkat tanpa GPU yang hanya
    // ~2-3 fps, animasi terpisah seperti ini cuma jalan 1-2 kali lalu
    // berhenti di tengah jalan, sehingga kamera terlihat tidak bergerak.
    sorotan = {
        mulai: performance.now(),
        durasi: 650,
        posAwal: camera.position.clone(),
        targetAwal: controls.target.clone(),
        posTujuan: posTujuan.clone(),
        sasaran: sasaran.clone(),
    };

    // Kartu detail bangunannya, ditempatkan di tengah atas layar.
    const data = model.userData.popover;
    if (data) bukaPopover(data, model, window.innerWidth / 2 - 150, window.innerHeight * 0.55);

    // Tandai tombol yang aktif di daftar.
    document.querySelectorAll('.daftar-bangunan-tombol').forEach((b) => {
        b.classList.toggle('aktif', Number(b.getAttribute('data-kavling')) === kavling);
    });
    if (window.__desa) window.__desa.kavlingDisorot = kavling;
    return true;
}

document.addEventListener('click', (e) => {
    const tombol = e.target.closest ? e.target.closest('.daftar-bangunan-tombol') : null;
    if (!tombol) return;
    const kavling = tombol.getAttribute('data-kavling');
    if (kavling === '') return; // bangunan di luar tabel kavling
    sorotKavling(Number(kavling));
});
if (window.__desa) window.__desa.sorotKavling = sorotKavling;
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// ---------------------------------------------------------------------------
// Suasana desa di bilah atas: ramai atau sepi.
//
// Dihitung dari data desa sendiri, bukan angka karangan: jumlah warga yang
// benar-benar berdiri di peta, ditambah jumlah bangunan yang sudah berdiri.
// Ambangnya sengaja dibuat rendah karena desa ini masih muda; begitu warganya
// bertambah, labelnya naik sendiri tanpa perlu diubah.
// ---------------------------------------------------------------------------
function perbaruiSuasana() {
    const chip = document.getElementById('chipSuasana');
    if (!chip) return;
    const warga = Math.min(WARGA_SEED.length, 80);
    const bangunan = BANGUNAN_SEED.length;
    let teks, kelas;
    if (warga >= 25) { teks = 'Ramai · ' + warga + ' warga di desa'; kelas = 'ramai'; }
    else if (warga >= 5) { teks = 'Mulai hidup · ' + warga + ' warga'; kelas = 'ramai'; }
    else if (warga >= 1) { teks = 'Sepi · ' + warga + ' warga baru'; kelas = 'sepi'; }
    else { teks = 'Desa baru · belum ada warga'; kelas = 'sepi'; }
    if (bangunan > 0) teks += ' · ' + bangunan + ' bangunan';
    chip.textContent = teks;
    chip.classList.remove('ramai', 'sepi');
    chip.classList.add(kelas);
    chip.setAttribute('data-suasana', kelas);
}
perbaruiSuasana();

// Kait untuk pengujian otomatis dan tangkapan layar.
window.__desa = {
    renderer, scene, camera, controls, aturModeJalan, kamu, agenWarga, agenUju,
    lompat, bounce,
    sumberData: DATA_DESA ? 'langsung' : 'cadangan',
    posisiKavling: POSISI_KAVLING,
    zonaGang: ZONA_GANG,
    fokus: FOKUS,
    bangunBangunan, bangunWarga, gambarPapanGang,
};

// Catatan data di kartu intro.
{
    const catatan = document.getElementById('catatanData');
    if (DATA_DESA) {
        catatan.textContent = 'Data langsung dari balai desa: ' + WARGA_SEED.length + ' warga terdaftar, ' + BANGUNAN_SEED.length + ' bangunan berdiri.';
        catatan.classList.add('langsung');
    } else {
        catatan.textContent = 'Data desa belum bisa dimuat. Peta ini memakai posisi cadangan, jadi mungkin tidak sama dengan Peta Desa 2D.';
    }
}

// ===========================================================================
// LINGKUNGAN: kawanan burung, bintang, dan siklus siang-malam langsung.
// Fase dihitung dari jam lokal pengunjung; tambahkan ?jam=HH (0-24, boleh
// pecahan) di alamat untuk memaksa jam tertentu saat meninjau malam.
// Semua warna dan cahaya diinterpolasi halus dari jangkar waktu di bawah,
// diperbarui tiap bingkai, tanpa lompatan mendadak.
// ===========================================================================

// Jangkar penampilan per jam: warna langit atas, cakrawala, kabut, cahaya
// hemisfer (langit/tanah/intensitas), matahari (warna/intensitas), sudut
// elevasi matahari dalam derajat, dan faktor malam (0 siang, 1 malam).
const JANGKAR_WAKTU = [
    { jam: 0.0,   atas: '#060d24', hor: '#1b2f52', kb: '#16263f', hL: '#33456b', hT: '#1d2b22', hI: 0.30, mW: '#93b8e8', mI: 0.22, el: -32, mlm: 1 },
    { jam: 4.75,  atas: '#060d24', hor: '#1b2f52', kb: '#16263f', hL: '#33456b', hT: '#1d2b22', hI: 0.30, mW: '#93b8e8', mI: 0.22, el: -18, mlm: 1 },
    { jam: 6.0,   atas: '#3d6fb4', hor: '#f2b879', kb: '#eec39a', hL: '#bcd6f5', hT: '#5f7a4a', hI: 0.55, mW: '#ffb066', mI: 0.85, el: 4,   mlm: 0.35 },
    { jam: 7.5,   atas: '#5db4ef', hor: '#cdeffd', kb: '#d6f0f7', hL: '#eaf6ff', hT: '#86c06c', hI: 0.92, mW: '#ffedb8', mI: 1.55, el: 26,  mlm: 0 },
    { jam: 12.0,  atas: '#54b6f5', hor: '#d8f6fd', kb: '#d6f0f7', hL: '#eaf6ff', hT: '#86c06c', hI: 0.95, mW: '#fff1d6', mI: 1.70, el: 62,  mlm: 0 },
    { jam: 16.5,  atas: '#5db4ef', hor: '#cdeffd', kb: '#d6f0f7', hL: '#eaf6ff', hT: '#86c06c', hI: 0.92, mW: '#ffe9b0', mI: 1.55, el: 30,  mlm: 0 },
    { jam: 18.0,  atas: '#46629e', hor: '#ffab5e', kb: '#f0b98a', hL: '#c3cfe8', hT: '#5d7046', hI: 0.58, mW: '#ff9448', mI: 0.80, el: 5,   mlm: 0.40 },
    { jam: 19.25, atas: '#0a1734', hor: '#2c3a5e', kb: '#1d2c47', hL: '#3d5076', hT: '#22301f', hI: 0.33, mW: '#93b8e8', mI: 0.25, el: -14, mlm: 1 },
    { jam: 24.0,  atas: '#060d24', hor: '#1b2f52', kb: '#16263f', hL: '#33456b', hT: '#1d2b22', hI: 0.30, mW: '#93b8e8', mI: 0.22, el: -32, mlm: 1 },
];
const PW = { atas: new THREE.Color(), hor: new THREE.Color(), kb: new THREE.Color(), hL: new THREE.Color(), hT: new THREE.Color(), mW: new THREE.Color(), hI: 0, mI: 0, el: 0, mlm: 0 };
const _tmpWarna = new THREE.Color();
function ambilWarna(tujuan, a, b, t) { tujuan.set(a); _tmpWarna.set(b); tujuan.lerp(_tmpWarna, t); }
function propertiWaktu(jam) {
    let a = JANGKAR_WAKTU[0], b = JANGKAR_WAKTU[JANGKAR_WAKTU.length - 1];
    for (let i = 0; i < JANGKAR_WAKTU.length - 1; i++) {
        if (jam >= JANGKAR_WAKTU[i].jam && jam <= JANGKAR_WAKTU[i + 1].jam) { a = JANGKAR_WAKTU[i]; b = JANGKAR_WAKTU[i + 1]; break; }
    }
    let t = THREE.MathUtils.clamp((jam - a.jam) / Math.max(b.jam - a.jam, 0.0001), 0, 1);
    t = t * t * (3 - 2 * t);   // smoothstep: transisi tanpa patahan
    ambilWarna(PW.atas, a.atas, b.atas, t);
    ambilWarna(PW.hor, a.hor, b.hor, t);
    ambilWarna(PW.kb, a.kb, b.kb, t);
    ambilWarna(PW.hL, a.hL, b.hL, t);
    ambilWarna(PW.hT, a.hT, b.hT, t);
    ambilWarna(PW.mW, a.mW, b.mW, t);
    PW.hI = a.hI + (b.hI - a.hI) * t;
    PW.mI = a.mI + (b.mI - a.mI) * t;
    PW.el = a.el + (b.el - a.el) * t;
    PW.mlm = a.mlm + (b.mlm - a.mlm) * t;
    return PW;
}
function namaFase(jam) {
    if (jam >= 5 && jam < 7) return { teks: 'Subuh', ikon: '\u{1F305}' };
    if (jam >= 7 && jam < 11) return { teks: 'Pagi', ikon: '\u{1F324}\u{FE0F}' };
    if (jam >= 11 && jam < 15) return { teks: 'Siang', ikon: '\u{2600}\u{FE0F}' };
    if (jam >= 15 && jam < 18) return { teks: 'Sore', ikon: '\u{1F324}\u{FE0F}' };
    if (jam >= 18 && jam < 19.5) return { teks: 'Senja', ikon: '\u{1F307}' };
    return { teks: 'Malam', ikon: '\u{1F319}' };
}
const paramJam = new URLSearchParams(location.search).get('jam');
const jamPaksaMentah = paramJam === null ? NaN : parseFloat(paramJam);
const jamPaksa = Number.isFinite(jamPaksaMentah) ? ((jamPaksaMentah % 24) + 24) % 24 : null;
function jamSekarang() {
    if (jamPaksa !== null) return jamPaksa;
    const d = new Date();
    return d.getHours() + d.getMinutes() / 60 + d.getSeconds() / 3600;
}

// Bulan: cahaya arah kedua yang mengambil alih saat faktor malam naik.
const bulan = new THREE.DirectionalLight(0xa9c6f5, 0);
bulan.position.set(650, 1050, 380);
scene.add(bulan);

// Bintang: titik-titik di kubah dalam, hanya tampak saat malam.
const bintang = (() => {
    const N = 420, posisi = new Float32Array(N * 3), r = mulberry32(777);
    for (let i = 0; i < N; i++) {
        const theta = r() * Math.PI * 2, y = 0.16 + r() * 0.82;
        const mendatar = Math.sqrt(Math.max(0, 1 - y * y)), R = 3600;
        posisi[i * 3] = Math.cos(theta) * mendatar * R;
        posisi[i * 3 + 1] = y * R;
        posisi[i * 3 + 2] = Math.sin(theta) * mendatar * R;
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(posisi, 3));
    const bahan = new THREE.PointsMaterial({ color: 0xdbeafe, size: 4, sizeAttenuation: false, transparent: true, opacity: 0, fog: false, depthWrite: false });
    const titik = new THREE.Points(geo, bahan);
    titik.visible = false;
    scene.add(titik);
    return { titik, bahan };
})();

// Cahaya lampu jalan: sprite pijar lembut di kepala tiap lampu, menyala
// hanya saat malam (tanpa PointLight tambahan, jadi tetap murah).
const grupGlowLampu = new THREE.Group();
const bahanGlowLampu = (() => {
    const c = document.createElement('canvas'); c.width = c.height = 64;
    const g = c.getContext('2d');
    if (g && g.createRadialGradient) {
        const gr = g.createRadialGradient(32, 32, 2, 32, 32, 31);
        gr.addColorStop(0, 'rgba(255,236,170,0.9)');
        gr.addColorStop(0.4, 'rgba(254,240,138,0.35)');
        gr.addColorStop(1, 'rgba(254,240,138,0)');
        g.fillStyle = gr; g.fillRect(0, 0, 64, 64);
    }
    const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace;
    return new THREE.SpriteMaterial({ map: tex, transparent: true, opacity: 0, depthWrite: false });
})();
for (const [lx, ly] of [[120, 648], [330, 556], [560, 622], [770, 512], [1005, 420]]) {
    const p = KE3(lx * SKALA, ly * SKALA);
    const pijar = new THREE.Sprite(bahanGlowLampu);
    pijar.scale.set(52, 52, 1);
    pijar.position.set(p.x, 70, p.z);
    grupGlowLampu.add(pijar);
}
grupGlowLampu.visible = false;
dunia.add(grupGlowLampu);

// ---------------------------------------------------------------------------
// Kawanan burung: padanan 3D burung SVG di peta 2D. Badan kerucut kecil dan
// dua sayap segitiga yang mengepak dari pivot bahu; satu geometri dan satu
// material dipakai bersama, yang bergerak hanya transformasi tiap burung.
// ---------------------------------------------------------------------------
const grupBurung = new THREE.Group();
dunia.add(grupBurung);
const bahanBurung = new THREE.MeshBasicMaterial({ color: 0x334155, side: THREE.DoubleSide });
const geoBadanBurung = new THREE.ConeGeometry(4.5, 20, 6);
geoBadanBurung.rotateX(Math.PI / 2);
const geoSayapBurung = (() => {
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.Float32BufferAttribute([0, 0, 7, 0, 0, -6, 26, 0, -2], 3));
    geo.computeVertexNormals();
    return geo;
})();
function buatBurung() {
    const g = new THREE.Group();
    g.add(new THREE.Mesh(geoBadanBurung, bahanBurung));
    const pivotKiri = new THREE.Group(); pivotKiri.position.set(-3, 2, 0); pivotKiri.scale.x = -1;
    pivotKiri.add(new THREE.Mesh(geoSayapBurung, bahanBurung));
    const pivotKanan = new THREE.Group(); pivotKanan.position.set(3, 2, 0);
    pivotKanan.add(new THREE.Mesh(geoSayapBurung, bahanBurung));
    g.add(pivotKiri, pivotKanan);
    g.userData.sayap = [pivotKiri, pivotKanan];
    return g;
}
const kawanan = [];
{
    const pusat = KE3(FOKUS.x, FOKUS.y);
    for (let i = 0; i < 7; i++) {
        const figur = buatBurung();
        grupBurung.add(figur);
        kawanan.push({
            figur,
            sudut: (i / 7) * Math.PI * 2,
            radius: 330 + (i % 4) * 85,
            tinggi: 295 + (i % 3) * 50,
            laju: 0.055 + (i % 3) * 0.012,
            faseBob: i * 1.37, faseKepak: i * 0.9,
            cx: pusat.x + ((i % 3) - 1) * 130,
            cz: pusat.z + ((i % 2) * 2 - 1) * 90,
        });
    }
}
const _tujuanBurung = new THREE.Vector3();
function perbaruiBurung(dt, t) {
    for (const b of kawanan) {
        b.sudut += b.laju * dt;
        const x = b.cx + Math.cos(b.sudut) * b.radius;
        const z = b.cz + Math.sin(b.sudut) * b.radius;
        const y = b.tinggi + Math.sin(t * 0.9 + b.faseBob) * 13;
        b.figur.position.set(x, y, z);
        _tujuanBurung.set(b.cx + Math.cos(b.sudut + 0.05) * b.radius, y, b.cz + Math.sin(b.sudut + 0.05) * b.radius);
        b.figur.lookAt(_tujuanBurung);
        const kepak = Math.sin(t * 9 + b.faseKepak) * 0.75;
        b.figur.userData.sayap[0].rotation.z = kepak;
        b.figur.userData.sayap[1].rotation.z = kepak;
    }
}
perbaruiBurung(0, 0);   // tempatkan kawanan walau pengunjung memilih gerak dikurangi

// Gambar ulang gradasi kubah hanya bila warna kuantisasinya berubah.
function gambarKubah(P) {
    const kunci = P.atas.getHexString() + '|' + P.hor.getHexString() + '|' + P.kb.getHexString();
    if (kunci === kubah.kunci) return;
    kubah.kunci = kunci;
    const gr = kubah.ctx.createLinearGradient(0, 0, 0, 256);
    gr.addColorStop(0, '#' + P.atas.getHexString());
    gr.addColorStop(0.58, '#' + P.hor.getHexString());
    gr.addColorStop(1, '#' + P.kb.getHexString());
    kubah.ctx.fillStyle = gr;
    kubah.ctx.fillRect(0, 0, 4, 256);
    kubah.tex.needsUpdate = true;
}

const chipWaktu = document.getElementById('chipWaktu');
const warnaTanahLuar = new THREE.Color(0x74b45e);
const warnaAwanPutih = new THREE.Color(0xffffff);
let bahanAwanRef = null, faseChipTerakhir = '';
function perbaruiLingkungan() {
    const j = jamSekarang();
    const P = propertiWaktu(j);
    gambarKubah(P);
    scene.background.copy(P.kb);
    scene.fog.color.copy(P.kb);
    scene.fog.near = 1500 + P.mlm * 250;
    scene.fog.far = 3400 - P.mlm * 500;
    cahayaHemi.color.copy(P.hL);
    cahayaHemi.groundColor.copy(P.hT);
    cahayaHemi.intensity = P.hI;
    matahari.color.copy(P.mW);
    matahari.intensity = P.mI;
    const az = (j - 6) / 12 * Math.PI;
    matahari.position.set(-Math.cos(az) * 950, Math.max(140, Math.sin(THREE.MathUtils.degToRad(P.el)) * 1300), -520);
    bulan.intensity = 0.38 * P.mlm;
    bintang.titik.visible = P.mlm > 0.03;
    bintang.bahan.opacity = P.mlm * 0.95;
    if (matTanahLuar) matTanahLuar.color.copy(warnaTanahLuar).lerp(P.kb, 0.55);
    if (bahanKepalaLampu) bahanKepalaLampu.emissiveIntensity = 0.7 + 1.6 * P.mlm;
    for (const jd of matJendela) jd.bahan.emissiveIntensity = jd.dasar + (2.1 - jd.dasar) * P.mlm;
    bahanGlowLampu.opacity = 0.85 * P.mlm;
    grupGlowLampu.visible = P.mlm > 0.02;
    grupBurung.visible = P.mlm < 0.75;
    if (!bahanAwanRef && window.__awan && window.__awan.length) bahanAwanRef = window.__awan[0].children[0].material;
    if (bahanAwanRef) {
        bahanAwanRef.color.copy(P.atas).lerp(warnaAwanPutih, 0.78);
        bahanAwanRef.opacity = 0.92 - P.mlm * 0.5;
    }
    const f = namaFase(j);
    if (f.teks !== faseChipTerakhir) {
        faseChipTerakhir = f.teks;
        chipWaktu.textContent = f.ikon + ' ' + f.teks;
        chipWaktu.classList.toggle('malam', f.teks === 'Malam' || f.teks === 'Senja');
    }
}
const lingkungan = { jamSekarang, namaFase, propertiWaktu, perbaruiLingkungan, perbaruiBurung, kawanan, bintang, grupBurung, grupGlowLampu, bulan, kubah };
window.__desa.lingkungan = lingkungan;

// ---------------------------------------------------------------------------
// Putaran utama
// ---------------------------------------------------------------------------
const jam = new THREE.Clock();
const labelUju = dunia.getObjectByName('labelUju');
const statsEl = document.getElementById('stats');
const tampilStats = new URLSearchParams(location.search).has('stats');
if (tampilStats) statsEl.style.display = 'block';
let bingkai = 0, akumulasiFps = 0, waktuStats = performance.now();
let muatSembunyi = false;

function putaran() {
    requestAnimationFrame(putaran);
    // Batas dt menjaga animasi ambien tetap stabil saat tab kembali dibuka.
    // Batas 0,05 s (20 fps) dulu juga membatasi KECEPATAN JALAN: di ponsel
    // yang hanya 15-20 fps, pemain merayap karena waktu yang dipakai selalu
    // dipotong. Jadi batasnya dilonggarkan ke 0,15 s; animasi ambien sendiri
    // memakai dtKecil supaya tidak melompat.
    const dt = Math.min(jam.getDelta(), 0.15);
    const dtKecil = Math.min(dt, 0.05);
    const t = jam.elapsedTime;

    // Lompatan dihitung lebih dulu supaya posisi objek yang ditulis ulang
    // tiap bingkai (warga, Bang Uju) sudah memuat offset terbaru.
    perbaruiBounce(performance.now());

    if (modeJalan) {
        perbaruiKamu(dt);
        // Chip pintu tetap dievaluasi walau pemain diam.
        perbaruiChipMasuk();
    } else if (sorotan) {
        // Terbang ke bangunan yang disorot, lalu kendali diserahkan ke
        // pengunjung seperti biasa.
        perbaruiSorotan(performance.now());
    } else {
        controls.update();
        controls.target.x = THREE.MathUtils.clamp(controls.target.x, -1000, 1000);
        controls.target.z = THREE.MathUtils.clamp(controls.target.z, -666, 666);
        controls.target.y = 0;
    }

    // Kehidupan ambien: warga dan patroli Uju berhenti selama mode jalan,
    // tab tersembunyi, atau pengunjung memilih gerak dikurangi.
    const ambienJalan = !kurangGerak && !modeJalan && !document.hidden;
    if (ambienJalan) {
        let bergerak = agenWarga.filter(a => a.target && a.tunggu <= 0 && !a.jedaPopover).length;
        for (const agen of agenWarga) {
            if (!agen.target && agen.tunggu <= 0 && bergerak >= 12) agen.tunggu = 0.6;
            gerakkanAgen(agen, dtKecil);
            terapkanAgen(agen, t);
            if (agen.target) bergerak++;
        }
        gerakkanAgen(agenUju, dtKecil, true);
        const posU = KE3(agenUju.x, agenUju.y);
        // Bang Uju melayang naik-turun sendiri; lompatan klik ditambahkan
        // di atasnya supaya keduanya tidak saling hapus.
        spriteUju.position.set(posU.x, 84 + Math.sin(t * 2.2) * 2.5 + offsetLompat(spriteUju), posU.z);
        if (labelUju) labelUju.position.set(posU.x, 196 + Math.sin(t * 2.2) * 2.5, posU.z);
        // Gelombang bendera dan kerlip air kolam.
        if (bendera && geoBenderaAsli) {
            const posAttr = bendera.geometry.attributes.position;
            for (let i = 0; i < posAttr.count; i++) {
                const bx = geoBenderaAsli[i * 3];
                posAttr.setZ(i, Math.sin(bx * 0.14 + t * 6) * 4.5 * (bx / 82 + 0.15));
            }
            posAttr.needsUpdate = true;
            bendera.geometry.computeVertexNormals();
        }
        if (bahanAir) bahanAir.opacity = 0.87 + Math.sin(t * 1.4) * 0.05;
        for (const awan of window.__awan) {
            awan.position.x += dtKecil * 7;
            if (awan.position.x > 1350) awan.position.x = -1350;
        }
        perbaruiBurung(dtKecil, t);
    }

    perbaruiLingkungan();
    perbaruiLabelWarga();
    // Selama ruangan Balai Desa terbuka, dunia luar tertutup sepenuhnya dan
    // kanvas ruangan yang menyala. Tetap merender dua kanvas WebGL sekaligus
    // membuat keduanya berebut GPU: di perangkat tanpa kartu grafis khusus,
    // ruangan turun ke bawah 1 bingkai/detik dan tombol jalan terasa mati.
    // Peta luar sudah "dibekukan" oleh lapisan ruangan, jadi aman dilewati.
    if (!interiorTerbuka3d) {
        renderer.render(scene, camera);
    }

    if (!muatSembunyi) {
        muatSembunyi = true;
        document.getElementById('layarMuat').classList.add('hilang');
    }
    if (tampilStats) {
        bingkai++; akumulasiFps += dt;
        const kini = performance.now();
        if (kini - waktuStats > 600) {
            const info = renderer.info.render;
            statsEl.textContent = `draw calls : ${info.calls}\ntriangles  : ${info.triangles.toLocaleString('id-ID')}\nfps        : ${Math.round(bingkai / akumulasiFps)}`;
            bingkai = 0; akumulasiFps = 0; waktuStats = kini;
        }
    }
}
putaran();
</script>
</body>
</html>
