-- Definições iniciais de modo SQL e timezone
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Criação do Banco de Dados: `notificacoes`
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

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('numero_inicial_notificacao', '110');

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `tipos_notificacao`
--

INSERT INTO `tipos_notificacao` (`id_tipo`, `nome_tipo`, `capitulacao_infracao`, `capitulacao_multa`, `ativo`, `data_criacao`, `qr_code_path`) VALUES
(1, 'RECOMPOSIÇÃO DE PASSEIO', 'Art. 447 da Lei Complementar 270/2024', '§ 17 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-08 14:55:26', 'qr_1760368363_calçada.png'),
(2, 'DANOS AO MEIO-FIO DE VIA PÚBLICA', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &amp;quot;E&amp;quot; 3.800/70', 1, '2025-10-13 15:04:42', 'qr_1760367882_meio-fio.png'),
(3, 'COLOCAÇÃO DE RAMPA OU CUNHA(FIXAS OU MÓVEIS) SOBRE PASSEIO EM VIAS PÚBLICAS', 'Art. 448 da Lei Complementar 270/2024', '§16º do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-13 15:10:43', 'qr_1760368243_rampa.png'),
(4, 'FECHAMENTO DE LOGRADOURO', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70\r\n', 1, '2025-10-14 15:18:09', 'qr_1760455089_fechamento_logradouro.png'),
(5, 'DISPOSITIVOS IRREGULARES NA CALÇADA', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-14 16:30:28', 'qr_1760459428_obstáculo.png'),
(6, 'OBRA SEM LICENÇA', 'Art. 8º do Regulamento p/ Obras, Reparos e Serviços em Vias Públicas aprovado pelo Dec. 2613/80, de 15/03/80', '§ 5º do Art. 136 do RLF do Dec. &amp;quot;E&amp;quot; 3.800/70, por força do disposto no Art. 22 § 1º do Dec. 2613/80', 1, '2025-10-29 18:43:14', 'qr_1761763394_obrasemlicença.jpg'),
(7, 'USURPAR VIA PÚBLICA', 'Art. 285 da Lei Complementar 270/2024\r\n', '§16 do Art. 136 do RLF do Dec. 3.800/70', 1, '2025-11-27 15:22:08', NULL);

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
  KEY `id_tipo` (`id_tipo`),
  CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_notificacao` (`id_tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id_notificacao`, `numero_documento`, `id_tipo`, `nome_proprietario`, `logradouro`, `bairro`, `prazo_dias`, `data_emissao`, `status`, `data_registro`, `obrigacao`, `protocolo_1746`) VALUES
(2, '110', 1, '', 'RUA BARCELOS DOMINGOS, 141', 'Campo Grande', 30, '2025-10-14', 'Emitida', '2025-10-14 13:21:55', 'RECOMPOSIÇÃO DE CALÇADA', NULL),
(3, '111', 4, '', 'RUA MARIA DO CARMO CASTRO, S/N°', 'Campo Grande', 30, '2025-10-14', 'Emitida', '2025-10-14 15:24:28', 'FECHAMENTO DE LOGRADOURO PÚBLICO', ''),
(4, '112', 1, '', 'RUA QUATORZE , N°40 ( ESTRADA DO CAMPINHO )', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 14:21:27', 'REPARO EM CALÇADA', ''),
(5, '113', 1, '', 'RUA CAMPO GRANDE, N°1256 ', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 14:29:40', 'REPARO EM CALÇADA', ''),
(6, '114', 5, '', 'RUA VITOR ALVES, N°1564', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 14:35:39', 'REMOVER OS OBSTÁCULOS NA CALÇADA', ''),
(7, '115', 1, '', 'RUA BERNARDO FRANCISCO BARROS, N°5', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 14:42:18', 'REPARO NA CALÇADA', ''),
(8, '116', 1, '', 'RUA FREI TIMÓTEO, N°190 ( ESTRADA DO CAMPINHO )', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 14:44:56', 'REPARO NA CALÇADA', ''),
(9, '117', 5, '', 'RUA VICENTE DE ARAÚJO, N°35', 'Campo Grande', 30, '2025-10-15', 'Emitida', '2025-10-15 15:56:25', 'RETIRAR OBSTÁCULO ( CONSTRUÇÃO NO PASSEIO )', ''),
(10, '118', 4, '', 'ESTRADA DA CAROBA, N°520 ( CONDOMÍNIO PARQUE DOURADO )', 'Campo Grande', 30, '2025-10-21', 'Emitida', '2025-10-21 18:13:39', 'APRESENTAR DOCUMENTAÇÃO QUE COMPROVE AUTORIZAÇÃO PARA FECHAMENTO DO LOGRADOURO', ''),
(11, '119', 5, '', 'RUA LANDULFO ALVES, N°30', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 14:00:04', 'REMOVER VASOS QUE OBSTRUEM O PASSEIO.', ''),
(12, '120', 4, '', 'ESTRADA DO LAMEIRÃO, N°488', 'Santíssimo', 30, '2025-10-23', 'Emitida', '2025-10-23 14:28:16', 'DOCUMENTAÇÃO QUE COMPROVE AUTORIZAÇÃO PARA FECHAMENTO DO LOGRADOURO.', ''),
(13, '121', 5, '', 'ESTRADA DO CAMPINHO , N°4522 ( TMC CAMPINHO )', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 14:34:40', 'REMOVER FECHAMENTO IRREGULAR DO PASSEIO.', ''),
(14, '122', 5, '', 'RUA ITAÚNAS, N°13', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 14:46:27', 'REMOVER VASOS QUE OBSTRUEM O PASSEIO.', ''),
(15, '123', 1, '', 'ESTRADA SANTA MARIA, N°8', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 14:57:11', 'PROVIDENCIAR A MANUTENÇÃO DA CALÇADA NOS PADRÕES EXISTENTES.', ''),
(16, '124', 1, '', 'ESTRADA DE SANTA MARIA , N°1210', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:00:42', 'PROVIDENCIAR REPAROS NA CALÇADA', ''),
(17, '125', 5, '', 'RUA RIO MANSO, N°28-A', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:07:19', 'REMOVER OS OBSTÁCULOS NA CALÇADA.', ''),
(18, '126', 1, '', 'RUA ALFREDO DE MORAIS, N°281', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:10:41', 'REPARO EM CALÇADA', ''),
(19, '127', 1, '', 'RUA ALFREDO DE MORAIS, N°363', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:11:18', 'REPARO EM CALÇADA', ''),
(20, '128', 1, '', 'RUA ALFREDO DE MORAIS, N°250', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:11:52', 'REPARO EM CALÇADA', ''),
(21, '129', 1, '', 'RUA ALFREDO DE MORAIS, N°362', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:12:54', 'REPARO EM CALÇADA', ''),
(22, '130', 4, '', 'RUA CAMPO GRANDE, N°100 ( CONDOMÍNIO PARQUE DOURADO )', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 15:15:10', 'APRESENTAR DOCUMENTAÇÃO QUE COMPROVE AUTORIZAÇÃO PARA FECHAMENTO DO LOGRADOURO.', ''),
(23, '131', 5, '', 'RUA VICENTE DE ARAÚJO, N°35 ', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 18:39:31', 'RETIRAR OBSTÁCULOS  ( CONSTRUÇÃO NO PASSEIO )', ''),
(24, '132', 5, '', 'RUA VICENTE DE ARAÚJO, N°50', 'Campo Grande', 30, '2025-10-23', 'Emitida', '2025-10-23 18:46:12', 'RETIRAR OBSTÁCULOS ( CONSTRUÇÃO NO PASSEIO )', ''),
(25, '133', 5, '', 'ESTRADA DA POSSE, N°2965', 'Campo Grande', 30, '2025-10-29', 'Emitida', '2025-10-29 18:43:10', 'REMOVER OBSTÁCULOS NA CALÇADA', ''),
(27, '134', 6, '', 'ESTRADA DA POSSE, N°2965', 'Campo Grande', 30, '2025-10-29', 'Emitida', '2025-10-29 19:01:41', 'APRESENTAR LICENÇA PARA OBRA EM VIA PÚBLICA.', ''),
(28, '135', 5, '', 'ESTRADA SANTA MARIA, N°1800', 'Campo Grande', 30, '2025-11-03', 'Emitida', '2025-11-03 16:37:07', 'REMOVER OBSTÁCULO', ''),
(29, '136', 1, '', 'ESTRADA DO MENDANHA, N°600', 'Campo Grande', 30, '2025-11-04', 'Emitida', '2025-11-04 18:53:11', 'REPARO NO PASSEIO', ''),
(30, '137', 5, '', 'RUA MANOEL DE OLIVEIRA,  LT:35 QD:3', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 12:51:50', 'REMOVER OBASTÁCULO', '22549171'),
(31, '138', 1, '', 'RUA VITOR ALVES, N°159', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 12:56:05', 'REPARO NO PASSEIO\r\n', '22549879'),
(32, '139', 1, '', 'RUA VITOR ALVES , N°384', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 12:58:52', 'REPARO NO PASSEIO', ''),
(33, '140', 1, '', 'RUA VITOR ALVES, N°414', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 13:00:32', 'REPARO NO PASSEIO', '22549828'),
(34, '141', 1, '', 'RUA VITOR ALVES , N°881', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 13:06:21', 'REPARO NO PASSEIO ', '22546857'),
(37, '142', 5, '', 'RUA MATARACA, N°5 ', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 13:23:27', 'REMOVER OBSTÁCULO', '22569772'),
(38, '143', 1, '', 'RUA RIACHÃO, N°126', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 13:25:17', 'REPARO NO PASSEIO', '22572376'),
(39, '144', 1, '', 'RUA VITOR ALVES, N°158', 'Campo Grande', 30, '2025-11-27', 'Emitida', '2025-11-27 15:05:09', 'REPARO NO PASSEIO', ''),
(40, '145', 7, '', 'ESTRADA DO PRE, 703', 'Senador Vasconcelos', 5, '2025-11-27', 'Emitida', '2025-11-27 15:38:59', 'APRESENTAR DOCUMENTOS QUE COMPROVEM O ALINHAMENTO DO IMÓVEL OU REMOVER AS EDIFICAÇÕES FORA DOS LIMITES', ''),
(41, '146', 1, '', 'ESTRADA DO CAMPINHO, N°4524', 'Campo Grande', 30, '2025-11-28', 'Emitida', '2025-11-28 13:44:31', 'REPARO NO PASSEIO', '');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;