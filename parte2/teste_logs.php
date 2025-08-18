<?php
// Teste para verificar se os logs estão sendo lidos corretamente

$date = date('Y-m-d');
echo "Data atual: $date\n\n";

// Verificar arquivo pendente
$pendenteFile = __DIR__ . '/logs/utmify-pendente-payloads-' . $date . '.json';
echo "Arquivo pendente: $pendenteFile\n";
echo "Existe: " . (file_exists($pendenteFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($pendenteFile)) {
    $content = file_get_contents($pendenteFile);
    echo "Tamanho do arquivo: " . strlen($content) . " bytes\n";
    echo "Conteúdo vazio: " . (empty($content) ? 'SIM' : 'NÃO') . "\n";
    
    $logs = json_decode($content, true);
    echo "JSON válido: " . ($logs !== null ? 'SIM' : 'NÃO') . "\n";
    echo "Número de logs: " . (is_array($logs) ? count($logs) : 0) . "\n\n";
    
    if (is_array($logs) && count($logs) > 0) {
        echo "Primeiro log:\n";
        print_r($logs[0]);
    }
} else {
    echo "Arquivo não encontrado!\n";
}

// Verificar arquivo pago
$pagoFile = __DIR__ . '/logs/utmify-pago-payloads-' . $date . '.json';
echo "\n\nArquivo pago: $pagoFile\n";
echo "Existe: " . (file_exists($pagoFile) ? 'SIM' : 'NÃO') . "\n";

// Simular a requisição AJAX
echo "\n\n=== SIMULANDO REQUISIÇÃO AJAX ===\n";

$logs = [];

// Carregar logs pendentes
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

// Carregar logs pagos
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

echo "Total de logs carregados: " . count($logs) . "\n";

if (count($logs) > 0) {
    echo "Tipos de logs encontrados:\n";
    foreach ($logs as $log) {
        echo "- " . $log['type'] . " (" . $log['timestamp'] . ")\n";
    }
}

echo "\n\nJSON final que seria retornado:\n";
echo json_encode($logs, JSON_PRETTY_PRINT);
?>