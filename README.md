# 🏛️ Sistema de Gestão de Notificações e Fiscalização

**Secretaria Municipal de Conservação - 22ª Gerência de Conservação**

Este sistema é uma solução web desenvolvida em PHP para modernizar,
padronizar e agilizar a emissão de notificações de infração. Ele
substitui o preenchimento manual por um fluxo digital que gera
documentos Word (`.docx`) e PDF, controla a numeração sequencial
automaticamente e integra a legislação via QR Codes dinâmicos.

------------------------------------------------------------------------

## 📋 Índice

1.  [Funcionalidades](#-funcionalidades)
2.  [Estrutura do Projeto](#-estrutura-de-arquivos)
3.  [Requisitos do Servidor](#-requisitos-do-servidor)
4.  [Instalação Passo a Passo](#-instalação-passo-a-passo)
5.  [Configuração do Banco de Dados](#-configuração-do-banco-de-dados)
6.  [Manual de Uso](#-manual-de-uso)
7.  [Resolução de Problemas](#-resolução-de-problemas)

------------------------------------------------------------------------

## ✨ Funcionalidades

### 🚀 Emissão e Gestão

-   **Numeração Automática:** Sequência contínua baseada no último
    registro (ex: 146/2025).
-   **Preenchimento Inteligente:** Campos legais preenchidos
    automaticamente ao selecionar um modelo.
-   **Protocolo 1746:** Campo integrado para vincular o documento ao
    chamado.
-   **Segurança:** Apenas a última notificação pode ser excluída.

### 📜 Modelos e Legislação (QR Code)

-   **Cadastro de Modelos:** Crie modelos para infrações recorrentes.
-   **Anexo de Leis:** Upload de PDF vinculado a cada modelo.
-   **QR Code Automático:** Gera código exclusivo que abre o PDF oficial
    ao escanear.

### 🖨️ Documentação e Impressão

-   **Pré-visualização tipo A4:** Layout fiel ao documento final.
-   **Exportação DOCX:** Gera documento Word editável com QR Code
    embutido.

------------------------------------------------------------------------

## 📂 Estrutura de Arquivos

``` text
/raiz-do-projeto
│
├── composer.json             
├── db.php                    
├── README.md                 
│
├── qrcodes/                 # QR Codes (Permissão 777)
├── uploads_pdfs/            # PDFs das leis (Permissão 777)
├── vendor/                  # Dependências do Composer (Será criada após executar composer install)
│
├── index.php
├── notificacoes.php
├── nova_notificacao.php
├── tipos_notificacao.php
├── configuracoes.php
├── visualizar_notificacao.php
├── gerar_docx.php
├── ver_documento.php
│
├── Modelo_Notificacao.docx
└── logo.png
```

------------------------------------------------------------------------

## 🖥️ Requisitos do Servidor

-   PHP 7.4+
-   MySQL / MariaDB
-   Extensões: pdo_mysql, gd/imagick, zip, xml, mbstring
-   Composer instalado

------------------------------------------------------------------------

## 🛠️ Instalação Passo a Passo

### 1. Clonar ou Baixar

Coloque o projeto em:

    /var/www/html/notificacoes

### 2. Instalar dependências

``` bash
composer install
```

### 3. Permissões (Linux)

``` bash
chmod 777 qrcodes/
chmod 777 uploads_pdfs/
```

### 4. Criar db.php

``` php
<?php
$host = 'localhost';
$db   = 'notificacoes';
$user = 'root';
$pass = '';

try {
    $pdo_notificacoes = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo_notificacoes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
```

------------------------------------------------------------------------

## 💾 Configuração do Banco de Dados

``` sql
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  PRIMARY KEY (`chave`)
);

INSERT INTO `configuracoes` VALUES ('numero_inicial_notificacao', '146');

CREATE TABLE IF NOT EXISTS `tipos_notificacao` (
  `id_tipo` int NOT NULL AUTO_INCREMENT,
  `nome_tipo` varchar(255) NOT NULL,
  `capitulacao_infracao` text NOT NULL,
  `capitulacao_multa` text NOT NULL,
  `prazo_dias` int DEFAULT 30,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `qr_code_path` varchar(255),
  `caminho_pdf` varchar(255),
  `token_pdf` varchar(64),
  PRIMARY KEY (`id_tipo`)
);

CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id_notificacao` int NOT NULL AUTO_INCREMENT,
  `numero_documento` varchar(20) NOT NULL,
  `id_tipo` int NOT NULL,
  `nome_proprietario` varchar(255),
  `logradouro` varchar(255) NOT NULL,
  `bairro` varchar(100) NOT NULL,
  `prazo_dias` int DEFAULT 30,
  `data_emissao` date NOT NULL,
  `status` enum('Emitida','Entregue','Cancelada') DEFAULT 'Emitida',
  `obrigacao` text NOT NULL,
  `protocolo_1746` varchar(50),
  `data_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacao`),
  UNIQUE KEY `numero_documento` (`numero_documento`),
  KEY `fk_tipo` (`id_tipo`),
  CONSTRAINT `fk_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_notificacao` (`id_tipo`)
);

COMMIT;
```

------------------------------------------------------------------------

## 📘 Manual de Uso

### 1. Definir Numeração Inicial

Acesse **Configurações** → coloque o último número usado no
talão/manual.

### 2. Criar Modelos

Menu **Gerenciar Modelos**:

-   Nome
-   Capitulação da Infraçāo
-   Capitulação da Multa
-   Prazo
-   Upload do PDF

QR Code é criado automaticamente.

### 3. Emitir Notificação

-   Clique em **+ Nova Notificação**
-   Selecione modelo
-   Preencha endereço, bairro, protocolo
-   Escreva a obrigação
-   Emitir

### 4. Impressão & Download

-   **👁️ Ver:** Visualização A4 (use Ctrl+P)
-   **DOCX:** Baixar arquivo editável
-   **Excluir:** Apenas a última pode ser excluída

------------------------------------------------------------------------

## ❓ Resolução de Problemas

**Erro: "Classe BaconQrCode não encontrada"**\
➡️ Execute `composer install`

**Permissão negada ao salvar PDF/QR Code**\
➡️ `chmod 777 qrcodes uploads_pdfs`

**QR Code não aparece no Word**\
➡️ Verifique variável `${QR_CODE}` no modelo `.docx`

**Caracteres estranhos**\
➡️ Banco deve estar em `utf8mb4`

------------------------------------------------------------------------

Desenvolvido para uso exclusivo da **22ª Gerência de Conservação -
Secretaria Municipal de Conservação**.
