<?php
// Tab bar bawah: Desa · Warga · Gang · Aku.
//
// Di halaman peta, tab "Desa" diganti tombol "Bangunan" yang membuka daftar
// bangunan di panel: mengetuk salah satunya menyorot bangunan itu di dunia.
// Di luar peta, "Desa" tetap beranda. Perilaku ini diatur halaman pemanggil
// lewat $tabDesaPanel.
//
// Penanda tab aktif dibaca dari $tabAktif yang diatur halaman pemanggil.
// Bila tidak diatur, tidak ada tab yang disorot. Tab "Desa" adalah tautan
// biasa (beranda); "Warga", "Gang", "Aku", dan "Ruko" membuka panel di
// halaman ini lewat data-panel, supaya dunia 3D tidak hilang saat diketuk.
// KTP dibuat langsung di panel Aku (ADR-0007).
// Pencarian warga lebih berguna daripada daftar misi yang belum ada, jadi
// slot Misi dipakai untuk Warga. Misi tetap ada sebagai isi panel tersembunyi
// sampai fiturnya dibuat (lihat replica/rencana-kerja.md Tahap 3).
//
// Variabel yang dipakai:
//   $tabAktif   id tab yang disorot: desa | misi | gang | aku | ruko
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
$tabDesaPanel = $tabDesaPanel ?? false;
$daftarTab = [
    $tabDesaPanel
        ? ['id' => 'bangunan', 'ikon' => '&#127970;', 'teks' => 'Bangunan', 'panel' => 'bangunan']
        : ['id' => 'desa', 'ikon' => '&#127968;', 'teks' => 'Desa', 'href' => $tabAwalan . 'index.php'],
    ['id' => 'warga', 'ikon' => '&#128269;', 'teks' => 'Warga', 'panel' => 'warga'],
    ['id' => 'gang', 'ikon' => '&#128200;', 'teks' => 'Gang', 'panel' => 'papan-gang'],
    // Aku sekarang membuka panel: formulir KTP ada di dalamnya (ADR-0007).
    ['id' => 'aku', 'ikon' => '&#129470;', 'teks' => 'Aku', 'panel' => 'aku'],
    // Ruko warga (Fitur 3) butuh jalan masuk dari dalam peta. Digambar sama
    // seperti empat tab utama (bukan pil kecil) supaya barisnya rata, lalu
    // membuka panel Ruko di dalam peta, bukan halaman baru (ADR-0006).
    ['id' => 'ruko', 'ikon' => '&#127978;', 'teks' => 'Ruko', 'panel' => 'ruko'],
    ['id' => 'pasar', 'ikon' => '&#128717;', 'teks' => 'Pasar', 'panel' => 'pasar'],
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
