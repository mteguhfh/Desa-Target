<?php
require_once __DIR__ . '/db.php';

$pdo = db();
$bangunan = semua_bangunan($pdo);
$papanGang = papan_gang($pdo);
$totalKavling = 12; // Ukuran peta v1: 12 kavling dalam grid 4 x 3.
$terbaruId = $bangunan !== [] ? (int) $bangunan[count($bangunan) - 1]['id'] : null;

$judulHalaman = 'Peta Desa';
$halamanAktif = 'peta';
require __DIR__ . '/includes/header.php';
?>

<section class="seksi">
    <h1>Peta Desa Target</h1>
    <p class="seksi-deskripsi">
        Setiap bangunan di peta ini lahir dari komentar warga di Instagram.
        Komentar paling banyak disukai hari ini, besok paginya sudah berdiri di sini.
        Masih banyak tanah kosong, desa ini menunggu usulan usahamu.
    </p>

    <div class="peta-grid" role="list">
        <?php foreach ($bangunan as $b): ?>
            <article class="kavling terisi<?= (int) $b['id'] === $terbaruId ? ' terbaru' : '' ?>" role="listitem">
                <div class="kavling-atap"></div>
                <div class="kavling-badan">
                    <h3><?= e($b['nama']) ?></h3>
                    <?php if (!empty($b['gang'])): ?>
                        <p><span class="lencana-gang"><?= e($b['gang']) ?></span></p>
                    <?php endif; ?>
                    <?php if (!empty($b['diminta_oleh'])): ?>
                        <p class="kavling-meta">Diminta oleh <?= e($b['diminta_oleh']) ?></p>
                    <?php endif; ?>
                    <p class="kavling-meta">Berdiri <?= e(format_tanggal($b['tanggal'])) ?></p>
                    <?php if ((int) $b['id'] === $terbaruId): ?>
                        <p class="lencana-baru">Baru dibangun</p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php for ($i = count($bangunan); $i < $totalKavling; $i++): ?>
            <article class="kavling kosong" role="listitem">
                <div class="kavling-badan">
                    <h3>Tanah Kosong</h3>
                    <p class="kavling-meta">Kavling ini belum ada yang bangun.</p>
                    <p class="kavling-meta">Usulkan usahamu di komentar Instagram, siapa tahu besok kavling ini jadi milikmu.</p>
                </div>
            </article>
        <?php endfor; ?>
    </div>
</section>

<section class="seksi">
    <div class="seksi-kepala">
        <h2>Papan Gang</h2>
        <a href="ktp.php">Gabung gang-mu</a>
    </div>
    <p class="seksi-deskripsi">Jumlah warga terdaftar di tiap gang. Ajak teman seprofesimu biar gang-mu naik peringkat.</p>
    <?php require __DIR__ . '/includes/papan-gang.php'; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
