<?php
/**
 * templates/modal-rincian.php
 *
 * Komponen modal rincian perubahan (diff + riwayat + konteks) yang dapat
 * digunakan ulang di seluruh halaman. Include sekali di bawah body, kemudian
 * panggil window.bukaRincian(config) dari mana saja.
 *
 * @param config objek dengan properti:
 *   id      – id log
 *   judul   – judul dialog  (cth: "Kunjungan — Diubah")
 *   sub     – subtitle      (cth: "2025-01-01 10:00 · dr. Budi · rec 12")
 *   sebelum – object JSON sebelum
 *   sesudah – object JSON sesudah
 *   objek   – ID objek/modul untuk muat riwayat
 *   ref     – record ID untuk muat riwayat
 */
?>

<!-- Rincian Dialog (shared component) -->
<dialog class="audit-dialog" id="rincian-dialog">
    <div class="dialog-head">
        <div>
            <h3 id="d-judul"><i class="bi bi-shield-check me-2"></i>Rincian Perubahan</h3>
            <p id="d-sub"></p>
        </div>
        <div class="dialog-tools">
            <button class="dialog-tool-btn" id="d-alih">
                <i class="bi bi-code-slash me-1"></i>Kode asli
            </button>
            <button class="dialog-close" onclick="document.getElementById('rincian-dialog').close()" aria-label="Tutup modal">&times;</button>
        </div>
    </div>
    <div class="dialog-body">
        <div class="dialog-konteks" id="d-konteks" style="display:none"></div>
        <div class="dialog-riwayat" id="d-riwayat"></div>
        <div class="diff-table-wrapper">
            <table class="diff-table">
                <thead><tr>
                    <th style="width:22%">Kolom</th>
                    <th style="width:39%">Sebelum</th>
                    <th style="width:39%">Sesudah</th>
                </tr></thead>
                <tbody id="d-tbody"></tbody>
            </table>
        </div>
    </div>
</dialog>

