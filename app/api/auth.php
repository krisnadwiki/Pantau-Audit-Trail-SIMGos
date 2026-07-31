<?php
/**
 * API Controller: Authentication
 * GET /api/auth.php?action=captcha
 * POST /api/auth.php?action=login
 * POST /api/auth.php?action=logout
 * GET /api/auth.php?action=check
 */

require_once __DIR__ . '/../config/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'captcha') {
    // Clear cookie SIMGOS lama sebelum minta captcha baru.
    // Ini memastikan setiap sesi login dimulai fresh — penting agar SIMGOS
    // mengembalikan XPRIV terbaru (misalnya setelah hak akses user diubah).
    unset($_SESSION['simgos_cookie']);

    // Dedicated curl call for captcha to preserve raw binary output and exact Content-Type header
    $url = API_BASE_URL . '/authentication/captcha';
    $ch = curl_init();

    $headers = [];
    
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
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
    
    $response = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!empty($responseCookies)) {
        $_SESSION['simgos_cookie'] = merge_cookies($_SESSION['simgos_cookie'] ?? null, $responseCookies);
    }
    
    http_response_code($httpCode ?: 200);
    if ($contentType) {
        header('Content-Type: ' . $contentType);
    } else {
        header('Content-Type: image/png');
    }
    echo $response;
    exit;

} elseif ($action === 'login') {
    require_method('POST');

    // Read JSON payload
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    if (empty($input['LOGIN']) || empty($input['PASSWORD']) || empty($input['CAPTCHA'])) {
        json_response([
            'success' => false,
            'message' => 'Username, password, dan captcha harus diisi'
        ], 400);
    }
    
    // Call SIMGos backend login API
    $result = curl_request(API_BASE_URL . '/authentication/login', 'POST', [
        'LOGIN'    => trim($input['LOGIN']),
        'PASSWORD' => $input['PASSWORD'],
        'CAPTCHA'  => trim($input['CAPTCHA'])
    ]);
    
    if ($result['status'] === 200 && !empty($result['body']['success'])) {
        $userData = $result['body']['data'] ?? [];

        // ── Validasi hak akses modul 28 (PANTAU) ─────────────────────────
        // Strategi: cari string literal "28":"28" atau "2801":"2801" dst.
        // langsung di raw response menggunakan strpos — tidak bergantung pada
        // json_decode yang terpotong saat response sangat besar.
        // Pattern yang dicari: "28": "28" atau "2801": "2801" (dengan/tanpa spasi)
        $rawResponse = $result['raw'] ?? json_encode($result['body']);
        $hasAccess   = false;

        // Cek beberapa variasi format JSON yang mungkin
        $patterns = ['"28":"28"', '"28" : "28"','"2801":"2801"','"2801" : "2801"']; // cek id m=modul
        foreach ($patterns as $pattern) {
            if (strpos($rawResponse, $pattern) !== false) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            // Clear cookie SIMGOS agar captcha berikutnya memulai session baru di SIMGOS.
            // Tanpa ini, user yang baru saja diberi akses masih akan ditolak
            // karena cookie lama masih membawa XPRIV versi sebelumnya.
            unset($_SESSION['simgos_cookie']);

            json_response([
                'success' => false,
                'message' => 'Akun Anda tidak memiliki akses ke aplikasi PANTAU. Hubungi administrator.'
            ], 403);
        }
        // ─────────────────────────────────────────────────────────────────

        // Save authenticated user profile in session
        $_SESSION['user_data'] = [
            'id'       => $userData['ID']   ?? $userData['id']   ?? 1,
            'username' => trim($input['LOGIN']),
            'NAME'     => $userData['NAME'] ?? $userData['name'] ?? trim($input['LOGIN']),
            'NIP'      => $userData['NIP']  ?? $userData['nip']  ?? '',
            'email'    => $userData['EMAIL'] ?? $userData['email'] ?? '',
            'role'     => $userData['ROLE'] ?? $userData['role']  ?? 'user',
        ];

        json_response([
            'success' => true,
            'message' => 'Login berhasil',
            'user'    => $_SESSION['user_data']
        ]);
    } else {
        $message = $result['body']['message'] ?? 'Username, password, atau captcha salah';
        json_response([
            'success' => false,
            'message' => $message
        ], $result['status'] ?: 400);
    }

} elseif ($action === 'logout') {
    // Invalidate session di SIMGOS backend
    if (!empty($_SESSION['simgos_cookie'])) {
        curl_request(API_BASE_URL . '/authentication/logout', 'POST');
    }

    // Hapus cookie SIMGOS dari session
    unset($_SESSION['simgos_cookie']);
    
    // Clear dan destroy PHP Session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    
    json_response([
        'success' => true,
        'message' => 'Logout berhasil'
    ]);

} elseif ($action === 'check') {
    require_method('GET');
    
    if (isset($_SESSION['user_data'])) {
        json_response([
            'authenticated' => true,
            'user'          => $_SESSION['user_data']
        ]);
    } else {
        json_response([
            'authenticated' => false
        ], 401);
    }

} else {
    json_response([
        'success' => false,
        'message' => 'Action not found'
    ], 404);
}
