<?php
// notificacoes.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once __DIR__ . '/../../db.php'; 

$mensagem = '';

// --- LÓGICA DE EXCLUSÃO ---
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    try {
        $id_excluir = (int)$_GET['id'];

        $stmtMax = $pdo_notificacoes->query("SELECT MAX(id_notificacao) FROM notificacoes");
        $ultimo_id_real = (int)$stmtMax->fetchColumn();

        if ($id_excluir !== $ultimo_id_real) {
            throw new Exception("Medida de Segurança: Apenas a última notificação emitida pode ser excluída.");
        }

        $stmtDel = $pdo_notificacoes->prepare("DELETE FROM notificacoes WHERE id_notificacao = ?");
        $stmtDel->execute([$id_excluir]);

        $mensagem = "<div class='alert alert-success mt-3'>Notificação #$id_excluir excluída com sucesso!</div>";

    } catch (Exception $e) {
        $mensagem = "<div class='alert alert-danger mt-3'>Erro: " . $e->getMessage() . "</div>";
    }
}

// --- CONSULTAS ---
$stmtLast = $pdo_notificacoes->query("SELECT MAX(id_notificacao) FROM notificacoes");
$id_permitido_exclusao = (int)$stmtLast->fetchColumn();

try {
    $sql = "
        SELECT 
            n.id_notificacao,
            n.numero_documento,
            n.nome_proprietario,
            n.logradouro,
            n.bairro,
            n.data_emissao,
            n.status,
            n.protocolo_1746, 
            t.nome_tipo
        FROM notificacoes n
        LEFT JOIN tipos_notificacao t ON n.id_tipo = t.id_tipo
        ORDER BY n.id_notificacao DESC
    ";
    $stmt = $pdo_notificacoes->query($sql);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notificacoes = [];
    $mensagem = "<div class='alert alert-danger mt-3'>Erro ao carregar lista: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Notificações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 0; }
        .header-fixo {
            position: sticky; top: 0; z-index: 1020;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .container-conteudo { padding: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-action { margin-right: 5px; }
        .badge-protocolo { font-size: 0.85em; background-color: #e9ecef; color: #495057; border: 1px solid #ced4da; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    
    <div class="header-fixo d-flex justify-content-between align-items-center">
        <h2 class="m-0">Notificações Emitidas</h2>
        <div>
            <a href="configuracoes.php" class="btn btn-outline-secondary me-2">
                🔧 Configurações
            </a>
            
            <a href="tipos_notificacao.php" class="btn btn-outline-dark me-2">
                ⚙️ Gerenciar Modelos
            </a>
            <a href="nova_notificacao.php" class="btn btn-primary">
                + Nova Notificação
            </a>
        </div>
    </div>

    <div class="container-conteudo">
        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Histórico</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nº Doc</th>
                                <th>Data</th>
                                <th>Protocolo 1746</th>
                                <th>Tipo / Infração</th>
                                <th>Local</th>
                                <th>Status</th>
                                <th class="text-end" style="min-width: 220px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($notificacoes) > 0): ?>
                                <?php foreach ($notificacoes as $notif): ?>
                                    <?php 
                                        $data_br = date('d/m/Y', strtotime($notif['data_emissao']));
                                        $pode_excluir = ($notif['id_notificacao'] === $id_permitido_exclusao);
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-primary">
                                            <?php echo htmlspecialchars($notif['numero_documento']); ?>
                                            <div style="font-size: 0.75rem; color: #999;">ID: <?php echo $notif['id_notificacao']; ?></div>
                                        </td>
                                        <td><?php echo $data_br; ?></td>
                                        <td>
                                            <?php if (!empty($notif['protocolo_1746'])): ?>
                                                <span class="badge badge-protocolo">
                                                    <?php echo htmlspecialchars($notif['protocolo_1746']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($notif['nome_tipo'] ?? 'Desconhecido'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($notif['logradouro']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($notif['bairro']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($notif['status'] == 'Emitida') ? 'warning' : 'success'; ?>">
                                                <?php echo htmlspecialchars($notif['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="visualizar_notificacao.php?id=<?php echo $notif['id_notificacao']; ?>" class="btn btn-sm btn-info btn-action text-white" target="_blank" title="Ver">👁️ Ver</a>
                                            <a href="gerar_docx.php?id=<?php echo $notif['id_notificacao']; ?>" class="btn btn-sm btn-outline-primary btn-action" title="DOCX">DOCX</a>
                                            <?php if ($pode_excluir): ?>
                                                <a href="notificacoes.php?acao=excluir&id=<?php echo $notif['id_notificacao']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('ATENÇÃO: Excluir?');">Excluir</a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary btn-action" disabled>Excluir</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center p-4">Nenhuma notificação encontrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>