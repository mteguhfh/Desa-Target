<?php
// Panel bersayap: satu panel yang isinya berganti mengikuti tab yang diketuk.
//
// Tujuannya meniru pola pembanding: berpindah isi TANPA meninggalkan dunia 3D.
// Panel menempel di sisi bawah, jadi bagian atas dunia tetap terlihat.
//
// Variabel yang dipakai:
//   $tabAwalan          awalan URL, sama seperti tab-bar.php
//   $panelBangunan      daftar bangunan (dari semua_bangunan_aktif()), boleh kosong
//   $panelPapanGang     peringkat gang (dari papan_gang())
//   $panelTotalWarga    jumlah warga terdaftar
//   $panelKavling       peta id bangunan -> indeks kavling, supaya ketukan
//                       pada daftar bisa menyorot bangunan yang tepat
//   $panelMisi          hasil daftar_misi(), tampil di tab Misi
//   $panelTotalBangunan jumlah bangunan aktif (diisi halaman peta)
//   $panelTerbaru       bangunan terbaru (dari bangunan_terbaru()), boleh null
//
// Semua isi berasal dari data yang sudah dibaca halaman. Tidak ada permintaan
// jaringan tambahan, jadi panel tidak bisa menampilkan angka yang berbeda
// dengan dunia 3D di belakangnya.
if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$tabAwalan = $tabAwalan ?? '';
$panelBangunan = $panelBangunan ?? [];
$panelPapanGang = $panelPapanGang ?? [];
$panelTotalWarga = $panelTotalWarga ?? 0;
$panelKavling = $panelKavling ?? []; // id bangunan -> indeks kavling
$panelMisi = $panelMisi ?? [];
$panelTotalBangunan = $panelTotalBangunan ?? count($panelBangunan);
$panelTerbaru = $panelTerbaru ?? null;

