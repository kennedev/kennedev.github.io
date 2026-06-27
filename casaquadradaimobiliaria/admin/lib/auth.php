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

/** Redireciona para o login se não houver sessão (caminho relativo ao /admin/). */
function exigir_login() {
    if (!admin_id()) {
        header('Location: index.php');
        exit;
    }
}

function existe_algum_admin() {
    return ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()) > 0;
}

function criar_admin($nome, $senha) {
    $st = db()->prepare('INSERT INTO admins (nome, senha_hash) VALUES (?, ?)');
    $st->execute(array($nome, password_hash($senha, PASSWORD_DEFAULT)));
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
        return true;
    }
    return false;
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

// ── Mensagens flash (sobrevivem a um redirect PRG) ────────────────────────────
function flash_set($texto, $tipo = 'ok') {
    sessao();
    $_SESSION['flash'] = array('texto' => $texto, 'tipo' => $tipo);
}

/** Retorna ['texto'=>, 'tipo'=>] e limpa, ou null. */
function flash_get() {
    sessao();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
