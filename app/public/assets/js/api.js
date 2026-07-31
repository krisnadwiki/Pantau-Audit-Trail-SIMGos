/**
 * api.js — PANTAU API Helper
 * Wrapper untuk fetch ke /api/audit.php
 */

window.API = (function() {
    'use strict';

    async function get(action, params = {}) {
        params.action = action;
        const qs = new URLSearchParams(params).toString();
        const res = await fetch('/api/audit.php?' + qs, {
            headers: { 'Accept': 'application/json' }
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Response API tidak valid: ' + text.replace(/<[^>]*>/g, '').trim().substring(0, 120));
        }
        if (!res.ok) {
            throw new Error(data.message || 'HTTP ' + res.status);
        }
        return data;
    }

    return { get };
})();
