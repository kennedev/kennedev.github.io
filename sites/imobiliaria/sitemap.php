<?php
// Sitemap dinâmico da Luis Imóveis: páginas fixas + imóveis disponíveis.
require_once __DIR__ . '/lib/publico.php';

header('Content-Type: application/xml; charset=utf-8');

$base = rtrim(SITE_URL, '/');
$hoje = date('Y-m-d');

function _url($loc, $changefreq, $priority, $lastmod = null) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    if ($lastmod) echo '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1) . "</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Páginas fixas
_url($base . '/', 'daily', '1.0', $hoje);
_url($base . '/imoveis.php', 'daily', '0.9', $hoje);
_url($base . '/imoveis.php?finalidade=venda', 'daily', '0.8', $hoje);
_url($base . '/imoveis.php?finalidade=aluguel', 'daily', '0.8', $hoje);
_url($base . '/sobre.php', 'monthly', '0.5');
_url($base . '/contato.php', 'monthly', '0.5');

// Imóveis disponíveis (páginas dinâmicas)
try {
    $st = db()->query("SELECT referencia FROM imoveis WHERE status = 'disponivel' AND referencia IS NOT NULL AND referencia <> '' ORDER BY criado_em DESC");
    foreach ($st as $row) {
        _url($base . '/imovel.php?ref=' . urlencode($row['referencia']), 'weekly', '0.7');
    }
} catch (Throwable $e) {
    // Sem banco/config: entrega ao menos as páginas fixas.
}

echo '</urlset>' . "\n";
