/**
 * theme.js — PANTAU Theme Toggle
 * Menggunakan localStorage key 'pantau-theme'
 */
(function() {
    'use strict';

    var STORAGE_KEY = 'pantau-theme';

    function getTheme() {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved) return saved;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }

    function applyTheme(theme) {
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        var btn = document.getElementById('btn-theme-toggle');
        if (btn) {
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'light' ? 'bi bi-moon-fill' : 'bi bi-brightness-high-fill';
            }
            btn.title = theme === 'light' ? 'Ganti ke Dark Mode' : 'Ganti ke Light Mode';
        }
    }

    function toggleTheme() {
        var current = getTheme();
        var next = current === 'light' ? 'dark' : 'light';
        localStorage.setItem(STORAGE_KEY, next);
        applyTheme(next);
    }

    // Apply on load
    applyTheme(getTheme());

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('btn-theme-toggle');
        if (btn) {
            btn.addEventListener('click', toggleTheme);
            applyTheme(getTheme()); // re-apply to update button icon
        }
    });
})();
