<?php
// ver_documento.php
// Este arquivo deve ficar na mesma pasta que o cadastrar_tipo.php
require_once __DIR__ . '/../../db.php';

if (!isset($_GET['t'])) {
    die("Documento não especificado.");
}

$token = $_GET['t'];

try {
    if (!isset($pdo_notificacoes)) {
        throw new Exception("Erro de conexão com banco de dados.");
    }

    $pdo_notificacoes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Busca o caminho do PDF baseando-se no token
    $stmt = $pdo_notificacoes->prepare("SELECT caminho_pdf FROM tipos_notificacao WHERE token_pdf = ?");
    $stmt->execute([$token]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultado || empty($resultado['caminho_pdf'])) {
        die("Documento não encontrado ou link inválido.");
    }

    // Monta o caminho físico completo
    $caminho_arquivo = __DIR__ . '/' . $resultado['caminho_pdf'];

    if (!file_exists($caminho_arquivo)) {
        die("Erro: O arquivo físico não foi encontrado no servidor.");
    }

    // Força o navegador a abrir o PDF (inline)
    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"documento_legal.pdf\"");
    header("Content-Length: " . filesize($caminho_arquivo));
    readfile($caminho_arquivo);
    exit;

} catch (Exception $e) {
    die("Erro interno: " . $e->getMessage());
}
?>