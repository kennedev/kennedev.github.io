<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();

$ref = trim($_GET['ref'] ?? '');
$im = $ref !== '' ? buscar_imovel_por_ref($ref) : null;
if (!$im || $im['status'] === 'inativo') {
    http_response_code(404);
    site_head('Imóvel não encontrado');
    echo '<div class="shell vazio" style="padding-top:140px"><h3>Imóvel não encontrado</h3>'
       . '<p>Este anúncio pode ter saído do ar.</p><p style="margin-top:18px">'
       . '<a class="btn btn-primary" href="imoveis.php">Ver imóveis disponíveis</a></p></div>';
    site_foot();
    exit;
}

// ── Lead (formulário de interesse) ────────────────────────────────────────────
$leadOk = false;
$leadErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website'])) {                 // honeypot anti-spam
        $leadOk = true;                               // finge sucesso para o bot
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $tel  = trim($_POST['telefone'] ?? '');
        if ($nome === '' || $tel === '') {
            $leadErro = 'Informe ao menos nome e telefone.';
        } else {
            registrar_lead($nome, $tel, trim($_POST['email'] ?? ''), trim($_POST['mensagem'] ?? ''), $im['id'], 'imovel');
            $leadOk = true;
        }
    }
}

$fotos = fotos_do_imovel($im['id']);
$thumbs = array_slice($fotos, 1);
$capaUrl = foto_url($im['id'], $fotos[0]['arquivo'] ?? ($im['capa_arquivo'] ?? null), false);
$caracs = caracteristicas_do_imovel($im);
$mapaCaracs = caracteristicas_mapa();
$local = trim(($im['bairro'] ?? '') . ($im['bairro'] && $im['cidade'] ? ', ' : '') . ($im['cidade'] ?? '') . ($im['uf'] ? ' / ' . $im['uf'] : ''));

$descCurta = $im['descricao'] ? mb_substr(trim(preg_replace('/\s+/', ' ', $im['descricao'])), 0, 155) : ('' . tipo_label($im['tipo']) . ' em ' . $local);
$waTexto = 'Olá! Tenho interesse no imóvel ' . $im['referencia'] . ' (' . $im['titulo'] . ') que vi no site.';

