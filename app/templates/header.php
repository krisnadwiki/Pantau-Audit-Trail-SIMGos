<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PANTAU — Pusat ANalitik Transaksi dan Aktivitas User. Monitoring Audit Trail SIMGOS.">
    <meta name="theme-color" content="#0f172a" id="meta-theme-color">
    <title><?= htmlspecialchars($pageTitle ?? APP_SHORT) ?> — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/image/favicon.ico">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom App CSS -->
    <link href="/assets/css/app.css" rel="stylesheet">

    <!--
        ANTI-FLASH: Apply tema dari localStorage SEBELUM CSS dirender.
        Script ini harus berada di <head>, sebelum render pertama.
    -->
    <script>
    (function() {
        var saved = localStorage.getItem('pantau-theme');
        var system = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        var theme = saved || system;
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>

    <!-- Theme JS (load early, before body scripts) -->
    <script src="/assets/js/theme.js"></script>
</head>
<body class="app-body">

