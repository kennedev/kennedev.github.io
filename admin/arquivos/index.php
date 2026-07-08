<?php
/* Gerenciador de arquivos — lista, envia, baixa e exclui arquivos avulsos.
   Os arquivos ficam em ./files/ (bloqueada por .htaccess); o download é
   servido pelo baixar.php, que exige login. */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
exigir_admin();
sessao();

$DIR = __DIR__ . '/files';
if (!is_dir($DIR)) @mkdir($DIR, 0755, true);

/** Sanitiza o nome do arquivo enviado (sem path, sem caracteres problemáticos). */
function nome_seguro($nome) {
    $nome = basename($nome);
    $nome = preg_replace('/[^\w.\- ]+/u', '_', $nome);
    $nome = trim($nome, ". \t");
    return $nome === '' ? null : $nome;
}

function formatar_tamanho($bytes) {
    $u = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($bytes >= 1024 && $i < count($u) - 1) { $bytes /= 1024; $i++; }
    return ($i === 0 ? $bytes : number_format($bytes, 1, ',', '.')) . ' ' . $u[$i];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'upload') {
        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['flash'] = array('erro', 'Selecione um arquivo para enviar.');
        } elseif ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = array('erro', 'Falha no envio (arquivo maior que o limite do servidor?).');
        } else {
            $nome = nome_seguro($_FILES['arquivo']['name']);
            if ($nome === null) {
                $_SESSION['flash'] = array('erro', 'Nome de arquivo inválido.');
            } else {
                $destino = $DIR . '/' . $nome;
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
                    $_SESSION['flash'] = array('ok', "Arquivo \"{$nome}\" enviado.");
                } else {
                    $_SESSION['flash'] = array('erro', 'Não foi possível salvar o arquivo.');
                }
            }
        }
    } elseif ($acao === 'excluir') {
        $nome = nome_seguro($_POST['nome'] ?? '');
        $alvo = $nome ? realpath($DIR . '/' . $nome) : false;
        if ($alvo && strpos($alvo, realpath($DIR)) === 0 && is_file($alvo)) {
            unlink($alvo);
            $_SESSION['flash'] = array('ok', "Arquivo \"{$nome}\" excluído.");
        } else {
            $_SESSION['flash'] = array('erro', 'Arquivo não encontrado.');
        }
    }
    header('Location: /admin/arquivos/');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Lista os arquivos (mais recentes primeiro).
$arquivos = array();
foreach (glob($DIR . '/*') as $path) {
    if (is_file($path)) {
        $arquivos[] = array(
            'nome'     => basename($path),
            'tamanho'  => filesize($path),
            'mtime'    => filemtime($path),
        );
    }
}
usort($arquivos, function ($a, $b) { return $b['mtime'] - $a['mtime']; });

layout_head('Arquivos', true);
echo '<h1>Arquivos</h1><p class="sub">Envie arquivos aqui e baixe de onde estiver — tudo protegido pelo login.</p>';

if ($flash) {
    $cls = $flash[0] === 'erro' ? 'erro' : 'ok-msg';
    echo '<div class="' . $cls . '">' . e($flash[1]) . '</div>';
}

// Envio
echo '<div class="card"><form method="post" enctype="multipart/form-data" class="row">' . campo_csrf();
echo '<input type="hidden" name="acao" value="upload">';
echo '<div style="flex:2"><label>Enviar arquivo</label><input type="file" name="arquivo" required></div>';
echo '<button class="btn">Enviar</button>';
echo '</form></div>';

// Lista
echo '<div class="card" style="padding:8px 16px">';
if (!$arquivos) {
    echo '<p class="sub" style="margin:16px 4px">Nenhum arquivo ainda.</p>';
} else {
    echo '<table><thead><tr><th>Arquivo</th><th>Tamanho</th><th>Enviado</th><th>Ações</th></tr></thead><tbody>';
    foreach ($arquivos as $a) {
        $q = urlencode($a['nome']);
        echo '<tr>';
        echo '<td><b>' . e($a['nome']) . '</b></td>';
        echo '<td>' . e(formatar_tamanho($a['tamanho'])) . '</td>';
        echo '<td>' . e(date('d/m/Y H:i', $a['mtime'])) . '</td>';
        echo '<td><div class="acoes">';
        echo '<a class="btn sm" href="baixar.php?arquivo=' . $q . '">Baixar</a>';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Excluir este arquivo?\')">' . campo_csrf()
           . '<input type="hidden" name="acao" value="excluir">'
           . '<input type="hidden" name="nome" value="' . e($a['nome']) . '">'
           . '<button class="btn danger sm">Excluir</button></form>';
        echo '</div></td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

layout_foot();
