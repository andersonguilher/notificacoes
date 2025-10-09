<?php
// gerar_docx.php

// Inicia o buffer de saída no topo do script para capturar qualquer saída indesejada
ob_start(); 

require 'vendor/autoload.php';
require_once 'config.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Variáveis de controle
$templateProcessor = null;
$qr_code_path = null; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de notificação inválido.");
}

$id_notificacao = $_GET['id'];

// --- CONSULTA AO BANCO DE DADOS ---
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("
        SELECT 
            n.*, 
            t.capitulacao_infracao, 
            t.obrigacao, 
            t.capitulacao_multa,
            t.qr_code_path  
        FROM notificacoes n
        JOIN tipos_notificacao t ON n.id_tipo = t.id_tipo
        WHERE n.id_notificacao = ?
    ");
    $stmt->execute([$id_notificacao]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notif) {
        die("Notificação não encontrada.");
    }
    
    // Define o caminho completo ABSOLUTO da imagem estática.
    // O __DIR__ garante que a imagem é encontrada no sistema de arquivos.
    $qr_code_full_path = __DIR__ . '/qrcodes/' . $notif['qr_code_path'];
    
} catch (PDOException $e) {
    die("Erro ao consultar dados: " . $e->getMessage());
}

// --- FORMATAÇÃO DOS DADOS ---
$numero_ano = $notif['numero_documento'] . '/' . date('Y', strtotime($notif['data_emissao']));
$endereco_completo = $notif['logradouro'] . ' - ' . $notif['bairro'];

// Formatação da data por extenso
$meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$mes_numero = (int)date('m', strtotime($notif['data_emissao']));
$data_emissao_formatada = date('d', strtotime($notif['data_emissao'])) . ' de ' . 
                          $meses[$mes_numero] . 
                          ' de ' . date('Y', strtotime($notif['data_emissao']));


// --- PREENCHIMENTO DO TEMPLATE DOCX ---
try {
    $templateProcessor = new TemplateProcessor('Modelo_Notificacao.docx'); 

    // 1. Substituição dos Placeholders de TEXTO
    $templateProcessor->setValue('NUMERO_ANO', $numero_ano);
    $templateProcessor->setValue('CAP_INFRACAO', htmlspecialchars($notif['capitulacao_infracao']));
    $templateProcessor->setValue('ENDERECO_COMPLETO', $endereco_completo);
    $templateProcessor->setValue('PRAZO_DIAS', $notif['prazo_dias']);
    $templateProcessor->setValue('OBRIGACAO', htmlspecialchars($notif['obrigacao']));
    $templateProcessor->setValue('CAP_MULTA', $notif['capitulacao_multa']);
    $templateProcessor->setValue('DATA_EXTENSO', $data_emissao_formatada);

    // 2. INSERÇÃO DA IMAGEM ESTÁTICA (Usando a técnica de teste.php: setImageValue)
    $image_inserted = false;

    // Apenas tenta inserir se houver um caminho e o arquivo existir no servidor
    if ($notif['qr_code_path'] && file_exists($qr_code_full_path)) {
        try {
            // Define as opções de como a imagem deve ser inserida
            $opcoes_imagem = array(
                'path' => $qr_code_full_path, // Caminho ABSOLUTO
                'width' => 80, 
                'height' => 80, 
                'ratio' => true, // Mantém a proporção
                // O alinhamento 'right' é o equivalente em string para o estilo anterior
                'align' => 'right',
            );
            
            // O método setImageValue faz a substituição do placeholder 'QR_CODE'
            $templateProcessor->setImageValue('QR_CODE', $opcoes_imagem);
            
            $image_inserted = true;
        } catch (\Exception $e) {
            // Se falhar na inserção (erro interno do PHPWord), loga e continua
            error_log("Erro ao inserir imagem no DOCX (setImageValue): " . $e->getMessage());
        }
    }
    
    // Se a imagem não foi inserida com sucesso, usa o fallback de texto.
    if (!$image_inserted) {
        // Alerta o usuário para o problema (no documento)
        $templateProcessor->setValue('QR_CODE', 'IMAGEM QR CODE AUSENTE OU ERRO DE INSERÇÃO.');
    }

} catch (\Throwable $e) {
    // ERRO CRÍTICO no processamento do DOCX
    ob_end_clean(); 
    die("Erro Crítico ao processar o DOCX. Verifique a formatação do Modelo_Notificacao.docx. Erro: " . $e->getMessage());
}


// --- DOWNLOAD DO ARQUIVO ---

// Limpa o buffer de saída antes de enviar o arquivo binário
ob_end_clean(); 

$nome_arquivo = 'Notificacao_' . $notif['numero_documento'] . '_' . date('Y') . '.docx';

header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');

exit;