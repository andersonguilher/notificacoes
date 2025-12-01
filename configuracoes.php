<?php
// configuracoes.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php'; 

$mensagem = '';

// --- PROCESSAR SALVAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $novo_numero = (int)$_POST['numero_inicial'];
        
        // Verifica se a configuração já existe
        $stmt = $pdo_notificacoes->prepare("SELECT COUNT(*) FROM configuracoes WHERE chave = 'numero_inicial_notificacao'");
        $stmt->execute();
        $existe = $stmt->fetchColumn();

        if ($existe) {
            $stmtUpdate = $pdo_notificacoes->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'numero_inicial_notificacao'");
            $stmtUpdate->execute([$novo_numero]);
        } else {
            $stmtInsert = $pdo_notificacoes->prepare("INSERT INTO configuracoes (chave, valor) VALUES ('numero_inicial_notificacao', ?)");
            $stmtInsert->execute([$novo_numero]);
        }

        $mensagem = "<div class='alert alert-success'>Configuração salva com sucesso!</div>";

    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro ao salvar: " . $e->getMessage() . "</div>";
    }
}

// --- CARREGAR VALOR ATUAL ---
try {
    $stmt = $pdo_notificacoes->query("SELECT valor FROM configuracoes WHERE chave = 'numero_inicial_notificacao'");
    $valor_atual = $stmt->fetchColumn();
    if (!$valor_atual) $valor_atual = 146; // Valor padrão se não existir
} catch (PDOException $e) {
    $mensagem = "<div class='alert alert-danger'>Erro ao ler configurações: " . $e->getMessage() . "</div>";
    $valor_atual = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Configurações</h2>
                <a href="notificacoes.php" class="btn btn-secondary">Voltar</a>
            </div>

            <?php echo $mensagem; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-secondary">Numeração de Documentos</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="numero_inicial" class="form-label fw-bold">Número Inicial da Notificação</label>
                            <input type="number" name="numero_inicial" id="numero_inicial" class="form-control form-control-lg" value="<?php echo htmlspecialchars($valor_atual); ?>" required>
                            <div class="form-text text-muted">
                                Defina qual será o número usado caso não haja nenhuma notificação no banco. <br>
                                <em>Se já existirem notificações, o sistema ignorará este campo e usará o último número + 1.</em>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>