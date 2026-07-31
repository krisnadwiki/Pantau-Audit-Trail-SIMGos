<?php
/**
 * search.php — Halaman Pencarian Global
 */

require_once __DIR__ . '/../config/config.php';
require_auth();

$pageTitle  = 'Pencarian';
$activePage = 'search';

include __DIR__ . '/../templates/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>

        <main class="app-content">

            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-search me-2" style="color:var(--color-primary)"></i>Pencarian
                </h1>
                <p class="page-subtitle">Cari di seluruh catatan audit trail SIMGOS</p>
            </div>

            <!-- Search Bar -->
            <div class="app-card mb-3" style="padding:1.5rem">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <div class="filter-col" style="min-width:unset">
                            <label>Keyword</label>
                            <div style="position:relative">
                                <i class="bi bi-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--color-text-subtle);font-size:.85rem;pointer-events:none"></i>
                                <input type="text" id="f-keyword" placeholder="Cari user, modul, record ID, data..."
                                    style="padding-left:2.25rem!important;width:100%"
                                    class="form-control-dark">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="filter-col" style="min-width:unset">
                            <label>Dari</label>
                            <input type="date" id="f-dari" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="filter-col" style="min-width:unset">
                            <label>Sampai</label>
                            <input type="date" id="f-sampai" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="filter-col" style="min-width:unset">
                            <label>Jenis Aksi</label>
                            <select id="f-aksi">
                                <option value="">Semua</option>
                                <option value="C">Dibuat</option>
                                <option value="U">Diubah</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <button class="filter-btn" id="btn-search" style="width:100%">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hasil -->
            <div id="hasil-area">
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <b>Masukkan kata kunci</b>
                    Ketik keyword dan klik Cari untuk melihat hasil pencarian.
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
    let activeData = null, modeMentah = false;

    function esc(s) { return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function fmtNum(n) { return Number(n||0).toLocaleString('id-ID'); }
    function aksiHtml(a) { return `<span class="aksi-badge aksi-${esc(a)}">${esc(AKSI_NAMA[a]||a)}</span>`; }

    // Semua logika diff/riwayat/konteks ada di templates/modal-rincian.php

    // Highlight keyword in text
    function highlight(text, keyword) {
        if (!keyword) return esc(text);
        const re = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return esc(text).replace(re, '<mark style="background:rgba(245,158,11,.3);color:inherit;border-radius:2px">$1</mark>');
    }

    async function doSearch() {
        const keyword = document.getElementById('f-keyword').value.trim();
        const dari    = document.getElementById('f-dari').value;
        const sampai  = document.getElementById('f-sampai').value;
        const aksi    = document.getElementById('f-aksi').value;

        if (!keyword && !aksi) {
            document.getElementById('hasil-area').innerHTML = `<div class="empty-state">
                <i class="bi bi-search"></i><b>Masukkan kata kunci</b>Ketik keyword atau pilih jenis aksi untuk mencari.
            </div>`;
            return;
        }

        document.getElementById('hasil-area').innerHTML = '<div class="text-center py-5"><span class="spin-sm"></span></div>';

        try {
            const data = await API.get('log', { dari, sampai, aksi, keyword, per_page: 100, page: 1 });
            const rows = data.data || [];
            const meta = data.meta || {};

            if (!rows.length) {
                document.getElementById('hasil-area').innerHTML = `<div class="empty-state">
                    <i class="bi bi-file-earmark-x"></i>
                    <b>Tidak ada hasil</b>
                    Coba kata kunci lain atau perluas periode pencarian.
                </div>`;
                return;
            }

            const html = `
                <div class="d-flex align-items-center justify-content-between mb-2" style="font-size:.82rem;color:var(--color-text-muted)">
                    <span>Menampilkan <strong>${rows.length}</strong> dari <strong>${fmtNum(meta.total||rows.length)}</strong> hasil${keyword ? ` untuk "<strong>${esc(keyword)}</strong>"` : ''}</span>
                </div>
                <div class="app-card">
                    <div class="table-responsive">
                        <table class="app-table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Modul</th>
                                    <th>Aksi</th>
                                    <th>Record ID</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows.map(r => {
                                    const hasData = Object.keys({...(r.sebelum||{}), ...(r.sesudah||{})}).length > 0;
                                    return `<tr>
                                        <td class="cell-waktu">
                                            <b>${esc(r.tanggal.substring(11,19))}</b>
                                            <span>${esc(r.tanggal.substring(0,10).split('-').reverse().join('/'))}</span>
                                        </td>
                                        <td>${highlight(r.user_nama, keyword)}</td>
                                        <td><strong>${highlight(r.modul, keyword)}</strong></td>
                                        <td>${aksiHtml(r.aksi)}</td>
                                        <td class="cell-mono">${highlight(String(r.ref), keyword)}</td>
                                        <td>
                                            ${hasData ? `<button class="btn-rincian" onclick="bukaRincianBtn(this)"
                                                data-id="${esc(r.id)}"
                                                data-judul="${esc(r.modul)} — ${esc(AKSI_NAMA[r.aksi]||r.aksi)}"
                                                data-sub="${esc(r.tanggal)} · ${esc(r.user_nama)} · rec ${esc(r.ref)}"
                                                data-objek="${esc(r.objek_id||'')}"
                                                data-ref="${esc(r.ref||'')}"
                                                data-sebelum="${esc(JSON.stringify(r.sebelum||{}))}"
                                                data-sesudah="${esc(JSON.stringify(r.sesudah||{}))}">Rincian</button>` : ''}
                                        </td>
                                    </tr>`;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            document.getElementById('hasil-area').innerHTML = html;

        } catch(err) {
            document.getElementById('hasil-area').innerHTML = `<div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i><b>Error</b>${esc(err.message)}
            </div>`;
        }
    }

    document.getElementById('btn-search').addEventListener('click', doSearch);
    document.getElementById('f-keyword').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') doSearch();
    });
});
</script>
