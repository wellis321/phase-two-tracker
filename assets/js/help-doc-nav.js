// Sticky doc sidebar: smooth in-page jumps, hash on load, scroll-spy, keep active link visible.
(function () {
  if (window.location.hash && 'scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }

  var pendingHashTimers = [];
  var userNavigatingUntil = 0;

  function isPublicDocPage() {
    return document.body.classList.contains('landing-body');
  }

  function scrollOffset() {
    var header = document.querySelector('.site-header');
    if (header) {
      return header.getBoundingClientRect().height + 12;
    }
    return isPublicDocPage() ? 12 : 24;
  }

  function elementTop(el) {
    return el.getBoundingClientRect().top + window.scrollY;
  }

  function scrollToEl(el, smooth) {
    if (!el) return;
    // scrollIntoView honours scroll-margin-top on .help-card / .about-section
    // (4.5rem when logged in, 1.5rem on public help/about pages).
    el.scrollIntoView({
      block: 'start',
      behavior: smooth ? 'smooth' : 'auto',
    });
  }

  // Keep the active item visible inside the sticky nav only — never scroll the page.
  function scrollNavLinkIntoView(link, smooth) {
    var nav = link.closest('.help-nav');
    if (!nav) return;
    var navStyle = window.getComputedStyle(nav);
    var navScrollable = nav.scrollHeight > nav.clientHeight + 2
      && navStyle.overflowY !== 'visible'
      && navStyle.overflowY !== 'hidden';
    if (!navScrollable) return;

    var navRect = nav.getBoundingClientRect();
    var linkRect = link.getBoundingClientRect();
    if (linkRect.top >= navRect.top && linkRect.bottom <= navRect.bottom) return;

    var behavior = smooth ? 'smooth' : 'auto';
    var linkTop = linkRect.top - navRect.top + nav.scrollTop;
    var target = linkTop - nav.clientHeight / 2 + linkRect.height / 2;
    if (typeof nav.scrollTo === 'function') {
      nav.scrollTo({ top: Math.max(0, target), behavior: behavior });
    } else {
      nav.scrollTop = Math.max(0, target);
    }
  }

  function cancelPendingHash() {
    pendingHashTimers.forEach(clearTimeout);
    pendingHashTimers = [];
  }

  function initNav(nav) {
    if (!nav || nav.dataset.helpDocNavInit === '1') return;
    nav.dataset.helpDocNavInit = '1';

    var links = nav.querySelectorAll('a[href^="#"]');
    var headings = [];
    links.forEach(function (a) {
      var id = a.getAttribute('href');
      if (!id || id.length < 2) return;
      var el = document.querySelector(id);
      if (el) headings.push({ el: el, a: a });
    });
    headings.sort(function (a, b) {
      return elementTop(a.el) - elementTop(b.el);
    });

    links.forEach(function (a) {
      a.addEventListener('click', function (e) {
        var hash = a.getAttribute('href');
        if (!hash || hash.length < 2) return;
        var el = document.querySelector(hash);
        if (!el) return;
        e.preventDefault();
        cancelPendingHash();
        userNavigatingUntil = Date.now() + 900;
        scrollToEl(el, true);
        if (window.location.hash !== hash) {
          if (history.pushState) {
            history.pushState(null, '', hash);
          } else {
            window.location.hash = hash.slice(1);
          }
        }
        a.classList.add('help-nav--active');
        links.forEach(function (link) {
          if (link !== a) link.classList.remove('help-nav--active');
        });
        window.setTimeout(function () {
          scrollNavLinkIntoView(a, true);
        }, 50);
      });
    });

    var activeLink = null;
    function syncActive(scrollNav) {
      if (Date.now() < userNavigatingUntil) return;
      var pos = window.scrollY + scrollOffset() + 8;
      var active = null;
      headings.forEach(function (h) {
        if (elementTop(h.el) <= pos) active = h;
      });
      links.forEach(function (a) { a.classList.remove('help-nav--active'); });
      if (!active) return;
      active.a.classList.add('help-nav--active');
      if (scrollNav && active.a !== activeLink) {
        activeLink = active.a;
        scrollNavLinkIntoView(active.a, true);
      }
    }

    window.addEventListener('scroll', function () { syncActive(true); }, { passive: true });
    syncActive(false);

    function goToHash(smooth) {
      var hash = window.location.hash;
      if (!hash) return;
      var el = document.querySelector(hash);
      if (!el) return;
      userNavigatingUntil = Date.now() + (smooth ? 900 : 100);
      scrollToEl(el, smooth);
      syncActive(false);
      window.setTimeout(function () { syncActive(true); }, smooth ? 400 : 50);
    }

    nav._helpDocGoToHash = goToHash;
  }

  function initAll() {
    document.querySelectorAll('[data-help-doc-nav]').forEach(initNav);
  }

  function initialHash(smooth) {
    if (!window.location.hash) return;
    var hash = window.location.hash;
    if (!document.querySelector(hash)) return;
    var ran = false;
    document.querySelectorAll('[data-help-doc-nav]').forEach(function (nav) {
      if (typeof nav._helpDocGoToHash === 'function') {
        nav._helpDocGoToHash(smooth);
        ran = true;
      }
    });
    if (!ran) {
      userNavigatingUntil = Date.now() + 100;
      scrollToEl(document.querySelector(hash), smooth);
    }
  }

  function scheduleInitialHash(smooth, delay) {
    pendingHashTimers.push(window.setTimeout(function () { initialHash(smooth); }, delay));
  }

  function scheduleHashLanding() {
    if (!window.location.hash) return;
    cancelPendingHash();
    initialHash(false);
  }

  function boot() {
    initAll();
    if (!window.location.hash) return;
    scheduleHashLanding();
    scheduleInitialHash(false, 50);
    scheduleInitialHash(false, 200);
    if (isPublicDocPage()) {
      scheduleInitialHash(false, 500);
      scheduleInitialHash(false, 1000);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('load', function () {
    if (!window.location.hash) return;
    cancelPendingHash();
    initialHash(false);
    scheduleInitialHash(true, 80);
    if (isPublicDocPage()) {
      scheduleInitialHash(false, 400);
    }
  });

  window.addEventListener('hashchange', function () {
    cancelPendingHash();
    initialHash(true);
  });
})();
