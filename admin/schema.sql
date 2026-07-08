-- Esquema do admin + licenças. Importe via phpMyAdmin no banco u481523548_kennedev_db.
-- Ordem importa: tabelas referenciadas por FOREIGN KEY vêm antes de quem as referencia.

CREATE TABLE IF NOT EXISTS licencas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chave          VARCHAR(40)  NOT NULL UNIQUE,
  cliente        VARCHAR(160) NOT NULL,
  status         ENUM('ativa','pendente','inativa') NOT NULL DEFAULT 'ativa',
  pendente_desde DATETIME     NULL,
  fingerprint    VARCHAR(128) NULL,
  ativado_em     DATETIME     NULL,
  criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(80)  NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  papel      ENUM('admin','parceiro') NOT NULL DEFAULT 'admin',
  ativo      TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Propostas comerciais geradas pelo módulo /admin/parceiros/proposta/.
-- Colunas estruturadas para listar/filtrar/ordenar; o documento completo
-- (listas, benefícios, condições, comentários) vive no JSON de `dados`.
CREATE TABLE IF NOT EXISTS propostas (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa       VARCHAR(160)  NOT NULL,
  segmento      VARCHAR(120)  NULL,
  emissao       DATE          NULL,
  valor_mensal  DECIMAL(10,2) NULL,
  status        ENUM('rascunho','emitida','enviada','respondida','aceita','rejeitada') NOT NULL DEFAULT 'rascunho',
  dados         LONGTEXT      NOT NULL,
  criado_por    INT UNSIGNED  NULL,   -- admins.id de quem criou (parceiro vê só as suas)
  criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalhe 1:1 do parceiro comercial: percentuais de comissão padrão.
-- O login/senha do parceiro vive em `admins` (papel = 'parceiro'); aqui
-- ficam só os dados comerciais dele.
CREATE TABLE IF NOT EXISTS parceiros (
  admin_id               INT UNSIGNED PRIMARY KEY,
  percentual_projeto     DECIMAL(5,2) NOT NULL DEFAULT 0,
  percentual_recorrencia DECIMAL(5,2) NOT NULL DEFAULT 0,
  criado_em              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clientes fechados por um parceiro. A comissão sobre a recorrência é paga
-- por `prazo_comissao_meses` (12/24/36, editável) a partir de `primeira_recorrencia`.
-- Percentuais NULL herdam os do parceiro; preenchidos, sobrescrevem por cliente.
CREATE TABLE IF NOT EXISTS clientes_fechados (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parceiro_id            INT UNSIGNED  NOT NULL,
  proposta_id            INT UNSIGNED  NULL,
  empresa                VARCHAR(160)  NOT NULL,
  valor_projeto          DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor_recorrencia      DECIMAL(10,2) NOT NULL DEFAULT 0,
  percentual_projeto     DECIMAL(5,2)  NULL,
  percentual_recorrencia DECIMAL(5,2)  NULL,
  prazo_comissao_meses   INT           NOT NULL DEFAULT 12,
  primeira_recorrencia   DATE          NULL,   -- só definida na aprovação (proposta não tem essa data)
  status                 ENUM('ativo','encerrado','cancelado') NOT NULL DEFAULT 'ativo',
  -- Fluxo de aprovação: manual (admin) já entra 'aprovado'; gerado de proposta aceita entra 'pendente'.
  aprovacao              ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'aprovado',
  criado_em              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parceiro_id) REFERENCES admins(id),
  FOREIGN KEY (proposta_id) REFERENCES propostas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Migração para bancos que já existem ──────────────────────────────────────
-- As tabelas acima usam IF NOT EXISTS, mas colunas novas em tabelas antigas não
-- são adicionadas por reimportar. Rode os ALTER abaixo UMA vez no phpMyAdmin.
-- (MySQL não tem "ADD COLUMN IF NOT EXISTS"; se a coluna já existir, ignore o erro.)
--
--   ALTER TABLE admins    ADD papel ENUM('admin','parceiro') NOT NULL DEFAULT 'admin';
--   ALTER TABLE propostas ADD criado_por INT UNSIGNED NULL;
--
-- Se você já tinha criado `clientes_fechados` na versão anterior, rode também:
--   ALTER TABLE clientes_fechados MODIFY primeira_recorrencia DATE NULL;
--   ALTER TABLE clientes_fechados ADD aprovacao ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'aprovado';
