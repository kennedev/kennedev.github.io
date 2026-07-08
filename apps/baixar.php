<?php
require_once __DIR__ . '/lib.php';

function nao_encontrado() {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Aplicativo não encontrado.');
}

$slug = $_GET['app'] ?? '';
$dir  = pasta_do_app($slug);          // valida slug + barra path traversal
if ($dir === null) nao_encontrado();

$inst = instalador_mais_recente($dir);
if ($inst === null) nao_encontrado();

// Nome de download saneado (sem espaços/caracteres problemáticos no header).
$download = preg_replace('/[^\w.\-]+/', '-', $inst['nome']);

// Encerra qualquer buffer aberto para não corromper o binário.
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download . '"');
header('Content-Length: ' . $inst['tamanho']);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

readfile($inst['path']);
exit;