<script>
(function() {
    // ─────────────────────────────────────────────────────
    // State
    // ─────────────────────────────────────────────────────
    const AKSI_NAMA = { C: 'Dibuat', U: 'Diubah', D: 'Dihapus' };
    let _activeData        = null;
    let _modeMentah        = false;
    let _currentRiwayatList = [];

    // ─────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])
        );
    }

    function aksiHtml(a) {
        return `<span class="aksi-badge aksi-${esc(a)}">${esc(AKSI_NAMA[a] || a)}</span>`;
    }

    // ─────────────────────────────────────────────────────
    // Diff logic
    // ─────────────────────────────────────────────────────
    function bersihkan(html) {
        let s = String(html);
        s = s.replace(/<br\s*\/?>/gi, '\n').replace(/<\/(p|div|li|tr|h[1-6])>/gi, '\n');
        s = s.replace(/<li[^>]*>/gi, '• ').replace(/<[^>]+>/g, '');
        const t = document.createElement('textarea'); t.innerHTML = s; s = t.value;
        return s.replace(/\u00a0/g, ' ').replace(/[ \t]+/g, ' ')
                .replace(/\n[ ]+/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function keTeks(v, mentah) {
        if (v === undefined || v === null || v === '') return null;
        if (typeof v === 'object') return JSON.stringify(v, null, 2);
        const s = String(v);
        if (mentah) return s;
        return /<[a-z][\s\S]*>/i.test(s) ? bersihkan(s) : s;
    }

    function belah(a, b) {
        const A = a.split(/(\s+)/), B = b.split(/(\s+)/);
        let i = 0, j = 0;
        while (i < A.length && i < B.length && A[i] === B[i]) i++;
        while (j < A.length - i && j < B.length - i && A[A.length - 1 - j] === B[B.length - 1 - j]) j++;
        return {
            awalA: A.slice(0, i).join(''), tengahA: A.slice(i, A.length - j).join(''), akhirA: A.slice(A.length - j).join(''),
            awalB: B.slice(0, i).join(''), tengahB: B.slice(i, B.length - j).join(''), akhirB: B.slice(B.length - j).join('')
        };
    }

    function renderDiff() {
        if (!_activeData) return;
        const tbody = document.getElementById('d-tbody');
        const lama  = _activeData.sebelum || {};
        const baru  = _activeData.sesudah || {};
        const fields = [...new Set([...Object.keys(lama), ...Object.keys(baru)])];

        tbody.innerHTML = fields.map(k => {
            const la = keTeks(lama[k], _modeMentah);
            const ba = keTeks(baru[k], _modeMentah);
            let laHtml, baHtml;

            if (la === null && ba === null) return '';
            if (la === null) {
                laHtml = `<span class="diff-empty">kosong</span>`;
                baHtml = `<span class="diff-add">${esc(ba)}</span>`;
            } else if (ba === null) {
                laHtml = esc(la);
                baHtml = `<span class="diff-empty">kosong</span>`;
            } else if (la === ba) {
                laHtml = esc(la);
                baHtml = `<span class="diff-same">tidak berubah</span>`;
            } else {
                const p = belah(la, ba);
                laHtml = esc(p.awalA) + (p.tengahA ? `<span class="diff-del">${esc(p.tengahA)}</span>` : '') + esc(p.akhirA);
                baHtml = esc(p.awalB) + (p.tengahB ? `<span class="diff-add">${esc(p.tengahB)}</span>` : '') + esc(p.akhirB);
            }

            const kelas = _modeMentah ? 'raw' : '';
            return `<tr>
                <td class="diff-field"><span class="diff-mobile-label-field">KOLOM</span>${esc(k)}</td>
                <td class="diff-value ${kelas}"><span class="diff-mobile-label"><i class="bi bi-arrow-left-circle me-1"></i>SEBELUM:</span><div class="diff-val-content">${laHtml}</div></td>
                <td class="diff-value ${kelas}"><span class="diff-mobile-label"><i class="bi bi-arrow-right-circle me-1"></i>SESUDAH:</span><div class="diff-val-content">${baHtml}</div></td>
            </tr>`;
        }).join('');
    }

    // ─────────────────────────────────────────────────────
    // Konteks (identitas pasien / kunjungan)
    // ─────────────────────────────────────────────────────
    function gambarKonteks(k, pesan) {
        const kDiv = document.getElementById('d-konteks');
        let html = '';
        if (k) {
            if (k.norm)      html += `<div class="konteks-item"><small>No. Rekam Medis</small><b>${esc(k.norm)}</b></div>`;
            if (k.pasien)    html += `<div class="konteks-item"><small>Nama Pasien</small><b style="font-family:inherit;font-size:.88rem">${esc(k.pasien)}</b></div>`;
            if (k.ruangan)   html += `<div class="konteks-item"><small>Ruangan</small><b style="font-family:inherit">${esc(k.ruangan)}</b></div>`;
            if (k.kunjungan) html += `<div class="konteks-item"><small>No. Kunjungan</small><b>${esc(k.kunjungan)}</b></div>`;
            if (k.nopen)     html += `<div class="konteks-item"><small>No. Pendaftaran</small><b>${esc(k.nopen)}</b></div>`;
            if (k.masuk)     html += `<div class="konteks-item"><small>Waktu Masuk</small><b>${esc(k.masuk)}</b></div>`;
            if (k.rincian && k.rincian.length) {
                k.rincian.forEach(d => {
                    html += `<div class="konteks-item"><small>${esc(d.label)}</small><b>${esc(d.nilai)}</b></div>`;
                });
            }
        } else if (pesan) {
            html = `<div style="font-size:.82rem;color:var(--color-warning);line-height:1.5">${esc(pesan)}</div>`;
        }
        kDiv.innerHTML = html;
        kDiv.style.display = html === '' ? 'none' : 'flex';
    }

    // ─────────────────────────────────────────────────────
    // Riwayat list (timeline, navigable)
    // ─────────────────────────────────────────────────────
    function renderRiwayatList() {
        const rDiv = document.getElementById('d-riwayat');
        if (!_currentRiwayatList || _currentRiwayatList.length === 0) {
            rDiv.innerHTML = `<div class="riwayat-title">Riwayat</div><div style="font-size:.8rem;color:var(--color-text-muted)">Tidak ada catatan riwayat tambahan.</div>`;
            return;
        }

        let html = `<div class="riwayat-title">Riwayat Record — ${_currentRiwayatList.length} catatan (klik untuk berpindah)</div>`;
        html += _currentRiwayatList.map((x, idx) => {
            const isCurrent = _activeData && x.id == _activeData.id;
            return `<div class="riwayat-item interactive ${isCurrent ? 'current' : ''}" data-idx="${idx}">
                ${aksiHtml(x.aksi)}
                <span class="riwayat-jam">${esc(x.waktu)}</span>
                <span class="riwayat-user">${esc(x.oleh)}</span>
                ${isCurrent ? '<span class="riwayat-now">sedang dilihat</span>' : ''}
            </div>`;
        }).join('');

        rDiv.innerHTML = html;

        rDiv.querySelectorAll('.riwayat-item').forEach(el => {
            el.addEventListener('click', function() {
                const idx  = parseInt(this.dataset.idx, 10);
                if (isNaN(idx) || !_currentRiwayatList[idx]) return;
                const item = _currentRiwayatList[idx];
                _activeData = { id: item.id, sebelum: item.sebelum || {}, sesudah: item.sesudah || {} };
                renderDiff();
                renderRiwayatList();
            });
        });
    }

    async function muatRiwayat(objekId, refId) {
        const rDiv = document.getElementById('d-riwayat');
        const kDiv = document.getElementById('d-konteks');
        rDiv.innerHTML = `<div class="riwayat-title">Riwayat</div><div style="font-style:italic;font-size:.82rem;color:var(--color-text-muted)"><span class="spin-sm"></span> Memuat riwayat...</div>`;
        kDiv.style.display = 'none';

        try {
            const d = await API.get('riwayat', { objek: objekId, ref: refId });
            gambarKonteks(d.konteks, d.pesan);
            _currentRiwayatList = d.data || [];
            renderRiwayatList();
        } catch {
            rDiv.innerHTML = `<div class="riwayat-title">Riwayat</div><div style="font-size:.8rem;color:var(--color-text-muted)">Gagal memuat riwayat.</div>`;
        }
    }

    // ─────────────────────────────────────────────────────
    // Public API — bukaRincian(config)
    // config = { id, judul, sub, sebelum, sesudah, objek, ref }
    // ─────────────────────────────────────────────────────
    window.bukaRincian = function(config) {
        _activeData  = { id: config.id, sebelum: config.sebelum || {}, sesudah: config.sesudah || {} };
        _modeMentah  = false;
        _currentRiwayatList = [];

        const dJudul = document.getElementById('d-judul');
        if (config.judul) {
            dJudul.innerHTML = `<i class="bi bi-shield-check me-2"></i>${esc(config.judul)}`;
        } else {
            dJudul.innerHTML = `<i class="bi bi-shield-check me-2"></i>Rincian Perubahan`;
        }

        document.getElementById('d-sub').textContent  = config.sub  || '';
        document.getElementById('d-alih').innerHTML = '<i class="bi bi-code-slash me-1"></i>Kode asli';

        renderDiff();
        muatRiwayat(config.objek, config.ref);
        document.getElementById('rincian-dialog').showModal();
    };

    // Convenience: dipakai oleh tombol inline data-* (backward-compat audit.php)
    window.bukaRincianBtn = function(btn) {
        window.bukaRincian({
            id:      btn.dataset.id,
            judul:   btn.dataset.judul,
            sub:     btn.dataset.sub,
            sebelum: JSON.parse(btn.dataset.sebelum || '{}'),
            sesudah: JSON.parse(btn.dataset.sesudah || '{}'),
            objek:   btn.dataset.objek,
            ref:     btn.dataset.ref,
        });
    };

    // ─────────────────────────────────────────────────────
    // Toggle raw mode
    // ─────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('d-alih').addEventListener('click', function() {
            _modeMentah = !_modeMentah;
            this.innerHTML = _modeMentah ? '<i class="bi bi-text-left me-1"></i>Teks terbaca' : '<i class="bi bi-code-slash me-1"></i>Kode asli';
            renderDiff();
        });

        // Close on backdrop click
        document.getElementById('rincian-dialog').addEventListener('click', function(e) {
            if (e.target === this) this.close();
        });
    });
})();
</script>
