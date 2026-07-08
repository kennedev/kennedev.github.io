<?php
/* =========================================================================
   api.php — API JSON do módulo de propostas (consumida por fetch no admin).
   Login obrigatório; mutações exigem CSRF via header X-CSRF-Token.
   Reads:     GET  ?op=list[&status=&q=]  |  ?op=get&id=
   Mutations: POST {op:create|update|updateStatus|addComment|duplicate|delete, ...}
   ========================================================================= */
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/http.php';

sessao();
if (!admin_id()) {
    json_out(401, array('ok' => false, 'erro' => 'nao_autenticado'));
}

const STATUSES = array('rascunho', 'emitida', 'enviada', 'respondida', 'aceita', 'rejeitada');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ── Reads ──────────────────────────────────────────────────────────────────
if ($metodo === 'GET') {
    $op = $_GET['op'] ?? '';

    if ($op === 'list') {
        $where = array(); $params = array();
        // Parceiro só enxerga as próprias propostas; admin vê todas.
        if (!eh_admin()) { $where[] = 'criado_por = ?'; $params[] = (int) admin_id(); }
        $status = $_GET['status'] ?? '';
        if (in_array($status, STATUSES, true)) { $where[] = 'status = ?'; $params[] = $status; }
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = 'empresa LIKE ?'; $params[] = '%' . $q . '%'; }

        $sql = 'SELECT id, empresa, segmento, emissao, valor_mensal, status, atualizado_em FROM propostas';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY criado_em DESC';

        $st = db()->prepare($sql);
        $st->execute($params);
        $out = array();
        foreach ($st->fetchAll() as $r) {
            $out[] = array(
                'id'           => (int) $r['id'],
                'empresa'      => $r['empresa'],
                'segmento'     => $r['segmento'],
                'emissao'      => $r['emissao'],
                'valorMensal'  => $r['valor_mensal'],
                'status'       => $r['status'],
                'atualizadoEm' => $r['atualizado_em'],
            );
        }
        json_out(200, array('ok' => true, 'propostas' => $out));
    }

    if ($op === 'get') {
        $p = carregar((int) ($_GET['id'] ?? 0));
        if (!$p || !dono_ok($p)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        json_out(200, array('ok' => true, 'proposta' => $p));
    }

    json_out(400, array('ok' => false, 'erro' => 'op_invalida'));
}

