(function () {
  var fab      = document.getElementById('qa-fab');
  var dialog   = document.getElementById('qa-dialog');
  if (!fab || !dialog) return;

  var form      = document.getElementById('qa-form');
  var typeInput = document.getElementById('qa-type');
  var titleInput = document.getElementById('qa-title');
  var statusBox = document.getElementById('qa-status');
  var submitBtn = document.getElementById('qa-submit');
  var endpoint  = dialog.dataset.endpoint;
  var addedCount = 0;
  var usersLoaded = false;
  var tagsLoaded = false;

  var TITLE_PLACEHOLDERS = {
    task: "What's the task?",
    milestone: 'Milestone name',
    risk: "What's the risk or issue?",
    decision: 'What decision is needed?',
    supplier: 'What does the supplier need to do?'
  };

  function setType(type) {
    typeInput.value = type;
    titleInput.placeholder = TITLE_PLACEHOLDERS[type] || '';
    document.querySelectorAll('.qa-tab').forEach(function (tab) {
      tab.classList.toggle('qa-tab--active', tab.dataset.type === type);
    });
    document.querySelectorAll('.qa-fields').forEach(function (block) {
      block.hidden = block.dataset.for !== type;
    });
  }

  document.querySelectorAll('.qa-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      setType(tab.dataset.type);
      titleInput.focus();
    });
  });

  function loadUsers() {
    if (usersLoaded) return;
    usersLoaded = true;
    fetch(endpoint + '?action=users', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var options = (data.users || []).map(function (u) {
          return '<option value="' + u.id + '">' + u.name.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>';
        }).join('');
        document.querySelectorAll('.qa-user-select').forEach(function (select) {
          select.insertAdjacentHTML('beforeend', options);
        });
      })
      .catch(function () { /* selects just stay at "Unassigned" */ });
  }

  function showStatus(kind, messages) {
    statusBox.hidden = false;
    statusBox.className = 'qa-status qa-status--' + kind;
    statusBox.innerHTML = Array.isArray(messages) ? messages.join('<br>') : messages;
  }

  function resetForRepeat() {
    titleInput.value = '';
    form.querySelectorAll('input[type="date"]').forEach(function (input) { input.value = ''; });
    titleInput.focus();
  }

  function escapeHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;'); }

  function loadTags() {
    if (tagsLoaded) return;
    tagsLoaded = true;
    var picker = document.getElementById('qa-tag-picker');
    if (!picker) return;
    fetch(endpoint + '?action=categories', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var categories = (data.categories || []).filter(function (c) { return c.tags && c.tags.length; });
        if (!categories.length) {
          picker.innerHTML = '<p class="empty-note">No tags set up yet.</p>';
          return;
        }
        picker.innerHTML = categories.map(function (c) {
          var options = c.tags.map(function (t) {
            return '<label class="tag-picker-option"><input type="checkbox" name="tag_ids[]" value="' + t.id + '"> ' + escapeHtml(t.name) + '</label>';
          }).join('');
          return '<div class="tag-picker-group"><span class="tag-picker-label">' + escapeHtml(c.name) + '</span><div class="tag-picker-options">' + options + '</div></div>';
        }).join('');
      })
      .catch(function () { picker.innerHTML = ''; });
  }

  fab.addEventListener('click', function () {
    loadUsers();
    loadTags();
    statusBox.hidden = true;
    dialog.showModal();
    titleInput.focus();
  });

  document.getElementById('qa-close').addEventListener('click', function () { dialog.close(); });
  document.getElementById('qa-done').addEventListener('click', function () { dialog.close(); });

  dialog.addEventListener('click', function (e) {
    if (e.target === dialog) dialog.close();
  });

  dialog.addEventListener('close', function () {
    if (addedCount > 0) window.location.reload();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    submitBtn.disabled = true;
    var formData = new FormData(form);
    fetch(endpoint, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
      .then(function (res) {
        submitBtn.disabled = false;
        if (res.ok && res.data.success) {
          addedCount++;
          showStatus('success', '✓ ' + res.data.message + ' Add another below, or press Done.');
          resetForRepeat();
        } else {
          showStatus('error', res.data.errors || ['Something went wrong.']);
        }
      })
      .catch(function () {
        submitBtn.disabled = false;
        showStatus('error', 'Network error — please try again.');
      });
  });

  setType('task');
})();
