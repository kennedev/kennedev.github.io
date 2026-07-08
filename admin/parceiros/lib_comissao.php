<?php
/* =========================================================================
   lib_comissao.php — cálculos de comissão dos clientes fechados.
   Tudo é derivado das colunas + da data de hoje; nada é gravado redundante.
   Usado por clientes.php (admin e parceiro).
   ========================================================================= */

/** Formata um número como moeda BRL (ex.: 1234.5 → "R$ 1.234,50"). */
function brl($v) {
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}

/**
 * Recebe a linha do cliente (com percentuais padrão do parceiro já mesclados
 * em `pct_parceiro_*`) e devolve todos os números da comissão + o contador.
 *
 * Espera as chaves: valor_projeto, valor_recorrencia, percentual_projeto,
 * percentual_recorrencia (override, pode ser null), pct_parceiro_projeto,
 * pct_parceiro_recorrencia, prazo_comissao_meses, primeira_recorrencia.
 */
function calcular_comissao(array $c) {
    // Percentual efetivo: override do cliente quando definido, senão o do parceiro.
    $pctProjeto = $c['percentual_projeto'] !== null
        ? (float) $c['percentual_projeto'] : (float) ($c['pct_parceiro_projeto'] ?? 0);
    $pctRecorr = $c['percentual_recorrencia'] !== null
        ? (float) $c['percentual_recorrencia'] : (float) ($c['pct_parceiro_recorrencia'] ?? 0);

    $prazo = max(0, (int) $c['prazo_comissao_meses']);
    $valorProjeto = (float) $c['valor_projeto'];
    $valorRecorr  = (float) $c['valor_recorrencia'];

    $comissaoProjeto = round($valorProjeto * $pctProjeto / 100, 2);
    $comissaoMensal  = round($valorRecorr * $pctRecorr / 100, 2);

    // Sem data de início (cliente pendente de aprovação): o contrato ainda tem
    // valor total conhecido, mas não há como contar meses decorridos nem data final.
    $inicio = $c['primeira_recorrencia'] ?? null;
    if (!$inicio) {
        $ultima = null;
        $decorridos = 0;
        $restantes = $prazo;
    } else {
        $ultima = data_mais_meses($inicio, $prazo);
        $decorridos = min($prazo, max(0, meses_entre($inicio, date('Y-m-d'))));
        $restantes  = max(0, $prazo - $decorridos);
    }

    return array(
        'pct_projeto'          => $pctProjeto,
        'pct_recorrencia'      => $pctRecorr,
        'comissao_projeto'     => $comissaoProjeto,
        'comissao_mensal'      => $comissaoMensal,
        'recorrencia_total'    => round($comissaoMensal * $prazo, 2),
        'recorrencia_recebida' => round($comissaoMensal * $decorridos, 2),
        'recorrencia_restante' => round($comissaoMensal * $restantes, 2),
        // Ganho total do parceiro no cliente: projeto (uma vez) + toda a recorrência.
        'total_contrato'       => round($comissaoProjeto + $comissaoMensal * $prazo, 2),
        'prazo'                => $prazo,
        'meses_decorridos'     => $decorridos,
        'meses_restantes'      => $restantes,
        'primeira_recorrencia' => $inicio,
        'ultima_recorrencia'   => $ultima,
    );
}

/** Soma $meses meses a uma data 'Y-m-d'. Retorna 'Y-m-d'. */
function data_mais_meses($data, $meses) {
    $ts = strtotime($data . ' +' . ((int) $meses) . ' months');
    return $ts ? date('Y-m-d', $ts) : $data;
}

/**
 * Nº de meses "cheios" decorridos entre duas datas 'Y-m-d' (>= 0).
 * Conta um mês só quando o dia do mês corrente já alcançou o dia de início.
 */
function meses_entre($de, $ate) {
    $d1 = date_create($de);
    $d2 = date_create($ate);
    if (!$d1 || !$d2 || $d2 < $d1) return 0;
    $diff = date_diff($d1, $d2);
    return $diff->y * 12 + $diff->m;
}

/** dd/mm/aaaa a partir de 'Y-m-d' (ou '—' se vazio). */
function data_br($ymd) {
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : $ymd;
}
