/**
 * utils.js — PANTAU Utility Functions
 */

window.Utils = (function() {
    'use strict';

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
    }

    function fmtNum(n) {
        return Number(n).toLocaleString('id-ID');
    }

    function fmtDate(dt) {
        if (!dt) return '—';
        const d = new Date(dt);
        if (isNaN(d)) return dt;
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
             + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function showAlert(msg, type = 'success') {
        let container = document.querySelector('.alert-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert-container';
            document.body.appendChild(container);
        }
        const el = document.createElement('div');
        el.className = `app-alert alert-${type}`;
        const icons = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill', warning: 'bi-exclamation-circle-fill', info: 'bi-info-circle-fill' };
        el.innerHTML = `<i class="bi ${icons[type] || 'bi-info-circle-fill'}"></i><span>${esc(msg)}</span>`;
        container.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    return { esc, fmtNum, fmtDate, showAlert };
})();
