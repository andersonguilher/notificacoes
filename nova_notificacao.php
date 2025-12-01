<?php
// nova_notificacao.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once __DIR__ . '/../../db.php';

$mensagem = '';

// --- LÓGICA DE GERAÇÃO DO PRÓXIMO NÚMERO ---
function proximoNumeroNotificacao($pdo) {
    $stmt = $pdo->query("SELECT MAX(CAST(numero_documento AS UNSIGNED)) FROM notificacoes");
    $ultimo = $stmt->fetchColumn();

    if ($ultimo) {
        return $ultimo + 1;
    } else {
        $stmtConf = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'numero_inicial_notificacao'");
        $inicial = $stmtConf->fetchColumn();
        return $inicial ? $inicial : 1; 
    }
}

// --- PROCESSAR O FORMULÁRIO (SALVAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo_notificacoes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id_tipo = $_POST['id_tipo'];
        $logradouro = $_POST['logradouro'];
        $bairro = $_POST['bairro'];
        $protocolo = $_POST['protocolo_1746'] ?? '';
        $prazo = $_POST['prazo_dias'];
        
        // OBRIGAÇÃO AGORA É MANDATÓRIA (Vem do POST)
        if (empty($_POST['obrigacao'])) {
            throw new Exception("O campo 'Obrigação do Notificado' é obrigatório.");
        }
        $obrigacao_final = mb_strtoupper($_POST['obrigacao'], 'UTF-8'); // Salva em Maiúsculas

        $data_emissao = date('Y-m-d');
        $proximo_numero = proximoNumeroNotificacao($pdo_notificacoes);

        // NOME DO PROPRIETÁRIO FOI REMOVIDO DO FORMULÁRIO
        // Passamos uma string vazia '' para o banco aceitar (já que a coluna é NOT NULL na estrutura antiga)
        $nome_proprietario_dummy = '';

        $stmt = $pdo_notificacoes->prepare("
            INSERT INTO notificacoes 
            (numero_documento, id_tipo, nome_proprietario, logradouro, bairro, prazo_dias, data_emissao, status, obrigacao, protocolo_1746)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Emitida', ?, ?)
        ");

        $stmt->execute([
            $proximo_numero,
            $id_tipo,
            $nome_proprietario_dummy, // Valor vazio
            mb_strtoupper($logradouro, 'UTF-8'),
            $bairro,
            $prazo,
            $data_emissao,
            $obrigacao_final,
            $protocolo
        ]);

        header("Location: notificacoes.php?msg=sucesso");
        exit;

    } catch (Exception $e) {
        $mensagem = "<div class='alert alert-danger'>Erro ao salvar: " . $e->getMessage() . "</div>";
    }
}

// --- CARREGAR TIPOS ---
try {
    $stmt = $pdo_notificacoes->query("SELECT * FROM tipos_notificacao WHERE ativo = 1 ORDER BY nome_tipo ASC");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tipos = [];
    $mensagem = "<div class='alert alert-danger'>Erro ao carregar tipos: " . $e->getMessage() . "</div>";
}

$numero_sugerido = proximoNumeroNotificacao($pdo_notificacoes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Notificação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Emitir Nova Notificação</h2>
        <a href="notificacoes.php" class="btn btn-secondary">Cancelar e Voltar</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Dados da Notificação #<?php echo $numero_sugerido; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                
                <div class="mb-4">
                    <label for="id_tipo" class="form-label fw-bold">1. Selecione o Modelo de Infração *</label>
                    <select name="id_tipo" id="id_tipo" class="form-select" required onchange="atualizarPrazo(this)">
                        <option value="">-- Escolha um modelo --</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo']; ?>" data-prazo="<?php echo $tipo['prazo_dias']; ?>">
                                <?php echo htmlspecialchars($tipo['nome_tipo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>

                <h6 class="text-secondary mb-3">2. O que o cidadão deve fazer?</h6>
                <div class="mb-4">
                    <label class="form-label fw-bold text-danger">Obrigação do Notificado *</label>
                    <textarea name="obrigacao" class="form-control border-danger" rows="3" placeholder="EX: RETIRAR OBSTÁCULOS (VASOS DE PLANTA) DA CALÇADA." required></textarea>
                    <div class="form-text">Escreva a ordem direta que aparecerá no documento.</div>
                </div>

                <hr>

                <h6 class="text-secondary mb-3">3. Local da Infração</h6>
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Endereço (Logradouro e Número) *</label>
                        <input type="text" name="logradouro" class="form-control" placeholder="Ex: Rua A, 123" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bairro *</label>
                        <select name="bairro" class="form-select" required>
                            <option value="Campo Grande">Campo Grande</option>
                            <option value="Senador Vasconcelos">Senador Vasconcelos</option>
                            <option value="Santíssimo">Santíssimo</option>
                            <option value="Inhoaíba">Inhoaíba</option>
                            <option value="Cosmos">Cosmos</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Protocolo 1746 (Opcional)</label>
                        <input type="text" name="protocolo_1746" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prazo para Cumprimento (Dias)</label>
                        <input type="number" name="prazo_dias" id="prazo_dias" class="form-control" value="30">
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg">Emitir Notificação</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function atualizarPrazo(select) {
        var option = select.options[select.selectedIndex];
        var prazo = option.getAttribute('data-prazo');
        if (prazo) {
            document.getElementById('prazo_dias').value = prazo;
        }
    }
</script>

</body>
</html>