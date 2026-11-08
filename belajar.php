<?php
// Pusat Pembelajaran Desa Target (Fitur 5 dari spesifikasi).
//
// Kelas berisi materi berurutan: video, artikel, kuis. Materi berikutnya
// hanya terbuka setelah materi sebelumnya selesai untuk warga yang sama.
// Menyelesaikan materi memberi poin lewat tambah_poin() dengan sumber
// 'materi'. Kunci jawaban kuis dinilai di server dan tidak pernah dikirim
// ke peramban. Lapisan ini endpoint JSON sempit ala misi.php: GET membaca
// keadaan belajar, POST mencatat penyelesaian dan menilai kuis.
require __DIR__ . '/db.php';

function belajar_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Warga dari nomor + kunci rahasia kartu. Pesan galatnya sama untuk nomor
// tak dikenal dan kunci yang salah, supaya tidak membocorkan nomor siapa
// saja yang terdaftar.
function belajar_warga(PDO $pdo): ?array
{
    $nomor = trim((string) ($_REQUEST['nomor'] ?? ''));
    $kunci = trim((string) ($_REQUEST['kunci'] ?? ''));
    if ($nomor === '' || $kunci === '') {
        return null;
    }
    return warga_dari_nomor_dan_kunci($pdo, $nomor, $kunci);
}

function belajar_gagal_identitas(): void
{
    belajar_json(['ok' => false, 'galat' => 'identitas', 'pesan' => 'Nomor atau kunci warga tidak cocok.'], 403);
}

