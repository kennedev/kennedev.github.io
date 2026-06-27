<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/img.php';
require_once __DIR__ . '/../lib/publico.php'; // helpers: foto_url, preco_fmt, tipo_label, etc.
exigir_login();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($acao === 'destaque' && $id) {
        db()->prepare('UPDATE imoveis SET destaque = 1 - destaque WHERE id = ?')->execute(array($id));
        $msg = 'Destaque atualizado.';
    } elseif ($acao === 'excluir' && $id) {
        $fotos = db()->prepare('SELECT arquivo FROM imovel_fotos WHERE imovel_id = ?');
        $fotos->execute(array($id));
        db()->prepare('DELETE FROM imoveis WHERE id = ?')->execute(array($id)); // fotos caem por cascade
        img_remover_pasta(__DIR__ . '/../uploads/imoveis/' . $id);
        $msg = 'Imóvel excluído.';
    }
}

$q = trim($_GET['q'] ?? '');
$statusF = trim($_GET['status'] ?? '');
$where = array();
$params = array();
if ($q !== '') {
    $where[] = '(titulo LIKE ? OR referencia LIKE ? OR bairro LIKE ?)';
    $t = '%' . $q . '%';
    array_push($params, $t, $t, $t);
}
if ($statusF !== '') { $where[] = 'status = ?'; $params[] = $statusF; }
$wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = 'SELECT i.*, '
     . '(SELECT arquivo FROM imovel_fotos f WHERE f.imovel_id=i.id ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS capa_arquivo, '
     . '(SELECT COUNT(*) FROM imovel_fotos f WHERE f.imovel_id=i.id) AS n_fotos '
     . "FROM imoveis i $wsql ORDER BY i.atualizado_em DESC";
$st = db()->prepare($sql);
$st->execute($params);
$imoveis = $st->fetchAll();

layout_head('Imóveis', true);
?>
<div class="bar">
  <div><h1>Imóveis</h1><p class="sub" style="margin:0">Cadastre, edite e destaque os anúncios do site.</p></div>
  <a class="btn" href="imovel-editar.php">+ Novo imóvel</a>
</div>

<?php if ($msg): ?><div class="ok-msg"><?php echo e($msg); ?></div><?php endif; ?>

<form method="get" class="card" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
  <div style="flex:2;min-width:200px;margin:0"><label>Buscar</label><input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Título, referência ou bairro"></div>
  <div style="flex:1;min-width:150px;margin:0"><label>Status</label>
    <select name="status">
      <option value="">Todos</option>
      <?php foreach (array('disponivel','reservado','vendido','alugado','inativo') as $s): ?>
        <option value="<?php echo $s; ?>" <?php if ($statusF === $s) echo 'selected'; ?>><?php echo e(status_label($s)); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn ghost">Filtrar</button>
</form>

<div class="card">
<?php if ($imoveis): ?>
  <table>
    <thead><tr><th>Imóvel</th><th>Finalidade</th><th>Preço</th><th>Fotos</th><th>Status</th><th>Destaque</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($imoveis as $im): ?>
      <tr>
        <td>
          <div style="display:flex;gap:10px;align-items:center">
            <img class="thumb" src="<?php echo e(foto_url($im['id'], $im['capa_arquivo'], true, '../')); ?>" alt="">
            <div>
              <div style="font-weight:700"><?php echo e($im['titulo']); ?></div>
              <div class="muted"><code><?php echo e($im['referencia']); ?></code> · <?php echo e(tipo_label($im['tipo'])); ?> · <?php echo e(trim(($im['bairro'] ?? '') . ' ' . ($im['cidade'] ?? ''))); ?></div>
            </div>
          </div>
        </td>
        <td><?php echo e(finalidade_tag($im['finalidade'])); ?></td>
        <td><?php echo e(preco_fmt($im['preco'])); ?></td>
        <td><?php echo (int) $im['n_fotos']; ?></td>
        <td><span class="tag <?php echo e($im['status']); ?>"><?php echo e(status_label($im['status'])); ?></span></td>
        <td>
          <form method="post" style="margin:0">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="destaque"><input type="hidden" name="id" value="<?php echo (int) $im['id']; ?>">
            <button class="btn sm <?php echo $im['destaque'] ? 'warn' : 'ghost'; ?>"><?php echo $im['destaque'] ? '★ Em destaque' : '☆ Destacar'; ?></button>
          </form>
        </td>
        <td>
          <div class="acoes">
            <a class="btn sm ghost" href="imovel-editar.php?id=<?php echo (int) $im['id']; ?>">Editar</a>
            <a class="btn sm ghost" href="../imovel.php?ref=<?php echo urlencode($im['referencia']); ?>" target="_blank">Ver</a>
            <form method="post" style="margin:0" onsubmit="return confirm('Excluir o imóvel <?php echo e($im['referencia']); ?>? Esta ação não pode ser desfeita.')">
              <?php echo campo_csrf(); ?>
              <input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?php echo (int) $im['id']; ?>">
              <button class="btn sm danger">Excluir</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="muted">Nenhum imóvel encontrado. <a href="imovel-editar.php">Cadastrar o primeiro</a>.</p>
<?php endif; ?>
</div>

<?php layout_foot(); ?>
