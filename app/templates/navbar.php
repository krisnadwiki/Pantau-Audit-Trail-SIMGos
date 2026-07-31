<nav class="navbar navbar-expand-lg navbar-dark app-navbar" id="main-navbar">
    <div class="container-fluid px-4">
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
                <span class="brand-sub">Audit Trail SIMGOS</span>
            </span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
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
    const logoutBtn     = document.getElementById('logoutBtn');
    const confirmBtn    = document.getElementById('confirmLogoutBtn');
    const logoutSpinner = document.getElementById('logoutSpinner');
    const logoutIcon    = document.getElementById('logoutIcon');
    const logoutModal   = new bootstrap.Modal(document.getElementById('logoutModal'));

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.show();
        });
    }

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
});
</script>
<?php endif; ?>
