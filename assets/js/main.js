// Clickable table rows (skip clicks on links and buttons)
document.querySelectorAll('tr[data-href]').forEach(function (row) {
  row.addEventListener('click', function (e) {
    if (e.target.closest('a, button, input, label, select, textarea')) return;
    window.location.href = row.dataset.href;
  });
});

// Inline rename: toggle between a .editable-view and a .editable-form
// sharing the same .editable-wrap ancestor.
document.querySelectorAll('.editable-wrap').forEach(function (wrap) {
  var view   = wrap.querySelector('.editable-view');
  var form   = wrap.querySelector('.editable-form');
  var toggle = wrap.querySelector('[data-edit-toggle]');
  var cancel = wrap.querySelector('[data-edit-cancel]');
  if (!view || !form) return;
  if (toggle) {
    toggle.addEventListener('click', function () {
      view.hidden = true;
      form.hidden = false;
      var input = form.querySelector('input[type="text"]');
      if (input) { input.focus(); input.select(); }
    });
  }
  if (cancel) {
    cancel.addEventListener('click', function () {
      form.hidden = true;
      view.hidden = false;
    });
  }
});

// Generic reveal panel: a [data-show-target] button un-hides the element
// with that id and focuses its first text input; a [data-hide-self] button
// inside that element hides its own closest form/panel again.
document.querySelectorAll('[data-show-target]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var el = document.getElementById(btn.dataset.showTarget);
    if (!el) return;
    el.hidden = false;
    var input = el.querySelector('input[type="text"]');
    if (input) input.focus();
  });
});
document.querySelectorAll('[data-hide-self]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var panel = btn.closest('[id]');
    if (panel) panel.hidden = true;
  });
});

// Nav dropdown groups (Work / Reports): click the toggle to open, click
// elsewhere or press Escape to close. Only one group open at a time.
document.querySelectorAll('[data-nav-toggle]').forEach(function (toggle) {
  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    var group = toggle.closest('.nav-group');
    var wasOpen = group.classList.contains('nav-group--open');
    document.querySelectorAll('.nav-group--open').forEach(function (g) {
      g.classList.remove('nav-group--open');
      g.querySelector('[data-nav-toggle]').setAttribute('aria-expanded', 'false');
    });
    if (!wasOpen) {
      group.classList.add('nav-group--open');
      toggle.setAttribute('aria-expanded', 'true');
    }
  });
});
document.addEventListener('click', function () {
  document.querySelectorAll('.nav-group--open').forEach(function (g) {
    g.classList.remove('nav-group--open');
    g.querySelector('[data-nav-toggle]').setAttribute('aria-expanded', 'false');
  });
});
document.addEventListener('keydown', function (e) {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.nav-group--open').forEach(function (g) {
    g.classList.remove('nav-group--open');
    g.querySelector('[data-nav-toggle]').setAttribute('aria-expanded', 'false');
  });
});
