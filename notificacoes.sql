-- Definições iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `notificacoes`
--
CREATE DATABASE IF NOT EXISTS `notificacoes` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
USE `notificacoes`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id_notificacao` int NOT NULL AUTO_INCREMENT,
  `numero_documento` varchar(10) NOT NULL,
  `id_tipo` int NOT NULL,
  `nome_proprietario` varchar(255) NOT NULL,
  `logradouro` varchar(255) NOT NULL,
  `bairro` varchar(255) NOT NULL,
  `prazo_dias` int DEFAULT '30',
  `data_emissao` date NOT NULL,
  `status` enum('Emitida','Entregue','Arquivada','Cancelada') DEFAULT 'Emitida',
  `data_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `obrigacao` text,
  `protocolo_1746` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_notificacao`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `id_tipo` (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_notificacao`
--

CREATE TABLE IF NOT EXISTS `tipos_notificacao` (
  `id_tipo` int NOT NULL AUTO_INCREMENT,
  `nome_tipo` varchar(255) NOT NULL,
  `capitulacao_infracao` text NOT NULL,
  `capitulacao_multa` text NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `qr_code_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_notificacao` (`id_tipo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;