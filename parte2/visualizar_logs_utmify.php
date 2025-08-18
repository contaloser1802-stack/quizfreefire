<?php
$logDir = __DIR__ . '/logs';
$logFiles = [];

if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if (strpos($file, 'utmify-') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $logFiles[] = $file;
        }
    }
}

// Função para limpar logs
if (isset($_POST['clear_logs'])) {
    foreach ($logFiles as $file) {
        unlink($logDir . '/' . $file);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs UTMify - Monitoramento</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .controls {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .log-section {
            margin: 20px;
        }
        .log-file {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .log-header {
            background: #343a40;
            color: white;
            padding: 15px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .log-content {
            padding: 20px;
            background: #2d3748;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            display: none;
        }
        .log-content.show {
            display: block;
        }
        .no-logs {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-size: 1.2em;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success { background: #28a745; }
        .status-error { background: #dc3545; }
        .status-warning { background: #ffc107; }
        .temp-files {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .temp-files h3 {
            margin-top: 0;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Logs UTMify</h1>
            <p>Monitoramento de envio de dados para UTMify</p>
        </div>

        <div class="controls">
            <div>
                <a href="?" class="btn btn-primary">🔄 Atualizar</a>
                <a href="visualizar_clientes.php" class="btn btn-success">👥 Ver Clientes</a>
            </div>
            <form method="post" style="display: inline;">
                <button type="submit" name="clear_logs" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar todos os logs?')">🗑️ Limpar Logs</button>
            </form>
        </div>

        <?php
        // Verifica arquivos temporários
        $tempDir = __DIR__ . '/temp';
        $tempFiles = [];
        if (is_dir($tempDir)) {
            $files = scandir($tempDir);
            foreach ($files as $file) {
                if (strpos($file, 'transaction_') === 0) {
                    $tempFiles[] = $file;
                }
            }
        }

        if (!empty($tempFiles)) {
            echo '<div class="temp-files">';
            echo '<h3>📁 Arquivos Temporários (' . count($tempFiles) . ')</h3>';
            echo '<p>Transações aguardando confirmação de pagamento:</p>';
            echo '<ul>';
            foreach ($tempFiles as $file) {
                $filePath = $tempDir . '/' . $file;
                $data = json_decode(file_get_contents($filePath), true);
                $externalId = $data['external_id'] ?? 'N/A';
                $nome = $data['nome'] ?? 'N/A';
                echo '<li><strong>' . $externalId . '</strong> - ' . $nome . ' (' . date('H:i:s', filemtime($filePath)) . ')</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <div class="log-section">
            <?php if (empty($logFiles)): ?>
                <div class="no-logs">
                    <h3>📝 Nenhum log encontrado</h3>
                    <p>Os logs aparecerão aqui quando houver tentativas de envio para o UTMify.</p>
                    <p><strong>Dica:</strong> Faça um teste de pagamento para gerar logs.</p>
                </div>
            <?php else: ?>
                <?php foreach ($logFiles as $logFile): ?>
                    <?php
                    $logPath = $logDir . '/' . $logFile;
                    $logContent = file_get_contents($logPath);
                    $fileSize = filesize($logPath);
                    $lastModified = date('d/m/Y H:i:s', filemtime($logPath));
                    
                    // Determina status baseado no conteúdo
                    $status = 'warning';
                    $statusText = 'Processando';
                    if (strpos($logContent, '✅') !== false) {
                        $status = 'success';
                        $statusText = 'Sucesso';
                    } elseif (strpos($logContent, '❌') !== false) {
                        $status = 'error';
                        $statusText = 'Erro';
                    }
                    ?>
                    <div class="log-file">
                        <div class="log-header" onclick="toggleLog('<?php echo $logFile; ?>')">
                            <div>
                                <span class="status-indicator status-<?php echo $status; ?>"></span>
                                📄 <?php echo $logFile; ?>
                                <small>(<?php echo $statusText; ?>)</small>
                            </div>
                            <div>
                                <small><?php echo number_format($fileSize / 1024, 2); ?> KB | <?php echo $lastModified; ?></small>
                            </div>
                        </div>
                        <div class="log-content" id="log-<?php echo $logFile; ?>">
<?php echo htmlspecialchars($logContent); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleLog(logFile) {
            const content = document.getElementById('log-' + logFile);
            content.classList.toggle('show');
        }

        // Auto-refresh a cada 30 segundos
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>