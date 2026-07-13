<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        if ($acao === 'ler')        db()->prepare('UPDATE leads SET lido = 1 WHERE id = ?')->execute(array($id));
        elseif ($acao === 'nao_lido') db()->prepare('UPDATE leads SET lido = 0 WHERE id = ?')->execute(array($id));
        elseif ($acao === 'excluir')  db()->prepare('DELETE FROM leads WHERE id = ?')->execute(array($id));
    }
    flash_set('Pronto.');
    header('Location: leads.php' . (!empty($_GET['f']) ? '?f=' . urlencode($_GET['f']) : '')); exit;
}

$filtro = $_GET['f'] ?? '';
$wsql = $filtro === 'nao_lidos' ? 'WHERE l.lido = 0' : '';
$leads = db()->query(
    'SELECT l.*, i.referencia AS ref FROM leads l LEFT JOIN imoveis i ON i.id = l.imovel_id '
    . $wsql . ' ORDER BY l.lido ASC, l.criado_em DESC'
)->fetchAll();

$flash = flash_get();
layout_head('Leads', true);

function wa_tel($t) {
    $d = preg_replace('/\D/', '', (string) $t);
    if ($d === '') return '';
    if (strlen($d) <= 11) $d = '55' . $d;
    return 'https://wa.me/' . $d;
}
?>
<div class="bar">
  <div><h1>Leads</h1><p class="sub" style="margin:0">Contatos recebidos pelo site.</p></div>
</div>

<?php if ($flash): ?><div class="ok-msg"><?php echo e($flash['texto']); ?></div><?php endif; ?>

<div class="pill-tabs">
  <a href="leads.php" class="<?php echo $filtro === '' ? 'on' : ''; ?>">Todos</a>
  <a href="leads.php?f=nao_lidos" class="<?php echo $filtro === 'nao_lidos' ? 'on' : ''; ?>">Não lidos</a>
</div>

<div class="card">
<?php if ($leads): ?>
  <table>
    <thead><tr><th>Quando</th><th>Contato</th><th>Mensagem</th><th>Imóvel</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($leads as $l): ?>
      <tr style="<?php echo $l['lido'] ? 'opacity:.62' : ''; ?>">
        <td class="muted" style="white-space:nowrap"><?php echo e(date('d/m/y H:i', strtotime($l['criado_em']))); ?><?php echo $l['lido'] ? '' : ' <span class="tag destaque">novo</span>'; ?></td>
        <td>
          <div style="font-weight:700"><?php echo e($l['nome']); ?></div>
          <div class="muted">
            <a href="<?php echo e(wa_tel($l['telefone'])); ?>" target="_blank"><?php echo e($l['telefone']); ?></a>
            <?php if ($l['email']): ?> · <a href="mailto:<?php echo e($l['email']); ?>"><?php echo e($l['email']); ?></a><?php endif; ?>
          </div>
        </td>
        <td style="max-width:320px"><?php echo nl2br(e($l['mensagem'] ?: '—')); ?></td>
        <td><?php echo $l['ref'] ? '<a href="imovel-editar.php?id=' . (int) $l['imovel_id'] . '"><code>' . e($l['ref']) . '</code></a>' : '<span class="muted">' . e($l['origem']) . '</span>'; ?></td>
        <td>
          <div class="acoes">
            <form method="post" style="margin:0">
              <?php echo campo_csrf(); ?>
              <input type="hidden" name="acao" value="<?php echo $l['lido'] ? 'nao_lido' : 'ler'; ?>"><input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
              <button class="btn sm ghost"><?php echo $l['lido'] ? 'Marcar não lido' : 'Marcar lido'; ?></button>
            </form>
            <form method="post" style="margin:0" onsubmit="return confirm('Excluir este lead?')">
              <?php echo campo_csrf(); ?>
              <input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
              <button class="btn sm danger">Excluir</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="muted">Nenhum lead por aqui ainda.</p>
<?php endif; ?>
</div>

<?php layout_foot(); ?>
