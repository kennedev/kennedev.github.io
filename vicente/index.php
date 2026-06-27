<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();

$destaques = buscar_imoveis(array('destaque' => 1), 6);
if (!$destaques) $destaques = buscar_imoveis(array(), 6); // sem destaques marcados → mostra recentes

site_head('', array('home' => true, 'canonical' => SITE_URL . '/'));
?>

<section class="hero">
  <div class="shell hero-inner reveal">
    <span class="eyebrow">Vicente Corretor de Imóveis · Desde 1992</span>
    <h1>O imóvel certo merece o cuidado certo.</h1>
    <p>Casas, apartamentos, terrenos e pontos comerciais para compra e locação — com atendimento próximo, do primeiro contato às chaves na mão.</p>

    <form class="busca" method="get" action="imoveis.php" role="search" aria-label="Buscar imóveis">
      <div class="busca-tabs">
        <input type="radio" name="finalidade" id="f-venda" value="venda" checked>
        <label for="f-venda">Comprar</label>
        <input type="radio" name="finalidade" id="f-aluguel" value="aluguel">
        <label for="f-aluguel">Alugar</label>
      </div>
      <div class="busca-campos">
        <select name="tipo" aria-label="Tipo de imóvel">
          <option value="">Todos os tipos</option>
          <option value="casa">Casa</option>
          <option value="apartamento">Apartamento</option>
          <option value="sobrado">Sobrado</option>
          <option value="terreno">Terreno</option>
          <option value="comercial">Comercial</option>
          <option value="chacara">Chácara</option>
        </select>
        <input type="text" name="q" placeholder="Bairro, cidade ou código (ex.: VI025)">
        <button class="btn btn-primary" type="submit">Buscar</button>
      </div>
    </form>
  </div>
</section>

<section class="section">
  <div class="shell">
    <div class="bar-top">
      <div class="section-head reveal">
        <span class="eyebrow">Seleção da casa</span>
        <h2>Imóveis em destaque</h2>
        <p>Uma curadoria do que temos de melhor neste momento.</p>
      </div>
      <a class="btn btn-ghost reveal" href="imoveis.php">Ver todos os imóveis</a>
    </div>

    <?php if ($destaques): ?>
      <div class="imoveis-grid">
        <?php foreach ($destaques as $im) echo card_imovel($im); ?>
      </div>
    <?php else: ?>
      <div class="vazio"><h3>Em breve, novos imóveis</h3><p>Estamos atualizando nossa carteira. Fale com a gente pelo WhatsApp.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="section tight">
  <div class="shell">
    <div class="section-head center reveal">
      <span class="eyebrow">Para onde você olha</span>
      <h2>Explore por tipo</h2>
    </div>
    <div class="cats">
      <a class="cat reveal" href="imoveis.php?tipo=casa"><img loading="lazy" src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=700&q=70" alt="Casas"><span>Casas</span></a>
      <a class="cat reveal" href="imoveis.php?tipo=apartamento"><img loading="lazy" src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=700&q=70" alt="Apartamentos"><span>Apartamentos</span></a>
      <a class="cat reveal" href="imoveis.php?tipo=comercial"><img loading="lazy" src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=700&q=70" alt="Comercial"><span>Comercial</span></a>
      <a class="cat reveal" href="imoveis.php?tipo=terreno"><img loading="lazy" src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=700&q=70" alt="Terrenos"><span>Terrenos</span></a>
    </div>
  </div>
</section>

<section class="section">
  <div class="shell split">
    <div class="reveal">
      <img loading="lazy" src="https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?auto=format&fit=crop&w=900&q=70" alt="Atendimento <?php echo e(SITE_NOME); ?>">
    </div>
    <div class="reveal">
      <span class="eyebrow">Quem somos</span>
      <h2 style="font-size:clamp(28px,4vw,42px);margin:14px 0 16px">Mais de 30 anos realizando o sonho da casa própria.</h2>
      <p class="lead" style="margin-bottom:22px">Desde 1992, a Vicente Corretor de Imóveis atua com respeito ao cliente e total transparência. Já foram mais de 5.000 imóveis vendidos — entre casas, apartamentos e terrenos.</p>
      <ul class="caracs" style="margin-bottom:26px">
        <li>Avaliação de imóveis sem compromisso</li>
        <li>Departamento Jurídico, SAC e Despachantaria próprios</li>
        <li>Documentação e contratos acompanhados de perto</li>
        <li>Regularizada — <?php echo e(SITE_CRECI); ?></li>
      </ul>
      <a class="btn btn-dark" href="sobre.php">Conheça a <?php echo e(SITE_NOME); ?></a>
    </div>
  </div>
</section>

<section class="section tight">
  <div class="shell">
    <div class="cta-band reveal">
      <div>
        <span class="eyebrow">Quer vender ou alugar?</span>
        <h2>Anuncie seu imóvel com a <?php echo e(SITE_NOME); ?>.</h2>
        <p>Fazemos a avaliação, a divulgação e cuidamos da negociação para você. Fale com nossa equipe agora.</p>
      </div>
      <a class="btn btn-primary btn-lg" href="<?php echo e(wa_link('Olá! Quero anunciar meu imóvel com a ' . SITE_NOME . '.')); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </div>
  </div>
</section>

<?php site_foot(); ?>
