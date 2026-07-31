<nav class="navbar navbar-expand-lg navbar-dark app-navbar" id="main-navbar">
    <div class="container-fluid px-3 px-md-4">

        <!-- Mobile: hamburger untuk membuka sidebar (offcanvas) -->
        <button class="btn-sidebar-toggle d-lg-none me-2"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileSidebar"
                aria-controls="mobileSidebar"
                aria-label="Buka menu navigasi">
            <i class="bi bi-list"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="/dashboard.php">
            <span class="brand-logo-wrap">
                <img src="/assets/image/pantau_logo.png"
                     alt="PANTAU"
                     class="brand-logo-icon"
                     width="30" height="30">
            </span>
            <span class="brand-text">
                <span class="brand-name">PANTAU</span>
                <span class="brand-sub d-none d-sm-inline">Audit Trail SIMGOS</span>
            </span>
        </a>

        <!-- Desktop: collapse nav items (theme toggle + user) -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center gap-1">
                <!-- Theme Toggle Button -->
                <li class="nav-item ms-1">
                    <button class="btn-theme-toggle" id="btn-theme-toggle"
                            title="Ganti tema" aria-label="Toggle tema">
                        <i class="bi bi-brightness-high-fill"></i>
                    </button>
                </li>

                <?php if (isset($_SESSION['user_data'])): ?>
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-1 rounded-pill user-pill"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="userDropdown">
                            <span class="user-avatar-badge">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <span class="d-none d-md-inline text-truncate" style="max-width: 140px;"
                                  title="<?= htmlspecialchars($_SESSION['user_data']['NAME']) ?>">
                                <?= htmlspecialchars($_SESSION['user_data']['NAME']) ?>
                            </span>
                            <i class="bi bi-chevron-down" style="font-size:.65rem; opacity:.6;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown mt-2"
                            aria-labelledby="userDropdown">
                            <li>
                                <div class="user-dropdown-header">
                                    <span class="user-avatar-badge-lg">
                                        <i class="bi bi-person-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem; color:var(--color-text);">
                                            <?= htmlspecialchars($_SESSION['user_data']['NAME']) ?>
                                        </div>
                                        <div style="font-size:.75rem; color:var(--color-text-muted);">
                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['user_data']['username'] ?? '-') ?>
                                        </div>
                                        <span class="role-badge text-capitalize">
                                            <i class="bi bi-shield me-1"></i><?= htmlspecialchars($_SESSION['user_data']['role'] ?? 'User') ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider" style="border-color:var(--color-border); margin:.25rem 0;"></li>
                            <li>
                                <a class="dropdown-item logout-item d-flex align-items-center gap-2" href="#" id="logoutBtn">
                                    <i class="bi bi-box-arrow-right"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Mobile: theme toggle selalu terlihat (di luar collapse) -->
        <div class="d-flex d-lg-none align-items-center gap-2 ms-auto">
            <button class="btn-theme-toggle" id="btn-theme-toggle-mobile"
                    title="Ganti tema" aria-label="Toggle tema">
                <i class="bi bi-brightness-high-fill"></i>
            </button>
            <?php if (isset($_SESSION['user_data'])): ?>
                <div class="dropdown">
                    <button class="user-avatar-badge border-0 p-0"
                            style="cursor:pointer;"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Menu user">
                        <i class="bi bi-person-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown mt-2">
                        <li>
                            <div class="user-dropdown-header">
                                <span class="user-avatar-badge-lg">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold" style="font-size:.85rem; color:var(--color-text);">
                                        <?= htmlspecialchars($_SESSION['user_data']['NAME']) ?>
                                    </div>
                                    <div style="font-size:.75rem; color:var(--color-text-muted);">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['user_data']['username'] ?? '-') ?>
                                    </div>
                                    <span class="role-badge text-capitalize">
                                        <i class="bi bi-shield me-1"></i><?= htmlspecialchars($_SESSION['user_data']['role'] ?? 'User') ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider" style="border-color:var(--color-border); margin:.25rem 0;"></li>
                        <li>
                            <a class="dropdown-item logout-item d-flex align-items-center gap-2" href="#" id="logoutBtnMobile">
                                <i class="bi bi-box-arrow-right"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade modal-app" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="logout-modal-icon mb-3">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <h5 class="fw-bold mb-1" style="color:var(--color-text);">Konfirmasi Logout</h5>
                <p class="mb-4" style="color:var(--color-text-muted); font-size:.875rem;">
                    Apakah Anda yakin ingin keluar dari sesi ini?
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn-app-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </button>
                    <button type="button" class="btn-logout-confirm px-4" id="confirmLogoutBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="logoutSpinner"></span>
                        <i class="bi bi-box-arrow-right me-1" id="logoutIcon"></i>Ya, Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_data'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn    = document.getElementById('confirmLogoutBtn');
    const logoutSpinner = document.getElementById('logoutSpinner');
    const logoutIcon    = document.getElementById('logoutIcon');
    const logoutModal   = new bootstrap.Modal(document.getElementById('logoutModal'));

    // Handle semua tombol logout (desktop dan mobile)
    document.querySelectorAll('#logoutBtn, #logoutBtnMobile').forEach(function(btn) {
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.show();
            });
        }
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function() {
            confirmBtn.disabled = true;
            logoutSpinner.classList.remove('d-none');
            logoutIcon.classList.add('d-none');
            try {
                const response = await fetch('/api/auth.php?action=logout', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    window.location.href = '/login.php';
                } else {
                    logoutModal.hide();
                    confirmBtn.disabled = false;
                    logoutSpinner.classList.add('d-none');
                    logoutIcon.classList.remove('d-none');
                }
            } catch (error) {
                logoutModal.hide();
                confirmBtn.disabled = false;
                logoutSpinner.classList.add('d-none');
                logoutIcon.classList.remove('d-none');
            }
        });
    }

    // Sinkronisasi tombol theme mobile & desktop
    const toggleMobile  = document.getElementById('btn-theme-toggle-mobile');
    const toggleDesktop = document.getElementById('btn-theme-toggle');

    if (toggleMobile && toggleDesktop) {
        toggleMobile.addEventListener('click', function() {
            toggleDesktop.click();
        });
        // Sinkronisasi ikon setelah toggle desktop dieksekusi
        const observer = new MutationObserver(function() {
            toggleMobile.querySelector('i').className = toggleDesktop.querySelector('i').className;
        });
        observer.observe(toggleDesktop, { subtree: true, childList: true, characterData: true, attributes: true });
    }
});
</script>
<?php endif; ?>
