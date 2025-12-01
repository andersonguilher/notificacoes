<?php
// tipos_notificacao.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../../db.php'; 

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

$mensagem = '';

// Variáveis para preencher o formulário
$id_editar = '';
$nome_tipo_val = '';
$cap_infracao_val = '';
$cap_multa_val = '';
// $obrigacao_val removida pois não existe mais no modelo
$prazo_val = '30';
$acao_formulario = 'cadastrar';
$texto_botao = 'Salvar Modelo e Gerar QR Code';
$classe_botao = 'btn-success';

// --- 1. LÓGICA DE EXCLUSÃO ---
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    try {
        $id_excluir = $_GET['id'];
        
        $stmt = $pdo_notificacoes->prepare("SELECT qr_code_path, caminho_pdf FROM tipos_notificacao WHERE id_tipo = ?");
        $stmt->execute([$id_excluir]);
        $dados_arq = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados_arq) {
            $stmtDel = $pdo_notificacoes->prepare("DELETE FROM tipos_notificacao WHERE id_tipo = ?");
            $stmtDel->execute([$id_excluir]);

            if (!empty($dados_arq['qr_code_path'])) {
                $file_qr = __DIR__ . '/qrcodes/' . $dados_arq['qr_code_path'];
                if (file_exists($file_qr)) unlink($file_qr);
            }
            if (!empty($dados_arq['caminho_pdf'])) {
                $file_pdf = __DIR__ . '/' . $dados_arq['caminho_pdf'];
                if (file_exists($file_pdf)) unlink($file_pdf);
            }

            $mensagem = "<div class='alert alert-success'>Modelo excluído com sucesso!</div>";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $mensagem = "<div class='alert alert-warning'><strong>Não é possível excluir:</strong> Este modelo já está a ser utilizado em notificações emitidas.</div>";
        } else {
            $mensagem = "<div class='alert alert-danger'>Erro ao excluir: " . $e->getMessage() . "</div>";
        }
    }
}

// --- 2. CARREGAR DADOS PARA EDIÇÃO ---
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    try {
        $stmt = $pdo_notificacoes->prepare("SELECT * FROM tipos_notificacao WHERE id_tipo = ?");
        $stmt->execute([$_GET['id']]);
        $dados_editar = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados_editar) {
            $id_editar = $dados_editar['id_tipo'];
            $nome_tipo_val = $dados_editar['nome_tipo'];
            $cap_infracao_val = $dados_editar['capitulacao_infracao'];
            $cap_multa_val = $dados_editar['capitulacao_multa'];
            $prazo_val = $dados_editar['prazo_dias'] ?? '30';
            
            $acao_formulario = 'atualizar';
            $texto_botao = 'Atualizar Modelo';
            $classe_botao = 'btn-primary';
        }
    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro ao buscar dados: " . $e->getMessage() . "</div>";
    }
}

