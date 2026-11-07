<?php
// Tab bar bawah: Desa · Misi · Papan Gang · Aku.
//
// Penanda tab aktif dibaca dari $tabAktif yang diatur halaman pemanggil.
// Bila tidak diatur, tidak ada tab yang disorot. Tab "Desa" dan "Aku" adalah
// tautan biasa (beranda dan KTP); "Misi" dan "Papan Gang" membuka panel di
// halaman ini lewat data-panel, supaya dunia 3D tidak hilang saat diketuk.
//
// Variabel yang dipakai:
//   $tabAktif   id tab yang disorot: desa | misi | gang | aku
//   $tabAwalan  awalan URL untuk tautan, '' dari akar situs, '../' dari peta3d/
//
// Markup saja. Perilakunya di tab-bar-skrip.php, gayanya di assets/style.css.
// e() disediakan header.php atau config.php; di sini dijaga sendiri supaya
// partial ini tidak meledak bila ditempel sebelum keduanya dimuat.
if (!function_exists('e')) {
    function e(?string $teks): string
    {
        return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$tabAktif = $tabAktif ?? '';
$tabAwalan = $tabAwalan ?? '';
$daftarTab = [
    ['id' => 'desa', 'ikon' => '&#127968;', 'teks' => 'Desa', 'href' => $tabAwalan . 'index.php'],
    ['id' => 'misi', 'ikon' => '&#127919;', 'teks' => 'Misi', 'panel' => 'misi'],
    ['id' => 'gang', 'ikon' => '&#128200;', 'teks' => 'Papan Gang', 'panel' => 'papan-gang'],
    ['id' => 'aku', 'ikon' => '&#129470;', 'teks' => 'Aku', 'href' => $tabAwalan . 'ktp.php'],
];
?>
<nav class="tab-bar" id="tabBar" aria-label="Navigasi utama desa">
    <?php foreach ($daftarTab as $tab): ?>
        <?php $aktif = $tabAktif === $tab['id']; ?>
        <?php if (isset($tab['panel'])): ?>
            <button type="button"
                    class="tab-item<?= $aktif ? ' aktif' : '' ?>"
                    data-panel="<?= e($tab['panel']) ?>"
                    aria-expanded="false"
                    aria-controls="panelBersayap">
                <span class="tab-ikon" aria-hidden="true"><?= $tab['ikon'] ?></span>
                <span class="tab-teks"><?= e($tab['teks']) ?></span>
            </button>
        <?php else: ?>
            <a class="tab-item<?= $aktif ? ' aktif' : '' ?>" href="<?= e($tab['href']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                <span class="tab-ikon" aria-hidden="true"><?= $tab['ikon'] ?></span>
                <span class="tab-teks"><?= e($tab['teks']) ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
