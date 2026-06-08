<?php
require_once __DIR__ . '/db.php';

function layout_head($titulo, $logado = false) {
    $t = e($titulo);
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo "<title>{$t} · Admin kennedev</title><style>" . layout_css() . '</style></head><body>';
    echo '<header class="top"><a class="brand" href="/admin/">kennedev<span>admin</span></a>';
    if ($logado) {
        echo '<nav>';
        echo '<a href="/admin/">Início</a>';
        echo '<a href="/admin/usuarios.php">Administradores</a>';
        echo '<span class="who">' . e(admin_nome()) . '</span>';
        echo '<a class="out" href="/admin/logout.php">Sair</a>';
        echo '</nav>';
    }
    echo '</header><main class="wrap">';
}

function layout_foot() {
    echo '</main></body></html>';
}

function layout_css() {
    return <<<CSS
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;line-height:1.5}
.top{display:flex;align-items:center;justify-content:space-between;padding:16px 28px;background:#020617;border-bottom:1px solid #1e293b}
.brand{font-weight:800;font-size:18px;color:#fff;text-decoration:none;letter-spacing:-.02em}
.brand span{color:#818cf8;margin-left:6px;font-weight:600}
nav{display:flex;align-items:center;gap:18px}
nav a{color:#94a3b8;text-decoration:none;font-weight:600;font-size:14px}
nav a:hover{color:#fff}
nav .who{color:#475569;font-size:13px}
nav .out{color:#f87171}
.wrap{max-width:980px;margin:0 auto;padding:32px 28px}
h1{font-size:24px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
.sub{color:#94a3b8;font-size:14px;margin-bottom:28px}
.card{background:#1e293b;border:1px solid #334155;border-radius:18px;padding:24px;margin-bottom:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.tile{display:block;background:#1e293b;border:1px solid #334155;border-radius:18px;padding:28px;text-decoration:none;color:#fff;transition:.15s}
.tile:hover{border-color:#6366f1;transform:translateY(-2px)}
.tile b{font-size:18px;font-weight:800}
.tile p{color:#94a3b8;font-size:13px;margin-top:4px}
label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-weight:700;margin-bottom:6px}
input,select{width:100%;padding:11px 14px;background:#0f172a;border:1px solid #334155;border-radius:11px;color:#fff;font-size:14px;font-weight:600;outline:none}
input:focus,select:focus{border-color:#6366f1}
.row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.row>div{flex:1;min-width:160px}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;background:#6366f1;color:#fff;border:none;border-radius:11px;font-weight:800;font-size:14px;cursor:pointer;text-decoration:none}
.btn:hover{background:#4f46e5}
.btn.ghost{background:transparent;border:1px solid #334155;color:#cbd5e1}
.btn.sm{padding:7px 12px;font-size:12px}
.btn.danger{background:#dc2626}.btn.danger:hover{background:#b91c1c}
.btn.warn{background:#d97706}.btn.warn:hover{background:#b45309}
.btn.ok{background:#16a34a}.btn.ok:hover{background:#15803d}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #334155}
th{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8}
code{background:#0f172a;padding:3px 8px;border-radius:6px;font-size:13px;color:#a5b4fc;font-weight:700}
.tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase}
.tag.ativa{background:#14532d;color:#86efac}
.tag.pendente{background:#78350f;color:#fcd34d}
.tag.inativa{background:#7f1d1d;color:#fca5a5}
.erro{background:#7f1d1d;color:#fecaca;padding:11px 14px;border-radius:11px;font-weight:600;font-size:14px;margin-bottom:16px}
.ok-msg{background:#14532d;color:#bbf7d0;padding:11px 14px;border-radius:11px;font-weight:600;font-size:14px;margin-bottom:16px}
.auth{max-width:380px;margin:8vh auto 0}
.acoes{display:flex;gap:6px;flex-wrap:wrap}
CSS;
}
