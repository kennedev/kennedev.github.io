-- Esquema do admin + licenças. Importe via phpMyAdmin no banco u493566980_kennedev_db.

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
