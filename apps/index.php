<?php
require_once __DIR__ . '/lib.php';
$apps = apps_disponiveis();
?><!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#070b16">
<title>Downloads · Kennedev</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#070b16;color:#e2e8f0;line-height:1.55;min-height:100vh}
.top{display:flex;align-items:center;justify-content:space-between;padding:20px 28px;border-bottom:1px solid #1e293b;background:#020617}
.brand{font-weight:800;font-size:18px;color:#fff;text-decoration:none;letter-spacing:-.02em}
.brand span{color:#818cf8;margin-left:6px;font-weight:600}
.top a.back{color:#94a3b8;text-decoration:none;font-weight:600;font-size:14px}
.top a.back:hover{color:#fff}
.wrap{max-width:920px;margin:0 auto;padding:48px 28px}
h1{font-size:28px;font-weight:800;letter-spacing:-.02em;margin-bottom:8px}
.sub{color:#94a3b8;font-size:15px;margin-bottom:36px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.card{background:#0f172a;border:1px solid #1e293b;border-radius:18px;padding:24px;display:flex;flex-direction:column;gap:14px}
.card .icone{font-size:34px;line-height:1}
.card h2{font-size:19px;font-weight:800;letter-spacing:-.01em}
.card p{color:#94a3b8;font-size:14px;flex:1}
.meta{display:flex;gap:8px;flex-wrap:wrap;font-size:12px;color:#64748b}
.meta .pill{background:#020617;border:1px solid #1e293b;border-radius:999px;padding:3px 10px;font-weight:700;color:#a5b4fc}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 18px;background:#6366f1;color:#fff;border:none;border-radius:12px;font-weight:800;font-size:14px;cursor:pointer;text-decoration:none;transition:.15s}
.btn:hover{background:#4f46e5}
.btn[disabled],.btn.off{background:#1e293b;color:#64748b;pointer-events:none;cursor:default}
.vazio{color:#64748b;font-size:15px;background:#0f172a;border:1px dashed #1e293b;border-radius:18px;padding:40px;text-align:center}
</style>
</head>
<body>
<header class="top">
    <a class="brand" href="/">kennedev<span>apps</span></a>
    <a class="back" href="/">← Voltar ao site</a>
</header>
<main class="wrap">
    <h1>Downloads</h1>
    <p class="sub">Baixe a versão mais recente dos aplicativos da Kennedev.</p>
<?php if (!$apps): ?>
    <div class="vazio">Nenhum aplicativo disponível no momento.</div>
<?php else: ?>
    <div class="grid">
    <?php foreach ($apps as $app): $inst = $app['instalador']; ?>
        <div class="card">
            <div class="icone"><?= eh($app['icone']) ?></div>
            <h2><?= eh($app['nome']) ?></h2>
            <p><?= eh($app['descricao']) ?></p>
            <?php if ($inst): ?>
                <div class="meta">
                    <?php if ($inst['versao']): ?><span class="pill">v<?= eh($inst['versao']) ?></span><?php endif; ?>
                    <span class="pill"><?= eh(formatar_tamanho($inst['tamanho'])) ?></span>
                </div>
                <a class="btn" href="/apps/baixar.php?app=<?= urlencode($app['slug']) ?>">⬇ Baixar</a>
            <?php else: ?>
                <div class="meta"><span class="pill">Em breve</span></div>
                <span class="btn off">Indisponível</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</main>
</body>
</html>