// ── Mutations (POST) ─────────────────────────────────────────────────────────
if ($metodo === 'POST') {
    $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $tok)) {
        json_out(400, array('ok' => false, 'erro' => 'csrf'));
    }

    $in = json_input();
    $op = $in['op'] ?? '';

    if ($op === 'create') {
        $d = sanitize_dados($in['dados'] ?? array());
        if ($d['empresa'] === '') json_out(422, array('ok' => false, 'erro' => 'empresa_obrigatoria'));
        $d['comentarios'] = array();
        $st = db()->prepare(
            'INSERT INTO propostas (empresa, segmento, emissao, valor_mensal, status, dados, criado_por) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute(array(
            $d['empresa'], $d['segmento'], data_ou_null($d['emissao']),
            $d['valorMensal'], $d['status'], enc($d), (int) admin_id(),
        ));
        json_out(200, array('ok' => true, 'id' => (int) db()->lastInsertId()));
    }

    if ($op === 'update') {
        $id = (int) ($in['id'] ?? 0);
        $atual = carregar($id);
        if (!$atual || !dono_ok($atual)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        $d = sanitize_dados($in['dados'] ?? array());
        if ($d['empresa'] === '') json_out(422, array('ok' => false, 'erro' => 'empresa_obrigatoria'));
        // Comentários são geridos por addComment — preserva os existentes.
        $d['comentarios'] = is_array($atual['comentarios'] ?? null) ? $atual['comentarios'] : array();
        $st = db()->prepare(
            'UPDATE propostas SET empresa=?, segmento=?, emissao=?, valor_mensal=?, status=?, dados=?, atualizado_em=NOW() WHERE id=?'
        );
        $st->execute(array(
            $d['empresa'], $d['segmento'], data_ou_null($d['emissao']),
            $d['valorMensal'], $d['status'], enc($d), $id,
        ));
        json_out(200, array('ok' => true));
    }

    if ($op === 'updateStatus') {
        $id = (int) ($in['id'] ?? 0);
        $status = $in['status'] ?? '';
        if (!in_array($status, STATUSES, true)) json_out(422, array('ok' => false, 'erro' => 'status_invalido'));
        $atual = carregar($id);
        if (!$atual || !dono_ok($atual)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        $atual['status'] = $status;
        $st = db()->prepare('UPDATE propostas SET status=?, dados=?, atualizado_em=NOW() WHERE id=?');
        $st->execute(array($status, enc($atual), $id));
        // Proposta aceita de um parceiro → gera cliente pendente na carteira dele.
        if ($status === 'aceita') gerar_cliente_pendente($atual);
        json_out(200, array('ok' => true));
    }

    if ($op === 'addComment') {
        $id = (int) ($in['id'] ?? 0);
        $texto = trim((string) ($in['texto'] ?? ''));
        if ($texto === '') json_out(422, array('ok' => false, 'erro' => 'texto_vazio'));
        $atual = carregar($id);
        if (!$atual || !dono_ok($atual)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        $c = array('data' => date('Y-m-d H:i:s'), 'texto' => mb_substr($texto, 0, 2000));
        if (!is_array($atual['comentarios'] ?? null)) $atual['comentarios'] = array();
        $atual['comentarios'][] = $c;
        $st = db()->prepare('UPDATE propostas SET dados=?, atualizado_em=NOW() WHERE id=?');
        $st->execute(array(enc($atual), $id));
        json_out(200, array('ok' => true, 'comentario' => $c));
    }

    if ($op === 'duplicate') {
        $id = (int) ($in['id'] ?? 0);
        $src = carregar($id);
        if (!$src || !dono_ok($src)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        $src['empresa']     = mb_substr(($src['empresa'] ?? '') . ' (cópia)', 0, 160);
        $src['status']      = 'rascunho';
        $src['comentarios'] = array();
        unset($src['id'], $src['criadoEm'], $src['atualizadoEm'], $src['criadoPor']);
        $st = db()->prepare(
            'INSERT INTO propostas (empresa, segmento, emissao, valor_mensal, status, dados, criado_por) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute(array(
            $src['empresa'], $src['segmento'] ?? '', data_ou_null($src['emissao'] ?? ''),
            (float) ($src['valorMensal'] ?? 0), 'rascunho', enc($src), (int) admin_id(),
        ));
        json_out(200, array('ok' => true, 'id' => (int) db()->lastInsertId()));
    }

    if ($op === 'delete') {
        $id = (int) ($in['id'] ?? 0);
        $alvo = carregar($id);
        if (!$alvo || !dono_ok($alvo)) json_out(404, array('ok' => false, 'erro' => 'nao_encontrada'));
        db()->prepare('DELETE FROM propostas WHERE id=?')->execute(array($id));
        json_out(200, array('ok' => true));
    }

    json_out(400, array('ok' => false, 'erro' => 'op_invalida'));
}

json_out(405, array('ok' => false, 'erro' => 'metodo_nao_permitido'));


// ── Helpers ──────────────────────────────────────────────────────────────────

/** Carrega uma proposta completa (colunas + JSON de dados) por id, ou null. */
function carregar($id) {
    if ($id <= 0) return null;
    $st = db()->prepare('SELECT * FROM propostas WHERE id = ?');
    $st->execute(array($id));
    $row = $st->fetch();
    if (!$row) return null;
    $dados = json_decode($row['dados'], true);
    if (!is_array($dados)) $dados = array();
    $dados['id']           = (int) $row['id'];
    $dados['status']       = $row['status'];        // coluna é a fonte da verdade
    $dados['criadoPor']    = $row['criado_por'] !== null ? (int) $row['criado_por'] : null;
    $dados['criadoEm']     = $row['criado_em'];
    $dados['atualizadoEm'] = $row['atualizado_em'];
    return $dados;
}

/** Parceiro só acessa as próprias propostas; admin acessa qualquer uma. */
function dono_ok($prop) {
    return eh_admin() || (int) ($prop['criadoPor'] ?? 0) === (int) admin_id();
}

/**
 * Ao aceitar uma proposta criada por um PARCEIRO, cria um cliente na carteira dele
 * com aprovacao='pendente' (o admin aprova depois e define a data de recorrência).
 * Não faz nada se a proposta é de admin/sem autor ou se já há cliente para ela.
 */
function gerar_cliente_pendente($proposta) {
    $autor = (int) ($proposta['criadoPor'] ?? 0);
    if ($autor <= 0) return;

    $st = db()->prepare('SELECT papel FROM admins WHERE id = ? LIMIT 1');
    $st->execute(array($autor));
    if ($st->fetchColumn() !== 'parceiro') return;   // só parceiro gera cliente

    $ck = db()->prepare('SELECT COUNT(*) FROM clientes_fechados WHERE proposta_id = ?');
    $ck->execute(array((int) $proposta['id']));
    if ((int) $ck->fetchColumn() > 0) return;         // já existe cliente para esta proposta

    db()->prepare(
        'INSERT INTO clientes_fechados
           (parceiro_id, proposta_id, empresa, valor_projeto, valor_recorrencia,
            prazo_comissao_meses, primeira_recorrencia, status, aprovacao)
         VALUES (?,?,?,?,?,?,NULL,?,?)'
    )->execute(array(
        $autor, (int) $proposta['id'], mb_substr((string) ($proposta['empresa'] ?? ''), 0, 160),
        (float) ($proposta['valorCriacao'] ?? 0), (float) ($proposta['valorMensal'] ?? 0),
        12, 'ativo', 'pendente',
    ));
}

function enc($d) {
    return json_encode($d, JSON_UNESCAPED_UNICODE);
}

/** Retorna a string se for uma data yyyy-mm-dd; senão NULL (para a coluna DATE). */
function data_ou_null($s) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $s) ? $s : null;
}

