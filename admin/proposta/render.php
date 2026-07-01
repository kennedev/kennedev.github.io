<?php
/* render.php — view de impressão limpa (sem chrome do admin).
   Lê ?id=, injeta os dados salvos e renderiza o documento via template.js.
   Botão "Baixar PDF" chama window.print() → impressão vetorial fiel (A4). */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
exigir_login();
sessao();

$id = (int) ($_GET['id'] ?? 0);
$dados = null; $empresa = '';
if ($id > 0) {
    $st = db()->prepare('SELECT * FROM propostas WHERE id = ?');
    $st->execute(array($id));
    $row = $st->fetch();
    if ($row) {
        $dados = json_decode($row['dados'], true);
        if (!is_array($dados)) $dados = array();
        $dados['id']           = (int) $row['id'];
        $dados['status']       = $row['status'];
        $dados['criadoEm']     = $row['criado_em'];
        $dados['atualizadoEm'] = $row['atualizado_em'];
        $empresa = $dados['empresa'] ?? '';
    }
}
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?><!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proposta<?= $empresa ? ' · ' . e($empresa) : '' ?> · Kennedev</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/doc.css">
<style>
  /* Barra de utilidades — só na tela, nunca no PDF. */
  .toolbar{position:fixed;top:0;left:0;right:0;z-index:99;display:flex;gap:14px;align-items:center;
    padding:10px 18px;background:#0B0F1A;color:#AEB9CE;border-bottom:1px solid #1e293b;
    font-family:"Inter",system-ui,sans-serif;font-size:13px}
  .toolbar b{color:#fff}
  .toolbar .sp{flex:1}
  .toolbar a{color:#9FB4E8;text-decoration:none}
  .pbtn{background:linear-gradient(120deg,#7C6CF6,#4EA8FF);color:#fff;border:none;border-radius:10px;
    padding:9px 16px;font-weight:800;font-size:13px;cursor:pointer;font-family:"Sora",sans-serif}
  .pbtn[disabled]{opacity:.5;cursor:default}
  @media screen{ body{ padding-top:52px; background:#334155; } #doc{ margin:0 auto; width:210mm; background:#fff; } }
  @media print{ .toolbar{ display:none !important; } body{ padding-top:0; background:#fff; } #doc{ width:auto; } }
</style>
</head>
<body>
<?php if (!$dados): ?>
  <div class="toolbar"><b>Proposta não encontrada.</b><span class="sp"></span><a href="/admin/proposta/">← Voltar</a></div>
  <p style="font-family:sans-serif;padding:80px 24px;color:#334155">O id informado não existe. <a href="/admin/proposta/">Voltar à lista</a>.</p>
<?php else: ?>
  <div class="toolbar">
    <button class="pbtn" id="btnPrint" disabled>⬇ Baixar PDF</button>
    <span>No diálogo: mantenha <b>"Gráficos de segundo plano"</b> ligado e destino <b>"Salvar como PDF"</b>.</span>
    <span class="sp"></span>
    <a href="novo.php?id=<?= (int) $id ?>">Editar</a>
    <a href="/admin/proposta/">← Lista</a>
  </div>
  <div id="doc"></div>
  <script>window.PROPOSTA = <?= json_encode($dados, $jsonFlags) ?>;</script>
  <script src="assets/template.js"></script>
  <script>
    document.getElementById('doc').innerHTML = window.renderDoc(window.PROPOSTA);
    var btn = document.getElementById('btnPrint');
    btn.addEventListener('click', function () { window.print(); });
    // Libera o botão só quando as fontes carregarem (para o 1º PDF já sair certo).
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () { btn.disabled = false; });
      setTimeout(function () { btn.disabled = false; }, 2500); // fallback
    } else {
      btn.disabled = false;
    }
  </script>
<?php endif; ?>
</body>
</html>
