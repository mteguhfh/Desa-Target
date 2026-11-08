<?php
const KATEGORI_USAHA = ['makanan' => 'Makanan', 'jasa' => 'Jasa', 'kerajinan' => 'Kerajinan', 'lainnya' => 'Lainnya'];

function simpan_katalog(PDO $pdo, string $tabel, int $id, mixed $kategori, mixed $harga): void
{
    if (!in_array($tabel, ['bangunan', 'lapak'], true)) throw new InvalidArgumentException('Tabel katalog tidak sah.');
    if (!is_string($kategori) || !isset(KATEGORI_USAHA[$kategori])) $kategori = 'lainnya';
    $harga = filter_var($harga, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000000]]);
    $pdo->prepare("UPDATE $tabel SET kategori = ?, harga = ? WHERE id = ?")->execute([$kategori, $harga === false ? null : $harga, $id]);
}

function harga_usaha(mixed $harga): string
{
    return $harga === null || $harga === '' ? 'Tanyakan harga' : 'Rp' . number_format((int) $harga, 0, ',', '.');
}

function isian_katalog(array $data, string $id): void
{
    echo '<label for="' . $id . 'Kategori">Kategori usaha</label><select id="' . $id . 'Kategori" name="kategori">';
    foreach (KATEGORI_USAHA as $kode => $nama) {
        echo '<option value="' . $kode . '"' . (($data['kategori'] ?? 'lainnya') === $kode ? ' selected' : '') . '>' . $nama . '</option>';
    }
    echo '</select><label for="' . $id . 'Harga">Harga mulai (rupiah, opsional)</label><input id="' . $id . 'Harga" name="harga" type="number" min="0" max="1000000000" value="' . htmlspecialchars((string) ($data['harga'] ?? ''), ENT_QUOTES, 'UTF-8') . '">';
}
