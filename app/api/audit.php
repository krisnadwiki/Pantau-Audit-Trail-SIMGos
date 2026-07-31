<?php
/**
 * API Controller: Audit Trail
 *
 * GET  /api/audit.php?action=dashboard
 * GET  /api/audit.php?action=log&dari=&sampai=&modul=&user=&aksi=&norm=&keyword=&page=
 * GET  /api/audit.php?action=detail&id=
 * GET  /api/audit.php?action=riwayat&objek=&ref=
 * GET  /api/audit.php?action=users&q=
 * GET  /api/audit.php?action=modules
 * GET  /api/audit.php?action=statistic&dari=&sampai=
 * GET  /api/audit.php?action=export&dari=&sampai=&format=csv|excel
 *
 * Semua action memerlukan sesi login.
 *
 * Strategi:
 *   1. Coba panggil REST API SIMGOS di /audit/*
 *   2. Jika gagal (HTTP != 200 atau endpoint tidak ada), gunakan direct DB query
 *   3. Jika DB pun tidak tersedia, kembalikan demo data agar UI tetap bisa digunakan
 */

require_once __DIR__ . '/../config/config.php';

// Semua endpoint audit memerlukan autentikasi
if (!isset($_SESSION['user_data'])) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

// ===========================================================================
// Input Sanitization Helper
// ===========================================================================

/**
 * Sanitasi string dari parameter GET/POST.
 * Strip tag HTML, batasi panjang, dan hapus karakter kontrol.
 */
function sanitize_string(string $value, int $maxLen = 200): string
{
    // Hapus tag HTML dan null bytes
    $value = strip_tags($value);
    $value = str_replace("\0", '', $value);
    // Trim whitespace
    $value = trim($value);
    // Batasi panjang
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

/**
 * Sanitasi integer dari parameter GET/POST.
 * Kembalikan default jika tidak valid atau di luar rentang.
 */
function sanitize_int(mixed $value, int $min = 0, int $max = PHP_INT_MAX, int $default = 0): int
{
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false) return $default;
    return max($min, min($max, $int));
}

/**
 * Sanitasi format tanggal YYYY-MM-DD.
 * Kembalikan string kosong jika format tidak valid.
 */
function sanitize_date(string $value): string
{
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if ($d && $d->format('Y-m-d') === $value) {
            return $value;
        }
    }
    return '';
}

// Sanitasi action parameter
$action = sanitize_string($_GET['action'] ?? '', 50);
// Hanya izinkan karakter alfanumerik dan underscore untuk action
if (!preg_match('/^[a-z_]+$/i', $action)) {
    $action = '';
}


// ===========================================================================
// Helper: Coba REST API dulu, fallback ke DB
// ===========================================================================

/**
 * Coba REST API. Jika gagal, coba DB, jika gagal kembalikan null.
 */
function try_api(string $path, array $params = []): ?array
{
    // Gunakan timeout kecil agar fallback ke DB cepat jika API mati/lambat
    $res = api_get($path, $params, 3, 2);
    if ($res['status'] === 200 && is_array($res['body'])) {
        return $res['body'];
    }
    return null;
}

// ===========================================================================
// Helper: Demo data (agar UI bisa dipreview tanpa API/DB)
// ===========================================================================

function demo_dashboard(): array
{
    return [
        'success' => true,
        'data' => [
            'total_aktivitas'  => 1247,
            'total_user_aktif' => 18,
            'total_login'      => 42,
            'total_create'     => 387,
            'total_update'     => 791,
            'total_delete'     => 69,
            'top_modul' => [
                ['nama' => 'Rekam Medis',    'total' => 412],
                ['nama' => 'Rawat Jalan',    'total' => 298],
                ['nama' => 'Pendaftaran',    'total' => 213],
                ['nama' => 'Rawat Inap',     'total' => 156],
                ['nama' => 'Farmasi',        'total' => 89],
                ['nama' => 'Laboratorium',   'total' => 79],
            ],
            'top_user' => [
                ['nama' => 'dr. Andi Firmansyah',   'total' => 312],
                ['nama' => 'Siti Rahayu, S.Kep',    'total' => 278],
                ['nama' => 'Budi Santoso',           'total' => 195],
                ['nama' => 'Nur Indah Lestari',      'total' => 167],
                ['nama' => 'Reza Pratama',           'total' => 143],
            ],
            'per_jam' => [
                0=>2, 1=>1, 2=>0, 3=>0, 4=>1, 5=>3,
                6=>12, 7=>48, 8=>92, 9=>115, 10=>138, 11=>102,
                12=>56, 13=>88, 14=>121, 15=>109, 16=>95, 17=>72,
                18=>38, 19=>21, 20=>14, 21=>9, 22=>6, 23=>4
            ],
            'per_hari' => [
                'Senin' => 287, 'Selasa' => 312, 'Rabu' => 298,
                'Kamis' => 321, 'Jumat' => 259, 'Sabtu' => 48, 'Minggu' => 15
            ],
            'distribusi' => ['C' => 387, 'U' => 791, 'D' => 69],
        ],
        '_demo' => true,
    ];
}

function demo_log(int $page = 1, int $perPage = 50): array
{
    $moduls = ['Rekam Medis', 'Rawat Jalan', 'Pendaftaran', 'Rawat Inap', 'Farmasi', 'Laboratorium', 'Kasir'];
    $users  = [
        ['id' => 1, 'nama' => 'dr. Andi Firmansyah'],
        ['id' => 2, 'nama' => 'Siti Rahayu, S.Kep'],
        ['id' => 3, 'nama' => 'Budi Santoso'],
        ['id' => 4, 'nama' => 'Nur Indah Lestari'],
    ];
    $aksi_list = ['C', 'U', 'U', 'U', 'D'];
    $aksi_nama = ['C' => 'Dibuat', 'U' => 'Diubah', 'D' => 'Dihapus'];

    $rows = [];
    $base = strtotime('today 08:00');
    for ($i = 0; $i < $perPage; $i++) {
        $u    = $users[array_rand($users)];
        $aksi = $aksi_list[array_rand($aksi_list)];
        $modul = $moduls[array_rand($moduls)];
        $rows[] = [
            'id'         => 10000 + ($page - 1) * $perPage + $i,
            'tanggal'    => date('Y-m-d H:i:s', $base + rand(0, 28800) - ($i * 60)),
            'user_id'    => $u['id'],
            'user_nama'  => $u['nama'],
            'modul'      => $modul,
            'tabel'      => 'medicalrecord.' . strtolower(str_replace(' ', '_', $modul)),
            'aksi'       => $aksi,
            'aksi_nama'  => $aksi_nama[$aksi],
            'objek_id'   => rand(10, 99),
            'ref'        => rand(100000, 999999),
            'ip'         => '192.168.' . rand(1,5) . '.' . rand(10,99),
            'sebelum'    => $aksi !== 'C' ? ['STATUS' => 'AKTIF', 'CATATAN' => 'Kondisi stabil'] : null,
            'sesudah'    => $aksi !== 'D' ? ['STATUS' => 'SELESAI', 'CATATAN' => 'Telah diproses'] : null,
        ];
    }
    return [
        'success' => true,
        'data'    => $rows,
        'meta'    => ['total' => 1247, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 25],
        '_demo'   => true,
    ];
}

