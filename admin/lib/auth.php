<?php
require_once __DIR__ . '/db.php';

function sessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function admin_id() {
    sessao();
    return $_SESSION['admin_id'] ?? null;
}

function admin_nome() {
    sessao();
    return $_SESSION['admin_nome'] ?? null;
}

function admin_papel() {
    sessao();
    return $_SESSION['admin_papel'] ?? 'admin';
}

function eh_admin() {
    return admin_papel() === 'admin';
}

/** Redireciona para o login se não houver sessão. */
function exigir_login() {
    if (!admin_id()) {
        header('Location: /admin/');
        exit;
    }
}

/** Exige login E papel de admin; parceiro é mandado para a área comercial. */
function exigir_admin() {
    exigir_login();
    if (!eh_admin()) {
        header('Location: /admin/parceiros/');
        exit;
    }
}

function existe_algum_admin() {
    return ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()) > 0;
}

/** Cria uma conta de acesso. Retorna o id gerado. */
function criar_admin($nome, $senha, $papel = 'admin') {
    $papel = $papel === 'parceiro' ? 'parceiro' : 'admin';
    $st = db()->prepare('INSERT INTO admins (nome, senha_hash, papel) VALUES (?, ?, ?)');
    $st->execute(array($nome, password_hash($senha, PASSWORD_DEFAULT), $papel));
    return (int) db()->lastInsertId();
}

/** Cria um parceiro: conta em `admins` + percentuais em `parceiros` (atômico). */
function criar_parceiro($nome, $senha, $pctProjeto, $pctRecorrencia) {
    db()->beginTransaction();
    try {
        $id = criar_admin($nome, $senha, 'parceiro');
        $st = db()->prepare(
            'INSERT INTO parceiros (admin_id, percentual_projeto, percentual_recorrencia) VALUES (?, ?, ?)'
        );
        $st->execute(array($id, round((float) $pctProjeto, 2), round((float) $pctRecorrencia, 2)));
        db()->commit();
        return $id;
    } catch (Exception $ex) {
        db()->rollBack();
        throw $ex;
    }
}

function autenticar($nome, $senha) {
    $st = db()->prepare('SELECT * FROM admins WHERE nome = ? AND ativo = 1 LIMIT 1');
    $st->execute(array($nome));
    $a = $st->fetch();
    if ($a && password_verify($senha, $a['senha_hash'])) {
        sessao();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        $_SESSION['admin_nome'] = $a['nome'];
        $_SESSION['admin_papel'] = $a['papel'] ?? 'admin';
        return true;
    }
    return false;
}

/** Confere se a senha informada bate com a da conta (para trocas de senha). */
function senha_confere($id, $senha) {
    $st = db()->prepare('SELECT senha_hash FROM admins WHERE id = ? LIMIT 1');
    $st->execute(array((int) $id));
    $row = $st->fetch();
    return $row && password_verify($senha, $row['senha_hash']);
}

/** Grava uma nova senha (hash) para a conta. */
function atualizar_senha($id, $nova) {
    $st = db()->prepare('UPDATE admins SET senha_hash = ? WHERE id = ?');
    $st->execute(array(password_hash($nova, PASSWORD_DEFAULT), (int) $id));
}

function logout() {
    sessao();
    $_SESSION = array();
    session_destroy();
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token() {
    sessao();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** Valida o token nos POSTs; aborta se inválido. */
function csrf_check() {
    sessao();
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(400);
        exit('Token inválido. Recarregue a página.');
    }
}

function campo_csrf() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