$jumlahTerbanyak = 0;
foreach ($panelPapanGang as $barisPapan) {
    $jumlahTerbanyak = max($jumlahTerbanyak, (int) $barisPapan['jumlah']);
}
?>
<section class="panel-bersayap" id="panelBersayap" aria-label="Panel desa" aria-hidden="true" hidden>
    <div class="panel-bersayap-kepala">
        <h2 class="panel-bersayap-judul" id="panelBersayapJudul">Desa</h2>
        <button type="button" class="panel-bersayap-tutup" id="panelBersayapTutup" aria-label="Tutup panel">&times;</button>
    </div>
    <div class="panel-bersayap-isi">

        <div class="panel-isi" data-isi="warga" hidden>
            <p class="panel-deskripsi">Cari warga berdasarkan nama atau nomor kartu. Contoh: "sari" atau "DT-0007".</p>
            <form class="cari-warga" id="cariWargaForm" method="get" action="<?= e($tabAwalan) ?>warga.php"
                  data-endpoint="<?= e($tabAwalan) ?>cari-warga.php">
                <label class="sr-saja" for="cariWargaKunci">Nama atau nomor warga</label>
                <input class="cari-warga-isian" id="cariWargaKunci" name="cari" type="search"
                       placeholder="Nama atau DT-0007" autocomplete="off"
                       maxlength="50" enterkeyhint="search">
                <button class="tombol-panel-utama cari-warga-tombol" type="submit">Cari</button>
            </form>
            <div class="cari-warga-hasil" id="cariWargaHasil" aria-live="polite"></div>
            <p class="panel-catatan">Semua warga juga bisa dilihat di <a href="<?= e($tabAwalan) ?>warga.php">daftar warga desa</a>.</p>
        </div>

        <div class="panel-isi" data-isi="misi" hidden>
            <?php $ringkasMisi = ringkasan_misi($panelMisi); ?>
            <p class="panel-deskripsi">
                <strong id="misiRingkasan"><?= (int) $ringkasMisi['selesai'] ?> dari <?= (int) $ringkasMisi['total'] ?></strong> misi hari ini sudah selesai.
                Status dan poin di bawah ini milikmu sendiri setelah kartu warga tersimpan di peramban ini.
            </p>
            <div class="misi-kemajuan" aria-label="Kemajuan misi hari ini">
                <div class="misi-kemajuan-kepala"><strong>Kemajuan hari ini</strong><span id="misiKemajuanLabel"><?= (int) $ringkasMisi['persen'] ?>%</span></div>
                <div class="misi-bar" id="misiBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $ringkasMisi['persen'] ?>"><span style="width: <?= (int) $ringkasMisi['persen'] ?>%"></span></div>
                <p class="misi-streak" id="misiStreak">Streak: belum mulai</p>
            </div>

            <div class="misi-dompet" id="misiDompet" hidden>
                <p class="misi-saldo"><span class="misi-saldo-angka" id="misiSaldo">0</span> poin</p>
                <p class="misi-saldo-ket" id="misiSaldoKet">Saldo poin milikmu. Poin nanti bisa dipakai untuk usaha di desa.</p>
                <button type="button" class="tombol-panel-utama misi-klaim" id="tombolKlaimHarian">Klaim poin harian</button>
                <p class="misi-kabar" id="misiKabar" role="status" hidden></p>
            </div>

            <div class="misi-tamu" id="misiTamu">
                <p class="panel-deskripsi">Bikin KTP Warga dulu supaya misi dan poinmu tercatat atas namamu sendiri.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga</a>
            </div>

            <ul class="daftar-misi" id="daftarMisi">
                <?php foreach ($panelMisi as $m): ?>
                    <li class="misi-baris<?= $m['selesai'] ? ' selesai' : '' ?>" data-kode="<?= e($m['kode']) ?>">
                        <span class="misi-tanda" aria-hidden="true"><?= $m['selesai'] ? '&#10003;' : '' ?></span>
                        <span class="misi-teks">
                            <span class="misi-judul"><?= e($m['judul']) ?></span>
                            <span class="misi-keterangan"><?= e($m['keterangan']) ?></span>
                            <button type="button" class="misi-tandai" data-kode="<?= e($m['kode']) ?>"
                                    <?= $m['selesai'] ? 'hidden' : '' ?>>Tandai selesai, +<?= (int) $m['poin'] ?> poin</button>
                            <button type="button" class="misi-aksi" data-aksi="<?= e($m['kode']) ?>">Buka tujuan</button>
                        </span>
                        <span class="misi-status"><?= $m['selesai'] ? 'Selesai' : 'Belum' ?></span>
                        <span class="misi-poin">+<?= (int) $m['poin'] ?> poin</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="tombol-panel-utama" href="https://www.instagram.com/desatarget" target="_blank" rel="noopener">Buka Instagram</a>
        </div>

        <div class="panel-isi" data-isi="papan-gang" hidden>
            <p class="panel-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
            <div class="papan-gang">
                <?php foreach ($panelPapanGang as $i => $baris): ?>
                    <?php $persen = $jumlahTerbanyak > 0 ? (int) round($baris['jumlah'] / $jumlahTerbanyak * 100) : 0; ?>
                    <div class="papan-baris">
                        <span class="papan-peringkat"><?= $i + 1 ?></span>
                        <span class="papan-nama"><?= e($baris['gang']) ?></span>
                        <span class="papan-palangan"><span class="papan-isi" style="width: <?= $persen ?>%"></span></span>
                        <span class="papan-jumlah"><?= (int) $baris['jumlah'] ?> warga</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="panel-gabung"><a href="<?= e($tabAwalan) ?>ktp.php">Gabung gang-mu</a></p>
        </div>

        <div class="panel-isi" data-isi="bangunan" hidden>
            <?php if ($panelBangunan === []): ?>
                <p class="panel-deskripsi">Belum ada bangunan berdiri. Usulkan usaha impianmu lewat komentar Instagram.</p>
            <?php else: ?>
                <p class="panel-deskripsi">
                    <strong><?= (int) $panelTotalWarga ?></strong> warga terdaftar,
                    <strong><?= (int) $panelTotalBangunan ?></strong> bangunan berdiri,
                    <strong><?= count(GANG_WARGA) ?></strong> gang profesi.
                    Urut dari yang paling baru.
                </p>
                <?php // "Pembangunan Hari Ini" dari beranda lama (ADR-0005):
                      // bangunan terbaru sudah ada di puncak daftar, jadi yang
                      // ditambahkan cukup kalimat pengantarnya. ?>
                <?php if ($panelTerbaru !== null): ?>
                    <p class="panel-catatan panel-catatan-atas">
                        Pembangunan hari ini: <strong><?= e($panelTerbaru['nama']) ?></strong><?php if (!empty($panelTerbaru['diminta_oleh'])): ?>, diminta oleh <?= e($panelTerbaru['diminta_oleh']) ?><?php endif; ?>.
                    </p>
                <?php endif; ?>
                <p class="panel-catatan panel-catatan-atas">Di peta, ketuk nama bangunan untuk menyorotnya di dunia.</p>
                <ul class="daftar-bangunan">
                    <?php foreach (array_reverse($panelBangunan) as $b): ?>
                        <?php $kavling = $panelKavling[$b['id']] ?? null; ?>
                        <li class="daftar-bangunan-baris">
                            <button type="button" class="daftar-bangunan-tombol"
                                    data-bangunan="<?= (int) $b['id'] ?>"
                                    data-kavling="<?= $kavling === null ? '' : (int) $kavling ?>">
                                <span class="daftar-bangunan-nama"><?= e($b['nama']) ?></span>
                                <?php if (!empty($b['gang'])): ?>
                                    <span class="daftar-bangunan-gang"><?= e($b['gang']) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php // Ruko warga (Fitur 3): jalan masuk ke halaman klaim ruko. ?>
            <p class="panel-catatan"><a href="<?= e($tabAwalan) ?>ruko.php">Buka ruko usahamu di desa</a></p>
        </div>

        <?php // Pasar Malam dan CFD (Fitur 4). Lapak gratis, hanya saat acara
              // berlangsung. Dibuka dari chip suasana di bilah atas. ?>
        <div class="panel-isi" data-isi="pasar" hidden>
            <p class="panel-deskripsi">Pasar Malam tiap Sabtu sore dan CFD tiap Minggu pagi. Lapak gratis, dan yang dikunjungi pembeli adalah tempat jualanmu sendiri.</p>

            <p class="pasar-status" id="pasarStatus" role="status">Memuat jadwal…</p>

            <div class="pasar-pilih" id="pasarPilih" role="group" aria-label="Pilih acara"></div>

            <ul class="pasar-lapak" id="pasarLapak"></ul>

            <?php // Pemilih gambar ringkas untuk warga tanpa ruko. Hanya
                  // dipakai saat formulir lapak terbuka. ?>
            <div class="pasar-gambar" id="pasarGambar" hidden>
                <p class="pasar-form-judul">Pilih gambar lapak</p>
                <div class="pasar-gambar-pilih" role="radiogroup" aria-label="Pilih gambar lapak">
                    <?php foreach (daftar_gambar_usaha() as $gambarPasar): ?>
                        <label class="pasar-gambar-kartu">
                            <input type="radio" name="gambar_lapak" value="<?= e($gambarPasar) ?>">
                            <img src="<?= e($gambarPasar) ?>" alt="" loading="lazy" width="640" height="480">
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pasar-klaim" id="pasarKlaim" hidden>
                <button type="button" class="tombol-panel-utama" id="tombolKlaimLapak">Ambil lapak gratis</button>
            </div>

            <form class="pasar-form" id="pasarForm" hidden>
                <p class="pasar-form-judul">Kamu belum punya ruko, isi nama usaha dan tautan dulu</p>
                <label class="sr-saja" for="pasarNamaUsaha">Nama usaha</label>
                <input id="pasarNamaUsaha" name="nama_usaha" type="text" maxlength="60" placeholder="Nama usaha" autocomplete="off">
                <label class="sr-saja" for="pasarTautan">Tautan jualan</label>
                <input id="pasarTautan" name="tautan" type="url" maxlength="300" placeholder="https://wa.me/62812..." autocomplete="off">
                <button type="button" class="tombol-panel-utama" id="tombolKirimLapak">Ambil lapak gratis</button>
            </form>

            <p class="pasar-kabar" id="pasarKabar" role="status" hidden></p>

            <div class="pasar-tamu" id="pasarTamu" hidden>
                <p class="panel-deskripsi">Bikin KTP Warga supaya bisa membuka lapak. Melihat jadwal dan lapak tetap gratis.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga</a>
            </div>

            <p class="panel-catatan"><a href="<?= e($tabAwalan) ?>pasar.php">Lihat halaman Pasar Desa</a> atau <a href="<?= e($tabAwalan) ?>ruko.php">buka ruko usahamu</a>.</p>
        </div>

        <?php // KTP Warga dibuat langsung di panel ini (ADR-0007), supaya
              // pengunjung tidak meninggalkan peta. Formulir kirim ke
              // ktp-json.php; halaman ktp.php tetap ada untuk kartu penuh. ?>
        <div class="panel-isi" data-isi="aku" hidden>
            <p class="panel-deskripsi">Kartu warga adalah identitasmu di desa. Dibuat sekali, gratis, nomornya milikmu sendiri.</p>

            <div class="aku-muat" id="akuMuat">
                <p class="panel-deskripsi">Memeriksa kartu warga di peramban ini…</p>
            </div>

            <div class="aku-tamu" id="akuTamu" hidden>
                <p class="panel-statistik"><strong><?= (int) $panelTotalWarga ?></strong> warga sudah tercatat di balai desa.</p>
                <?php require_once __DIR__ . '/avatar-markup.php'; ?>
                <?= avatar_warga_html(WARNA_BAWAAN['baju'], WARNA_BAWAAN['kulit'], WARNA_BAWAAN['topi'], 'aku-avatar-pratinjau') ?>
                <form class="aku-form" id="akuFormKtp" novalidate>
                    <label class="sr-saja" for="akuNama">Nama lengkap</label>
                    <input id="akuNama" name="nama" type="text" maxlength="50" placeholder="Nama lengkap"
                           autocomplete="name" enterkeyhint="done">
                    <label class="sr-saja" for="akuGang">Gang profesi</label>
                    <select id="akuGang" name="gang">
                        <option value="">Pilih gang profesimu</option>
                        <?php foreach (GANG_WARGA as $gangPilihan): ?>
                            <option value="<?= e($gangPilihan) ?>"><?= e($gangPilihan) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <fieldset class="pilih-warna pilih-warna-panel">
                        <legend>Tampilan figurnya</legend>
                        <?php foreach ([
                            'baju' => ['label' => 'Warna baju', 'palet' => WARNA_BAJU],
                            'kulit' => ['label' => 'Warna kulit', 'palet' => WARNA_KULIT],
                            'topi' => ['label' => 'Warna topi', 'palet' => WARNA_TOPI],
                        ] as $bagian => $info): ?>
                            <div class="pilih-warna-baris" role="radiogroup" aria-label="<?= e($info['label']) ?>">
                                <span class="pilih-warna-label"><?= e($info['label']) ?></span>
                                <span class="pilih-warna-swal">
                                    <?php foreach ($info['palet'] as $kunci => $warna): ?>
                                        <label class="pilih-warna-satu" title="<?= e($warna['nama']) ?>">
                                            <input type="radio" name="warna_<?= e($bagian) ?>" value="<?= e($kunci) ?>"<?= $kunci === WARNA_BAWAAN[$bagian] ? ' checked' : '' ?>>
                                            <span class="pilih-warna-bulat" style="background: <?= e($warna['hex']) ?>"></span>
                                            <span class="sr-saja"><?= e($warna['nama']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    <button type="submit" class="tombol-panel-utama">Bikin KTP Warga, Gratis</button>
                </form>
                <p class="aku-kabar" id="akuKabar" role="status" hidden></p>
                <p class="panel-catatan">Kartumu tersimpan di peramban ini. Buka <a href="<?= e($tabAwalan) ?>ktp.php">halaman KTP Warga</a> kalau mau melihat kartu lengkapnya.</p>
            </div>

            <div class="aku-punya" id="akuPunya" hidden>
                <p class="label-kecil">KARTU WARGAMU</p>
                <p class="aku-nama" id="akuName">Warga</p>
                <p class="aku-meta" id="akuMeta"></p>
                <p class="aku-poin"><strong id="akuPoinAngka">0</strong> poin</p>
                <p class="aku-kabar" id="akuKabarPunya" role="status" hidden></p>
                <p class="aku-aksi">
                    <button type="button" class="tombol-panel-utama" id="akuKlaim">Klaim poin harian</button>
                    <a class="tombol-panel-biasa" href="<?= e($tabAwalan) ?>ktp.php">Lihat kartu lengkap</a>
                </p>
                <p class="panel-catatan">Poin bisa dipakai untuk usaha di desa. Misi dan riwayatnya ada di panel Misi.</p>
                <div class="aku-koleksi" id="akuKoleksi"><h3>Koleksiku</h3><div class="aku-lencana" id="akuLencana"><p class="panel-catatan">Memuat lencana…</p></div></div>
            </div>
            <?php require __DIR__ . '/pemulihan-markup.php'; ?>
        </div>

        <?php // Ruko warga (Fitur 3), dibuka langsung dari tab Ruko (ADR-0006).
              // Isinya dua keadaan yang dibedakan includes/ruko-skrip.php:
              // tamu (ajakan bikin KTP) dan pemilik ruko (data rukunya).
              // Formulir klaim dan lepas ruko memakai ruko.php yang sama. ?>
        <div class="panel-isi" data-isi="ruko" hidden>
            <div class="ruko-panel-tamu" id="rukoPanelTamu" hidden>
                <p class="panel-deskripsi">Buka ruko usahamu di desa: nama usaha, cerita singkat, dan satu tautan jualan. Gratis, dan pembeli diarahkan langsung ke tempat jualanmu sendiri.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga dulu</a>
                <p class="panel-catatan">Sudah punya kartu? Buka <a href="<?= e($tabAwalan) ?>ktp.php">KTP Warga</a> sekali, lalu kembali ke sini.</p>
            </div>

            <div class="ruko-panel-muat" id="rukoPanelMuat">
                <p class="panel-deskripsi">Memuat data rukomu…</p>
            </div>

            <div class="ruko-panel-milik" id="rukoPanelMilik" hidden>
                <img class="gambar-usaha ruko-panel-gambar" id="rukoPanelGambar" alt="" width="640" height="480" hidden>
                <p class="label-kecil ruko-panel-label">RUKO PUNYAMU</p>
                <h3 class="ruko-panel-nama" id="rukoPanelNama">Ruko</h3>
                <p class="ruko-panel-gang" id="rukoPanelGang"></p>
                <p class="ruko-panel-cerita" id="rukoPanelCerita"></p>
                <p class="ruko-panel-kabar" id="rukoPanelKabar" role="status" hidden></p>
                <p class="ruko-panel-aksi">
                    <a class="tombol-panel-utama" id="rukoPanelKunjungi" href="#" target="_blank" rel="noopener noreferrer" hidden>Kunjungi tempat jualan</a>
                    <a class="tombol-panel-biasa" id="rukoPanelUbah" href="<?= e($tabAwalan) ?>ruko.php">Ubah ruko</a>
                </p>
                <p class="panel-catatan">Pesanan tetap berjalan di tempat jualanmu sendiri. Desa tidak memproses pembayaran apa pun.</p>
            </div>

            <div class="ruko-panel-kosong" id="rukoPanelKosong" hidden>
                <p class="panel-deskripsi">Kamu belum punya ruko di desa. Ruko tampil di peta dengan namamu, dan bisa dipakai berjualan di Pasar Malam atau CFD.</p>
                <a class="tombol-panel-utama" id="rukoPanelBuka" href="<?= e($tabAwalan) ?>ruko.php">Buka ruko usahamu</a>
            </div>

            <?php // Ruko warga lain: 2 kolom bergambar seperti katalog, supaya
                  // tab Ruko tidak cuma berisi ruko sendiri. ?>
            <div class="ruko-panel-daftar" id="rukoPanelDaftar" hidden>
                <h3 class="ruko-panel-judul-daftar">Ruko warga desa</h3>
                <ul class="ruko-grid" id="rukoPanelGrid"></ul>
            </div>

            <p class="panel-catatan">
                <a href="<?= e($tabAwalan) ?>ruko.php">Halaman ruko warga</a>
                &middot; <a href="<?= e($tabAwalan) ?>pasar.php">Pasar Desa</a>
            </p>
        </div>

        <?php // Chat antar gang (Fitur 2). Tidak ada tab kelima (sekarang lima
              // tab sudah terpakai): isi ini dibuka
              // dari tombol Chat di bilah atas. Hanya frase preset, tanpa kolom
              // teks bebas, dan semua pesan publik. ?>
        <div class="panel-isi" data-isi="chat" hidden>
            <p class="panel-deskripsi">Sapa warga desa lewat pilihan kalimat di bawah. Chat ini publik, semua warga bisa membaca, tidak ada pesan pribadi.</p>

            <div class="chat-pilih" id="chatPilih" role="group" aria-label="Pilih kanal chat"></div>

            <div class="chat-riwayat" id="chatRiwayat" aria-live="polite" aria-label="Pesan terbaru">
                <p class="chat-kosong" id="chatKosong">Sedang memuat pesan…</p>
            </div>

            <div class="chat-kirim" id="chatKirim" hidden>
                <p class="chat-kirim-judul">Pilih kalimat untuk dikirim</p>
                <div class="chat-frase" id="chatFrase"></div>
                <p class="chat-kabar" id="chatKabar" role="status" hidden></p>
            </div>

            <div class="chat-tamu" id="chatTamu" hidden>
                <p class="panel-deskripsi">Bikin KTP Warga supaya bisa ikut menyapa di chat. Membaca pesan tetap gratis tanpa kartu.</p>
                <a class="tombol-panel-utama" href="<?= e($tabAwalan) ?>ktp.php">Bikin KTP Warga</a>
            </div>
        </div>

    </div>
</section>
