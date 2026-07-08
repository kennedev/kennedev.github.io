<?php
/* clientes.php — clientes fechados + comissões.
   · Admin:    CRUD completo (escolhe parceiro, valores, prazo, vínculo à proposta).
   · Parceiro: somente-leitura da própria carteira ("quanto vou receber").
   As comissões são calculadas em runtime (lib_comissao.php); nada redundante no banco. */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/lib_comissao.php';
exigir_login();
sessao();

$souAdmin = eh_admin();

/** Lê e valida os campos do formulário de cliente (compartilhado entre criar/atualizar). */
function ler_form() {
    $primeira = trim($_POST['primeira_recorrencia'] ?? '');
    $pctProj  = trim($_POST['percentual_projeto'] ?? '');
    $pctRec   = trim($_POST['percentual_recorrencia'] ?? '');
    $propId   = (int) ($_POST['proposta_id'] ?? 0);
    $status   = $_POST['status'] ?? 'ativo';
    return array(
        'parceiro_id'            => (int) ($_POST['parceiro_id'] ?? 0),
        'proposta_id'            => $propId > 0 ? $propId : null,
        'empresa'                => trim($_POST['empresa'] ?? ''),
        'valor_projeto'          => round((float) str_replace(',', '.', $_POST['valor_projeto'] ?? '0'), 2),
        'valor_recorrencia'      => round((float) str_replace(',', '.', $_POST['valor_recorrencia'] ?? '0'), 2),
        'percentual_projeto'     => $pctProj === '' ? null : round((float) str_replace(',', '.', $pctProj), 2),
        'percentual_recorrencia' => $pctRec === '' ? null : round((float) str_replace(',', '.', $pctRec), 2),
        'prazo_comissao_meses'   => max(1, (int) ($_POST['prazo_comissao_meses'] ?? 12)),
        'primeira_recorrencia'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $primeira) ? $primeira : '',
        'status'                 => in_array($status, array('ativo', 'encerrado', 'cancelado'), true) ? $status : 'ativo',
    );
}

