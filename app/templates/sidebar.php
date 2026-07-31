<?php
/**
 * sidebar.php — Sidebar navigasi utama
 * Desktop: fixed sidebar
 * Mobile:  Bootstrap Offcanvas drawer
 */
?>

<!-- ── Desktop Sidebar (fixed, hidden on mobile) ── -->
<aside class="app-sidebar d-none d-lg-flex" id="app-sidebar">
    <?php include __DIR__ . '/sidebar-nav.php'; ?>
</aside>

<!-- ── Mobile Sidebar (Bootstrap Offcanvas) ── -->
<div class="offcanvas offcanvas-start app-offcanvas-sidebar"
     tabindex="-1"
     id="mobileSidebar"
     aria-labelledby="mobileSidebarLabel">

    <div class="offcanvas-header border-bottom" style="border-color:var(--color-border) !important; padding:.85rem 1rem;">
        <!-- Brand di header offcanvas -->
        <a href="/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="brand-logo-wrap" style="width:28px; height:28px; padding:4px;">
                <img src="/assets/image/pantau_logo.png"
                     alt="PANTAU"
                     class="brand-logo-icon"
                     width="20" height="20">
            </span>
            <span class="brand-text">
                <span class="brand-name" style="font-size:.8rem;">PANTAU</span>
                <span class="brand-sub" style="font-size:.55rem;">Audit Trail SIMGOS</span>
            </span>
        </a>
        <button type="button"
                class="btn-offcanvas-close"
                data-bs-dismiss="offcanvas"
                aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="offcanvas-body p-0" style="overflow-y:auto;">
        <?php include __DIR__ . '/sidebar-nav.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileSidebarEl = document.getElementById('mobileSidebar');
    if (mobileSidebarEl) {
        mobileSidebarEl.querySelectorAll('.sidebar-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const instance = bootstrap.Offcanvas.getInstance(mobileSidebarEl);
                if (instance) {
                    instance.hide();
                }
            });
        });
    }
});
</script>
