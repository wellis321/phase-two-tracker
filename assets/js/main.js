// Clickable table rows (skip clicks on links and buttons)
document.querySelectorAll('tr[data-href]').forEach(function (row) {
  row.addEventListener('click', function (e) {
    if (e.target.closest('a, button, input, label, select, textarea')) return;
    window.location.href = row.dataset.href;
  });
});
