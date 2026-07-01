# Admin kennedev (PHP + MySQL)

Painel administrativo servido pela Hostinger em `kennedev.com.br/admin`.
Hoje administra as **licenças do PDV**; o menu cresce conforme novos produtos.

```
admin/                      → login + menu + gestão de admins
admin/pdv/                  → criar chaves, vincular cliente, ligar/desligar
admin/proposta/             → gerar e gerenciar propostas comerciais (PDF)
api/verificar.php           → endpoint consultado pelo app PDV
```

## Deploy

1. PHP 8.x no hPanel (padrão da Hostinger).
2. Suba `admin/` e `api/` para o `public_html` (ficam em `kennedev.com.br/admin` e `kennedev.com.br/api`).
3. `cp admin/config.sample.php admin/config.php` e preencha a senha do banco.
   (`config.php` está no `.gitignore` — só existe na Hostinger.)
4. Importe `admin/schema.sql` no banco `u481523548_kennedev_db` (phpMyAdmin).
   Já instalado? Rode de novo — as tabelas usam `CREATE TABLE IF NOT EXISTS`, então
   reimportar só cria o que falta (ex.: a tabela `propostas` do módulo de propostas).
5. Acesse `kennedev.com.br/admin` → tela de **primeiro acesso** cria o admin principal.

## Uso

- **Início** → botão **PDV**.
- **PDV** → "Gerar chave" cria uma licença para um cliente; copie a chave e envie.
  - **Ativar / Pendência / Bloquear** controlam o acesso do cliente.
  - **Pendência** dispara, no app do cliente, o aviso de atraso com contagem de
    `DIAS_PENDENTE` dias (definido no `config.php`) antes do bloqueio.
  - **Liberar PC** desvincula a máquina (use quando o cliente troca de computador).
- **Administradores** → adiciona/desativa quem acessa o painel.
- **Gerador de Proposta** → cria propostas comerciais em PDF (layout fixo Kennedev).
  - **+ Nova proposta** abre o builder: preencha empresa e valores (o resto já vem
    com o texto padrão) e acompanhe o **preview ao vivo** ao lado.
  - **Salvar e abrir PDF** leva à view de impressão → botão **Baixar PDF** usa a
    impressão do navegador (Ctrl+P → "Salvar como PDF", com gráficos de fundo ligados).
  - Na lista: **status** editável inline (rascunho → … → aceita/rejeitada), **Notas**
    (comentários com data/hora), **Editar**, **Duplicar**, **Excluir**, filtro por
    status e busca por empresa.
  - Persistência na tabela `propostas`; o documento completo fica no campo JSON `dados`.

## Segurança

- Senhas de admin: hash (`password_hash`), nunca em texto.
- Sessão + token CSRF nas ações.
- `config.php` fora do Git; o repositório é público, o código pode ser visto, mas
  não dá acesso sem as credenciais (que só existem na Hostinger).