function demo_users(): array
{
    return [
        'success' => true,
        'data' => [
            ['id' => 101, 'nama' => 'dr. Andi Firmansyah', 'username' => 'andi.f', 'nip' => '198501152010121001', 'role' => 'Dokter Spesialis', 'total_aktivitas' => 452, 'total_create' => 84, 'total_update' => 368, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-15 minutes'))],
            ['id' => 102, 'nama' => 'Siti Rahayu, S.Kep',  'username' => 'siti.r', 'nip' => '199003202014022003', 'role' => 'Perawat Kepala', 'total_aktivitas' => 380, 'total_create' => 142, 'total_update' => 238, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
            ['id' => 103, 'nama' => 'Budi Santoso',        'username' => 'budi.s', 'nip' => '198811052012011002', 'role' => 'Petugas Rekam Medis', 'total_aktivitas' => 295, 'total_create' => 210, 'total_update' => 85, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
            ['id' => 104, 'nama' => 'Nur Indah Lestari',     'username' => 'nur.i',  'nip' => '199207122018012004', 'role' => 'Petugas Farmasi', 'total_aktivitas' => 210, 'total_create' => 95, 'total_update' => 115, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
            ['id' => 105, 'nama' => 'Reza Pratama',          'username' => 'reza.p', 'nip' => '199404182019031005', 'role' => 'Administrator Sistem', 'total_aktivitas' => 165, 'total_create' => 30, 'total_update' => 135, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ['id' => 106, 'nama' => 'Dewi Anggraini, Amd.PK','username' => 'dewi.a', 'nip' => '199509252020122006', 'role' => 'Petugas Registration', 'total_aktivitas' => 128, 'total_create' => 110, 'total_update' => 18, 'total_delete' => 0, 'login_terakhir' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ],
        '_demo' => true,
    ];
}

function demo_modules(): array
{
    return [
        'success' => true,
        'data' => [
            ['id' => 1, 'nama' => 'Pendaftaran',  'schema' => 'pendaftaran',   'total' => 213, 'create' => 45,  'update' => 158, 'delete' => 10],
            ['id' => 2, 'nama' => 'Rawat Jalan',  'schema' => 'layanan',       'total' => 298, 'create' => 78,  'update' => 204, 'delete' => 16],
            ['id' => 3, 'nama' => 'Rawat Inap',   'schema' => 'layanan',       'total' => 156, 'create' => 34,  'update' => 112, 'delete' => 10],
            ['id' => 4, 'nama' => 'IGD',          'schema' => 'layanan',       'total' => 67,  'create' => 21,  'update' => 41,  'delete' => 5],
            ['id' => 5, 'nama' => 'Farmasi',      'schema' => 'pembayaran',    'total' => 89,  'create' => 12,  'update' => 72,  'delete' => 5],
            ['id' => 6, 'nama' => 'Laboratorium', 'schema' => 'layanan',       'total' => 79,  'create' => 31,  'update' => 42,  'delete' => 6],
            ['id' => 7, 'nama' => 'Radiologi',    'schema' => 'layanan',       'total' => 54,  'create' => 18,  'update' => 33,  'delete' => 3],
            ['id' => 8, 'nama' => 'Rekam Medis',  'schema' => 'medicalrecord', 'total' => 412, 'create' => 102, 'update' => 287, 'delete' => 23],
            ['id' => 9, 'nama' => 'Kasir',        'schema' => 'pembayaran',    'total' => 44,  'create' => 11,  'update' => 28,  'delete' => 5],
        ],
        '_demo' => true,
    ];
}

function demo_statistic(string $dari, string $sampai): array
{
    return [
        'success' => true,
        'data' => [
            'jam_tersibuk'  => '09:00 - 10:00',
            'hari_tersibuk' => 'Kamis',
            'aksi_terbanyak' => 'Diubah',
            'modul_teraktif' => 'Rekam Medis',
            'rata_per_hari'  => 178,
            'puncak_harian'  => 321,
        ],
        '_demo' => true,
    ];
}

// ===========================================================================
// DB Helpers (fallback langsung ke database SIMGOS)
// ===========================================================================

function db_dashboard(): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $today    = date('ymd');
        $id_awal  = $today . str_repeat('0', 9);
        $id_akhir = $today . str_repeat('9', 9);

        // Ringkasan aksi
        $st = $pdo->prepare("SELECT AKSI, COUNT(*) AS N FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b GROUP BY AKSI");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $ringkas = ['C' => 0, 'U' => 0, 'D' => 0];
        foreach ($st->fetchAll() as $r) {
            if (isset($ringkas[$r['AKSI']])) $ringkas[$r['AKSI']] = (int)$r['N'];
        }
        $total = array_sum($ringkas);

        // User aktif
        $st = $pdo->prepare("SELECT COUNT(DISTINCT PENGGUNA) FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $totalUser = (int)$st->fetchColumn();

        // Top modul
        $st = $pdo->prepare("SELECT o.DESKRIPSI AS nama, o.TABEL AS tabel, l.OBJEK AS objek_id, COUNT(*) AS total
                              FROM logs.pengguna_akses_log l
                              LEFT JOIN aplikasi.objek o ON o.ID = l.OBJEK
                              WHERE l.ID BETWEEN :a AND :b
                              GROUP BY l.OBJEK ORDER BY total DESC LIMIT 6");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $topModul = $st->fetchAll();

        // Top user
        $st = $pdo->prepare("SELECT TRIM(CONCAT(COALESCE(g.GELAR_DEPAN,''),' ',COALESCE(g.NAMA,''),' ',
                                                 COALESCE(g.GELAR_BELAKANG,''))) AS nama,
                                     COUNT(*) AS total
                              FROM logs.pengguna_akses_log l
                              LEFT JOIN aplikasi.pengguna u ON u.ID = l.PENGGUNA
                              LEFT JOIN master.pegawai    g ON g.NIP = u.NIP
                              WHERE l.ID BETWEEN :a AND :b
                              GROUP BY l.PENGGUNA ORDER BY total DESC LIMIT 5");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $topUser = $st->fetchAll();

        // Per jam
        $st = $pdo->prepare("SELECT HOUR(TANGGAL) AS jam, COUNT(*) AS n
                              FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b
                              GROUP BY jam ORDER BY jam");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $perJam = array_fill(0, 24, 0);
        foreach ($st->fetchAll() as $r) {
            $perJam[(int)$r['jam']] = (int)$r['n'];
        }

        return [
            'success' => true,
            'data' => [
                'total_aktivitas'  => $total,
                'total_user_aktif' => $totalUser,
                'total_login'      => 0,
                'total_create'     => $ringkas['C'],
                'total_update'     => $ringkas['U'],
                'total_delete'     => $ringkas['D'],
                'top_modul'        => array_map(function($m) {
                    $nama = !empty($m['nama']) ? $m['nama']
                          : (!empty($m['tabel']) ? str_replace('medicalrecord.', '', $m['tabel'])
                          : ('Objek ' . $m['objek_id']));
                    return [
                        'nama'  => $nama,
                        'total' => (int)$m['total']
                    ];
                }, $topModul),
                'top_user'         => array_map(fn($u) => ['nama' => trim($u['nama']) ?: 'Unknown', 'total' => (int)$u['total']], $topUser),
                'per_jam'          => $perJam,
                'distribusi'       => ['C' => $ringkas['C'], 'U' => $ringkas['U'], 'D' => $ringkas['D']],
            ],
        ];
    } catch (Exception $e) {
        return null;
    }
}

function db_log(array $filters): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $dari   = $filters['dari']   ?? date('Y-m-d');
        $sampai = $filters['sampai'] ?? date('Y-m-d');
        $page   = max(1, (int)($filters['page'] ?? 1));
        $limit  = max(10, min(200, (int)($filters['per_page'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $id_awal  = date('ymd', strtotime($dari))   . str_repeat('0', 9);
        $id_akhir = date('ymd', strtotime($sampai)) . str_repeat('9', 9);

        $where  = ['l.ID BETWEEN :id_awal AND :id_akhir'];
        $params = ['id_awal' => $id_awal, 'id_akhir' => $id_akhir];

        if (!empty($filters['modul'])) {
            $where[]         = 'l.OBJEK = :objek';
            $params['objek'] = $filters['modul'];
        }
        if (!empty($filters['aksi'])) {
            $where[]        = 'l.AKSI = :aksi';
            $params['aksi'] = $filters['aksi'];
        }
        if (!empty($filters['user'])) {
            $where[] = 'l.PENGGUNA IN (SELECT u2.ID FROM aplikasi.pengguna u2
                                        LEFT JOIN master.pegawai g2 ON g2.NIP = u2.NIP
                                        WHERE g2.NAMA LIKE :unama OR u2.ID = :uid)';
            $params['unama'] = '%' . $filters['user'] . '%';
            $params['uid']   = $filters['user'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(l.REF LIKE :kw OR l.SEBELUM LIKE :kw2 OR l.SESUDAH LIKE :kw3)';
            $params['kw']  = '%' . $filters['keyword'] . '%';
            $params['kw2'] = '%' . $filters['keyword'] . '%';
            $params['kw3'] = '%' . $filters['keyword'] . '%';
        }

        $klausa = implode(' AND ', $where);

        $stTotal = $pdo->prepare("SELECT COUNT(*) FROM logs.pengguna_akses_log l WHERE $klausa");
        $stTotal->execute($params);
        $total = (int)$stTotal->fetchColumn();

        $st = $pdo->prepare("SELECT l.ID, l.TANGGAL, l.PENGGUNA, l.AKSI, l.OBJEK, l.REF,
                                     l.SEBELUM, l.SESUDAH,
                                     o.TABEL, o.DESKRIPSI,
                                     TRIM(CONCAT(COALESCE(g.GELAR_DEPAN,''),' ',COALESCE(g.NAMA,''),' ',
                                                 COALESCE(g.GELAR_BELAKANG,''))) AS user_nama
                              FROM logs.pengguna_akses_log l
                              LEFT JOIN aplikasi.objek    o ON o.ID  = l.OBJEK
                              LEFT JOIN aplikasi.pengguna u ON u.ID  = l.PENGGUNA
                              LEFT JOIN master.pegawai    g ON g.NIP = u.NIP
                              WHERE $klausa
                              ORDER BY l.ID DESC
                              LIMIT $limit OFFSET $offset");
        $st->execute($params);
        $rows = $st->fetchAll();

        $aksi_nama = ['C' => 'Dibuat', 'U' => 'Diubah', 'D' => 'Dihapus'];
        $data = [];
        foreach ($rows as $r) {
            $modul = !empty($r['DESKRIPSI']) ? $r['DESKRIPSI']
                   : str_replace('medicalrecord.', '', $r['TABEL'] ?? '');
            $data[] = [
                'id'        => $r['ID'],
                'tanggal'   => $r['TANGGAL'],
                'user_id'   => $r['PENGGUNA'],
                'user_nama' => trim($r['user_nama']) ?: ('ID ' . $r['PENGGUNA']),
                'modul'     => $modul,
                'tabel'     => $r['TABEL'],
                'aksi'      => $r['AKSI'],
                'aksi_nama' => $aksi_nama[$r['AKSI']] ?? $r['AKSI'],
                'objek_id'  => $r['OBJEK'],
                'ref'       => $r['REF'],
                'ip'        => '',
                'sebelum'   => !empty($r['SEBELUM']) ? json_decode($r['SEBELUM'], true) : null,
                'sesudah'   => !empty($r['SESUDAH']) ? json_decode($r['SESUDAH'], true) : null,
            ];
        }

        return [
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $limit,
                'total_pages' => (int)ceil($total / $limit),
            ],
        ];
    } catch (Exception $e) {
        return null;
    }
}

function pecah_tabel($penuh) {
    $titik = strpos((string)$penuh, '.');
    if ($titik === false) return null;
    $schema = substr($penuh, 0, $titik);
    $tabel  = substr($penuh, $titik + 1);
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $schema)) return null;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tabel))     return null;
    return [$schema, $tabel];
}

function kolom_kunci($pdo, $schema, $tabel) {
    $q = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.STATISTICS
                         WHERE TABLE_SCHEMA=:s AND TABLE_NAME=:t
                           AND INDEX_NAME='PRIMARY' AND SEQ_IN_INDEX=1");
    $q->execute(['s' => $schema, 't' => $tabel]);
    return $q->fetchColumn();
}

function kolom_penghubung($pdo, $schema, $tabel) {
    $q = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA=:s AND TABLE_NAME=:t
                           AND COLUMN_NAME IN ('KUNJUNGAN','NOPEN','PENDAFTARAN')");
    $q->execute(['s' => $schema, 't' => $tabel]);
    $ada = $q->fetchAll(PDO::FETCH_COLUMN);
    foreach (['KUNJUNGAN', 'NOPEN', 'PENDAFTARAN'] as $k) {
        if (in_array($k, $ada)) return $k;
    }
    return null;
}

function induk_penghubung($pdo, $schema, $tabel) {
    $q = $pdo->prepare("SELECT c.COLUMN_NAME, t2.TABLE_SCHEMA AS S2, t2.TABLE_NAME AS T2
                          FROM information_schema.COLUMNS c
                          JOIN information_schema.TABLES t2
                            ON t2.TABLE_NAME = LOWER(c.COLUMN_NAME)
                           AND t2.TABLE_TYPE = 'BASE TABLE'
                         WHERE c.TABLE_SCHEMA = :s AND c.TABLE_NAME = :t
                         LIMIT 8");
    $q->execute(['s' => $schema, 't' => $tabel]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $penghubung = kolom_penghubung($pdo, $r['S2'], $r['T2']);
        if ($penghubung) {
            $pk = kolom_kunci($pdo, $r['S2'], $r['T2']);
            if ($pk) {
                return ['kolom' => $r['COLUMN_NAME'], 'schema' => $r['S2'],
                        'tabel' => $r['T2'], 'penghubung' => $penghubung, 'pk' => $pk];
            }
        }
    }
    return null;
}

function peta_kode() {
    return [
        'layanan.hasil_lab.PARAMETER_TINDAKAN' =>
            ['master.parameter_tindakan_lab', 'ID', 'PARAMETER'],
    ];
}

function konteks_record($pdo, $tabelPenuh, $refId) {
    $kosong = ['konteks' => null, 'pesan' => ''];
    $pecah = pecah_tabel($tabelPenuh);
    if (!$pecah) return $kosong;
    list($schema, $tabel) = $pecah;

    try {
        $pk = kolom_kunci($pdo, $schema, $tabel);
        if (!$pk) {
            return ['konteks' => null,
                    'pesan'   => 'Tabel ' . $tabel . ' tidak punya kunci utama tunggal, jadi record-nya tidak bisa ditelusuri.'];
        }

        $kolom  = kolom_penghubung($pdo, $schema, $tabel);
        $dari   = "FROM `$schema`.`$tabel` x";
        $sumber = 'x';
        $lewat  = '';

        if (!$kolom) {
            $induk = induk_penghubung($pdo, $schema, $tabel);
            if ($induk) {
                $dari .= " JOIN `{$induk['schema']}`.`{$induk['tabel']}` y"
                       . " ON y.`{$induk['pk']}` = x.`{$induk['kolom']}`";
                $sumber = 'y';
                $kolom  = $induk['penghubung'];
                $lewat  = $induk['tabel'];
            }
        }

        if (!$kolom) {
            return ['konteks' => null,
                    'pesan'   => 'Tabel ' . $tabel . ' tidak menyimpan nomor kunjungan, nopen, maupun pendaftaran, dan tidak ditemukan tabel induk yang menyimpannya.'];
        }

        if ($kolom === 'KUNJUNGAN') {
            $sql = "SELECT k.NOMOR AS KUNJUNGAN, k.MASUK, k.NOPEN, p.NORM,
                           r.DESKRIPSI AS RUANGAN
                      $dari
                      JOIN pendaftaran.kunjungan   k ON k.NOMOR = $sumber.KUNJUNGAN
                      JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
                 LEFT JOIN master.ruangan          r ON r.ID    = k.RUANGAN
                     WHERE x.`$pk` = :ref LIMIT 1";
        } else {
            $sql = "SELECT NULL AS KUNJUNGAN, NULL AS MASUK, p.NOMOR AS NOPEN,
                           p.NORM, NULL AS RUANGAN
                      $dari
                      JOIN pendaftaran.pendaftaran p ON p.NOMOR = $sumber.`$kolom`
                     WHERE x.`$pk` = :ref LIMIT 1";
        }

        $q = $pdo->prepare($sql);
        $q->execute(['ref' => $refId]);
        $ktx = $q->fetch(PDO::FETCH_ASSOC);

        if (!$ktx) {
            return ['konteks' => null,
                    'pesan'   => 'Data induk untuk record ini tidak ditemukan.'];
        }

        $konteks = [
            'norm'      => $ktx['NORM'],
            'kunjungan' => $ktx['KUNJUNGAN'],
            'nopen'     => $ktx['NOPEN'],
            'ruangan'   => $ktx['RUANGAN'],
            'masuk'     => !empty($ktx['MASUK']) ? date('d/m/Y H:i', strtotime($ktx['MASUK'])) : '',
            'pasien'    => '',
            'lewat'     => $lewat,
            'rincian'   => [],
        ];

function mask_nama($nama) {
    if (empty($nama)) return '';
    $words = preg_split('/\s+/', trim($nama));
    $masked = array_map(function($w) {
        if (mb_strlen($w) <= 3) return $w . '**';
        return mb_substr($w, 0, 3) . '**';
    }, $words);
    return implode(' ', $masked);
}

        try {
            $n = $pdo->prepare("SELECT NAMA FROM master.pasien WHERE NORM = :n LIMIT 1");
            $n->execute(['n' => $ktx['NORM']]);
            $rawNama = (string) $n->fetchColumn();
            $konteks['pasien'] = mask_nama($rawNama);
        } catch (Exception $e) { }

        foreach (peta_kode() as $jalur => $rujuk) {
            $bagian = explode('.', $jalur);
            if (count($bagian) !== 3) continue;
            if ($bagian[0] !== $schema || $bagian[1] !== $tabel) continue;

            $kolomKode = $bagian[2];
            $tujuan = pecah_tabel($rujuk[0]);
            if (!$tujuan) continue;

            try {
                $r = $pdo->prepare("SELECT ref.`{$rujuk[2]}` AS NAMA
                                      FROM `$schema`.`$tabel` x
                                      JOIN `{$tujuan[0]}`.`{$tujuan[1]}` ref
                                        ON ref.`{$rujuk[1]}` = x.`$kolomKode`
                                     WHERE x.`$pk` = :ref LIMIT 1");
                $r->execute(['ref' => $refId]);
                $nama = (string) $r->fetchColumn();
                if ($nama !== '') {
                    $konteks['rincian'][] = [
                        'label' => ucwords(strtolower(str_replace('_', ' ', $kolomKode))),
                        'nilai' => $nama
                    ];
                }
            } catch (Exception $e) { }
        }

        return ['konteks' => $konteks, 'pesan' => ''];
    } catch (Exception $e) {
        return $kosong;
    }
}

function db_riwayat(string $objekId, string $refId): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $konteks = null;
        $pesan   = '';

        try {
            $qObj = $pdo->prepare("SELECT TABEL FROM aplikasi.objek WHERE ID = :o LIMIT 1");
            $qObj->execute(['o' => $objekId]);
            $tabelPenuh = (string) $qObj->fetchColumn();

            if ($tabelPenuh) {
                $hasil = konteks_record($pdo, $tabelPenuh, $refId);
                $konteks = $hasil['konteks'];
                if (!empty($hasil['pesan'])) $pesan = $hasil['pesan'];
            }
        } catch (Exception $e) {}

        $st = $pdo->prepare("SELECT l.ID, l.TANGGAL, l.AKSI, l.SEBELUM, l.SESUDAH,
                                    TRIM(CONCAT(COALESCE(g.GELAR_DEPAN,''),' ',COALESCE(g.NAMA,''),' ',
                                                COALESCE(g.GELAR_BELAKANG,''))) AS nama
                             FROM logs.pengguna_akses_log l
                             LEFT JOIN aplikasi.pengguna u ON u.ID  = l.PENGGUNA
                             LEFT JOIN master.pegawai    g ON g.NIP = u.NIP
                             WHERE l.OBJEK = :o AND l.REF = :r
                             ORDER BY l.ID ASC LIMIT 200");
        $st->execute(['o' => $objekId, 'r' => $refId]);

        $aksi_nama = ['C' => 'Dibuat', 'U' => 'Diubah', 'D' => 'Dihapus'];
        $riwayat = [];
        foreach ($st->fetchAll() as $b) {
            $riwayat[] = [
                'id'        => $b['ID'],
                'aksi'      => $b['AKSI'],
                'aksi_nama' => $aksi_nama[$b['AKSI']] ?? $b['AKSI'],
                'waktu'     => date('d/m/Y H:i:s', strtotime($b['TANGGAL'])),
                'oleh'      => trim($b['nama']) ?: ('ID ' . $b['PENGGUNA']),
                'sebelum'   => !empty($b['SEBELUM']) ? json_decode($b['SEBELUM'], true) : null,
                'sesudah'   => !empty($b['SESUDAH']) ? json_decode($b['SESUDAH'], true) : null,
            ];
        }

        return [
            'success' => true,
            'konteks' => $konteks,
            'pesan'   => $pesan,
            'data'    => $riwayat
        ];
    } catch (Exception $e) {
        return null;
    }
}

function db_modules(): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $today    = date('ymd');
        $id_awal  = $today . str_repeat('0', 9);
        $id_akhir = $today . str_repeat('9', 9);

        $st = $pdo->prepare("SELECT o.ID, o.DESKRIPSI AS nama, o.TABEL,
                                    COALESCE(t.creates, 0) AS creates,
                                    COALESCE(t.updates, 0) AS updates,
                                    COALESCE(t.deletes, 0) AS deletes,
                                    COALESCE(t.total, 0) AS total
                             FROM aplikasi.objek o
                             LEFT JOIN (
                                 SELECT OBJEK,
                                        SUM(CASE WHEN AKSI='C' THEN 1 ELSE 0 END) AS creates,
                                        SUM(CASE WHEN AKSI='U' THEN 1 ELSE 0 END) AS updates,
                                        SUM(CASE WHEN AKSI='D' THEN 1 ELSE 0 END) AS deletes,
                                        COUNT(*) AS total
                                 FROM logs.pengguna_akses_log
                                 WHERE ID BETWEEN :a AND :b
                                 GROUP BY OBJEK
                             ) t ON t.OBJEK = o.ID
                             ORDER BY total DESC");
        $st->execute(['a' => $id_awal, 'b' => $id_akhir]);
        $rows = $st->fetchAll();

        $data = [];
        foreach ($rows as $r) {
            $schema = strstr($r['TABEL'] ?? '', '.', true) ?: '';
            $data[] = [
                'id'     => $r['ID'],
                'nama'   => $r['nama'] ?: (!empty($r['TABEL']) ? str_replace('medicalrecord.', '', $r['TABEL']) : ('Objek ' . $r['ID'])),
                'schema' => $schema,
                'total'  => (int)$r['total'],
                'create' => (int)$r['creates'],
                'update' => (int)$r['updates'],
                'delete' => (int)$r['deletes'],
            ];
        }

        return ['success' => true, 'data' => $data];
    } catch (Exception $e) {
        return null;
    }
}

function db_users(array $filters): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $dari   = $filters['dari']   ?? date('Y-m-d', strtotime('-30 days'));
        $sampai = $filters['sampai'] ?? date('Y-m-d');
        $q      = trim($filters['q'] ?? '');
        $sort   = $filters['sort']   ?? 'total_desc';

        $id_awal  = date('ymd', strtotime($dari))   . str_repeat('0', 9);
        $id_akhir = date('ymd', strtotime($sampai)) . str_repeat('9', 9);

        $params = ['a' => $id_awal, 'b' => $id_akhir];
        $where  = [];

        if (!empty($q)) {
            $where[]  = '(g.NAMA LIKE :q1 OR u.LOGIN LIKE :q2 OR g.NIP LIKE :q3 OR u.ID = :q4)';
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
            $params['q4'] = $q;
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        switch ($sort) {
            case 'nama_asc':  $orderBy = 'nama ASC'; break;
            case 'latest':    $orderBy = 'aktivitas_terakhir DESC'; break;
            case 'total_asc': $orderBy = 'total_aktivitas ASC'; break;
            default:          $orderBy = 'total_aktivitas DESC, nama ASC'; break;
        }

        $sql = "SELECT u.ID AS id,
                    u.LOGIN AS username,
                    u.JENIS AS jenis,
                    r.DESKRIPSI AS role,
                    TRIM(CONCAT(COALESCE(g.GELAR_DEPAN,''),' ',COALESCE(g.NAMA,''),' ',COALESCE(g.GELAR_BELAKANG,''))) AS nama,
                    g.NIP AS nip,
                    COALESCE(t.total_aktivitas, 0) AS total_aktivitas,
                    COALESCE(t.total_create, 0) AS total_create,
                    COALESCE(t.total_update, 0) AS total_update,
                    COALESCE(t.total_delete, 0) AS total_delete,
                    t.aktivitas_terakhir,
                    pl.login_terakhir
                FROM aplikasi.pengguna u
                LEFT JOIN master.pegawai g
                    ON g.NIP = u.NIP
                LEFT JOIN master.referensi r
                    ON r.ID = g.PROFESI
                AND r.JENIS = 36
                LEFT JOIN (
                    SELECT PENGGUNA,
                        COUNT(*) AS total_aktivitas,
                        SUM(CASE WHEN AKSI='C' THEN 1 ELSE 0 END) AS total_create,
                        SUM(CASE WHEN AKSI='U' THEN 1 ELSE 0 END) AS total_update,
                        SUM(CASE WHEN AKSI='D' THEN 1 ELSE 0 END) AS total_delete,
                        MAX(TANGGAL) AS aktivitas_terakhir
                    FROM logs.pengguna_akses_log
                    WHERE ID BETWEEN :a AND :b
                    GROUP BY PENGGUNA
                ) t ON t.PENGGUNA = u.ID
                LEFT JOIN (
                    SELECT PENGGUNA,
                        MAX(TANGGAL_AKSES) AS login_terakhir
                    FROM aplikasi.pengguna_log
                    GROUP BY PENGGUNA
                ) pl ON pl.PENGGUNA = u.ID
                $whereClause
                ORDER BY $orderBy";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();

        $data = [];
        foreach ($rows as $r) {
            $nama = trim($r['nama']) ?: ($r['username'] ?: ('User #' . $r['id']));
            $data[] = [
                'id'                 => $r['id'],
                'nama'               => $nama,
                'username'           => $r['username'],
                'nip'                => $r['nip'] ?: '—',
                'role'               => trim($r['role'] ?? '') ?: 'Pengguna',
                'total_aktivitas'    => (int)$r['total_aktivitas'],
                'total_create'       => (int)$r['total_create'],
                'total_update'       => (int)$r['total_update'],
                'total_delete'       => (int)$r['total_delete'],
                'aktivitas_terakhir' => $r['aktivitas_terakhir'],
                'login_terakhir'     => $r['login_terakhir'] ?: $r['aktivitas_terakhir'],
            ];
        }

        return ['success' => true, 'data' => $data];
    } catch (Exception $e) {
        return null;
    }
}

// ===========================================================================
// DB Helpers: Login Monitor (pengguna_log)
// ===========================================================================

function db_login_stat(string $dari, string $sampai): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $params = ['dari' => $dari . ' 00:00:00', 'sampai' => $sampai . ' 23:59:59'];

        // Total login & user unik
        $st = $pdo->prepare("SELECT COUNT(*) AS total, COUNT(DISTINCT PENGGUNA) AS user_unik,
                                     COUNT(DISTINCT LOKASI_AKSES) AS ip_unik
                              FROM aplikasi.pengguna_log
                              WHERE TANGGAL_AKSES BETWEEN :dari AND :sampai");
        $st->execute($params);
        $ring = $st->fetch();

        // Login per jam
        $st = $pdo->prepare("SELECT HOUR(TANGGAL_AKSES) AS jam, COUNT(*) AS n
                              FROM aplikasi.pengguna_log
                              WHERE TANGGAL_AKSES BETWEEN :dari AND :sampai
                              GROUP BY jam ORDER BY jam");
        $st->execute($params);
        $perJam = array_fill(0, 24, 0);
        foreach ($st->fetchAll() as $r) {
            $perJam[(int)$r['jam']] = (int)$r['n'];
        }

        // Top user login
        $st = $pdo->prepare("SELECT p.NAMA AS nama, p.LOGIN AS login, COUNT(*) AS total
                              FROM aplikasi.pengguna_log l
                              LEFT JOIN aplikasi.pengguna p ON p.ID = l.PENGGUNA
                              WHERE l.TANGGAL_AKSES BETWEEN :dari AND :sampai
                              GROUP BY l.PENGGUNA ORDER BY total DESC LIMIT 10");
        $st->execute($params);
        $topUser = $st->fetchAll();

        // Jam puncak
        $jamPeak = array_search(max($perJam), $perJam);
        $maxVal  = $perJam[$jamPeak] ?? 0;
        $jamPuncak = $maxVal > 0 ? sprintf('%02d:00 - %02d:00', $jamPeak, ($jamPeak + 1) % 24) : '—';

        return [
            'success' => true,
            'data' => [
                'total_login'   => (int)$ring['total'],
                'user_unik'     => (int)$ring['user_unik'],
                'ip_unik'       => (int)$ring['ip_unik'],
                'jam_puncak'    => $jamPuncak,
                'per_jam'       => $perJam,
                'top_user'      => array_map(fn($u) => [
                    'nama'  => $u['nama'] ?: $u['login'] ?: 'Unknown',
                    'login' => $u['login'],
                    'total' => (int)$u['total'],
                ], $topUser),
            ],
        ];
    } catch (Exception $e) {
        return null;
    }
}

function db_login_log(array $filters): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $dari   = $filters['dari']   ?? date('Y-m-d');
        $sampai = $filters['sampai'] ?? date('Y-m-d');
        $page   = max(1, (int)($filters['page'] ?? 1));
        $limit  = max(10, min(200, (int)($filters['per_page'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $where  = ['l.TANGGAL_AKSES BETWEEN :dari AND :sampai'];
        $params = ['dari' => $dari . ' 00:00:00', 'sampai' => $sampai . ' 23:59:59'];

        if (!empty($filters['user'])) {
            $where[]          = '(p.NAMA LIKE :uq OR p.LOGIN LIKE :uq2 OR l.PENGGUNA = :uid)';
            $params['uq']     = '%' . $filters['user'] . '%';
            $params['uq2']    = '%' . $filters['user'] . '%';
            $params['uid']    = $filters['user'];
        }
        if (!empty($filters['ip'])) {
            $where[]       = 'l.LOKASI_AKSES LIKE :ip';
            $params['ip']  = '%' . $filters['ip'] . '%';
        }

        $klausa = implode(' AND ', $where);

        $stTotal = $pdo->prepare("SELECT COUNT(*) FROM aplikasi.pengguna_log l
                                   LEFT JOIN aplikasi.pengguna p ON p.ID = l.PENGGUNA
                                   WHERE $klausa");
        $stTotal->execute($params);
        $total = (int)$stTotal->fetchColumn();

        $st = $pdo->prepare("SELECT l.ID, l.TANGGAL_AKSES, l.LOKASI_AKSES, l.TUJUAN_AKSES, l.AGENT,
                                     p.NAMA AS nama, p.LOGIN AS login, p.JENIS AS jenis
                              FROM aplikasi.pengguna_log l
                              LEFT JOIN aplikasi.pengguna p ON p.ID = l.PENGGUNA
                              WHERE $klausa
                              ORDER BY l.ID DESC
                              LIMIT $limit OFFSET $offset");
        $st->execute($params);
        $rows = $st->fetchAll();

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id'       => $r['ID'],
                'tanggal'  => $r['TANGGAL_AKSES'],
                'nama'     => $r['nama'] ?: 'ID ' . $r['login'],
                'login'    => $r['login'],
                'ip_asal'  => $r['LOKASI_AKSES'],
                'tujuan'   => $r['TUJUAN_AKSES'],
                'agent'    => $r['AGENT'],
                'jenis'    => (int)$r['jenis'],
            ];
        }

        return [
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $limit,
                'total_pages' => (int)ceil($total / $limit),
            ],
        ];
    } catch (Exception $e) {
        return null;
    }
}

function db_analytic_range(string $dari, string $sampai): ?array
{
    $pdo = get_db();
    if (!$pdo) return null;

    try {
        $dariTs   = date('ymd', strtotime($dari))   . str_repeat('0', 9);
        $sampaiTs = date('ymd', strtotime($sampai)) . str_repeat('9', 9);
        $params   = ['a' => $dariTs, 'b' => $sampaiTs];

        // Ringkasan aksi
        $st = $pdo->prepare("SELECT AKSI, COUNT(*) AS N FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b GROUP BY AKSI");
        $st->execute($params);
        $ringkas = ['C' => 0, 'U' => 0, 'D' => 0];
        foreach ($st->fetchAll() as $r) {
            if (isset($ringkas[$r['AKSI']])) $ringkas[$r['AKSI']] = (int)$r['N'];
        }
        $total = array_sum($ringkas);

        // User aktif
        $st = $pdo->prepare("SELECT COUNT(DISTINCT PENGGUNA) FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b");
        $st->execute($params);
        $totalUser = (int)$st->fetchColumn();

        // Top 10 user
        $st = $pdo->prepare("SELECT TRIM(CONCAT(COALESCE(g.GELAR_DEPAN,''),' ',COALESCE(g.NAMA,''),' ',
                                               COALESCE(g.GELAR_BELAKANG,''))) AS nama,
                                    COUNT(*) AS total
                              FROM logs.pengguna_akses_log l
                              LEFT JOIN aplikasi.pengguna u ON u.ID = l.PENGGUNA
                              LEFT JOIN master.pegawai    g ON g.NIP = u.NIP
                              WHERE l.ID BETWEEN :a AND :b
                              GROUP BY l.PENGGUNA ORDER BY total DESC LIMIT 10");
        $st->execute($params);
        $topUser = $st->fetchAll();

        // Top 10 modul
        $st = $pdo->prepare("SELECT o.DESKRIPSI AS nama, o.TABEL AS tabel, l.OBJEK AS objek_id, COUNT(*) AS total
                              FROM logs.pengguna_akses_log l
                              LEFT JOIN aplikasi.objek o ON o.ID = l.OBJEK
                              WHERE l.ID BETWEEN :a AND :b
                              GROUP BY l.OBJEK ORDER BY total DESC LIMIT 10");
        $st->execute($params);
        $topModul = $st->fetchAll();

        // Per jam (sum selama rentang)
        $st = $pdo->prepare("SELECT HOUR(TANGGAL) AS jam, COUNT(*) AS n
                              FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b
                              GROUP BY jam ORDER BY jam");
        $st->execute($params);
        $perJam = array_fill(0, 24, 0);
        foreach ($st->fetchAll() as $r) {
            $perJam[(int)$r['jam']] = (int)$r['n'];
        }

        // Hari tersibuk
        $st = $pdo->prepare("SELECT DAYOFWEEK(TANGGAL) AS dow, COUNT(*) AS n
                              FROM logs.pengguna_akses_log
                              WHERE ID BETWEEN :a AND :b
                              GROUP BY dow ORDER BY n DESC LIMIT 1");
        $st->execute($params);
        $dowRow = $st->fetch();
        $hariMap = [1=>'Minggu',2=>'Senin',3=>'Selasa',4=>'Rabu',5=>'Kamis',6=>'Jumat',7=>'Sabtu'];
        $hariTersibuk = $dowRow ? ($hariMap[(int)$dowRow['dow']] ?? '—') : '—';

        // Jam tersibuk
        $jamPeak = array_search(max($perJam), $perJam);
        $jamTersibuk = sprintf('%02d:00 - %02d:00', $jamPeak, $jamPeak + 1);

        // Rata per hari
        $selisihHari = max(1, (int)((strtotime($sampai) - strtotime($dari)) / 86400) + 1);
        $rataPerHari = round($total / $selisihHari);

        // Modul teraktif
        $modulTeraktif = !empty($topModul) ? ($topModul[0]['nama'] ?: $topModul[0]['tabel']) : '—';

        // Aksi terbanyak
        arsort($ringkas);
        $aksiMap     = ['C' => 'Dibuat', 'U' => 'Diubah', 'D' => 'Dihapus'];
        $aksiTerbanyak = $aksiMap[array_key_first($ringkas)] ?? '—';

        return [
            'success' => true,
            'data' => [
                'total_aktivitas'  => $total,
                'total_user_aktif' => $totalUser,
                'total_create'     => $ringkas['C'],
                'total_update'     => $ringkas['U'],
                'total_delete'     => $ringkas['D'],
                'top_user'         => array_map(fn($u) => ['nama' => trim($u['nama']) ?: 'Unknown', 'total' => (int)$u['total']], $topUser),
                'top_modul'        => array_map(function($m) {
                    $nama = !empty($m['nama']) ? $m['nama']
                          : (!empty($m['tabel']) ? str_replace('medicalrecord.', '', $m['tabel'])
                          : ('Objek ' . $m['objek_id']));
                    return ['nama' => $nama, 'total' => (int)$m['total']];
                }, $topModul),
                'per_jam'          => $perJam,
                'distribusi'       => ['C' => $ringkas['C'], 'U' => $ringkas['U'], 'D' => $ringkas['D']],
                'jam_tersibuk'     => $jamTersibuk,
                'hari_tersibuk'    => $hariTersibuk,
                'aksi_terbanyak'   => $aksiTerbanyak,
                'modul_teraktif'   => $modulTeraktif,
                'rata_per_hari'    => $rataPerHari,
            ],
        ];
    } catch (Exception $e) {
        return null;
    }
}

// Demo fallbacks login
function demo_login_stat(): array
{
    $perJam = [
        0=>1, 1=>0, 2=>0, 3=>0, 4=>0, 5=>2,
        6=>8, 7=>22, 8=>45, 9=>38, 10=>29, 11=>21,
        12=>12, 13=>18, 14=>31, 15=>27, 16=>19, 17=>14,
        18=>7, 19=>4, 20=>3, 21=>2, 22=>1, 23=>1
    ];
    $jamPeak = array_search(max($perJam), $perJam);
    $jamPuncak = sprintf('%02d:00 - %02d:00', $jamPeak, ($jamPeak + 1) % 24);

    return [
        'success' => true,
        'data' => [
            'total_login' => 306,
            'user_unik'   => 24,
            'ip_unik'     => 18,
            'jam_puncak'  => $jamPuncak,
            'per_jam'     => $perJam,
            'top_user'    => [
                ['nama' => 'dr. Andi Firmansyah',   'login' => 'andi.f',  'total' => 42],
                ['nama' => 'Siti Rahayu, S.Kep',    'login' => 'siti.r',  'total' => 38],
                ['nama' => 'Budi Santoso',           'login' => 'budi.s',  'total' => 31],
                ['nama' => 'Nur Indah Lestari',      'login' => 'nur.i',   'total' => 27],
                ['nama' => 'Reza Pratama',           'login' => 'reza.p',  'total' => 22],
            ],
        ],
        '_demo' => true,
    ];
}

function demo_login_log(int $page = 1, int $perPage = 50): array
{
    $users = [
        ['nama' => 'dr. Andi Firmansyah', 'login' => 'andi.f'],
        ['nama' => 'Siti Rahayu, S.Kep',  'login' => 'siti.r'],
        ['nama' => 'Budi Santoso',         'login' => 'budi.s'],
        ['nama' => 'Nur Indah Lestari',    'login' => 'nur.i'],
        ['nama' => 'Reza Pratama',         'login' => 'reza.p'],
    ];
    $agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126',
        'Mozilla/5.0 (Linux; Android 11) Chrome/125',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 13) Safari/17',
    ];
    $rows = [];
    $base = strtotime('today 07:00');
    for ($i = 0; $i < $perPage; $i++) {
        $u = $users[array_rand($users)];
        $rows[] = [
            'id'      => 200000 + ($page - 1) * $perPage + $i,
            'tanggal' => date('Y-m-d H:i:s', $base + rand(0, 36000) - ($i * 60)),
            'nama'    => $u['nama'],
            'login'   => $u['login'],
            'ip_asal' => '192.168.' . rand(1, 5) . '.' . rand(10, 99),
            'tujuan'  => '192.168.12.' . rand(10, 20),
            'agent'   => $agents[array_rand($agents)],
            'jenis'   => 1,
        ];
    }
    return [
        'success' => true,
        'data'    => $rows,
        'meta'    => ['total' => 306, 'page' => $page, 'per_page' => $perPage, 'total_pages' => 7],
        '_demo'   => true,
    ];
}

// ===========================================================================
// Route actions
// ===========================================================================

if ($action === 'dashboard') {

    // 1. REST API
    $data = try_api('/audit/dashboard');
    if ($data) {
        json_response($data);
    }

    // 2. Direct DB
    $data = db_dashboard();
    if ($data) {
        json_response($data);
    }

    // 3. Demo
    json_response(demo_dashboard());

} elseif ($action === 'log') {

    $filters = [
        'dari'     => sanitize_date($_GET['dari']     ?? date('Y-m-d')),
        'sampai'   => sanitize_date($_GET['sampai']   ?? date('Y-m-d')),
        'modul'    => sanitize_string($_GET['modul']  ?? '', 100),
        'aksi'     => sanitize_string($_GET['aksi']   ?? '', 1),
        'user'     => sanitize_string($_GET['user']   ?? '', 100),
        'norm'     => sanitize_string($_GET['norm']   ?? '', 20),
        'keyword'  => sanitize_string($_GET['keyword']?? '', 100),
        'page'     => sanitize_int($_GET['page']     ?? 1, 1, 9999, 1),
        'per_page' => sanitize_int($_GET['per_page'] ?? 50, 10, 200, 50),
    ];
    // Pastikan field aksi hanya nilai yang valid
    if (!in_array($filters['aksi'], ['', 'C', 'U', 'D'], true)) {
        $filters['aksi'] = '';
    }

    // 1. REST API
    $data = try_api('/audit/log', $filters);
    if ($data) {
        json_response($data);
    }

    // 2. Direct DB
    $data = db_log($filters);
    if ($data) {
        json_response($data);
    }

    // 3. Demo
    json_response(demo_log((int)$filters['page']));

} elseif ($action === 'detail') {

    $id = sanitize_string($_GET['id'] ?? '', 30);
    // ID hanya boleh berisi angka dan strip
    if ($id !== '' && !preg_match('/^[\d\-]+$/', $id)) {
        json_response(['success' => false, 'message' => 'ID tidak valid'], 400);
    }
    if (!$id) {
        json_response(['success' => false, 'message' => 'ID diperlukan'], 400);
    }

    $data = try_api("/audit/detail/$id");
    if ($data) {
        json_response($data);
    }

    // Demo detail
    json_response([
        'success' => true,
        'data' => [
            'id' => $id,
            'tanggal' => date('Y-m-d H:i:s'),
            'user_nama' => 'Budi Santoso',
            'modul' => 'Rekam Medis',
            'tabel' => 'medicalrecord.resume_medis',
            'aksi' => 'U',
            'aksi_nama' => 'Diubah',
            'ref' => 'RM-2024-001234',
            'sebelum' => ['STATUS' => 'DRAFT', 'CATATAN' => 'Belum lengkap', 'DIAGNOSA' => 'Hipertensi'],
            'sesudah'  => ['STATUS' => 'FINAL', 'CATATAN' => 'Sudah diverifikasi', 'DIAGNOSA' => 'Hipertensi Esensial'],
        ],
        '_demo' => true,
    ]);

} elseif ($action === 'riwayat') {

    $objekId = sanitize_string($_GET['objek'] ?? '', 30);
    $refId   = sanitize_string($_GET['ref']   ?? '', 50);

    $data = try_api('/audit/riwayat', ['objek' => $objekId, 'ref' => $refId]);
    if ($data) {
        json_response($data);
    }

    $data = db_riwayat($objekId, $refId);
    if ($data) {
        json_response($data);
    }

    json_response([
        'success' => true,
        'konteks' => [
            'norm'      => '00.12.34.56',
            'pasien'    => 'Siti Aminah',
            'ruangan'   => 'Poliklinik Penyakit Dalam',
            'kunjungan' => '2607300001',
            'nopen'     => '2607300042',
            'masuk'     => date('d/m/Y 08:30'),
            'rincian'   => [],
        ],
        'data' => [
            [
                'id' => 9001, 'aksi' => 'C', 'aksi_nama' => 'Dibuat', 'waktu' => '25/07/2026 08:12:34', 'oleh' => 'Siti Rahayu, S.Kep',
                'sebelum' => null,
                'sesudah' => ['STATUS' => 'DRAFT', 'CATATAN' => 'Pemeriksaan awal', 'DIAGNOSA' => 'Hipertensi']
            ],
            [
                'id' => 9045, 'aksi' => 'U', 'aksi_nama' => 'Diubah', 'waktu' => '25/07/2026 14:22:11', 'oleh' => 'dr. Andi Firmansyah',
                'sebelum' => ['STATUS' => 'DRAFT', 'CATATAN' => 'Pemeriksaan awal', 'DIAGNOSA' => 'Hipertensi'],
                'sesudah' => ['STATUS' => 'PROSES', 'CATATAN' => 'Diberi Amlodipine 5mg', 'DIAGNOSA' => 'Hipertensi Primer']
            ],
            [
                'id' => 9102, 'aksi' => 'U', 'aksi_nama' => 'Diubah', 'waktu' => '26/07/2026 09:05:47', 'oleh' => 'Budi Santoso',
                'sebelum' => ['STATUS' => 'PROSES', 'CATATAN' => 'Diberi Amlodipine 5mg', 'DIAGNOSA' => 'Hipertensi Primer'],
                'sesudah' => ['STATUS' => 'FINAL', 'CATATAN' => 'Sudah diverifikasi DPJP', 'DIAGNOSA' => 'Hipertensi Primer']
            ],
        ],
        '_demo' => true,
    ]);

} elseif ($action === 'users') {

    $filters = [
        'q'      => sanitize_string($_GET['q']      ?? '', 100),
        'dari'   => sanitize_date($_GET['dari']     ?? date('Y-m-d')),
        'sampai' => sanitize_date($_GET['sampai']   ?? date('Y-m-d')),
        'sort'   => sanitize_string($_GET['sort']   ?? 'total_desc', 20),
    ];
    // Validasi nilai sort yang diizinkan
    if (!in_array($filters['sort'], ['total_desc', 'total_asc', 'nama_asc', 'latest'], true)) {
        $filters['sort'] = 'total_desc';
    }

    $data = try_api('/audit/users', $filters);
    if ($data) {
        json_response($data);
    }

    $data = db_users($filters);
    if ($data) {
        json_response($data);
    }

    json_response(demo_users());

} elseif ($action === 'modules') {

    $data = try_api('/audit/modules');
    if ($data) {
        json_response($data);
    }

    $data = db_modules();
    if ($data) {
        json_response($data);
    }

    json_response(demo_modules());

} elseif ($action === 'statistic') {

    $dari   = sanitize_date($_GET['dari']   ?? date('Y-m-d'));
    $sampai = sanitize_date($_GET['sampai'] ?? date('Y-m-d'));

    $data = try_api('/audit/statistic', ['dari' => $dari, 'sampai' => $sampai]);
    if ($data) {
        json_response($data);
    }

    json_response(demo_statistic($dari, $sampai));

} elseif ($action === 'export') {

    $format  = in_array($_GET['format'] ?? '', ['csv', 'excel']) ? $_GET['format'] : 'csv';
    $filters = [
        'dari'     => sanitize_date($_GET['dari']     ?? date('Y-m-d')),
        'sampai'   => sanitize_date($_GET['sampai']   ?? date('Y-m-d')),
        'modul'    => sanitize_string($_GET['modul']  ?? '', 100),
        'aksi'     => sanitize_string($_GET['aksi']   ?? '', 1),
        'user'     => sanitize_string($_GET['user']   ?? '', 100),
        'norm'     => sanitize_string($_GET['norm']   ?? '', 20),
        'keyword'  => sanitize_string($_GET['keyword']?? '', 100),
        'page'     => 1,
        'per_page' => 5000,
    ];
    if (!in_array($filters['aksi'], ['', 'C', 'U', 'D'], true)) {
        $filters['aksi'] = '';
    }

    // Coba REST API export
    $data = try_api('/audit/export', $_GET);
    if ($data && isset($data['url'])) {
        header('Location: ' . $data['url']);
        exit;
    }

    // Fallback: generate CSV dari DB atau demo
    $logData = db_log($filters);
    if (!$logData) {
        $logData = demo_log(1);
        $logData['data'] = array_slice($logData['data'], 0, 20);
    }

    $filename = 'audit_trail_' . date('Ymd') . '.' . ($format === 'csv' ? 'csv' : 'csv');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 agar Excel baca benar
    fputcsv($out, ['No', 'Waktu', 'User', 'Modul', 'Aksi', 'Record ID', 'Tabel'], ';');

    $no = 1;
    foreach ($logData['data'] as $r) {
        fputcsv($out, [
            $no++,
            $r['tanggal'],
            $r['user_nama'],
            $r['modul'],
            $r['aksi_nama'],
            $r['ref'],
            $r['tabel'],
        ], ';');
    }
    fclose($out);
    exit;

} elseif ($action === 'objek_list') {
    // Ambil daftar objek/modul untuk dropdown filter
    $pdo = get_db();
    if ($pdo) {
        try {
            $st = $pdo->query("SELECT ID, TABEL, DESKRIPSI FROM aplikasi.objek WHERE TABEL LIKE '%.%' ORDER BY TABEL");
            $rows = $st->fetchAll();
            json_response(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            json_response(['success' => true, 'data' => []]);
        }
    } else {
        json_response(['success' => true, 'data' => []]);
    }

} elseif ($action === 'login_stat') {

    $dari   = sanitize_date($_GET['dari']   ?? date('Y-m-d'));
    $sampai = sanitize_date($_GET['sampai'] ?? date('Y-m-d'));

    $data = db_login_stat($dari, $sampai);
    if ($data) {
        json_response($data);
    }
    json_response(demo_login_stat());

} elseif ($action === 'login_log') {

    $filters = [
        'dari'     => sanitize_date($_GET['dari']     ?? date('Y-m-d')),
        'sampai'   => sanitize_date($_GET['sampai']   ?? date('Y-m-d')),
        'user'     => sanitize_string($_GET['user']   ?? '', 100),
        'ip'       => sanitize_string($_GET['ip']     ?? '', 45),
        'page'     => sanitize_int($_GET['page']     ?? 1, 1, 9999, 1),
        'per_page' => sanitize_int($_GET['per_page'] ?? 50, 10, 200, 50),
    ];

    $data = db_login_log($filters);
    if ($data) {
        json_response($data);
    }
    json_response(demo_login_log((int)$filters['page']));

} elseif ($action === 'analytic_range') {

    $dari   = sanitize_date($_GET['dari']   ?? date('Y-m-d', strtotime('-30 days')));
    $sampai = sanitize_date($_GET['sampai'] ?? date('Y-m-d'));

    $data = db_analytic_range($dari, $sampai);
    if ($data) {
        json_response($data);
    }
    // Fallback ke demo_dashboard jika DB tidak ada
    $demo = demo_dashboard();
    $demo['data']['jam_tersibuk']   = '09:00 - 10:00';
    $demo['data']['hari_tersibuk']  = 'Kamis';
    $demo['data']['aksi_terbanyak'] = 'Diubah';
    $demo['data']['modul_teraktif'] = 'Rekam Medis';
    $demo['data']['rata_per_hari']  = 178;
    json_response($demo);

} else {
    json_response(['success' => false, 'message' => 'Action not found'], 404);
}
