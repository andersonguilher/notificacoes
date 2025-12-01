SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. TABELA DE CONFIGURAÇÕES
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insere o valor padrão inicial
INSERT INTO `configuracoes` (`chave`, `valor`) VALUES ('numero_inicial_notificacao', '146');

-- 2. TABELA DE TIPOS (MODELOS)
CREATE TABLE IF NOT EXISTS `tipos_notificacao` (
  `id_tipo` int(11) NOT NULL AUTO_INCREMENT,
  `nome_tipo` varchar(255) NOT NULL,
  `capitulacao_infracao` text NOT NULL,
  `capitulacao_multa` text NOT NULL,
  `prazo_dias` int(11) DEFAULT 30,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `qr_code_path` varchar(255) DEFAULT NULL,
  `caminho_pdf` varchar(255) DEFAULT NULL,
  `token_pdf` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABELA DE NOTIFICAÇÕES
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id_notificacao` int(11) NOT NULL AUTO_INCREMENT,
  `numero_documento` varchar(20) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `nome_proprietario` varchar(255) DEFAULT NULL,
  `logradouro` varchar(255) NOT NULL,
  `bairro` varchar(100) NOT NULL,
  `prazo_dias` int(11) DEFAULT 30,
  `data_emissao` date NOT NULL,
  `status` enum('Emitida','Entregue','Cancelada') DEFAULT 'Emitida',
  `obrigacao` text NOT NULL,
  `protocolo_1746` varchar(50) DEFAULT NULL,
  `data_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacao`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `fk_tipo_notificacao` (`id_tipo`),
  CONSTRAINT `fk_tipo_notificacao` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_notificacao` (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;