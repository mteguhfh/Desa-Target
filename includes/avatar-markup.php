<?php
function avatar_warga_html(string $baju, string $kulit, string $topi, string $kelas = ''): string
{
    $bajuHex = WARNA_BAJU[$baju]['hex'] ?? WARNA_BAJU[WARNA_BAWAAN['baju']]['hex'];
    $kulitHex = WARNA_KULIT[$kulit]['hex'] ?? WARNA_KULIT[WARNA_BAWAAN['kulit']]['hex'];
    $topiHex = WARNA_TOPI[$topi]['hex'] ?? WARNA_TOPI[WARNA_BAWAAN['topi']]['hex'];
    return '<div class="avatar-warga ' . e($kelas) . '" '
        . 'style="--avatar-baju:' . e($bajuHex) . ';--avatar-kulit:' . e($kulitHex) . ';--avatar-topi:' . e($topiHex) . '" '
        . 'aria-label="Pratinjau avatar warga"><span class="avatar-bayang"></span><span class="avatar-kaki avatar-kaki-kiri"></span><span class="avatar-kaki avatar-kaki-kanan"></span><span class="avatar-badan"></span><span class="avatar-kepala"></span><span class="avatar-topi"></span><span class="avatar-mata avatar-mata-kiri"></span><span class="avatar-mata avatar-mata-kanan"></span></div>';
}
