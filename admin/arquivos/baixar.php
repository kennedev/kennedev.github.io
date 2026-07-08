<?php
/* Download autenticado dos arquivos guardados em ./files/. */
require_once __DIR__ . '/../lib/auth.php';
exigir_admin();

$DIR  = realpath(__DIR__ . '/files');
$nome = basename($_GET['arquivo'] ?? '');           // barra path traversal
$path = $DIR ? realpath($DIR . '/' . $nome) : false;

// Confere que o caminho resolvido está mesmo dentro de ./files/ e é um arquivo.
if ($nome === '' || !$path || strpos($path, $DIR) !== 0 || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Arquivo não encontrado.');
}

$download = preg_replace('/[^\w.\-]+/', '-', $nome);

while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download . '"');
header('Content-Length: ' . filesize($path));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;
