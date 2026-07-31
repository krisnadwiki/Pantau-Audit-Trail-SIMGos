<?php
/**
 * Application Configuration — PANTAU
 * Load .env dan definisikan konstanta aplikasi
 */

require_once __DIR__ . '/env.php';

// Load .env dari root aplikasi (HARUS sebelum define konstanta)
loadEnv(__DIR__ . '/../.env');

// Konstanta aplikasi
define('APP_NAME',        env('APP_NAME',    'PANTAU - Pusat Analitik Transaksi dan Aktivitas User'));
define('APP_SHORT',       'PANTAU');
define('APP_VERSION',     '1.0.0');
define('API_BASE_URL',    rtrim(env('API_BASE_URL', 'http://192.168.12.15/webservice'), '/'));
define('APP_TIMEZONE',    env('TIMEZONE',    'Asia/Jakarta'));
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', 3600)); // detik

// ── Session Hardening ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Konfigurasi session aman sebelum session_start()
    ini_set('session.cookie_httponly',  '1');      // Cegah akses cookie via JS
    ini_set('session.cookie_samesite',  'Lax');   // Lax: izinkan navigasi GET lintas-halaman (redirect login→dashboard)
    ini_set('session.use_strict_mode',  '1');      // Tolak session ID yang tidak diinisialisasi
    ini_set('session.cookie_secure',    '0');      // Set ke 1 jika sudah menggunakan HTTPS
    ini_set('session.gc_maxlifetime',   (string) SESSION_TIMEOUT);
    session_start();
}

// Database config (fallback langsung ke DB jika REST API audit belum tersedia)
define('DB_HOST', env('DB_HOST', ''));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'medicalrecord'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

date_default_timezone_set(APP_TIMEZONE);

// -------------------------------------------------------
// Auth Helpers
// -------------------------------------------------------

/**
 * Proteksi halaman — redirect ke login jika belum auth
 */
function require_auth(): void
{
    if (!isset($_SESSION['user_data'])) {
        header('Location: /login.php');
        exit;
    }
    // Regenerate session ID secara berkala untuk mencegah session fixation
    if (!isset($_SESSION['_last_regenerate'])) {
        $_SESSION['_last_regenerate'] = time();
    } elseif (time() - $_SESSION['_last_regenerate'] > 1800) { // setiap 30 menit
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }
}

// -------------------------------------------------------
// CSRF Protection
// -------------------------------------------------------

/**
 * Generate CSRF token dan simpan di session.
 * Panggil saat render halaman yang butuh proteksi.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Verifikasi CSRF token dari header atau POST data.
 * Kembalikan false jika token tidak valid.
 */
