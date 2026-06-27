<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();
site_head('Sobre', array('canonical' => SITE_URL . '/sobre.php',
    'desc' => 'Conheça a Imobiliaria Negócios Imobiliários: atendimento próximo e transparente na compra, venda e locação de imóveis.'));
?>

<div class="shell page-head">
  <span class="eyebrow">Quem somos</span>
  <h1>Imobiliaria Negócios Imobiliários</h1>
</div>

<section class="section" style="padding-top:30px">
  <div class="shell split">
    <div class="reveal">
      <img loading="lazy" src="https://images.unsplash.com/photo-1556912172-45b7abe8b7e1?auto=format&fit=crop&w=900&q=70" alt="Equipe Imobiliaria">
    </div>
    <div class="reveal">
      <p class="lead" style="margin-bottom:18px">A Imobiliaria é uma imobiliária de relacionamento: conhecemos a região, ouvimos quem compra e quem vende, e tratamos cada negócio com a atenção que ele merece.</p>
      <p style="color:var(--muted);margin-bottom:16px">Trabalhamos com casas, apartamentos, terrenos e imóveis comerciais — para venda e locação. Da avaliação inicial à assinatura do contrato, acompanhamos cada etapa para que você decida com segurança e tranquilidade.</p>
      <p style="color:var(--muted)">Atuamos de forma regularizada e transparente, <?php echo e(SITE_CRECI); ?>, com toda a documentação dos negócios conduzida de perto.</p>
    </div>
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
