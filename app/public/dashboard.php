<?php
/**
 * dashboard.php — Dashboard PANTAU
 * KPI + Grafik Audit Trail Hari Ini
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

include __DIR__ . '/../templates/header.php';
?>

<div class="app-wrapper">

    <?php include __DIR__ . '/../templates/sidebar.php'; ?>

    <div class="app-main">

        <?php include __DIR__ . '/../templates/navbar.php'; ?>

        <main class="app-content">

            <!-- Page Header -->
            <div class="page-header d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="page-title">
                        <i class="bi bi-speedometer2 me-2" style="color:var(--color-primary)"></i>Dashboard
                    </h1>
                    <p class="page-subtitle">Ringkasan aktivitas audit trail SIMGOS hari ini &mdash; <?= date('d F Y') ?></p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span id="demo-badge-dash" class="demo-badge d-none">
                        <i class="bi bi-exclamation-triangle-fill"></i>Data Demo
                    </span>
                    <button class="btn-app-secondary" id="btn-refresh">
                        <i class="bi bi-arrow-clockwise"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- KPI Cards Row 1 -->
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card card-teal">
                        <div class="stat-card-icon"><i class="bi bi-activity"></i></div>
                        <div class="stat-card-value" id="kpi-total">—</div>
                        <div class="stat-card-label">Total Aktivitas Hari Ini</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card card-primary">
                        <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-card-value" id="kpi-user">—</div>
                        <div class="stat-card-label">User Aktif</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card card-create">
                        <div class="stat-card-icon"><i class="bi bi-plus-circle-fill"></i></div>
                        <div class="stat-card-value" id="kpi-create">—</div>
                        <div class="stat-card-label">Total Dibuat</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card card-update">
                        <div class="stat-card-icon"><i class="bi bi-pencil-fill"></i></div>
                        <div class="stat-card-value" id="kpi-update">—</div>
                        <div class="stat-card-label">Total Diubah</div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row 2 (Shortcuts) -->
            <div class="row g-3 mb-3">
                <div class="col-xl-12 col-md-12">
                    <!-- Shortcut links -->
                    <div class="app-card h-100">
                        <div class="app-card-body d-flex gap-3 align-items-center flex-wrap" style="padding:.85rem 1.25rem">
                            <a href="/audit.php" class="btn-app-primary btn-app-sm">
                                <i class="bi bi-shield-check"></i>Audit Trail
                            </a>
                            <a href="/user-activity.php" class="btn-app-secondary btn-app-sm">
                                <i class="bi bi-person-lines-fill"></i>Aktivitas User
                            </a>
                            <a href="/analytics.php" class="btn-app-secondary btn-app-sm">
                                <i class="bi bi-graph-up-arrow"></i>Analitik
                            </a>
                            <a href="/export.php" class="btn-app-secondary btn-app-sm">
                                <i class="bi bi-download"></i>Export
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-3">
                <!-- Aktivitas Per Jam -->
                <div class="col-lg-8">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-clock-history" style="color:var(--color-primary)"></i>
                                Aktivitas per Jam
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-per-jam" height="140"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Distribusi Jenis -->
                <div class="col-lg-4">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-pie-chart-fill" style="color:var(--color-info)"></i>
                                Distribusi Aksi
                            </h2>
                        </div>
                        <div class="app-card-body d-flex align-items-center justify-content-center">
                            <canvas id="chart-distribusi" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Panels -->
            <div class="row g-3">
                <!-- Top Modul -->
                <div class="col-lg-6">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-grid-3x3-gap" style="color:var(--color-warning)"></i>
                                Top Modul
                            </h2>
                            <a href="/modules.php" class="btn-app-secondary btn-app-sm">Semua</a>
                        </div>
                        <div class="app-card-body" id="panel-top-modul">
                            <div class="text-center py-3"><span class="spin-sm"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Top User -->
                <div class="col-lg-6">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-person-badge-fill" style="color:var(--color-success)"></i>
                                Top User
                            </h2>
                            <a href="/user-activity.php" class="btn-app-secondary btn-app-sm">Semua</a>
                        </div>
                        <div class="app-card-body" id="panel-top-user">
                            <div class="text-center py-3"><span class="spin-sm"></span></div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>

    </div><!-- .app-main -->

</div><!-- .app-wrapper -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let chartJam, chartDist;

    function fmtNum(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function esc(s)    { return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    function renderKpiBar(containerId, items, maxN) {
        const c = document.getElementById(containerId);
        if (!items || !items.length) {
            c.innerHTML = '<div class="text-muted small py-2">Belum ada data.</div>';
            return;
        }
        const peak = maxN || Math.max(...items.map(i => i.total));
        c.innerHTML = items.map(m => `
            <div class="kpi-bar">
                <div class="kpi-bar-head">
                    <span>${esc(m.nama)}</span>
                    <b>${fmtNum(m.total)}</b>
                </div>
                <div class="kpi-bar-track">
                    <div class="kpi-bar-fill" style="width:${Math.max(2,Math.round(m.total/peak*100))}%"></div>
                </div>
            </div>
        `).join('');
    }

    function buildCharts(perJam, distribusi) {
        const isDark = !document.documentElement.getAttribute('data-theme');
        const gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
        const textColor = isDark ? '#94a3b8' : '#5b6b68';

        // Per Jam Bar Chart
        const ctxJam = document.getElementById('chart-per-jam').getContext('2d');
        if (chartJam) chartJam.destroy();
        const labels = Array.from({length:24}, (_,i) => i.toString().padStart(2,'0')+':00');
        const data   = Array.from({length:24}, (_,i) => perJam[i] || 0);

        chartJam = new Chart(ctxJam, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Aktivitas',
                    data,
                    backgroundColor: 'rgba(15,118,110,.65)',
                    borderColor:     '#0f766e',
                    borderWidth: 1,
                    borderRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 }, maxRotation: 0 } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });

        // Distribusi Donut
        const ctxDist = document.getElementById('chart-distribusi').getContext('2d');
        if (chartDist) chartDist.destroy();
        chartDist = new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: ['Dibuat', 'Diubah'],
                datasets: [{
                    data: [distribusi.C || 0, distribusi.U || 0],
                    backgroundColor: ['rgba(22,163,74,.8)', 'rgba(245,158,11,.8)'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#fff',
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: textColor, padding: 14, font: { size: 11 }, usePointStyle: true }
                    }
                }
            }
        });
    }

    function showPanelError(containerId, msg) {
        const c = document.getElementById(containerId);
        if (c) {
            c.innerHTML = `<div class="text-center py-3" style="color:var(--color-danger);font-size:.82rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>${msg}
            </div>`;
        }
    }

    async function loadDashboard() {
        // Reset panels to loading state
        ['panel-top-modul','panel-top-user'].forEach(id => {
            const c = document.getElementById(id);
            if (c) c.innerHTML = '<div class="text-center py-3"><span class="spin-sm"></span></div>';
        });

        try {
            const data = await API.get('dashboard');
            const d = data.data || {};

            document.getElementById('kpi-total').textContent  = fmtNum(d.total_aktivitas);
            document.getElementById('kpi-user').textContent   = fmtNum(d.total_user_aktif);
            document.getElementById('kpi-create').textContent = fmtNum(d.total_create);
            document.getElementById('kpi-update').textContent = fmtNum(d.total_update);

            renderKpiBar('panel-top-modul', d.top_modul);
            renderKpiBar('panel-top-user',  d.top_user);
            buildCharts(d.per_jam || {}, d.distribusi || {});

            if (data._demo) {
                document.getElementById('demo-badge-dash').classList.remove('d-none');
            }
        } catch (err) {
            console.error('Dashboard load error:', err);
            // Jika sesi habis/401 — redirect ke login
            if (err.message && (err.message.includes('401') || err.message.toLowerCase().includes('unauthorized'))) {
                window.location.href = '/login.php';
                return;
            }
            // Tampilkan error state agar UI tidak terlihat loading selamanya
            ['kpi-total','kpi-user','kpi-create','kpi-update'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '—';
            });
            showPanelError('panel-top-modul', 'Gagal memuat data');
            showPanelError('panel-top-user',  'Gagal memuat data');
        }
    }

    loadDashboard();
    document.getElementById('btn-refresh').addEventListener('click', loadDashboard);
});
</script>

<?php
// Close footer already in template
?>