$metode = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo = db();

    if ($metode === 'POST') {
        $warga = belajar_warga($pdo);
        if ($warga === null) {
            belajar_gagal_identitas();
        }
        $nomor = nomor_warga((int) $warga['id']);
        $materiId = (int) ($_POST['materi'] ?? 0);
        $materi = $materiId > 0 ? materi_dari_id($pdo, $materiId) : null;
        if ($materi === null) {
            belajar_json(['ok' => false, 'galat' => 'materi', 'pesan' => 'Materinya tidak ditemukan.'], 404);
        }
        if (isset(progres_warga($pdo, $nomor)[(int) $materi['id']])) {
            belajar_json(['ok' => true, 'baru' => false, 'pesan' => 'Materi ini sudah kamu selesaikan sebelumnya.', 'saldo' => saldo_poin($pdo, $nomor)]);
        }
        if (!materi_terbuka($pdo, $materi, $nomor)) {
            belajar_json(['ok' => false, 'galat' => 'terkunci', 'pesan' => 'Selesaikan materi sebelumnya dulu ya.'], 403);
        }
        $aksi = (string) ($_POST['aksi'] ?? '');
        if ($materi['tipe'] === 'kuis') {
            if ($aksi !== 'kuis') {
                belajar_json(['ok' => false, 'galat' => 'aksi', 'pesan' => 'Kirim jawaban kuisnya dulu ya.'], 400);
            }
            $mentah = $_POST['jawaban'] ?? [];
            $jawabanWarga = [];
            if (is_array($mentah)) {
                foreach ($mentah as $i => $nilai) {
                    $jawabanWarga[(int) $i] = is_numeric($nilai) ? (int) $nilai : -1;
                }
            }
            $nilai = nilai_kuis($materi, $jawabanWarga);
            if (!$nilai['lulus']) {
                belajar_json([
                    'ok' => true, 'lulus' => false, 'benar' => $nilai['benar'], 'total' => $nilai['total'],
                    'pesan' => 'Belum lulus: ' . $nilai['benar'] . ' dari ' . $nilai['total'] . ' soal benar. Baca lagi materinya lalu coba lagi.',
                ]);
            }
            $hasil = selesaikan_materi($pdo, $nomor, $materi);
            if (!$hasil['ok']) {
                belajar_json(['ok' => false, 'galat' => 'gagal', 'pesan' => 'Hasil kuis belum bisa dicatat. Coba lagi sebentar lagi.'], 500);
            }
            $berikutnya = materi_berikutnya($pdo, $nomor, (int) $materi['kelas_id']);
            belajar_json([
                'ok' => true, 'lulus' => true, 'baru' => $hasil['baru'], 'poin' => $hasil['poin'],
                'saldo' => $hasil['saldo'], 'berikutnya' => $berikutnya !== null ? (int) $berikutnya['id'] : null,
                'kelasTuntas' => kelas_selesai_untuk($pdo, $nomor, (int) $materi['kelas_id']),
                'pesan' => 'Lulus! Semua jawaban benar. Kamu dapat ' . $hasil['poin'] . ' poin.',
            ]);
        }
        if ($aksi !== 'selesai') {
            belajar_json(['ok' => false, 'galat' => 'aksi', 'pesan' => 'Aksi tidak dikenal.'], 400);
        }
        if ($materi['tipe'] === 'video' && trim((string) ($materi['youtube_id'] ?? '')) === '') {
            belajar_json(['ok' => false, 'galat' => 'segera', 'pesan' => 'Video ini masih segera hadir, belum bisa ditandai selesai.'], 422);
        }
        $hasil = selesaikan_materi($pdo, $nomor, $materi);
        if (!$hasil['ok']) {
            belajar_json(['ok' => false, 'galat' => 'gagal', 'pesan' => 'Penyelesaian belum bisa dicatat. Coba lagi sebentar lagi.'], 500);
        }
        $berikutnya = materi_berikutnya($pdo, $nomor, (int) $materi['kelas_id']);
        belajar_json([
            'ok' => true, 'baru' => $hasil['baru'], 'poin' => $hasil['poin'], 'saldo' => $hasil['saldo'],
            'berikutnya' => $berikutnya !== null ? (int) $berikutnya['id'] : null,
            'kelasTuntas' => kelas_selesai_untuk($pdo, $nomor, (int) $materi['kelas_id']),
            'pesan' => $hasil['baru'] ? 'Materi selesai. Kamu dapat ' . $hasil['poin'] . ' poin.' : 'Materi ini sudah kamu selesaikan sebelumnya.',
        ]);
    }

    if ($metode !== 'GET') {
        belajar_json(['ok' => false, 'galat' => 'metode', 'pesan' => 'Metode tidak didukung.'], 405);
    }

    // GET: tanpa nomor dan kunci, yang tampil adalah katalog publik plus
    // petunjuk masuk. Dengan identitas yang sah, setiap kelas dan materi
    // membawa status warga itu sendiri.
    $warga = belajar_warga($pdo);
    $nomor = $warga !== null ? nomor_warga((int) $warga['id']) : null;
    $kelasSlug = trim((string) ($_GET['kelas'] ?? ''));

    if ($kelasSlug === '') {
        $daftar = [];
        foreach (daftar_kelas($pdo) as $k) {
            $keadaan = keadaan_belajar($pdo, $nomor, (int) $k['id']);
            $daftar[] = [
                'slug' => $k['slug'],
                'judul' => $k['judul'],
                'keterangan' => $k['keterangan'],
                'jumlahMateri' => (int) $k['jumlah_materi'],
                'totalPoin' => (int) $k['total_poin'],
                'selesai' => $keadaan['selesai'],
                'total' => $keadaan['total'],
                'persen' => $keadaan['persen'],
                'tuntas' => $keadaan['tuntas'],
            ];
        }
        // Pengunjung (belum login): halaman penuh. Pemakai panel dan
        // interior menerima JSON berisi data yang sama (ADR-0004).
        if (($_GET['format'] ?? '') !== 'json') {
            require __DIR__ . '/belajar-markup.php';
            exit;
        }
        belajar_json([
            'ok' => true,
            'pengunjung' => $warga === null,
            'warga' => $warga !== null ? ['nomor' => $nomor, 'nama' => $warga['nama'], 'gang' => $warga['gang']] : null,
            'kelas' => $daftar,
        ]);
    }

    $kelas = kelas_dari_slug($pdo, $kelasSlug);
    if ($kelas === null) {
        if (($_GET['format'] ?? '') === 'json') {
            belajar_json(['ok' => false, 'galat' => 'kelas', 'pesan' => 'Kelasnya tidak ditemukan.'], 404);
        }
        require __DIR__ . '/belajar-markup.php';
        exit;
    }
    $keadaan = keadaan_belajar($pdo, $nomor, (int) $kelas['id']);
    $materiTerpilih = null;
    $mintaId = (int) ($_GET['materi'] ?? 0);
    foreach ($keadaan['materi'] as $m) {
        if ($mintaId > 0 ? (int) $m['id'] === $mintaId : (!$m['selesai'] && $m['terbuka'])) {
            $materiTerpilih = $m;
            break;
        }
    }
    if ($materiTerpilih === null && $mintaId === 0 && $keadaan['materi'] !== []) {
        // Kelas sudah tuntas untuk warga ini: tampilkan materi terakhir.
        $materiTerpilih = $keadaan['materi'][count($keadaan['materi']) - 1];
    }
    if (($_GET['format'] ?? '') !== 'json') {
        require __DIR__ . '/belajar-markup.php';
        exit;
    }
    $materiKeluar = [];
    foreach ($keadaan['materi'] as $m) {
        $baris = [
            'id' => (int) $m['id'],
            'judul' => $m['judul'],
            'tipe' => $m['tipe'],
            'poin' => (int) $m['poin'],
            'selesai' => $m['selesai'],
            'selesaiPada' => $m['selesai_pada'],
            'terbuka' => $m['terbuka'],
            'segera' => $m['segera'],
        ];
        if ((int) ($materiTerpilih['id'] ?? 0) === (int) $m['id'] && $m['terbuka']) {
            if ($m['tipe'] === 'artikel') {
                $baris['isi'] = $m['isi'];
            } elseif ($m['tipe'] === 'video') {
                $baris['youtubeId'] = $m['segera'] ? null : (string) $m['youtube_id'];
            } elseif ($m['tipe'] === 'kuis') {
                $baris['soal'] = soal_kuis_publik($m);
            }
        }
        $materiKeluar[] = $baris;
    }
    belajar_json([
        'ok' => true,
        'pengunjung' => $warga === null,
        'warga' => $warga !== null ? ['nomor' => $nomor, 'nama' => $warga['nama'], 'gang' => $warga['gang']] : null,
        'kelas' => [
            'slug' => $kelas['slug'],
            'judul' => $kelas['judul'],
            'keterangan' => $kelas['keterangan'],
            'selesai' => $keadaan['selesai'],
            'total' => $keadaan['total'],
            'persen' => $keadaan['persen'],
            'tuntas' => $keadaan['tuntas'],
            'materi' => $materiKeluar,
            'materiAktif' => $materiTerpilih !== null ? (int) $materiTerpilih['id'] : null,
        ],
    ]);
} catch (Throwable) {
    // Jangan bocorkan rincian basis data ke peramban.
    belajar_json(['ok' => false, 'galat' => 'server', 'pesan' => 'Belajar belum bisa dibuka. Coba lagi sebentar lagi.'], 500);
}
