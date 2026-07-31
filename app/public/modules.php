<?php
/**
 * modules.php — Halaman Monitoring Modul
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Monitoring Modul';
$activePage = 'modules';

include __DIR__ . '/../templates/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>

        <main class="app-content">

            <div class="page-header d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="page-title">
                        <i class="bi bi-grid-3x3-gap me-2" style="color:var(--color-primary)"></i>Monitoring Modul
                    </h1>
                    <p class="page-subtitle">Statistik penggunaan setiap modul SIMGOS hari ini</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span id="demo-badge" class="demo-badge d-none">
                        <i class="bi bi-exclamation-triangle-fill"></i>Data Demo
                    </span>
                    <button class="btn-app-secondary" id="btn-refresh">
                        <i class="bi bi-arrow-clockwise"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-3" id="modul-summary">
                <div class="col-12 text-center py-3"><span class="spin-sm"></span></div>
            </div>

            <!-- Chart + Table Row -->
            <div class="row g-3">
                <!-- Bar Chart -->
                <div class="col-lg-7">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-bar-chart-fill" style="color:var(--color-primary)"></i>
                                Aktivitas per Modul
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-modul" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Detail Table -->
                <div class="col-lg-5">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-table" style="color:var(--color-warning)"></i>
                                Rincian per Modul
                            </h2>
                        </div>
                        <div class="table-responsive">
                            <table class="app-table">
                                <thead>
                                    <tr>
                                        <th>Modul</th>
                                        <th class="text-end">Dibuat</th>
                                        <th class="text-end">Diubah</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="modul-tbody">
                                    <tr><td colspan="4" class="text-center py-4"><span class="spin-sm"></span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let chart;

    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }
    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    async function loadModules() {
        try {
            const data = await API.get('modules');
            const items = data.data || [];

            if (data._demo) document.getElementById('demo-badge').classList.remove('d-none');

            const total  = items.reduce((s,m)=>s+(m.total||0), 0);
            const create = items.reduce((s,m)=>s+(m.create||0), 0);
            const update = items.reduce((s,m)=>s+(m.update||0), 0);

            // Summary cards
            document.getElementById('modul-summary').innerHTML = `
                <div class="col-xl-4 col-md-4"><div class="stat-card card-teal">
                    <div class="stat-card-icon"><i class="bi bi-layers-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(total)}</div>
                    <div class="stat-card-label">Total Aktivitas</div>
                </div></div>
                <div class="col-xl-4 col-md-4"><div class="stat-card card-create">
                    <div class="stat-card-icon"><i class="bi bi-plus-circle-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(create)}</div>
                    <div class="stat-card-label">Total Dibuat</div>
                </div></div>
                <div class="col-xl-4 col-md-4"><div class="stat-card card-update">
                    <div class="stat-card-icon"><i class="bi bi-pencil-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(update)}</div>
                    <div class="stat-card-label">Total Diubah</div>
                </div></div>
            `;

            // Table
            document.getElementById('modul-tbody').innerHTML = items.map(m => `
                <tr>
                    <td>
                        <span style="font-weight:600">${esc(m.nama)}</span>
                        <small class="d-block cell-mono" style="font-size:.7rem">${esc(m.schema||'')}</small>
                    </td>
                    <td class="text-end"><span class="aksi-badge aksi-C">${fmtNum(m.create)}</span></td>
                    <td class="text-end"><span class="aksi-badge aksi-U">${fmtNum(m.update)}</span></td>
                    <td class="text-end" style="font-weight:700;font-family:ui-monospace,Consolas,monospace">${fmtNum(m.total)}</td>
                </tr>
            `).join('');

            // Chart
            const isDark    = !document.documentElement.getAttribute('data-theme');
            const gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
            const textColor = isDark ? '#94a3b8' : '#5b6b68';

            if (chart) chart.destroy();
            const ctx = document.getElementById('chart-modul').getContext('2d');
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: items.map(m => m.nama),
                    datasets: [
                        { label: 'Dibuat',  data: items.map(m=>m.create), backgroundColor: 'rgba(22,163,74,.75)', borderRadius: 3 },
                        { label: 'Diubah',  data: items.map(m=>m.update), backgroundColor: 'rgba(245,158,11,.75)', borderRadius: 3 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { color: textColor, font: { size: 11 }, usePointStyle: true } }
                    },
                    scales: {
                        x: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } }, beginAtZero: true }
                    }
                }
            });

        } catch(err) {
            document.getElementById('modul-summary').innerHTML =
                `<div class="col-12"><div class="empty-state"><i class="bi bi-exclamation-triangle"></i><b>Gagal memuat data</b>${err.message}</div></div>`;
        }
    }

    document.getElementById('btn-refresh').addEventListener('click', loadModules);
    loadModules();
});
</script>
