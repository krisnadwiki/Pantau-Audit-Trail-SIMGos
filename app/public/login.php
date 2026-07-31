<?php
/**
 * login.php — Halaman Login PANTAU
 */

require_once __DIR__ . '/../config/config.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_data'])) {
    header('Location: /dashboard.php');
    exit;
}

// Generate CSRF token untuk form login
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login PANTAU — Monitoring Audit Trail SIMGOS">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Login <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/image/favicon.ico">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --lp-bg:          #f0f4f8;
            --lp-card:        #ffffff;
            --lp-border:      #e2e8f0;
            --lp-text:        #0f172a;
            --lp-muted:       #64748b;
            --lp-subtle:      #94a3b8;
            --lp-input-bg:    #f8fafc;
            --lp-primary:     #0f766e;
            --lp-primary-d:   #0d6460;
            --lp-secondary:   #0891b2;
            --lp-grad:        linear-gradient(135deg, #0f766e 0%, #0891b2 100%);
            --lp-shadow:      0 20px 60px rgba(15,118,110,.12), 0 4px 16px rgba(0,0,0,.06);
            --lp-radius:      14px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--lp-bg);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Background decorative blobs ── */
        .login-bg {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .login-bg::before {
            content: '';
            position: absolute;
            top: -120px; left: -120px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(15,118,110,.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .login-bg::after {
            content: '';
            position: absolute;
            bottom: -100px; right: -100px;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(8,145,178,.10) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* ── Layout ── */
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 1;
        }

        .login-split {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: var(--lp-card);
            border-radius: var(--lp-radius);
            box-shadow: var(--lp-shadow);
            overflow: hidden;
            border: 1px solid var(--lp-border);
        }

        /* ── Left Panel ── */
        .login-panel-left {
            flex: 0 0 42%;
            background: var(--lp-grad);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-panel-left::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,.07);
            border-radius: 50%;
        }
        .login-panel-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 280px; height: 280px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }

        .left-brand {
            position: relative;
            z-index: 1;
        }

        .left-logo {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 2.5rem;
        }

        .left-logo-img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .left-logo-text .app-wordmark {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.03em;
            line-height: 1;
        }

        .left-logo-text .app-tagline {
            font-size: .65rem;
            font-weight: 600;
            color: rgba(255,255,255,.7);
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        .left-headline {
            color: #fff;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.35;
            margin-bottom: .75rem;
            letter-spacing: -.02em;
        }

        .left-desc {
            color: rgba(255,255,255,.75);
            font-size: .82rem;
            line-height: 1.6;
        }

        .left-features {
            position: relative;
            z-index: 1;
            margin-top: 2rem;
        }

        .left-feature-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .5rem 0;
            color: rgba(255,255,255,.85);
            font-size: .8rem;
        }

        .left-feature-item .feat-icon {
            width: 28px;
            height: 28px;
            background: rgba(255,255,255,.15);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            flex-shrink: 0;
        }

        .left-bottom {
            position: relative;
            z-index: 1;
            margin-top: auto;
            padding-top: 2rem;
        }

        .left-version {
            font-size: .72rem;
            color: rgba(255,255,255,.5);
            font-weight: 500;
        }

        /* ── Right Panel ── */
        .login-panel-right {
            flex: 1;
            padding: 3rem 2.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right-header {
            margin-bottom: 2rem;
        }

        .login-right-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--lp-text);
            letter-spacing: -.03em;
            margin-bottom: .3rem;
        }

        .login-right-header p {
            color: var(--lp-muted);
            font-size: .85rem;
            margin: 0;
        }

        /* ── Form ── */
        .lp-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--lp-muted);
            margin-bottom: .35rem;
            display: block;
        }

        .lp-input {
            width: 100%;
            background: var(--lp-input-bg);
            border: 1.5px solid var(--lp-border);
            border-radius: 8px;
            padding: .65rem 1rem;
            font-size: .88rem;
            color: var(--lp-text);
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }

        .lp-input:focus {
            border-color: var(--lp-primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(15,118,110,.12);
        }

        .lp-input::placeholder { color: var(--lp-subtle); }

        .lp-input-group {
            position: relative;
        }

        .lp-input-group .lp-input {
            padding-right: 2.75rem;
        }

        .lp-input-toggle {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--lp-subtle);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            line-height: 1;
            transition: color .15s;
        }

        .lp-input-toggle:hover { color: var(--lp-text); }

        /* ── Captcha ── */
        .captcha-row {
            display:     flex;
            align-items: center;
            gap:         .5rem;
        }

        .captcha-row .lp-input {
            flex: 1;
            min-width: 0;
        }

        .captcha-img-wrap {
            flex-shrink: 0;
            width:       110px;
            height:      40px;
            background:  #fff;
            border:      1.5px solid var(--lp-border);
            border-radius: 8px;
            overflow:    hidden;
            display:     flex;
            align-items: center;
            justify-content: center;
        }

        .captcha-img-wrap img { max-height: 38px; width: auto; }

        .btn-captcha-refresh {
            flex-shrink: 0;
            width:       40px;
            height:      40px;
            border-radius: 8px;
            background:  #e9eef5;
            border:      1.5px solid var(--lp-border);
            color:       var(--lp-muted);
            display:     flex;
            align-items: center;
            justify-content: center;
            cursor:      pointer;
            font-size:   .95rem;
            transition:  all .2s;
        }

        .btn-captcha-refresh:hover {
            background:   var(--lp-primary);
            border-color: var(--lp-primary);
            color:        #fff;
        }

        /* ── Error Alert ── */
        .lp-error {
            display: none;
            background: rgba(220,38,38,.07);
            border: 1.5px solid rgba(220,38,38,.2);
            border-radius: 8px;
            padding: .65rem .9rem;
            font-size: .82rem;
            color: #dc2626;
            margin-bottom: 1.25rem;
        }

        /* ── Submit Button ── */
        .btn-submit {
            width: 100%;
            padding: .75rem;
            background: var(--lp-grad);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(15,118,110,.3);
            letter-spacing: -.01em;
        }

        .btn-submit:hover:not(:disabled) {
            opacity: .93;
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(15,118,110,.38);
        }

        .btn-submit:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Footer ── */
        .login-footer {
            text-align: center;
            padding: 1.25rem 1rem;
            position: relative;
            z-index: 1;
        }

        .login-footer p {
            color: var(--lp-subtle);
            font-size: .75rem;
            margin: 0;
            line-height: 1.6;
        }

        .login-footer strong {
            color: var(--lp-muted);
        }

        /* ── Responsive ── */
        @media (max-width: 680px) {
            .login-split { flex-direction: column; max-width: 440px; }
            .login-panel-left {
                flex: 0 0 auto;
                padding: 2rem 2rem 1.5rem;
            }
            .left-features { display: none; }
            .login-panel-right { padding: 2rem; }
        }
    </style>
