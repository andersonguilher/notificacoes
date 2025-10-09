<?php
// notificacoes.php
require_once __DIR__ . '/../../config.php';

$mensagem = '';
$numero_inicial_notificacao = 1; // Valor de fallback padrão

// --- CARREGA CONFIGURAÇÃO DE NÚMERO INICIAL ---
try {
    // Garante que o PDO esteja em modo de exceção, caso não esteja em config.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt_config = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'numero_inicial_notificacao'");
    $stmt_config->execute();
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    if ($config && is_numeric($config['valor'])) {
        $numero_inicial_notificacao = (int)$config['valor'];
    }
} catch (PDOException $e) {
    // Em caso de erro (ex: tabela configuracoes não existe), usa o valor de fallback (1)
    // error_log("Erro ao carregar numero_inicial_notificacao: " . $e->getMessage()); // Descomente para logar
}


// --- LÓGICA DE CADASTRO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    
    // 1. Obtém o próximo ID e o formata
    try {
        // Busca o número de documento máximo existente
        $stmt = $pdo->query("SELECT MAX(numero_documento) AS max_numero FROM notificacoes");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $max_numero_existente = $resultado['max_numero'] ? (int)$resultado['max_numero'] : 0;
        
        // O próximo número é o maior valor entre:
        // 1. O máximo existente + 1
        // 2. O número inicial configurado
        $proximo_numero = max($max_numero_existente + 1, $numero_inicial_notificacao);
        
        // Formata o número do documento (ex: 001, 010, 100)
        $numero_documento = str_pad($proximo_numero, 3, '0', STR_PAD_LEFT);
        
        // 2. Coleta e sanitiza os dados
        $id_tipo = $_POST['id_tipo'];
        $logradouro = htmlspecialchars($_POST['logradouro']);
        $bairro = htmlspecialchars($_POST['bairro']);
        $prazo_dias = (int)$_POST['prazo_dias'];
        $data_emissao = $_POST['data_emissao'];
        
        // 3. Insere no banco de dados
        $stmt = $pdo->prepare("INSERT INTO notificacoes (id_tipo, numero_documento, logradouro, bairro, prazo_dias, data_emissao) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_tipo, $numero_documento, $logradouro, $bairro, $prazo_dias, $data_emissao]);
        
        header("Location: notificacoes.php?msg=" . urlencode("Notificação No $numero_documento gerada com sucesso!"));
        exit;
        
    } catch (PDOException $e) {
        $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Erro ao gerar notificação: " . $e->getMessage() . "</div>";
    }
}

// --- LÓGICA DE CONSULTA ---
try {
    $modelos = $pdo->query("SELECT id_tipo, nome_tipo FROM tipos_notificacao ORDER BY nome_tipo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $modelos = [];
    $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Erro ao carregar modelos: " . $e->getMessage() . "</div>";
}

try {
    $stmt_notif = $pdo->query("
        SELECT n.*, t.nome_tipo 
        FROM notificacoes n
        JOIN tipos_notificacao t ON n.id_tipo = t.id_tipo
        ORDER BY n.id_notificacao DESC
    ");
    $notificacoes = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notificacoes = []; 
    $mensagem .= "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Erro ao carregar lista de notificações: " . $e->getMessage() . "</div>";
}
if (!$notificacoes) {
    $notificacoes = []; 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Notificações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-6xl mx-auto bg-white p-10 rounded-2xl shadow-2xl shadow-gray-300/50">
        
        <h1 class="text-4xl font-extrabold mb-4 text-gray-800 border-b-2 border-gray-100 pb-3">
            Sistema Gerenciador de Notificações
        </h1>
        
        <p class="mb-4">
            <a href="tipos_notificacao.php" class="text-blue-600 hover:text-blue-800 font-semibold transition duration-150">→ Gerenciar Modelos de Notificação</a>
        </p>
        <p class="mb-8">
            <a href="configuracoes.php" class="text-blue-600 hover:text-blue-800 font-semibold transition duration-150">→ Configurações do Sistema</a>
        </p>
        
        <?= $mensagem ?>
        <?php if (!empty($_GET['msg'])): ?>
            <div class='p-4 bg-green-50 border border-green-300 text-green-700 rounded-xl mb-6 font-medium'>
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="border border-blue-200 p-8 rounded-xl mb-10 bg-blue-50/50 shadow-inner">
            <h2 class="text-2xl font-bold mb-6 text-blue-800">Gerar Nova Notificação</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="cadastrar">
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="id_tipo" class="block text-sm font-medium text-gray-700 mb-1">Modelo:</label>
                        <select id="id_tipo" name="id_tipo" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white hover:border-blue-400 transition">
                            <option value="">-- Selecione o Modelo --</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?= $modelo['id_tipo'] ?>"><?= htmlspecialchars($modelo['nome_tipo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="logradouro" class="block text-sm font-medium text-gray-700 mb-1">Endereço:</label>
                        <input type="text" id="logradouro" name="logradouro" required 
                               placeholder="Estr. do Mendanha, 140" 
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro:</label>
                        <select id="bairro" name="bairro" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white hover:border-blue-400 transition">
                            <option value="">-- Selecione o Bairro --</option>
                            <option value="Campo Grande">Campo Grande</option>
                            <option value="Santíssimo">Santíssimo</option>
                            <option value="Senador Vasconcelos">Senador Vasconcelos</option>
                        </select>
                    </div>
                    <div>
                        <label for="prazo_dias" class="block text-sm font-medium text-gray-700 mb-1">Prazo Máximo (Dias):</label>
                        <input type="number" id="prazo_dias" name="prazo_dias" value="30" min="1" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="data_emissao" class="block text-sm font-medium text-gray-700 mb-1">Data de Emissão:</label>
                    <input type="date" id="data_emissao" name="data_emissao" value="<?= date('Y-m-d') ?>" required class="mt-1 block w-1/2 px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <button type="submit" class="mt-8 w-full bg-indigo-600 text-white py-3 px-4 rounded-xl text-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-lg hover:shadow-xl">
                    Gerar e Salvar Notificação
                </button>
            </form>
        </div>

        <h2 class="text-2xl font-bold mb-5 text-gray-700 border-b border-gray-200 pb-2">Notificações Emitidas</h2>
        <div class="overflow-x-auto shadow-xl rounded-xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nº/Ano</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Localização</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Modelo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Emissão</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($notificacoes)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 italic">Nenhuma notificação emitida ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notificacoes as $notif): ?>
                        <tr class="hover:bg-blue-50/50 transition duration-100">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($notif['numero_documento']) ?>/<?= date('Y', strtotime($notif['data_emissao'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($notif['logradouro']) ?> - <?= htmlspecialchars($notif['bairro']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($notif['nome_tipo']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= date('d/m/Y', strtotime($notif['data_emissao'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-yellow-100 text-yellow-700">
                                    <?= htmlspecialchars($notif['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="gerar_docx.php?id=<?= $notif['id_notificacao'] ?>" class="text-red-600 hover:text-red-800 font-medium transition duration-150 p-2 rounded-lg hover:bg-red-50">
                                    Gerar DOCX
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>