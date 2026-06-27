<?php
require_once __DIR__ . '/lib/publico.php';
garantir_db();

$leadOk = false;
$leadErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['website'])) {
        $leadOk = true;
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $tel  = trim($_POST['telefone'] ?? '');
        if ($nome === '' || $tel === '') {
            $leadErro = 'Informe ao menos nome e telefone.';
        } else {
            registrar_lead($nome, $tel, trim($_POST['email'] ?? ''), trim($_POST['mensagem'] ?? ''), null, 'contato');
            $leadOk = true;
        }
    }
}

site_head('Contato', array('canonical' => SITE_URL . '/contato.php',
    'desc' => 'Fale com a Imobiliaria Negócios Imobiliários. WhatsApp, telefone e formulário de contato.'));
?>

<div class="shell page-head">
  <span class="eyebrow">Fale com a gente</span>
  <h1>Contato</h1>
</div>

<section class="section" style="padding-top:30px">
  <div class="shell contato-grid">
    <div class="contato-info reveal">
      <p class="lead" style="margin-bottom:14px">Estamos por aqui para ajudar você a comprar, vender ou alugar. Escolha o canal que preferir.</p>

      <a class="linha" href="<?php echo e(wa_link('Olá! Vim pelo site da ' . SITE_NOME . '.')); ?>" target="_blank" rel="noopener">
        <?php echo icone('chat'); ?><span><b>WhatsApp</b><span><?php echo e(SITE_WHATSAPP_FMT); ?> · resposta rápida</span></span>
      </a>
      <a class="linha" href="tel:+<?php echo e(SITE_TEL_FIXO_RAW); ?>">
        <?php echo icone('phone'); ?><span><b>Telefone</b><span><?php echo e(SITE_TEL_FIXO); ?></span></span>
      </a>
      <a class="linha" href="mailto:<?php echo e(SITE_EMAIL); ?>">
        <?php echo icone('mail'); ?><span><b>E-mail</b><span><?php echo e(SITE_EMAIL); ?></span></span>
      </a>
      <div class="linha">
        <?php echo icone('pino'); ?><span><b>Atendimento</b><span><?php echo e(SITE_CIDADE); ?> e região · <?php echo e(SITE_CRECI); ?></span></span>
      </div>
      <div class="linha" style="border-bottom:0">
        <?php echo icone('clock'); ?><span><b>Horário</b><span>Seg. a sex. 9h–18h · sáb. 9h–13h</span></span>
      </div>
    </div>

    <div class="reveal">
      <div class="price-card" style="box-shadow:var(--shadow)">
        <h2 style="font-size:24px;margin-bottom:6px">Envie uma mensagem</h2>
        <p class="muted" style="margin-bottom:18px;color:var(--muted)">Retornamos o mais rápido possível.</p>
        <?php if ($leadOk): ?>
          <div class="aviso ok">Mensagem enviada! Em breve entraremos em contato. Obrigado.</div>
        <?php else: ?>
          <?php if ($leadErro): ?><div class="aviso erro"><?php echo e($leadErro); ?></div><?php endif; ?>
          <form method="post" class="form-grid" action="contato.php">
            <div class="hp"><label>Não preencha<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="field"><label>Nome*</label><input type="text" name="nome" required value="<?php echo e($_POST['nome'] ?? ''); ?>"></div>
            <div class="field"><label>Telefone / WhatsApp*</label><input type="text" name="telefone" required value="<?php echo e($_POST['telefone'] ?? ''); ?>"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>"></div>
            <div class="field"><label>Como podemos ajudar?</label><textarea name="mensagem" placeholder="Quero comprar / vender / alugar..."><?php echo e($_POST['mensagem'] ?? ''); ?></textarea></div>
            <button class="btn btn-primary btn-block" type="submit">Enviar mensagem</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php site_foot(); ?>
