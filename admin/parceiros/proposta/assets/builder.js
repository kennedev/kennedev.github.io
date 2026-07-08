/* builder.js — formulário + preview ao vivo. Estado único (`state`) alimenta
   tanto os inputs quanto o renderDoc() do iframe. Salva via api.php. */
(function () {
  'use strict';

  var H = window.propostaHelpers;
  var esc = H.esc;

  // Estado: defaults + o que vier do banco (edição). Garante todas as chaves.
  var state = Object.assign(window.propostaDefaults(), window.PROPOSTA || {});

  var form = document.getElementById('form');
  var frame = document.getElementById('preview');
  var scaler = document.getElementById('scaler');
  var badgePrev = document.getElementById('badgePrev');
  var descontoBox = document.getElementById('descontoBox');
  var badgePrevCriacao = document.getElementById('badgePrevCriacao');
  var descontoCriacaoBox = document.getElementById('descontoCriacaoBox');
  var $erro = document.getElementById('erro');

  var FONTS =
    '<link rel="preconnect" href="https://fonts.googleapis.com">' +
    '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' +
    '<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
  var DOCCSS = null;

  // ---- Hidratação dos campos escalares ---------------------------------
  function hydrate() {
    form.querySelectorAll('[data-field]').forEach(function (el) {
      var f = el.getAttribute('data-field');
      if (el.type === 'checkbox') el.checked = !!state[f];
      else el.value = (state[f] == null ? '' : state[f]);
    });
    descontoBox.style.display = state.temDesconto ? '' : 'none';
    descontoCriacaoBox.style.display = state.temDescontoCriacao ? '' : 'none';
    updateBadge();
    updateBadgeCriacao();
    ['entregue', 'objetivo', 'inclusoes', 'novasCriacoes', 'beneficios', 'condicoes'].forEach(renderList);
  }

  function updateBadge() {
    if (badgePrev) badgePrev.textContent = '−' + H.calcPercent(state.valorMensalCheio, state.valorMensal) + '%';
  }
  function updateBadgeCriacao() {
    if (badgePrevCriacao) badgePrevCriacao.textContent = '−' + H.calcPercent(state.valorCriacaoCheio, state.valorCriacao) + '%';
  }

  // ---- Listas editáveis -------------------------------------------------
  function xbtn(name, i) {
    return '<button type="button" class="xbtn" data-del="' + name + '" data-idx="' + i + '" title="Remover">×</button>';
  }
  function inp(name, i, key, val, ph) {
    var attrs = 'data-list="' + name + '" data-idx="' + i + '"' + (key ? ' data-key="' + key + '"' : '');
    return '<input ' + attrs + ' value="' + esc(val) + '" placeholder="' + esc(ph || '') + '">';
  }

  function rowHTML(name, item, i) {
    if (name === 'novasCriacoes') {
      return '<div class="litem">' +
        '<div class="g">' + inp(name, i, 'descricao', item.descricao, 'Descrição do pedido') + '</div>' +
        '<div class="g" style="flex:0 0 140px">' + inp(name, i, 'cobranca', item.cobranca, 'Incluso / R$ 200') + '</div>' +
        xbtn(name, i) + '</div>';
    }
    if (name === 'beneficios') {
      return '<div class="litem" style="flex-direction:column;gap:6px;border:1px solid #334155;border-radius:12px;padding:10px 10px 12px;position:relative">' +
        inp(name, i, 'titulo', item.titulo, 'Título do benefício') +
        '<textarea data-list="' + name + '" data-idx="' + i + '" data-key="texto" placeholder="Texto">' + esc(item.texto) + '</textarea>' +
        '<button type="button" class="xbtn" data-del="' + name + '" data-idx="' + i + '" style="position:absolute;top:8px;right:8px;width:30px;height:30px" title="Remover">×</button>' +
        '</div>';
    }
    if (name === 'condicoes') {
      return '<div class="litem">' +
        '<div class="g" style="flex:0 0 38%">' + inp(name, i, 'rotulo', item.rotulo, 'Rótulo') + '</div>' +
        '<div class="g">' + inp(name, i, 'valor', item.valor, 'Valor') + '</div>' +
        xbtn(name, i) + '</div>';
    }
    // listas de string simples
    return '<div class="litem"><div class="g">' + inp(name, i, null, item, 'Item') + '</div>' + xbtn(name, i) + '</div>';
  }

  function renderList(name) {
    var box = form.querySelector('[data-list="' + name + '"]');
    if (!box) return;
    var arr = state[name] || [];
    box.innerHTML = arr.map(function (item, i) { return rowHTML(name, item, i); }).join('');
  }

  function novoItem(name) {
    if (name === 'novasCriacoes') return { descricao: '', cobranca: 'Incluso' };
    if (name === 'beneficios') return { titulo: '', texto: '' };
    if (name === 'condicoes') return { rotulo: '', valor: '' };
    return '';
  }

  // ---- Eventos ----------------------------------------------------------
  form.addEventListener('input', function (ev) {
    var el = ev.target;
    var f = el.getAttribute('data-field');
    if (f) {
      state[f] = el.getAttribute('data-type') === 'number' ? (parseFloat(el.value) || 0) : el.value;
      if (f === 'valorMensal' || f === 'valorMensalCheio') updateBadge();
      if (f === 'valorCriacao' || f === 'valorCriacaoCheio') updateBadgeCriacao();
    } else if (el.getAttribute('data-list') != null) {
      var name = el.getAttribute('data-list');
      var i = +el.getAttribute('data-idx');
      var key = el.getAttribute('data-key');
      if (key) state[name][i][key] = el.value;
      else state[name][i] = el.value;
    } else { return; }
    schedule();
  });

  form.addEventListener('change', function (ev) {
    var el = ev.target;
    if (el.getAttribute('data-field') === 'temDesconto') {
      state.temDesconto = el.checked;
      descontoBox.style.display = el.checked ? '' : 'none';
      updateBadge();
      schedule();
    } else if (el.getAttribute('data-field') === 'temDescontoCriacao') {
      state.temDescontoCriacao = el.checked;
      descontoCriacaoBox.style.display = el.checked ? '' : 'none';
      updateBadgeCriacao();
      schedule();
    }
  });

  form.addEventListener('click', function (ev) {
    var el = ev.target.closest ? ev.target.closest('[data-add],[data-del]') : null;
    if (!el) return;
    var add = el.getAttribute('data-add');
    var del = el.getAttribute('data-del');
    if (add) {
      state[add] = state[add] || [];
      state[add].push(novoItem(add));
      renderList(add);
      schedule();
    } else if (del) {
      state[del].splice(+el.getAttribute('data-idx'), 1);
      renderList(del);
      schedule();
    }
  });

  // ---- Preview (iframe escalado para caber na coluna) -------------------
  var deb;
  function schedule() { clearTimeout(deb); deb = setTimeout(paint, 200); }

  function paint() {
    if (DOCCSS === null) return;
    var html = '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">' +
      FONTS + '<style>' + DOCCSS + '</style></head><body>' + window.renderDoc(state) + '</body></html>';
    frame.onload = sizeFrame;
    frame.srcdoc = html;
  }

  function sizeFrame() {
    try {
      var h = frame.contentDocument.documentElement.scrollHeight;
      frame.style.width = '794px';        // A4 ≈ 210mm @96dpi
      frame.style.height = h + 'px';
      frame.style.transformOrigin = 'top left';
      var wrap = frame.closest('.previewwrap');
      var avail = wrap.clientWidth - 24;  // menos o padding
      var scale = Math.min(1, avail / 794);
      frame.style.transform = 'scale(' + scale + ')';
      scaler.style.width = (794 * scale) + 'px';
      scaler.style.height = (h * scale) + 'px';
    } catch (e) { /* srcdoc same-origin; ignora se indisponível */ }
  }

  var rdeb;
  window.addEventListener('resize', function () { clearTimeout(rdeb); rdeb = setTimeout(sizeFrame, 150); });

  // ---- Salvar -----------------------------------------------------------
  function post(payload) {
    return fetch('api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF || '' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); });
  }

  function showErr(m) { $erro.textContent = m || ''; }

  function save(destino) {
    state.empresa = (state.empresa || '').trim();
    if (!state.empresa) {
      showErr('Informe o nome da empresa.');
      var emp = form.querySelector('[data-field="empresa"]'); if (emp) emp.focus();
      return;
    }
    showErr('Salvando…');
    var op = window.PROP_ID ? 'update' : 'create';
    var payload = { op: op, dados: state };
    if (window.PROP_ID) payload.id = window.PROP_ID;
    post(payload).then(function (r) {
      if (!r.ok) { showErr('Erro ao salvar (' + (r.erro || '?') + ').'); return; }
      var id = window.PROP_ID || r.id;
      if (destino === 'pdf') window.location.href = 'render.php?id=' + id;
      else window.location.href = '/admin/parceiros/proposta/';
    }).catch(function () { showErr('Falha de conexão.'); });
  }

  document.getElementById('salvar').addEventListener('click', function () { save('lista'); });
  document.getElementById('salvarPdf').addEventListener('click', function () { save('pdf'); });

  // ---- Init -------------------------------------------------------------
  hydrate();
  fetch('assets/doc.css', { credentials: 'same-origin' })
    .then(function (r) { return r.text(); })
    .then(function (css) { DOCCSS = css; paint(); });
})();
