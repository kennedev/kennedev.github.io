<?php
// POST /api/verificar.php  { chave, fingerprint }
// Endpoint que o app PDV consulta. Compartilha config/banco com o /admin.
// Resposta sempre 200 com { ok: bool, motivo: string, dias_restantes?: int }.

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../admin/lib/db.php';
require_once __DIR__ . '/../admin/lib/http.php';

if (!defined('DIAS_PENDENTE')) {
    define('DIAS_PENDENTE', 5);
}

cors();
require_post();

$in    = json_input();
$chave = trim($in['chave'] ?? '');
$fp    = trim($in['fingerprint'] ?? '');

if ($chave === '' || $fp === '') {
    json_out(400, array('ok' => false, 'motivo' => 'dados_incompletos'));
}

try {
    $st = db()->prepare('SELECT * FROM licencas WHERE chave = ? LIMIT 1');
    $st->execute(array($chave));
    $lic = $st->fetch();

    if (!$lic) {
        json_out(200, array('ok' => false, 'motivo' => 'invalida'));
    }
    if ($lic['status'] === 'inativa') {
        json_out(200, array('ok' => false, 'motivo' => 'inativa'));
    }

    // Vínculo de máquina (vale para 'ativa' e 'pendente').
    if ($lic['fingerprint'] === null) {
        db()->prepare('UPDATE licencas SET fingerprint = ?, ativado_em = NOW() WHERE id = ?')
            ->execute(array($fp, $lic['id']));
    } elseif ($lic['fingerprint'] !== $fp) {
        json_out(200, array('ok' => false, 'motivo' => 'outra_maquina'));
    }

    // 'pendente': contagem regressiva cronometrada AQUI (servidor).
    if ($lic['status'] === 'pendente') {
        $desde = $lic['pendente_desde'];
        if ($desde === null) {
            db()->prepare('UPDATE licencas SET pendente_desde = NOW() WHERE id = ?')
                ->execute(array($lic['id']));
            $desde = 'now';
        }
        $passados  = (int) floor((time() - strtotime($desde)) / 86400);
        $restantes = DIAS_PENDENTE - $passados;
        if ($restantes <= 0) {
            json_out(200, array('ok' => false, 'motivo' => 'bloqueada'));
        }
        json_out(200, array('ok' => true, 'motivo' => 'pendente', 'dias_restantes' => $restantes));
    }

    json_out(200, array('ok' => true, 'motivo' => 'ativa'));
} catch (Exception $e) {
    json_out(500, array('ok' => false, 'motivo' => 'erro_interno'));
}
