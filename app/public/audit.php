<?php
/**
 * audit.php — Halaman Monitoring Audit Trail
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Audit Trail';
$activePage = 'audit';

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
                        <i class="bi bi-shield-check me-2" style="color:var(--color-primary)"></i>Monitoring Audit Trail
                    </h1>
                    <p class="page-subtitle">Rekam jejak seluruh aktivitas pengguna — siapa, kapan, dan apa yang berubah</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span id="demo-badge" class="demo-badge d-none">
                        <i class="bi bi-exclamation-triangle-fill"></i>Data Demo
                    </span>
                    <a href="/export.php" class="btn-app-secondary btn-app-sm">
                        <i class="bi bi-download"></i>Export
                    </a>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-col">
                    <label>Dari Tanggal</label>
                    <input type="date" id="f-dari" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-col">
                    <label>Sampai Tanggal</label>
                    <input type="date" id="f-sampai" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-col">
                    <label>Modul</label>
                    <select id="f-modul">
                        <option value="">Semua Modul</option>
                    </select>
                </div>
                <div class="filter-col">
                    <label>Jenis Aksi</label>
                    <select id="f-aksi">
                        <option value="">Semua</option>
                        <option value="C">Dibuat</option>
                        <option value="U">Diubah</option>
                    </select>
                </div>
                <div class="filter-col">
                    <label>User</label>
                    <input type="text" id="f-user" placeholder="Nama atau ID user">
                </div>
                <div class="filter-col">
                    <label>No. RM</label>
                    <input type="text" id="f-norm" placeholder="Cth: 00.12.34">
                </div>
                <div class="filter-col">
                    <label>Keyword</label>
                    <input type="text" id="f-keyword" placeholder="Cari di data">
                </div>
                <div class="d-flex gap-2" style="align-self:flex-end">
                    <button class="filter-btn" id="btn-filter">
                        <i class="bi bi-search"></i>Tampilkan
                    </button>
                    <button class="filter-btn secondary" id="btn-reset">
                        <i class="bi bi-x-circle"></i>Reset
                    </button>
                </div>
            </div>

            <!-- Info bar -->
            <div id="info-bar" class="d-flex align-items-center justify-content-between mb-2" style="font-size:.82rem;color:var(--color-text-muted)">
                <span id="info-text">Memuat data...</span>
                <div class="d-flex gap-2">
                    <button class="filter-btn secondary btn-app-sm" id="btn-prev" disabled>
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span id="page-info" style="padding:.3rem .5rem;font-size:.78rem">—</span>
                    <button class="filter-btn secondary btn-app-sm" id="btn-next" disabled>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="app-card">
                <div class="table-responsive">
                    <table class="app-table" id="audit-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Modul</th>
                                <th>Aksi</th>
                                <th>Record ID</th>
                                <th class="d-none d-xl-table-cell">Yang Berubah</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="audit-tbody">
                            <tr><td colspan="7" class="text-center py-4">
                                <span class="spin-sm"></span>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/modal-rincian.php'; ?>

<!-- Tom Select (CSS & JS) -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
/* Tom Select Dark/Light Theme Integration */
.ts-wrapper .ts-control {
    background-color: var(--color-surface) !important;
    color: var(--color-text) !important;
    border: 1px solid var(--color-border) !important;
    border-radius: var(--radius-sm) !important;
    padding: .45rem .75rem !important;
    font-size: .85rem !important;
    box-shadow: none !important;
    transition: border-color .15s ease-in-out;
    display: flex;
    align-items: center;
}
.ts-wrapper.focus .ts-control {
    border-color: var(--color-primary) !important;
}
.ts-dropdown {
    background-color: var(--color-surface) !important;
    border: 1px solid var(--color-border) !important;
    border-radius: var(--radius-sm) !important;
    box-shadow: var(--shadow-md) !important;
    color: var(--color-text) !important;
    font-size: .85rem !important;
    margin-top: 4px !important;
}
.ts-dropdown .option {
    padding: .45rem .75rem !important;
    color: var(--color-text) !important;
}
.ts-dropdown .active {
    background-color: var(--color-primary) !important;
    color: #fff !important;
}
.ts-dropdown .active.create {
    color: #fff !important;
}
.ts-wrapper.single .ts-control::after {
    border-color: var(--color-text-muted) transparent transparent transparent !important;
}
.ts-wrapper.single.open .ts-control::after {
    border-color: transparent transparent var(--color-text-muted) transparent !important;
}
.ts-control input {
    color: var(--color-text) !important;
}
.ts-wrapper .items {
    color: var(--color-text) !important;
}
/* Memastikan dropdown tidak dipotong container */
.filter-col {
    position: relative;
    overflow: visible !important;
}
.filter-bar {
    overflow: visible !important;
}
.riwayat-item.interactive {
    cursor: pointer;
    transition: background-color .15s ease;
}
.riwayat-item.interactive:hover {
    background-color: rgba(15,118,110,.15);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const AKSI_NAMA = { C: 'Dibuat', U: 'Diubah', D: 'Dihapus' };
    let currentPage  = 1;
    let totalPages   = 1;
    let activeData   = null;
    let modeMentah   = false;

let tsModul = null;
    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }

    function aksiHtml(a) {
        return `<span class="aksi-badge aksi-${esc(a)}">${esc(AKSI_NAMA[a]||a)}</span>`;
    }

    // Load modul list for dropdown
    async function loadModul() {
        try {
            const d = await API.get('objek_list');
            const sel = document.getElementById('f-modul');
            (d.data || []).forEach(o => {
                const nama = o.DESKRIPSI || o.TABEL.split('.').pop();
                sel.innerHTML += `<option value="${esc(o.ID)}">${esc(nama)}</option>`;
            });
            // Initialize TomSelect for searchable dropdown
            if (window.TomSelect) {
                tsModul = new TomSelect('#f-modul', {
                    create: false,
                    placeholder: 'Semua Modul',
                    // Optional: sort options alphabetically
                    sortField: { field: 'text', direction: 'asc' }
                });
            }
        } catch {}
    }

    function getFilters() {
        return {
            dari:     document.getElementById('f-dari').value,
            sampai:   document.getElementById('f-sampai').value,
            modul:    document.getElementById('f-modul').value,
            aksi:     document.getElementById('f-aksi').value,
            user:     document.getElementById('f-user').value,
            norm:     document.getElementById('f-norm').value,
            keyword:  document.getElementById('f-keyword').value,
            page:     currentPage,
            per_page: 50,
        };
    }

    async function loadLog() {
        const tbody = document.getElementById('audit-tbody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><span class="spin-sm"></span></td></tr>';
        document.getElementById('info-text').textContent = 'Memuat...';
        document.getElementById('btn-prev').disabled = true;
        document.getElementById('btn-next').disabled = true;

        try {
            const data = await API.get('log', getFilters());
            const rows = data.data || [];
            const meta = data.meta || {};
            totalPages  = meta.total_pages || 1;

            if (data._demo) document.getElementById('demo-badge').classList.remove('d-none');

            document.getElementById('info-text').textContent =
                `Menampilkan ${rows.length} dari ${fmtNum(meta.total || 0)} catatan`;
            document.getElementById('page-info').textContent =
                `Hal. ${currentPage} / ${totalPages}`;
            document.getElementById('btn-prev').disabled = currentPage <= 1;
            document.getElementById('btn-next').disabled = currentPage >= totalPages;

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <b>Tidak ada catatan</b>
                    Coba ubah filter periode atau modul.
                </div></td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map(r => {
                const sebelum = r.sebelum || {};
                const sesudah = r.sesudah || {};
                const fields  = [...new Set([...Object.keys(sebelum), ...Object.keys(sesudah)])];
                const preview = fields.slice(0, 3).join(', ') + (fields.length > 3 ? ` +${fields.length-3}` : '');
                const hasData = fields.length > 0;

                return `<tr>
                    <td class="cell-waktu">
                        <b>${esc(r.tanggal.substring(11,19))}</b>
                        <span>${esc(r.tanggal.substring(0,10).split('-').reverse().join('/'))}</span>
                    </td>
                    <td>${esc(r.user_nama)}</td>
                    <td><strong>${esc(r.modul)}</strong><br>
                        <span class="cell-mono" style="font-size:.7rem">${esc(r.tabel||'')}</span>
                    </td>
                    <td>${aksiHtml(r.aksi)}</td>
                    <td class="cell-mono">${esc(r.ref)}</td>
                    <td class="d-none d-xl-table-cell" style="color:var(--color-text-muted);font-size:.78rem;max-width:22ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        ${hasData ? esc(preview) : '<span style="font-style:italic">—</span>'}
                    </td>
                    <td>
                        ${hasData ? `<button class="btn-rincian" onclick="bukaRincianBtn(this)" 
                            data-id="${esc(r.id)}"
                            data-judul="${esc(r.modul)} — ${esc(AKSI_NAMA[r.aksi]||r.aksi)}"
                            data-sub="${esc(r.tanggal)} · ${esc(r.user_nama)} · rec ${esc(r.ref)}"
                            data-objek="${esc(r.objek_id)}"
                            data-ref="${esc(r.ref)}"
                            data-sebelum="${esc(JSON.stringify(r.sebelum||{}))}"
                            data-sesudah="${esc(JSON.stringify(r.sesudah||{}))}"
                        >Rincian</button>` : ''}
                    </td>
                </tr>`;
            }).join('');

        } catch(err) {
            tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <b>Gagal memuat data</b>${esc(err.message)}
            </div></td></tr>`;
        }
    }

    // Semua logika diff/riwayat/konteks sekarang ada di templates/modal-rincian.php
    // Gunakan window.bukaRincianBtn(btn) atau window.bukaRincian(config) dari mana saja

    // Filter & pagination
    document.getElementById('btn-filter').addEventListener('click', function() { currentPage = 1; loadLog(); });
    document.getElementById('btn-reset').addEventListener('click', function() {
        document.getElementById('f-dari').value    = '<?= date('Y-m-d') ?>';
        document.getElementById('f-sampai').value  = '<?= date('Y-m-d') ?>';
        if (tsModul) { tsModul.setValue(''); } else { document.getElementById('f-modul').value = ''; }
        document.getElementById('f-aksi').value    = '';
        document.getElementById('f-user').value    = '';
        document.getElementById('f-norm').value    = '';
        document.getElementById('f-keyword').value = '';
        currentPage = 1; loadLog();
    });

    ['f-dari','f-sampai'].forEach(id => {
        document.getElementById(id).addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { currentPage=1; loadLog(); }
        });
    });

    document.getElementById('btn-prev').addEventListener('click', function() { if(currentPage>1) { currentPage--; loadLog(); }});
    document.getElementById('btn-next').addEventListener('click', function() { if(currentPage<totalPages) { currentPage++; loadLog(); }});

    loadModul();
    loadLog();
});
</script>
