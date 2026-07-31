<?php
/**
 * Application Configuration — PANTAU
 * Load .env dan definisikan konstanta aplikasi
 */

require_once __DIR__ . '/env.php';

// Mulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load .env dari root aplikasi
loadEnv(__DIR__ . '/../.env');

// Konstanta aplikasi
define('APP_NAME',        env('APP_NAME',    'PANTAU - Pusat ANalitik Transaksi dan Aktivitas User'));
define('APP_SHORT',       'PANTAU');
define('APP_VERSION',     '1.0.0');
define('API_BASE_URL',    rtrim(env('API_BASE_URL', 'http://192.168.12.15/webservice'), '/'));
define('APP_TIMEZONE',    env('TIMEZONE',    'Asia/Jakarta'));
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', 3600)); // detik

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
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
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