// --- 3. PROCESSAMENTO (CADASTRAR / ATUALIZAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    try {
        $pdo_notificacoes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $nome_tipo = $_POST['nome_tipo'];
        $cap_infracao = $_POST['capitulacao_infracao'];
        $cap_multa = $_POST['capitulacao_multa'];
        // Obrigação removida daqui
        $prazo = $_POST['prazo_dias'];
        
        $nome_qrcode_db = null;
        $caminho_pdf_db = null;
        $token_pdf = null;
        $atualizar_arquivos = false;

        // UPLOAD PDF
        if (isset($_FILES['pdf_anexo']) && $_FILES['pdf_anexo']['error'] === UPLOAD_ERR_OK) {
            
            $dir_pdfs = __DIR__ . '/uploads_pdfs/';
            $dir_qrcodes = __DIR__ . '/qrcodes/';
            if (!is_dir($dir_pdfs)) mkdir($dir_pdfs, 0755, true);
            if (!is_dir($dir_qrcodes)) mkdir($dir_qrcodes, 0755, true);

            $ext = strtolower(pathinfo($_FILES['pdf_anexo']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') throw new Exception("O ficheiro deve ser um PDF.");

            $token_pdf = bin2hex(random_bytes(16));
            $nome_pdf = 'doc_' . time() . '_' . $token_pdf . '.pdf';
            if (!move_uploaded_file($_FILES['pdf_anexo']['tmp_name'], $dir_pdfs . $nome_pdf)) {
                throw new Exception("Falha ao salvar PDF.");
            }
            $caminho_pdf_db = 'uploads_pdfs/' . $nome_pdf;

            $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $pasta_atual = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $url_destino = "{$protocolo}://{$host}{$pasta_atual}/ver_documento.php?t={$token_pdf}";

            if (extension_loaded('imagick')) {
                $backend = new ImagickImageBackEnd();
                $ext_img = 'png';
            } else {
                $backend = new SvgImageBackEnd();
                $ext_img = 'svg';
            }

            $renderer = new ImageRenderer(new RendererStyle(400), $backend);
            $writer = new Writer($renderer);
            
            $nome_qrcode_db = 'qr_' . time() . '_' . uniqid() . '.' . $ext_img;
            $writer->writeFile($url_destino, $dir_qrcodes . $nome_qrcode_db);
            
            $atualizar_arquivos = true;
        }

        if ($_POST['acao'] === 'cadastrar') {
            // Removido campo 'obrigacao' do INSERT
            $stmt = $pdo_notificacoes->prepare("
                INSERT INTO tipos_notificacao 
                (nome_tipo, capitulacao_infracao, capitulacao_multa, prazo_dias, qr_code_path, caminho_pdf, token_pdf)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nome_tipo, $cap_infracao, $cap_multa, $prazo, $nome_qrcode_db, $caminho_pdf_db, $token_pdf]);
            $mensagem = "<div class='alert alert-success'>Modelo cadastrado com sucesso!</div>";
        } 
        elseif ($_POST['acao'] === 'atualizar') {
            $id = $_POST['id_tipo'];
            
            if ($atualizar_arquivos) {
                // Apaga antigos
                $stmtOld = $pdo_notificacoes->prepare("SELECT qr_code_path, caminho_pdf FROM tipos_notificacao WHERE id_tipo = ?");
                $stmtOld->execute([$id]);
                $oldFiles = $stmtOld->fetch(PDO::FETCH_ASSOC);
                
                if ($oldFiles['qr_code_path'] && file_exists(__DIR__ . '/qrcodes/' . $oldFiles['qr_code_path'])) unlink(__DIR__ . '/qrcodes/' . $oldFiles['qr_code_path']);
                if ($oldFiles['caminho_pdf'] && file_exists(__DIR__ . '/' . $oldFiles['caminho_pdf'])) unlink(__DIR__ . '/' . $oldFiles['caminho_pdf']);

                // Removido 'obrigacao' do UPDATE
                $sql = "UPDATE tipos_notificacao SET 
                        nome_tipo=?, capitulacao_infracao=?, capitulacao_multa=?, prazo_dias=?, 
                        qr_code_path=?, caminho_pdf=?, token_pdf=? 
                        WHERE id_tipo=?";
                $params = [$nome_tipo, $cap_infracao, $cap_multa, $prazo, $nome_qrcode_db, $caminho_pdf_db, $token_pdf, $id];
            } else {
                // Removido 'obrigacao' do UPDATE
                $sql = "UPDATE tipos_notificacao SET 
                        nome_tipo=?, capitulacao_infracao=?, capitulacao_multa=?, prazo_dias=? 
                        WHERE id_tipo=?";
                $params = [$nome_tipo, $cap_infracao, $cap_multa, $prazo, $id];
            }
            
            $stmt = $pdo_notificacoes->prepare($sql);
            $stmt->execute($params);
            
            $mensagem = "<div class='alert alert-primary'>Modelo atualizado com sucesso!</div>";
            
            $id_editar = '';
            $nome_tipo_val = '';
            $cap_infracao_val = '';
            $cap_multa_val = '';
            $prazo_val = '30';
            $acao_formulario = 'cadastrar';
            $texto_botao = 'Salvar Modelo e Gerar QR Code';
            $classe_botao = 'btn-success';
        }

    } catch (Exception $e) {
        $mensagem = "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
    }
}

