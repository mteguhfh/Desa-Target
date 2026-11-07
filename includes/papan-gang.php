<?php
// Partial papan gang: peringkat jumlah warga per gang.
// Mengharapkan variabel $papanGang dari papan_gang() di db.php.
// Partial ini sengaja berdiri sendiri supaya gampang ditempel di halaman mana pun
// dan kelak bisa dimuat ulang terpisah (misalnya lewat HTMX).
$jumlahTerbanyak = 0;
foreach ($papanGang as $baris) {
    $jumlahTerbanyak = max($jumlahTerbanyak, (int) $baris['jumlah']);
}
?>
<div class="papan-gang">
    <?php foreach ($papanGang as $i => $baris): ?>
        <?php $persen = $jumlahTerbanyak > 0 ? (int) round($baris['jumlah'] / $jumlahTerbanyak * 100) : 0; ?>
        <div class="papan-baris">
            <span class="papan-peringkat"><?= $i + 1 ?></span>
            <span class="papan-nama"><?= e($baris['gang']) ?></span>
            <span class="papan-palangan"><span class="papan-isi" style="width: <?= $persen ?>%"></span></span>
            <span class="papan-jumlah"><?= (int) $baris['jumlah'] ?> warga</span>
        </div>
    <?php endforeach; ?>
</div>
