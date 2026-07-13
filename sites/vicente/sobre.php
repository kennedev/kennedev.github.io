<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();
site_head('Sobre', array('canonical' => SITE_URL . '/sobre.php',
    'desc' => 'Conheça a Vicente Corretor de Imóveis: 31 anos de experiência, atendimento transparente na compra, venda e administração de imóveis em Anchieta/RJ.'));
?>

<div class="shell page-head">
  <span class="eyebrow">Quem somos</span>
  <h1>Vicente Corretor de Imóveis</h1>
</div>

<section class="section" style="padding-top:30px">
  <div class="shell split">
    <div class="reveal">
      <img loading="lazy" src="https://images.unsplash.com/photo-1556912172-45b7abe8b7e1?auto=format&fit=crop&w=900&q=70" alt="Equipe <?php echo e(SITE_NOME); ?>">
    </div>
    <div class="reveal">
      <p class="lead" style="margin-bottom:18px">A Vicente Corretor de Imóveis iniciou suas atividades em 1992 com base em dois grandes princípios: respeito ao cliente e total transparência em seus procedimentos.</p>
      <p style="color:var(--muted);margin-bottom:16px">Desde então, ajudou a realizar o sonho da casa própria de milhares de brasileiros — mais de 5.000 unidades, entre terrenos, casas, apartamentos e outros imóveis já foram vendidos.</p>
      <p style="color:var(--muted);margin-bottom:16px">Os departamentos Jurídico, SAC e Despachantaria foram especialmente desenvolvidos para dar suporte na compra, administração e avaliação de imóveis com segurança e rapidez.</p>
      <p style="color:var(--muted)">Atuamos de forma regularizada e transparente, <?php echo e(SITE_CRECI); ?>. São 31 anos de experiência no setor imobiliário.</p>
    </div>
  </div>
</section>

<section class="section tight">
  <div class="shell">
    <div class="section-head center reveal">
      <span class="eyebrow">Despachantaria e assessoria</span>
      <h2>Serviços além da compra e venda</h2>
      <p>Atuamos também em despachantaria e assessoria nos mais diversos ramos.</p>
    </div>
    <ul class="caracs servicos-grid reveal">
      <li>Assistência jurídica</li>
      <li>Avaliações para processos judiciais</li>
      <li>Legalização (da planta ao habite-se)</li>
      <li>Averbações</li>
      <li>Registros</li>
      <li>Desmembramentos</li>
      <li>Fracionamentos</li>
      <li>Escrituras</li>
      <li>Procurações</li>
      <li>Testamentos</li>
    </ul>
  </div>
</section>

<section class="section tight">
  <div class="shell">
    <div class="section-head center reveal">
      <span class="eyebrow">Como trabalhamos</span>
      <h2>O que você pode esperar</h2>
    </div>
    <div class="valores">
      <div class="valor-card reveal">
        <div class="ico"><?php echo icone('chat'); ?></div>
        <h3>Atendimento próximo</h3>
        <p>Você fala com gente de verdade pelo WhatsApp, sem robô e sem enrolação, do primeiro "oi" até as chaves.</p>
      </div>
      <div class="valor-card reveal">
        <div class="ico"><?php echo icone('shield'); ?></div>
        <h3>Segurança e transparência</h3>
        <p>Documentação verificada, contratos claros e cada passo explicado. Sem surpresas no meio do caminho.</p>
      </div>
      <div class="valor-card reveal">
        <div class="ico"><?php echo icone('key'); ?></div>
        <h3>Do anúncio às chaves</h3>
        <p>Avaliamos, divulgamos e negociamos o seu imóvel — e ajudamos você a encontrar o próximo lar ou ponto comercial.</p>
      </div>
    </div>
  </div>
</section>

<section class="section tight">
  <div class="shell">
    <div class="cta-band reveal">
      <div>
        <span class="eyebrow">Vamos conversar?</span>
        <h2>Conte o que você procura.</h2>
        <p>Comprar, vender ou alugar — a gente ajuda a encontrar o melhor caminho.</p>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a class="btn btn-primary btn-lg" href="<?php echo e(wa_link('Olá! Vim pelo site da ' . SITE_NOME . ' e gostaria de conversar.')); ?>" target="_blank" rel="noopener">WhatsApp</a>
        <a class="btn btn-ghost btn-lg" style="border-color:rgba(255,255,255,.3);color:#fff" href="contato.php">Página de contato</a>
      </div>
    </div>
  </div>
</section>

<?php site_foot(); ?>
