<?php
// Helpers compartilhados do site público da Luis Imóveis.
// Usa a mesma conexão/credenciais do admin (única fonte de config).
require_once __DIR__ . '/../admin/lib/db.php';

// ── Dados da imobiliária (edite aqui) ─────────────────────────────────────────
// Extraídos da placa. Itens marcados (confirmar) precisam ser validados com o cliente.
if (!defined('SITE_NOME')) {
    define('SITE_NOME', 'Luis Imóveis');
    define('SITE_TAGLINE', 'Compra · Vende · Aluga · Administra');
    define('SITE_CRECI', 'CRECI 29.564-J');
    // Telefones — Unidade I e Unidade II (extraídos da placa)
    define('SITE_TEL_U1', '(11) 2361-0083');
    define('SITE_TEL_U1_2', '(11) 93905-6691');
    define('SITE_TEL_U2', '(11) 5662-8359');
    define('SITE_TEL_U2_2', '(11) 5663-3097');
    // Telefone principal (compat. com usos existentes)
    define('SITE_TEL_FIXO', '(11) 2361-0083');
    define('SITE_TEL_FIXO_RAW', '551123610083');
    define('SITE_WHATSAPP', '5511974734831');
    define('SITE_WHATSAPP_FMT', '(11) 97473-4831');
    define('SITE_EMAIL', 'contato@imobiliarialuisimoveis.com.br'); // (placeholder — confirmar)
    define('SITE_URL', 'https://www.imobiliarialuisimoveis.com.br');
    define('SITE_CIDADE', 'São Paulo · SP');           // (confirmar endereço completo)
    define('SITE_INSTAGRAM', '');                      // ex.: https://instagram.com/...
}

/** Garante a conexão; se faltar config/banco, mostra página de "em configuração". */
function garantir_db() {
    try {
        db();
    } catch (Throwable $ex) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Em breve</title>';
        echo '<div style="font-family:system-ui;max-width:520px;margin:18vh auto;text-align:center;color:#1F1D1A;padding:0 24px">';
        echo '<h1 style="font-weight:800;font-size:28px">Site em configuração</h1>';
        echo '<p style="color:#6B7670;margin-top:8px">Estamos preparando tudo. Volte em instantes.</p></div>';
        exit;
    }
}

// ── Formatação ────────────────────────────────────────────────────────────────
function preco_fmt($v) {
    if ($v === null || $v === '' || (float) $v <= 0) return 'Sob consulta';
    return 'R$ ' . number_format((float) $v, 0, ',', '.');
}

function area_fmt($v) {
    if ($v === null || $v === '' || (float) $v <= 0) return '';
    return number_format((float) $v, 0, ',', '.') . ' m²';
}

function tipo_label($t) {
    $m = array('casa' => 'Casa', 'apartamento' => 'Apartamento', 'sobrado' => 'Sobrado',
        'terreno' => 'Terreno', 'comercial' => 'Imóvel comercial', 'sala' => 'Sala comercial',
        'galpao' => 'Galpão', 'chacara' => 'Chácara');
    return $m[$t] ?? ucfirst($t);
}

function finalidade_tag($f) {
    return $f === 'aluguel' ? 'Para alugar' : 'À venda';
}

function status_label($s) {
    $m = array('disponivel' => 'Disponível', 'reservado' => 'Reservado',
        'vendido' => 'Vendido', 'alugado' => 'Alugado', 'inativo' => 'Inativo');
    return $m[$s] ?? $s;
}

function caracteristicas_mapa() {
    return array(
        'piscina' => 'Piscina', 'churrasqueira' => 'Churrasqueira', 'academia' => 'Academia',
        'salao_festas' => 'Salão de festas', 'portaria_24h' => 'Portaria 24h', 'elevador' => 'Elevador',
        'mobiliado' => 'Mobiliado', 'ar_condicionado' => 'Ar-condicionado', 'varanda_gourmet' => 'Varanda gourmet',
        'jardim' => 'Jardim', 'quintal' => 'Quintal', 'area_servico' => 'Área de serviço',
        'closet' => 'Closet', 'aquecimento_solar' => 'Aquecimento solar', 'pet_friendly' => 'Aceita pet',
        'energia_solar' => 'Energia solar',
    );
}

