<?php
// tipos_notificacao.php
require_once __DIR__ . '/../../config.php';

$mensagem = '';
$upload_dir = 'qrcodes/';

// Dados do modelo sendo editado
$modelo_edicao = null; 

// Garante que a pasta de upload exista
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        $mensagem .= "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>ERRO: Não foi possível criar a pasta 'qrcodes/'. Crie-a manualmente e defina permissão 777.</div>";
    }
}


// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIO (INSERT / UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_tipo = htmlspecialchars($_POST['nome_tipo']);
    $capitulacao_infracao = htmlspecialchars($_POST['capitulacao_infracao']);
    $obrigacao = htmlspecialchars($_POST['obrigacao']);
    $capitulacao_multa = htmlspecialchars($_POST['capitulacao_multa']);
    $modelo_id = isset($_POST['id_tipo']) ? (int)$_POST['id_tipo'] : null;
    $qr_code_file_name = null;

    // 1. LÓGICA DE UPLOAD
    if (isset($_FILES['qr_code_file']) && $_FILES['qr_code_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['qr_code_file']['tmp_name'];
        $original_file_name = basename($_FILES['qr_code_file']['name']);
        
        $qr_code_file_name = 'qr_' . time() . '_' . $original_file_name;
        $destination = $upload_dir . $qr_code_file_name;

        if (!move_uploaded_file($file_tmp_name, $destination)) {
            $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>ERRO: Falha ao mover o arquivo de QR Code. Verifique as permissões (777) da pasta 'qrcodes/'.</div>";
            $qr_code_file_name = null;
        }
    }

    try {
        if ($modelo_id) {
            // --- OPERAÇÃO DE EDIÇÃO (UPDATE) ---
            $sql_update = "UPDATE tipos_notificacao SET nome_tipo=?, capitulacao_infracao=?, obrigacao=?, capitulacao_multa=? ";
            $params = [$nome_tipo, $capitulacao_infracao, $obrigacao, $capitulacao_multa];
            
            // Se um novo arquivo foi enviado, atualiza o caminho no BD
            if ($qr_code_file_name) {
                // Remove o arquivo antigo, se existir
                if (isset($_POST['qr_code_path_antigo']) && file_exists($upload_dir . $_POST['qr_code_path_antigo'])) {
                    unlink($upload_dir . $_POST['qr_code_path_antigo']);
                }
                $sql_update .= ", qr_code_path=? ";
                $params[] = $qr_code_file_name;
            } else {
                // Caso contrário, mantém o caminho antigo
                $params[] = isset($_POST['qr_code_path_antigo']) ? $_POST['qr_code_path_antigo'] : null;
                $sql_update .= ", qr_code_path=? ";
            }
            
            $sql_update .= " WHERE id_tipo=?";
            $params[] = $modelo_id;

            $stmt = $pdo->prepare($sql_update);
            $stmt->execute($params);
            $mensagem = "<div class='p-3 bg-green-100 border border-green-400 text-green-700 rounded mb-4'>Modelo atualizado com sucesso!</div>";
        } else {
            // --- OPERAÇÃO DE CADASTRO (INSERT) ---
            $sql_insert = "INSERT INTO tipos_notificacao (nome_tipo, capitulacao_infracao, obrigacao, capitulacao_multa, qr_code_path) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([$nome_tipo, $capitulacao_infracao, $obrigacao, $capitulacao_multa, $qr_code_file_name]);
            $mensagem = "<div class='p-3 bg-green-100 border border-green-400 text-green-700 rounded mb-4'>Tipo de Notificação salvo com sucesso!</div>";
        }
        
        // Redireciona para o modo de visualização após a ação
        header("Location: tipos_notificacao.php?msg=" . urlencode(strip_tags($mensagem)));
        exit;
        
    } catch (PDOException $e) {
        $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>Erro ao processar: " . $e->getMessage() . "</div>";
    }
}


// --- LÓGICA DE CARREGAMENTO PARA EDIÇÃO ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_para_editar = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tipos_notificacao WHERE id_tipo = ?");
        $stmt->execute([$id_para_editar]);
        $modelo_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$modelo_edicao) {
            $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>Modelo não encontrado!</div>";
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>Erro ao carregar modelo: " . $e->getMessage() . "</div>";
    }
}