function _s($v, $max = 2000) {
    return mb_substr(trim((string) ($v ?? '')), 0, $max);
}

function _n($v) {
    return round((float) $v, 2);
}

function _arr_str($a, $maxItems = 100, $maxLen = 2000) {
    if (!is_array($a)) return array();
    $out = array();
    foreach ($a as $x) {
        if (is_string($x) || is_numeric($x)) $out[] = _s($x, $maxLen);
        if (count($out) >= $maxItems) break;
    }
    return $out;
}

function _arr_obj($a, $keys, $maxItems = 100) {
    if (!is_array($a)) return array();
    $out = array();
    foreach ($a as $x) {
        if (!is_array($x)) continue;
        $row = array();
        foreach ($keys as $k) $row[$k] = _s($x[$k] ?? '');
        $out[] = $row;
        if (count($out) >= $maxItems) break;
    }
    return $out;
}

/** Resolve validade tipo "15 dias" para uma data concreta a partir da emissão. */
function resolve_validade($val, $emissao) {
    if (preg_match('/^\s*(\d+)\s*dias?\s*$/i', $val, $m)) {
        $base = preg_match('/^\d{4}-\d{2}-\d{2}$/', $emissao) ? $emissao : date('Y-m-d');
        return date('Y-m-d', strtotime($base . ' +' . ((int) $m[1]) . ' days'));
    }
    return $val;
}

/** Normaliza/valida o payload do documento em um array limpo (whitelist de campos). */
function sanitize_dados($in) {
    if (!is_array($in)) $in = array();
    $d = array();
    $d['empresa']         = _s($in['empresa'] ?? '', 160);
    $d['segmento']        = _s($in['segmento'] ?? '', 120);
    $d['dominio']         = _s($in['dominio'] ?? '', 160);
    $d['cidadeUf']        = _s($in['cidadeUf'] ?? '', 80);
    $d['acResponsavel']   = _s($in['acResponsavel'] ?? '', 160);
    $d['contatoCliente']  = _s($in['contatoCliente'] ?? '', 160);
    $d['emissao']         = _s($in['emissao'] ?? '', 40);
    $d['validade']        = resolve_validade(_s($in['validade'] ?? '', 40), $d['emissao']);
    $d['tituloCapa']      = _s($in['tituloCapa'] ?? '', 300);
    $d['palavraDestaque'] = _s($in['palavraDestaque'] ?? '', 80);
    $d['subtitulo']       = _s($in['subtitulo'] ?? '', 1000);
    $d['valorCriacao']       = _n($in['valorCriacao'] ?? 0);
    $d['temDescontoCriacao'] = !empty($in['temDescontoCriacao']);
    $d['valorCriacaoCheio']  = _n($in['valorCriacaoCheio'] ?? 0);
    $d['rotuloCriacao']      = _s($in['rotuloCriacao'] ?? '', 120);
    $d['valorMensal']     = _n($in['valorMensal'] ?? 0);
    $d['temDesconto']     = !empty($in['temDesconto']);
    $d['valorMensalCheio']= _n($in['valorMensalCheio'] ?? 0);
    $d['rotuloCondicao']  = _s($in['rotuloCondicao'] ?? '', 120);
    $d['valorNovaCriacao']= _n($in['valorNovaCriacao'] ?? 0);
    $d['entregue']        = _arr_str($in['entregue'] ?? array());
    $d['objetivo']        = _arr_str($in['objetivo'] ?? array());
    $d['inclusoes']       = _arr_str($in['inclusoes'] ?? array());
    $d['notaAsterisco']   = _s($in['notaAsterisco'] ?? '', 1000);
    $d['novasCriacoes']   = _arr_obj($in['novasCriacoes'] ?? array(), array('descricao', 'cobranca'));
    $d['calloutNovasTitulo'] = _s($in['calloutNovasTitulo'] ?? '', 200);
    $d['calloutNovasTexto']  = _s($in['calloutNovasTexto'] ?? '', 1000);
    $d['beneficios']      = _arr_obj($in['beneficios'] ?? array(), array('titulo', 'texto'));
    $d['condicoes']       = _arr_obj($in['condicoes'] ?? array(), array('rotulo', 'valor'));
    $d['ctaTitulo']       = _s($in['ctaTitulo'] ?? '', 200);
    $d['ctaTexto']        = _s($in['ctaTexto'] ?? '', 1000);
    $d['contatoSite']     = _s($in['contatoSite'] ?? '', 120);
    $d['contatoEmail']    = _s($in['contatoEmail'] ?? '', 160);
    $status = $in['status'] ?? 'rascunho';
    $d['status'] = in_array($status, STATUSES, true) ? $status : 'rascunho';
    return $d;
}
