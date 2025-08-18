<?php
header('Content-Type: application/json');

$utmifyApiUrl = "https://api.utmify.com.br/api-credentials/orders";
$utmifyToken = "oPNL0wMgnj3jgDWfRa5WTGvkY1zZE5tkagKA";

$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/utmify-pago-' . date('Y-m-d') . '.log';
$jsonLogFile = $logDir . '/utmify-pago-payloads-' . date('Y-m-d') . '.json';

function writeLog($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    if ($data !== null) {
        $logMessage .= "Dados: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    $logMessage .= "----------------------------------------\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function writeJsonLog($type, $data) {
    global $jsonLogFile;
    $timestamp = gmdate('Y-m-d H:i:s');
    
    $logEntry = [
        'timestamp' => $timestamp,
        'type' => $type,
        'data' => $data
    ];
    
    // Lê logs existentes
    $existingLogs = [];
    if (file_exists($jsonLogFile)) {
        $content = file_get_contents($jsonLogFile);
        if (!empty($content)) {
            $existingLogs = json_decode($content, true) ?: [];
        }
    }
    
    // Adiciona novo log
    $existingLogs[] = $logEntry;
    
    // Salva de volta
    file_put_contents($jsonLogFile, json_encode($existingLogs, JSON_PRETTY_PRINT));
}

try {
    $rawData = file_get_contents('php://input');
    writeLog("📥 PAYLOAD RAW RECEBIDO (PAGO)", ['raw_data' => $rawData]);
    writeJsonLog("payload_raw_recebido_pago", ['raw_data' => $rawData]);

    $inputData = json_decode($rawData, true);
    if (!$inputData) {
        throw new Exception("Dados JSON inválidos");
    }

    writeLog("🔄 PAYLOAD DECODIFICADO (PAGO)", $inputData);
    writeJsonLog("payload_decodificado_pago", $inputData);
    
    // Log específico dos trackingParameters recebidos
    if (isset($inputData['trackingParameters'])) {
        writeLog("🎯 TRACKING PARAMETERS RECEBIDOS (PAGO)", $inputData['trackingParameters']);
        writeJsonLog("tracking_parameters_recebidos_pago", $inputData['trackingParameters']);
    } else {
        writeLog("⚠️ NENHUM TRACKING PARAMETERS ENCONTRADO NO PAYLOAD (PAGO)");
        writeJsonLog("tracking_parameters_nao_encontrado_pago", null);
    }
    
    // Log de todos os campos UTM individualmente
    $utmFields = ['utm_source', 'utm_campaign', 'utm_medium', 'utm_content', 'utm_term', 'src', 'sck', 'xcod', 'fbclid', 'gclid', 'ttclid'];
    $utmLog = [];
    foreach ($utmFields as $field) {
        $utmLog[$field] = $inputData['trackingParameters'][$field] ?? 'NÃO ENCONTRADO';
    }
    writeLog("🔍 ANÁLISE INDIVIDUAL DOS CAMPOS UTM (PAGO)", $utmLog);
    writeJsonLog("analise_campos_utm_pago", $utmLog);

    $utmifyData = [
        'orderId' => $inputData['external_id'],
        'platform' => 'BuckPay',
        'paymentMethod' => 'pix',
        'status' => 'paid',
        'createdAt' => $inputData['createdAt'] ?? gmdate('Y-m-d H:i:s'),
        'approvedDate' => gmdate('Y-m-d H:i:s'),
        'refundedAt' => null,
        'customer' => [
            'name' => $inputData['buyer']['name'],
            'email' => $inputData['buyer']['email'],
            'phone' => $inputData['buyer']['phone'] ?? null,
            'document' => $inputData['buyer']['document'],
            'country' => 'BR',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ],
        'products' => [
            [
                'id' => $inputData['product']['id'],
                'name' => $inputData['product']['name'],
                'planId' => null,
                'planName' => null,
                'quantity' => $inputData['offer']['quantity'] ?? 1,
                'priceInCents' => $inputData['amount']
            ]
        ],
        'trackingParameters' => [
            'src' => $inputData['trackingParameters']['src'] ?? null,
            'sck' => $inputData['trackingParameters']['sck'] ?? null,
            'utm_source' => $inputData['trackingParameters']['utm_source'] ?? null,
            'utm_campaign' => $inputData['trackingParameters']['utm_campaign'] ?? null,
            'utm_medium' => $inputData['trackingParameters']['utm_medium'] ?? null,
            'utm_content' => $inputData['trackingParameters']['utm_content'] ?? null,
            'utm_term' => $inputData['trackingParameters']['utm_term'] ?? null,
            'xcod' => $inputData['trackingParameters']['xcod'] ?? null,
            'fbclid' => $inputData['trackingParameters']['fbclid'] ?? null,
            'gclid' => $inputData['trackingParameters']['gclid'] ?? null,
            'ttclid' => $inputData['trackingParameters']['ttclid'] ?? null
        ],
        'commission' => [
            'totalPriceInCents' => $inputData['amount'],
            'gatewayFeeInCents' => 0,
            'userCommissionInCents' => $inputData['amount']
        ],
        'isTest' => false
    ];

    writeLog("📤 PAYLOAD FINAL PARA UTMIFY (PAGO)", $utmifyData);
    writeJsonLog("payload_final_utmify_pago", $utmifyData);
    
    // Log específico dos trackingParameters que serão enviados
    writeLog("🎯 TRACKING PARAMETERS QUE SERÃO ENVIADOS (PAGO)", $utmifyData['trackingParameters']);
    writeJsonLog("tracking_parameters_enviados_pago", $utmifyData['trackingParameters']);
    
    // Log do JSON que será enviado
    $jsonPayload = json_encode($utmifyData);
    writeLog("📦 JSON FINAL PARA ENVIO (PAGO)", ['json_payload' => $jsonPayload]);
    writeJsonLog("json_final_envio_pago", ['json_payload' => $jsonPayload]);

    $ch = curl_init($utmifyApiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-token: $utmifyToken"
        ],
        CURLOPT_POSTFIELDS => json_encode($utmifyData)
    ]);

    writeLog("📡 Enviando requisição para Utmify", [
        'url' => $utmifyApiUrl,
        'headers' => [
            'Content-Type: application/json',
            'x-api-token: [REDACTED]'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        writeLog("❌ Erro CURL", ['error' => curl_error($ch)]);
        throw new Exception("Erro ao enviar dados para Utmify: " . curl_error($ch));
    }
    
    curl_close($ch);

    $responseData = [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
    
    writeLog("✅ Resposta da API Utmify", $responseData);
    writeJsonLog("resposta_api_utmify_pago", $responseData);

    if ($httpCode !== 200) {
        throw new Exception("Erro na API Utmify. HTTP Code: $httpCode");
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Dados enviados com sucesso para Utmify'
    ]);

} catch (Exception $e) {
    writeLog("❌ Erro", ['message' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>