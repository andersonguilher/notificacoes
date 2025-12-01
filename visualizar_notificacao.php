<?php
// visualizar_notificacao.php
require 'vendor/autoload.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}

$id = $_GET['id'];

try {
    $pdo_notificacoes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo_notificacoes->prepare("
        SELECT 
            n.*, 
            t.nome_tipo,
            t.capitulacao_infracao, 
            t.capitulacao_multa,
            t.qr_code_path  
        FROM notificacoes n
        JOIN tipos_notificacao t ON n.id_tipo = t.id_tipo
        WHERE n.id_notificacao = ?
    ");
    $stmt->execute([$id]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notif) {
        die("Notificação não encontrada.");
    }

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// --- FORMATAÇÃO ---
$meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$mes_numero = (int)date('m', strtotime($notif['data_emissao']));
$data_extenso = 'Rio de Janeiro, ' . date('d', strtotime($notif['data_emissao'])) . ' de ' . $meses[$mes_numero] . ' de ' . date('Y', strtotime($notif['data_emissao']));
$ano_atual = date('Y', strtotime($notif['data_emissao']));
$numero_doc_formatado = $notif['numero_documento'] . '/' . $ano_atual;

// Logo
$caminho_logo = 'logo.png'; 
$logo_base64 = '';
if (file_exists($caminho_logo)) {
    $type = pathinfo($caminho_logo, PATHINFO_EXTENSION);
    $data = file_get_contents($caminho_logo);
    $logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Notificação <?php echo $notif['numero_documento']; ?></title>
    <style>
        body { 
            background: #525659; 
            display: flex; 
            justify-content: center; 
            padding: 20px; 
            font-family: 'Times New Roman', Times, serif; 
            margin: 0;
        }
        
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 25mm 15mm 25mm; 
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            position: relative;
            box-sizing: border-box;
            color: #000;
            display: flex;
            flex-direction: column;
        }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; width: 100%; height: auto; margin: 0; padding: 15mm 25mm; }
            .no-print { display: none !important; }
            -webkit-print-color-adjust: exact; 
        }

        /* --- CABEÇALHO --- */
        .header-rio {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .header-rio img { height: 65px; margin-bottom: 5px; }
        .header-rio .prefeitura { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
        .header-rio .secretaria { font-size: 10pt; font-weight: bold; }
        .header-rio .coordenadoria { font-size: 9pt; font-weight: bold; }

        /* --- CORPO --- */
        .titulo-notificacao {
            font-weight: bold;
            font-size: 12pt;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .destinatario { font-weight: bold; font-size: 11pt; margin-bottom: 15px; }
        p { font-size: 11pt; line-height: 1.4; text-align: justify; margin-bottom: 15px; text-indent: 0; }
        .campo-dinamico { font-weight: bold; text-decoration: underline; }
        .data-local { margin-top: 20px; margin-bottom: 30px; }

        /* --- ASSINATURA GERENTE (CENTRALIZADA) --- */
        .assinatura-gerente-box {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .assinatura-gerente-box strong { font-size: 11pt; display: block; }
        .assinatura-gerente-box span { font-size: 10pt; display: block; }

        /* --- RECIBO E QR CODE (RODAPÉ) --- */
        .rodape-container {
            margin-top: auto; 
            border-top: 1px dashed #000;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .recibo-box {
            width: 70%;
            font-size: 10pt;
        }
        .titulo-recibo { font-weight: bold; margin-bottom: 10px; display: block; }
        
        .linha-input {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-end;
        }
        .label { white-space: nowrap; margin-right: 5px; }
        .linha { border-bottom: 1px solid #000; flex-grow: 1; height: 1px; }

        .qr-box {
            width: 25%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-box img { width: 90px; height: 90px; border: 1px solid #ccc; margin-bottom: 5px; }
        .qr-legenda { font-size: 8pt; font-weight: bold; }

        /* --- BOTÕES FLUTUANTES (Updated) --- */
        .toolbar {
            position: fixed; top: 20px; right: 20px; display: flex; gap: 10px;
            background: rgba(255,255,255,0.9); padding: 10px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .btn { padding: 10px 15px; border-radius: 4px; text-decoration: none; font-family: sans-serif; font-weight: bold; border: none; cursor: pointer; display: inline-flex; align-items: center; }
        
        /* Botão Download (Azul) */
        .btn-download { background: #007bff; color: white; }
        .btn-download:hover { background: #0056b3; }

        /* Botão Fechar (Vermelho) */
        .btn-close { background: #dc3545; color: white; }
        .btn-close:hover { background: #a71d2a; }

    </style>
</head>
<body>

    <div class="no-print toolbar">
        <a href="gerar_docx.php?id=<?php echo $notif['id_notificacao']; ?>" class="btn btn-download">
            📄 Baixar DOCX
        </a>
        
        <a href="javascript:window.close()" class="btn btn-close">
            ✖ Fechar
        </a>
    </div>

    <div class="page">
        
        <div class="header-rio">
            <?php if ($logo_base64): ?>
                <img src="<?php echo $logo_base64; ?>" alt="Brasão">
            <?php else: ?>
                <div style="height: 60px;"></div>
            <?php endif; ?>
            <div class="prefeitura">PREFEITURA DA CIDADE DO RIO DE JANEIRO</div>
            <div class="secretaria">Secretaria Municipal de Conservação e Serviços Públicos</div>
            <div class="coordenadoria">Coordenadoria Geral de Engenharia e Conservação</div>
        </div>

        <div class="titulo-notificacao">
            Notificação SC/SUBEC/CGEC/ 4.2ªCRC/22ª GC Nº <?php echo $numero_doc_formatado; ?>
        </div>

        <div class="destinatario">
            Ao Sr.<br>
            PROPRIETÁRIO OU RESPONSÁVEL
        </div>

        <p>
            O Senhor Gerente da 22ª GERÊNCIA DE CONSERVAÇÃO da SC/SUBEC/CGEC/2ªCRC, abaixo assinado, de acordo com o 
            <span class="campo-dinamico"><?php echo htmlspecialchars($notif['capitulacao_infracao']); ?></span>, 
            determina o(a) SR(A) PROPRIETÁRIO(A) OU RESPONSÁVEL pelo imóvel situado na 
            <span class="campo-dinamico"><?php echo mb_strtoupper($notif['logradouro'], 'UTF-8'); ?> - <?php echo mb_strtoupper($notif['bairro'], 'UTF-8'); ?></span> 
            que em obediência à presente NOTIFICAÇÃO, fica obrigado a, no prazo máximo de 
            <span class="campo-dinamico"><?php echo $notif['prazo_dias']; ?> DIAS</span> a contar do recebimento deste a, 
            <span class="campo-dinamico"><?php echo htmlspecialchars($notif['obrigacao']); ?></span>.
        </p>

        <p>
            Em caso de não observância a presente Notificação, será lavrado EDITAL com possibilidade de MULTA de acordo com o 
            <span class="campo-dinamico"><?php echo htmlspecialchars($notif['capitulacao_multa']); ?></span>.
        </p>

        <?php if(!empty($notif['protocolo_1746'])): ?>
        <p>Ref. Protocolo 1746: <strong><?php echo htmlspecialchars($notif['protocolo_1746']); ?></strong></p>
        <?php endif; ?>

        <div class="data-local">
            <?php echo $data_extenso; ?>
        </div>

        <div class="assinatura-gerente-box">
            _____________________________________________________<br>
            <strong>JORGE HENRIQUE F. S. MOREIRA</strong>
            <span>Mat. 283-850-06 / CREA-RJ 2012104458</span>
            <span>SC/SUBEC/CGEC/4ªCRC/22ª GC - Campo Grande Gerente II</span>
        </div>

        <div class="rodape-container">
            
            <div class="recibo-box">
                <span class="titulo-recibo">RECEBI O ORIGINAL</span>
                
                <div class="linha-input" style="width: 50%;">
                    <span class="label">Em,</span>
                    <div class="linha"></div>
                </div>
                
                <div class="linha-input">
                    <span class="label">Assinatura:</span>
                    <div class="linha"></div>
                </div>

                <div class="linha-input">
                    <span class="label">Nome Legível:</span>
                    <div class="linha"></div>
                </div>

                <div class="linha-input">
                    <span class="label">CNPJ ou CPF:</span>
                    <div class="linha"></div>
                </div>
            </div>

            <?php if (!empty($notif['qr_code_path'])): ?>
                <div class="qr-box">
                    <img src="qrcodes/<?php echo htmlspecialchars($notif['qr_code_path']); ?>" alt="QR Code">
                    <div class="qr-legenda">Saiba mais</div>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>