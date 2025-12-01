# Sistema Gerenciador de Notificações

Um sistema web simples e eficiente desenvolvido em PHP para gerenciar, gerar e controlar a emissão de Notificações/Documentos, utilizando modelos pré-definidos e numeração sequencial. O sistema utiliza a biblioteca PHPWord para gerar documentos em formato DOCX.

## 🚀 Funcionalidades

* **Geração de Documentos:** Criação de novas notificações com base em modelos.
* **Gerenciamento de Modelos:** Cadastro e edição de tipos de notificação, incluindo:
    * Nome do Modelo
    * Capitulação da Infração, Obrigação e Capitulação da Multa.
    * Upload opcional de imagem de QR Code (para inclusão no documento DOCX).
* **Numeração Automática:** Cálculo do próximo número de documento sequencial, com a opção de configurar um número inicial.
* **Pré-visualização:** Modal de pré-visualização (via AJAX) para conferir os dados e o conteúdo do modelo antes de salvar.
* **Estrutura de Navegação Consistente:** Menu fixo e padronizado em todas as páginas para facilitar a navegação.
* **Saída DOCX:** Gera o documento final para download (via `gerar_docx.php`).

## 🛠️ Tecnologias e Dependências

O projeto é construído principalmente em PHP e depende de algumas bibliotecas importantes gerenciadas pelo Composer.

* **Backend:** PHP (com PDO para conexão com o banco de dados).
* **Frontend:** HTML5, Tailwind CSS (via CDN) e JavaScript.
* **Gerenciador de Pacotes:** [Composer](https://getcomposer.org/)
* **Processamento de DOCX:** [PHPOffice/PHPWord](https://github.com/PHPOffice/PHPWord)
* **Outras Dependências (Presentes na Estrutura):** dompdf, sabberworm/php-css-parser, masterminds/html5.

## ⚙️ Instalação e Configuração

Siga os passos abaixo para configurar o projeto localmente.

### Pré-requisitos

* Servidor Web (Apache, Nginx, etc.)
* PHP 7.4+
* MySQL/MariaDB
* Composer

### Passos de Instalação

1.  **Clone o Repositório:**
    ```bash
    git clone [URL_DO_SEU_REPOSITÓRIO]
    cd nome-do-projeto
    ```

2.  **Instale as Dependências PHP:**
    ```bash
    composer install
    ```

3.  **Configuração do Banco de Dados:**

    * Crie o banco de dados e as tabelas utilizando o script SQL abaixo.

    **Script SQL de Criação (DDL e Dados Iniciais):**

    ```sql
    -- Criação do Banco de Dados
    CREATE DATABASE IF NOT EXISTS `notificacoes` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
    USE `notificacoes`;

    -- Tabela para configurações chave/valor (usada para o número inicial)
    CREATE TABLE IF NOT EXISTS `configuracoes` (
      `chave` varchar(50) NOT NULL,
      `valor` varchar(255) NOT NULL,
      PRIMARY KEY (`chave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

    -- Dados iniciais de configuração
    INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
    ('numero_inicial_notificacao', '110');

    -- Tabela para os modelos de notificação
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

    -- Dados iniciais dos tipos de notificação
    INSERT INTO `tipos_notificacao` (`id_tipo`, `nome_tipo`, `capitulacao_infracao`, `capitulacao_multa`, `ativo`, `data_criacao`, `qr_code_path`) VALUES
    (1, 'RECOMPOSIÇÃO DE PASSEIO', 'Art. 447 da Lei Complementar 270/2024', '§ 17 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-08 14:55:26', 'qr_1760368363_calçada.png'),
    (2, 'DANOS AO MEIO-FIO DE VIA PÚBLICA', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &amp;quot;E&amp;quot; 3.800/70', 1, '2025-10-13 15:04:42', 'qr_1760367882_meio-fio.png'),
    (3, 'COLOCAÇÃO DE RAMPA OU CUNHA(FIXAS OU MÓVEIS) SOBRE PASSEIO EM VIAS PÚBLICAS', 'Art. 448 da Lei Complementar 270/2024', '§16º do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-13 15:10:43', 'qr_1760368243_rampa.png'),
    (4, 'FECHAMENTO DE LOGRADOURO', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70\r\n', 1, '2025-10-14 15:18:09', 'qr_1760455089_fechamento_logradouro.png'),
    (5, 'DISPOSITIVOS IRREGULARES NA CALÇADA', 'Art. 285 da Lei Complementar 270/2024', '§16 do Art. 136 do RLF do Dec. &quot;E&quot; 3.800/70', 1, '2025-10-14 16:30:28', 'qr_1760459428_obstáculo.png'),
    (6, 'OBRA SEM LICENÇA', 'Art. 8º do Regulamento p/ Obras, Reparos e Serviços em Vias Públicas aprovado pelo Dec. 2613/80, de 15/03/80', '§ 5º do Art. 136 do RLF do Dec. &amp;quot;E&amp;quot; 3.800/70, por força do disposto no Art. 22 § 1º do Dec. 2613/80', 1, '2025-10-29 18:43:14', 'qr_1761763394_obrasemlicença.jpg'),
    (7, 'USURPAR VIA PÚBLICA', 'Art. 285 da Lei Complementar 270/2024\r\n', '§16 do Art. 136 do RLF do Dec. 3.800/70', 1, '2025-11-27 15:22:08', NULL);

    -- Tabela para as notificações emitidas
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
    ```

4.  **Configuração de Conexão (`config.php`)**

    Crie o arquivo `config.php` na pasta superior (`../../config.php` conforme referenciado no código) com os detalhes da conexão PDO.

    ```php
    <?php
    // Exemplo de config.php (ajuste os valores conforme seu ambiente)
    $host = 'localhost';
    $db   = 'notificacoes'; // Nome do banco de dados criado
    $user = 'seu_usuario';
    $pass = 'sua_senha';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
         $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
         // O sistema de notificação depende desta conexão, então é importante tratar falhas.
         die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
    }
    ```

5.  **Estrutura de Pastas:**

    * Crie a pasta `qrcodes/` no mesmo nível de `notificacoes.php` e `tipos_notificacao.php` para armazenar as imagens de QR Code. Esta pasta deve ter permissões de escrita (777 ou similar).

6.  **Acesso:**

    Acesse o sistema pelo seu navegador, apontando para o diretório raiz do projeto:
    ```
    http://localhost/seu-projeto/
    ```

## 📝 Uso

1.  **Configurações:** Acesse a aba **Configurações** para definir o número inicial das notificações.
2.  **Modelos:** Acesse **Gerenciar Modelos** para cadastrar os diferentes tipos de notificação, incluindo os campos de texto e o QR Code.
3.  **Nova Notificação:** Na página principal **Notificações (Início)**:
    * **Atenção:** Embora os formulários atuais não solicitem o campo `nome_proprietario` explicitamente, ele é um campo obrigatório no banco de dados (`notificacoes.nome_proprietario NOT NULL`). Certifique-se de que sua lógica de cadastro no `notificacoes.php` esteja inserindo um valor válido para este campo, ou adicione o campo ao formulário.
    * Selecione o **Modelo**.
    * Insira os dados de **Endereço, Bairro, Prazo** e **Data de Emissão**.
    * Clique em **Pré-visualizar e Confirmar Geração**.
    * No modal de confirmação, clique em **CONFIRMAR E SALVAR** para registrar a notificação e sequencialmente gerar o número do documento.
4.  **Baixar DOCX:** Após salvar, a notificação aparecerá na lista "Notificações Emitidas", onde poderá ser baixada no formato DOCX.