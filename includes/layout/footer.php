  </div>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <span class="footer-env footer-env--<?= e(APP_ENV) ?>"><?= e(strtoupper(APP_ENV)) ?></span>
    <div class="footer-tools">
      <?php if (SOR_SYSTEM_URL !== ''): ?><a href="<?= e(SOR_SYSTEM_URL) ?>/">SOR System</a><?php endif; ?>
      <a href="<?= e(ERC_SITE_URL) ?>/">ERC Portal</a>
      <a href="<?= e(ASIS_SITE_URL) ?>/">AS-IS Mapping</a>
      <a href="<?= e(METRICS_SITE_URL) ?>/">Housing Metrics</a>
    </div>
  </div>
</footer>
<?php if (!empty($includeHelpNavJs)): ?>
<script src="<?= asset_url('/assets/js/help-doc-nav.js') ?>"></script>
<?php endif; ?>
</body>
</html>