// ── Mutações (só admin) ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$souAdmin) { http_response_code(403); exit('Sem permissão.'); }
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar' || $acao === 'update') {
        $f = ler_form();
        // parceiro_id precisa ser um parceiro de fato.
        $ok = db()->prepare("SELECT COUNT(*) FROM admins WHERE id = ? AND papel = 'parceiro'");
        $ok->execute(array($f['parceiro_id']));
        if ($f['empresa'] === '' || $f['parceiro_id'] <= 0 || !$ok->fetchColumn()) {
            $_SESSION['flash'] = array('erro', 'Selecione um parceiro válido e informe a empresa.');
        } elseif ($f['primeira_recorrencia'] === '') {
            $_SESSION['flash'] = array('erro', 'Informe a data da primeira recorrência.');
        } elseif ($acao === 'criar') {
            // Cadastro manual pelo admin já entra aprovado.
            db()->prepare(
                'INSERT INTO clientes_fechados
                   (parceiro_id, proposta_id, empresa, valor_projeto, valor_recorrencia,
                    percentual_projeto, percentual_recorrencia, prazo_comissao_meses, primeira_recorrencia, status, aprovacao)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute(array(
                $f['parceiro_id'], $f['proposta_id'], $f['empresa'], $f['valor_projeto'], $f['valor_recorrencia'],
                $f['percentual_projeto'], $f['percentual_recorrencia'], $f['prazo_comissao_meses'],
                $f['primeira_recorrencia'], $f['status'], 'aprovado',
            ));
            $_SESSION['flash'] = array('ok', 'Cliente fechado cadastrado.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            db()->prepare(
                'UPDATE clientes_fechados SET
                    parceiro_id=?, proposta_id=?, empresa=?, valor_projeto=?, valor_recorrencia=?,
                    percentual_projeto=?, percentual_recorrencia=?, prazo_comissao_meses=?, primeira_recorrencia=?,
                    status=?, atualizado_em=NOW()
                  WHERE id=?'
            )->execute(array(
                $f['parceiro_id'], $f['proposta_id'], $f['empresa'], $f['valor_projeto'], $f['valor_recorrencia'],
                $f['percentual_projeto'], $f['percentual_recorrencia'], $f['prazo_comissao_meses'],
                $f['primeira_recorrencia'], $f['status'], $id,
            ));
            $_SESSION['flash'] = array('ok', 'Cliente atualizado.');
        }
    } elseif ($acao === 'aprovar') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT primeira_recorrencia FROM clientes_fechados WHERE id = ?');
        $st->execute(array($id));
        if (!$st->fetchColumn()) {
            $_SESSION['flash'] = array('erro', 'Defina a data da primeira recorrência (em Editar) antes de aprovar.');
        } else {
            db()->prepare("UPDATE clientes_fechados SET aprovacao = 'aprovado', atualizado_em = NOW() WHERE id = ?")->execute(array($id));
            $_SESSION['flash'] = array('ok', 'Cliente aprovado — comissão passa a contar.');
        }
    } elseif ($acao === 'rejeitar') {
        db()->prepare("UPDATE clientes_fechados SET aprovacao = 'rejeitado', atualizado_em = NOW() WHERE id = ?")->execute(array((int) ($_POST['id'] ?? 0)));
        $_SESSION['flash'] = array('ok', 'Cliente rejeitado.');
    } elseif ($acao === 'delete') {
        db()->prepare('DELETE FROM clientes_fechados WHERE id = ?')->execute(array((int) ($_POST['id'] ?? 0)));
        $_SESSION['flash'] = array('ok', 'Cliente removido.');
    }
    header('Location: /admin/parceiros/clientes.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Carrega a carteira ───────────────────────────────────────────────────────
$sql = "SELECT c.*, a.nome AS parceiro_nome,
               p.percentual_projeto     AS pct_parceiro_projeto,
               p.percentual_recorrencia AS pct_parceiro_recorrencia
          FROM clientes_fechados c
          JOIN admins    a ON a.id = c.parceiro_id
          JOIN parceiros p ON p.admin_id = c.parceiro_id";
$params = array();
$filtroParceiro = 0;
if (!$souAdmin) {
    // Parceiro: só a própria carteira.
    $sql .= ' WHERE c.parceiro_id = ?'; $params[] = (int) admin_id();
} elseif (!empty($_GET['parceiro'])) {
    // Admin: pode isolar a carteira de um parceiro específico.
    $filtroParceiro = (int) $_GET['parceiro'];
    $sql .= ' WHERE c.parceiro_id = ?'; $params[] = $filtroParceiro;
}
$sql .= ' ORDER BY c.criado_em DESC';
$st = db()->prepare($sql);
$st->execute($params);
$clientes = $st->fetchAll();

// Cliente em edição (admin, via ?edit=id)
$editar = null;
if ($souAdmin && isset($_GET['edit'])) {
    foreach ($clientes as $c) { if ((int) $c['id'] === (int) $_GET['edit']) { $editar = $c; break; } }
}

// Dados auxiliares do formulário (admin)
$listaParceiros = $listaPropostas = array();
if ($souAdmin) {
    $listaParceiros = db()->query(
        "SELECT a.id, a.nome FROM admins a JOIN parceiros p ON p.admin_id = a.id
          WHERE a.papel = 'parceiro' AND a.ativo = 1 ORDER BY a.nome ASC"
    )->fetchAll();
    $listaPropostas = db()->query('SELECT id, empresa, valor_mensal, dados FROM propostas ORDER BY criado_em DESC')->fetchAll();
    // Mapa {id: {empresa, projeto, recorrencia}} para pré-preencher o form via JS.
    // projeto ← valorCriacao (criação do site, no JSON); recorrencia ← valor_mensal.
    foreach ($listaPropostas as $pr) {
        $dados = json_decode($pr['dados'] ?? '', true);
        $propMap[(int) $pr['id']] = array(
            'empresa'     => $pr['empresa'],
            'projeto'     => is_array($dados) ? (float) ($dados['valorCriacao'] ?? 0) : 0,
            'recorrencia' => (float) ($pr['valor_mensal'] ?? 0),
        );
    }
}

// Totais da carteira — só clientes aprovados e não cancelados entram.
$totProjeto = $totRecTotal = $totRecebido = $totRestante = 0.0;
$qtdPendentes = 0;
foreach ($clientes as $c) {
    if ($c['aprovacao'] === 'pendente') $qtdPendentes++;
    if ($c['aprovacao'] !== 'aprovado' || $c['status'] === 'cancelado') continue;
    $k = calcular_comissao($c);
    $totProjeto  += $k['comissao_projeto'];
    $totRecTotal += $k['recorrencia_total'];
    $totRecebido += $k['recorrencia_recebida'];
    $totRestante += $k['recorrencia_restante'];
}

$STATUS_TAG = array('ativo' => 'ativa', 'encerrado' => 'pendente', 'cancelado' => 'inativa');
$APROV_TAG  = array('pendente' => 'pendente', 'aprovado' => 'ativa', 'rejeitado' => 'inativa');
$APROV_LBL  = array('pendente' => 'aprovação pendente', 'aprovado' => 'aprovado', 'rejeitado' => 'rejeitado');

layout_head($souAdmin ? 'Clientes fechados' : 'Minha carteira', true);
?>
<style>
.cli{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:18px 20px;margin-bottom:14px}
.cli .hd{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.cli .hd b{font-size:17px}
.cli .hd .quem{color:#94a3b8;font-size:13px}
.cli .hd .sp{flex:1}
.metrics{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
.metric{background:#0f172a;border:1px solid #334155;border-radius:12px;padding:10px 12px}
.metric .k{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:700}
.metric .v{font-size:16px;font-weight:800;margin-top:3px}
.metric .v.hi{color:#86efac}
.metric small{color:#64748b;font-weight:600;font-size:11px}
.bar{height:6px;background:#0f172a;border-radius:999px;overflow:hidden;margin-top:8px}
.bar>i{display:block;height:100%;background:linear-gradient(90deg,#6366f1,#4ea8ff)}
.sumrow{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px}
.sumcard{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:16px 18px}
.sumcard .k{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;font-weight:700}
.sumcard .v{font-size:22px;font-weight:800;margin-top:4px}
details.form{margin-bottom:24px}
details.form>summary{cursor:pointer;font-weight:800;color:#c7d2fe;list-style:none;padding:6px 0}
details.form>summary::-webkit-details-marker{display:none}
</style>

<h1><?= $souAdmin ? 'Clientes fechados' : 'Minha carteira' ?></h1>
<p class="sub"><?= $souAdmin
    ? 'Cadastre clientes por parceiro, defina prazo da comissão e acompanhe os valores.'
    : 'Seus clientes fechados e a comissão a receber. Cadastro e valores são definidos pela Kennedev.' ?></p>

<?php if ($flash): $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg'; ?>
  <div class="<?= $cls ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if ($souAdmin && $qtdPendentes > 0): ?>
  <div class="erro" style="background:#78350f;color:#fcd34d"><?= (int) $qtdPendentes ?> cliente(s) de proposta aceita aguardando aprovação. Defina a data da primeira recorrência (Editar) e clique em Aprovar.</div>
<?php endif; ?>

<?php if ($souAdmin): ?>
<!-- Filtro por parceiro -->
<div class="card"><form method="get" class="row" style="margin:0">
  <div style="flex:1"><label>Ver carteira do parceiro</label>
    <select name="parceiro" onchange="this.form.submit()">
      <option value="">Todos os parceiros</option>
      <?php foreach ($listaParceiros as $p):
        $selF = $filtroParceiro === (int) $p['id'] ? ' selected' : ''; ?>
        <option value="<?= (int) $p['id'] ?>"<?= $selF ?>><?= e($p['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <noscript><button class="btn">Filtrar</button></noscript>
</form></div>
<?php endif; ?>

<!-- Resumo da carteira -->
<div class="sumrow">
  <div class="sumcard"><div class="k">Comissão de projetos</div><div class="v"><?= e(brl($totProjeto)) ?></div></div>
  <div class="sumcard"><div class="k">Recorrência (total do contrato)</div><div class="v"><?= e(brl($totRecTotal)) ?></div></div>
  <div class="sumcard"><div class="k">Recorrência já vencida</div><div class="v" style="color:#86efac"><?= e(brl($totRecebido)) ?></div></div>
  <div class="sumcard"><div class="k">Recorrência a vencer</div><div class="v"><?= e(brl($totRestante)) ?></div></div>
</div>

<?php if ($souAdmin): ?>
<!-- Formulário (criar / editar) -->
<details class="form" <?= $editar ? 'open' : '' ?>>
  <summary><?= $editar ? '✎ Editando cliente' : '+ Cadastrar cliente fechado' ?></summary>
  <div class="card">
    <form method="post">
      <?= campo_csrf() ?>
      <input type="hidden" name="acao" value="<?= $editar ? 'update' : 'criar' ?>">
      <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>"><?php endif; ?>

      <div class="row">
        <div style="flex:1"><label>Vincular a uma proposta (opcional) — preenche empresa e valores</label>
          <select name="proposta_id" id="f_proposta">
            <option value="">— nenhuma —</option>
            <?php foreach ($listaPropostas as $pr):
              $selPr = $editar && (int) ($editar['proposta_id'] ?? 0) === (int) $pr['id'] ? ' selected' : ''; ?>
              <option value="<?= (int) $pr['id'] ?>"<?= $selPr ?>><?= e($pr['empresa']) ?> (#<?= (int) $pr['id'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row">
        <div><label>Parceiro *</label><select name="parceiro_id" required>
          <option value="">— selecione —</option>
          <?php foreach ($listaParceiros as $p):
            $selP = $editar && (int) $editar['parceiro_id'] === (int) $p['id'] ? ' selected' : ''; ?>
            <option value="<?= (int) $p['id'] ?>"<?= $selP ?>><?= e($p['nome']) ?></option>
          <?php endforeach; ?>
        </select></div>
        <div><label>Empresa (cliente) *</label><input name="empresa" id="f_empresa" value="<?= e($editar['empresa'] ?? '') ?>" required></div>
      </div>

      <div class="row">
        <div><label>Valor do projeto (R$)</label><input type="number" step="0.01" min="0" name="valor_projeto" id="f_projeto" value="<?= e($editar['valor_projeto'] ?? '0') ?>"></div>
        <div><label>Recorrência mensal (R$)</label><input type="number" step="0.01" min="0" name="valor_recorrencia" id="f_recorrencia" value="<?= e($editar['valor_recorrencia'] ?? '0') ?>"></div>
      </div>

      <div class="row">
        <div><label>% projeto (vazio = padrão do parceiro)</label><input type="number" step="0.01" min="0" max="100" name="percentual_projeto" value="<?= e($editar['percentual_projeto'] ?? '') ?>" placeholder="padrão"></div>
        <div><label>% recorrência (vazio = padrão)</label><input type="number" step="0.01" min="0" max="100" name="percentual_recorrencia" value="<?= e($editar['percentual_recorrencia'] ?? '') ?>" placeholder="padrão"></div>
      </div>

      <div class="row">
        <div><label>Prazo da comissão (meses)</label>
          <input type="number" step="1" min="1" list="prazos" name="prazo_comissao_meses" value="<?= e($editar['prazo_comissao_meses'] ?? '12') ?>" required>
          <datalist id="prazos"><option value="12"><option value="24"><option value="36"></datalist>
        </div>
        <div><label>Primeira recorrência *</label><input type="date" name="primeira_recorrencia" value="<?= e($editar['primeira_recorrencia'] ?? '') ?>" required></div>
      </div>

      <div class="row">
        <div><label>Status</label><select name="status">
          <?php foreach (array('ativo' => 'Ativo', 'encerrado' => 'Encerrado', 'cancelado' => 'Cancelado') as $sv => $sl):
            $selS = ($editar['status'] ?? 'ativo') === $sv ? ' selected' : ''; ?>
            <option value="<?= $sv ?>"<?= $selS ?>><?= $sl ?></option>
          <?php endforeach; ?>
        </select></div>
        <div></div>
      </div>

      <div class="row" style="margin-top:6px">
        <button class="btn"><?= $editar ? 'Salvar alterações' : 'Cadastrar' ?></button>
        <?php if ($editar): ?><a class="btn ghost" href="/admin/parceiros/clientes.php">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
</details>
<script>
// Ao escolher uma proposta, pré-preenche empresa e valores com os dados dela.
(function () {
  var MAP = <?= json_encode($propMap ?? array(), JSON_UNESCAPED_UNICODE) ?>;
  var sel = document.getElementById('f_proposta');
  if (!sel) return;
  sel.addEventListener('change', function () {
    var p = MAP[this.value];
    if (!p) return;
    var emp = document.getElementById('f_empresa');
    var prj = document.getElementById('f_projeto');
    var rec = document.getElementById('f_recorrencia');
    if (emp && p.empresa) emp.value = p.empresa;
    if (prj) prj.value = p.projeto;
    if (rec) rec.value = p.recorrencia;
  });
})();
</script>
<?php endif; ?>

<!-- Lista de clientes -->
<?php if (!$clientes): ?>
  <div class="card" style="color:#94a3b8">Nenhum cliente fechado <?= $souAdmin ? 'cadastrado' : 'na sua carteira' ?> ainda.</div>
<?php else: foreach ($clientes as $c):
  $k = calcular_comissao($c);
  $pctMes = $k['prazo'] > 0 ? round($k['meses_decorridos'] / $k['prazo'] * 100) : 0;
  $tag = $STATUS_TAG[$c['status']] ?? 'ativa'; ?>
  <div class="cli">
    <div class="hd">
      <b><?= e($c['empresa']) ?></b>
      <span class="tag <?= $APROV_TAG[$c['aprovacao']] ?? 'pendente' ?>"><?= e($APROV_LBL[$c['aprovacao']] ?? $c['aprovacao']) ?></span>
      <span class="tag <?= $tag ?>"><?= e($c['status']) ?></span>
      <?php if ($souAdmin): ?><span class="quem">· parceiro: <?= e($c['parceiro_nome']) ?></span><?php endif; ?>
      <?php if ($c['proposta_id']): ?><span class="quem">· proposta #<?= (int) $c['proposta_id'] ?></span><?php endif; ?>
      <span class="sp"></span>
      <?php if ($souAdmin): ?>
        <?php if ($c['aprovacao'] === 'pendente'): ?>
          <form method="post" style="display:inline"><?= campo_csrf() ?><input type="hidden" name="acao" value="aprovar"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn sm ok">Aprovar</button></form>
          <form method="post" style="display:inline"><?= campo_csrf() ?><input type="hidden" name="acao" value="rejeitar"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn sm warn">Rejeitar</button></form>
        <?php endif; ?>
        <a class="btn sm ghost" href="/admin/parceiros/clientes.php?edit=<?= (int) $c['id'] ?>">Editar</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remover este cliente?')">
          <?= campo_csrf() ?><input type="hidden" name="acao" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <button class="btn sm danger">Excluir</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="metrics">
      <div class="metric"><div class="k">Comissão do projeto</div>
        <div class="v hi"><?= e(brl($k['comissao_projeto'])) ?></div>
        <small><?= e(rtrim(rtrim(number_format($k['pct_projeto'], 2, '.', ''), '0'), '.')) ?>% de <?= e(brl($c['valor_projeto'])) ?></small></div>

      <div class="metric"><div class="k">Comissão / mês</div>
        <div class="v"><?= e(brl($k['comissao_mensal'])) ?></div>
        <small><?= e(rtrim(rtrim(number_format($k['pct_recorrencia'], 2, '.', ''), '0'), '.')) ?>% de <?= e(brl($c['valor_recorrencia'])) ?></small></div>

      <div class="metric"><div class="k">Recorrência total (<?= (int) $k['prazo'] ?> meses)</div>
        <div class="v"><?= e(brl($k['recorrencia_total'])) ?></div>
        <small>já vencida <?= e(brl($k['recorrencia_recebida'])) ?></small></div>

      <div class="metric"><div class="k">Ganho total do contrato</div>
        <div class="v hi"><?= e(brl($k['total_contrato'])) ?></div>
        <small>projeto + recorrência</small></div>

      <div class="metric"><div class="k">Contador da recorrência</div>
        <div class="v"><?= (int) $k['meses_decorridos'] ?>/<?= (int) $k['prazo'] ?> <small>meses</small></div>
        <div class="bar"><i style="width:<?= $pctMes ?>%"></i></div></div>

      <div class="metric"><div class="k">Período</div>
        <div class="v" style="font-size:13px"><?= e(data_br($k['primeira_recorrencia'])) ?> → <?= e(data_br($k['ultima_recorrencia'])) ?></div>
        <small><?= (int) $k['meses_restantes'] ?> meses restantes</small></div>
    </div>
  </div>
<?php endforeach; endif; ?>

<?php layout_foot(); ?>
