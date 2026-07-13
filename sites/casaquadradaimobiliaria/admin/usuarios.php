<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
exigir_login();

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $eu = (int) admin_id();

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $senha = $_POST['senha'] ?? '';
        if ($nome === '' || strlen($senha) < 6) {
            $erro = 'Informe nome e senha de ao menos 6 caracteres.';
        } else {
            $chk = db()->prepare('SELECT id FROM admins WHERE nome = ?');
            $chk->execute(array($nome));
            if ($chk->fetch()) $erro = 'Já existe um administrador com esse nome.';
            else { criar_admin($nome, $senha); flash_set('Administrador criado.'); header('Location: usuarios.php'); exit; }
        }
    } elseif ($acao === 'desativar' && $id) {
        if ($id === $eu) $erro = 'Você não pode desativar a própria conta.';
        else { db()->prepare('UPDATE admins SET ativo = 0 WHERE id = ?')->execute(array($id)); flash_set('Acesso desativado.'); header('Location: usuarios.php'); exit; }
    } elseif ($acao === 'ativar' && $id) {
        db()->prepare('UPDATE admins SET ativo = 1 WHERE id = ?')->execute(array($id)); flash_set('Acesso reativado.'); header('Location: usuarios.php'); exit;
    } elseif ($acao === 'excluir' && $id) {
        $totAtivos = (int) db()->query('SELECT COUNT(*) FROM admins WHERE ativo = 1')->fetchColumn();
        if ($id === $eu) $erro = 'Você não pode excluir a própria conta.';
        elseif ($totAtivos <= 1) $erro = 'Deve existir ao menos um administrador ativo.';
        else { db()->prepare('DELETE FROM admins WHERE id = ?')->execute(array($id)); flash_set('Administrador excluído.'); header('Location: usuarios.php'); exit; }
    }
}

$admins = db()->query('SELECT * FROM admins ORDER BY criado_em ASC')->fetchAll();
$flash = flash_get();
layout_head('Administradores', true);
?>
<div class="bar">
  <div><h1>Administradores</h1><p class="sub" style="margin:0">Quem pode acessar este painel.</p></div>
</div>

<?php if ($flash): ?><div class="ok-msg"><?php echo e($flash['texto']); ?></div><?php endif; ?>
<?php if ($erro): ?><div class="erro"><?php echo e($erro); ?></div><?php endif; ?>

<div class="card">
  <h2>Novo administrador</h2>
  <form method="post" class="row" style="margin:0">
    <?php echo campo_csrf(); ?>
    <input type="hidden" name="acao" value="criar">
    <div><label>Nome</label><input name="nome" autocomplete="off" required></div>
    <div><label>Senha (mín. 6)</label><input type="password" name="senha" required></div>
    <div style="flex:0"><label>&nbsp;</label><button class="btn">Adicionar</button></div>
  </form>
</div>

<div class="card">
  <table>
    <thead><tr><th>Nome</th><th>Criado em</th><th>Status</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($admins as $a): $souEu = (int) $a['id'] === (int) admin_id(); ?>
      <tr>
        <td style="font-weight:700"><?php echo e($a['nome']); ?><?php echo $souEu ? ' <span class="muted">(você)</span>' : ''; ?></td>
        <td class="muted"><?php echo e(date('d/m/Y', strtotime($a['criado_em']))); ?></td>
        <td><span class="tag <?php echo $a['ativo'] ? 'disponivel' : 'inativo'; ?>"><?php echo $a['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
        <td>
          <div class="acoes">
            <?php if (!$souEu): ?>
              <form method="post" style="margin:0">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="<?php echo $a['ativo'] ? 'desativar' : 'ativar'; ?>"><input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                <button class="btn sm ghost"><?php echo $a['ativo'] ? 'Desativar' : 'Ativar'; ?></button>
              </form>
              <form method="post" style="margin:0" onsubmit="return confirm('Excluir o administrador <?php echo e($a['nome']); ?>?')">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                <button class="btn sm danger">Excluir</button>
              </form>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php layout_foot(); ?>
