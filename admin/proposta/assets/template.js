/* =========================================================================
   template.js — fonte ÚNICA do documento de proposta.
   Usado por:
     - novo.php  (preview ao vivo, dentro de um <iframe srcdoc>)
     - render.php (view de impressão / PDF)
   Expõe em window: propostaDefaults(), renderDoc(dados), e helpers de cálculo.
   Sem dependências. Todo texto injetado é escapado (esc) para não quebrar o layout.
   ========================================================================= */
(function () {
  'use strict';

  // ---- Helpers ----------------------------------------------------------
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /** Número BR: inteiro sem casas, fracionado com 2 casas (700 → "700", 79.9 → "79,90"). */
  function fmtNum(v) {
    v = Number(v) || 0;
    if (Number.isInteger(v)) return v.toLocaleString('pt-BR');
    return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /** yyyy-mm-dd → dd/mm/yyyy; qualquer outro texto passa direto (permite "15 dias" etc.). */
  function fmtDate(v) {
    v = String(v || '');
    var m = v.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? (m[3] + '/' + m[2] + '/' + m[1]) : v;
  }

  /** Percentual de desconto arredondado: round((cheio-desc)/cheio*100). */
  function calcPercent(cheio, desc) {
    cheio = Number(cheio) || 0; desc = Number(desc) || 0;
    if (cheio <= 0) return 0;
    return Math.round((cheio - desc) / cheio * 100);
  }

  /** "menos de R$ X por dia" = mensal/30, arredondado p/ cima em R$0,10 (79,90/30 → 2,70). */
  function calcPorDia(mensal) {
    var perDay = (Number(mensal) || 0) / 30;
    var ceil10 = Math.ceil(perDay * 10) / 10;
    return ceil10.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /** Substitui placeholders de conteúdo pelos dados atuais. */
  function subst(t, d) {
    return String(t == null ? '' : t)
      .split('{{EMPRESA}}').join(d.empresa || 'sua empresa')
      .split('{{DOMINIO}}').join(d.dominio || 'seu domínio')
      .split('{{VALIDADE}}').join(fmtDate(d.validade))
      .split('{{EMISSAO}}').join(fmtDate(d.emissao));
  }

  /** Divide o título da capa em torno da palavra em destaque. */
  function splitTitulo(titulo, palavra) {
    titulo = String(titulo || ''); palavra = String(palavra || '');
    if (!palavra) return { antes: titulo, palavra: '', depois: '' };
    var i = titulo.indexOf(palavra);
    if (i < 0) return { antes: titulo, palavra: '', depois: '' };
    return { antes: titulo.slice(0, i), palavra: palavra, depois: titulo.slice(i + palavra.length) };
  }

  /** Classe de cor da coluna "como é cobrado" da tabela de novas criações. */
  function cobrancaCls(c) {
    c = String(c || '').toLowerCase();
    if (c.indexOf('inclu') >= 0) return 'inc';
    if (c.indexOf('r$') >= 0) return 'paid';
    return '';
  }

  // ---- Ícones (SVG inline — não usar caractere unicode que pode não imprimir) ----
  var CK =
    '<svg class="ck" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" fill="#EDEBFF"/>' +
    '<path d="M6 10.5l2.5 2.5L14 7.5" stroke="#6C5CE7" stroke-width="2" fill="none" ' +
    'stroke-linecap="round" stroke-linejoin="round"/></svg>';
  var ICO_BENE =
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none">' +
    '<path d="M12 3l1.8 4.7L18.5 9l-4.7 1.8L12 15l-1.8-4.2L5.5 9l4.7-1.3L12 3z" fill="#6C5CE7"/></svg>';

  // ---- Defaults (textos padrão Kennedev — Seção 6.3 do briefing) --------
  function propostaDefaults() {
    var hoje = new Date();
    var val = new Date(hoje.getTime() + 15 * 86400000);
    var iso = function (d) { return d.toISOString().slice(0, 10); };
    return {
      empresa: '', segmento: '', dominio: '', cidadeUf: '', acResponsavel: '', contatoCliente: '',
      emissao: iso(hoje),
      validade: iso(val),
      tituloCapa: 'Seu site profissional, no ar e sempre atualizado.',
      palavraDestaque: 'atualizado',
      subtitulo: 'Proposta de hospedagem, manutenção e evolução contínua do site da {{EMPRESA}} — para você focar no seu negócio enquanto a Kennedev cuida da sua presença digital.',
      valorCriacao: 1200, temDescontoCriacao: false, valorCriacaoCheio: 1200, rotuloCriacao: 'único, já contratado',
      valorMensal: 150, temDesconto: false, valorMensalCheio: 150, rotuloCondicao: 'Condição especial',
      valorNovaCriacao: 200,
      entregue: [
        'Site institucional completo e responsivo (celular, tablet e desktop)',
        'Identidade visual aplicada — cores, tipografia e logotipo',
        'Seções de serviços, sobre e contato prontas',
        'Otimização de velocidade e SEO básico',
        'No ar com domínio próprio e certificado SSL (HTTPS)'
      ],
      objetivo: [
        'Manter o site sempre no ar, rápido e seguro',
        'Atualizar textos, preços e fotos sempre que precisar',
        'Evoluir o site conforme o negócio cresce',
        'Você foca no atendimento; a Kennedev cuida da tecnologia'
      ],
      inclusoes: [
        'Hospedagem gerenciada em servidor rápido e estável',
        'Certificado SSL (cadeado/HTTPS) sempre ativo',
        'Registro e renovação do domínio {{DOMINIO}}',
        'Alterações de conteúdo ilimitadas* (textos, preços, fotos)',
        'Novos itens nas seções já existentes',
        'Correção de erros e ajustes',
        'Backups periódicos e atualizações de segurança',
        'Monitoramento para manter o site no ar',
        'Suporte prioritário (WhatsApp/e-mail), resposta em até 24–48h úteis',
        'Pequenos ajustes visuais e de layout'
      ],
      notaAsterisco: '* Alterações ilimitadas referem-se a ajustes no conteúdo e na estrutura já existentes do site. A criação de itens novos (páginas, landing pages, blog, funcionalidades) é contratada à parte.',
      novasCriacoes: [
        { descricao: 'Trocar preços, textos ou fotos', cobranca: 'Incluso' },
        { descricao: 'Adicionar/editar item nas seções atuais', cobranca: 'Incluso' },
        { descricao: 'Corrigir erros / manter no ar', cobranca: 'Incluso' },
        { descricao: 'Criar nova página', cobranca: 'R$ 200' },
        { descricao: 'Landing page de campanha', cobranca: 'R$ 200' },
        { descricao: 'Blog / seção de conteúdo', cobranca: 'R$ 200' },
        { descricao: 'Nova funcionalidade (integração, agenda)', cobranca: 'sob orçamento' }
      ],
      calloutNovasTitulo: 'Novidade entra, mensalidade não sobe',
      calloutNovasTexto: 'Depois de publicada, cada nova página passa a ser mantida dentro do seu plano mensal, sem custo extra.',
      beneficios: [
        { titulo: 'Mais contatos e orçamentos', texto: 'Um site rápido, claro e sempre no ar transforma visitantes em clientes.' },
        { titulo: 'Zero dor de cabeça técnica', texto: 'Hospedagem, domínio, SSL e backups por nossa conta — você não mexe em nada disso.' },
        { titulo: 'Atualização ágil', texto: 'Mandou o que mudar, a gente altera. Pequenos ajustes saem em até 3 dias úteis.' },
        { titulo: 'Sempre no ar e seguro', texto: 'Monitoramento, HTTPS e atualizações de segurança contínuas para o site não cair.' },
        { titulo: 'Custo previsível', texto: 'Uma mensalidade fixa que cabe no caixa — sem surpresas nem cobranças escondidas.' },
        { titulo: 'Sem aprisionamento', texto: 'O site é seu. Se um dia quiser sair, entregamos os arquivos e apoiamos a transferência.' }
      ],
      condicoes: [
        { rotulo: 'Investimento', valor: 'Manutenção mensal recorrente; novas criações cobradas à parte quando houver' },
        { rotulo: 'Vigência', valor: 'Mensal, sem fidelidade' },
        { rotulo: 'Cancelamento & portabilidade', valor: 'Entregamos os arquivos (.zip) e apoiamos a transferência do domínio; o site é seu' },
        { rotulo: 'Aviso de cancelamento', valor: '15 dias' },
        { rotulo: 'Pagamento', valor: 'PIX, vencimento no dia 10; novas criações 50% + 50%' },
        { rotulo: 'Atendimento', valor: 'Resposta em 24–48h úteis; pequenas alterações em até 3 dias úteis' },
        { rotulo: 'Reajuste', valor: 'Anual, pelo IPCA' },
        { rotulo: 'Validade', valor: 'Proposta válida até {{VALIDADE}}' }
      ],
      ctaTitulo: 'Bora manter a {{EMPRESA}} sempre no ar?',
      ctaTexto: 'Aprovando esta proposta, a Kennedev cuida de tudo — hospedagem, segurança e atualizações contínuas — para você focar no que faz de melhor. Qualquer dúvida, é só chamar.',
      contatoSite: 'kennedev.com.br',
      contatoEmail: 'contato@kennedev.com.br',
      status: 'rascunho',
      comentarios: []
    };
  }

  // ---- Render -----------------------------------------------------------
  function checks(list, d) {
    var out = '';
    (list || []).forEach(function (t) {
      out += '<li>' + CK + '<span>' + esc(subst(t, d)) + '</span></li>';
    });
    return '<ul class="checks">' + out + '</ul>';
  }

  function precos(d) {
    var criacao;
    if (d.temDescontoCriacao) {
      criacao =
        '<div class="price">' +
          '<span class="badge">−' + calcPercent(d.valorCriacaoCheio, d.valorCriacao) + '%</span>' +
          '<div class="tag">Criação do site</div>' +
          '<div class="was">de R$ ' + fmtNum(d.valorCriacaoCheio) + '</div>' +
          '<div class="amt">R$ ' + fmtNum(d.valorCriacao) + '</div>' +
          '<div class="note">' + esc(d.rotuloCriacao || '') + '</div>' +
        '</div>';
    } else {
      criacao =
        '<div class="price">' +
          '<div class="tag">Criação do site</div>' +
          '<div class="amt">R$ ' + fmtNum(d.valorCriacao) + '</div>' +
          '<div class="note">' + esc(d.rotuloCriacao || '') + '</div>' +
        '</div>';
    }

    var manut;
    if (d.temDesconto) {
      manut =
        '<div class="price feature">' +
          '<span class="badge">−' + calcPercent(d.valorMensalCheio, d.valorMensal) + '%</span>' +
          '<div class="tag">Manutenção</div>' +
          '<div class="was">de R$ ' + fmtNum(d.valorMensalCheio) + '/mês</div>' +
          '<div class="amt">R$ ' + fmtNum(d.valorMensal) + '<small>/mês</small></div>' +
          '<div class="note">' + esc(d.rotuloCondicao || 'Condição especial') +
            '. Hospedagem, domínio, segurança e alterações — tudo incluso. Menos de R$ ' +
            calcPorDia(d.valorMensal) + ' por dia.</div>' +
        '</div>';
    } else {
      manut =
        '<div class="price feature">' +
          '<div class="tag">Manutenção mensal</div>' +
          '<div class="amt">R$ ' + fmtNum(d.valorMensal) + '<small>/mês</small></div>' +
          '<div class="note">Hospedagem, domínio, segurança e alterações — tudo incluso. Menos de R$ ' +
            calcPorDia(d.valorMensal) + ' por dia.</div>' +
        '</div>';
    }

    var novas =
      '<div class="price">' +
        '<div class="tag">Novas criações</div>' +
        '<div class="note" style="margin-top:8px;margin-bottom:0">a partir de</div>' +
        '<div class="amt" style="margin-top:2px">R$ ' + fmtNum(d.valorNovaCriacao) + '</div>' +
        '<div class="note">Por página ou landing page nova. Depois de publicada, entra na manutenção.</div>' +
      '</div>';

    return '<div class="prices">' + criacao + manut + novas + '</div>';
  }

  function tabelaNovas(d) {
    var rows = '';
    (d.novasCriacoes || []).forEach(function (r) {
      rows += '<tr><td>' + esc(subst(r.descricao, d)) + '</td>' +
              '<td class="' + cobrancaCls(r.cobranca) + '">' + esc(r.cobranca) + '</td></tr>';
    });
    return '<table><thead><tr><th>Pedido</th><th>Como é cobrado</th></tr></thead><tbody>' +
           rows + '</tbody></table>';
  }

  function beneficios(d) {
    var out = '';
    (d.beneficios || []).forEach(function (b) {
      out +=
        '<div class="bene">' +
          '<div class="bt"><span class="ico">' + ICO_BENE + '</span><h4>' + esc(subst(b.titulo, d)) + '</h4></div>' +
          '<p>' + esc(subst(b.texto, d)) + '</p>' +
        '</div>';
    });
    return '<div class="benes">' + out + '</div>';
  }

  function condicoes(d) {
    var list = (d.condicoes || []).slice();
    if (d.temDesconto) {
      list.unshift({
        rotulo: 'Condição especial',
        valor: (d.rotuloCondicao || 'Desconto') + ': R$ ' + fmtNum(d.valorMensal) + '/mês (de R$ ' +
               fmtNum(d.valorMensalCheio) + '), −' + calcPercent(d.valorMensalCheio, d.valorMensal) + '%'
      });
    }
    var out = '';
    list.forEach(function (c) {
      out += '<li><span class="lab">' + esc(subst(c.rotulo, d)) + '</span>' +
             '<span class="val">' + esc(subst(c.valor, d)) + '</span></li>';
    });
    return '<ul class="cond">' + out + '</ul>';
  }

  function renderDoc(d) {
    d = d || propostaDefaults();
    var t = splitTitulo(d.tituloCapa, d.palavraDestaque);

    // Célula 2 da capa: A/C responsável se houver, senão Segmento.
    var rotulo2 = d.acResponsavel ? 'A/C' : 'Segmento';
    var valor2 = d.acResponsavel ? d.acResponsavel : (d.segmento || '—');

    var cover =
      '<section class="cover">' +
        '<div class="grid"></div>' +
        '<div class="inner">' +
          '<div class="brand"><span class="mark">&gt;</span><span class="name">Kennedev</span></div>' +
          '<div style="margin-top:16px"><span class="pill"><span class="dot"></span>Proposta Comercial · Site &amp; Manutenção</span></div>' +
          '<div class="lead">' +
            '<h1>' + esc(t.antes) + (t.palavra ? '<span class="hl">' + esc(t.palavra) + '</span>' : '') + esc(t.depois) + '</h1>' +
            '<p class="sub">' + esc(subst(d.subtitulo, d)) + '</p>' +
            '<div class="clientbox">' +
              '<div class="c"><div class="k">Preparado para</div><div class="v">' + esc(d.empresa || 'Sua Empresa') + '</div></div>' +
              '<div class="c"><div class="k">' + esc(rotulo2) + '</div><div class="v">' + esc(valor2) + '</div></div>' +
              '<div class="c"><div class="k">Emissão</div><div class="v">' + esc(fmtDate(d.emissao)) + '</div></div>' +
            '</div>' +
            '<div class="foot"><span>' + esc([d.cidadeUf, d.dominio].filter(Boolean).join(' · ') || '—') + '</span>' +
              '<span>Proposta válida até ' + esc(fmtDate(d.validade)) + '</span></div>' +
          '</div>' +
        '</div>' +
        '<div class="accent"></div>' +
      '</section>';

    var apresentacao =
      '<section class="section">' +
        '<div class="eyebrow">A proposta</div>' +
        '<h2 class="sec">Seu site, sempre no ar e bem cuidado</h2>' +
        '<p class="sec-desc">' + esc(subst('Este documento reúne o que já está no ar e como a Kennedev mantém o site da {{EMPRESA}} rápido, seguro e sempre atualizado.', d)) + '</p>' +
        '<div class="grid2">' +
          '<div class="card"><div class="eyebrow" style="color:#1E9E6A">O que já foi entregue</div>' + checks(d.entregue, d) + '</div>' +
          '<div class="card"><div class="eyebrow">O objetivo</div>' + checks(d.objetivo, d) + '</div>' +
        '</div>' +
      '</section>';

    var investimento =
      '<section class="section pb">' +
        '<div class="eyebrow">Investimento</div>' +
        '<h2 class="sec">Valores e tudo o que está incluso</h2>' +
        '<p class="sec-desc">Criação já contratada e uma mensalidade que cobre tudo para o site funcionar sem preocupação.</p>' +
        precos(d) +
        '<div class="callout"><div class="t">O plano mensal inclui</div><div class="col2">' + checks(d.inclusoes, d) + '</div></div>' +
        '<p class="small">' + esc(subst(d.notaAsterisco, d)) + '</p>' +
      '</section>';

    var novas =
      '<section class="section pb">' +
        '<div class="eyebrow">Novas criações</div>' +
        '<h2 class="sec">Precisou de algo novo depois?</h2>' +
        '<p class="sec-desc">Ajustes no que já existe são inclusos. Itens totalmente novos têm preço claro, combinado antes.</p>' +
        tabelaNovas(d) +
        '<div class="callout"><div class="t">' + esc(subst(d.calloutNovasTitulo, d)) + '</div><p>' + esc(subst(d.calloutNovasTexto, d)) + '</p></div>' +
      '</section>';

    var benes =
      '<section class="section pb">' +
        '<div class="eyebrow">Benefícios</div>' +
        '<h2 class="sec">Por que vale a pena</h2>' +
        beneficios(d) +
      '</section>';

    var cond =
      '<section class="section pb">' +
        '<div class="eyebrow">Condições comerciais</div>' +
        '<h2 class="sec">Combinado final</h2>' +
        condicoes(d) +
        '<div class="cta">' +
          '<h3>' + esc(subst(d.ctaTitulo, d)) + '</h3>' +
          '<p>' + esc(subst(d.ctaTexto, d)) + '</p>' +
          '<div class="sign">' +
            '<div class="box"><div class="line">Kennedev</div></div>' +
            '<div class="box"><div class="line">' + esc(d.empresa || 'Cliente') + '</div></div>' +
          '</div>' +
        '</div>' +
        '<div class="contact"><span>' + esc(d.contatoSite || '') + '</span><span>' + esc(d.contatoEmail || '') + '</span></div>' +
      '</section>';

    return cover + apresentacao + investimento + novas + benes + cond;
  }

  // ---- Export -----------------------------------------------------------
  window.propostaDefaults = propostaDefaults;
  window.renderDoc = renderDoc;
  window.propostaHelpers = {
    esc: esc, fmtNum: fmtNum, fmtDate: fmtDate,
    calcPercent: calcPercent, calcPorDia: calcPorDia
  };
})();
