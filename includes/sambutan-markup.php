<?php
// Markup pop-up sambutan beranda (ADR-0005).
//
// Muncul sekali setelah kamera selesai terbang. Isinya dua versi yang
// dipilih skrip menurut kartu warga di peramban:
//   - tamu: sapaan + tiga langkah cara main + Bikin KTP Warga / Jelajahi Desa
//   - warga (dt_nomor + dt_kunci): sapa namanya + arahkan ke klaim poin harian
//
// Versi tamu ditulis di HTML dan tampil pertama. Kalau kartu warga ada dan
// namanya berhasil diambil dari sapa.php, isinya diganti sebelum muncul, jadi
// pengunjung tidak pernah melihat versi tamu berkedip lalu berubah.
//
// Variabel: $sambutanAwalan ('' dari akar, '../' dari peta3d/),
//           $sambutanWarga, $sambutanBangunan, $sambutanGang (angka ringkas
//           untuk baris statistik di kaki kartu).
$sambutanAwalan = $sambutanAwalan ?? '';
$sambutanWarga = $sambutanWarga ?? 0;
$sambutanBangunan = $sambutanBangunan ?? 0;
$sambutanGang = $sambutanGang ?? 0;
?>
<div class="sambutan" id="sambutan" hidden>
    <div class="sambutan-kartu" role="dialog" aria-modal="true" aria-labelledby="sambutanJudul" aria-describedby="sambutanIsi">
        <button type="button" class="sambutan-tutup" id="sambutanTutup" aria-label="Tutup sambutan">&times;</button>
        <img class="sambutan-logo" src="<?= e($sambutanAwalan) ?>assets/bang-uju.png" alt="">
        <p class="sambutan-kicker">SELAMAT DATANG</p>
        <h2 id="sambutanJudul">Halo, selamat datang di Desa Target!</h2>
        <p class="sambutan-isi" id="sambutanIsi">
            Desa fiktif para UMKM hebat, tempat para UMKM tinggal. Bangunan di sini lahir dari
            komentar warga di Instagram <a href="<?= e(APP_INSTAGRAM) ?>" target="_blank" rel="noopener">@desatarget</a>,
            dan yang paling banyak disukai didirikan keesokan harinya.
        </p>

        <p class="sambutan-isi sambutan-warga" id="sambutanWarga" hidden>
            <span id="sambutanNama">Warga</span>, poin harianmu menunggu. Klaim sekarang, lalu jalan-jalan keliling desa.
        </p>

        <ol class="sambutan-langkah" id="sambutanLangkah">
            <li><span><strong>Bikin KTP Warga</strong>, gratis, langsung dapat nomor warga sendiri.</span></li>
            <li><span><strong>Buka ruko atau ambil lapak</strong> di Pasar Malam dan CFD.</span></li>
            <li><span><strong>Kumpulkan poin</strong> dari misi harian, lalu bangun usahamu di desa.</span></li>
        </ol>

        <div class="sambutan-aksi" id="sambutanAksiTamu">
            <a class="sambutan-tombol utama" href="<?= e($sambutanAwalan) ?>ktp">Bikin KTP Warga, Gratis</a>
            <button type="button" class="sambutan-tombol" id="sambutanJelajah">Jelajahi Desa Dulu</button>
        </div>

        <div class="sambutan-aksi" id="sambutanAksiWarga" hidden>
            <button type="button" class="sambutan-tombol utama" id="sambutanKlaim">Klaim poin harian</button>
            <button type="button" class="sambutan-tombol" id="sambutanMisi">Lihat misi hari ini</button>
        </div>

        <p class="sambutan-kabar" id="sambutanKabar" role="status" hidden></p>

        <p class="sambutan-stat" id="sambutanStat">
            <?= number_format((int) $sambutanWarga, 0, ',', '.') ?> warga
            &middot; <?= (int) $sambutanBangunan ?> bangunan
            &middot; <?= (int) $sambutanGang ?> gang
        </p>
        <p class="sambutan-kaki">
            <a href="<?= e($sambutanAwalan) ?>tentang">Kenalan dulu sama Desa Target</a>
            <span aria-hidden="true">&middot;</span>
            <a href="<?= e($sambutanAwalan) ?>peta2d">Versi 2D</a>
        </p>
    </div>
</div>
