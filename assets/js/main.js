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
