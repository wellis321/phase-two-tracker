(function () {
  var copyBtn = document.getElementById('agenda-copy-link');
  if (!copyBtn) return;

  copyBtn.addEventListener('click', function () {
    var url = copyBtn.dataset.url;
    var originalText = copyBtn.textContent;
    function showCopied() {
      copyBtn.textContent = 'Link copied';
      setTimeout(function () { copyBtn.textContent = originalText; }, 1800);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showCopied, function () {
        window.prompt('Copy this link:', url);
      });
    } else {
      window.prompt('Copy this link:', url);
    }
  });
})();
