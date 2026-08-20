(function () {
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta) return;
  var csrfToken = meta.content;

  function updateButton(btn, state) {
    btn.classList.toggle('flag-btn--active', !!state.flaggedByMe);
    btn.setAttribute('aria-pressed', state.flaggedByMe ? 'true' : 'false');
    var names = (state.flaggers || []).map(function (f) { return f.name; });
    btn.title = names.length ? 'Flagged by ' + names.join(', ') : 'Flag for discussion';

    var countEl = btn.querySelector('.flag-btn-count');
    if (state.flaggers && state.flaggers.length > 0) {
      if (!countEl) {
        countEl = document.createElement('span');
        countEl.className = 'flag-btn-count';
        btn.appendChild(countEl);
      }
      countEl.textContent = state.flaggers.length;
    } else if (countEl) {
      countEl.remove();
    }

    // On the discussion page itself, a row that's just been unflagged by
    // everyone should disappear rather than sit there looking flagged-less.
    var row = btn.closest('[data-discussion-row]');
    if (row && (!state.flaggers || state.flaggers.length === 0) && !state.note) {
      row.remove();
    }
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.flag-btn');
    if (!btn || btn.disabled) return;
    var type = btn.dataset.flagType;
    var id = btn.dataset.flagId;
    var endpoint = btn.dataset.endpoint;
    if (!type || !id || !endpoint) return;

    btn.disabled = true;
    var body = 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrfToken);
    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        if (data.success) updateButton(btn, data.state);
      })
      .catch(function () { btn.disabled = false; });
  });
})();
