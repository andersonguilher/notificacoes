<?php
// visualizar_notificacao.php

require_once __DIR__ . '/../../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de notificação inválido.");
}

$id_notificacao = $_GET['id'];

// --- CONSULTA AO BANCO DE DADOS (Cópia de gerar_docx.php) ---
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("
        SELECT 
            n.*, 
            t.nome_tipo, 
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
    
} catch (PDOException $e) {
    die("Erro ao consultar dados: " . $e->getMessage());
}

// --- FORMATAÇÃO DOS DADOS (Cópia de gerar_docx.php) ---
$numero_ano = $notif['numero_documento'] . '/' . date('Y', strtotime($notif['data_emissao']));
$endereco_completo = $notif['logradouro'] . ' - ' . $notif['bairro'];

// Formatação da data por extenso
$meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$mes_numero = (int)date('m', strtotime($notif['data_emissao']));
$data_emissao_formatada = date('d', strtotime($notif['data_emissao'])) . ' de ' . 
                          $meses[$mes_numero] . 
                          ' de ' . date('Y', strtotime($notif['data_emissao']));

// Define o caminho do QR Code (para exibição na pré-visualização)
$qr_code_path = 'qrcodes/' . $notif['qr_code_path'];
$qr_code_full_path_check = __DIR__ . '/' . $qr_code_path;

// Defino o caminho do logo (assumindo que está na mesma pasta)
$logo_path = 'logo.png'; 

// Função auxiliar para renderizar itens de dados (em formato de card)
function renderDetailCard($label, $value, $className = 'bg-white') {
    $html = '<div class="' . htmlspecialchars($className) . ' p-4 rounded-xl shadow-md border border-gray-100">';
    $html .= '<p class="text-xs font-medium text-gray-500 uppercase">' . htmlspecialchars($label) . '</p>';
    $html .= '<p class="text-xl font-bold text-gray-800 break-words">' . htmlspecialchars($value) . '</p>';
    $html .= '</div>';
    return $html;
}

// Função auxiliar para renderizar blocos de conteúdo com placeholder
function renderContentBlock($title, $placeholder, $content, $color = 'indigo') {
    $html = '<div class="p-6 rounded-xl shadow-lg border border-gray-200 bg-white">';
    $html .= '<h3 class="text-lg font-bold mb-3 text-' . htmlspecialchars($color) . '-600 flex justify-between items-center">';
    $html .= '<span>' . htmlspecialchars($title) . '</span>';
    // Estilo clean para o placeholder
    $html .= '<span class="font-mono text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($placeholder) . '</span>';
    $html .= '</h3>';
    $html .= '<div class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-gray-700 text-sm leading-relaxed">';
    // Usar pre com font-family: inherit para respeitar a fonte do corpo, mas preservar espaços/quebras
    $html .= '<pre class="whitespace-pre-wrap font-sans text-sm">' . htmlspecialchars($content) . '</pre>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pré-Visualização da Notificação <?= $numero_ano ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            /* Fonte sans-serif moderna */
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
        pre {
            /* Garante que o texto pré-formatado (conteúdo do modelo) use a fonte moderna */
            font-family: inherit;
        }
    </style>
</head>
<body class="bg-gray-50 p-8">
    
    <div class="max-w-5xl mx-auto bg-white p-10 rounded-2xl shadow-2xl shadow-gray-300/50">
        
        <div class="flex justify-between items-center pb-6 mb-8 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                 <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="w-12 h-12 rounded-full border p-1" />
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800">Visualização de Notificação</h1>
                    <p class="text-sm text-gray-500">Confirme o conteúdo antes de gerar o documento final.</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold text-indigo-700 bg-indigo-100 px-4 py-2 rounded-lg shadow-inner">
                    Nº <?= htmlspecialchars($numero_ano) ?>
                </span>
            </div>
        </div>
        
        <p class="mb-8">
            <a href="notificacoes.php" class="text-blue-600 hover:text-blue-800 font-medium transition duration-150">← Voltar para o Gerenciador</a>
        </p>

        <h2 class="text-2xl font-bold mb-5 text-gray-700 border-l-4 border-blue-500 pl-3">Dados da Emissão</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="md:col-span-2">
                <?= renderDetailCard('Endereço Completo', $endereco_completo) ?>
            </div>
            <?= renderDetailCard('Modelo', $notif['nome_tipo']) ?>
            <?= renderDetailCard('Prazo (Dias)', $notif['prazo_dias'], 'bg-yellow-50') ?>
            <?= renderDetailCard('Data de Emissão', date('d/m/Y', strtotime($notif['data_emissao']))) ?>
            <div class="md:col-span-2">
                <?= renderDetailCard('Data por Extenso (Placeholder DATA_EXTENSO)', $data_emissao_formatada, 'bg-gray-100') ?>
            </div>
        </div>
        
        <h2 class="text-2xl font-bold mb-5 text-gray-700 border-l-4 border-indigo-500 pl-3 mt-10">Conteúdo do Documento (Mapeamento)</h2>
        <div class="space-y-6">
            
            <?= renderContentBlock('Capitulação da Infração', 'CAP_INFRACAO', $notif['capitulacao_infracao'], 'purple') ?>
            
            <?= renderContentBlock('Obrigação / Determinação', 'OBRIGACAO', $notif['obrigacao'], 'green') ?>

            <?= renderContentBlock('Capitulação da Multa', 'CAP_MULTA', $notif['capitulacao_multa'], 'red') ?>
        </div>

        <div class="mt-10 p-6 rounded-xl shadow-lg border border-gray-200 bg-white">
            <h3 class="text-lg font-bold mb-3 text-gray-700">QR Code (<span class="font-mono text-sm font-semibold text-pink-700">QR_CODE</span>)</h3>
            <div class="flex items-center space-x-6 p-4 rounded-xl border border-dashed border-gray-300 bg-gray-50">
                <?php if ($notif['qr_code_path'] && file_exists($qr_code_full_path_check)): ?>
                    <img src="<?= htmlspecialchars($qr_code_path) ?>" alt="QR Code Preview" class="border p-1 rounded-lg shadow-md w-24 h-24" style="object-fit: contain;">
                    <p class="text-green-600 font-medium">A imagem foi **encontrada** e será inserida no DOCX.</p>
                <?php else: ?>
                    <div class="flex items-center space-x-2 text-red-600 font-bold bg-red-100 p-3 rounded-lg border border-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>IMAGEM QR CODE AUSENTE ou não encontrada no sistema.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-gray-200 text-center">
            <a href="gerar_docx.php?id=<?= $id_notificacao ?>" class="inline-flex items-center space-x-2 bg-indigo-600 text-white py-3 px-8 rounded-xl text-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-lg hover:shadow-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L10 11.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z" clip-rule="evenodd" /><path d="M10 2a2 2 0 012 2v7a2 2 0 11-4 0V4a2 2 0 012-2z" /></svg>
                <span>Gerar e Baixar DOCX Final</span>
            </a>
        </div>
        
    </div>
</body>
</html>