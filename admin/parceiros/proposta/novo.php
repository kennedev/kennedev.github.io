<?php
/* Builder de proposta — formulário + preview ao vivo (iframe).
   Cria (sem ?id) ou edita (?id=N). Os campos são hidratados por builder.js
   a partir de window.PROPOSTA (edição) ou window.propostaDefaults() (novo). */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/layout.php';
exigir_login();
sessao();

$id = (int) ($_GET['id'] ?? 0);
$proposta = null;
if ($id > 0) {
    $st = db()->prepare('SELECT * FROM propostas WHERE id = ?');
    $st->execute(array($id));
    $row = $st->fetch();
    // Parceiro só edita as próprias propostas; admin edita qualquer uma.
    if ($row && !eh_admin() && (int) $row['criado_por'] !== (int) admin_id()) $row = null;
    if ($row) {
        $proposta = json_decode($row['dados'], true);
        if (!is_array($proposta)) $proposta = array();
        $proposta['id']     = (int) $row['id'];
        $proposta['status'] = $row['status'];
    } else {
        $id = 0; // id inexistente → trata como nova
    }
}

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

layout_head($id ? 'Editar proposta' : 'Nova proposta', true);
?>
<style>
.wrap{max-width:1440px}
.builder{display:grid;grid-template-columns:minmax(340px,440px) 1fr;gap:24px;align-items:start}
@media(max-width:1000px){.builder{grid-template-columns:1fr}}
.formcol{min-width:0}
.previewcol{position:sticky;top:16px;align-self:start}
.grp{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:18px 18px 6px;margin-bottom:16px}
.grp>h3{font-size:13px;text-transform:uppercase;letter-spacing:.08em;color:#818cf8;margin-bottom:14px}
.fld{margin-bottom:14px}
textarea{width:100%;padding:11px 14px;background:#0f172a;border:1px solid #334155;border-radius:11px;color:#fff;font-size:14px;font-weight:500;outline:none;font-family:inherit;min-height:70px;resize:vertical;line-height:1.5}
textarea:focus{border-color:#6366f1}
.litem{display:flex;gap:8px;margin-bottom:8px;align-items:flex-start}
.litem>.g{flex:1;min-width:0}
.litem input,.litem textarea{margin:0}
.xbtn{flex:0 0 auto;background:#0f172a;border:1px solid #334155;color:#f87171;border-radius:9px;width:38px;height:42px;cursor:pointer;font-size:18px;line-height:1}
.xbtn:hover{border-color:#dc2626}
.addbtn{background:transparent;border:1px dashed #475569;color:#94a3b8;border-radius:10px;padding:8px 12px;font-size:13px;font-weight:700;cursor:pointer;margin-bottom:12px}
.addbtn:hover{border-color:#6366f1;color:#c7d2fe}
.chk{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.chk input{width:auto}
.chk label{margin:0;text-transform:none;letter-spacing:0;font-size:14px;color:#e2e8f0;font-weight:600}
.hint{font-size:12px;color:#94a3b8;margin-top:-6px;margin-bottom:12px}
.badgeprev{display:inline-block;background:linear-gradient(120deg,#7C6CF6,#4EA8FF);color:#fff;font-weight:800;font-size:12px;padding:2px 9px;border-radius:999px}
.savebar{position:sticky;bottom:0;background:#0f172a;border-top:1px solid #1e293b;padding:14px 0;margin-top:8px;display:flex;gap:10px;align-items:center;z-index:5}
.savebar .err{color:#fca5a5;font-size:13px;font-weight:600}
.previewwrap{background:#334155;border:1px solid #334155;border-radius:12px;overflow:auto;max-height:calc(100vh - 120px);padding:12px}
#scaler{margin:0 auto}
#preview{border:0;background:#fff;display:block;box-shadow:0 8px 30px rgba(0,0,0,.4)}
.previewnote{font-size:12px;color:#64748b;margin-bottom:8px}
</style>

<a href="/admin/parceiros/proposta/" class="sub" style="text-decoration:none;color:#94a3b8">&larr; Voltar às propostas</a>
<h1 style="margin-top:8px"><?= $id ? 'Editar proposta' : 'Nova proposta' ?></h1>
<p class="sub">Preencha empresa e valores — o resto já vem preenchido. O preview atualiza ao digitar.</p>

<div class="builder">
  <!-- ── FORM ─────────────────────────────────────────────────────────── -->
  <div class="formcol">
    <form id="form" autocomplete="off">

      <div class="grp"><h3>Cliente / empresa</h3>
        <div class="fld"><label>Nome da empresa *</label><input data-field="empresa" placeholder="Ex.: M.A. Barbearia"></div>
        <div class="row">
          <div><label>Segmento</label><input data-field="segmento" placeholder="Ex.: Barbearia"></div>
          <div><label>Domínio</label><input data-field="dominio" placeholder="Ex.: mabarbearia.com.br"></div>
        </div>
        <div class="row">
          <div><label>Cidade/UF</label><input data-field="cidadeUf" placeholder="Ex.: São Paulo/SP"></div>
          <div><label>A/C responsável</label><input data-field="acResponsavel" placeholder="opcional"></div>
        </div>
        <div class="fld"><label>Contato do cliente</label><input data-field="contatoCliente" placeholder="opcional (telefone, e-mail)"></div>
      </div>

      <div class="grp"><h3>Proposta</h3>
        <div class="row">
          <div><label>Emissão</label><input type="date" data-field="emissao"></div>
          <div><label>Validade</label><input data-field="validade" placeholder="aaaa-mm-dd ou '15 dias'"></div>
        </div>
        <div class="fld"><label>Título da capa</label><input data-field="tituloCapa"></div>
        <div class="fld"><label>Palavra em destaque (ciano)</label><input data-field="palavraDestaque"></div>
        <div class="fld"><label>Subtítulo da capa</label><textarea data-field="subtitulo"></textarea></div>
      </div>

      <div class="grp"><h3>Valores</h3>
        <div class="row">
          <div><label>Criação — valor (R$)</label><input type="number" step="0.01" data-field="valorCriacao" data-type="number"></div>
          <div><label>Criação — rótulo</label><input data-field="rotuloCriacao"></div>
        </div>
        <div class="chk"><input type="checkbox" id="temDescontoCriacao" data-field="temDescontoCriacao"><label for="temDescontoCriacao">Aplicar desconto na criação</label></div>
        <div id="descontoCriacaoBox" style="display:none">
          <div class="fld"><label>Criação — valor cheio (R$, aparece riscado)</label><input type="number" step="0.01" data-field="valorCriacaoCheio" data-type="number"></div>
          <p class="hint">Desconto calculado: <span class="badgeprev" id="badgePrevCriacao">−0%</span></p>
        </div>
        <div class="fld"><label>Mensalidade (R$)</label><input type="number" step="0.01" data-field="valorMensal" data-type="number"></div>
        <div class="chk"><input type="checkbox" id="temDesconto" data-field="temDesconto"><label for="temDesconto">Aplicar desconto na mensalidade</label></div>
        <div id="descontoBox" style="display:none">
          <div class="row">
            <div><label>Valor cheio (R$)</label><input type="number" step="0.01" data-field="valorMensalCheio" data-type="number"></div>
            <div><label>Rótulo da condição</label><input data-field="rotuloCondicao"></div>
          </div>
          <p class="hint">Desconto calculado: <span class="badgeprev" id="badgePrev">−0%</span></p>
        </div>
        <div class="fld"><label>Novas criações — a partir de (R$)</label><input type="number" step="0.01" data-field="valorNovaCriacao" data-type="number"></div>
      </div>

      <div class="grp"><h3>O que já foi entregue</h3>
        <div data-list="entregue"></div>
        <button type="button" class="addbtn" data-add="entregue">+ adicionar item</button>
      </div>

      <div class="grp"><h3>O objetivo</h3>
        <div data-list="objetivo"></div>
        <button type="button" class="addbtn" data-add="objetivo">+ adicionar item</button>
      </div>

      <div class="grp"><h3>Inclusões do plano mensal</h3>
        <div data-list="inclusoes"></div>
        <button type="button" class="addbtn" data-add="inclusoes">+ adicionar inclusão</button>
      </div>

      <div class="grp"><h3>Novas criações (tabela)</h3>
        <div data-list="novasCriacoes"></div>
        <button type="button" class="addbtn" data-add="novasCriacoes">+ adicionar linha</button>
      </div>

      <div class="grp"><h3>Benefícios</h3>
        <div data-list="beneficios"></div>
        <button type="button" class="addbtn" data-add="beneficios">+ adicionar benefício</button>
      </div>

      <div class="grp"><h3>Condições comerciais</h3>
        <div data-list="condicoes"></div>
        <button type="button" class="addbtn" data-add="condicoes">+ adicionar condição</button>
      </div>

      <div class="grp"><h3>Textos do documento</h3>
        <div class="fld"><label>Nota do asterisco (inclusões)</label><textarea data-field="notaAsterisco"></textarea></div>
        <div class="fld"><label>Callout novas criações — título</label><input data-field="calloutNovasTitulo"></div>
        <div class="fld"><label>Callout novas criações — texto</label><textarea data-field="calloutNovasTexto"></textarea></div>
        <div class="fld"><label>CTA — título</label><input data-field="ctaTitulo"></div>
        <div class="fld"><label>CTA — texto</label><textarea data-field="ctaTexto"></textarea></div>
      </div>

      <div class="grp"><h3>Rodapé / contato Kennedev</h3>
        <div class="row">
          <div><label>Site</label><input data-field="contatoSite"></div>
          <div><label>E-mail</label><input data-field="contatoEmail"></div>
        </div>
      </div>

      <div class="savebar">
        <button type="button" class="btn" id="salvar">Salvar</button>
        <button type="button" class="btn ghost" id="salvarPdf">Salvar e abrir PDF</button>
        <span class="err" id="erro"></span>
      </div>
    </form>
  </div>

  <!-- ── PREVIEW ──────────────────────────────────────────────────────── -->
  <div class="previewcol">
    <p class="previewnote">Preview aproximado. O PDF final (fiel, A4) sai em <b>Salvar e abrir PDF</b> → imprimir.</p>
    <div class="previewwrap"><div id="scaler"><iframe id="preview" title="Preview da proposta"></iframe></div></div>
  </div>
</div>

<script>
window.PROP_ID  = <?= $id ? (int) $id : 'null' ?>;
window.PROPOSTA = <?= $proposta ? json_encode($proposta, $jsonFlags) : 'null' ?>;
window.CSRF     = <?= json_encode(csrf_token(), $jsonFlags) ?>;
</script>
<script src="assets/template.js"></script>
<script src="assets/builder.js"></script>
<?php
layout_foot();
