<?php
/**
 * index.php — Redirect ke halaman utama
 */
require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_data'])) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