</head>
<body>

    <!-- Background decorative -->
    <div class="login-bg"></div>

    <!-- Main wrapper -->
    <div class="login-wrapper">
        <div class="login-split">

            <!-- Left: Branding Panel -->
            <div class="login-panel-left">
                <div class="left-brand">
                    <div class="left-logo">
                        <img src="/assets/image/pantau_logo.png"
                             alt="PANTAU Logo"
                             class="left-logo-img">
                        <div class="left-logo-text">
                            <div class="app-wordmark">PANTAU</div>
                            <div class="app-tagline">Audit Trail SIMGOS</div>
                        </div>
                    </div>

                    <h2 class="left-headline">Pusat Analitik<br>Transaksi &amp;<br>Aktivitas User</h2>
                    <p class="left-desc">
                        Platform monitoring terpadu untuk audit trail, aktivitas pengguna, dan analitik sistem SIMGOS.
                    </p>

                    <div class="left-features">
                        <div class="left-feature-item">
                            <span class="feat-icon"><i class="bi bi-shield-check"></i></span>
                            Monitoring Audit Trail Real-time
                        </div>
                        <div class="left-feature-item">
                            <span class="feat-icon"><i class="bi bi-person-lines-fill"></i></span>
                            Analitik Aktivitas Pengguna
                        </div>
                        <div class="left-feature-item">
                            <span class="feat-icon"><i class="bi bi-graph-up-arrow"></i></span>
                            Dashboard &amp; Laporan Interaktif
                        </div>
                        <div class="left-feature-item">
                            <span class="feat-icon"><i class="bi bi-lock-fill"></i></span>
                            Login Monitor &amp; Keamanan
                        </div>
                    </div>
                </div>

               
                <div class="left-bottom">
                    <div class="left-version">PANTAU v<?= defined('APP_VERSION') ? htmlspecialchars(APP_VERSION) : '1.0' ?></div>
                </div>
            </div>

            <!-- Right: Login Form -->
            <div class="login-panel-right">

                <div class="login-right-header">
                    <h2>Selamat Datang</h2>
                    <p>Masuk dengan akun SIMGOS Anda untuk melanjutkan</p>
                </div>

                <!-- Error Alert -->
                <div class="lp-error" id="errorAlert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <span id="errorMessage">Username atau password salah</span>
                    </div>
                </div>

                <form id="loginForm" novalidate>
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="lp-label">Username</label>
                        <input type="text" class="lp-input" id="username"
                               autocomplete="username" placeholder="Masukkan username"
                               required autofocus maxlength="100"
                               pattern="[\w.\-@]+" title="Hanya huruf, angka, titik, underscore, dan @">
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="lp-label">Password</label>
                        <div class="lp-input-group">
                            <input type="password" class="lp-input" id="password"
                                   autocomplete="current-password" placeholder="Masukkan password"
                                   required maxlength="200">
                            <button type="button" class="lp-input-toggle" id="togglePassword" aria-label="Tampilkan password">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Captcha -->
                    <div class="mb-4">
                        <label for="captcha" class="lp-label">Kode Captcha</label>
                        <div class="captcha-row">
                            <input type="text" class="lp-input" id="captcha"
                                   placeholder="Ketik kode captcha" required
                                   autocomplete="off" maxlength="20"
                                   pattern="[a-zA-Z0-9]+" title="Hanya huruf dan angka">
                            <div class="captcha-img-wrap">
                                <img id="captchaImage" src="" alt="Captcha" style="display:none;">
                                <div id="captchaLoading">
                                    <span class="spinner-border spinner-border-sm" style="border-color:#0f766e; border-right-color:transparent; width:14px; height:14px;"></span>
                                </div>
                            </div>
                            <button type="button" class="btn-captcha-refresh" id="refreshCaptcha" title="Refresh Captcha">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-submit" id="loginBtn">
                        <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status"
                              style="border-color:rgba(255,255,255,.4); border-right-color:#fff;"></span>
                        <span id="loginBtnText">
                            <i class="bi bi-shield-lock me-1"></i>Masuk ke PANTAU
                        </span>
                    </button>
                </form>
                <div class="login-footer">
                    <p>
                        &copy; 2022&ndash;<?= date('Y') ?><br>
                        <strong>Information, Communication and Technology</strong>, RSUD Kilisuci
                    </p>
                </div>

            </div><!-- .login-panel-right -->
        </div><!-- .login-split -->
    </div><!-- .login-wrapper -->

    <!-- Footer copyright -->
    <!-- <footer class="login-footer">
        <p>
            &copy; 2022&ndash;<?= date('Y') ?> <strong>Information, Communication and Technology</strong><br>
            RSUD Kilisuci &mdash; Semua hak dilindungi
        </p>
    </footer> -->

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const captchaImg     = document.getElementById('captchaImage');
        const captchaLoading = document.getElementById('captchaLoading');
        const refreshBtn     = document.getElementById('refreshCaptcha');

        function loadCaptcha() {
            captchaLoading.style.display = 'flex';
            captchaImg.style.display = 'none';
            captchaImg.src = '/api/auth.php?action=captcha&_t=' + Date.now();
        }

        captchaImg.onload  = function() {
            captchaLoading.style.display = 'none';
            captchaImg.style.display = 'block';
        };
        captchaImg.onerror = function() {
            captchaLoading.innerHTML = '<span style="color:#dc2626;font-size:.7rem;">Gagal</span>';
        };

        refreshBtn.addEventListener('click', function(e) { e.preventDefault(); loadCaptcha(); });

        // Toggle password
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput  = document.getElementById('password');
        togglePassword.addEventListener('click', function() {
            const isPass = passwordInput.type === 'password';
            passwordInput.type = isPass ? 'text' : 'password';
            this.querySelector('i').className = isPass ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        // Form submit
        const loginForm    = document.getElementById('loginForm');
        const loginBtn     = document.getElementById('loginBtn');
        const loginSpinner = document.getElementById('loginSpinner');
        const loginBtnText = document.getElementById('loginBtnText');
        const errorAlert   = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const captcha  = document.getElementById('captcha').value.trim();

            // Validasi client-side
            if (!username || !password || !captcha) {
                showError('Semua field harus diisi');
                return;
            }
            if (username.length > 100) {
                showError('Username terlalu panjang (maks 100 karakter)');
                return;
            }
            if (password.length > 200) {
                showError('Password terlalu panjang (maks 200 karakter)');
                return;
            }
            if (captcha.length > 20) {
                showError('Captcha terlalu panjang');
                return;
            }
            if (!/^[\w.\-@]+$/.test(username)) {
                showError('Format username mengandung karakter tidak valid');
                return;
            }
            if (!/^[a-zA-Z0-9]+$/.test(captcha)) {
                showError('Format captcha tidak valid (hanya huruf dan angka)');
                return;
            }

            hideError();
            loginBtn.disabled = true;
            loginSpinner.classList.remove('d-none');
            loginBtnText.innerHTML = 'Memproses...';

            // Ambil CSRF token dari meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ LOGIN: username, PASSWORD: password, CAPTCHA: captcha })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    loginBtnText.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Login Berhasil!';
                    loginBtn.style.background = 'linear-gradient(135deg,#16a34a,#15803d)';
                    setTimeout(() => { window.location.href = '/dashboard.php'; }, 700);
                } else if (response.status === 429 && result.rate_limited) {
                    // Rate limit — tampilkan countdown
                    const retryAfter = result.retry_after || 900;
                    showError(result.message || 'Terlalu banyak percobaan login.');
                    loginBtn.disabled = true;
                    startLockoutCountdown(retryAfter, loginBtn, loginBtnText, loginSpinner);
                } else {
                    showError(result.message || 'Username, password, atau captcha salah');
                    loadCaptcha();
                    document.getElementById('captcha').value = '';
                    loginBtn.disabled = false;
                    loginSpinner.classList.add('d-none');
                    loginBtnText.innerHTML = '<i class="bi bi-shield-lock me-1"></i>Masuk ke PANTAU';
                }
            } catch (error) {
                showError('Gagal menghubungi server. Silakan coba kembali.');
                loadCaptcha();
                document.getElementById('captcha').value = '';
                loginBtn.disabled = false;
                loginSpinner.classList.add('d-none');
                loginBtnText.innerHTML = '<i class="bi bi-shield-lock me-1"></i>Masuk ke PANTAU';
            }
        });

        function startLockoutCountdown(seconds, btn, btnText, spinner) {
            spinner.classList.add('d-none');
            let remaining = seconds;
            function tick() {
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                btnText.innerHTML = `<i class="bi bi-lock-fill me-1"></i>Coba lagi dalam ${m}:${String(s).padStart(2,'0')}`;
                if (remaining <= 0) {
                    btn.disabled = false;
                    btnText.innerHTML = '<i class="bi bi-shield-lock me-1"></i>Masuk ke PANTAU';
                    return;
                }
                remaining--;
                setTimeout(tick, 1000);
            }
            tick();
        }

        function showError(msg) { errorMessage.textContent = msg; errorAlert.style.display = 'block'; }
        function hideError()    { errorAlert.style.display = 'none'; }

        loadCaptcha();
    });
    </script>
</body>
</html>
