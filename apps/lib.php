<?php
// Helpers da tela pública de downloads (/apps).
// Sem dependências externas: só PHP nativo.

const APPS_DIR = __DIR__;

// Extensões consideradas "instaladores baixáveis".
const APPS_EXTENSOES = array('exe', 'msi', 'dmg', 'appimage', 'zip', 'apk');

/** Slug de app válido: só minúsculas, dígitos, hífen e underline. Barra path traversal. */
function slug_valido($s) {
    return is_string($s) && preg_match('/^[a-z0-9_-]+$/', $s) === 1;
}

/**
 * Resolve o caminho absoluto seguro da pasta de um app.
 * Retorna null se o slug for inválido, a pasta não existir, ou (por segurança)
 * o realpath escapar de APPS_DIR.
 */
function pasta_do_app($slug) {
    if (!slug_valido($slug)) return null;
    $alvo = realpath(APPS_DIR . '/' . $slug);
    if ($alvo === false || !is_dir($alvo)) return null;
    $base = realpath(APPS_DIR);
    // O alvo tem que estar estritamente dentro de APPS_DIR.
    if ($alvo === $base || strpos($alvo, $base . DIRECTORY_SEPARATOR) !== 0) return null;
    return $alvo;
}

/** Extrai o semver (x.y.z) do nome do arquivo, ou null. */
function versao_do_nome($nome) {
    if (preg_match('/(\d+\.\d+\.\d+)/', $nome, $m)) return $m[1];
    return null;
}

/** Formata bytes em algo legível (KB/MB/GB). */
function formatar_tamanho($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $u = array('KB', 'MB', 'GB');
    $i = -1;
    do { $bytes /= 1024; $i++; } while ($bytes >= 1024 && $i < count($u) - 1);
    return number_format($bytes, 1, ',', '.') . ' ' . $u[$i];
}

/**
 * Acha o instalador mais recente numa pasta de app.
 * Ordena por version_compare (desc); empate ou sem versão → maior filemtime.
 * Retorna array [path, nome, versao, tamanho, data] ou null se não houver.
 */
function instalador_mais_recente($dir) {
    $arquivos = array();
    foreach (glob($dir . '/*') as $p) {
        if (!is_file($p)) continue;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        if (!in_array($ext, APPS_EXTENSOES, true)) continue;
        $arquivos[] = $p;
    }
    if (!$arquivos) return null;

    usort($arquivos, function ($a, $b) {
        $va = versao_do_nome(basename($a));
        $vb = versao_do_nome(basename($b));
        if ($va !== null && $vb !== null) {
            $cmp = version_compare($vb, $va); // desc
            if ($cmp !== 0) return $cmp;
        } elseif ($va !== null) {
            return -1; // com versão vem antes de sem versão
        } elseif ($vb !== null) {
            return 1;
        }
        return filemtime($b) - filemtime($a); // desc por data
    });

    $escolhido = $arquivos[0];
    return array(
        'path'     => $escolhido,
        'nome'     => basename($escolhido),
        'versao'   => versao_do_nome(basename($escolhido)),
        'tamanho'  => filesize($escolhido),
        'data'     => filemtime($escolhido),
    );
}

/**
 * Varre as subpastas de /apps e monta a lista pronta para a tela.
 * Cada item: slug, nome, descricao, icone, instalador (array|null).
 * Lê apps/<slug>/app.json opcional; fallback: nome = slug.
 */
function apps_disponiveis() {
    $lista = array();
    foreach (glob(APPS_DIR . '/*', GLOB_ONLYDIR) as $dir) {
        $slug = basename($dir);
        if (!slug_valido($slug)) continue;

        $meta = array('nome' => $slug, 'descricao' => '', 'icone' => '📦');
        $mp = $dir . '/app.json';
        if (is_file($mp)) {
            $j = json_decode(file_get_contents($mp), true);
            if (is_array($j)) $meta = array_merge($meta, $j);
        }

        $lista[] = array(
            'slug'       => $slug,
            'nome'       => $meta['nome'],
            'descricao'  => $meta['descricao'],
            'icone'      => $meta['icone'],
            'instalador' => instalador_mais_recente($dir),
        );
    }
    // Apps com instalador primeiro; depois ordem alfabética.
    usort($lista, function ($a, $b) {
        $da = $a['instalador'] ? 0 : 1;
        $db = $b['instalador'] ? 0 : 1;
        if ($da !== $db) return $da - $db;
        return strcasecmp($a['nome'], $b['nome']);
    });
    return $lista;
}

/** Escape HTML (mesma convenção do resto do projeto). */
function eh($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
