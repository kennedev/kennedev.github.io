-- Esquema do admin + licenças. Importe via phpMyAdmin no banco u481523548_kennedev_db.

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
  ativo      TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Propostas comerciais geradas pelo módulo /admin/proposta/.
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
  criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
