<?php
/**
 * settings.php — Halaman Pengaturan PANTAU
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Pengaturan';
$activePage = 'settings';

// Simpan/baca pengaturan dari session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $_SESSION['settings'] = [
        'timezone'        => $_POST['timezone']       ?? APP_TIMEZONE,
        'session_timeout' => (int)($_POST['session_timeout'] ?? 3600),
        'auto_refresh'    => isset($_POST['auto_refresh']) ? (int)$_POST['auto_refresh'] : 0,
        'default_rows'    => (int)($_POST['default_rows'] ?? 50),
        'theme'           => $_POST['theme'] ?? 'system',
    ];
    $_SESSION['settings_saved'] = true;
    header('Location: /settings.php');
    exit;
}

$settings = $_SESSION['settings'] ?? [
    'timezone'        => APP_TIMEZONE,
    'session_timeout' => 3600,
    'auto_refresh'    => 0,
    'default_rows'    => 50,
    'theme'           => 'system',
];

$saved = $_SESSION['settings_saved'] ?? false;
unset($_SESSION['settings_saved']);

$timezones = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'];

include __DIR__ . '/../templates/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>

        <main class="app-content">

            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-gear me-2" style="color:var(--color-primary)"></i>Pengaturan
                </h1>
                <p class="page-subtitle">Konfigurasi aplikasi PANTAU</p>
            </div>

            <?php if ($saved): ?>
            <div class="app-alert alert-success mb-3" style="animation:none">
                <i class="bi bi-check-circle-fill"></i>
                <span>Pengaturan berhasil disimpan.</span>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Left: Form -->
                <div class="col-lg-7">
                    <form method="POST">
                        <input type="hidden" name="save_settings" value="1">

                        <!-- Tampilan -->
                        <div class="app-card mb-3">
                            <div class="app-card-header">
                                <h2 class="app-card-title">
                                    <i class="bi bi-palette-fill" style="color:var(--color-primary)"></i>
                                    Tampilan
                                </h2>
                            </div>
                            <div class="app-card-body">
                                <div class="mb-3">
                                    <label class="form-label-dark">Tema Warna</label>
                                    <select name="theme" class="form-select form-select-dark" id="setting-theme">
                                        <option value="system"  <?= $settings['theme']==='system'  ? 'selected' : '' ?>>Ikuti Sistem</option>
                                        <option value="dark"    <?= $settings['theme']==='dark'    ? 'selected' : '' ?>>Dark Mode</option>
                                        <option value="light"   <?= $settings['theme']==='light'   ? 'selected' : '' ?>>Light Mode</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label-dark">Baris Default per Halaman</label>
                                    <select name="default_rows" class="form-select form-select-dark">
                                        <?php foreach ([25, 50, 100, 200] as $r): ?>
                                        <option value="<?= $r ?>" <?= $settings['default_rows'] == $r ? 'selected' : '' ?>><?= $r ?> baris</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Waktu & Sesi -->
                        <div class="app-card mb-3">
                            <div class="app-card-header">
                                <h2 class="app-card-title">
                                    <i class="bi bi-clock-fill" style="color:var(--color-info)"></i>
                                    Waktu & Sesi
                                </h2>
                            </div>
                            <div class="app-card-body">
                                <div class="mb-3">
                                    <label class="form-label-dark">Timezone</label>
                                    <select name="timezone" class="form-select form-select-dark">
                                        <?php foreach ($timezones as $tz): ?>
                                        <option value="<?= $tz ?>" <?= $settings['timezone'] === $tz ? 'selected' : '' ?>>
                                            <?= $tz ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted" style="font-size:.75rem">
                                        Waktu server saat ini: <?= date('d/m/Y H:i:s T') ?>
                                    </small>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label-dark">Session Timeout</label>
                                    <select name="session_timeout" class="form-select form-select-dark">
                                        <?php foreach ([1800=>'30 menit', 3600=>'1 jam', 7200=>'2 jam', 14400=>'4 jam', 28800=>'8 jam'] as $v=>$l): ?>
                                        <option value="<?= $v ?>" <?= $settings['session_timeout'] == $v ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Auto Refresh -->
                        <div class="app-card mb-3">
                            <div class="app-card-header">
                                <h2 class="app-card-title">
                                    <i class="bi bi-arrow-repeat" style="color:var(--color-success)"></i>
                                    Auto Refresh
                                </h2>
                            </div>
                            <div class="app-card-body">
                                <div>
                                    <label class="form-label-dark">Interval Auto Refresh Dashboard</label>
                                    <select name="auto_refresh" class="form-select form-select-dark">
                                        <option value="0"    <?= $settings['auto_refresh'] == 0    ? 'selected' : '' ?>>Tidak aktif</option>
                                        <option value="30"   <?= $settings['auto_refresh'] == 30   ? 'selected' : '' ?>>30 detik</option>
                                        <option value="60"   <?= $settings['auto_refresh'] == 60   ? 'selected' : '' ?>>1 menit</option>
                                        <option value="300"  <?= $settings['auto_refresh'] == 300  ? 'selected' : '' ?>>5 menit</option>
                                        <option value="600"  <?= $settings['auto_refresh'] == 600  ? 'selected' : '' ?>>10 menit</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-app-primary">
                            <i class="bi bi-floppy-fill"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>

                <!-- Right: Info -->
                <div class="col-lg-5">
                    <!-- App Info -->
                    <div class="app-card mb-3">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-shield-fill-check" style="color:var(--color-primary)"></i>
                                Informasi Aplikasi
                            </h2>
                        </div>
                        <div class="app-card-body">
                            <?php
                            $infos = [
                                'Aplikasi'    => APP_NAME,
                                'Versi'       => APP_VERSION,
                                'API Base URL'=> API_BASE_URL,
                                'Timezone'    => APP_TIMEZONE,
                                'PHP'         => PHP_VERSION,
                                'DB Host'     => DB_HOST ?: '(tidak dikonfigurasi)',
                                'Login sebagai' => $_SESSION['user_data']['NAME'] ?? '—',
                                'Role'        => $_SESSION['user_data']['role'] ?? '—',
                            ];
                            foreach ($infos as $label => $val): ?>
                            <div class="info-row" style="margin-bottom:.5rem">
                                <div class="info-label" style="min-width:130px;font-size:.78rem;color:var(--color-text-muted);font-weight:600">
                                    <?= htmlspecialchars($label) ?>
                                </div>
                                <div class="info-value" style="font-size:.82rem;word-break:break-all">
                                    <?= htmlspecialchars($val) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- DB Status -->
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-database-fill" style="color:var(--color-warning)"></i>
                                Status Koneksi
                            </h2>
                        </div>
                        <div class="app-card-body">
                            <?php
                            // Test REST API
                            $apiOk = false;
                            $apiMsg = '';
                            try {
                                $ch = curl_init(API_BASE_URL . '/authentication/captcha');
                                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>5,CURLOPT_SSL_VERIFYPEER=>false]);
                                curl_exec($ch);
                                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                $apiOk  = $code > 0;
                                $apiMsg = "HTTP $code";
                            } catch (Exception $e) {
                                $apiMsg = $e->getMessage();
                            }

                            // Test DB
                            $dbOk  = false;
                            $dbMsg = '';
                            $pdo   = get_db();
                            if ($pdo) {
                                try {
                                    $pdo->query('SELECT 1');
                                    $dbOk  = true;
                                    $dbMsg = 'Terhubung';
                                } catch (Exception $e) {
                                    $dbMsg = $e->getMessage();
                                }
                            } else {
                                $dbMsg = DB_HOST ? 'Gagal terhubung' : 'Tidak dikonfigurasi';
                            }
                            ?>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span style="width:10px;height:10px;border-radius:50%;background:<?= $apiOk?'#22c55e':'#ef4444' ?>;flex-shrink:0"></span>
                                <div>
                                    <div style="font-size:.82rem;font-weight:600">REST API SIMGOS</div>
                                    <div style="font-size:.75rem;color:var(--color-text-muted)"><?= htmlspecialchars($apiMsg) ?></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:10px;height:10px;border-radius:50%;background:<?= $dbOk?'#22c55e':($dbMsg==='Tidak dikonfigurasi'?'#94a3b8':'#ef4444') ?>;flex-shrink:0"></span>
                                <div>
                                    <div style="font-size:.82rem;font-weight:600">Database SIMGOS</div>
                                    <div style="font-size:.75rem;color:var(--color-text-muted)"><?= htmlspecialchars($dbMsg) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
</div>

<script>
// Apply theme immediately when changed
document.getElementById('setting-theme').addEventListener('change', function() {
    const v = this.value;
    const isDark = v === 'dark' || (v === 'system' && !window.matchMedia('(prefers-color-scheme: light)').matches);
    if (isDark) {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
    }
});
</script>
