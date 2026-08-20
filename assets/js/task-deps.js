(function () {
  var wrap    = document.getElementById('dep-picker');
  if (!wrap) return;

  var input    = document.getElementById('dep-search');
  var results  = document.getElementById('dep-search-results');
  var list     = document.getElementById('dep-list');
  var emptyNote = document.getElementById('dep-empty');
  var searchUrl = wrap.dataset.searchUrl;
  var excludeId = wrap.dataset.exclude || '0';
  var debounceTimer = null;

  function updateEmptyNote() {
    if (emptyNote) emptyNote.hidden = list.children.length > 0;
  }

  function alreadyAdded(id) {
    return Array.from(list.children).some(function (chip) { return chip.dataset.id === String(id); });
  }

  function statusLabel(status) {
    return { todo: 'To do', in_progress: 'In progress', done: 'Done' }[status] || status;
  }

  function addChip(id, title, status) {
    if (alreadyAdded(id)) return;
    var chip = document.createElement('div');
    chip.className = 'attendee-chip';
    chip.dataset.id = id;
    chip.innerHTML =
      '<span class="attendee-chip-name"></span>' +
      '<button type="button" class="attendee-chip-remove" aria-label="Remove">&times;</button>' +
      '<input type="hidden" name="depends_on_ids[]" value="' + id + '">';
    var nameSpan = chip.querySelector('.attendee-chip-name');
    nameSpan.textContent = title + ' ';
    var statusSpan = document.createElement('span');
    statusSpan.className = 'dep-picker-status';
    statusSpan.textContent = '(' + statusLabel(status) + ')';
    nameSpan.appendChild(statusSpan);
    list.appendChild(chip);
    updateEmptyNote();
  }

  function hideResults() { results.hidden = true; results.innerHTML = ''; }

  function renderResults(tasks, isDefault) {
    var label = isDefault ? '<div class="dep-search-label">Recently updated — or start typing to search</div>' : '';
    if (!tasks.length) {
      results.innerHTML = label + '<div class="dep-search-empty">No matching tasks.</div>';
      results.hidden = false;
      return;
    }
    results.innerHTML = label + tasks.map(function (t) {
      return '<div class="dep-search-result" data-id="' + t.id + '" data-title="' + t.title.replace(/"/g, '&quot;') + '" data-status="' + t.status + '">' +
        t.title.replace(/&/g, '&amp;').replace(/</g, '&lt;') + ' <span class="dep-picker-status">(' + statusLabel(t.status) + ')</span></div>';
    }).join('');
    results.hidden = false;
  }

  function fetchAndRender(q) {
    fetch(searchUrl + '?q=' + encodeURIComponent(q) + '&exclude=' + excludeId, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) { renderResults(data.tasks || [], !!data.isDefault); })
      .catch(hideResults);
  }

  input.addEventListener('focus', function () {
    if (input.value.trim() === '') fetchAndRender('');
  });

  input.addEventListener('input', function () {
    var q = input.value.trim();
    clearTimeout(debounceTimer);
    if (q === '') { fetchAndRender(''); return; }
    debounceTimer = setTimeout(function () { fetchAndRender(q); }, 250);
  });

  results.addEventListener('click', function (e) {
    var row = e.target.closest('.dep-search-result');
    if (!row || !row.dataset.id) return;
    addChip(row.dataset.id, row.dataset.title, row.dataset.status);
    input.value = '';
    hideResults();
    input.focus();
  });

  list.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.attendee-chip-remove');
    if (!removeBtn) return;
    removeBtn.closest('.attendee-chip').remove();
    updateEmptyNote();
  });

  document.addEventListener('click', function (e) {
    if (!wrap.contains(e.target)) hideResults();
  });

  updateEmptyNote();
})();
