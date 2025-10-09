<?php
// Certifique-se de que a biblioteca está instalada via Composer
require 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// --- Configurações ---
$arquivo_template = 'template.docx';
$caminho_nova_imagem = 'nova_logo.png'; // Crie este arquivo no seu diretório
$arquivo_saida = 'documento_final_com_logo.docx';

// 1. Verificação de arquivos
if (!file_exists($arquivo_template)) {
    die("Erro: O arquivo de template '$arquivo_template' não foi encontrado. Crie um e insira o placeholder \${IMAGEM_LOGO}.\n");
}
if (!file_exists($caminho_nova_imagem)) {
    die("Erro: O arquivo de imagem '$caminho_nova_imagem' não foi encontrado.\n");
}

try {
    // 2. Instancia o TemplateProcessor
    $templateProcessor = new TemplateProcessor($arquivo_template);

    // 3. Insere a Imagem (substituindo o placeholder)
    $placeholder_imagem = 'IMAGEM_LOGO'; // O nome do placeholder (sem ${})
    
    // Define as opções de como a imagem deve ser inserida
    $opcoes_imagem = array(
        'path' => $caminho_nova_imagem,
        'width' => 200,    // Largura em pixels
        'height' => 100,   // Altura em pixels
        'ratio' => true,   // Mantém a proporção (recomendado)
        'align' => 'center', // Alinhamento da imagem
    );

    // O método setImageValue faz a substituição do placeholder
    $templateProcessor->setImageValue($placeholder_imagem, $opcoes_imagem);


    // Exemplo extra: Substituindo um placeholder de texto
    $templateProcessor->setValue('NOME_CLIENTE', 'Tech Solutions Ltda.');
    

    // 4. Salva o novo documento
    $templateProcessor->saveAs($arquivo_saida);

    echo "Sucesso! O documento final foi gerado em '$arquivo_saida', substituindo o placeholder de imagem e texto.\n";

} catch (\Exception $e) {
    echo "Erro durante o processamento do template: " . $e->getMessage() . "\n";
}
?>