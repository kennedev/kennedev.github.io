<?php
/* Dashboard das propostas — listagem, filtro, status inline, notas e ações.
   A tabela é preenchida via fetch em assets/dashboard.js (API em api.php). */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/layout.php';
exigir_login();
sessao();

$LABELS = array(
    'rascunho'   => 'Rascunho',
    'emitida'    => 'Emitida',
    'enviada'    => 'Enviada',
    'respondida' => 'Respondida',
    'aceita'     => 'Aceita',
    'rejeitada'  => 'Rejeitada',
);

layout_head('Propostas', true);
?>
<style>
/* Cores dos status (page-scoped; não mexe no .tag global do layout). */
.stsel{width:auto;min-width:120px;padding:6px 10px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;border-radius:999px;cursor:pointer}
.st-rascunho{background:#334155;color:#cbd5e1;border-color:#475569}
.st-emitida{background:#1e3a8a;color:#bfdbfe;border-color:#1d4ed8}
.st-enviada{background:#3730a3;color:#c7d2fe;border-color:#4338ca}
.st-respondida{background:#78350f;color:#fcd34d;border-color:#b45309}
.st-aceita{background:#14532d;color:#86efac;border-color:#15803d}
.st-rejeitada{background:#7f1d1d;color:#fca5a5;border-color:#b91c1c}
#lista td{vertical-align:middle}
.muted{color:#475569}
tr.detail>td{background:#0f172a;padding:16px}
.notas-lista{margin:0 0 12px;display:flex;flex-direction:column;gap:8px}
.nota{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:8px 12px;font-size:13px}
.nota .quando{color:#64748b;font-size:11px;margin-bottom:2px}
</style>
<h1>Gerador de Proposta</h1>
<p class="sub">Crie, acompanhe e baixe suas propostas comerciais.</p>

<div class="card">
  <div class="row">
    <div style="flex:2"><label>Buscar empresa</label><input id="busca" placeholder="Nome da empresa…" autocomplete="off"></div>
    <div><label>Status</label>
      <select id="filtro">
        <option value="">Todos</option>
        <?php foreach ($LABELS as $k => $v) echo '<option value="' . e($k) . '">' . e($v) . '</option>'; ?>
      </select>
    </div>
    <a class="btn" href="/admin/parceiros/proposta/novo.php">+ Nova proposta</a>
  </div>
</div>

<div class="card" style="padding:8px 16px">
  <table>
    <thead><tr>
      <th>Empresa</th><th>Segmento</th><th>Emissão</th><th>Mensal</th>
      <th>Status</th><th>Atualizado</th><th>Ações</th>
    </tr></thead>
    <tbody id="lista"><tr><td colspan="7" class="muted" style="padding:20px 4px">Carregando…</td></tr></tbody>
  </table>
</div>

<script>
window.CSRF = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
window.STATUS_LABELS = <?= json_encode($LABELS, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/template.js"></script>
<script src="assets/dashboard.js"></script>
<?php
layout_foot();