// Lógica para listar todos os modelos existentes
try {
    $modelos = $pdo->query("SELECT id_tipo, nome_tipo FROM tipos_notificacao ORDER BY nome_tipo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $modelos = [];
    $mensagem .= "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-4'>Erro ao carregar modelos: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Modelos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-xl">
        <h1 class="text-3xl font-bold mb-4 text-gray-800 border-b pb-2">Gerenciamento de Modelos de Notificação</h1>
        
        <p class="mb-6"><a href="notificacoes.php" class="text-indigo-600 hover:text-indigo-800 font-medium">← Voltar para Geração de Notificações</a></p>
        
        <?php if (!empty($_GET['msg'])): ?>
            <div class='p-4 bg-green-50 border border-green-300 text-green-700 rounded-xl mb-6 font-medium'>
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        <?= $mensagem ?>

        <div class="border p-6 rounded-lg mb-8 bg-gray-50">
            <h2 class="text-xl font-semibold mb-4 text-gray-700 border-b pb-2">
                <?= $modelo_edicao ? 'Editar Modelo: ' . htmlspecialchars($modelo_edicao['nome_tipo']) : 'Cadastrar Novo Modelo' ?>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                
                <?php if ($modelo_edicao): ?>
                    <input type="hidden" name="id_tipo" value="<?= $modelo_edicao['id_tipo'] ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label for="nome_tipo" class="block text-sm font-medium text-gray-700">Nome do Modelo</label>
                    <input type="text" id="nome_tipo" name="nome_tipo" required
                           value="<?= $modelo_edicao ? htmlspecialchars($modelo_edicao['nome_tipo']) : '' ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="mb-4">
                    <label for="capitulacao_infracao" class="block text-sm font-medium text-gray-700">CAPTULAÇÃO DA INFRAÇÃO:</label>
                    <textarea id="capitulacao_infracao" name="capitulacao_infracao" rows="3" required
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"><?= $modelo_edicao ? htmlspecialchars($modelo_edicao['capitulacao_infracao']) : '' ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="obrigacao" class="block text-sm font-medium text-gray-700">OBRIGAÇÃO:</label>
                    <textarea id="obrigacao" name="obrigacao" rows="5" required
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"><?= $modelo_edicao ? htmlspecialchars($modelo_edicao['obrigacao']) : '' ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="capitulacao_multa" class="block text-sm font-medium text-gray-700">CAPITULAÇÃO A MULTA:</label>
                    <textarea id="capitulacao_multa" name="capitulacao_multa" rows="3" required
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"><?= $modelo_edicao ? htmlspecialchars($modelo_edicao['capitulacao_multa']) : '' ?></textarea>
                </div>
                
                <div class="mb-6 border p-4 rounded-lg bg-yellow-50">
                    <label for="qr_code_file" class="block text-sm font-bold text-gray-800">Imagem QR Code (PNG/JPG):</label>
                    
                    <p class="text-xs text-gray-600 mb-3">
                        Gere o QR Code em: <a href="https://www.qrcode-monkey.com/pt/" target="_blank" class="text-blue-600 hover:underline font-medium">www.qrcode-monkey.com/pt</a>
                    </p>
                    <?php if ($modelo_edicao && $modelo_edicao['qr_code_path']): ?>
                        <div class="mb-3 flex items-center space-x-4">
                            <p class="text-sm text-gray-600">Arquivo Atual:</p>
                            <img src="<?= $upload_dir . htmlspecialchars($modelo_edicao['qr_code_path']) ?>" alt="QR Code Atual" class="h-12 w-12 border rounded p-1">
                            <input type="hidden" name="qr_code_path_antigo" value="<?= htmlspecialchars($modelo_edicao['qr_code_path']) ?>">
                        </div>
                        <p class="text-xs text-red-600 mb-2">Envie um novo arquivo APENAS se quiser substituir o QR Code acima.</p>
                    <?php endif; ?>
                    
                    <input type="file" id="qr_code_file" name="qr_code_file" accept="image/png, image/jpeg" 
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-100 file:text-yellow-700 hover:file:bg-yellow-200"/>
                </div>

                <button type="submit"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-md transition duration-150">
                    <?= $modelo_edicao ? 'Salvar Alterações' : 'Salvar Novo Modelo' ?>
                </button>
                <?php if ($modelo_edicao): ?>
                    <a href="tipos_notificacao.php" class="mt-2 block text-center text-sm text-gray-600 hover:text-gray-800">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700 border-b pb-2">Modelos Existentes (Clique para Editar)</h2>
        <div class="bg-white p-4 border rounded-md shadow-sm">
            <?php if (!empty($modelos)): ?>
                <?php foreach ($modelos as $modelo): ?>
                    <div class="p-3 border-b last:border-b-0 hover:bg-gray-100 rounded">
                        <a href="tipos_notificacao.php?id=<?= $modelo['id_tipo'] ?>" class="font-medium text-blue-600 hover:underline">
                            <?= htmlspecialchars($modelo['nome_tipo']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500">Nenhum modelo cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>