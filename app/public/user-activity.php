<?php
/**
 * user-activity.php — Halaman Aktivitas User
 *
 * Histori, statistik, dan analisis aktivitas per pengguna SIMGOS.
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Aktivitas User';
$activePage = 'user-activity';

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
                        <i class="bi bi-person-lines-fill me-2" style="color:var(--color-primary)"></i>Aktivitas User
                    </h1>
                    <p class="page-subtitle">Histori dan analisis statistik aktivitas setiap pengguna SIMGOS</p>
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
                <div class="filter-col" style="min-width:200px">
                    <label>Cari User</label>
                    <input type="text" id="f-user" placeholder="Nama, username, NIP...">
                </div>
                <div class="filter-col" style="min-width:160px">
                    <label>Urutkan</label>
                    <select id="f-sort">
                        <option value="total_desc">Aktivitas Terbanyak</option>
                        <option value="latest">Aktivitas Terbaru</option>
                        <option value="nama_asc">Nama (A-Z)</option>
                        <option value="total_asc">Aktivitas Terkecil</option>
                    </select>
                </div>
                <div style="align-self:flex-end">
                    <button class="filter-btn" id="btn-filter">
                        <i class="bi bi-search"></i>Cari
                    </button>
                </div>
                <div style="align-self:flex-end;display:flex;gap:.35rem">
                    <button class="filter-btn secondary" onclick="setRange(0)">Hari Ini</button>
                    <button class="filter-btn secondary" onclick="setRange(7)">7 Hari</button>
                    <button class="filter-btn secondary" onclick="setRange(30)">30 Hari</button>
                </div>
            </div>

            <!-- KPI Summary Row -->
            <div class="row g-3 mb-3" id="stat-summary">
                <div class="col-12 text-center py-3"><span class="spin-sm"></span></div>
            </div>

            <!-- User Cards Grid -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 style="font-size:1rem;font-weight:700;color:var(--color-text);margin:0">
                    Daftar Pengguna (<span id="user-count">0</span>)
                </h3>
            </div>
            <div id="user-grid" class="row g-3 mb-4">
                <div class="col-12 text-center py-4"><span class="spin-sm"></span></div>
            </div>

            <!-- Detail Panel -->
            <div id="user-detail-panel" class="app-card" style="display:none">
                <div class="app-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h2 class="app-card-title mb-1" id="detail-user-name">
                            <i class="bi bi-person-fill" style="color:var(--color-primary)"></i>
                            Detail Aktivitas
                        </h2>
                        <div id="detail-user-meta" style="font-size:0.78rem;color:var(--color-text-muted)"></div>
                    </div>

                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="filter-col" style="min-width:130px">
                            <select id="detail-aksi">
                                <option value="">Semua Aksi</option>
                                <option value="C">Dibuat (C)</option>
                                <option value="U">Diubah (U)</option>
                                <option value="D">Dihapus (D)</option>
                            </select>
                        </div>

                        <a id="btn-export-user" href="#" target="_blank" class="filter-btn secondary" title="Export Log User ke CSV">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </a>

                        <button class="filter-btn secondary" id="btn-close-detail" title="Tutup Detail">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- User Stat Chips inside Detail -->
                <div class="p-3 border-bottom d-flex gap-3 flex-wrap bg-light-subtle" id="detail-stat-chips" style="background:var(--color-bg-alt);font-size:0.82rem">
                    <div><span class="text-muted">Total Akses:</span> <b id="d-total">0</b></div>
                    <div><span class="text-muted">Dibuat:</span> <b id="d-create" class="text-teal">0</b></div>
                    <div><span class="text-muted">Diubah:</span> <b id="d-update" class="text-primary">0</b></div>
                    <div><span class="text-muted">Dihapus:</span> <b id="d-delete" class="text-danger">0</b></div>
                </div>

                <div class="table-responsive">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th style="width:160px">Waktu</th>
                                <th>Modul</th>
                                <th>Aksi</th>
                                <th>Record ID / Ref</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="detail-tbody">
                            <tr><td colspan="5" class="text-center py-3"><span class="spin-sm"></span></td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Detail Footer / Pagination -->
                <div class="app-card-footer d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding:0.75rem 1rem">
                    <div id="pager-info" style="font-size:0.82rem;color:var(--color-text-subtle)"></div>
                    <div class="d-flex gap-1" id="pager-btns"></div>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>
        <?php include __DIR__ . '/../templates/modal-rincian.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const AKSI_NAMA = { C: 'Dibuat', U: 'Diubah', D: 'Dihapus' };
    let selectedUserId   = null;
    let selectedUserName = '';
    let detailPage       = 1;

    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }

    function aksiHtml(a) {
        return `<span class="aksi-badge aksi-${esc(a)}">${esc(AKSI_NAMA[a]||a)}</span>`;
    }

    function timeSince(dateStr) {
        if (!dateStr) return '—';
        const diff = Date.now() - new Date(dateStr).getTime();
        if (isNaN(diff) || diff < 0) return '—';
        const mins = Math.floor(diff/60000);
        if (mins < 1) return 'Baru saja';
        if (mins < 60) return `${mins} menit lalu`;
        const hrs = Math.floor(mins/60);
        if (hrs < 24) return `${hrs} jam lalu`;
        return `${Math.floor(hrs/24)} hari lalu`;
    }

    function getInitials(name) {
        if (!name) return 'U';
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
        return name.substring(0, 2).toUpperCase();
    }

    window.setRange = function(days) {
        const today = new Date();
        document.getElementById('f-sampai').value = today.toISOString().substring(0,10);
        if (days === 0) {
            document.getElementById('f-dari').value = today.toISOString().substring(0,10);
        } else {
            const from = new Date(today.getTime() - days * 86400000);
            document.getElementById('f-dari').value = from.toISOString().substring(0,10);
        }
        loadUsers();
    };

    async function loadUsers() {
        const grid = document.getElementById('user-grid');
        grid.innerHTML = '<div class="col-12 text-center py-4"><span class="spin-sm"></span></div>';
        document.getElementById('stat-summary').innerHTML = '<div class="col-12 text-center py-3"><span class="spin-sm"></span></div>';

        const q      = document.getElementById('f-user').value.trim();
        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;
        const sort   = document.getElementById('f-sort').value;

        try {
            const data = await API.get('users', { q, dari, sampai, sort });
            const users = data.data || [];

            if (data._demo) document.getElementById('demo-badge').classList.remove('d-none');

            document.getElementById('user-count').textContent = fmtNum(users.length);

            if (!users.length) {
                grid.innerHTML = '<div class="col-12"><div class="empty-state"><i class="bi bi-people"></i><b>Tidak ada data pengguna</b><p style="font-size:.82rem">Coba ubah kata kunci atau rentang tanggal pencarian.</p></div></div>';
                document.getElementById('stat-summary').innerHTML = '';
                return;
            }

            const totalAct  = users.reduce((s,u)=>s+(u.total_aktivitas||0), 0);
            const peakAct   = Math.max(...users.map(u=>u.total_aktivitas||0)) || 1;
            const topUserObj = users.find(u=>(u.total_aktivitas||0) === peakAct);
            const avgAct    = users.length > 0 ? Math.round(totalAct / users.length) : 0;

            // Render KPI Summary Cards
            document.getElementById('stat-summary').innerHTML = `
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-teal">
                    <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-card-value">${fmtNum(users.length)}</div>
                    <div class="stat-card-label">User Aktif Terdata</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-primary">
                    <div class="stat-card-icon"><i class="bi bi-activity"></i></div>
                    <div class="stat-card-value">${fmtNum(totalAct)}</div>
                    <div class="stat-card-label">Total Aktivitas Periode Ini</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-update">
                    <div class="stat-card-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div class="stat-card-value" style="font-size:1.1rem">${esc(topUserObj ? topUserObj.nama : '—')}</div>
                    <div class="stat-card-label">Teraktif (${fmtNum(peakAct)} aks)</div>
                </div></div>
                <div class="col-xl-3 col-sm-6"><div class="stat-card card-create">
                    <div class="stat-card-icon"><i class="bi bi-calculator"></i></div>
                    <div class="stat-card-value">${fmtNum(avgAct)}</div>
                    <div class="stat-card-label">Rata-rata / User</div>
                </div></div>
            `;

            // Render User Cards Grid
            grid.innerHTML = users.map(u => {
                const totalU = u.total_aktivitas || 0;
                const pctTotal = totalAct > 0 ? Math.round(totalU / totalAct * 100) : 0;
                const pctBar = Math.max(2, Math.round(totalU / peakAct * 100));

                return `
                <div class="col-xl-4 col-md-6">
                    <div class="app-card user-card" onclick="pilihUser(${JSON.stringify(u).replace(/"/g, '&quot;')})" style="cursor:pointer;transition:box-shadow .2s,transform .2s">
                        <div class="app-card-body">
                            <div class="d-flex align-items-start gap-3 mb-2">
                                <div style="width:46px;height:46px;border-radius:50%;background:rgba(15,118,110,.12);border:1.5px solid rgba(15,118,110,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:1.05rem;color:var(--color-primary)">
                                    ${esc(getInitials(u.nama))}
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:700;font-size:.9rem;color:var(--color-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(u.nama)}">
                                        ${esc(u.nama)}
                                    </div>
                                    <div style="font-size:.73rem;color:var(--color-text-muted)" class="text-truncate">
                                        @${esc(u.username||'—')} &middot; <span class="badge bg-secondary-subtle text-secondary" style="font-size:.68rem">${esc(u.role||'Pengguna')}</span>
                                    </div>
                                </div>
                                <div class="text-end" style="flex-shrink:0">
                                    <span style="font-family:ui-monospace,Consolas,monospace;font-size:1.25rem;font-weight:700;color:var(--color-primary)">
                                        ${fmtNum(totalU)}
                                    </span>
                                    <div style="font-size:.68rem;color:var(--color-text-subtle)">aktivitas</div>
                                </div>
                            </div>

                            <!-- Progress Track -->
                            <div class="kpi-bar-track mb-2">
                                <div class="kpi-bar-fill" style="width:${pctBar}%"></div>
                            </div>

                            <!-- Action breakdown badges -->
                            <div class="d-flex justify-content-between align-items-center" style="font-size:.72rem">
                                <div class="d-flex gap-1">
                                    <span class="aksi-badge aksi-C" style="font-size:.65rem">C: ${fmtNum(u.total_create||0)}</span>
                                    <span class="aksi-badge aksi-U" style="font-size:.65rem">U: ${fmtNum(u.total_update||0)}</span>
                                    ${(u.total_delete > 0) ? `<span class="aksi-badge aksi-D" style="font-size:.65rem">D: ${fmtNum(u.total_delete)}</span>` : ''}
                                </div>
                                <span style="color:var(--color-text-muted)">
                                    <i class="bi bi-clock me-1"></i>${timeSince(u.aktivitas_terakhir || u.login_terakhir)}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `}).join('');

            // Hover effect for cards
            document.querySelectorAll('.user-card').forEach(card => {
                card.addEventListener('mouseenter', () => { card.style.boxShadow = 'var(--shadow-lg)'; card.style.transform = 'translateY(-2px)'; });
                card.addEventListener('mouseleave', () => { card.style.boxShadow = ''; card.style.transform = ''; });
            });

        } catch(err) {
            grid.innerHTML = `<div class="col-12"><div class="empty-state"><i class="bi bi-exclamation-triangle"></i><b>Gagal memuat data</b>${esc(err.message)}</div></div>`;
        }
    }

    window.pilihUser = function(userObj) {
        selectedUserId   = userObj.id;
        selectedUserName = userObj.nama;
        detailPage       = 1;

        document.getElementById('user-detail-panel').style.display = 'block';
        document.getElementById('detail-user-name').innerHTML =
            `<i class="bi bi-person-fill" style="color:var(--color-primary)"></i> ${esc(userObj.nama)}`;

        document.getElementById('detail-user-meta').textContent =
            `Username: @${userObj.username||'—'} | NIP: ${userObj.nip||'—'} | Role: ${userObj.role||'Pengguna'}`;

        document.getElementById('d-total').textContent  = fmtNum(userObj.total_aktivitas||0);
        document.getElementById('d-create').textContent = fmtNum(userObj.total_create||0);
        document.getElementById('d-update').textContent = fmtNum(userObj.total_update||0);
        document.getElementById('d-delete').textContent = fmtNum(userObj.total_delete||0);

        // Update Export link for user
        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;
        document.getElementById('btn-export-user').href =
            `/api/audit.php?action=export&user=${encodeURIComponent(userObj.id)}&dari=${dari}&sampai=${sampai}&format=csv`;

        loadUserDetail(detailPage);
        document.getElementById('user-detail-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    async function loadUserDetail(page = 1) {
        if (!selectedUserId) return;
        detailPage = page;

        const tbody = document.getElementById('detail-tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spin-sm"></span></td></tr>';

        const dari   = document.getElementById('f-dari').value;
        const sampai = document.getElementById('f-sampai').value;
        const aksi   = document.getElementById('detail-aksi').value;

        try {
            const data = await API.get('log', {
                dari, sampai,
                user:     selectedUserId,
                aksi:     aksi,
                per_page: 50,
                page:     page,
                with_diff: 1,
            });

            const rows = data.data || [];
            const meta = data.meta || {};

            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i><b>Tidak ada riwayat aktivitas</b></div></td></tr>';
                renderPager({ total: 0, page: 1, total_pages: 1 });
                return;
            }

            const AKSI_NAMA = { C: 'Dibuat', U: 'Diubah', D: 'Dihapus' };
            tbody.innerHTML = rows.map(r => {
                const hasData = r.sebelum || r.sesudah;
                const judul   = esc(`${r.modul || r.tabel || ''} — ${AKSI_NAMA[r.aksi] || r.aksi}`);
                const sub     = esc(`${r.tanggal || ''} \u00b7 ${selectedUserName} \u00b7 rec ${r.ref || ''}`);
                return `<tr>
                <td class="cell-waktu">
                    <b>${esc(r.tanggal ? r.tanggal.substring(11,19) : '')}</b>
                    <span>${esc(r.tanggal ? r.tanggal.substring(0,10).split('-').reverse().join('/') : '')}</span>
                </td>
                <td><b>${esc(r.modul || r.tabel || '\u2014')}</b></td>
                <td>${aksiHtml(r.aksi)}</td>
                <td class="cell-mono">${esc(r.ref || '\u2014')}</td>
                <td>${hasData ? `<button class="btn-rincian" onclick="bukaRincianBtn(this)"
                    data-id="${esc(r.id)}"
                    data-judul="${judul}"
                    data-sub="${sub}"
                    data-objek="${esc(r.objek_id || '')}"
                    data-ref="${esc(r.ref || '')}"
                    data-sebelum="${esc(JSON.stringify(r.sebelum || {}))}"
                    data-sesudah="${esc(JSON.stringify(r.sesudah || {}))}">Rincian</button>` : '\u2014'}</td>
            </tr>`;
            }).join('');

            renderPager(meta);

        } catch(err) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${esc(err.message)}</td></tr>`;
        }
    }

    function renderPager(meta) {
        const total    = meta.total || 0;
        const page     = meta.page  || 1;
        const perPage  = meta.per_page || 50;
        const totalPgs = meta.total_pages || 1;
        const from     = total > 0 ? (page - 1) * perPage + 1 : 0;
        const to       = Math.min(page * perPage, total);

        document.getElementById('pager-info').textContent =
            total > 0 ? `Menampilkan ${fmtNum(from)}-${fmtNum(to)} dari ${fmtNum(total)}` : '';

        const btns = document.getElementById('pager-btns');
        btns.innerHTML = '';

        function mkBtn(label, pg, disabled, active) {
            const b = document.createElement('button');
            b.className = 'filter-btn secondary' + (active ? ' active' : '');
            b.style.minWidth = '34px';
            b.style.padding = '0.2rem 0.5rem';
            b.disabled = !!disabled;
            b.innerHTML = label;
            if (!disabled) b.addEventListener('click', function(){ loadUserDetail(pg); });
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

    document.getElementById('btn-filter').addEventListener('click', loadUsers);
    document.getElementById('f-user').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadUsers();
    });
    document.getElementById('f-sort').addEventListener('change', loadUsers);

    document.getElementById('btn-close-detail').addEventListener('click', function() {
        document.getElementById('user-detail-panel').style.display = 'none';
        selectedUserId = null;
    });

    document.getElementById('detail-aksi').addEventListener('change', function() {
        if (selectedUserId) loadUserDetail(1);
    });

    loadUsers();
});
</script>
