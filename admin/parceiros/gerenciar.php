<?php
/* gerenciar.php — [admin-only] cadastro e gestão de parceiros comerciais.
   Cria a conta de acesso (papel=parceiro) + os percentuais de comissão padrão. */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
exigir_admin();
sessao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome    = trim($_POST['nome'] ?? '');
        $senha   = $_POST['senha'] ?? '';
        $pctProj = (float) str_replace(',', '.', $_POST['pct_projeto'] ?? '0');
        $pctRec  = (float) str_replace(',', '.', $_POST['pct_recorrencia'] ?? '0');
        if ($nome === '' || strlen($senha) < 6) {
            $_SESSION['flash'] = array('erro', 'Informe o nome e uma senha de ao menos 6 caracteres.');
        } elseif ($pctProj < 0 || $pctProj > 100 || $pctRec < 0 || $pctRec > 100) {
            $_SESSION['flash'] = array('erro', 'Os percentuais devem ficar entre 0 e 100.');
        } else {
            try {
                criar_parceiro($nome, $senha, $pctProj, $pctRec);
                $_SESSION['flash'] = array('ok', "Parceiro {$nome} criado.");
            } catch (Exception $ex) {
                $_SESSION['flash'] = array('erro', 'Já existe alguém com esse nome de acesso.');
            }
        }
    } elseif ($acao === 'pct') {
        $id      = (int) ($_POST['id'] ?? 0);
        $pctProj = (float) str_replace(',', '.', $_POST['pct_projeto'] ?? '0');
        $pctRec  = (float) str_replace(',', '.', $_POST['pct_recorrencia'] ?? '0');
        if ($pctProj < 0 || $pctProj > 100 || $pctRec < 0 || $pctRec > 100) {
            $_SESSION['flash'] = array('erro', 'Os percentuais devem ficar entre 0 e 100.');
        } else {
            db()->prepare('UPDATE parceiros SET percentual_projeto = ?, percentual_recorrencia = ? WHERE admin_id = ?')
               ->execute(array(round($pctProj, 2), round($pctRec, 2), $id));
            $_SESSION['flash'] = array('ok', 'Percentuais atualizados.');
        }
    } elseif ($acao === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("UPDATE admins SET ativo = 1 - ativo WHERE id = ? AND papel = 'parceiro'")->execute(array($id));
        $_SESSION['flash'] = array('ok', 'Parceiro atualizado.');
    }
    header('Location: /admin/parceiros/gerenciar.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$parceiros = db()->query(
    "SELECT a.id, a.nome, a.ativo, p.percentual_projeto, p.percentual_recorrencia
       FROM admins a
       JOIN parceiros p ON p.admin_id = a.id
      WHERE a.papel = 'parceiro'
      ORDER BY a.nome ASC"
)->fetchAll();

layout_head('Gerenciar parceiros', true);
echo '<h1>Parceiros</h1><p class="sub">Contas do time comercial e percentuais de comissão padrão.</p>';

if ($flash) {
    $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg';
    echo '<div class="' . $cls . '">' . e($flash[1]) . '</div>';
}

// ── Cadastro ──────────────────────────────────────────────────────────────
echo '<div class="card"><form method="post" class="row">' . campo_csrf();
echo '<input type="hidden" name="acao" value="criar">';
echo '<div><label>Nome de acesso</label><input name="nome" autocomplete="off" required></div>';
echo '<div><label>Senha (mín. 6)</label><input type="password" name="senha" required></div>';
echo '<div style="flex:0 0 130px"><label>% projeto</label><input type="number" step="0.01" min="0" max="100" name="pct_projeto" value="0" required></div>';
echo '<div style="flex:0 0 150px"><label>% recorrência</label><input type="number" step="0.01" min="0" max="100" name="pct_recorrencia" value="0" required></div>';
echo '<button class="btn">Cadastrar</button>';
echo '</form></div>';

// ── Lista ───────────────────────────────────────────────────────────────────
echo '<div class="card" style="padding:8px 16px"><table><thead><tr>';
echo '<th>Parceiro</th><th>Status</th><th>Comissão (projeto / recorrência)</th><th>Ações</th>';
echo '</tr></thead><tbody>';

if (!$parceiros) {
    echo '<tr><td colspan="4" style="color:#475569;padding:20px 10px">Nenhum parceiro cadastrado ainda.</td></tr>';
}
foreach ($parceiros as $p) {
    $id = (int) $p['id'];
    echo '<tr><td><b>' . e($p['nome']) . '</b></td>';
    echo '<td><span class="tag ' . ($p['ativo'] ? 'ativa' : 'inativa') . '">' . ($p['ativo'] ? 'ativo' : 'inativo') . '</span></td>';
    // Editar percentuais inline
    echo '<td><form method="post" class="row" style="gap:8px;align-items:flex-end;margin:0">' . campo_csrf();
    echo '<input type="hidden" name="acao" value="pct"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div style="flex:0 0 90px"><input type="number" step="0.01" min="0" max="100" name="pct_projeto" value="' . e(rtrim(rtrim(number_format((float) $p['percentual_projeto'], 2, '.', ''), '0'), '.')) . '"></div>';
    echo '<div style="flex:0 0 90px"><input type="number" step="0.01" min="0" max="100" name="pct_recorrencia" value="' . e(rtrim(rtrim(number_format((float) $p['percentual_recorrencia'], 2, '.', ''), '0'), '.')) . '"></div>';
    echo '<button class="btn sm ghost">Salvar %</button>';
    echo '</form></td>';
    // Ativar/desativar
    echo '<td><form method="post" style="display:inline">' . campo_csrf()
       . '<input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="' . $id . '">'
       . '<button class="btn ' . ($p['ativo'] ? 'danger' : 'ok') . ' sm">' . ($p['ativo'] ? 'Desativar' : 'Reativar') . '</button></form></td>';
    echo '</tr>';
}
echo '</tbody></table></div>';

layout_foot();
