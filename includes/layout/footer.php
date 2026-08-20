  </div>
</main>

<?php if (is_logged_in() && is_admin()): ?>
<button type="button" id="qa-fab" class="qa-fab" aria-haspopup="dialog" aria-label="Quick add">+</button>
<dialog id="qa-dialog" class="qa-dialog" data-endpoint="<?= APP_URL ?>/quick-add.php">
  <div class="qa-head">
    <h2>Quick add</h2>
    <button type="button" class="qa-close" id="qa-close" aria-label="Close">&times;</button>
  </div>
  <div class="qa-tabs" role="tablist">
    <button type="button" class="qa-tab qa-tab--active" data-type="task" role="tab">Task</button>
    <button type="button" class="qa-tab" data-type="milestone" role="tab">Milestone</button>
    <button type="button" class="qa-tab" data-type="risk" role="tab">Risk / Issue</button>
    <button type="button" class="qa-tab" data-type="decision" role="tab">Decision</button>
    <button type="button" class="qa-tab" data-type="supplier" role="tab">Supplier</button>
  </div>
  <div class="qa-status" id="qa-status" hidden></div>
  <form id="qa-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="type" id="qa-type" value="task">

    <div class="field form-full">
      <label for="qa-title">Title</label>
      <input type="text" id="qa-title" name="title" required>
    </div>

    <div class="qa-fields form-grid" data-for="task">
      <div class="field"><label for="qa-task-due">Due date</label><input type="date" id="qa-task-due" name="due_date"></div>
      <div class="field"><label for="qa-task-assignee">Assignee</label><select id="qa-task-assignee" name="assignee_user_id" class="qa-user-select"><option value="">Unassigned</option></select></div>
      <div class="field form-full">
        <label>Tags</label>
        <div id="qa-tag-picker" class="tag-picker"></div>
      </div>
    </div>
    <div class="qa-fields form-grid" data-for="milestone" hidden>
      <div class="field"><label for="qa-ms-phase">Phase</label><input type="text" id="qa-ms-phase" name="phase" placeholder="e.g. Solution Design"></div>
      <div class="field"><label for="qa-ms-date">Target date</label><input type="date" id="qa-ms-date" name="target_date"></div>
    </div>
    <div class="qa-fields form-grid" data-for="risk" hidden>
      <div class="field">
        <label for="qa-risk-type">Type</label>
        <select id="qa-risk-type" name="risk_type"><option value="risk">Risk</option><option value="issue">Issue</option></select>
      </div>
      <div class="field">
        <label for="qa-risk-severity">Severity</label>
        <select id="qa-risk-severity" name="severity">
          <option value="red">Red</option>
          <option value="amber" selected>Amber</option>
          <option value="green">Green</option>
        </select>
      </div>
    </div>
    <div class="qa-fields form-grid" data-for="decision" hidden>
      <div class="field"><label for="qa-dec-owner">Owner</label><select id="qa-dec-owner" name="decision_owner_user_id" class="qa-user-select"><option value="">Unassigned</option></select></div>
      <div class="field"><label for="qa-dec-needed">Needed by</label><input type="date" id="qa-dec-needed" name="needed_by_date"></div>
    </div>
    <div class="qa-fields form-grid" data-for="supplier" hidden>
      <div class="field"><label for="qa-sup-supplier">Supplier</label><input type="text" id="qa-sup-supplier" name="supplier" value="ROCC"></div>
      <div class="field"><label for="qa-sup-due">Due date</label><input type="date" id="qa-sup-due" name="due_date"></div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn--primary" id="qa-submit">Add &amp; add another</button>
      <button type="button" class="btn btn--outline" id="qa-done">Done</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<footer class="site-footer">
  <div class="footer-inner">
    <span class="footer-env footer-env--<?= e(APP_ENV) ?>"><?= e(app_env_label()) ?></span>
    <div class="footer-tools">
      <?php if (SOR_SYSTEM_URL !== ''): ?><a href="<?= e(SOR_SYSTEM_URL) ?>/">SOR System</a><?php endif; ?>
      <a href="<?= e(ERC_SITE_URL) ?>/">ERC Portal</a>
      <a href="<?= e(ASIS_SITE_URL) ?>/">AS-IS Mapping</a>
      <a href="<?= e(METRICS_SITE_URL) ?>/">Housing Metrics</a>
    </div>
  </div>
</footer>
<script src="<?= asset_url('/assets/js/main.js') ?>"></script>
<?php if (is_logged_in()): ?>
<script src="<?= asset_url('/assets/js/discussion-flag.js') ?>"></script>
<?php endif; ?>
<?php if (is_logged_in() && is_admin()): ?>
<script src="<?= asset_url('/assets/js/quick-add.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($includeHelpNavJs)): ?>
<script src="<?= asset_url('/assets/js/help-doc-nav.js') ?>"></script>
<?php endif; ?>
</body>
</html>
