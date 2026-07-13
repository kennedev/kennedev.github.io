/* Vicente Corretor de Imóveis — interações leves, vanilla. */
(function () {
  'use strict';
  var reduz = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── Header solidifica ao rolar ──────────────────────────
  var header = document.getElementById('siteHeader');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('scrolled', window.scrollY > 24);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // ── Fecha menu mobile ao clicar num link ────────────────
  var navToggle = document.getElementById('navtoggle');
  if (navToggle) {
    document.querySelectorAll('.site-nav a').forEach(function (a) {
      a.addEventListener('click', function () { navToggle.checked = false; });
    });
  }

  // ── Reveal on scroll ────────────────────────────────────
  var alvos = document.querySelectorAll('.reveal');
  if (reduz || !('IntersectionObserver' in window)) {
    alvos.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    alvos.forEach(function (el) { io.observe(el); });
  }

  // ── Auto-submit nos selects de ordenação/filtro ─────────
  document.querySelectorAll('[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      if (sel.form) sel.form.submit();
    });
  });

  // ── Lightbox da galeria ─────────────────────────────────
  var gatilhos = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
  if (gatilhos.length) {
    var fotos = gatilhos.map(function (g) {
      return { src: g.getAttribute('data-lightbox'), alt: g.getAttribute('data-alt') || '' };
    });
    var atual = 0;

    var lb = document.createElement('div');
    lb.className = 'lb';
    lb.innerHTML =
      '<button class="lb-btn lb-close" aria-label="Fechar">&times;</button>' +
      '<button class="lb-btn lb-prev" aria-label="Anterior">&#8249;</button>' +
      '<img alt="">' +
      '<button class="lb-btn lb-next" aria-label="Próxima">&#8250;</button>' +
      '<div class="lb-count"></div>';
    document.body.appendChild(lb);

    var imgEl = lb.querySelector('img');
    var countEl = lb.querySelector('.lb-count');

    function mostra(i) {
      atual = (i + fotos.length) % fotos.length;
      imgEl.src = fotos[atual].src;
      imgEl.alt = fotos[atual].alt;
      countEl.textContent = (atual + 1) + ' / ' + fotos.length;
    }
    function abre(i) { mostra(i); lb.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function fecha() { lb.classList.remove('open'); document.body.style.overflow = ''; }

    gatilhos.forEach(function (g, i) {
      g.addEventListener('click', function (e) { e.preventDefault(); abre(i); });
    });
    lb.querySelector('.lb-close').addEventListener('click', fecha);
    lb.querySelector('.lb-prev').addEventListener('click', function () { mostra(atual - 1); });
    lb.querySelector('.lb-next').addEventListener('click', function () { mostra(atual + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) fecha(); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape') fecha();
      else if (e.key === 'ArrowLeft') mostra(atual - 1);
      else if (e.key === 'ArrowRight') mostra(atual + 1);
    });
  }
})();
