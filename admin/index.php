<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
sessao();

$erro = '';
$temAdmin = existe_algum_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'setup' && !$temAdmin) {
        $nome  = trim($_POST['nome'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $conf  = $_POST['confirma'] ?? '';
        if ($nome === '' || strlen($senha) < 6) {
            $erro = 'Informe o nome e uma senha de ao menos 6 caracteres.';
        } elseif ($senha !== $conf) {
            $erro = 'As senhas não conferem.';
        } else {
            criar_admin($nome, $senha);
            autenticar($nome, $senha);
            header('Location: /admin/');
            exit;
        }
    } elseif ($acao === 'login') {
        if (autenticar(trim($_POST['nome'] ?? ''), $_POST['senha'] ?? '')) {
            header('Location: /admin/');
            exit;
        }
        $erro = 'Usuário ou senha inválidos.';
    }
}

// ── Logado: menu ─────────────────────────────────────────────────────────────
if (admin_id()) {
    layout_head('Início', true);
    echo '<h1>Painel administrativo</h1>';
    echo '<p class="sub">O que você quer administrar?</p>';
    echo '<div class="grid">';
    echo '<a class="tile" href="/admin/pdv/"><b>PDV</b><p>Licenças do sistema de PDV</p></a>';
    echo '<a class="tile" href="/admin/proposta/"><b>Gerador de Proposta</b><p>Crie e gerencie propostas comerciais</p></a>';
    echo '</div>';
    layout_foot();
    exit;
}

// ── Não logado: primeiro acesso ou login ─────────────────────────────────────
layout_head($temAdmin ? 'Entrar' : 'Primeiro acesso', false);
echo '<div class="auth">';
if ($erro) echo '<div class="erro">' . e($erro) . '</div>';

if (!$temAdmin) {
    echo '<h1>Primeiro acesso</h1><p class="sub">Crie o administrador principal.</p>';
    echo '<form method="post" class="card">' . campo_csrf();
    echo '<input type="hidden" name="acao" value="setup">';
    echo '<div style="margin-bottom:14px"><label>Nome</label><input name="nome" autocomplete="off" required></div>';
    echo '<div style="margin-bottom:14px"><label>Senha (mín. 6)</label><input type="password" name="senha" required></div>';
    echo '<div style="margin-bottom:18px"><label>Confirmar senha</label><input type="password" name="confirma" required></div>';
    echo '<button class="btn" style="width:100%;justify-content:center">Criar e entrar</button>';
    echo '</form>';
} else {
    echo '<h1>Entrar</h1><p class="sub">Acesso restrito.</p>';
    echo '<form method="post" class="card">' . campo_csrf();
    echo '<input type="hidden" name="acao" value="login">';
    echo '<div style="margin-bottom:14px"><label>Nome</label><input name="nome" autocomplete="off" required></div>';
    echo '<div style="margin-bottom:18px"><label>Senha</label><input type="password" name="senha" required></div>';
    echo '<button class="btn" style="width:100%;justify-content:center">Entrar</button>';
    echo '</form>';
}
echo '</div>';
layout_foot();