function verify_csrf_token(string $token): bool
{
    if (empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

// -------------------------------------------------------
// Rate Limiting (Session-based)
// -------------------------------------------------------

/**
 * Cek dan catat percobaan login yang gagal.
 * Kembalikan array status rate limit.
 *
 * @param  bool $reset  Jika true, reset counter (setelah login sukses)
 * @return array ['blocked' => bool, 'remaining' => int, 'retry_after' => int (detik)]
 */
function check_rate_limit(bool $reset = false): array
{
    $maxAttempts   = 5;    // Maksimum percobaan gagal
    $windowSeconds = 600;  // Window 10 menit
    $lockoutSeconds = 900; // Lockout 15 menit

    if ($reset) {
        unset($_SESSION['_login_attempts'], $_SESSION['_login_lockout_until']);
        return ['blocked' => false, 'remaining' => $maxAttempts, 'retry_after' => 0];
    }

    // Cek apakah sedang dalam masa lockout
    if (!empty($_SESSION['_login_lockout_until'])) {
        $retryAfter = (int) $_SESSION['_login_lockout_until'] - time();
        if ($retryAfter > 0) {
            return ['blocked' => true, 'remaining' => 0, 'retry_after' => $retryAfter];
        }
        // Lockout sudah lewat, reset
        unset($_SESSION['_login_attempts'], $_SESSION['_login_lockout_until']);
    }

    // Init atau bersihkan percobaan lama
    if (empty($_SESSION['_login_attempts'])) {
        $_SESSION['_login_attempts'] = [];
    }

    // Bersihkan percobaan di luar window
    $now = time();
    $_SESSION['_login_attempts'] = array_filter(
        $_SESSION['_login_attempts'],
        fn($t) => ($now - $t) < $windowSeconds
    );

    // Catat percobaan ini
    $_SESSION['_login_attempts'][] = $now;

    $count = count($_SESSION['_login_attempts']);

    if ($count >= $maxAttempts) {
        $_SESSION['_login_lockout_until'] = $now + $lockoutSeconds;
        unset($_SESSION['_login_attempts']);
        return ['blocked' => true, 'remaining' => 0, 'retry_after' => $lockoutSeconds];
    }

    return [
        'blocked'     => false,
        'remaining'   => $maxAttempts - $count,
        'retry_after' => 0,
    ];
}

// -------------------------------------------------------
// Security Headers
// -------------------------------------------------------

/**
 * Kirim security headers standar.
 * Panggil di awal setiap response (dipanggil otomatis oleh json_response).
 */
function send_security_headers(): void
{
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

/**
 * Cek apakah user memiliki role tertentu
 */
function has_role(string ...$roles): bool
{
    $userRole = $_SESSION['user_data']['role'] ?? '';
    return in_array($userRole, $roles, true);
}

/**
 * Data user aktif dari session
 */
function current_user(): array
{
    return $_SESSION['user_data'] ?? [];
}

// -------------------------------------------------------
// HTTP Proxy Helpers
// -------------------------------------------------------

/**
 * Lakukan HTTP GET ke Webservice SIMGOS
 */
function api_get(string $path, array $params = [], int $timeout = 30, int $connectTimeout = 10): array
{
    $url = API_BASE_URL . $path;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return curl_request($url, 'GET', null, $timeout, $connectTimeout);
}

/**
 * Lakukan HTTP POST ke Webservice SIMGOS
 */
function api_post(string $path, array $payload = []): array
{
    $url = API_BASE_URL . $path;
    return curl_request($url, 'POST', $payload);
}

/**
 * Lakukan HTTP PUT ke Webservice SIMGOS
 */
function api_put(string $path, array $payload = []): array
{
    $url = API_BASE_URL . $path;
    return curl_request($url, 'PUT', $payload);
}

/**
 * Menggabungkan cookie lama dan cookie baru
 */
function merge_cookies(?string $existingCookieStr, array $newCookies): string
{
    $cookies = [];
    if (!empty($existingCookieStr)) {
        $parts = explode(';', $existingCookieStr);
        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $cookies[trim($kv[0])] = trim($kv[1]);
            }
        }
    }
    foreach ($newCookies as $newCookie) {
        $kv = explode('=', trim($newCookie), 2);
        if (count($kv) === 2) {
            $cookies[trim($kv[0])] = trim($kv[1]);
        }
    }
    $cookieStrings = [];
    foreach ($cookies as $k => $v) {
        $cookieStrings[] = "$k=$v";
    }
    return implode('; ', $cookieStrings);
}

/**
 * Eksekusi cURL request
 */
function curl_request(string $url, string $method = 'GET', ?array $payload = null, int $timeout = 30, int $connectTimeout = 10): array
{
    $ch = curl_init();

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if (!empty($_SESSION['simgos_cookie'])) {
        $headers[] = 'Cookie: ' . $_SESSION['simgos_cookie'];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $responseCookies = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $headerLine) use (&$responseCookies) {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $headerLine, $matches)) {
            $responseCookies[] = trim($matches[1]);
        }
        return strlen($headerLine);
    });

    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    }

    $response   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'status' => 0,
            'body'   => ['error' => 'cURL Error: ' . $curlError],
        ];
    }

    if (!empty($responseCookies)) {
        $_SESSION['simgos_cookie'] = merge_cookies($_SESSION['simgos_cookie'] ?? null, $responseCookies);
    }

    $decoded = json_decode($response, true);

    return [
        'status' => $httpStatus,
        'body'   => $decoded ?? $response,
        'raw'    => is_string($response) ? $response : '',
    ];
}

/**
 * Kirim response JSON dan akhiri eksekusi
 */
function json_response($data, int $status = 200)
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status);
    send_security_headers();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Pastikan hanya method tertentu yang diizinkan
 */
function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        json_response(['error' => 'Method not allowed'], 405);
    }
}

/**
 * Koneksi PDO ke database SIMGOS (fallback jika REST API audit tidak tersedia)
 */
function get_db(): ?PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (DB_HOST === '') return null;

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}