/** Decodifica o JSON de características num array de chaves. */
function caracteristicas_do_imovel($im) {
    $raw = $im['caracteristicas'] ?? null;
    if (!$raw) return array();
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : array();
}

// ── Fotos ─────────────────────────────────────────────────────────────────────
/** URL da foto. Aceita URL externa (seed) ou arquivo local em uploads/imoveis/{id}/. */
function foto_url($imovelId, $arquivo, $thumb = false, $prefix = '') {
    if (!$arquivo) return $prefix . 'assets/img/placeholder.svg';
    if (preg_match('~^https?://~', $arquivo)) return $arquivo;
    if ($thumb) {
        $dot = strrpos($arquivo, '.');
        $arquivo = $dot !== false ? substr($arquivo, 0, $dot) . '_t' . substr($arquivo, $dot) : $arquivo . '_t';
    }
    return $prefix . 'uploads/imoveis/' . (int) $imovelId . '/' . $arquivo;
}

// ── Consultas ───────────────────────────────────────────────────────────────--
function _imoveis_where($f, &$p) {
    $w = array("i.status = 'disponivel'");
    if (!empty($f['finalidade'])) { $w[] = 'i.finalidade = ?'; $p[] = $f['finalidade']; }
    if (!empty($f['tipo']))       { $w[] = 'i.tipo = ?';       $p[] = $f['tipo']; }
    if (!empty($f['cidade']))     { $w[] = 'i.cidade = ?';     $p[] = $f['cidade']; }
    if (!empty($f['bairro']))     { $w[] = 'i.bairro = ?';     $p[] = $f['bairro']; }
    if (!empty($f['dormitorios'])){ $w[] = 'i.dormitorios >= ?'; $p[] = (int) $f['dormitorios']; }
    if (!empty($f['vagas']))      { $w[] = 'i.vagas >= ?';     $p[] = (int) $f['vagas']; }
    if (isset($f['preco_min']) && $f['preco_min'] !== '') { $w[] = 'i.preco >= ?'; $p[] = (float) $f['preco_min']; }
    if (isset($f['preco_max']) && $f['preco_max'] !== '') { $w[] = 'i.preco <= ?'; $p[] = (float) $f['preco_max']; }
    if (!empty($f['q'])) {
        $w[] = '(i.titulo LIKE ? OR i.bairro LIKE ? OR i.cidade LIKE ? OR i.referencia LIKE ?)';
        $t = '%' . $f['q'] . '%';
        array_push($p, $t, $t, $t, $t);
    }
    if (!empty($f['destaque'])) { $w[] = 'i.destaque = 1'; }
    return implode(' AND ', $w);
}

