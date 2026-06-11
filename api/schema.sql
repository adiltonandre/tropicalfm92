-- ══════════════════════════════════════════════════
--  TROPICAL FM — BANCO DE DADOS
--  Execute este arquivo no phpMyAdmin
--  Crie o banco primeiro: CREATE DATABASE tropicalfm
--    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ══════════════════════════════════════════════════

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ── EMPRESA / RÁDIO ──────────────────────────────
CREATE TABLE IF NOT EXISTS empresa (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    razao      VARCHAR(200),
    cnpj       VARCHAR(18),
    slogan     VARCHAR(200),
    freq       VARCHAR(20),
    tipo       ENUM('fm','am','web','tv') DEFAULT 'fm',
    ano_fund   YEAR,
    stream_url VARCHAR(500),
    -- Endereço
    cep        VARCHAR(10),
    rua        VARCHAR(200),
    numero     VARCHAR(20),
    bairro     VARCHAR(100),
    cidade     VARCHAR(100),
    estado     CHAR(2),
    complemento VARCHAR(100),
    -- Contato
    telefone   VARCHAR(20),
    whatsapp   VARCHAR(20),
    wa_link    VARCHAR(50),
    email      VARCHAR(150),
    email2     VARCHAR(150),
    -- Identidade visual
    logo_url   VARCHAR(500),
    favicon_url VARCHAR(500),
    -- Redes sociais (JSON)
    redes      JSON,
    -- Config visual (JSON: cores, fontes)
    identidade JSON,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro padrão
INSERT IGNORE INTO empresa (id, nome, freq, tipo, cidade, estado)
VALUES (1, 'Tropical FM 92.7', '92.7 MHz', 'fm', 'Presidente Médici', 'RO');

-- ── NOTÍCIAS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS noticias (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo     VARCHAR(300) NOT NULL,
    slug       VARCHAR(300),
    categoria  VARCHAR(50) DEFAULT 'Geral',
    resumo     TEXT,
    conteudo   LONGTEXT,
    imagem_url VARCHAR(500),
    autor      VARCHAR(100) DEFAULT 'Redação',
    status     ENUM('published','draft','archived') DEFAULT 'draft',
    destaque   TINYINT(1) DEFAULT 0,
    visualizacoes INT UNSIGNED DEFAULT 0,
    publicado_em TIMESTAMP NULL,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_categoria (categoria),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PROGRAMAS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS programas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    host       VARCHAR(100),
    horario_ini TIME,
    horario_fim TIME,
    dias       VARCHAR(50)  DEFAULT 'Diário',
    foto_url   VARCHAR(500),
    descricao  TEXT,
    ativo      TINYINT(1)   DEFAULT 1,
    ordem      SMALLINT     DEFAULT 0,
    criado_em  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SLIDES / CAROUSEL ────────────────────────────
CREATE TABLE IF NOT EXISTS slides (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo     VARCHAR(300) NOT NULL,
    subtitulo  TEXT,
    imagem_url VARCHAR(500),
    tag        VARCHAR(80)  DEFAULT 'Destaque',
    btn_texto  VARCHAR(80),
    btn_link   VARCHAR(500),
    ativo      TINYINT(1)   DEFAULT 1,
    ordem      SMALLINT     DEFAULT 0,
    criado_em  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo_ordem (ativo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TOP MÚSICAS ──────────────────────────────────
CREATE TABLE IF NOT EXISTS top_musicas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    musica     VARCHAR(200) NOT NULL,
    artista    VARCHAR(200) NOT NULL,
    tendencia  ENUM('up','down','same') DEFAULT 'same',
    capa_url   VARCHAR(500),
    posicao    TINYINT UNSIGNED DEFAULT 1,
    ativo      TINYINT(1) DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posicao (posicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PODCAST / FRENTE A FRENTE ────────────────────
CREATE TABLE IF NOT EXISTS podcast_config (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    canal_url   VARCHAR(500),
    channel_id  VARCHAR(100),
    live_ativo  TINYINT(1) DEFAULT 0,
    live_url    VARCHAR(500),
    live_titulo VARCHAR(300),
    live_desc   TEXT,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO podcast_config (id, canal_url, channel_id)
VALUES (1, 'https://www.youtube.com/@FrenteaFrente', 'UCE6Lkid4h406OQ2PEp60ocg');

CREATE TABLE IF NOT EXISTS podcast_episodios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo     VARCHAR(300) NOT NULL,
    youtube_id VARCHAR(20)  NOT NULL,
    data_ep    DATE,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_ep)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ENQUETE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS enquetes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pergunta   TEXT NOT NULL,
    ativa      TINYINT(1) DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquete_opcoes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquete_id INT UNSIGNED NOT NULL,
    texto      VARCHAR(200) NOT NULL,
    votos      INT UNSIGNED DEFAULT 0,
    ordem      TINYINT DEFAULT 0,
    FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
    INDEX idx_enquete (enquete_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PUBLICIDADE ──────────────────────────────────
CREATE TABLE IF NOT EXISTS publicidade (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo       ENUM('principal','flutuante') NOT NULL,
    ativo      TINYINT(1) DEFAULT 0,
    imagem_url VARCHAR(500),
    link_url   VARCHAR(500),
    texto      TEXT,
    delay_s    TINYINT UNSIGNED DEFAULT 8,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO publicidade (tipo) VALUES ('principal'), ('flutuante');

-- ── CONFIGURAÇÕES GERAIS ──────────────────────────
CREATE TABLE IF NOT EXISTS configuracoes (
    chave   VARCHAR(100) PRIMARY KEY,
    valor   TEXT,
    tipo    ENUM('string','json','bool','int') DEFAULT 'string',
    descricao VARCHAR(300)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES
('pin_hash',       '',       'string', 'Hash SHA256 do PIN admin'),
('player_vol',     '80',     'int',    'Volume padrão do player (0-100)'),
('player_autoplay','0',      'bool',   'Autoplay ao abrir'),
('tema_cores',     '{}',     'json',   'Paleta de cores do site'),
('tema_fontes',    '{}',     'json',   'Tipografia do site'),
('site_ativo',     '1',      'bool',   'Site em produção'),
('manutencao',     '0',      'bool',   'Modo manutenção');

-- ── LOG DE ATIVIDADE ──────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    acao       VARCHAR(200) NOT NULL,
    detalhes   TEXT,
    ip         VARCHAR(45),
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════
--  FIM — Banco criado com sucesso!
--  Próximo passo: configure api/config/database.php
-- ════════════════════════════════════════════════════
