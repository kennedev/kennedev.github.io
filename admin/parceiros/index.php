<?php
/* Landing da área comercial (parceiros).
   Admin: gerencia parceiros, propostas e clientes fechados.
   Parceiro: acessa o gerador de propostas e a própria carteira. */
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';
exigir_login();

layout_head('Parceiros', true);

if (eh_admin()) {
    echo '<h1>Parceiros</h1>';
    echo '<p class="sub">Área comercial: propostas, parceiros e carteira de clientes fechados.</p>';
    echo '<div class="grid">';
    echo '<a class="tile" href="/admin/parceiros/gerenciar.php"><b>Gerenciar parceiros</b><p>Cadastre parceiros e defina os percentuais de comissão</p></a>';
    echo '<a class="tile" href="/admin/parceiros/clientes.php"><b>Clientes fechados</b><p>Cadastre clientes, valores, prazo e acompanhe comissões</p></a>';
    echo '<a class="tile" href="/admin/parceiros/proposta/"><b>Gerador de Proposta</b><p>Crie e gerencie propostas comerciais</p></a>';
    echo '</div>';
} else {
    echo '<h1>Bem-vindo, ' . e(admin_nome()) . '</h1>';
    echo '<p class="sub">Sua área comercial.</p>';
    echo '<div class="grid">';
    echo '<a class="tile" href="/admin/parceiros/proposta/"><b>Gerador de Proposta</b><p>Crie e acompanhe suas propostas</p></a>';
    echo '<a class="tile" href="/admin/parceiros/clientes.php"><b>Minha carteira</b><p>Seus clientes fechados e comissões a receber</p></a>';
    echo '</div>';
}

layout_foot();
