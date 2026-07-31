<aside class="app-sidebar" id="app-sidebar">
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">MONITORING</div>

        <a href="/dashboard.php"
           class="sidebar-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>"
           id="sidebar-dashboard">
            <i class="bi bi-speedometer2 sidebar-icon"></i>
            <span>Dashboard</span>
        </a>

        <a href="/audit.php"
           class="sidebar-link <?= ($activePage ?? '') === 'audit' ? 'active' : '' ?>"
           id="sidebar-audit">
            <i class="bi bi-shield-check sidebar-icon"></i>
            <span>Audit Trail</span>
        </a>

        <a href="/user-activity.php"
           class="sidebar-link <?= ($activePage ?? '') === 'user-activity' ? 'active' : '' ?>"
           id="sidebar-user-activity">
            <i class="bi bi-person-lines-fill sidebar-icon"></i>
            <span>Aktivitas User</span>
        </a>

        <a href="/login-monitor.php"
           class="sidebar-link <?= ($activePage ?? '') === 'login-monitor' ? 'active' : '' ?>"
           id="sidebar-login-monitor">
            <i class="bi bi-person-check sidebar-icon"></i>
            <span>Login Monitor</span>
        </a>

        <a href="/modules.php"
           class="sidebar-link <?= ($activePage ?? '') === 'modules' ? 'active' : '' ?>"
           id="sidebar-modules">
            <i class="bi bi-grid-3x3-gap sidebar-icon"></i>
            <span>Monitoring Modul</span>
        </a>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">ANALITIK & TOOLS</div>

        <a href="/analytics.php"
           class="sidebar-link <?= ($activePage ?? '') === 'analytics' ? 'active' : '' ?>"
           id="sidebar-analytics">
            <i class="bi bi-graph-up-arrow sidebar-icon"></i>
            <span>Analitik</span>
        </a>

        <a href="/search.php"
           class="sidebar-link <?= ($activePage ?? '') === 'search' ? 'active' : '' ?>"
           id="sidebar-search">
            <i class="bi bi-search sidebar-icon"></i>
            <span>Pencarian</span>
        </a>

        <a href="/export.php"
           class="sidebar-link <?= ($activePage ?? '') === 'export' ? 'active' : '' ?>"
           id="sidebar-export">
            <i class="bi bi-download sidebar-icon"></i>
            <span>Export</span>
        </a>

        <div class="sidebar-divider"></div>

        <a href="/settings.php"
           class="sidebar-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>"
           id="sidebar-settings">
            <i class="bi bi-gear sidebar-icon"></i>
            <span>Pengaturan</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-version">
            <img src="/assets/image/pantau_logo.png"
                 alt="PANTAU"
                 class="sidebar-logo-img"
                 width="16" height="16"
                 style="object-fit:contain;">
            PANTAU v<?= APP_VERSION ?>
        </div>
    </div>
</aside>
