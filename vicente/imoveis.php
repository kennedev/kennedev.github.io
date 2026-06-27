<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();

// ── Filtros vindos da URL ─────────────────────────────────────────────────────
$campos = array('finalidade', 'tipo', 'cidade', 'bairro', 'dormitorios', 'vagas', 'preco_min', 'preco_max', 'q', 'ordem');
$filtros = array();
foreach ($campos as $c) {
    if (isset($_GET[$c]) && $_GET[$c] !== '') $filtros[$c] = trim($_GET[$c]);
}

$porPagina = 9;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$total = contar_imoveis($filtros);
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) $pagina = $totalPaginas;
$offset = ($pagina - 1) * $porPagina;
$imoveis = buscar_imoveis($filtros, $porPagina, $offset);

$cidades = lista_cidades();

$fin = $filtros['finalidade'] ?? '';
$titulo = $fin === 'venda' ? 'Imóveis à venda' : ($fin === 'aluguel' ? 'Imóveis para alugar' : 'Todos os imóveis');

/** Inputs ocultos com os filtros atuais, exceto a(s) chave(s) informada(s). */
function hidden_filtros($filtros, $exceto = array()) {
    $h = '';
    foreach ($filtros as $k => $v) {
        if (in_array($k, (array) $exceto, true)) continue;
        $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $h;
}

site_head($titulo, array('canonical' => SITE_URL . '/imoveis.php'));
?>

<div class="shell page-head">
  <span class="eyebrow">Carteira de imóveis</span>
  <h1><?php echo e($titulo); ?></h1>
</div>

<div class="filtros">
  <form class="shell" method="get" action="imoveis.php">
    <div class="fcampo">
      <label>Finalidade</label>
      <select name="finalidade" data-autosubmit>
        <option value="">Comprar e alugar</option>
        <option value="venda" <?php if ($fin === 'venda') echo 'selected'; ?>>Comprar</option>
        <option value="aluguel" <?php if ($fin === 'aluguel') echo 'selected'; ?>>Alugar</option>
      </select>
    </div>
    <div class="fcampo">
      <label>Tipo</label>
      <select name="tipo" data-autosubmit>
        <option value="">Todos</option>
        <?php foreach (array('casa','apartamento','sobrado','terreno','comercial','sala','galpao','chacara') as $t): ?>
          <option value="<?php echo $t; ?>" <?php if (($filtros['tipo'] ?? '') === $t) echo 'selected'; ?>><?php echo e(tipo_label($t)); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fcampo">
      <label>Dormitórios</label>
      <select name="dormitorios" data-autosubmit>
        <option value="">Qualquer</option>
        <?php foreach (array(1,2,3,4) as $d): ?>
          <option value="<?php echo $d; ?>" <?php if ((int)($filtros['dormitorios'] ?? 0) === $d) echo 'selected'; ?>><?php echo $d; ?>+</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fcampo">
      <label>Cidade</label>
      <select name="cidade" data-autosubmit>
        <option value="">Todas</option>
        <?php foreach ($cidades as $cid): ?>
          <option value="<?php echo e($cid); ?>" <?php if (($filtros['cidade'] ?? '') === $cid) echo 'selected'; ?>><?php echo e($cid); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fcampo">
      <label>Busca</label>
      <input type="text" name="q" value="<?php echo e($filtros['q'] ?? ''); ?>" placeholder="Bairro ou código">
    </div>
    <div class="fcampo fsubmit">
      <label>&nbsp;</label>
      <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
    </div>
  </form>
</div>

<div class="shell">
  <div class="result-bar">
    <p class="count"><b><?php echo $total; ?></b> <?php echo $total === 1 ? 'imóvel encontrado' : 'imóveis encontrados'; ?></p>
    <form class="ordenar" method="get" action="imoveis.php">
      <?php echo hidden_filtros($filtros, array('ordem')); ?>
      <label for="ordem">Ordenar:</label>
      <select id="ordem" name="ordem" data-autosubmit>
        <option value="">Relevância</option>
        <option value="menor_preco" <?php if (($filtros['ordem'] ?? '') === 'menor_preco') echo 'selected'; ?>>Menor preço</option>
        <option value="maior_preco" <?php if (($filtros['ordem'] ?? '') === 'maior_preco') echo 'selected'; ?>>Maior preço</option>
      </select>
    </form>
  </div>

  <?php if ($imoveis): ?>
    <div class="imoveis-grid">
      <?php foreach ($imoveis as $im) echo card_imovel($im); ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
      <nav class="paginacao" aria-label="Paginação">
        <?php
        $base = $filtros;
        $linkPagina = function ($n) use ($base) {
            $base['pagina'] = $n;
            return 'imoveis.php?' . http_build_query($base);
        };
        if ($pagina > 1) echo '<a href="' . e($linkPagina($pagina - 1)) . '" aria-label="Anterior">&#8249;</a>';
        for ($n = 1; $n <= $totalPaginas; $n++) {
            if ($n == $pagina) echo '<span class="atual">' . $n . '</span>';
            elseif ($n <= 2 || $n > $totalPaginas - 2 || abs($n - $pagina) <= 1) echo '<a href="' . e($linkPagina($n)) . '">' . $n . '</a>';
            elseif ($n == 3 || $n == $totalPaginas - 2) echo '<span>…</span>';
        }
        if ($pagina < $totalPaginas) echo '<a href="' . e($linkPagina($pagina + 1)) . '" aria-label="Próxima">&#8250;</a>';
        ?>
      </nav>
    <?php endif; ?>

  <?php else: ?>
    <div class="vazio">
      <h3>Nenhum imóvel encontrado</h3>
      <p>Tente ajustar os filtros ou fale com a gente — podemos ter a opção certa fora do site.</p>
      <p style="margin-top:18px"><a class="btn btn-primary" href="<?php echo e(wa_link('Olá! Procuro um imóvel e não encontrei no site.')); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a></p>
    </div>
  <?php endif; ?>
</div>

<div style="height:70px"></div>
<?php site_foot(); ?>
