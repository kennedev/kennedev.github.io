<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
exigir_admin();
sessao();

/** Chave AAAAA-BBBBB-CCCCC (sem 0/O/1/I para não confundir). */
function gerar_chave() {
    $alf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $g = array();
    for ($i = 0; $i < 3; $i++) {
        $s = '';
        for ($j = 0; $j < 5; $j++) $s .= $alf[random_int(0, strlen($alf) - 1)];
        $g[] = $s;
    }
    return implode('-', $g);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao  = $_POST['acao'] ?? '';
    $chave = trim($_POST['chave'] ?? '');

    if ($acao === 'criar') {
        $cliente = trim($_POST['cliente'] ?? '');
        if ($cliente === '') {
            $_SESSION['flash'] = array('erro', 'Informe o nome do cliente.');
        } else {
            $nova = gerar_chave();
            db()->prepare('INSERT INTO licencas (chave, cliente) VALUES (?, ?)')
                ->execute(array($nova, $cliente));
            $_SESSION['flash'] = array('ok', "Chave criada para {$cliente} — envie ao cliente: {$nova}");
        }
    } elseif ($acao === 'status') {
        $novo = $_POST['novo'] ?? '';
        if (in_array($novo, array('ativa', 'pendente', 'inativa'), true)) {
            if ($novo === 'pendente') {
                db()->prepare('UPDATE licencas SET status=?, pendente_desde=NOW() WHERE chave=?')
                    ->execute(array($novo, $chave));
            } elseif ($novo === 'ativa') {
                db()->prepare('UPDATE licencas SET status=?, pendente_desde=NULL WHERE chave=?')
                    ->execute(array($novo, $chave));
            } else {
                db()->prepare('UPDATE licencas SET status=? WHERE chave=?')
                    ->execute(array($novo, $chave));
            }
            $_SESSION['flash'] = array('ok', 'Status atualizado.');
        }
    } elseif ($acao === 'limpar_fp') {
        db()->prepare('UPDATE licencas SET fingerprint=NULL WHERE chave=?')->execute(array($chave));
        $_SESSION['flash'] = array('ok', 'Máquina liberada — o cliente pode ativar em outro computador.');
    }
    header('Location: /admin/pdv/');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$licencas = db()->query('SELECT * FROM licencas ORDER BY criado_em DESC')->fetchAll();

layout_head('PDV — Licenças', true);
echo '<h1>Licenças do PDV</h1><p class="sub">Crie chaves, vincule a clientes e controle o acesso.</p>';

if ($flash) {
    $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg';
    echo '<div class="' . $cls . '">' . e($flash[1]) . '</div>';
}

// Criar nova chave
echo '<div class="card"><form method="post" class="row">' . campo_csrf();
echo '<input type="hidden" name="acao" value="criar">';
echo '<div><label>Novo cliente</label><input name="cliente" placeholder="Ex.: Loja da Maria" autocomplete="off" required></div>';
echo '<button class="btn">Gerar chave</button>';
echo '</form></div>';

// Lista
echo '<div class="card" style="padding:8px 16px">';
if (!$licencas) {
    echo '<p class="sub" style="margin:16px 4px">Nenhuma licença ainda.</p>';
} else {
    echo '<table><thead><tr><th>Cliente</th><th>Chave</th><th>Status</th><th>Máquina</th><th>Ações</th></tr></thead><tbody>';
    foreach ($licencas as $l) {
        $st = $l['status'];
        echo '<tr>';
        echo '<td><b>' . e($l['cliente']) . '</b></td>';
        echo '<td><code>' . e($l['chave']) . '</code></td>';
        echo '<td><span class="tag ' . $st . '">' . $st . '</span></td>';
        echo '<td>' . ($l['fingerprint'] ? 'vinculada' : '<span style="color:#475569">livre</span>') . '</td>';
        echo '<td><div class="acoes">';
        echo botao_status($l['chave'], 'ativa', 'Ativar', 'ok', $st);
        echo botao_status($l['chave'], 'pendente', 'Pendência', 'warn', $st);
        echo botao_status($l['chave'], 'inativa', 'Bloquear', 'danger', $st);
        if ($l['fingerprint']) {
            echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Liberar a máquina vinculada?\')">' . campo_csrf()
               . '<input type="hidden" name="acao" value="limpar_fp"><input type="hidden" name="chave" value="' . e($l['chave']) . '">'
               . '<button class="btn ghost sm">Liberar PC</button></form>';
        }
        echo '</div></td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

layout_foot();

/** Botão que muda o status (oculto se já está nesse status). */
function botao_status($chave, $novo, $label, $cor, $atual) {
    if ($atual === $novo) return '';
    return '<form method="post" style="display:inline">' . campo_csrf()
        . '<input type="hidden" name="acao" value="status">'
        . '<input type="hidden" name="chave" value="' . e($chave) . '">'
        . '<input type="hidden" name="novo" value="' . e($novo) . '">'
        . '<button class="btn ' . $cor . ' sm">' . e($label) . '</button></form>';
}
