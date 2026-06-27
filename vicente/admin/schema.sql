-- Vicente Corretor de Imóveis — esquema do banco.
-- Importe via phpMyAdmin no banco MySQL DEDICADO deste site (não use o mesmo do kennedev).
-- Charset utf8mb4. MySQL 5.7+ / MariaDB 10.2+.

SET NAMES utf8mb4;

-- ── Administradores do painel ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(80)  NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  ativo      TINYINT(1)   NOT NULL DEFAULT 1,
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Imóveis ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS imoveis (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  referencia       VARCHAR(20)  NOT NULL UNIQUE,            -- ex.: LI025
  titulo           VARCHAR(160) NOT NULL,
  finalidade       ENUM('venda','aluguel') NOT NULL DEFAULT 'venda',
  tipo             ENUM('casa','apartamento','sobrado','terreno','comercial','sala','galpao','chacara') NOT NULL DEFAULT 'casa',
  preco            DECIMAL(12,2) NULL,                      -- NULL = "sob consulta"
  condominio       DECIMAL(10,2) NULL,
  iptu             DECIMAL(10,2) NULL,
  descricao        TEXT         NULL,
  dormitorios      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  suites           TINYINT UNSIGNED NOT NULL DEFAULT 0,
  banheiros        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  vagas            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  area_util        DECIMAL(10,2) NULL,                      -- m²
  area_total       DECIMAL(10,2) NULL,                      -- m²
  cep              VARCHAR(12)  NULL,
  logradouro       VARCHAR(160) NULL,
  numero           VARCHAR(20)  NULL,
  bairro           VARCHAR(120) NULL,
  cidade           VARCHAR(120) NULL,
  uf               CHAR(2)      NULL,
  mostrar_endereco TINYINT(1)   NOT NULL DEFAULT 0,         -- 0 = esconde nº/logradouro no site
  latitude         DECIMAL(10,7) NULL,
  longitude        DECIMAL(10,7) NULL,
  caracteristicas  JSON          NULL,                      -- ["piscina","churrasqueira",...]
  status           ENUM('disponivel','reservado','vendido','alugado','inativo') NOT NULL DEFAULT 'disponivel',
  destaque         TINYINT(1)   NOT NULL DEFAULT 0,         -- aparece na home
  criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_busca (status, finalidade, tipo),
  INDEX idx_local (cidade, bairro),
  INDEX idx_preco (preco),
  INDEX idx_destaque (destaque)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Fotos dos imóveis ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS imovel_fotos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  imovel_id INT UNSIGNED NOT NULL,
  arquivo   VARCHAR(255) NOT NULL,                          -- nome do arquivo local OU URL http (seed)
  legenda   VARCHAR(160) NULL,
  ordem     INT          NOT NULL DEFAULT 0,
  capa      TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_imovel (imovel_id, capa, ordem),
  CONSTRAINT fk_foto_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Leads / contatos do site ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leads (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(120) NOT NULL,
  telefone  VARCHAR(40)  NOT NULL,
  email     VARCHAR(160) NULL,
  mensagem  TEXT         NULL,
  imovel_id INT UNSIGNED NULL,                              -- de qual imóvel partiu o contato
  origem    VARCHAR(40)  NOT NULL DEFAULT 'site',           -- imovel | contato | site
  lido      TINYINT(1)   NOT NULL DEFAULT 0,
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lido (lido, criado_em),
  CONSTRAINT fk_lead_imovel FOREIGN KEY (imovel_id) REFERENCES imoveis(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
