<?php
// Pemilih gambar usaha untuk ruko (dipakai ruko-markup.php).
//
// Gambar disajikan sebagai deretan kartu radio. Hanya berkas yang ada di
// assets/img/ yang muncul; foto unggahan pemilik ditampilkan terpisah.
//
// Variabel yang dipakai:
//   $pilihanGambar  daftar jalur gambar yang sah
//   $gambarSaya     gambar yang sedang dipakai ('' bila belum ada)
//   $isianRuko      isian formulir terakhir, termasuk 'gambar' dan 'hapus_gambar'
$pilihanGambar = $pilihanGambar ?? [];
$gambarSaya = $gambarSaya ?? '';
$terpilih = (string) ($isianRuko['gambar'] ?? $gambarSaya);
$hapusTercentang = !empty($isianRuko['hapus_gambar']);
?>
<fieldset class="pilih-gambar">
    <legend>Gambar usaha</legend>
    <?php if (str_starts_with($gambarSaya, 'assets/uploads/usaha/')): ?>
        <label class="pilih-gambar-kartu"><input type="radio" name="gambar" value="<?= e($gambarSaya) ?>"<?= $terpilih === $gambarSaya ? ' checked' : '' ?>><img src="<?= e($gambarSaya) ?>" alt="Foto usahamu" width="640" height="480"></label>
    <?php endif; ?>
    <p class="formulir-catatan">Pilih satu gambar yang paling mirip usahamu. Gambar juga tampil di lapak Pasar Malam dan CFD.</p>
    <?php if ($pilihanGambar === []): ?>
        <p class="formulir-catatan">Belum ada gambar yang bisa dipilih.</p>
    <?php else: ?>
        <label class="pilih-gambar-kartu pilih-gambar-kosong<?= $terpilih === '' ? ' terpilih' : '' ?>">
            <input type="radio" name="gambar" value=""<?= $terpilih === '' ? ' checked' : '' ?>>
            <span class="sr-saja">Tanpa gambar</span>
            <span class="gambar-usaha-kosong">Tanpa gambar</span>
        </label>
        <?php foreach ($pilihanGambar as $jalur): ?>
            <?php $namaBerkas = basename($jalur); ?>
            <label class="pilih-gambar-kartu<?= $terpilih === $jalur ? ' terpilih' : '' ?>" data-gambar="<?= e($jalur) ?>">
                <input type="radio" name="gambar" value="<?= e($jalur) ?>"<?= $terpilih === $jalur ? ' checked' : '' ?>>
                <img class="gambar-usaha" src="<?= e($jalur) ?>" alt="" loading="lazy"
                     width="640" height="480" style="height:auto">
            </label>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($gambarSaya !== '' && $hapusTercentang === false): ?>
        <label class="pilih-gambar-hapus">
            <input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar usaha
        </label>
    <?php endif; ?>
</fieldset>
<script>
// Sorot kartu gambar yang dipilih supaya pilihan jelas tanpa harus membaca
// radio kecil di pojok.
(function () {
    document.querySelectorAll('.pilih-gambar').forEach(function (kotak) {
        kotak.addEventListener('change', function (e) {
            if (e.target.name !== 'gambar') return;
            kotak.querySelectorAll('.pilih-gambar-kartu').forEach(function (k) {
                k.classList.toggle('terpilih', k.contains(e.target) && e.target.checked);
            });
        });
    });
})();
</script>
