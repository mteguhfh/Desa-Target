<?php
function unggah_foto_usaha(array $berkas, string $nomor): string
{
    if (($berkas['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Foto gagal diunggah. Pilih berkas maksimal 5 MB.');
    if ($berkas['size'] > 5 * 1024 * 1024 || !is_uploaded_file($berkas['tmp_name'])) throw new RuntimeException('Foto harus maksimal 5 MB.');
    $info = @getimagesize($berkas['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) || $info[0] * $info[1] > 16000000) throw new RuntimeException('Gunakan JPG, PNG, atau WebP hingga 16 megapiksel.');
    if (!function_exists('imagecreatefromstring')) throw new RuntimeException('Pengolah foto belum tersedia. Pilih ilustrasi dahulu.');
    $sumber = @imagecreatefromstring(file_get_contents($berkas['tmp_name']));
    if (!$sumber) throw new RuntimeException('Foto tidak dapat dibaca. Coba foto lain.');
    $rasio = min(1, 1280 / max($info[0], $info[1]));
    $hasil = imagecreatetruecolor(max(1, (int) round($info[0] * $rasio)), max(1, (int) round($info[1] * $rasio)));
    imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255));
    imagecopyresampled($hasil, $sumber, 0, 0, 0, 0, imagesx($hasil), imagesy($hasil), $info[0], $info[1]);
    $dir = dirname(__DIR__) . '/assets/uploads/usaha';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Foto belum bisa disimpan. Coba lagi.');
    $path = 'assets/uploads/usaha/' . $nomor . '-' . bin2hex(random_bytes(12)) . '.jpg';
    if (!imagejpeg($hasil, dirname(__DIR__) . '/' . $path, 82)) throw new RuntimeException('Foto belum bisa disimpan. Coba lagi.');
    return $path;
}

function isian_foto_usaha(string $id): void
{
    echo '<div class="unggah-usaha"><label for="' . $id . '">Atau unggah foto usahamu</label><p class="formulir-catatan">Satu foto produk atau toko. JPG, PNG, atau WebP, maksimal 5 MB. Foto akan diperkecil otomatis.</p><input type="file" id="' . $id . '" name="foto_usaha" accept="image/jpeg,image/png,image/webp"><img class="foto-usaha-pratinjau" hidden width="640" height="480" alt="Pratinjau foto usaha"></div>';
}
