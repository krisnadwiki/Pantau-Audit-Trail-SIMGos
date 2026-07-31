<?php
/**
 * analytics.php — Halaman Analitik
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Analitik';
$activePage = 'analytics';

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
                        <i class="bi bi-graph-up-arrow me-2" style="color:var(--color-primary)"></i>Analitik
                    </h1>
                    <p class="page-subtitle">Analisis mendalam pola aktivitas audit trail</p>
                </div>
                <span id="demo-badge" class="demo-badge d-none">
                    <i class="bi bi-exclamation-triangle-fill"></i>Data Demo
                </span>
            </div>

            <!-- Period Filter -->
            <div class="filter-bar mb-3">
                <div class="filter-col">
                    <label>Dari Tanggal</label>
                    <input type="date" id="f-dari" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                </div>
                <div class="filter-col">
                    <label>Sampai Tanggal</label>
                    <input type="date" id="f-sampai" value="<?= date('Y-m-d') ?>">
                </div>
                <div style="align-self:flex-end">
                    <button class="filter-btn" id="btn-filter">
                        <i class="bi bi-graph-up"></i>Analisis
                    </button>
                </div>
                <div style="align-self:flex-end;display:flex;gap:.5rem">
                    <button class="filter-btn secondary" onclick="setRange(7)">7 Hari</button>
                    <button class="filter-btn secondary" onclick="setRange(30)">30 Hari</button>
                    <button class="filter-btn secondary" onclick="setRange(90)">90 Hari</button>
                </div>
            </div>

            <!-- Stat Summary Row -->
            <div class="row g-3 mb-3" id="stat-summary">
                <div class="col-12 text-center py-3"><span class="spin-sm"></span></div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row g-3 mb-3">
                <!-- Top User -->
                <div class="col-lg-6">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-trophy-fill" style="color:#f59e0b"></i>
                                Top 10 User Teraktif
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-top-user" height="240"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Modul -->
                <div class="col-lg-6">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-layers-fill" style="color:var(--color-primary)"></i>
                                Top 10 Modul Teraktif
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-top-modul" height="240"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row g-3">
                <!-- Jam Tersibuk -->
                <div class="col-lg-8">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-clock-history" style="color:var(--color-info)"></i>
                                Pola Jam Aktivitas
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-per-jam" height="180"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Insight Cards -->
                <div class="col-lg-4">
                    <div class="app-card h-100">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-lightbulb-fill" style="color:#f59e0b"></i>
                                Insight
                            </h2>
                        </div>
                        <div class="app-card-body" id="insight-panel">
                            <div class="text-center py-3"><span class="spin-sm"></span></div>
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
    let charts = {};

    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }

    window.setRange = function(days) {
        const today = new Date();
        const from  = new Date(today.getTime() - days * 86400000);
        document.getElementById('f-sampai').value = today.toISOString().substring(0,10);
        document.getElementById('f-dari').value   = from.toISOString().substring(0,10);
        loadAll();
    };

    function destroyChart(key) { if (charts[key]) { charts[key].destroy(); delete charts[key]; } }

    function makeChart(id, type, labels, datasets, opts = {}) {
        destroyChart(id);
        const isDark    = !document.documentElement.getAttribute('data-theme');
        const gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
        const textColor = isDark ? '#94a3b8' : '#5b6b68';

        charts[id] = new Chart(document.getElementById(id).getContext('2d'), {
            type,
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: opts.horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: opts.showLegend || false, labels: { color: textColor, font: { size: 10 }, usePointStyle: true } }
                },
                scales: type === 'doughnut' ? {} : {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 }, maxRotation: opts.horizontal ? 0 : 45 } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    async function loadAll() {
        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;

        document.getElementById('stat-summary').innerHTML = '<div class="col-12 text-center py-3"><span class="spin-sm"></span></div>';
        document.getElementById('insight-panel').innerHTML = '<div class="text-center py-3"><span class="spin-sm"></span></div>';        try {
            const res = await API.get('analytic_range', { dari, sampai });

            if (res._demo) document.getElementById('demo-badge').classList.remove('d-none');

            const d = res.data || {};

            // Summary
            document.getElementById('stat-summary').innerHTML = `
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-teal">
                    <div class="stat-card-icon"><i class="bi bi-activity"></i></div>
                    <div class="stat-card-value">${fmtNum(d.total_aktivitas)}</div>
                    <div class="stat-card-label">Total Aktivitas</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-primary">
                    <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(d.total_user_aktif)}</div>
                    <div class="stat-card-label">User Aktif</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-update">
                    <div class="stat-card-icon"><i class="bi bi-calendar-check-fill"></i></div>
                    <div class="stat-card-value">${esc(d.hari_tersibuk||'—')}</div>
                    <div class="stat-card-label">Hari Tersibuk</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-create">
                    <div class="stat-card-icon"><i class="bi bi-clock-fill"></i></div>
                    <div class="stat-card-value" style="font-size:1.3rem">${esc(d.jam_tersibuk||'—')}</div>
                    <div class="stat-card-label">Jam Tersibuk</div>
                </div></div>
            `;

            // Top User Chart (horizontal bar)
            const topUsers = d.top_user || [];
            makeChart('chart-top-user', 'bar', topUsers.map(u=>u.nama||'User #'+u.id), [{
                label: 'Aktivitas', data: topUsers.map(u=>u.total),
                backgroundColor: 'rgba(15,118,110,.75)', borderRadius: 4,
            }], { horizontal: true });

            // Top Modul Chart (horizontal bar)
            const topModul = d.top_modul || [];
            makeChart('chart-top-modul', 'bar', topModul.map(m=>m.nama||m.tabel||'Modul'), [{
                label: 'Aktivitas', data: topModul.map(m=>m.total),
                backgroundColor: 'rgba(6,182,212,.75)', borderRadius: 4,
            }], { horizontal: true });

            // Per Jam Chart
            const perJam = d.per_jam || {};
            makeChart('chart-per-jam', 'line',
                Array.from({length:24}, (_,i)=>i.toString().padStart(2,'0')+':00'),
                [{
                    label: 'Aktivitas',
                    data: Array.from({length:24}, (_,i)=>perJam[i]||0),
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15,118,110,.1)',
                    fill: true,
                    tension: .4,
                    pointRadius: 3,
                }],
                { showLegend: false }
            );

            // Insight panel
            const dist = d.distribusi || {};
            const totalDist = (dist.C||0) + (dist.U||0);
            const pctU = totalDist > 0 ? Math.round((dist.U||0)/totalDist*100) : 0;

            document.getElementById('insight-panel').innerHTML = `
                <div class="mb-3">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-subtle);margin-bottom:.5rem">Aksi Terbanyak</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--color-text)">${esc(d.aksi_terbanyak||'Diubah')}</div>
                </div>
                <div class="mb-3">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-subtle);margin-bottom:.5rem">Modul Teraktif</div>
                    <div style="font-size:1rem;font-weight:700;color:var(--color-primary)">${esc(d.modul_teraktif||'—')}</div>
                </div>
                <div class="mb-3">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-subtle);margin-bottom:.5rem">Rata-rata / Hari</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--color-text)">${fmtNum(d.rata_per_hari||0)}</div>
                </div>
                <div>
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--color-text-subtle);margin-bottom:.5rem">Distribusi Aksi</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="aksi-badge aksi-C">Dibuat: ${fmtNum(dist.C||0)}</span>
                        <span class="aksi-badge aksi-U">Diubah: ${fmtNum(dist.U||0)}</span>
                    </div>
                    <div style="margin-top:.5rem;font-size:.75rem;color:var(--color-text-muted)">
                        ${pctU}% aktivitas adalah perubahan data
                    </div>
                </div>
            `;

        } catch(err) {
            document.getElementById('stat-summary').innerHTML =
                `<div class="col-12"><div class="empty-state"><i class="bi bi-exclamation-triangle"></i><b>Gagal memuat</b>${esc(err.message)}</div></div>`;
        }      
    }

    document.getElementById('btn-filter').addEventListener('click', loadAll);
    loadAll();
});
</script>
