    <!-- Footer (sticky) -->
    <footer class="app-footer">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="footer-text">
                    <img src="/assets/image/pantau_logo.png"
                         alt="PANTAU"
                         class="footer-logo-img me-1"
                         width="16" height="16">
                    <strong><?= htmlspecialchars(APP_SHORT) ?></strong>
                    &mdash; Pusat Analitik Transaksi dan Aktivitas User
                </span>
                <span class="footer-text">
                    &copy; 2022&ndash;<?= date('Y') ?> ICT RSUD Kilisuci
                    <span class="footer-divider">·</span>
                    Audit Trail SIMGOS
                </span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- App JS Modules -->
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/utils.js"></script>

    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
