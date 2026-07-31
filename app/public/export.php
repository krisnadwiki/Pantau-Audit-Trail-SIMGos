<?php
/**
 * export.php — Halaman Export Data
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Export Data';
$activePage = 'export';

include __DIR__ . '/../templates/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>

        <main class="app-content">

            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-download me-2" style="color:var(--color-primary)"></i>Export Data
                </h1>
                <p class="page-subtitle">Unduh data audit trail sesuai filter aktif dalam format pilihan Anda</p>
            </div>

            <div class="row g-3">
                <!-- Export Form -->
                <div class="col-lg-6">
                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-funnel-fill" style="color:var(--color-primary)"></i>
                                Konfigurasi Export
                            </h2>
                        </div>
                        <div class="app-card-body">
                            <div class="mb-3">
                                <label class="form-label-dark">Periode</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" id="e-dari" value="<?= date('Y-m-d') ?>"
                                               class="form-control form-control-dark" placeholder="Dari">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" id="e-sampai" value="<?= date('Y-m-d') ?>"
                                               class="form-control form-control-dark" placeholder="Sampai">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-dark">Filter Modul (opsional)</label>
                                <select id="e-modul" class="form-select form-select-dark">
                                    <option value="">Semua Modul</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-dark">Filter Jenis Aksi (opsional)</label>
                                <select id="e-aksi" class="form-select form-select-dark">
                                    <option value="">Semua Aksi</option>
                                    <option value="C">Dibuat</option>
                                    <option value="U">Diubah</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-dark">Filter User (opsional)</label>
                                <input type="text" id="e-user" class="form-control form-control-dark"
                                       placeholder="Nama atau ID user...">
                            </div>

                            <div class="mb-4">
                                <label class="form-label-dark">Format Export</label>
                                <div class="d-flex gap-3">
                                    <?php foreach (['csv' => 'CSV (Excel)', 'excel' => 'Excel (.xlsx)', 'pdf' => 'PDF'] as $val => $label): ?>
                                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.85rem">
                                        <input type="radio" name="e-format" value="<?= $val ?>"
                                               <?= $val === 'csv' ? 'checked' : '' ?>>
                                        <?= $label ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn-app-primary" id="btn-preview">
                                    <i class="bi bi-eye"></i>Preview
                                </button>
                                <button class="btn-app-primary" id="btn-export" style="background:var(--grad-success)">
                                    <i class="bi bi-download"></i>Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortcut & Info -->
                <div class="col-lg-6">
                    <div class="app-card mb-3">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-lightning-charge-fill" style="color:#f59e0b"></i>
                                Export Cepat
                            </h2>
                        </div>
                        <div class="app-card-body">
                            <p style="font-size:.83rem;color:var(--color-text-muted);margin-bottom:1rem">
                                Download langsung tanpa konfigurasi tambahan:
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <button class="btn-app-secondary" onclick="exportCepat('hari_ini')">
                                    <i class="bi bi-calendar-day"></i>Audit Trail Hari Ini (CSV)
                                </button>
                                <button class="btn-app-secondary" onclick="exportCepat('minggu_ini')">
                                    <i class="bi bi-calendar-week"></i>Audit Trail Minggu Ini (CSV)
                                </button>
                                <button class="btn-app-secondary" onclick="exportCepat('bulan_ini')">
                                    <i class="bi bi-calendar-month"></i>Audit Trail Bulan Ini (CSV)
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="app-card">
                        <div class="app-card-header">
                            <h2 class="app-card-title">
                                <i class="bi bi-info-circle-fill" style="color:var(--color-info)"></i>
                                Informasi
                            </h2>
                        </div>
                        <div class="app-card-body" style="font-size:.83rem;color:var(--color-text-muted)">
                            <ul style="padding-left:1.2rem;line-height:2">
                                <li>Format <strong>CSV</strong> dapat dibuka langsung di Microsoft Excel</li>
                                <li>File menggunakan encoding <strong>UTF-8 BOM</strong> agar karakter Indonesia terbaca benar</li>
                                <li>Export mengikuti <strong>semua filter</strong> yang dipilih</li>
                                <li>Maksimal <strong>5.000 baris</strong> per export untuk performa optimal</li>
                                <li>Data bersumber dari <strong>REST API SIMGOS</strong> atau langsung dari database</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Area -->
            <div id="preview-area" class="mt-3" style="display:none">
                <div class="app-card">
                    <div class="app-card-header">
                        <h2 class="app-card-title">
                            <i class="bi bi-table" style="color:var(--color-primary)"></i>
                            Preview Data
                        </h2>
                        <span id="preview-info" class="text-muted" style="font-size:.82rem"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="app-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Modul</th>
                                    <th>Aksi</th>
                                    <th>Record ID</th>
                                </tr>
                            </thead>
                            <tbody id="preview-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>

        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
</div>

<!-- Tom Select (CSS & JS) -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
.ts-wrapper .ts-control {
    background-color: var(--color-surface) !important;
    color: var(--color-text) !important;
    border: 1px solid var(--color-border) !important;
    border-radius: var(--radius-sm) !important;
    padding: .45rem .75rem !important;
    font-size: .85rem !important;
    box-shadow: none !important;
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
}
.ts-dropdown .option {
    padding: .45rem .75rem !important;
    color: var(--color-text) !important;
}
.ts-dropdown .active {
    background-color: var(--color-primary) !important;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const AKSI_NAMA = { C: 'Dibuat', U: 'Diubah', D: 'Dihapus' };
    let tsModul = null;

    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function aksiHtml(a) { return `<span class="aksi-badge aksi-${esc(a)}">${esc(AKSI_NAMA[a]||a)}</span>`; }

    // Load modul dropdown
    async function loadModul() {
        try {
            const d = await API.get('objek_list');
            const sel = document.getElementById('e-modul');
            (d.data||[]).forEach(o => {
                const nama = o.DESKRIPSI || o.TABEL.split('.').pop();
                sel.innerHTML += `<option value="${esc(o.ID)}">${esc(nama)}</option>`;
            });
            if (window.TomSelect) {
                tsModul = new TomSelect('#e-modul', {
                    create: false,
                    placeholder: 'Semua Modul',
                    sortField: { field: 'text', direction: 'asc' }
                });
            }
        } catch {}
    }

    function getParams() {
        const modulVal = tsModul ? tsModul.getValue() : document.getElementById('e-modul').value;
        return {
            dari:   document.getElementById('e-dari').value,
            sampai: document.getElementById('e-sampai').value,
            modul:  modulVal,
            aksi:   document.getElementById('e-aksi').value,
            user:   document.getElementById('e-user').value.trim(),
        };
    }

    function getFormat() {
        return document.querySelector('input[name="e-format"]:checked')?.value || 'csv';
    }

    // Preview
    document.getElementById('btn-preview').addEventListener('click', async function() {
        const p = getParams();
        const area  = document.getElementById('preview-area');
        const tbody = document.getElementById('preview-tbody');
        area.style.display = 'block';
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><span class="spin-sm"></span></td></tr>';
        area.scrollIntoView({ behavior: 'smooth', block: 'start' });

        try {
            const data = await API.get('log', { ...p, page: 1, per_page: 25 });
            const rows = data.data || [];
            const meta = data.meta || {};

            document.getElementById('preview-info').textContent =
                `Preview 25 baris pertama dari ${Number(meta.total||0).toLocaleString('id-ID')} total catatan`;

            tbody.innerHTML = rows.map((r, i) => `<tr>
                <td class="cell-mono" style="color:var(--color-text-subtle)">${i+1}</td>
                <td class="cell-waktu">
                    <b>${esc(r.tanggal.substring(11,19))}</b>
                    <span>${esc(r.tanggal.substring(0,10).split('-').reverse().join('/'))}</span>
                </td>
                <td>${esc(r.user_nama)}</td>
                <td>${esc(r.modul)}</td>
                <td>${aksiHtml(r.aksi)}</td>
                <td class="cell-mono">${esc(r.ref)}</td>
            </tr>`).join('') || '<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data.</td></tr>';

        } catch(err) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${esc(err.message)}</td></tr>`;
        }
    });

    // Download
    document.getElementById('btn-export').addEventListener('click', function() {
        const p      = getParams();
        const format = getFormat();
        const params = new URLSearchParams({ action:'export', ...p, format });
        window.location.href = '/api/audit.php?' + params.toString();
    });

    // Export Cepat
    window.exportCepat = function(periode) {
        const today  = new Date();
        let dari, sampai = today.toISOString().substring(0,10);

        if (periode === 'hari_ini') {
            dari = sampai;
        } else if (periode === 'minggu_ini') {
            const d = new Date(today.getTime() - 6 * 86400000);
            dari = d.toISOString().substring(0,10);
        } else {
            dari = today.toISOString().substring(0,8) + '01';
        }

        const params = new URLSearchParams({ action:'export', dari, sampai, format:'csv' });
        window.location.href = '/api/audit.php?' + params.toString();
    };

    loadModul();
});
</script>
