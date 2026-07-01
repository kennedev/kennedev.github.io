/* dashboard.js — listagem e gestão das propostas (consome api.php via fetch). */
(function () {
  'use strict';

  var LABELS = window.STATUS_LABELS || {};
  var H = window.propostaHelpers || {};
  var esc = H.esc || function (s) { return String(s == null ? '' : s); };
  var fmtNum = H.fmtNum || function (v) { return v; };
  var fmtDate = H.fmtDate || function (v) { return v; };

  var $lista = document.getElementById('lista');
  var $busca = document.getElementById('busca');
  var $filtro = document.getElementById('filtro');

  // ---- HTTP -------------------------------------------------------------
  function get(qs) {
    return fetch('api.php?' + qs, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function post(payload) {
    return fetch('api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF || '' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); });
  }

  // ---- Render -----------------------------------------------------------
  function money(v) {
    if (v === null || v === '' || v === undefined) return '<span class="muted">—</span>';
    return 'R$ ' + fmtNum(parseFloat(v));
  }
  function dateOr(v) { return v ? esc(fmtDate(v)) : '<span class="muted">—</span>'; }

  function statusSelect(id, cur) {
    var opts = '';
    Object.keys(LABELS).forEach(function (k) {
      opts += '<option value="' + k + '"' + (k === cur ? ' selected' : '') + '>' + esc(LABELS[k]) + '</option>';
    });
    return '<select class="stsel st-' + esc(cur) + '" data-act="status" data-id="' + id + '">' + opts + '</select>';
  }

  function acoes(id) {
    return '<div class="acoes">' +
      '<a class="btn ghost sm" href="render.php?id=' + id + '" target="_blank" rel="noopener">PDF</a>' +
      '<a class="btn ghost sm" href="novo.php?id=' + id + '">Editar</a>' +
      '<button class="btn ghost sm" data-act="notas" data-id="' + id + '">Notas</button>' +
      '<button class="btn ghost sm" data-act="dup" data-id="' + id + '">Duplicar</button>' +
      '<button class="btn danger sm" data-act="del" data-id="' + id + '">Excluir</button>' +
      '</div>';
  }

  function linha(p) {
    return '<tr data-id="' + p.id + '">' +
      '<td><b>' + esc(p.empresa) + '</b></td>' +
      '<td>' + (p.segmento ? esc(p.segmento) : '<span class="muted">—</span>') + '</td>' +
      '<td>' + dateOr(p.emissao) + '</td>' +
      '<td>' + money(p.valorMensal) + '</td>' +
      '<td>' + statusSelect(p.id, p.status) + '</td>' +
      '<td>' + dateOr(p.atualizadoEm) + '</td>' +
      '<td>' + acoes(p.id) + '</td>' +
    '</tr>';
  }

  function carregar() {
    var qs = 'op=list';
    if ($filtro.value) qs += '&status=' + encodeURIComponent($filtro.value);
    if ($busca.value.trim()) qs += '&q=' + encodeURIComponent($busca.value.trim());
    get(qs).then(function (r) {
      if (!r.ok) { $lista.innerHTML = fila('Erro ao carregar.'); return; }
      if (!r.propostas.length) { $lista.innerHTML = fila('Nenhuma proposta encontrada.'); return; }
      $lista.innerHTML = r.propostas.map(linha).join('');
    }).catch(function () { $lista.innerHTML = fila('Falha de conexão.'); });
  }
  function fila(msg) {
    return '<tr><td colspan="7" class="muted" style="padding:20px 4px">' + esc(msg) + '</td></tr>';
  }

  // ---- Notas (painel expansível) ---------------------------------------
  function toggleNotas(id, trBase) {
    var existente = $lista.querySelector('tr.detail[data-for="' + id + '"]');
    if (existente) { existente.parentNode.removeChild(existente); return; }
    fecharNotas();
    var tr = document.createElement('tr');
    tr.className = 'detail';
    tr.setAttribute('data-for', id);
    tr.innerHTML = '<td colspan="7"><div class="notas-lista" data-box="' + id + '">Carregando notas…</div>' +
      '<div class="row"><div style="flex:1"><input data-cmt="' + id + '" placeholder="Escrever uma nota…" autocomplete="off"></div>' +
      '<button class="btn sm" data-act="addcmt" data-id="' + id + '">Adicionar</button></div></td>';
    trBase.parentNode.insertBefore(tr, trBase.nextSibling);
    renderNotas(id);
  }
  function fecharNotas() {
    var abertos = $lista.querySelectorAll('tr.detail');
    for (var i = 0; i < abertos.length; i++) abertos[i].parentNode.removeChild(abertos[i]);
  }
  function renderNotas(id) {
    var box = $lista.querySelector('[data-box="' + id + '"]');
    if (!box) return;
    get('op=get&id=' + id).then(function (r) {
      if (!r.ok) { box.innerHTML = '<span class="muted">Erro ao carregar notas.</span>'; return; }
      var cs = (r.proposta && r.proposta.comentarios) || [];
      if (!cs.length) { box.innerHTML = '<span class="muted">Nenhuma nota ainda.</span>'; return; }
      box.innerHTML = cs.map(function (c) {
        return '<div class="nota"><div class="quando">' + esc(fmtDate(c.data) + ' ' + (c.data || '').slice(11, 16)) + '</div>' + esc(c.texto) + '</div>';
      }).join('');
    });
  }

  // ---- Ações (event delegation) ----------------------------------------
  $lista.addEventListener('change', function (ev) {
    var el = ev.target;
    if (el.getAttribute && el.getAttribute('data-act') === 'status') {
      var id = el.getAttribute('data-id');
      var novo = el.value;
      post({ op: 'updateStatus', id: +id, status: novo }).then(function (r) {
        if (r.ok) { el.className = 'stsel st-' + novo; recarregarLinhaData(id); }
        else alert('Não foi possível mudar o status.');
      });
    }
  });

  $lista.addEventListener('click', function (ev) {
    var el = ev.target.closest ? ev.target.closest('[data-act]') : null;
    if (!el) return;
    var act = el.getAttribute('data-act');
    var id = el.getAttribute('data-id');

    if (act === 'del') {
      if (!confirm('Excluir esta proposta? Esta ação não pode ser desfeita.')) return;
      post({ op: 'delete', id: +id }).then(function (r) { if (r.ok) carregar(); else alert('Erro ao excluir.'); });
    } else if (act === 'dup') {
      post({ op: 'duplicate', id: +id }).then(function (r) {
        if (r.ok) carregar(); else alert('Erro ao duplicar.');
      });
    } else if (act === 'notas') {
      toggleNotas(id, el.closest('tr'));
    } else if (act === 'addcmt') {
      var inp = $lista.querySelector('[data-cmt="' + id + '"]');
      var texto = (inp.value || '').trim();
      if (!texto) return;
      post({ op: 'addComment', id: +id, texto: texto }).then(function (r) {
        if (r.ok) { inp.value = ''; renderNotas(id); recarregarLinhaData(id); }
        else alert('Erro ao adicionar nota.');
      });
    }
  });

  // Atualiza só a coluna "Atualizado" da linha (após status/nota), sem recarregar tudo.
  function recarregarLinhaData(id) {
    get('op=get&id=' + id).then(function (r) {
      if (!r.ok) return;
      var tr = $lista.querySelector('tr[data-id="' + id + '"]');
      if (tr) tr.children[5].innerHTML = dateOr(r.proposta.atualizadoEm);
    });
  }

  // ---- Filtros ----------------------------------------------------------
  var deb;
  $busca.addEventListener('input', function () { clearTimeout(deb); deb = setTimeout(carregar, 250); });
  $filtro.addEventListener('change', carregar);

  carregar();
})();