function buscar_imoveis($f = array(), $limit = 12, $offset = 0) {
    $p = array();
    $where = _imoveis_where($f, $p);
    switch ($f['ordem'] ?? '') {
        case 'menor_preco': $ord = 'i.preco IS NULL ASC, i.preco ASC'; break;
        case 'maior_preco': $ord = 'i.preco DESC'; break;
        default:            $ord = 'i.destaque DESC, i.criado_em DESC';
    }
    $sql = 'SELECT i.*, '
         . '(SELECT f.arquivo FROM imovel_fotos f WHERE f.imovel_id = i.id ORDER BY f.capa DESC, f.ordem ASC, f.id ASC LIMIT 1) AS capa_arquivo '
         . 'FROM imoveis i WHERE ' . $where . ' ORDER BY ' . $ord
         . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function contar_imoveis($f = array()) {
    $p = array();
    $where = _imoveis_where($f, $p);
    $st = db()->prepare('SELECT COUNT(*) FROM imoveis i WHERE ' . $where);
    $st->execute($p);
    return (int) $st->fetchColumn();
}

function buscar_imovel_por_ref($ref) {
    $st = db()->prepare('SELECT * FROM imoveis WHERE referencia = ? LIMIT 1');
    $st->execute(array($ref));
    return $st->fetch();
}

function fotos_do_imovel($id) {
    $st = db()->prepare('SELECT * FROM imovel_fotos WHERE imovel_id = ? ORDER BY capa DESC, ordem ASC, id ASC');
    $st->execute(array((int) $id));
    return $st->fetchAll();
}

function lista_cidades() {
    return db()->query("SELECT DISTINCT cidade FROM imoveis WHERE status='disponivel' AND cidade IS NOT NULL AND cidade<>'' ORDER BY cidade")
        ->fetchAll(PDO::FETCH_COLUMN);
}

function lista_bairros() {
    return db()->query("SELECT DISTINCT bairro FROM imoveis WHERE status='disponivel' AND bairro IS NOT NULL AND bairro<>'' ORDER BY bairro")
        ->fetchAll(PDO::FETCH_COLUMN);
}

function registrar_lead($nome, $telefone, $email, $mensagem, $imovelId, $origem) {
    $st = db()->prepare('INSERT INTO leads (nome, telefone, email, mensagem, imovel_id, origem) VALUES (?,?,?,?,?,?)');
    $st->execute(array($nome, $telefone, $email ?: null, $mensagem ?: null, $imovelId ?: null, $origem));
}

// ── Links ─────────────────────────────────────────────────────────────────────
function wa_link($texto = '') {
    return 'https://wa.me/' . SITE_WHATSAPP . ($texto !== '' ? '?text=' . rawurlencode($texto) : '');
}

/** Monta href tel: a partir de um número formatado (assume Brasil, +55). */
function tel_href($fmt) {
    return 'tel:+55' . preg_replace('/\D+/', '', $fmt);
}

// ── Ícones (SVG inline, traço fino) ───────────────────────────────────────────
function icone($nome) {
    $i = array(
        'cama' => '<path d="M3 7v10M3 12h18M21 17V9a2 2 0 0 0-2-2H8v5"/>',
        'banho' => '<path d="M4 12h16v2a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-2zM6 12V6a2 2 0 0 1 2-2 2 2 0 0 1 2 2"/>',
        'carro' => '<path d="M5 13l1.5-4.5A2 2 0 0 1 8.4 7h7.2a2 2 0 0 1 1.9 1.5L19 13M5 13h14v4H5v-4zM7 17v2M17 17v2"/>',
        'area' => '<path d="M4 4h16v16H4zM4 9h16M9 9v11"/>',
        'pino' => '<path d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2"/>',
        'tag' => '<path d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M14.5 8.5l2.5 2.5"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L9.6 9.8a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<path d="M4 5h16v14H4z"/><path d="M4 6l8 6 8-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    );
    $p = $i[$nome] ?? '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Linha de specs (só mostra o que existe). $prefix vazio nas páginas públicas. */
function specs_imovel($im) {
    $out = array();
    if ((int) $im['dormitorios'] > 0) $out[] = icone('cama') . '<span>' . (int) $im['dormitorios'] . ' dorm.</span>';
    if ((int) $im['banheiros'] > 0)   $out[] = icone('banho') . '<span>' . (int) $im['banheiros'] . ' banh.</span>';
    if ((int) $im['vagas'] > 0)       $out[] = icone('carro') . '<span>' . (int) $im['vagas'] . ' vaga' . ((int) $im['vagas'] > 1 ? 's' : '') . '</span>';
    $area = $im['area_util'] ?: $im['area_total'];
    if ($area)                        $out[] = icone('area') . '<span>' . area_fmt($area) . '</span>';
    return $out;
}

/** Card de imóvel. $prefix permite uso fora da raiz (não usado hoje). */
function card_imovel($im, $prefix = '') {
    $ref  = e($im['referencia']);
    $href = $prefix . 'imovel.php?ref=' . urlencode($im['referencia']);
    $capa = foto_url($im['id'], $im['capa_arquivo'] ?? null, true, $prefix);
    $local = trim(($im['bairro'] ?? '') . ($im['bairro'] && $im['cidade'] ? ', ' : '') . ($im['cidade'] ?? ''));
    $specs = specs_imovel($im);

    $h  = '<a class="card-imovel reveal" href="' . $href . '">';
    $h .= '<div class="ci-media"><img loading="lazy" src="' . e($capa) . '" alt="' . e($im['titulo']) . '">';
    $h .= '<span class="ci-fin">' . e(finalidade_tag($im['finalidade'])) . '</span></div>';
    $h .= '<div class="ci-body">';
    $h .= '<span class="eyebrow">' . e(tipo_label($im['tipo'])) . ' · ' . $ref . '</span>';
    $h .= '<h3>' . e($im['titulo']) . '</h3>';
    if ($local) $h .= '<p class="ci-local">' . icone('pino') . e($local) . '</p>';
    if ($specs) $h .= '<div class="ci-specs">' . implode('', array_map(function ($s) { return '<i>' . $s . '</i>'; }, $specs)) . '</div>';
    $h .= '<p class="ci-preco">' . e(preco_fmt($im['preco'])) . ($im['finalidade'] === 'aluguel' && $im['preco'] > 0 ? '<small>/mês</small>' : '') . '</p>';
    $h .= '</div></a>';
    return $h;
}

// ── Layout público (header + footer) ──────────────────────────────────────────
/**
 * $opts: ['desc'=>, 'og_image'=>, 'canonical'=>, 'jsonld'=>(string), 'home'=>bool]
 */
function site_head($titulo, $opts = array()) {
    $desc = $opts['desc'] ?? 'Imóveis para compra, venda e locação com a Luis Imóveis. Casas, apartamentos, terrenos e imóveis comerciais.';
    $og   = $opts['og_image'] ?? 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=70';
    $canon = $opts['canonical'] ?? '';
    $home = !empty($opts['home']);
    $tituloFull = $titulo ? ($titulo . ' · ' . SITE_NOME) : (SITE_NOME . ' · ' . SITE_TAGLINE);

    echo '<!doctype html><html lang="pt-br"><head>';
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($tituloFull) . '</title>';
    echo '<meta name="description" content="' . e($desc) . '">';
    if ($canon) echo '<link rel="canonical" href="' . e($canon) . '">';
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="' . e(SITE_NOME) . '">';
    echo '<meta property="og:title" content="' . e($tituloFull) . '"><meta property="og:description" content="' . e($desc) . '">';
    echo '<meta property="og:image" content="' . e($og) . '">';
    if ($canon) echo '<meta property="og:url" content="' . e($canon) . '">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="assets/css/style.css">';
    if (!empty($opts['jsonld'])) echo '<script type="application/ld+json">' . $opts['jsonld'] . '</script>';
    echo '</head><body class="' . ($home ? 'home' : 'inner') . '">';

    echo '<header class="site-header" id="siteHeader"><div class="shell nav-inner">';
    echo '<a class="logo" href="index.php"><img class="logo-mark-img" src="assets/img/logo-li-mark.png" alt="' . e(SITE_NOME) . '" height="40"><span class="logo-txt">' . e(SITE_NOME) . '<small>' . e(SITE_TAGLINE) . '</small></span></a>';
    echo '<input type="checkbox" id="navtoggle" class="navtoggle" hidden>';
    echo '<label for="navtoggle" class="burger" aria-label="Menu"><span></span><span></span><span></span></label>';
    echo '<nav class="site-nav">';
    echo '<a href="index.php">Início</a>';
    echo '<a href="imoveis.php?finalidade=venda">Comprar</a>';
    echo '<a href="imoveis.php?finalidade=aluguel">Alugar</a>';
    echo '<a href="sobre.php">Sobre</a>';
    echo '<a href="contato.php">Contato</a>';
    echo '<a class="nav-cta" href="' . e(wa_link('Olá! Vim pelo site da ' . SITE_NOME . ' e gostaria de atendimento.')) . '" target="_blank" rel="noopener">WhatsApp</a>';
    echo '</nav></div></header>';

    echo '<main id="conteudo">';
}

function site_foot() {
    echo '</main>';
    echo '<footer class="site-footer"><div class="shell footer-grid">';

    echo '<div class="f-brand"><a class="logo logo--light" href="index.php"><img class="logo-mark-img" src="assets/img/logo-li-mark.png" alt="' . e(SITE_NOME) . '" height="40"><span class="logo-txt">' . e(SITE_NOME) . '<small>' . e(SITE_TAGLINE) . '</small></span></a>';
    echo '<p class="f-creci">' . e(SITE_CRECI) . '</p>';
    echo '<p class="f-desc">Compra, venda e locação de imóveis com atendimento próximo e transparente.</p></div>';

    echo '<div class="f-col"><h4>Navegação</h4>';
    echo '<a href="imoveis.php?finalidade=venda">Comprar</a><a href="imoveis.php?finalidade=aluguel">Alugar</a>';
    echo '<a href="sobre.php">Sobre</a><a href="contato.php">Contato</a></div>';

    echo '<div class="f-col"><h4>Contato</h4>';
    echo '<a href="' . e(tel_href(SITE_TEL_U1)) . '">Unidade I · ' . e(SITE_TEL_U1) . '</a>';
    echo '<a href="' . e(tel_href(SITE_TEL_U1_2)) . '">Unidade I · ' . e(SITE_TEL_U1_2) . '</a>';
    echo '<a href="' . e(tel_href(SITE_TEL_U2)) . '">Unidade II · ' . e(SITE_TEL_U2) . '</a>';
    echo '<a href="' . e(tel_href(SITE_TEL_U2_2)) . '">Unidade II · ' . e(SITE_TEL_U2_2) . '</a>';
    echo '<a href="' . e(wa_link('Olá! Vim pelo site da ' . SITE_NOME . '.')) . '" target="_blank" rel="noopener">WhatsApp ' . e(SITE_WHATSAPP_FMT) . '</a>';
    echo '<a href="mailto:' . e(SITE_EMAIL) . '">' . e(SITE_EMAIL) . '</a>';
    echo '<span class="muted">' . e(SITE_CIDADE) . '</span></div>';

    echo '</div><div class="shell f-base"><span>© ' . date('Y') . ' ' . e(SITE_NOME) . ' · ' . e(SITE_CRECI) . '</span>';
    echo '<span>Desenvolvido por <a href="https://www.kennedev.com.br" target="_blank" rel="noopener">Kennedev</a></span></div></footer>';

    echo '<a class="wa-float" href="' . e(wa_link('Olá! Vim pelo site da ' . SITE_NOME . ' e gostaria de atendimento.')) . '" target="_blank" rel="noopener" aria-label="WhatsApp">';
    echo '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.06 24l1.7-6.2A11.9 11.9 0 1 1 12 24a11.9 11.9 0 0 1-5.7-1.46L.06 24zM6.6 20.1l.36.21a9.9 9.9 0 1 0-3.4-3.42l.23.37-1 3.66 3.81-.82zM17.9 14.3c-.15-.25-.55-.4-1.15-.7s-1.35-.66-1.55-.74c-.2-.07-.36-.11-.51.12s-.59.73-.72.88c-.13.15-.27.17-.5.06a8.1 8.1 0 0 1-2.38-1.47 9 9 0 0 1-1.65-2.05c-.17-.3 0-.45.13-.6.12-.12.27-.31.4-.47.13-.15.17-.26.26-.43.09-.17.04-.32-.02-.45s-.51-1.23-.7-1.69c-.18-.44-.37-.38-.51-.39h-.43c-.15 0-.4.06-.6.29s-.79.77-.79 1.88.81 2.18.92 2.33c.11.15 1.59 2.43 3.86 3.41.54.23.96.37 1.29.47.54.17 1.03.15 1.42.09.43-.06 1.35-.55 1.54-1.08.19-.53.19-.98.13-1.08z"/></svg></a>';

    echo '<script src="assets/js/main.js"></script></body></html>';
}
