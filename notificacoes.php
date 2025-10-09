<?php
// notificacoes.php
require_once __DIR__ . '/../../config.php';

$mensagem = '';
$numero_inicial_notificacao = 1; // Valor de fallback padrão
$dados_pre_visualizacao = [];

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
}


// --- LÓGICA DE PRÉ-VISUALIZAÇÃO E CADASTRO ---
$acao_post = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) ? $_POST['acao'] : '';

// 1. Ação: CADASTRAR (Executa o salvamento no banco de dados, vem do formulário de confirmação no modal)
if ($acao_post === 'cadastrar') {
    if (isset($_POST['numero_documento_calculado'])) {
        try {
            $numero_documento = $_POST['numero_documento_calculado'];
            $id_tipo = $_POST['id_tipo'];
            $logradouro = htmlspecialchars($_POST['logradouro']);
            $bairro = htmlspecialchars($_POST['bairro']);
            $prazo_dias = (int)$_POST['prazo_dias'];
            $data_emissao = $_POST['data_emissao'];
            
            $stmt = $pdo->prepare("INSERT INTO notificacoes (id_tipo, numero_documento, logradouro, bairro, prazo_dias, data_emissao) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_tipo, $numero_documento, $logradouro, $bairro, $prazo_dias, $data_emissao]);
            
            header("Location: notificacoes.php?msg=" . urlencode("Notificação No $numero_documento gerada e salva com sucesso!"));
            exit;
            
        } catch (PDOException $e) {
            header("Location: notificacoes.php?msg=" . urlencode("Erro ao salvar notificação: " . $e->getMessage()));
            exit;
        }
    }
}

// 2. Ação: PRE_VISUALIZAR_MODAL (Formulário) OU GET_SAVED_PREVIEW_MODAL (Lista de Ações)
$is_modal_request = ($acao_post === 'pre_visualizar_modal' || $acao_post === 'get_saved_preview_modal');

if ($is_modal_request) {
    
    $dados_pre_visualizacao = [];
    $is_saved_notification = ($acao_post === 'get_saved_preview_modal');
    $modal_error = '';

    if ($is_saved_notification && isset($_POST['id_notificacao']) && is_numeric($_POST['id_notificacao'])) {
        // PATH: Saved Notification Preview
        $id_notificacao = (int)$_POST['id_notificacao'];
        try {
            $stmt_saved = $pdo->prepare("
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
            $stmt_saved->execute([$id_notificacao]);
            $notif_saved = $stmt_saved->fetch(PDO::FETCH_ASSOC);

            if ($notif_saved) {
                 $dados_pre_visualizacao = [
                    'numero_documento'      => $notif_saved['numero_documento'],
                    'id_tipo'               => $notif_saved['id_tipo'],
                    'nome_tipo'             => $notif_saved['nome_tipo'],
                    'logradouro'            => $notif_saved['logradouro'],
                    'bairro'                => $notif_saved['bairro'],
                    'prazo_dias'            => $notif_saved['prazo_dias'],
                    'data_emissao'          => $notif_saved['data_emissao'],
                    'capitulacao_infracao'  => $notif_saved['capitulacao_infracao'],
                    'obrigacao'             => $notif_saved['obrigacao'],
                    'capitulacao_multa'     => $notif_saved['capitulacao_multa'],
                    'qr_code_path'          => $notif_saved['qr_code_path'],
                    'id_notificacao'        => $id_notificacao,
                ];
            } else {
                $modal_error = "Notificação salva não encontrada.";
            }
        } catch (PDOException $e) {
            $modal_error = "Erro ao carregar dados da notificação salva: " . $e->getMessage();
        }

    } elseif ($acao_post === 'pre_visualizar_modal') {
        // PATH: Unsaved Form Preview
        $id_tipo_prev = $_POST['id_tipo'] ?? '';
        $logradouro_prev = htmlspecialchars($_POST['logradouro'] ?? '');
        $bairro_prev = htmlspecialchars($_POST['bairro'] ?? '');
        $prazo_dias_prev = (int)($_POST['prazo_dias'] ?? 30);
        $data_emissao_prev = $_POST['data_emissao'] ?? date('Y-m-d');
        $proximo_numero_documento = str_pad($numero_inicial_notificacao, 3, '0', STR_PAD_LEFT);
        $qr_code_path = ''; // Inicializa a variável

        try {
            $stmt = $pdo->query("SELECT MAX(numero_documento) AS max_numero FROM notificacoes");
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $max_numero_existente = $resultado['max_numero'] ? (int)$resultado['max_numero'] : 0;
            $proximo_numero = max($max_numero_existente + 1, $numero_inicial_notificacao);
            $proximo_numero_documento = str_pad($proximo_numero, 3, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            $modal_error = "Aviso: Não foi possível calcular o número do documento. Usando valor inicial: $proximo_numero_documento";
        }

        $modelo_selecionado = $pdo->prepare("SELECT * FROM tipos_notificacao WHERE id_tipo = ?");
        $modelo_selecionado->execute([$id_tipo_prev]);
        $modelo_dados = $modelo_selecionado->fetch(PDO::FETCH_ASSOC);

        if ($modelo_dados) {
            $dados_pre_visualizacao = [
                'numero_documento'      => $proximo_numero_documento,
                'id_tipo'               => $id_tipo_prev,
                'nome_tipo'             => $modelo_dados['nome_tipo'],
                'logradouro'            => $logradouro_prev,
                'bairro'                => $bairro_prev,
                'prazo_dias'            => $prazo_dias_prev,
                'data_emissao'          => $data_emissao_prev,
                'capitulacao_infracao'  => $modelo_dados['capitulacao_infracao'],
                'obrigacao'             => $modelo_dados['obrigacao'],
                'capitulacao_multa'     => $modelo_dados['capitulacao_multa'],
                'qr_code_path'          => $modelo_dados['qr_code_path'],
            ];
        } else {
            $modal_error = "Modelo de notificação inválido. Selecione um modelo válido.";
        }
    } else {
        $modal_error = "Requisição de pré-visualização inválida ou incompleta.";
    }

    // --- RENDER MODAL CONTENT ---
    
    // Check for errors
    if (!empty($modal_error)) {
        echo "<div class='p-4 bg-red-100 border border-red-400 text-red-700 rounded-xl mb-6'>$modal_error</div>";
        exit;
    }

    // Common rendering part
    $numero_ano = $dados_pre_visualizacao['numero_documento'] . '/' . date('Y', strtotime($dados_pre_visualizacao['data_emissao']));
    $endereco_completo = $dados_pre_visualizacao['logradouro'] . ' - ' . $dados_pre_visualizacao['bairro'];
    $qr_code_path = 'qrcodes/' . $dados_pre_visualizacao['qr_code_path'];
    $qr_code_full_path = __DIR__ . '/' . $qr_code_path;
    $qr_code_found = !empty($dados_pre_visualizacao['qr_code_path']) && file_exists($qr_code_full_path);
    
    // Início da saída do modal (retornada via AJAX)
    ?>
    <div class="p-4 <?= $is_saved_notification ? 'bg-indigo-50/50 border border-indigo-200' : 'bg-green-50/50 border border-green-200' ?> rounded-xl mb-6">
        <h2 class="text-3xl font-bold mb-4 <?= $is_saved_notification ? 'text-indigo-800' : 'text-green-800' ?>">
            <?= $is_saved_notification ? 'Visualização de Notificação Salva' : 'Confirmação de Nova Notificação' ?>
        </h2>
        <p class="text-xl font-bold border-b pb-2">Notificação Nº <span class="text-red-600"><?= htmlspecialchars($numero_ano) ?></span></p>
    </div>
    
    <div class="space-y-4 mb-8 text-gray-700">
        <p><strong>Modelo:</strong> <?= htmlspecialchars($dados_pre_visualizacao['nome_tipo']) ?></p>
        <p><strong>Endereço:</strong> <?= htmlspecialchars($endereco_completo) ?></p>
        <p><strong>Prazo:</strong> <?= htmlspecialchars($dados_pre_visualizacao['prazo_dias']) ?> dias</p>
        <p><strong>Data de Emissão:</strong> <?= date('d/m/Y', strtotime($dados_pre_visualizacao['data_emissao'])) ?></p>

        <h3 class="text-lg font-semibold pt-4 border-t border-gray-200">Conteúdo do Modelo:</h3>
        <p><strong>Capitulação Infração:</strong> <pre class="p-2 bg-gray-50 rounded-lg text-sm whitespace-pre-wrap"><?= htmlspecialchars($dados_pre_visualizacao['capitulacao_infracao']) ?></pre></p>
        <p><strong>Obrigação:</strong> <pre class="p-2 bg-gray-50 rounded-lg text-sm whitespace-pre-wrap"><?= htmlspecialchars($dados_pre_visualizacao['obrigacao']) ?></pre></p>
        <p><strong>Capitulação Multa:</strong> <pre class="p-2 bg-gray-50 rounded-lg text-sm whitespace-pre-wrap"><?= htmlspecialchars($dados_pre_visualizacao['capitulacao_multa']) ?></pre></p>
    </div>

    <div class="p-4 rounded-xl border <?= $qr_code_found ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' ?> mb-6">
        <p class="text-sm font-semibold">Status do QR Code (Placeholder: QR_CODE):</p>
        <p class="text-xs mt-1">Caminho esperado: `<?= htmlspecialchars($dados_pre_visualizacao['qr_code_path']) ?>`</p>
        <?php if ($qr_code_found): ?>
            <p class="text-green-700 font-medium mt-2">Arquivo de QR Code encontrado e será inserido no DOCX.</p>
        <?php else: ?>
            <p class="text-red-700 font-medium mt-2">AVISO: Arquivo de QR Code NÃO encontrado. O DOCX usará texto de fallback.</p>
        <?php endif; ?>
    </div>

    <?php if (!$is_saved_notification): ?>
        <form id="confirmSaveForm" method="POST">
            <input type="hidden" name="acao" value="cadastrar">
            <input type="hidden" name="numero_documento_calculado" value="<?= htmlspecialchars($dados_pre_visualizacao['numero_documento']) ?>">
            <input type="hidden" name="id_tipo" value="<?= htmlspecialchars($dados_pre_visualizacao['id_tipo']) ?>">
            <input type="hidden" name="logradouro" value="<?= htmlspecialchars($dados_pre_visualizacao['logradouro']) ?>">
            <input type="hidden" name="bairro" value="<?= htmlspecialchars($dados_pre_visualizacao['bairro']) ?>">
            <input type="hidden" name="prazo_dias" value="<?= htmlspecialchars($dados_pre_visualizacao['prazo_dias']) ?>">
            <input type="hidden" name="data_emissao" value="<?= htmlspecialchars($dados_pre_visualizacao['data_emissao']) ?>">
            
            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-xl text-lg font-semibold hover:bg-green-700 transition duration-300 shadow-lg">
                CONFIRMAR E SALVAR
            </button>
        </form>
    <?php else: ?>
        <p class="mb-4 text-center text-sm text-gray-500">Esta notificação já está salva. Você pode visualizá-la e baixar o documento DOCX:</p>
        <a href="gerar_docx.php?id=<?= $dados_pre_visualizacao['id_notificacao'] ?>" class="block w-full text-center bg-indigo-600 text-white py-3 px-4 rounded-xl text-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-lg">
            BAIXAR DOCX
        </a>
    <?php endif; ?>

    <?php
    exit; // Termina a execução para enviar apenas o HTML do modal
}


// --- LÓGICA DE CONSULTA DA LISTA (Normal para o corpo da página) ---
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

// Valores padrão para o formulário (sem persistência)
$valor_id_tipo = '';
$valor_logradouro = '';
$valor_bairro = '';
$valor_prazo_dias = '30';
$valor_data_emissao = date('Y-m-d');

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
        .overflow-hidden {
            overflow: hidden; /* Para travar o scroll do body quando o modal estiver aberto */
        }
        pre {
            font-family: inherit;
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
            <form id="newNotificationForm" method="POST">
                <input type="hidden" name="acao" value="pre_visualizar"> 
                
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

                <button type="button" id="previewFormButton" class="mt-8 w-full bg-indigo-600 text-white py-3 px-4 rounded-xl text-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-lg hover:shadow-xl">
                    Pré-visualizar e Confirmar Geração
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
                                    <?= htmlspecialchars($notif['status'] ?? 'Emitida') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button type="button" onclick="showSavedPreview(<?= $notif['id_notificacao'] ?>)" class="text-blue-600 hover:text-blue-800 font-medium transition duration-150 p-2 rounded-lg hover:bg-blue-50">
                                    Pré-visualizar
                                </button>
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

    <div id="previewModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-75 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative transform transition-all">
                
                <button type="button" onclick="closeModal()" class="absolute top-4 right-4 text-red-500 hover:text-red-700 p-2 rounded-full bg-white hover:bg-red-50 transition duration-150 z-10 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div id="modalContent" class="p-8">
                    </div>
                
                </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('previewModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden'); // Evita scroll do fundo
        }

        function closeModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden'); // Restaura scroll do fundo
            document.getElementById('modalContent').innerHTML = ''; // Limpa o conteúdo
        }

        // Função para pré-visualizar notificações JÁ SALVAS (chamada pelo botão na lista)
        function showSavedPreview(idNotificacao) {
            const formData = new FormData();
            formData.set('acao', 'get_saved_preview_modal'); // Ação para carregar dados salvos
            formData.set('id_notificacao', idNotificacao);
            
            // 2. Fazer o fetch para o servidor
            fetch('notificacoes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
                openModal();
            })
            .catch(error => {
                console.error('Erro ao pré-visualizar notificação salva:', error);
                alert('Erro ao carregar pré-visualização da notificação salva.');
            });
        }


        document.addEventListener('DOMContentLoaded', function() {
            const previewButton = document.getElementById('previewFormButton');
            const form = document.getElementById('newNotificationForm');
            
            // Lógica para o formulário de NOVA notificação
            previewButton.addEventListener('click', function(event) {
                event.preventDefault();
                
                // Validação simples (garante que os campos 'required' não estão vazios antes de enviar)
                if (!form.reportValidity()) {
                    return; 
                }

                // 1. Coletar os dados do formulário
                const formData = new FormData(form);
                formData.set('acao', 'pre_visualizar_modal'); // Ação para a lógica PHP
                
                // 2. Fazer o fetch para o servidor
                fetch('notificacoes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // 3. Inserir o HTML retornado no modal e abri-lo
                    document.getElementById('modalContent').innerHTML = html;
                    openModal();
                    
                    // 4. Capturar o evento de submissão do formulário de confirmação DENTRO do modal
                    const confirmForm = document.getElementById('confirmSaveForm');
                    if (confirmForm) {
                        confirmForm.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const confirmFormData = new FormData(confirmForm);
                            
                            // Envia para a ação 'cadastrar' real
                            fetch('notificacoes.php', {
                                method: 'POST',
                                body: confirmFormData
                            })
                            .then(response => {
                                // O PHP redireciona com mensagem, o JS apenas segue
                                window.location.href = response.url;
                            })
                            .catch(error => {
                                console.error('Erro ao salvar:', error);
                                closeModal();
                                window.location.href = 'notificacoes.php?msg=' + encodeURIComponent("Erro de comunicação ao salvar notificação.");
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao pré-visualizar:', error);
                    alert('Erro ao carregar pré-visualização. Verifique o console para detalhes.');
                });
            });
        });
    </script>
</body>
</html>