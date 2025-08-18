<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Payloads UTMify</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
        }
        
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .log-entry {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .log-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-type {
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        
        .log-timestamp {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .log-content {
            padding: 20px;
        }
        
        .json-viewer {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        
        .utm-highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .utm-highlight h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .utm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .utm-item {
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
        }
        
        .utm-item strong {
            color: #856404;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.9);
            padding: 10px 15px;
            border-radius: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            font-size: 0.9em;
        }
        
        .no-logs {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-logs i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="auto-refresh">
        🔄 Auto-refresh: <span id="countdown">30</span>s
    </div>

    <div class="container">
        <div class="header">
            <h1>📊 Visualizador de Payloads UTMify</h1>
            <p>Monitoramento detalhado dos dados enviados para o UTMify</p>
        </div>
        
        <div class="content">
            <div class="filters">
                <div class="filter-group">
                    <label>Tipo de Log:</label>
                    <select id="typeFilter">
                        <option value="">Todos</option>
                        <option value="payload_raw_recebido">Payload Raw Recebido</option>
                        <option value="tracking_parameters_recebidos">UTMs Recebidos</option>
                        <option value="payload_final_utmify">Payload Final UTMify</option>
                        <option value="tracking_parameters_enviados">UTMs Enviados</option>
                        <option value="resposta_api_utmify">Resposta API</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Arquivo:</label>
                    <select id="fileFilter">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Data:</label>
                    <input type="date" id="dateFilter" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <button class="btn" onclick="loadLogs()">🔍 Filtrar</button>
                <button class="btn" onclick="clearLogs()">🗑️ Limpar Logs</button>
            </div>
            
            <div id="logsContainer">
                <!-- Logs serão carregados aqui -->
            </div>
        </div>
    </div>

    <script>
        let autoRefreshInterval;
        let countdown = 30;

        function loadLogs() {
            const typeFilter = document.getElementById('typeFilter').value;
            const fileFilter = document.getElementById('fileFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            fetch('?action=get_logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: typeFilter,
                    file: fileFilter,
                    date: dateFilter
                })
            })
            .then(response => response.json())
            .then(data => {
                displayLogs(data);
            })
            .catch(error => {
                console.error('Erro ao carregar logs:', error);
            });
        }

        function displayLogs(logs) {
            const container = document.getElementById('logsContainer');
            
            if (!logs || logs.length === 0) {
                container.innerHTML = `
                    <div class="no-logs">
                        <div style="font-size: 4em; margin-bottom: 20px; opacity: 0.5;">📄</div>
                        <h3>Nenhum log encontrado</h3>
                        <p>Não há logs para os filtros selecionados.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = logs.map(log => {
                let statusClass = 'status-success';
                if (log.type.includes('erro') || (log.data && log.data.http_code && log.data.http_code !== 200)) {
                    statusClass = 'status-error';
                } else if (log.type.includes('warning') || log.type.includes('nao_encontrado')) {
                    statusClass = 'status-warning';
                }
                
                let content = '';
                
                // Destaque especial para UTMs
                if (log.type.includes('tracking_parameters') && log.data) {
                    content += `
                        <div class="utm-highlight">
                            <h4>🎯 Parâmetros UTM</h4>
                            <div class="utm-grid">
                                <div class="utm-item"><strong>Source:</strong> ${log.data.utm_source || 'N/A'}</div>
                                <div class="utm-item"><strong>Campaign:</strong> ${log.data.utm_campaign || 'N/A'}</div>
                                <div class="utm-item"><strong>Medium:</strong> ${log.data.utm_medium || 'N/A'}</div>
                                <div class="utm-item"><strong>Content:</strong> ${log.data.utm_content || 'N/A'}</div>
                                <div class="utm-item"><strong>Term:</strong> ${log.data.utm_term || 'N/A'}</div>
                                <div class="utm-item"><strong>FBCLID:</strong> ${log.data.fbclid || 'N/A'}</div>
                            </div>
                        </div>
                    `;
                }
                
                content += `<div class="json-viewer">${JSON.stringify(log.data, null, 2)}</div>`;
                
                return `
                    <div class="log-entry">
                        <div class="log-header">
                            <div>
                                <span class="log-type">${log.type}</span>
                                <span class="status-badge ${statusClass}">
                                    ${log.data && log.data.http_code ? 'HTTP ' + log.data.http_code : 'OK'}
                                </span>
                            </div>
                            <span class="log-timestamp">${log.timestamp}</span>
                        </div>
                        <div class="log-content">
                            ${content}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function clearLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs JSON?')) {
                fetch('?action=clear_logs', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadLogs();
                        alert('Logs limpos com sucesso!');
                    }
                });
            }
        }

        function startAutoRefresh() {
            autoRefreshInterval = setInterval(() => {
                countdown--;
                document.getElementById('countdown').textContent = countdown;
                
                if (countdown <= 0) {
                    loadLogs();
                    countdown = 30;
                }
            }, 1000);
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadLogs();
            startAutoRefresh();
        });
    </script>
</body>
</html>

<?php
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_logs') {
        $input = json_decode(file_get_contents('php://input'), true);
        $date = $input['date'] ?? date('Y-m-d');
        $typeFilter = $input['type'] ?? '';
        $fileFilter = $input['file'] ?? '';
        
        $logs = [];
        
        // Carregar logs pendentes
        if ($fileFilter === '' || $fileFilter === 'pendente') {
            $pendenteFile = __DIR__ . '/logs/utmify-pendente-payloads-' . $date . '.json';
            if (file_exists($pendenteFile)) {
                $content = file_get_contents($pendenteFile);
                if (!empty($content)) {
                    $pendenteLogs = json_decode($content, true) ?: [];
                    foreach ($pendenteLogs as $log) {
                        $log['source'] = 'pendente';
                        $logs[] = $log;
                    }
                }
            }
        }
        
        // Carregar logs pagos
        if ($fileFilter === '' || $fileFilter === 'pago') {
            $pagoFile = __DIR__ . '/logs/utmify-pago-payloads-' . $date . '.json';
            if (file_exists($pagoFile)) {
                $content = file_get_contents($pagoFile);
                if (!empty($content)) {
                    $pagoLogs = json_decode($content, true) ?: [];
                    foreach ($pagoLogs as $log) {
                        $log['source'] = 'pago';
                        $logs[] = $log;
                    }
                }
            }
        }
        
        // Filtrar por tipo
        if (!empty($typeFilter)) {
            $logs = array_filter($logs, function($log) use ($typeFilter) {
                return strpos($log['type'], $typeFilter) !== false;
            });
        }
        
        // Ordenar por timestamp (mais recente primeiro)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        echo json_encode(array_values($logs));
        exit;
    }
    
    if ($_GET['action'] === 'clear_logs') {
        $date = date('Y-m-d');
        $files = [
            __DIR__ . '/logs/utmify-pendente-payloads-' . $date . '.json',
            __DIR__ . '/logs/utmify-pago-payloads-' . $date . '.json'
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}
?>