<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
exigir_login();
sessao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome  = trim($_POST['nome'] ?? '');
        $senha = $_POST['senha'] ?? '';
        if ($nome === '' || strlen($senha) < 6) {
            $_SESSION['flash'] = array('erro', 'Informe o nome e uma senha de ao menos 6 caracteres.');
        } else {
            try {
                criar_admin($nome, $senha);
                $_SESSION['flash'] = array('ok', "Administrador {$nome} criado.");
            } catch (Exception $ex) {
                $_SESSION['flash'] = array('erro', 'Já existe um administrador com esse nome.');
            }
        }
    } elseif ($acao === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) admin_id()) {
            $_SESSION['flash'] = array('erro', 'Você não pode desativar a si mesmo.');
        } else {
            db()->prepare('UPDATE admins SET ativo = 1 - ativo WHERE id = ?')->execute(array($id));
            $_SESSION['flash'] = array('ok', 'Administrador atualizado.');
        }
    }
    header('Location: /admin/usuarios.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$admins = db()->query('SELECT * FROM admins ORDER BY criado_em ASC')->fetchAll();

layout_head('Administradores', true);
echo '<h1>Administradores</h1><p class="sub">Quem pode acessar este painel.</p>';

if ($flash) {
    $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg';
    echo '<div class="' . $cls . '">' . e($flash[1]) . '</div>';
}

echo '<div class="card"><form method="post" class="row">' . campo_csrf();
echo '<input type="hidden" name="acao" value="criar">';
echo '<div><label>Nome</label><input name="nome" autocomplete="off" required></div>';
echo '<div><label>Senha (mín. 6)</label><input type="password" name="senha" required></div>';
echo '<button class="btn">Adicionar</button>';
echo '</form></div>';

echo '<div class="card" style="padding:8px 16px"><table><thead><tr><th>Nome</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
foreach ($admins as $a) {
    $sou = ((int) $a['id'] === (int) admin_id());
    echo '<tr><td><b>' . e($a['nome']) . '</b>' . ($sou ? ' <span style="color:#475569">(você)</span>' : '') . '</td>';
    echo '<td><span class="tag ' . ($a['ativo'] ? 'ativa' : 'inativa') . '">' . ($a['ativo'] ? 'ativo' : 'inativo') . '</span></td>';
    echo '<td>';
    if (!$sou) {
        echo '<form method="post" style="display:inline">' . campo_csrf()
           . '<input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="' . (int) $a['id'] . '">'
           . '<button class="btn ' . ($a['ativo'] ? 'danger' : 'ok') . ' sm">' . ($a['ativo'] ? 'Desativar' : 'Reativar') . '</button></form>';
    } else {
        echo '<span style="color:#475569">—</span>';
    }
    echo '</td></tr>';
}
echo '</tbody></table></div>';

layout_foot();
