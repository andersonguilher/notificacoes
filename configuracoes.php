<?php
// configuracoes.php
require_once __DIR__ . '/../../config.php';

$mensagem = '';
$configuracao_atual = [];
$chave_notificacao = 'numero_inicial_notificacao';

// Lógica de SALVAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = isset($_POST['valor']) ? (int)$_POST['valor'] : null;

    if ($valor !== null) {
        try {
            $pdo->beginTransaction();
            // Verifica se existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM configuracoes WHERE chave = ?");
            $stmt_check->execute([$chave_notificacao]);
            
            if ($stmt_check->fetchColumn() > 0) {
                // Atualiza
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
                $stmt->execute([$valor, $chave_notificacao]);
            } else {
                // Insere
                $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?)");
                $stmt->execute([$chave_notificacao, $valor]);
            }
            $pdo->commit();
            $mensagem = "<div class='p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg'>Configuração salva com sucesso!</div>";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensagem = "<div class='p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
    }
}

// Lógica de CARREGAMENTO
try {
    $stmt_config = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
    $stmt_config->execute([$chave_notificacao]);
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    $valor_atual = $config ? (int)$config['valor'] : 1;
} catch (PDOException $e) {
    $valor_atual = 1; 
    $mensagem .= "<div class='p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg'>Aviso: Não foi possível carregar a configuração. Usando valor padrão (1).</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS customizado para a cor institucional */
        .focus-institutional:focus {
            border-color: #003366; 
            --tw-ring-color: #003366;
        }
        /* Para manter a fonte consistente */
        body {
            font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-6xl mx-auto bg-white p-10 rounded-2xl shadow-2xl shadow-gray-300/50">
        <h1 class="text-3xl font-bold mb-4 text-gray-800 pb-2">Configurações do Sistema</h1>
        
        <nav class="flex space-x-4 mb-8 p-3 rounded-xl shadow-lg" style="background-color: #003366;">
            <a href="notificacoes.php" class="py-2 px-4 rounded-lg text-sm font-medium text-white hover:bg-white hover:text-gray-800 transition duration-150">
                Notificações (Início)
            </a>
            <a href="tipos_notificacao.php" class="py-2 px-4 rounded-lg text-sm font-medium text-white hover:bg-white hover:text-gray-800 transition duration-150">
                Gerenciar Modelos
            </a>
            <a href="configuracoes.php" class="py-2 px-4 rounded-lg text-sm font-bold bg-white text-gray-800 transition duration-150 shadow-md">
                Configurações
            </a>
        </nav>
        
        <?= $mensagem ?>

        <div class="border p-6 rounded-lg" style="border-color: #DDE2E7; background-color: #F0F4F8;">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2" style="color: #003366; border-color: #DDE2E7;">Numeração de Documentos</h2>
            
            <p class="mb-4 text-gray-600">
                Define o número inicial para a próxima Notificação a ser gerada. 
                Se o último número emitido for maior que este valor, o sistema usará o próximo número sequencial.
            </p>

            <form method="POST">
                <div class="mb-4">
                    <label for="numero_inicial" class="block text-sm font-medium text-gray-700">Próximo Número a ser Usado:</label>
                    <input type="number" id="numero_inicial" name="valor" required
                           value="<?= $valor_atual ?>" min="1"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus-institutional">
                </div>
                
                <button type="submit"
                        class="w-full text-white py-2 px-4 rounded-md shadow-md transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus-institutional"
                        style="background-color: #003366;" 
                        onmouseover="this.style.backgroundColor='#002244'" 
                        onmouseout="this.style.backgroundColor='#003366'">
                    Salvar Configuração
                </button>
            </form>
        </div>
        
    </div>
</body>
</html>