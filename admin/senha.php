<?php
/* senha.php — troca da própria senha. Disponível a qualquer conta logada
   (admin ou parceiro). Exige a senha atual para confirmar. */
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
exigir_login();
sessao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $atual = $_POST['atual'] ?? '';
    $nova  = $_POST['nova'] ?? '';
    $conf  = $_POST['confirma'] ?? '';

    if (!senha_confere(admin_id(), $atual)) {
        $_SESSION['flash'] = array('erro', 'A senha atual está incorreta.');
    } elseif (strlen($nova) < 6) {
        $_SESSION['flash'] = array('erro', 'A nova senha precisa ter ao menos 6 caracteres.');
    } elseif ($nova !== $conf) {
        $_SESSION['flash'] = array('erro', 'A confirmação não confere com a nova senha.');
    } elseif ($nova === $atual) {
        $_SESSION['flash'] = array('erro', 'A nova senha precisa ser diferente da atual.');
    } else {
        atualizar_senha(admin_id(), $nova);
        $_SESSION['flash'] = array('ok', 'Senha alterada com sucesso.');
    }
    header('Location: /admin/senha.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

layout_head('Trocar senha', true);
echo '<h1>Trocar senha</h1><p class="sub">Atualize a senha da sua conta (' . e(admin_nome()) . ').</p>';

if ($flash) {
    $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg';
    echo '<div class="' . $cls . '">' . e($flash[1]) . '</div>';
}

echo '<div class="card" style="max-width:420px"><form method="post">' . campo_csrf();
echo '<div style="margin-bottom:14px"><label>Senha atual</label><input type="password" name="atual" required autocomplete="current-password"></div>';
echo '<div style="margin-bottom:14px"><label>Nova senha (mín. 6)</label><input type="password" name="nova" required autocomplete="new-password"></div>';
echo '<div style="margin-bottom:18px"><label>Confirmar nova senha</label><input type="password" name="confirma" required autocomplete="new-password"></div>';
echo '<button class="btn" style="width:100%;justify-content:center">Salvar nova senha</button>';
echo '</form></div>';

layout_foot();