// --- LISTAGEM ---
$stmt = $pdo_notificacoes->query("SELECT * FROM tipos_notificacao ORDER BY id_tipo DESC");
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tipos de Notificação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .qr-thumb { width: 60px; height: 60px; object-fit: contain; border: 1px solid #ddd; padding: 2px; background: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tipos de Notificação (Modelos)</h2>
        <a href="notificacoes.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php echo $mensagem; ?>

    <div class="card mb-4">
        <div class="card-header text-white <?php echo ($acao_formulario == 'atualizar') ? 'bg-warning' : 'bg-primary'; ?>">
            <h5 class="mb-0">
                <?php 
                echo ($acao_formulario == 'atualizar') 
                    ? 'Editando Modelo #' . $id_editar . ' - ' . htmlspecialchars($nome_tipo_val) 
                    : 'Cadastrar Novo Modelo'; 
                ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="<?php echo $acao_formulario; ?>">
                <?php if($id_editar): ?>
                    <input type="hidden" name="id_tipo" value="<?php echo $id_editar; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-9 mb-3">
                        <label class="form-label fw-bold">Nome do Tipo:</label>
                        <input type="text" name="nome_tipo" class="form-control" placeholder="Ex: Som Alto" value="<?php echo htmlspecialchars($nome_tipo_val); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Prazo Padrão (dias):</label>
                        <input type="number" name="prazo_dias" class="form-control" value="<?php echo htmlspecialchars($prazo_val); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capitulação da Infração:</label>
                        <textarea name="capitulacao_infracao" class="form-control" rows="2"><?php echo htmlspecialchars($cap_infracao_val); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capitulação da Multa:</label>
                        <textarea name="capitulacao_multa" class="form-control" rows="2"><?php echo htmlspecialchars($cap_multa_val); ?></textarea>
                    </div>
                </div>

                <div class="mb-3 p-3 bg-light border rounded">
                    <label class="form-label fw-bold text-primary">
                        <?php echo ($acao_formulario == 'atualizar') ? 'Substituir Documento (PDF) - Opcional' : 'Anexar Documento (PDF)'; ?>
                    </label>
                    <input type="file" name="pdf_anexo" class="form-control" accept="application/pdf">
                    <div class="form-text">
                        O sistema irá gerar automaticamente um <strong>QR Code</strong> que aponta para este documento.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn <?php echo $classe_botao; ?> flex-grow-1"><?php echo $texto_botao; ?></button>
                    <?php if ($acao_formulario == 'atualizar'): ?>
                        <a href="tipos_notificacao.php" class="btn btn-outline-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Modelos Cadastrados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Prazo</th>
                            <th>QR Code</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tipos) > 0): ?>
                            <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td><?php echo $tipo['id_tipo']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tipo['nome_tipo']); ?></strong><br>
                                    <small class="text-muted"><?php echo mb_substr($tipo['capitulacao_infracao'], 0, 50, 'UTF-8') . '...'; ?></small>
                                </td>
                                <td><?php echo isset($tipo['prazo_dias']) ? $tipo['prazo_dias'] : '-'; ?> dias</td>
                                <td>
                                    <?php 
                                    $caminho_qr = __DIR__ . '/qrcodes/' . ($tipo['qr_code_path'] ?? '');
                                    if (!empty($tipo['qr_code_path']) && file_exists($caminho_qr)): 
                                    ?>
                                        <a href="qrcodes/<?php echo $tipo['qr_code_path']; ?>" target="_blank">
                                            <img src="qrcodes/<?php echo $tipo['qr_code_path']; ?>" class="qr-thumb" alt="QR">
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sem QR</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="tipos_notificacao.php?acao=editar&id=<?php echo $tipo['id_tipo']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">Editar</a>
                                        <a href="tipos_notificacao.php?acao=excluir&id=<?php echo $tipo['id_tipo']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza?');" title="Excluir">Excluir</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center p-3">Nenhum modelo encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>