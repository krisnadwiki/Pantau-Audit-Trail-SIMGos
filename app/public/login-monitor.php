<?php
/**
 * login-monitor.php — Halaman Monitor Login Pengguna
 *
 * Memantau aktivitas login pengguna dari tabel pengguna_log.
 * Berisi KPI Cards, Grafik Login per Jam, Top User Login, dan Tabel Log Login.
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Login Monitor';
$activePage = 'login-monitor';

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
                        <i class="bi bi-person-check-fill me-2" style="color:var(--color-primary)"></i>Login Monitor
                    </h1>
                    <p class="page-subtitle">Pemantauan aktivitas login pengguna SIMGOS secara real-time</p>
                </div>
                <span id="demo-badge" class="demo-badge d-none">
                    <i class="bi bi-exclamation-triangle-fill"></i>Data Demo
                </span>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar mb-3">
                <div class="filter-col">
                    <label>Dari Tanggal</label>
                    <input type="date" id="f-dari" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-col">
                    <label>Sampai Tanggal</label>
                    <input type="date" id="f-sampai" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-col">
                    <label>Cari User</label>
                    <input type="text" id="f-user" placeholder="Nama atau username...">
                </div>
                <div class="filter-col">
                    <label>Filter IP</label>
                    <input type="text" id="f-ip" placeholder="192.168...">
                </div>
                <div style="align-self:flex-end">
                    <button class="filter-btn" id="btn-filter">
                        <i class="bi bi-search"></i>Terapkan
                    </button>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-3" id="stat-summary">
                <div class="col-12 text-center py-3"><span class="spin-sm"></span></div>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-3">
                <!-- Grafik Login per Jam -->
                <div class="col-lg-7">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-clock-history" style="color:var(--color-primary)"></i>
                                Login per Jam
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-login-jam" height="220"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top User Login -->
                <div class="col-lg-5">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-trophy-fill" style="color:#f59e0b"></i>
                                Top User Login
                            </h2>
                        </div>
                        <div class="app-card-body" style="padding:1rem">
                            <canvas id="chart-top-login-user" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="app-card">
                <div class="app-card-header d-flex justify-content-between align-items-center">
                    <h2 class="app-card-title">
                        <i class="bi bi-list-columns-reverse" style="color:var(--color-primary)"></i>
                        Log Riwayat Login
                    </h2>
                    <span id="page-info" style="font-size:0.8rem;color:var(--color-text-muted)"></span>
                </div>
                <div class="table-responsive">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th style="width:160px">Waktu</th>
                                <th>Nama User</th>
                                <th>Login / Username</th>
                                <th>IP Asal</th>
                                <th>Tujuan</th>
                                <th>Browser / User Agent</th>
                            </tr>
                        </thead>
                        <tbody id="log-tbody">
                            <tr>
                                <td colspan="6" class="text-center py-4"><span class="spin-sm"></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="app-card-footer d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding:0.75rem 1rem">
                    <div id="pager-info" style="font-size:0.82rem;color:var(--color-text-subtle)"></div>
                    <div class="d-flex gap-1" id="pager-btns"></div>
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
    let currentPage = 1;

    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }

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
                    legend: { display: opts.showLegend || false, labels: { color: textColor, font: { size: 10 } } }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    async function loadStat() {
        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;

        try {
            const res = await API.get('login_stat', { dari, sampai });
            if (res._demo) document.getElementById('demo-badge').classList.remove('d-none');

            const s = res.data || {};

            document.getElementById('stat-summary').innerHTML = `
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-teal">
                    <div class="stat-card-icon"><i class="bi bi-door-open-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(s.total_login)}</div>
                    <div class="stat-card-label">Total Login</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-primary">
                    <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(s.user_unik)}</div>
                    <div class="stat-card-label">User Unik</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-update">
                    <div class="stat-card-icon"><i class="bi bi-laptop-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(s.ip_unik)}</div>
                    <div class="stat-card-label">IP Unik</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-create">
                    <div class="stat-card-icon"><i class="bi bi-clock-fill"></i></div>
                    <div class="stat-card-value" style="font-size:1.3rem">${esc(s.jam_puncak||'—')}</div>
                    <div class="stat-card-label">Jam Puncak Login</div>
                </div></div>
            `;

            // Chart Login per Jam
            const perJam = s.per_jam || {};
            makeChart('chart-login-jam', 'line',
                Array.from({length:24}, (_,i)=>i.toString().padStart(2,'0')+':00'),
                [{
                    label: 'Login',
                    data: Array.from({length:24}, (_,i)=>perJam[i]||0),
                    borderColor: '#0f766e',
                    backgroundColor: 'rgba(15,118,110,.1)',
                    fill: true,
                    tension: .4,
                    pointRadius: 3,
                }],
                { showLegend: false }
            );

            // Chart Top User Login
            const topUsers = s.top_user || [];
            makeChart('chart-top-login-user', 'bar', topUsers.map(u=>u.nama||u.login||'User'), [{
                label: 'Jumlah Login', data: topUsers.map(u=>u.total),
                backgroundColor: 'rgba(245,158,11,.75)', borderRadius: 4,
            }], { horizontal: true });

        } catch(err) {
            document.getElementById('stat-summary').innerHTML =
                `<div class="col-12"><div class="empty-state"><i class="bi bi-exclamation-triangle"></i><b>Gagal memuat statistik</b> ${esc(err.message)}</div></div>`;
        }
    }

    async function loadLog(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('log-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><span class="spin-sm"></span></td></tr>';

        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;
        const user   = document.getElementById('f-user').value.trim();
        const ip     = document.getElementById('f-ip').value.trim();

        try {
            const res = await API.get('login_log', {
                dari, sampai, user, ip, page, per_page: 50
            });

            if (res._demo) document.getElementById('demo-badge').classList.remove('d-none');

            const rows = res.data || [];
            const meta = res.meta || {};

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><b>Tidak ada log login</b></div></td></tr>';
                renderPager({ total: 0, page: 1, total_pages: 1 });
                return;
            }

            tbody.innerHTML = rows.map(r => `<tr>
                <td class="cell-waktu">
                    <b>${esc(r.tanggal ? r.tanggal.substring(11,19) : '')}</b>
                    <span>${esc(r.tanggal ? r.tanggal.substring(0,10).split('-').reverse().join('/') : '')}</span>
                </td>
                <td><b>${esc(r.nama || '—')}</b></td>
                <td><span class="user-badge"><i class="bi bi-person me-1"></i>${esc(r.login || '—')}</span></td>
                <td class="cell-mono">${esc(r.ip_asal || '—')}</td>
                <td class="cell-mono">${esc(r.tujuan || '—')}</td>
                <td style="font-size:0.78rem;color:var(--color-text-subtle);max-width:250px" class="text-truncate" title="${esc(r.agent || '')}">
                    ${esc(r.agent || '—')}
                </td>
            </tr>`).join('');

            renderPager(meta);

        } catch(err) {
            tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="bi bi-exclamation-triangle"></i><b>Gagal memuat log</b> ' + esc(err.message) + '</div></td></tr>';
        }
    }

    function renderPager(meta) {
        const total    = meta.total || 0;
        const page     = meta.page  || 1;
        const perPage  = meta.per_page || 50;
        const totalPgs = meta.total_pages || 1;
        const from     = total > 0 ? (page - 1) * perPage + 1 : 0;
        const to       = Math.min(page * perPage, total);

        document.getElementById('page-info').textContent =
            total > 0 ? (`Menampilkan ${fmtNum(from)}-${fmtNum(to)} dari ${fmtNum(total)}`) : '';

        document.getElementById('pager-info').textContent =
            total > 0 ? `Halaman ${page} dari ${totalPgs}` : '';

        const btns = document.getElementById('pager-btns');
        btns.innerHTML = '';

        function mkBtn(label, pg, disabled, active) {
            const b = document.createElement('button');
            b.className = 'filter-btn secondary' + (active ? ' active' : '');
            b.style.minWidth = '34px';
            b.style.padding = '0.2rem 0.5rem';
            b.disabled = !!disabled;
            b.innerHTML = label;
            if (!disabled) b.addEventListener('click', function(){ loadLog(pg); });
            return b;
        }

        btns.appendChild(mkBtn('<i class="bi bi-chevron-left"></i>', page - 1, page <= 1, false));

        const startPg = Math.max(1, page - 2);
        const endPg   = Math.min(totalPgs, startPg + 4);
        for (let p = startPg; p <= endPg; p++) {
            btns.appendChild(mkBtn(p, p, false, p === page));
        }

        btns.appendChild(mkBtn('<i class="bi bi-chevron-right"></i>', page + 1, page >= totalPgs, false));
    }

    async function loadAll() {
        currentPage = 1;
        await Promise.all([loadStat(), loadLog(1)]);
    }

    document.getElementById('btn-filter').addEventListener('click', loadAll);
    ['f-dari','f-sampai','f-user','f-ip'].forEach(function(id) {
        document.getElementById(id).addEventListener('keydown', function(e) {
            if (e.key === 'Enter') loadAll();
        });
    });

    loadAll();
});
</script>
