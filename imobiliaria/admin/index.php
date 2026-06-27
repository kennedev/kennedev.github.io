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
            header('Location: index.php');
            exit;
        }
    } elseif ($acao === 'login') {
        if (autenticar(trim($_POST['nome'] ?? ''), $_POST['senha'] ?? '')) {
            header('Location: index.php');
            exit;
        }
        $erro = 'Usuário ou senha inválidos.';
    }
}

// ── Logado: painel ────────────────────────────────────────────────────────────
if (admin_id()) {
    $totImoveis = (int) db()->query('SELECT COUNT(*) FROM imoveis')->fetchColumn();
    $totDisp    = (int) db()->query("SELECT COUNT(*) FROM imoveis WHERE status='disponivel'")->fetchColumn();
    $totLeads   = (int) db()->query('SELECT COUNT(*) FROM leads WHERE lido = 0')->fetchColumn();

    layout_head('Início', true);
    echo '<h1>Painel</h1>';
    echo '<p class="sub">Bem-vindo, ' . e(admin_nome()) . '. O que você quer fazer?</p>';

    echo '<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:26px">';
    echo '<div class="card" style="margin:0"><label>Imóveis cadastrados</label><div style="font-size:34px;font-weight:800">' . $totImoveis . '</div></div>';
    echo '<div class="card" style="margin:0"><label>Disponíveis no site</label><div style="font-size:34px;font-weight:800">' . $totDisp . '</div></div>';
    echo '<div class="card" style="margin:0"><label>Leads não lidos</label><div style="font-size:34px;font-weight:800;color:#1D9137">' . $totLeads . '</div></div>';
    echo '</div>';

    echo '<div class="grid">';
    echo '<a class="tile" href="imovel-editar.php"><b>+ Novo imóvel</b><p>Cadastrar um imóvel com fotos e descrição</p></a>';
    echo '<a class="tile" href="imoveis.php"><b>Imóveis</b><p>Editar, destacar e excluir anúncios</p></a>';
    echo '<a class="tile" href="leads.php"><b>Leads</b><p>Contatos recebidos pelo site</p></a>';
    echo '<a class="tile" href="usuarios.php"><b>Administradores</b><p>Gerenciar acessos ao painel</p></a>';
    echo '<a class="tile" href="../index.php" target="_blank"><b>Ver site ↗</b><p>Abrir o site público em nova aba</p></a>';
    echo '</div>';
    layout_foot();
    exit;
}

// ── Não logado: primeiro acesso ou login ──────────────────────────────────────
layout_head($temAdmin ? 'Entrar' : 'Primeiro acesso', false);
echo '<div class="auth">';
if ($erro) echo '<div class="erro">' . e($erro) . '</div>';

if (!$temAdmin) {
    echo '<h1>Primeiro acesso</h1><p class="sub">Crie o administrador principal da Luis Imóveis.</p>';
    echo '<form method="post" class="card">' . campo_csrf();
    echo '<input type="hidden" name="acao" value="setup">';
    echo '<div class="field"><label>Nome</label><input name="nome" autocomplete="off" required></div>';
    echo '<div class="field"><label>Senha (mín. 6)</label><input type="password" name="senha" required></div>';
    echo '<div class="field"><label>Confirmar senha</label><input type="password" name="confirma" required></div>';
    echo '<button class="btn btn-block" style="width:100%;justify-content:center">Criar e entrar</button>';
    echo '</form>';
} else {
    echo '<h1>Entrar</h1><p class="sub">Acesso restrito à equipe.</p>';
    echo '<form method="post" class="card">' . campo_csrf();
    echo '<input type="hidden" name="acao" value="login">';
    echo '<div class="field"><label>Nome</label><input name="nome" autocomplete="off" required></div>';
    echo '<div class="field"><label>Senha</label><input type="password" name="senha" required></div>';
    echo '<button class="btn" style="width:100%;justify-content:center">Entrar</button>';
    echo '</form>';
}
echo '</div>';
layout_foot();
