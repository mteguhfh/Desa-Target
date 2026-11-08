<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
try {
    $pdo = db();
    $warga = warga_dari_nomor_dan_kunci($pdo, $_POST['nomor'] ?? '', $_POST['kunci'] ?? '');
    if (!$warga) { http_response_code(403); echo json_encode(['galat'=>'Nomor atau kunci warga tidak cocok.']); exit; }
    $nomor = nomor_warga((int)$warga['id']);
    $pdo->exec("CREATE TABLE IF NOT EXISTS inbox_pesan (id INTEGER PRIMARY KEY AUTOINCREMENT, pengirim TEXT NOT NULL, penerima TEXT NOT NULL, teks TEXT NOT NULL, dibuat_pada TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec('CREATE INDEX IF NOT EXISTS inbox_penerima ON inbox_pesan(penerima, id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS inbox_pengirim ON inbox_pesan(pengirim, id)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS inbox_baca (nomor TEXT PRIMARY KEY, terakhir INTEGER NOT NULL DEFAULT 0)');
    if (isset($_POST['baca']) && is_string($_POST['baca']) && ctype_digit($_POST['baca'])) {
        $s = $pdo->prepare('SELECT COALESCE(MAX(id),0) FROM inbox_pesan WHERE penerima=? AND id<=?');
        $s->execute([$nomor, $_POST['baca']]);
        $pdo->prepare('INSERT INTO inbox_baca(nomor,terakhir) VALUES(?,?) ON CONFLICT(nomor) DO UPDATE SET terakhir=MAX(terakhir,excluded.terakhir)')->execute([$nomor,(int)$s->fetchColumn()]);
    }
    $s = $pdo->prepare('SELECT terakhir FROM inbox_baca WHERE nomor=?'); $s->execute([$nomor]);
    $dibaca = (int)$s->fetchColumn();
    if (isset($_POST['teks'])) {
        $teks = is_string($_POST['teks']) ? trim($_POST['teks']) : '';
        $tujuan = $_POST['tujuan'] ?? '';
        if (!is_string($tujuan) || !preg_match('/^DT-[0-9]+$/D', $tujuan) || $tujuan === $nomor || !$teks || !preg_match('//u', $teks) || preg_match_all('/./us', $teks) > 280 || preg_match('/[\x00-\x1F\x7F]/', $teks)) throw new InvalidArgumentException('Pilih warga lain dan tulis pesan 1 sampai 280 karakter.');
        $s = $pdo->prepare('SELECT id FROM warga WHERE id = ?'); $s->execute([(int)substr($tujuan,3)]);
        if (!$s->fetchColumn()) throw new InvalidArgumentException('Warga tujuan tidak tersedia.');
        $pdo->exec('BEGIN IMMEDIATE');
        $s = $pdo->prepare('SELECT dibuat_pada FROM inbox_pesan WHERE pengirim=? ORDER BY id DESC LIMIT 1'); $s->execute([$nomor]); $waktu=$s->fetchColumn();
        if ($waktu && time()-strtotime($waktu.' UTC') < CHAT_JEDA_DETIK) { $pdo->exec('ROLLBACK'); http_response_code(429); echo json_encode(['galat'=>'Tunggu beberapa detik sebelum mengirim lagi.']); exit; }
        $pdo->prepare('INSERT INTO inbox_pesan(pengirim,penerima,teks) VALUES(?,?,?)')->execute([$nomor,$tujuan,$teks]);
        $pdo->exec('COMMIT');
    }
    $s = $pdo->prepare('SELECT p.*, w.nama AS nama_pengirim, v.nama AS nama_penerima FROM inbox_pesan p JOIN warga w ON w.id=CAST(substr(p.pengirim,4) AS INTEGER) JOIN warga v ON v.id=CAST(substr(p.penerima,4) AS INTEGER) WHERE p.pengirim=? OR p.penerima=? ORDER BY p.id DESC LIMIT 50');
    $s->execute([$nomor,$nomor]);
    $pesan = array_reverse($s->fetchAll());
    $s = $pdo->prepare('SELECT COUNT(*) FROM inbox_pesan WHERE penerima=? AND id>?'); $s->execute([$nomor,$dibaca]);
    echo json_encode(['pesan'=>$pesan, 'belum_dibaca'=>(int)$s->fetchColumn(), 'dibaca_sampai'=>$dibaca], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) { http_response_code(400); echo json_encode(['galat'=>$e->getMessage()]); }
catch (Throwable $e) { http_response_code(500); echo json_encode(['galat'=>'Inbox belum bisa dibuka.']); }