// JSON-LD
$jsonld = json_encode(array(
    '@context' => 'https://schema.org',
    '@type' => 'Residence',
    'name' => $im['titulo'],
    'description' => $descCurta,
    'image' => array($capaUrl),
    'address' => array(
        '@type' => 'PostalAddress',
        'addressLocality' => $im['cidade'],
        'addressRegion' => $im['uf'],
        'addressCountry' => 'BR',
    ),
) + ($im['preco'] > 0 ? array('offers' => array(
        '@type' => 'Offer',
        'price' => (string) (float) $im['preco'],
        'priceCurrency' => 'BRL',
        'availability' => 'https://schema.org/' . ($im['status'] === 'disponivel' ? 'InStock' : 'SoldOut'),
    )) : array()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

site_head($im['titulo'], array(
    'desc' => $descCurta,
    'og_image' => $capaUrl,
    'canonical' => SITE_URL . '/imovel.php?ref=' . urlencode($im['referencia']),
    'jsonld' => $jsonld,
));
?>

<div class="shell">
  <p class="breadcrumb"><a href="index.php">Início</a> · <a href="imoveis.php">Imóveis</a> · <?php echo e($im['referencia']); ?></p>

  <div class="imovel-top">
    <div>
      <span class="eyebrow"><?php echo e(tipo_label($im['tipo'])); ?> · <?php echo e(finalidade_tag($im['finalidade'])); ?> · <?php echo e($im['referencia']); ?></span>
      <h1><?php echo e($im['titulo']); ?></h1>
      <?php if ($local): ?><p class="local"><?php echo icone('pino'); ?><?php echo e($local); ?></p><?php endif; ?>
    </div>
  </div>

  <div class="galeria">
    <figure class="galeria-main" data-lightbox="<?php echo e($capaUrl); ?>" data-alt="<?php echo e($im['titulo']); ?>">
      <img src="<?php echo e($capaUrl); ?>" alt="<?php echo e($im['titulo']); ?>">
    </figure>
    <?php if ($thumbs): ?>
      <div class="galeria-thumbs">
        <?php
        $ocultos = count($thumbs) - 6;
        foreach ($thumbs as $i => $f):
            $g = foto_url($im['id'], $f['arquivo'], false);
            $t = foto_url($im['id'], $f['arquivo'], true);
        ?>
          <figure data-lightbox="<?php echo e($g); ?>" data-alt="<?php echo e($im['titulo']); ?>">
            <img loading="lazy" src="<?php echo e($t); ?>" alt="<?php echo e($f['legenda'] ?: $im['titulo']); ?>">
            <?php if ($i === 5 && $ocultos > 0): ?><span class="g-more">+<?php echo $ocultos; ?></span><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="imovel-cols">
    <div class="imovel-main">
      <?php $specs = specs_imovel($im); if ($specs): ?>
      <div class="specs-big">
        <?php if ((int) $im['dormitorios'] > 0): ?><div class="sb"><?php echo icone('cama'); ?><b><?php echo (int) $im['dormitorios']; ?></b><span>Dormitórios<?php echo (int) $im['suites'] > 0 ? ' · ' . (int) $im['suites'] . ' suíte' . ((int) $im['suites'] > 1 ? 's' : '') : ''; ?></span></div><?php endif; ?>
        <?php if ((int) $im['banheiros'] > 0): ?><div class="sb"><?php echo icone('banho'); ?><b><?php echo (int) $im['banheiros']; ?></b><span>Banheiros</span></div><?php endif; ?>
        <?php if ((int) $im['vagas'] > 0): ?><div class="sb"><?php echo icone('carro'); ?><b><?php echo (int) $im['vagas']; ?></b><span>Vagas</span></div><?php endif; ?>
        <?php $area = $im['area_util'] ?: $im['area_total']; if ($area): ?><div class="sb"><?php echo icone('area'); ?><b><?php echo number_format((float) $area, 0, ',', '.'); ?></b><span>m²<?php echo $im['area_util'] && $im['area_total'] ? ' úteis' : ''; ?></span></div><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if (trim((string) $im['descricao']) !== ''): ?>
      <div class="bloco">
        <h2>Sobre o imóvel</h2>
        <p class="descricao"><?php echo e($im['descricao']); ?></p>
      </div>
      <?php endif; ?>

      <?php if ($caracs): ?>
      <div class="bloco">
        <h2>Características</h2>
        <ul class="caracs">
          <?php foreach ($caracs as $c): if (isset($mapaCaracs[$c])): ?>
            <li><?php echo e($mapaCaracs[$c]); ?></li>
          <?php endif; endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($im['latitude'] && $im['longitude']): ?>
      <div class="bloco">
        <h2>Localização</h2>
        <iframe class="mapa" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
          src="https://www.google.com/maps?q=<?php echo e($im['latitude']); ?>,<?php echo e($im['longitude']); ?>&z=15&output=embed"></iframe>
        <?php if (!empty($im['mostrar_endereco'])): ?><p class="muted" style="margin-top:10px;color:var(--muted)"><?php echo e(trim(($im['logradouro'] ?? '') . ' ' . ($im['numero'] ?? '') . ' · ' . $local)); ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <aside class="sidebar">
      <div class="price-card">
        <span class="ref">Ref. <?php echo e($im['referencia']); ?></span>
        <span class="fin"><?php echo e(finalidade_tag($im['finalidade'])); ?><?php echo $im['status'] !== 'disponivel' ? ' · ' . e(status_label($im['status'])) : ''; ?></span>
        <div class="valor"><?php echo e(preco_fmt($im['preco'])); ?><?php echo $im['finalidade'] === 'aluguel' && $im['preco'] > 0 ? '<small>/mês</small>' : ''; ?></div>
        <div class="extras">
          <?php if ($im['condominio'] > 0): ?><span>Condomínio <b>R$ <?php echo number_format((float) $im['condominio'], 0, ',', '.'); ?></b></span><?php endif; ?>
          <?php if ($im['iptu'] > 0): ?><span>IPTU <b>R$ <?php echo number_format((float) $im['iptu'], 0, ',', '.'); ?></b></span><?php endif; ?>
        </div>
        <a class="btn btn-primary btn-block" href="<?php echo e(wa_link($waTexto)); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        <a class="btn btn-ghost btn-block" href="tel:+<?php echo e(SITE_TEL_FIXO_RAW); ?>"><?php echo e(SITE_TEL_FIXO); ?></a>
      </div>

      <div class="price-card" style="margin-top:18px;box-shadow:none">
        <h2 style="font-size:21px;margin-bottom:4px">Tenho interesse</h2>
        <p class="muted" style="margin-bottom:16px">Deixe seus dados e retornamos rapidinho.</p>
        <?php if ($leadOk): ?>
          <div class="aviso ok">Recebemos seu contato! Em breve falaremos com você.</div>
        <?php else: ?>
          <?php if ($leadErro): ?><div class="aviso erro"><?php echo e($leadErro); ?></div><?php endif; ?>
          <form method="post" class="form-grid" action="imovel.php?ref=<?php echo urlencode($im['referencia']); ?>">
            <div class="hp"><label>Não preencha<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="field"><label>Nome*</label><input type="text" name="nome" required value="<?php echo e($_POST['nome'] ?? ''); ?>"></div>
            <div class="field"><label>Telefone / WhatsApp*</label><input type="text" name="telefone" required value="<?php echo e($_POST['telefone'] ?? ''); ?>"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>"></div>
            <div class="field"><label>Mensagem</label><textarea name="mensagem"><?php echo e($_POST['mensagem'] ?? ('Tenho interesse no imóvel ' . $im['referencia'] . '.')); ?></textarea></div>
            <button class="btn btn-dark btn-block" type="submit">Enviar interesse</button>
          </form>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>

<?php site_foot(); ?>
