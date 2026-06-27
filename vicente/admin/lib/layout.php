<?php
require_once __DIR__ . '/auth.php';

function admin_leads_nao_lidos() {
    try {
        return (int) db()->query('SELECT COUNT(*) FROM leads WHERE lido = 0')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function layout_head($titulo, $logado = false) {
    $t = e($titulo);
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo "<title>{$t} · Vicente Imóveis Admin</title><style>" . layout_css() . '</style></head><body>';
    echo '<header class="top"><a class="brand" href="index.php">Vicente Imóveis<span>admin</span></a>';
    if ($logado) {
        $n = admin_leads_nao_lidos();
        $badge = $n > 0 ? ' <i class="badge">' . $n . '</i>' : '';
        echo '<nav>';
        echo '<a href="index.php">Início</a>';
        echo '<a href="imoveis.php">Imóveis</a>';
        echo '<a href="leads.php">Leads' . $badge . '</a>';
        echo '<a href="usuarios.php">Administradores</a>';
        echo '<span class="who">' . e(admin_nome()) . '</span>';
        echo '<a class="out" href="logout.php">Sair</a>';
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
body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#F4F7F5;color:#1F1D1A;line-height:1.5;-webkit-font-smoothing:antialiased}
a{color:inherit}
.top{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;padding:16px 28px;background:#fff;border-bottom:1px solid #E3E9E4;position:sticky;top:0;z-index:10}
.brand{font-weight:800;font-size:18px;color:#1F1D1A;text-decoration:none;letter-spacing:-.01em}
.brand span{color:#2D5F8A;margin-left:7px;font-weight:600}
nav{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
nav a{color:#6f675c;text-decoration:none;font-weight:600;font-size:14px;position:relative}
nav a:hover{color:#1F1D1A}
nav .who{color:#2D5F8A;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
nav .out{color:#b4543f}
.badge{font-style:normal;background:#2D5F8A;color:#fff;font-size:11px;font-weight:800;padding:1px 7px;border-radius:999px;margin-left:3px}
.wrap{max-width:1040px;margin:0 auto;padding:34px 28px 80px}
h1{font-size:26px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
h2{font-size:18px;font-weight:800;margin:8px 0 14px}
.sub{color:#6B7670;font-size:14px;margin-bottom:26px}
.card{background:#fff;border:1px solid #E3E9E4;border-radius:16px;padding:24px;margin-bottom:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.tile{display:block;background:#fff;border:1px solid #E3E9E4;border-radius:16px;padding:26px;text-decoration:none;color:#1F1D1A;transition:.15s}
.tile:hover{border-color:#2D5F8A;transform:translateY(-2px);box-shadow:0 12px 30px -18px rgba(31,29,26,.4)}
.tile b{font-size:17px;font-weight:800}
.tile p{color:#6B7670;font-size:13px;margin-top:4px}
label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.09em;color:#6B7670;font-weight:700;margin-bottom:6px}
input,select,textarea{width:100%;padding:11px 13px;background:#FBFDFB;border:1px solid #DDE6DE;border-radius:10px;color:#1F1D1A;font-size:14px;font-weight:500;font-family:inherit;outline:none}
textarea{min-height:120px;resize:vertical;line-height:1.5}
input:focus,select:focus,textarea:focus{border-color:#2D5F8A;box-shadow:0 0 0 3px rgba(45,95,138,.16)}
.row{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end}
.row>div{flex:1;min-width:150px;margin-bottom:14px}
.field{margin-bottom:14px}
.checks{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px}
.checks label{display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-weight:500;color:#1F1D1A;font-size:13px;background:#FBFDFB;border:1px solid #DDE6DE;border-radius:9px;padding:9px 11px;cursor:pointer;margin:0}
.checks input{width:auto}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;background:#2D5F8A;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;font-family:inherit;cursor:pointer;text-decoration:none;transition:.15s}
.btn:hover{background:#1E4365}
.btn.ghost{background:transparent;border:1px solid #DDE6DE;color:#5b544a}
.btn.ghost:hover{background:#fff;border-color:#2D5F8A}
.btn.sm{padding:7px 12px;font-size:12px;border-radius:8px}
.btn.danger{background:#c0492f}.btn.danger:hover{background:#9e3a24}
.btn.warn{background:#bd7d22}.btn.warn:hover{background:#9c6716}
.btn.ok{background:#3f8f54}.btn.ok:hover{background:#327343}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #E8EEE9;vertical-align:middle}
th{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#6B7670}
tr:hover td{background:#F6FAF7}
code{background:#F4F7F5;padding:3px 8px;border-radius:6px;font-size:13px;color:#1E4365;font-weight:700}
.tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.03em}
.tag.disponivel{background:#e3f1e6;color:#2f6b40}
.tag.reservado{background:#fbf0d8;color:#8a6516}
.tag.vendido,.tag.alugado{background:#e7e9ee;color:#4a5366}
.tag.inativo{background:#eceae5;color:#857d72}
.tag.destaque{background:#dde9f3;color:#1E4365}
.thumb{width:64px;height:48px;object-fit:cover;border-radius:7px;border:1px solid #E3E9E4;background:#E8EEE9}
.fotos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.foto-item{border:1px solid #E3E9E4;border-radius:12px;overflow:hidden;background:#fff}
.foto-item img{width:100%;height:120px;object-fit:cover;display:block}
.foto-item .meta{padding:9px}
.foto-item .meta .acoes{margin-top:8px}
.iscapa{outline:2px solid #2D5F8A;outline-offset:-2px}
.erro{background:#fae6e0;color:#9e3a24;padding:11px 14px;border-radius:10px;font-weight:600;font-size:14px;margin-bottom:16px}
.ok-msg{background:#e3f1e6;color:#2f6b40;padding:11px 14px;border-radius:10px;font-weight:600;font-size:14px;margin-bottom:16px}
.auth{max-width:380px;margin:7vh auto 0}
.acoes{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.muted{color:#6B7670;font-size:13px}
.bar{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px}
.pill-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.pill-tabs a{padding:7px 14px;border:1px solid #DDE6DE;border-radius:999px;text-decoration:none;font-size:13px;font-weight:600;color:#6f675c}
.pill-tabs a.on{background:#1F1D1A;color:#fff;border-color:#1F1D1A}
CSS;
}
