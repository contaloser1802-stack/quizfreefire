<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Clientes - Pagamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
        }
        .cliente-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .cliente-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .cliente-body {
            padding: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .info-section h4 {
            margin: 0 0 10px 0;
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-item strong {
            color: #495057;
        }
        .utm-params {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        .urls {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }
        .refresh-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background: #218838;
        }
        .clear-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .clear-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Clientes - Dados de Pagamento</h1>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <button class="refresh-btn" onclick="location.reload()">🔄 Atualizar</button>
            <button class="clear-btn" onclick="clearData()">🗑️ Limpar Dados</button>
        </div>

        <?php
        $jsonFile = 'clientes_pagamento.json';
        $clientes = [];
        
        if (file_exists($jsonFile)) {
            $clientes = json_decode(file_get_contents($jsonFile), true) ?: [];
        }
        
        if (empty($clientes)) {
            echo '<div class="no-data">Nenhum cliente registrado ainda.</div>';
        } else {
            // Estatísticas
            $totalClientes = count($clientes);
            $valorTotal = array_sum(array_column($clientes, 'pagamento')['valor'] ?? [0]);
            $utmsComDados = 0;
            
            foreach ($clientes as $cliente) {
                $hasUtm = false;
                foreach ($cliente['utm_parameters'] as $utm) {
                    if (!empty($utm)) {
                        $hasUtm = true;
                        break;
                    }
                }
                if ($hasUtm) $utmsComDados++;
            }
            
            echo '<div class="stats">';
            echo '<div class="stat-card"><h3>Total de Clientes</h3><div class="number">' . $totalClientes . '</div></div>';
            echo '<div class="stat-card"><h3>Valor Total (R$)</h3><div class="number">' . number_format($valorTotal / 100, 2, ',', '.') . '</div></div>';
            echo '<div class="stat-card"><h3>Com UTMs</h3><div class="number">' . $utmsComDados . '</div></div>';
            echo '<div class="stat-card"><h3>% com UTMs</h3><div class="number">' . round(($utmsComDados / $totalClientes) * 100, 1) . '%</div></div>';
            echo '</div>';
            
            // Lista de clientes (mais recentes primeiro)
            $clientes = array_reverse($clientes);
            
            foreach ($clientes as $index => $cliente) {
                echo '<div class="cliente-card">';
                echo '<div class="cliente-header">';
                echo '<strong>🕒 ' . $cliente['timestamp'] . ' | 👤 ' . htmlspecialchars($cliente['cliente']['nome']) . ' | 💰 R$ ' . $cliente['pagamento']['valor_real'] . '</strong>';
                echo '</div>';
                
                echo '<div class="cliente-body">';
                echo '<div class="info-grid">';
                
                // Dados do Cliente
                echo '<div class="info-section">';
                echo '<h4>👤 Dados do Cliente</h4>';
                echo '<div class="info-item"><strong>Nome:</strong> ' . htmlspecialchars($cliente['cliente']['nome']) . '</div>';
                echo '<div class="info-item"><strong>Email:</strong> ' . htmlspecialchars($cliente['cliente']['email']) . '</div>';
                echo '<div class="info-item"><strong>Telefone:</strong> ' . htmlspecialchars($cliente['cliente']['telefone']) . '</div>';
                echo '<div class="info-item"><strong>CPF:</strong> ' . htmlspecialchars($cliente['cliente']['document']) . '</div>';
                echo '<div class="info-item"><strong>ID Pedido:</strong> ' . htmlspecialchars($cliente['external_id']) . '</div>';
                echo '</div>';
                
                // URLs
                echo '<div class="info-section urls">';
                echo '<h4>🔗 URLs</h4>';
                echo '<div class="info-item"><strong>URL Atual:</strong><br><small>' . htmlspecialchars($cliente['urls']['url_atual']) . '</small></div>';
                echo '<div class="info-item"><strong>URL Origem:</strong><br><small>' . htmlspecialchars($cliente['urls']['url_origem']) . '</small></div>';
                echo '</div>';
                
                // Parâmetros UTM
                echo '<div class="info-section utm-params">';
                echo '<h4>📈 Parâmetros UTM</h4>';
                $hasUtmData = false;
                foreach ($cliente['utm_parameters'] as $key => $value) {
                    if (!empty($value)) {
                        echo '<div class="info-item"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</div>';
                        $hasUtmData = true;
                    }
                }
                if (!$hasUtmData) {
                    echo '<div class="info-item" style="color: #6c757d; font-style: italic;">Nenhum parâmetro UTM encontrado</div>';
                }
                echo '</div>';
                
                // Informações do Servidor
                echo '<div class="info-section">';
                echo '<h4>🖥️ Informações Técnicas</h4>';
                echo '<div class="info-item"><strong>IP:</strong> ' . htmlspecialchars($cliente['server_info']['ip']) . '</div>';
                echo '<div class="info-item"><strong>Método:</strong> ' . htmlspecialchars($cliente['server_info']['method']) . '</div>';
                echo '<div class="info-item"><strong>User Agent:</strong><br><small>' . htmlspecialchars($cliente['server_info']['user_agent']) . '</small></div>';
                echo '</div>';
                
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
        ?>
    </div>

    <script>
        function clearData() {
            if (confirm('Tem certeza que deseja limpar todos os dados? Esta ação não pode ser desfeita.')) {
                fetch('limpar_clientes.php', {
                    method: 'POST'
                }).then(() => {
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>